<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BookTocItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Owns the Table of Contents for a book: seeding it from the PDF's own
 * bookmarks, persisting an editor's ordered tree, and shaping the nested
 * payload the reader consumes.
 */
class BookTocService
{
    public function __construct(
        private PdfOutlineParser $outlineParser,
    ) {}

    /**
     * Replace a book's TOC with whatever bookmarks the uploaded PDF carries.
     * Returns the number of entries written (0 when the document has none, or
     * is scanned / uses compressed object streams the parser cannot read).
     */
    public function extractFromPdf(Book $book): int
    {
        $items = $book->file_path
            ? $this->outlineParser->extract($this->readDocument($book))
            : [];

        $this->replace($book, $items);

        return count($items);
    }

    /**
     * Persist a flat, ordered list of TOC entries as a parent/child tree.
     * Entries are matched to parents by their `parent_id` (client id) or, when
     * absent, by the `depth` of the preceding entry. All existing rows are
     * dropped first so the client can send the complete document.
     *
     * @param  array<int, array{title: string, title_en?: ?string, page: int, depth?: int, parent_index?: ?int}>  $items
     * @return Collection<int, BookTocItem>
     */
    public function replace(Book $book, array $items): Collection
    {
        $book->tocItems()->delete();

        $created = collect();
        $idByIndex = [];

        foreach (array_values($items) as $index => $item) {
            $parentId = null;

            if (isset($item['parent_index']) && $item['parent_index'] !== null) {
                $parentId = $idByIndex[$item['parent_index']] ?? null;
            }

            $depth = isset($item['depth'])
                ? max(1, (int) $item['depth'])
                : ($parentId !== null ? $this->depthOf($created, $parentId) + 1 : 1);

            $tocItem = $book->tocItems()->create([
                'parent_id' => $parentId,
                'title' => trim((string) $item['title']),
                'title_en' => isset($item['title_en']) ? trim((string) $item['title_en']) ?: null : null,
                'page' => max(1, (int) $item['page']),
                'position' => $index,
                'depth' => $depth,
            ]);

            $created->push($tocItem);
            $idByIndex[$index] = $tocItem->id;
        }

        return $created;
    }

    /**
     * Nested TOC for the reader. Every entry is flagged `locked` when the
     * viewer is on a preview-only scope and the target page sits past the free
     * preview: the client hides the page number and refuses to navigate, but
     * the entry title still shows so the structure is visible.
     *
     * @param  'full'|'preview'|'none'  $scope
     * @return array<int, array<string, mixed>>
     */
    public function tree(Book $book, string $scope = 'full'): array
    {
        $items = $book->tocItems()->get();
        $previewPages = $scope === 'preview' ? max(1, (int) $book->preview_pages) : null;

        return $this->buildTree($items, null, $previewPages);
    }

    /**
     * @param  Collection<int, BookTocItem>  $items
     * @return array<int, array<string, mixed>>
     */
    private function buildTree(Collection $items, ?int $parentId, ?int $previewPages): array
    {
        return $items
            ->where('parent_id', $parentId)
            ->sortBy('position')
            ->values()
            ->map(function (BookTocItem $item) use ($items, $previewPages) {
                $locked = $previewPages !== null && $item->page > $previewPages;

                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'title_en' => $item->title_en,
                    'page' => $item->page,
                    'depth' => $item->depth,
                    'locked' => $locked,
                    'children' => $this->buildTree($items, $item->id, $previewPages),
                ];
            })
            ->all();
    }

    private function depthOf(Collection $created, int $id): int
    {
        $item = $created->firstWhere('id', $id);

        return $item instanceof BookTocItem ? $item->depth : 1;
    }

    private function readDocument(Book $book): string
    {
        $disk = Storage::disk($book->fileStorageDisk());

        if (! $book->file_path || ! $disk->exists($book->file_path)) {
            return '';
        }

        return (string) $disk->get($book->file_path);
    }
}

<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Services\BookTocService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminBookTocController extends Controller
{
    public function __construct(
        private BookTocService $tocService,
    ) {}

    /**
     * Flat, ordered list of a book's TOC entries for the admin editor. A flat
     * shape (with `parent_id` + `depth`) round-trips an edit more simply than a
     * nested tree and matches what the client posts back.
     */
    public function show(Book $book): JsonResponse
    {
        $items = $book->tocItems()
            ->orderBy('position')
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'parent_id' => $item->parent_id,
                'title' => $item->title,
                'title_en' => $item->title_en,
                'page' => $item->page,
                'position' => $item->position,
                'depth' => $item->depth,
            ])
            ->values();

        return response()->json(['toc' => $items]);
    }

    /**
     * Rebuild a book's TOC from the PDF's embedded bookmarks. Reports how many
     * entries were found so the client can distinguish "no bookmarks in this
     * PDF" (scanned documents usually have none) from a hard failure.
     */
    public function extract(Book $book): JsonResponse
    {
        if (! $book->file_path) {
            return response()->json([
                'message' => 'This book has no document to extract a table of contents from.',
                'extracted' => 0,
            ], 422);
        }

        $count = $this->tocService->extractFromPdf($book);

        return response()->json([
            'message' => $count > 0
                ? "Extracted {$count} table of contents entries from the PDF."
                : 'No bookmarks were found in this PDF. This is common for scanned documents - add entries manually.',
            'extracted' => $count,
            'toc' => $book->tocItems()->orderBy('position')->get(),
        ]);
    }

    /**
     * Replace the whole TOC. The client owns ordering; `position` is derived
     * from array order and `parent_id` links children to their parent entry.
     */
    public function update(Request $request, Book $book): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['present', 'array'],
            'items.*.title' => ['required', 'string', 'max:255'],
            'items.*.title_en' => ['nullable', 'string', 'max:255'],
            'items.*.page' => ['required', 'integer', 'min:1'],
            'items.*.parent_index' => ['nullable', 'integer', 'min:0'],
            'items.*.depth' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        $items = $this->resolveParentIndexes($validated['items']);

        $this->tocService->replace($book, $items);

        return response()->json([
            'message' => 'Table of contents updated.',
            'toc' => $book->tocItems()->orderBy('position')->get(),
        ]);
    }

    public function destroy(Book $book): JsonResponse
    {
        $book->tocItems()->delete();

        return response()->json(['message' => 'Table of contents cleared.']);
    }

    /**
     * The editor sends entries either with a local `parent_id` reference into
     * the same payload (rebuilt on save) or with only a depth. Normalise both
     * to the `parent_index` the service expects.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function resolveParentIndexes(array $items): array
    {
        $items = array_values($items);
        $indexById = [];

        foreach ($items as $index => $item) {
            if (isset($item['id'])) {
                $indexById[$item['id']] = $index;
            }
        }

        foreach ($items as $index => &$item) {
            if (array_key_exists('parent_index', $item) && $item['parent_index'] !== null) {
                continue;
            }

            $item['parent_index'] = isset($item['parent_id'], $indexById[$item['parent_id']])
                ? $indexById[$item['parent_id']]
                : null;
        }

        unset($item);

        return $items;
    }
}

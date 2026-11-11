<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use App\Services\AccessService;
use App\Services\BookStorageService;
use App\Services\ReadingProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BookController extends Controller
{
    public function __construct(
        private BookStorageService $storage,
        private AccessService $accessService,
        private ReadingProgressService $readingProgress,
    ) {}

    /**
     * Resolve a supported locale from the request for category display names.
     */
    private function resolveDisplayLocale(Request $request): string
    {
        $locale = (string) $request->input('locale', app()->getLocale());

        return in_array($locale, Category::SUPPORTED_LOCALES, true) ? $locale : app()->getLocale();
    }

    /**
     * Attach a locale-aware display_name to the loaded category relation.
     */
    private function withLocalizedCategory(Book $book, string $locale): Book
    {
        if ($book->category) {
            $book->category->setAttribute('display_name', $book->category->translatedName($locale));
        }

        return $book;
    }

    /**
     * Admin listing: returns ALL books (published + drafts) with category.
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $locale = $this->resolveDisplayLocale($request);

        $query = Book::query()->with(['category' => fn ($q) => $q->with('translations')]);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('author', 'like', "%{$search}%");
            });
        }

        if ($category = $request->input('category')) {
            $query->where('category_id', $category);
        }

        $sort = $request->input('sort', 'latest');
        $query->when($sort === 'latest', fn ($q) => $q->latest())
            ->when($sort === 'price_asc', fn ($q) => $q->orderBy('price'))
            ->when($sort === 'price_desc', fn ($q) => $q->orderByDesc('price'));

        $books = $query->paginate((int) $request->input('per_page', 12))->withQueryString();

        $this->readingProgress->attachTo($books, auth('sanctum')->id());

        foreach ($books as $book) {
            $this->withLocalizedCategory($book, $locale);
        }

        return response()->json($books);
    }

    public function index(Request $request): JsonResponse
    {
        $locale = $this->resolveDisplayLocale($request);

        $query = Book::query()
            ->with(['category' => fn ($q) => $q->with('translations')])
            ->withAvg('reviews', 'rating')
            ->published();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('author', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($category = $request->input('category')) {
            $categoryIds = collect(explode(',', $category))
                ->map(fn ($value) => trim($value))
                ->filter(fn ($value) => $value !== '')
                ->values();

            if ($request->boolean('include_children')) {
                $categoryIds = $this->expandCategoryIds($categoryIds);
            }

            if ($categoryIds->isNotEmpty()) {
                $query->whereIn('category_id', $categoryIds);
            }
        }

        if ($request->input('free') === '1') {
            $query->where('is_free', true);
        }

        if ($request->input('free') === '0') {
            $query->where('is_free', false);
        }

        if ($request->input('vip') === '1') {
            $query->where('is_vip_only', true);
        }

        if ($audience = $request->input('audience')) {
            if (in_array($audience, ['student', 'teacher', 'general'], true)) {
                $query->whereIn('audience', [$audience, 'general']);
            }
        }

        if ($request->input('language')) {
            $query->where('language', $request->input('language'));
        }

        // "Purchased only" is per viewer, so a guest asking for it gets an
        // empty shelf rather than somebody else's purchases.
        $userId = auth('sanctum')->id();

        if ($request->boolean('owned')) {
            $userId === null
                ? $query->whereRaw('1 = 0')
                : $query->purchasedBy($userId);
        }

        $sort = $request->input('sort', 'latest');
        // "Latest" on a curated catalogue means "newest to arrive on the
        // shelf", i.e. when the book went live - not when the author uploaded
        // it. Under the review workflow a book can sit as a draft for weeks
        // before being published, so ordering by the upload date would bury
        // exactly the books this listing exists to surface. Books that predate
        // the workflow have no publication date, so they fall back to the
        // upload date instead of bunching together at one end.
        $query->when($sort === 'latest', fn ($q) => $q->orderByRaw('COALESCE(published_at, created_at) DESC'))
            ->when($sort === 'price_asc', fn ($q) => $q->orderBy('price'))
            ->when($sort === 'price_desc', fn ($q) => $q->orderByDesc('price'))
            ->when($sort === 'popular', fn ($q) => $q->orderByDesc('view_count'))
            ->orderByDesc('id');

        $books = $query->paginate((int) $request->input('per_page', 12))->withQueryString();

        $this->readingProgress->attachTo($books, $userId);
        $this->accessService->attachPurchaseFlags($books, $userId);

        $items = collect($books->items())->map(fn ($book) => $this->withLocalizedCategory($book, $locale));

        return response()->json([
            'current_page' => $books->currentPage(),
            'data' => $items->all(),
            'from' => $books->firstItem(),
            'last_page' => $books->lastPage(),
            'next_page_url' => $books->nextPageUrl(),
            'path' => $books->path(),
            'per_page' => (string) $books->perPage(),
            'prev_page_url' => $books->previousPageUrl(),
            'to' => $books->lastItem(),
            'total' => $books->total(),
        ]);
    }

    /**
     * Public platform statistics for the catalogue page. Only real,
     * aggregated numbers are exposed - no fabricated figures.
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'stats' => [
                'books' => (int) Book::published()->count(),
                'free_books' => (int) Book::published()->where('is_free', true)->count(),
                'categories' => (int) Category::whereHas('books', fn ($q) => $q->published())->count(),
                'members' => (int) User::count(),
                'languages' => Book::published()
                    ->select('language')
                    ->distinct()
                    ->orderBy('language')
                    ->pluck('language')
                    ->values(),
            ],
        ]);
    }

    /**
     * Distinct author names for dropdown selection in the admin book form.
     * Managed authors table first, then legacy names still used on books.
     */
    public function authors(): JsonResponse
    {
        $managed = Author::query()
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->orderBy('name')
            ->pluck('name')
            ->all();

        $legacy = Book::query()
            ->whereNotNull('author')
            ->where('author', '!=', '')
            ->whereNotIn('author', $managed)
            ->distinct()
            ->orderBy('author')
            ->pluck('author')
            ->all();

        return response()->json(['authors' => array_values(array_unique(array_merge($managed, $legacy)))]);
    }

    public function show(Request $request, Book $book): JsonResponse
    {
        abort_unless($book->is_published, 404);

        $locale = $this->resolveDisplayLocale($request);

        $book->load(['category' => fn ($q) => $q->with('translations')]);
        $this->withLocalizedCategory($book, $locale);
        $book->loadCount('reviews');
        $book->loadAvg('reviews', 'rating');

        $user = auth('sanctum')->user();
        $scope = $this->accessService->readScope($user, $book);

        $access = null;
        if ($user) {
            $access = [
                'can_read' => $scope === 'full',
                'owned' => ! $book->is_free && $this->accessService->hasAccess($user, $book->id),
                'mode' => $scope,
                'preview_pages' => $scope === 'preview' ? max(1, (int) $book->preview_pages) : null,
                'preview_mode' => $scope === 'preview' ? $book->preview_mode : null,
            ];
        }

        // The detail endpoint is public, so the stored text must never leak
        // beyond what the viewer's scope allows.
        $book->setAttribute(
            'content',
            $this->accessService->visibleContent($book, $scope, (string) $book->content)
        );

        $related = Book::where('category_id', $book->category_id)
            ->where('id', '!=', $book->id)
            ->published()
            ->with(['category' => fn ($q) => $q->with('translations')])
            ->latest('id')
            ->take(4)
            ->get()
            ->map(fn ($item) => $this->withLocalizedCategory($item, $locale));

        return response()->json([
            'book' => $book,
            'related' => $related->values(),
            'ratings' => [
                'avg' => round((float) $book->reviews_avg_rating, 2) ?: 0,
                'count' => (int) $book->reviews_count,
            ],
            'reviews' => $book->reviews()
                ->with('user:id,name')
                ->latest()
                ->limit(5)
                ->get(),
            'access' => $access,
        ]);
    }

    public function recordView(Book $book): JsonResponse
    {
        abort_unless($book->is_published, 404);

        $book->increment('view_count');

        return response()->json([
            'view_count' => (int) $book->view_count,
        ]);
    }

    /**
     * Single-book fetch for the admin (drafts included), mirroring adminIndex.
     */
    public function adminShow(Book $book): JsonResponse
    {
        return response()->json([
            'book' => $book->load('category'),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'author_id' => ['nullable', 'exists:authors,id'],
            'author' => ['nullable', 'string', 'max:255'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'education_level' => ['nullable', 'string', 'max:255'],
            'grade' => ['nullable', 'string', 'max:255'],
            'publication_date' => ['nullable', 'date'],
            'audience' => ['nullable', 'string', 'in:student,teacher,general'],
            'cover_image' => ['nullable', 'image', 'mimes:'.implode(',', config('books.cover_mimes')), 'max:'.config('books.max_cover_kilobytes')],
            'file' => ['nullable', 'file', 'mimes:'.implode(',', config('books.file_mimes')), 'max:'.config('books.max_file_kilobytes')],
            'language' => ['nullable', 'string', 'max:10'],
            'pages' => ['nullable', 'integer', 'min:0'],
            'preview_pages' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'preview_mode' => ['nullable', 'string', Rule::in(Book::previewModes())],
            'price' => ['nullable', 'numeric', 'min:0'],
            'is_free' => ['nullable', 'boolean'],
            'is_vip_only' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
            'is_coming_soon' => ['nullable', 'boolean'],
        ]);

        $data = $this->toBookArray($validated);

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $this->storage->storeCover($request->file('cover_image'));
            $data['cover_disk'] = $this->storage->coverDisk();
        }

        if ($request->hasFile('file')) {
            $stored = $this->storage->storeDocument($request->file('file'), $data['language'] ?? 'km');
            $data = array_merge($data, array_diff_key($stored, ['pages' => true]));

            if (! array_key_exists('pages', $data) && $stored['pages'] !== null) {
                $data['pages'] = $stored['pages'];
            }
        }

        $this->applyAuthor($data, $validated['author'] ?? null, $validated['author_id'] ?? null);

        $data = $this->syncWorkflowStatus($data, $data['is_published'] ?? null);

        $book = Book::create($data);

        return response()->json(['book' => $book->load('category')], 201);
    }

    public function update(Request $request, Book $book): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['sometimes', 'exists:categories,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'author_id' => ['sometimes', 'nullable', 'exists:authors,id'],
            'author' => ['nullable', 'string', 'max:255'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'education_level' => ['nullable', 'string', 'max:255'],
            'grade' => ['nullable', 'string', 'max:255'],
            'publication_date' => ['nullable', 'date'],
            'audience' => ['nullable', 'string', 'in:student,teacher,general'],
            'cover_image' => ['nullable', 'image', 'mimes:'.implode(',', config('books.cover_mimes')), 'max:'.config('books.max_cover_kilobytes')],
            'file' => ['nullable', 'file', 'mimes:'.implode(',', config('books.file_mimes')), 'max:'.config('books.max_file_kilobytes')],
            'language' => ['nullable', 'string', 'max:10'],
            'pages' => ['nullable', 'integer', 'min:0'],
            'preview_pages' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'preview_mode' => ['nullable', 'string', Rule::in(Book::previewModes())],
            'price' => ['nullable', 'numeric', 'min:0'],
            'is_free' => ['nullable', 'boolean'],
            'is_vip_only' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
            'is_coming_soon' => ['nullable', 'boolean'],
        ]);

        $data = $this->toBookArray($validated);

        $oldFilePath = $book->file_path;
        $oldFileDisk = $book->file_disk;
        $oldCoverPath = $book->cover_image;
        $oldCoverDisk = $book->cover_disk;

        $newCoverPath = null;
        $newFileMeta = null;

        if ($request->hasFile('cover_image')) {
            $newCoverPath = $this->storage->storeCover($request->file('cover_image'));
            $data['cover_image'] = $newCoverPath;
            $data['cover_disk'] = $this->storage->coverDisk();
        }

        if ($request->hasFile('file')) {
            $newFileMeta = $this->storage->storeDocument(
                $request->file('file'),
                $data['language'] ?? $book->language
            );
            $data = array_merge($data, array_diff_key($newFileMeta, ['pages' => true]));

            if (! array_key_exists('pages', $data) && $newFileMeta['pages'] !== null) {
                $data['pages'] = $newFileMeta['pages'];
            }
        }

        if (isset($data['title']) && $data['title'] !== $book->title) {
            $data['slug'] = Str::slug($data['title']).'-'.Str::lower(Str::random(4));
        }

        $this->applyAuthor($data, $validated['author'] ?? null, $validated['author_id'] ?? null);

        $data = $this->syncWorkflowStatus($data, $validated['is_published'] ?? null, $book);

        $book->update($data);

        if ($newFileMeta !== null) {
            $this->storage->deleteDocument($oldFilePath, $oldFileDisk);
        }

        if ($newCoverPath !== null) {
            $this->storage->deleteCover($oldCoverPath, $oldCoverDisk);
        }

        return response()->json(['book' => $book->fresh()->load('category')]);
    }

    /**
     * Keep the editorial workflow status in step with the legacy `is_published`
     * flag the admin book form still sends.
     *
     * An admin publishing a book outright is a legitimate shortcut past the
     * review queue, so `true` becomes `published`. Un-publishing an already
     * live book returns it to `approved` rather than all the way back to
     * `draft`, so it keeps the credit of having passed review and can be
     * re-published without another round trip. Anything else is a draft.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function syncWorkflowStatus(array $data, mixed $isPublished, ?Book $book = null): array
    {
        if ($isPublished === null) {
            return $data;
        }

        if ($isPublished) {
            $data['status'] = Book::STATUS_PUBLISHED;
        } elseif ($book !== null && $book->status === Book::STATUS_PUBLISHED) {
            $data['status'] = Book::STATUS_APPROVED;
        } elseif ($book !== null && $book->is_published) {
            $data['status'] = Book::STATUS_PUBLISHED;
        } else {
            $data['status'] = Book::STATUS_DRAFT;
        }

        return $data;
    }

    public function destroy(Book $book): JsonResponse
    {
        $book->delete();

        $this->storage->deleteCover($book->cover_image, $book->cover_disk);
        $this->storage->deleteDocument($book->file_path, $book->file_disk);

        return response()->json(['message' => 'Book deleted.']);
    }

    /**
     * Read-access details for a single published book: whether the current
     * user (or guest) can open the document in full, owns it, may read a free
     * preview, and which pricing model applies. The reader UI consumes this
     * before rendering the download.
     */
    public function access(Request $request, Book $book): JsonResponse
    {
        abort_unless($book->is_published, 404);

        $user = auth('sanctum')->user();
        $scope = $this->accessService->readScope($user, $book);

        return response()->json([
            'book_id' => $book->id,
            'authenticated' => $user !== null,
            'can_read' => $scope === 'full',
            'mode' => $scope,
            'preview_pages' => $scope === 'preview' ? max(1, (int) $book->preview_pages) : null,
            'preview_mode' => $scope === 'preview' ? $book->preview_mode : null,
            'owned' => $user !== null ? $this->accessService->hasAccess($user, $book->id) : false,
            'is_free' => (bool) $book->is_free,
            'is_vip_only' => (bool) $book->is_vip_only,
            'has_file' => $book->file_path !== null,
        ]);
    }

    /**
     * Expand a list of category ids to include every descendant, so requesting
     * a parent (e.g. Math) also returns books filed under its children.
     *
     * @param  Collection<int, mixed>  $categoryIds
     * @return Collection<int, mixed>
     */
    private function expandCategoryIds($categoryIds)
    {
        $all = $categoryIds->all();
        $frontier = $categoryIds->all();

        while ($frontier !== []) {
            $frontier = Category::query()
                ->whereIn('parent_id', $frontier)
                ->pluck('id')
                ->all();

            $all = array_merge($all, $frontier);
        }

        return collect(array_values(array_unique($all)));
    }

    private function toBookArray(array $validated): array
    {
        $map = [
            'category_id', 'title', 'description', 'content', 'author', 'language',
            'pages', 'preview_pages', 'preview_mode', 'price', 'is_free', 'is_published', 'is_vip_only', 'is_coming_soon',
            'publisher', 'subject', 'education_level', 'grade', 'publication_date', 'audience',
        ];

        $data = array_intersect_key($validated, array_flip($map));

        // The column is a plain count; an absent/null preview means "no free
        // preview" rather than an unknown state.
        if (array_key_exists('preview_pages', $data)) {
            $data['preview_pages'] = max(0, (int) $data['preview_pages']);
        }

        // Keep the gate type honest: a zero page count means the gate is off,
        // and an unrecognised value falls back to "no gate". Only touched when
        // the caller actually sent a mode, so unrelated updates never clobber it.
        if (array_key_exists('preview_mode', $data)) {
            $mode = $data['preview_mode'];
            $pages = (int) ($data['preview_pages'] ?? 0);

            $data['preview_mode'] = $pages > 0 && in_array($mode, Book::previewModes(), true)
                ? $mode
                : Book::PREVIEW_MODE_NONE;
        }

        if (isset($data['title'])) {
            $data['slug'] = Str::slug($data['title']).'-'.Str::lower(Str::random(4));
        }

        return $data;
    }

    /**
     * Resolve the author for a book by id (preferred) or by name and apply
     * both the author_id foreign key and the legacy author display string.
     * When neither is provided the book keeps whatever author it already has.
     *
     * @param  array<string, mixed>  $data
     */
    private function applyAuthor(array &$data, ?string $name, ?int $authorId): void
    {
        if ($authorId) {
            $author = Author::find($authorId);
            if ($author) {
                $data['author_id'] = $author->id;
                $data['author'] = $author->name;

                return;
            }
        }

        $name = trim((string) $name);
        if ($name === '') {
            return;
        }

        $author = Author::firstOrCreate(['name' => $name]);
        $data['author_id'] = $author->id;
        $data['author'] = $author->name;
    }
}

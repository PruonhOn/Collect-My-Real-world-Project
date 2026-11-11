<?php

namespace App\Http\Controllers\Api\Author;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\Book;
use App\Models\Purchase;
use App\Models\User;
use App\Notifications\BookSubmittedForReview;
use App\Services\BookStorageService;
use App\Services\BookTagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * The author workspace.
 *
 * An author can create books, edit and delete them while they are a draft or
 * have been rejected, and submit them for review. Every path here is scoped to
 * the authenticated author: an author can never read, change, submit or delete
 * another author's book, and can never set the workflow status directly.
 * Approving, rejecting and publishing are admin-only (AdminBookReviewController).
 */
class AuthorBookController extends Controller
{
    public function __construct(
        private BookStorageService $storage,
        private BookTagService $tagService,
    ) {}

    /**
     * The authenticated author's own books, including legacy books still
     * linked via the display string.
     */
    public function index(Request $request): JsonResponse
    {
        $author = $request->user();

        $books = $author->ownedBooksQuery()
            ->with(['category', 'tags'])
            ->withCount(['purchases' => fn ($q) => $q->where('status', Purchase::STATUS_PAID)])
            ->when($request->input('search'), fn ($q, $search) => $q->where('title', 'like', "%{$search}%"))
            ->when($request->filled('status'), fn ($q, $status) => $q->where('status', $this->normalizeStatusFilter($status)))
            ->latest()
            ->paginate((int) $request->input('per_page', 12))
            ->withQueryString();

        return response()->json($books);
    }

    /**
     * A single book owned by the author, with its sales figures.
     */
    public function show(Request $request, Book $book): JsonResponse
    {
        $author = $request->user();

        $this->authorizeOwnedBook($author, $book, 'view');

        $book->load(['category', 'tags']);
        $book->loadCount(['purchases' => fn ($q) => $q->where('status', Purchase::STATUS_PAID)]);
        $book->loadSum(['purchases' => fn ($q) => $q->where('status', Purchase::STATUS_PAID)], 'amount');

        return response()->json([
            'book' => $book,
            'sales' => [
                'purchases_count' => (int) $book->purchases_count,
                'revenue' => round((float) ($book->purchases_sum_amount ?? 0), 2),
            ],
        ]);
    }

    /**
     * Create a new book under the authenticated author. The book always starts
     * as a draft: the author cannot publish, and cannot set the workflow
     * status, the review trail or the counters.
     */
    public function store(Request $request): JsonResponse
    {
        $author = $request->user();

        $validated = $request->validate($this->bookRules(creating: true));

        $data = $this->toBookArray($validated);
        $data['author_id'] = $author->id;
        $data['author'] = $author->name;
        $data['status'] = Book::STATUS_DRAFT;

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $this->storage->storeCover($request->file('cover_image'));
            $data['cover_disk'] = $this->storage->coverDisk();
        }

        if ($request->hasFile('file')) {
            $stored = $this->storage->storeDocument($request->file('file'), $data['language'] ?? 'km');
            $data = array_merge($data, array_diff_key($stored, ['pages' => true]));

            if ($stored['pages'] !== null) {
                $data['pages'] = $stored['pages'];
            }
        }

        $book = Book::create($data);

        $this->tagService->sync($book, $validated['tags'] ?? null);

        return response()->json(['book' => $book->load(['category', 'tags'])], 201);
    }

    /**
     * Update one of the author's own books. Only drafts and rejected books are
     * editable; anything under review or already live is frozen.
     */
    public function update(Request $request, Book $book): JsonResponse
    {
        $author = $request->user();

        $this->authorizeOwnedBook($author, $book, 'update');

        $validated = $request->validate($this->bookRules(creating: false));

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

            if ($newFileMeta['pages'] !== null) {
                $data['pages'] = $newFileMeta['pages'];
            }
        }

        if (isset($data['title']) && $data['title'] !== $book->title) {
            $data['slug'] = Str::slug($data['title']).'-'.Str::lower(Str::random(4));
        }

        $book->update($data);

        if ($newFileMeta !== null) {
            $this->storage->deleteDocument($oldFilePath, $oldFileDisk);
        }

        if ($newCoverPath !== null) {
            $this->storage->deleteCover($oldCoverPath, $oldCoverDisk);
        }

        if (array_key_exists('tags', $validated)) {
            $this->tagService->sync($book, $validated['tags']);
        }

        return response()->json(['book' => $book->fresh()->load(['category', 'tags'])]);
    }

    /**
     * Submit a draft or a previously rejected book for admin review.
     *
     * Resubmitting after a rejection is the same call: the status moves back to
     * pending and the previous verdict is cleared, so the admin sees the
     * resubmission with no stale rejection reason attached.
     *
     * A notification is sent to the default admin so the book shows up as a
     * review request instead of waiting in the queue unnoticed.
     */
    public function submit(Request $request, Book $book): JsonResponse
    {
        $author = $request->user();

        $this->authorizeOwnedBook($author, $book, 'submit');

        $book->transitionTo(Book::STATUS_PENDING);

        User::defaultAdmin()?->notify(new BookSubmittedForReview(
            book: $book->fresh(),
            authorId: $author->id,
            authorName: $author->name,
        ));

        return response()->json(['book' => $book->fresh()->load(['category', 'tags'])]);
    }

    /**
     * Delete one of the author's own books. Only drafts and rejected books can
     * be removed, so a book that is under review or live is never deletable.
     */
    public function destroy(Request $request, Book $book): JsonResponse
    {
        $author = $request->user();

        $this->authorizeOwnedBook($author, $book, 'delete');

        $book->tags()->detach();
        $book->delete();

        $this->storage->deleteCover($book->cover_image, $book->cover_disk);
        $this->storage->deleteDocument($book->file_path, $book->file_disk);

        return response()->json(['message' => 'Book deleted.']);
    }

    /**
     * Statistics across everything the author owns, broken down by workflow
     * state so the dashboard can show the review pipeline.
     */
    public function stats(Request $request): JsonResponse
    {
        $author = $request->user();

        $bookIds = $author->ownedBooksQuery()->pluck('id');

        $counts = $author->ownedBooksQuery()
            ->selectRaw('status, COUNT(*) AS aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $byStatus = collect(Book::STATUSES)
            ->mapWithKeys(fn (string $status) => [$status => (int) ($counts[$status] ?? 0)])
            ->all();

        $paid = Purchase::where('status', Purchase::STATUS_PAID)
            ->whereIn('book_id', $bookIds)
            ->get();

        return response()->json([
            'stats' => [
                'books' => (int) array_sum($byStatus),
                'drafts' => $byStatus[Book::STATUS_DRAFT],
                'pending' => $byStatus[Book::STATUS_PENDING],
                'approved' => $byStatus[Book::STATUS_APPROVED],
                'rejected' => $byStatus[Book::STATUS_REJECTED],
                'published' => $byStatus[Book::STATUS_PUBLISHED],
                'views' => (int) $author->ownedBooksQuery()->sum('view_count'),
                'sold' => $paid->count(),
                'revenue' => round((float) $paid->sum('amount'), 2),
            ],
        ]);
    }

    /**
     * Ownership is answered with 404 so another author's book is
     * indistinguishable from one that does not exist; the policy then decides
     * whether the requested action is allowed on an owned book.
     */
    private function authorizeOwnedBook(Author $author, Book $book, string $ability): void
    {
        abort_unless($author->ownsBook($book), 404, 'Book not found.');

        $this->authorize($ability, $book);
    }

    /**
     * Validation rules for the author-facing book form. `prohibited` blocks the
     * workflow, review and counter columns outright so they can never be
     * mass-assigned from a request - the frontend is not the security boundary.
     *
     * @return array<string, array<int, mixed>>
     */
    private function bookRules(bool $creating): array
    {
        $present = $creating ? 'required' : 'sometimes';
        $optional = $creating ? 'nullable' : 'sometimes';

        return array_merge([
            'category_id' => [$present, 'integer', 'exists:categories,id'],
            'title' => [$present, 'string', 'max:255'],
            'description' => [$creating ? 'required' : 'sometimes', 'string', 'min:10', 'max:5000'],
            'content' => [$optional, 'string', 'max:2000000'],
            'cover_image' => [$creating ? 'required' : 'nullable', 'image', 'mimes:'.implode(',', config('books.cover_mimes')), 'max:'.config('books.max_cover_kilobytes')],
            'file' => [$creating ? 'required' : 'nullable', 'file', 'mimes:'.implode(',', config('books.file_mimes')), 'max:'.config('books.max_file_kilobytes')],
            'language' => [$present, 'string', 'max:10', Rule::in(['km', 'en', 'zh', 'fr'])],
            'pages' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'price' => ['required_if:is_free,0', 'nullable', 'numeric', 'min:0', 'max:9999'],
            'is_free' => ['nullable', 'boolean'],
            'is_vip_only' => ['nullable', 'boolean'],
            'is_coming_soon' => ['nullable', 'boolean'],
            'audience' => ['nullable', 'string', Rule::in(['student', 'teacher', 'general'])],
            'publisher' => ['nullable', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'education_level' => ['nullable', 'string', 'max:255'],
            'grade' => ['nullable', 'string', 'max:255'],
            'publication_date' => ['nullable', 'date', 'before_or_equal:today'],
            'tags' => ['nullable'],
            'tags.*' => ['string', 'max:'.BookTagService::MAX_TAG_LENGTH],
        ], $this->prohibitedRules());
    }

    /**
     * Columns an author may never set through the book form.
     *
     * @return array<string, array<int, string>>
     */
    private function prohibitedRules(): array
    {
        return array_fill_keys([
            'status', 'is_published', 'published_at', 'submitted_at',
            'reviewed_at', 'reviewed_by', 'review_note',
            'author', 'author_id', 'slug',
            'file_path', 'file_disk', 'file_name', 'file_hash', 'file_type', 'file_size',
            'cover_disk',
            'view_count', 'purchase_count',
            'preview_pages',
            'preview_mode',
        ], ['prohibited']);
    }

    /**
     * Map the request onto book columns. Author ownership, the workflow status
     * and the is_published mirror are never taken from the request.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function toBookArray(array $validated): array
    {
        $map = [
            'category_id', 'title', 'description', 'content', 'language',
            'pages', 'price', 'is_free', 'is_vip_only', 'is_coming_soon',
            'publisher', 'subject', 'education_level', 'grade', 'publication_date', 'audience',
        ];

        $data = array_intersect_key($validated, array_flip($map));

        // A free book never carries a price, so normalise it to zero rather
        // than storing the leftover figure the form submitted.
        if (array_key_exists('is_free', $data) && $data['is_free']) {
            $data['price'] = 0;
        }

        if (isset($data['title'])) {
            $data['slug'] = Str::slug($data['title']).'-'.Str::lower(Str::random(4));
        }

        return $data;
    }

    /**
     * Accept the new workflow statuses while still honouring the legacy
     * published/draft filter values the author UI used before the review queue.
     */
    private function normalizeStatusFilter(?string $status): ?string
    {
        return match ($status) {
            'published' => Book::STATUS_PUBLISHED,
            'draft', 'drafts' => Book::STATUS_DRAFT,
            default => $status,
        };
    }
}

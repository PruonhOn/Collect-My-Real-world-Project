<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Purchase;
use App\Services\BookStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The admin side of the editorial workflow: see what authors have submitted,
 * approve or reject it with a reason, and publish once it is good to go.
 *
 * Every route here sits behind the admin role middleware, and each action
 * asserts the BookPolicy ability so the transition rules live in one place
 * rather than being implied by which route was called.
 */
class AdminBookReviewController extends Controller
{
    public function __construct(private BookStorageService $storage) {}

    /**
     * The review queue, newest submissions first. Defaults to everything
     * waiting for a decision so an admin lands on actionable work.
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->input('status', Book::STATUS_PENDING);

        $books = Book::query()
            ->with(['authorRelation:id,name', 'category:id,name', 'tags:id,name,slug'])
            ->when(
                in_array($status, Book::STATUSES, true),
                fn ($q) => $q->where('status', $status),
                fn ($q) => $q->whereIn('status', Book::STATUSES),
            )
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->input('search');

                $q->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhereHas('authorRelation', fn ($a) => $a->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest('submitted_at')
            ->latest('id')
            ->paginate((int) $request->input('per_page', 15))
            ->withQueryString();

        return response()->json($books);
    }

    /**
     * Everything a reviewer needs for one submission, including a signed
     * preview link so the review screen can open the private PDF in a new tab
     * without needing a bearer token.
     */
    public function show(Book $book): JsonResponse
    {
        $this->authorize('view', $book);

        $book->load(['authorRelation:id,name', 'category:id,name', 'tags:id,name,slug', 'reviewer:id,name']);
        $book->loadCount(['purchases' => fn ($q) => $q->where('status', Purchase::STATUS_PAID)]);

        $book->preview_url = $this->storage->temporaryDocumentUrl($book->file_path, $book->file_disk);

        return response()->json(['book' => $book]);
    }

    /**
     * Approve or reject a submission. A rejection must carry a reason: the
     * author sees it and is expected to fix the problem before resubmitting.
     */
    public function review(Request $request, Book $book): JsonResponse
    {
        $this->authorize('review', $book);

        $validated = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'review_note' => ['nullable', 'string', 'max:2000', 'required_if:decision,rejected'],
        ]);

        $book->transitionTo(
            $validated['decision'],
            $request->user(),
            $validated['review_note'] ?? null,
        );

        return response()->json(['book' => $book->fresh()->load(['authorRelation:id,name', 'tags:id,name,slug'])]);
    }

    /**
     * Approve a submission (no body required). Route alias kept in step with
     * the review action so callers can target a decision directly.
     */
    public function approve(Request $request, Book $book): JsonResponse
    {
        $this->authorize('review', $book);

        $book->transitionTo(Book::STATUS_APPROVED, $request->user());

        return response()->json(['book' => $book->fresh()->load(['authorRelation:id,name', 'tags:id,name,slug'])]);
    }

    /**
     * Reject a submission; the review note is mandatory so the author always
     * has an explanation for why the book was sent back.
     */
    public function reject(Request $request, Book $book): JsonResponse
    {
        $this->authorize('review', $book);

        $validated = $request->validate([
            'review_note' => ['required', 'string', 'max:2000'],
        ]);

        $book->transitionTo(Book::STATUS_REJECTED, $request->user(), $validated['review_note']);

        return response()->json(['book' => $book->fresh()->load(['authorRelation:id,name', 'tags:id,name,slug'])]);
    }

    /**
     * Publish an approved book. Publication is the only path that sets
     * is_published, and it is admin-only end to end.
     */
    public function publish(Request $request, Book $book): JsonResponse
    {
        $this->authorize('publish', $book);

        abort_if(
            $book->status !== Book::STATUS_APPROVED,
            409,
            'Only an approved book can be published.',
        );

        $book->transitionTo(Book::STATUS_PUBLISHED, $request->user());

        return response()->json(['book' => $book->fresh()->load(['authorRelation:id,name', 'tags:id,name,slug'])]);
    }

    /**
     * Take a live book off the storefront without destroying it. The book keeps
     * its approved state so it can be republished.
     */
    public function unpublish(Request $request, Book $book): JsonResponse
    {
        $this->authorize('publish', $book);

        $book->transitionTo(Book::STATUS_APPROVED, $request->user(), $book->review_note);

        return response()->json(['book' => $book->fresh()->load(['authorRelation:id,name', 'tags:id,name,slug'])]);
    }

    /**
     * Send a submitted file back to the admin. The private disk is never
     * exposed directly: this is a gated, non-cached stream that only an admin
     * can reach, and it deliberately has no signed URL or guest path.
     */
    public function file(Request $request, Book $book): StreamedResponse
    {
        $this->authorize('review', $book);

        $disk = Storage::disk($book->fileStorageDisk());

        abort_if(! $book->file_path || ! $disk->exists($book->file_path), 404, 'Book file not found.');

        $size = (int) $disk->size($book->file_path);

        return response()->stream(function () use ($book, $disk) {
            $stream = $disk->readStream($book->file_path);

            if (! is_resource($stream)) {
                return;
            }

            try {
                while (! feof($stream)) {
                    $chunk = fread($stream, 8192);

                    if ($chunk === false || $chunk === '') {
                        break;
                    }

                    echo $chunk;
                }
            } finally {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $book->file_type ?: 'application/pdf',
            'Content-Length' => (string) $size,
            'Content-Disposition' => (new ResponseHeaderBag)->makeDisposition(
                ResponseHeaderBag::DISPOSITION_INLINE,
                $book->slug.'.pdf',
            ),
            'Cache-Control' => 'private, no-store',
        ]);
    }
}

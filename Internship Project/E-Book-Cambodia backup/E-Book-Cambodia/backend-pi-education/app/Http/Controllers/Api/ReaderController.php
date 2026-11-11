<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Bookmark;
use App\Models\ReadingProgress;
use App\Services\AccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReaderController extends Controller
{
    public function __construct(private AccessService $accessService) {}

    /**
     * Entry point for the reader. Returns book metadata + progress + bookmarks.
     * Free books are accessible without login; paid books require authentication.
     */
    public function read(Request $request, Book $book): JsonResponse
    {
        abort_unless($book->is_published, 404);

        $user = auth('sanctum')->user();

        if (! $book->is_free) {
            if (! $user) {
                return response()->json(['message' => 'Please log in to access this book.'], 401);
            }
            if (! $this->accessService->canRead($user, $book)) {
                return response()->json(['message' => 'You do not have access to this book.'], 403);
            }
        }

        $progress = null;
        $bookmarks = collect();

        if ($user) {
            $progress = $user->readingProgress()->where('book_id', $book->id)->first();
            $bookmarks = $user->bookmarks()
                ->where('book_id', $book->id)
                ->orderBy('page')
                ->get();
        }

        return response()->json([
            'book' => $book->load('category'),
            'progress' => $progress,
            'bookmarks' => $bookmarks,
        ]);
    }

    /**
     * Protected stream/download of the book file. Free books are accessible
     * without login; paid books require authentication and lawful access.
     */
    public function stream(Request $request, Book $book): StreamedResponse
    {
        abort_unless($book->is_published, 404);

        $user = auth('sanctum')->user();

        if (! $book->is_free) {
            if (! $user) {
                abort(401, 'Please log in to access this book.');
            }
            if (! $this->accessService->canRead($user, $book)) {
                abort(403, 'You do not have access to this book.');
            }
        }

        if (! $book->file_path || ! Storage::disk('local')->exists($book->file_path)) {
            abort(404, 'Book file not found.');
        }

        $file = Storage::disk('local')->get($book->file_path);

        return response()->stream(
            function () use ($file) {
                echo $file;
            },
            200,
            [
                'Content-Type' => $book->file_type ?: 'application/pdf',
                'Content-Length' => strlen($file),
                'Content-Disposition' => (new ResponseHeaderBag)->makeDisposition(
                    ResponseHeaderBag::DISPOSITION_INLINE,
                    $book->slug.'.pdf'
                ),
                'Cache-Control' => 'private, no-store',
            ]
        );
    }

    public function getProgress(Request $request, Book $book): JsonResponse
    {
        $this->assertBookAccess($request, $book);

        $progress = $request->user()->readingProgress()->where('book_id', $book->id)->first();

        return response()->json(['progress' => $progress]);
    }

    public function updateProgress(Request $request, Book $book): JsonResponse
    {
        $this->assertBookAccess($request, $book);

        $validated = $request->validate([
            'last_page' => ['required', 'integer', 'min:1'],
            'total_pages' => ['nullable', 'integer', 'min:0'],
        ]);

        $totalPages = $validated['total_pages'] ?? max($book->pages, $validated['last_page']);
        $progressPercent = $totalPages > 0
            ? round(($validated['last_page'] / $totalPages) * 100, 2)
            : 0;

        $progress = ReadingProgress::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'book_id' => $book->id,
            ],
            [
                'last_page' => $validated['last_page'],
                'total_pages' => $totalPages,
                'progress' => $progressPercent,
                'last_read_at' => now(),
            ]
        );

        return response()->json(['progress' => $progress]);
    }

    public function indexBookmarks(Request $request, Book $book): JsonResponse
    {
        $this->assertBookAccess($request, $book);

        $bookmarks = $request->user()->bookmarks()
            ->where('book_id', $book->id)
            ->orderBy('page')
            ->get();

        return response()->json(['bookmarks' => $bookmarks]);
    }

    public function storeBookmark(Request $request, Book $book): JsonResponse
    {
        $this->assertBookAccess($request, $book);

        $validated = $request->validate([
            'page' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $bookmark = Bookmark::firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'book_id' => $book->id,
                'page' => $validated['page'],
            ],
            [
                'note' => $validated['note'] ?? null,
            ]
        );

        return response()->json(['bookmark' => $bookmark], 201);
    }

    public function destroyBookmark(Request $request, Bookmark $bookmark): JsonResponse
    {
        abort_unless($bookmark->user_id === $request->user()->id, 403);
        $bookmark->delete();

        return response()->json(['message' => 'Bookmark removed.']);
    }

    private function assertBookAccess(Request $request, Book $book): void
    {
        abort_unless($book->is_published, 404);

        if (! $book->is_free && ! $this->accessService->canRead($request->user(), $book)) {
            abort(403, 'You do not have access to this book.');
        }
    }
}

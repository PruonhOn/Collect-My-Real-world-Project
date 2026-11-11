<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Purchase;
use App\Models\SharedAccess;
use App\Services\AccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LibraryController extends Controller
{
    public function __construct(private AccessService $accessService) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $ownedBookIds = Purchase::where('user_id', $user->id)
            ->where('status', Purchase::STATUS_PAID)
            ->whereNotNull('book_id')
            ->pluck('book_id');

        $shared = SharedAccess::with('book')
            ->where('user_id', $user->id)
            ->where('status', SharedAccess::STATUS_ACTIVE)
            ->get();

        $ownedBooks = $ownedBookIds->isNotEmpty()
            ? Book::with('category')->whereIn('id', $ownedBookIds)->get()
            : collect();

        $owned = $ownedBooks->map(fn ($book) => [
            'id' => $book->id,
            'title' => $book->title,
            'author' => $book->author,
            'cover_image' => $book->cover_image,
            'category' => $book->category,
            'source' => 'owned',
            'progress' => $user->readingProgress()->where('book_id', $book->id)->first()?->progress ?? 0,
        ]);

        $shared = $shared->map(fn (SharedAccess $access) => [
            'id' => $access->book->id,
            'title' => $access->book->title,
            'author' => $access->book->author,
            'cover_image' => $access->book->cover_image,
            'category' => $access->book->category,
            'source' => 'shared',
            'shared_by' => $access->sharingKey?->owner?->name,
        ]);

        $vip = collect();
        if ($this->accessService->isVip($user)) {
            $vip = Book::with('category')
                ->published()
                ->where('is_free', false)
                ->whereNotIn('id', $ownedBookIds)
                ->limit(100)
                ->get()
                ->map(fn ($book) => [
                    'id' => $book->id,
                    'title' => $book->title,
                    'author' => $book->author,
                    'cover_image' => $book->cover_image,
                    'category' => $book->category,
                    'source' => 'vip',
                    'progress' => $user->readingProgress()->where('book_id', $book->id)->first()?->progress ?? 0,
                ]);
        }

        return response()->json([
            'owned' => $owned->values(),
            'shared' => $shared->values(),
            'vip' => $vip->values(),
            'count' => $owned->count() + $shared->count() + $vip->count(),
        ]);
    }

    public function show(Request $request, Book $book): JsonResponse
    {
        $user = $request->user();

        if (! $this->accessService->canRead($user, $book)) {
            return response()->json(['message' => 'You do not have access to this book.'], 403);
        }

        $purchase = Purchase::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->where('status', Purchase::STATUS_PAID)
            ->first();

        $shared = SharedAccess::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->where('status', SharedAccess::STATUS_ACTIVE)
            ->first();

        $progress = $user->readingProgress()->where('book_id', $book->id)->first();

        return response()->json([
            'book' => $book->load('category'),
            'source' => $purchase ? 'owned' : ($shared ? 'shared' : ($this->accessService->isVip($user) ? 'vip' : null)),
            'progress' => $progress,
            'activated_at' => $purchase?->activated_at ?? $shared?->created_at,
        ]);
    }
}

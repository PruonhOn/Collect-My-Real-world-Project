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
        $isVip = $this->accessService->isVip($user);

        $ownedBookIds = Purchase::where('user_id', $user->id)
            ->where('status', Purchase::STATUS_PAID)
            ->whereNotNull('book_id')
            ->pluck('book_id');

        $shared = SharedAccess::with('book')
            ->where('user_id', $user->id)
            ->where('status', SharedAccess::STATUS_ACTIVE)
            ->get();

        // A VIP-only book stays unreadable to a non-VIP even when it was
        // purchased or shared before it was flagged, so hide it from them.
        $readable = fn ($book) => ! $book->is_vip_only || $isVip;

        $ownedBooks = $ownedBookIds->isNotEmpty()
            ? Book::with('category')->whereIn('id', $ownedBookIds)->get()
            : collect();

        $owned = $ownedBooks->filter($readable)->map(fn ($book) => [
            'id' => $book->id,
            'title' => $book->title,
            'author' => $book->author,
            'cover_image' => $book->cover_image,
            'category' => $book->category,
            'view_count' => (int) $book->view_count,
            'source' => 'owned',
            'progress' => $user->readingProgress()->where('book_id', $book->id)->first()?->progress ?? 0,
        ]);

        $shared = $shared->filter(fn (SharedAccess $access) => $readable($access->book))->map(fn (SharedAccess $access) => [
            'id' => $access->book->id,
            'title' => $access->book->title,
            'author' => $access->book->author,
            'cover_image' => $access->book->cover_image,
            'category' => $access->book->category,
            'view_count' => (int) $access->book->view_count,
            'source' => 'shared',
            'shared_by' => $access->sharingKey?->owner?->name,
        ]);

        $vip = collect();
        if ($isVip) {
            $vip = Book::with('category')
                ->published()
                ->where(function ($query) {
                    $query->where('is_free', false)->orWhere('is_vip_only', true);
                })
                ->whereNotIn('id', $ownedBookIds)
                ->limit(100)
                ->get()
                ->map(fn ($book) => [
                    'id' => $book->id,
                    'title' => $book->title,
                    'author' => $book->author,
                    'cover_image' => $book->cover_image,
                    'category' => $book->category,
                    'view_count' => (int) $book->view_count,
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

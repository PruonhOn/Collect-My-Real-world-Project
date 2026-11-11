<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Review;
use App\Services\AccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(private AccessService $accessService) {}

    public function index(Request $request, Book $book): JsonResponse
    {
        abort_unless($book->is_published, 404);

        $reviews = $book->reviews()
            ->with('user:id,name')
            ->latest()
            ->paginate((int) $request->input('per_page', 10))
            ->withQueryString();

        return response()->json(['reviews' => $reviews]);
    }

    public function store(Request $request, Book $book): JsonResponse
    {
        abort_unless($book->is_published, 404);

        $user = $request->user();

        if (! $this->accessService->canRead($user, $book)) {
            return response()->json([
                'message' => 'Only readers with access to this book can review it.',
            ], 403);
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $review = Review::updateOrCreate(
            ['user_id' => $user->id, 'book_id' => $book->id],
            [
                'rating' => $validated['rating'],
                'comment' => $validated['comment'] ?? null,
                'status' => Review::STATUS_APPROVED,
            ]
        );

        return response()->json([
            'message' => 'Review saved.',
            'review' => $review->fresh()->load('user:id,name'),
        ], 201);
    }

    public function destroy(Request $request, Review $review): JsonResponse
    {
        $user = $request->user();

        abort_unless($user->id === $review->user_id || $user->isAdmin(), 403);

        $review->delete();

        return response()->json(['message' => 'Review deleted.']);
    }

    public function adminIndex(Request $request): JsonResponse
    {
        $search = $request->input('search');

        $reviews = Review::with(['book:id,title,slug,view_count', 'user:id,name'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($search, function ($query) use ($search) {
                $query->where(
                    fn ($query) => $query
                        ->whereHas('book', fn ($book) => $book->where('title', 'like', "%{$search}%"))
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%"))
                );
            })
            ->latest()
            ->paginate((int) $request->input('per_page', 15))
            ->withQueryString();

        return response()->json($reviews);
    }

    public function updateStatus(Request $request, Review $review): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:approved,pending,rejected'],
        ]);

        $review->update(['status' => $validated['status']]);

        return response()->json([
            'message' => 'Review updated.',
            'review' => $review->fresh()->load(['book:id,title,view_count', 'user:id,name']),
        ]);
    }
}

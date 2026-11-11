<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Review\StoreReviewRequest;
use App\Http\Requests\Review\UpdateReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Book;
use App\Models\Review;
use App\Services\BookAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(private readonly BookAccessService $accessService) {}

    public function index(int $bookId, Request $request): JsonResponse
    {
        $book = Book::withAvg('approvedReviews as avg_rating', 'rating')
            ->withCount(['approvedReviews as reviews_count'])
            ->find($bookId);

        if (! $book) {
            return $this->error('Book not found.', null, 404);
        }

        $reviews = $book->approvedReviews()
            ->with('user:id,name')
            ->latest('id')
            ->paginate($request->integer('per_page', 10));

        return $this->success([
            'book' => [
                'id' => $book->id,
                'slug' => $book->slug,
                'title' => $book->translation($request->header('X-Locale', app()->getLocale()))?->title,
                'avg_rating' => $book->avg_rating !== null ? round((float) $book->avg_rating, 2) : null,
                'reviews_count' => $book->reviews_count,
            ],
            'reviews' => ReviewResource::collection($reviews),
            'pagination' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'total' => $reviews->total(),
                'per_page' => $reviews->perPage(),
            ],
            'my_review' => $request->user('sanctum')
                ? new ReviewResource($book->reviews()->where('user_id', $request->user('sanctum')->id)->first())
                : null,
        ], 'Reviews retrieved.');
    }

    public function store(StoreReviewRequest $request): JsonResponse
    {
        $user = $request->user();
        $book = Book::find($request->integer('book_id'));

        if (! $book || $book->status !== Book::STATUS_PUBLISHED) {
            return $this->error('Book not found or not available for review.', null, 404);
        }

        if (! $this->accessService->canRead($user, $book)) {
            return $this->error('You must have access to this book before reviewing it.', null, 403);
        }

        if ($book->reviews()->where('user_id', $user->id)->exists()) {
            return $this->error('You already reviewed this book.', null, 422);
        }

        $review = $book->reviews()->create([
            'user_id' => $user->id,
            'rating' => $request->integer('rating'),
            'comment' => $request->input('comment'),
            'status' => Review::STATUS_PENDING,
        ]);

        return $this->success(
            new ReviewResource($review->load('user:id,name')),
            'Review submitted for approval.',
            201
        );
    }

    public function my(int $bookId): JsonResponse
    {
        $review = Review::where('user_id', auth('sanctum')->id())
            ->where('book_id', $bookId)
            ->first();

        return $this->success([
            'review' => $review ? new ReviewResource($review) : null,
        ], 'Review retrieved.');
    }

    public function update(string $id, UpdateReviewRequest $request): JsonResponse
    {
        $review = Review::find($id);

        if (! $review) {
            return $this->error('Review not found.', null, 404);
        }

        if ($review->user_id !== $request->user()->id) {
            return $this->error('You can only edit your own review.', null, 403);
        }

        $review->fill($request->safe()->only(['rating', 'comment']));
        $review->status = Review::STATUS_PENDING;
        $review->save();

        return $this->success(
            new ReviewResource($review->load('user:id,name')),
            'Review updated and pending moderation.'
        );
    }

    public function destroy(string $id, Request $request): JsonResponse
    {
        $review = Review::find($id);

        if (! $review) {
            return $this->error('Review not found.', null, 404);
        }

        if ($request->user()->cannot('delete', $review)) {
            return $this->error('You can only delete your own review.', null, 403);
        }

        $review->delete();

        return $this->success(null, 'Review deleted.');
    }
}

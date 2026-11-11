<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminReviewController extends Controller
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function index(Request $request): JsonResponse
    {
        $reviews = Review::query()
            ->with(['user:id,name', 'book.translations'])
            ->when($request->filled('status') && in_array($request->string('status'), ['pending', 'approved', 'rejected']), function ($query) use ($request) {
                $query->where('status', $request->string('status'));
            })
            ->latest('id')
            ->paginate($request->integer('per_page', 15));

        $reviews->getCollection()->transform(function (Review $review) {
            $data = (new ReviewResource($review))->resolve();
            $data['book_title'] = $review->book?->translation()?->title;

            return $data;
        });

        return $this->success([
            'reviews' => $reviews,
            'pagination' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
        ], 'Reviews retrieved.');
    }

    public function update(string $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([Review::STATUS_PENDING, Review::STATUS_APPROVED, Review::STATUS_REJECTED])],
        ]);

        $review = Review::find($id);

        if (! $review) {
            return $this->error('Review not found.', null, 404);
        }

        $review->update(['status' => $validated['status']]);

        $review->load('user:id,name');

        if ($review->status === Review::STATUS_APPROVED) {
            $this->notifications->reviewApproved($review);
        } elseif ($review->status === Review::STATUS_REJECTED) {
            $this->notifications->reviewRejected($review);
        }

        return $this->success(
            new ReviewResource($review),
            'Review status updated.'
        );
    }

    public function destroy(string $id): JsonResponse
    {
        $review = Review::find($id);

        if (! $review) {
            return $this->error('Review not found.', null, 404);
        }

        $review->delete();

        return $this->success(null, 'Review deleted.');
    }
}

<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Api\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class AdminReviewController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Review::with(['user:id,name,email', 'book']);

        if ($request->has('rating') && $request->rating) {
            $query->where('rating', $request->rating);
        }

        $reviews = $query->latest()->paginate($request->per_page ?? 15);

        $mapped = $reviews->getCollection()->map(fn ($review) => [
            'id' => $review->id,
            'rating' => $review->rating,
            'comment' => $review->comment,
            'user' => [
                'id' => $review->user->id,
                'name' => $review->user->name,
                'email' => $review->user->email,
            ],
            'book' => [
                'id' => $review->book->id,
                'slug' => $review->book->slug,
                'title' => $review->book->title,
            ],
            'created_at' => $review->created_at->toDateTimeString(),
        ]);

        return $this->success([
            'reviews' => $mapped,
            'pagination' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }

    public function destroy(Request $request, int $id)
    {
        $review = Review::find($id);

        if (! $review) {
            return $this->error('Review not found', 404);
        }

        $review->delete();

        return $this->success(null, 'Review deleted successfully');
    }
}
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $books = $request->user()
            ->wishlistedBooks()
            ->with(['translations', 'authors.translations', 'categories.translations'])
            ->withAvg('approvedReviews as avg_rating', 'rating')
            ->withCount(['approvedReviews as reviews_count'])
            ->paginate($request->integer('per_page', 12));

        return $this->success([
            'books' => BookResource::collection($books),
            'pagination' => [
                'current_page' => $books->currentPage(),
                'last_page' => $books->lastPage(),
                'per_page' => $books->perPage(),
                'total' => $books->total(),
            ],
        ], 'Wishlist retrieved.');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'book_id' => ['required', 'exists:books,id'],
        ]);

        $book = Book::published()->find($validated['book_id']);

        if (! $book) {
            return $this->error('Book not found.', null, 404);
        }

        $request->user()->wishlistedBooks()->syncWithoutDetaching([$book->id]);

        return $this->success(null, 'Book added to wishlist.', 201);
    }

    public function destroy(string $bookId, Request $request): JsonResponse
    {
        $request->user()->wishlistedBooks()->detach($bookId);

        return $this->success(null, 'Book removed from wishlist.');
    }
}

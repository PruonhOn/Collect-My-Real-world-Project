<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookResource;
use App\Models\Book;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $locale = $request->header('X-Locale', app()->getLocale());
        $user = $request->user('sanctum');
        $sort = (string) $request->string('sort');

        $books = Book::query()
            ->published()
            ->with(['translations', 'authors.translations', 'categories.translations'])
            ->withAvg('approvedReviews as avg_rating', 'rating')
            ->withCount(['approvedReviews as reviews_count'])
            ->when($user, function ($query) use ($user) {
                $query->with('wishlistedBy', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->whereHas('translations', function ($q) use ($request) {
                    $q->where('title', 'like', '%'.$request->string('search').'%')
                        ->orWhere('description', 'like', '%'.$request->string('search').'%');
                });
            })
            ->when($request->filled('type') && in_array($request->string('type')->upper(), ['FREE', 'PAID']), function ($query) use ($request) {
                $query->where('type', $request->string('type')->upper());
            })
            ->when($request->filled('category'), function ($query) use ($request) {
                $query->whereHas('categories', function ($q) use ($request) {
                    $q->where('slug', $request->string('category'));
                });
            })
            ->when($request->filled('author'), function ($query) use ($request) {
                $query->whereHas('authors', function ($q) use ($request) {
                    $q->where('slug', $request->string('author'));
                });
            })
            ->when($request->filled('language'), function ($query) use ($request) {
                $query->where('language', $request->string('language'));
            })
            ->when($request->filled('min_price'), function ($query) use ($request) {
                $query->where('price', '>=', $request->float('min_price'));
            })
            ->when($request->filled('max_price'), function ($query) use ($request) {
                $query->where('price', '<=', $request->float('max_price'));
            })
            ->when($request->filled('min_rating'), function ($query) use ($request) {
                $min = min(5, max(1, $request->float('min_rating')));

                $query->whereIn('id', function ($sub) use ($min) {
                    $sub->select('book_id')
                        ->from('reviews')
                        ->where('status', Review::STATUS_APPROVED)
                        ->groupBy('book_id')
                        ->havingRaw('avg(rating) >= ?', [$min]);
                })->orderByDesc('avg_rating');
            })
            ->when($request->boolean('wishlist') && $user, function ($query) use ($user) {
                $query->whereHas('wishlistedBy', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            })
            ->when($sort === 'oldest', function ($query) {
                $query->orderBy('published_at', 'asc');
            })
            ->when($sort === 'most_read', function ($query) {
                $query->orderByDesc('reader_count');
            })
            ->when($sort === 'top_rated', function ($query) {
                $query->orderByRaw('avg_rating IS NULL ASC')
                    ->orderByDesc('avg_rating')
                    ->orderByDesc('reviews_count');
            })
            ->when($sort === 'price_asc', function ($query) {
                $query->orderBy('price');
            })
            ->when($sort === 'price_desc', function ($query) {
                $query->orderByDesc('price');
            })
            ->when($sort === 'newest' || $sort === 'popular', function ($query) use ($sort) {
                $query->orderByDesc($sort === 'popular' ? 'reader_count' : 'published_at');
            })
            ->paginate($request->integer('per_page', 12));

        return $this->success([
            'books' => BookResource::collection($books),
            'pagination' => [
                'current_page' => $books->currentPage(),
                'last_page' => $books->lastPage(),
                'per_page' => $books->perPage(),
                'total' => $books->total(),
            ],
        ], 'Books retrieved.');
    }

    public function show(string $slug, Request $request): JsonResponse
    {
        $book = Book::query()
            ->published()
            ->where('slug', $slug)
            ->with(['translations', 'authors.translations', 'categories.translations'])
            ->withAvg('approvedReviews as avg_rating', 'rating')
            ->withCount(['approvedReviews as reviews_count'])
            ->first();

        if (! $book) {
            return $this->error('Book not found.', null, 404);
        }

        return $this->success(new BookResource($book), 'Book retrieved.');
    }
}

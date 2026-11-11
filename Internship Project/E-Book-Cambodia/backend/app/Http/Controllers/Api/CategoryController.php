<?php

namespace App\Http\Controllers\Api;

use App\Http\Api\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $withCount = $request->has('with_count');

        $categories = Category::with(['translations'])
            ->where('is_active', true)
            ->when($withCount, fn ($q) => $q->withCount('books'))
            ->get()
            ->map(function ($category) {
                $locale = app()->getLocale();
                $translation = $category->translations()->where('locale', $locale)->first()
                    ?? $category->translations()->where('locale', 'km')->first();

                return [
                    'id' => $category->id,
                    'slug' => $category->slug,
                    'name' => $translation->name ?? $category->slug,
                    'description' => $translation->description ?? null,
                    'icon' => $category->icon,
                    'books_count' => $category->books_count ?? 0,
                ];
            });

        return $this->success($categories);
    }

    public function show(Request $request, string $slug)
    {
        $category = Category::with(['books' => function ($q) {
            $q->where('is_published', true)->with(['category', 'author'])->latest();
        }, 'translations'])->where('slug', $slug)->where('is_active', true)->first();

        if (! $category) {
            return $this->error('Category not found', 404);
        }

        $locale = app()->getLocale();
        $translation = $category->translations()->where('locale', $locale)->first()
            ?? $category->translations()->where('locale', 'km')->first();

        $books = $category->books->map(function ($book) {
            $bt = $book->translations()->where('locale', app()->getLocale())->first()
                ?? $book->translations()->where('locale', 'km')->first();

            return [
                'id' => $book->id,
                'slug' => $book->slug,
                'title' => $bt->title ?? $book->slug,
                'cover_image' => $book->cover_image ? url('/storage/'.$book->cover_image) : null,
                'price' => (float) $book->price,
                'is_free' => $book->is_free,
                'pages' => $book->pages,
                'author_name' => $book->author?->name,
                'rating' => $book->averageRating(),
            ];
        });

        return $this->success([
            'id' => $category->id,
            'slug' => $category->slug,
            'name' => $translation->name ?? $category->slug,
            'description' => $translation->description ?? null,
            'icon' => $category->icon,
            'books' => $books,
        ]);
    }
}

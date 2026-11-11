<?php

namespace App\Http\Controllers\Api;

use App\Http\Api\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Author;
use Illuminate\Http\Request;

class AuthorController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $authors = Author::with(['translations'])
            ->withCount('books')
            ->when($request->has('search') && $request->search, function ($q) use ($request) {
                $q->whereHas('translations', function ($t) use ($request) {
                    $t->where('name', 'like', '%'.$request->search.'%');
                });
            })
            ->get()
            ->map(fn ($author) => $this->authorPayload($author));

        return $this->success($authors);
    }

    public function show(Request $request, string $slug)
    {
        $author = Author::with(['books' => function ($q) {
            $q->where('is_published', true)->with(['category', 'author'])->latest();
        }, 'translations'])->where('slug', $slug)->first();

        if (! $author) {
            return $this->error('Author not found', 404);
        }

        $payload = $this->authorPayload($author);
        $payload['books'] = $author->books->map(function ($book) {
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
                'rating' => $book->averageRating(),
            ];
        });

        return $this->success($payload);
    }

    protected function authorPayload(Author $author): array
    {
        $locale = app()->getLocale();
        $translation = $author->translations()->where('locale', $locale)->first()
            ?? $author->translations()->where('locale', 'km')->first();

        return [
            'id' => $author->id,
            'slug' => $author->slug,
            'name' => $translation->name ?? $author->slug,
            'bio' => $translation->bio ?? null,
            'photo' => $author->photo ? url('/storage/'.$author->photo) : null,
            'books_count' => $author->books_count ?? $author->books()->count(),
        ];
    }
}

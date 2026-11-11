<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuthorResource;
use App\Models\Author;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $authors = Author::query()
            ->with('translations')
            ->withCount(['books' => fn ($q) => $q->published()])
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->whereHas('translations', function ($q) use ($request) {
                    $q->where('name', 'like', '%'.$request->string('search').'%');
                });
            })
            ->orderByDesc('books_count')
            ->paginate($request->integer('per_page', 20));

        return $this->success([
            'authors' => AuthorResource::collection($authors),
            'pagination' => [
                'current_page' => $authors->currentPage(),
                'last_page' => $authors->lastPage(),
                'per_page' => $authors->perPage(),
                'total' => $authors->total(),
            ],
        ], 'Authors retrieved.');
    }

    public function show(string $slug, Request $request): JsonResponse
    {
        $author = Author::query()
            ->where('slug', $slug)
            ->with(['translations', 'books' => fn ($q) => $q->published()->with('translations', 'authors.translations', 'categories.translations')])
            ->withCount(['books' => fn ($q) => $q->published()])
            ->first();

        if (! $author) {
            return $this->error('Author not found.', null, 404);
        }

        return $this->success(new AuthorResource($author), 'Author retrieved.');
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuthorResource;
use App\Models\Author;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminAuthorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $authors = Author::query()
            ->with('translations')
            ->withCount(['books' => fn ($q) => $q->published()])
            ->latest('id')
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

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:255', 'unique:authors,slug'],
            'image' => ['nullable', 'string', 'max:500'],
            'translations' => ['required', 'array', 'min:1'],
            'translations.*.name' => ['required', 'string', 'max:255'],
            'translations.*.biography' => ['nullable', 'string'],
        ]);

        $author = Author::create(['slug' => $validated['slug'], 'image' => $validated['image'] ?? null]);

        foreach ($validated['translations'] as $locale => $fields) {
            $author->translations()->updateOrCreate(['locale' => $locale], $fields);
        }

        return $this->success(
            new AuthorResource($author->load('translations')),
            'Author created.',
            201
        );
    }

    public function update(string $id, Request $request): JsonResponse
    {
        $author = Author::find($id);

        if (! $author) {
            return $this->error('Author not found.', null, 404);
        }

        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:255', Rule::unique('authors', 'slug')->ignore($author->id)],
            'image' => ['nullable', 'string', 'max:500'],
            'translations' => ['required', 'array', 'min:1'],
            'translations.*.name' => ['required', 'string', 'max:255'],
            'translations.*.biography' => ['nullable', 'string'],
        ]);

        $author->update(['slug' => $validated['slug'], 'image' => $validated['image'] ?? null]);

        foreach ($validated['translations'] as $locale => $fields) {
            $author->translations()->updateOrCreate(['locale' => $locale], $fields);
        }

        return $this->success(
            new AuthorResource($author->load('translations')),
            'Author updated.'
        );
    }

    public function destroy(string $id): JsonResponse
    {
        $author = Author::find($id);

        if (! $author) {
            return $this->error('Author not found.', null, 404);
        }

        $author->delete();

        return $this->success(null, 'Author deleted.');
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminCategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $categories = Category::query()
            ->with('translations')
            ->withCount(['books' => fn ($q) => $q->published()])
            ->latest('id')
            ->paginate($request->integer('per_page', 20));

        return $this->success([
            'categories' => CategoryResource::collection($categories),
            'pagination' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
            ],
        ], 'Categories retrieved.');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:255', 'unique:categories,slug'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'translations' => ['required', 'array', 'min:1'],
            'translations.*.name' => ['required', 'string', 'max:255'],
            'translations.*.description' => ['nullable', 'string'],
        ]);

        $category = Category::create(['slug' => $validated['slug'], 'status' => $validated['status']]);

        foreach ($validated['translations'] as $locale => $fields) {
            $category->translations()->updateOrCreate(['locale' => $locale], $fields);
        }

        return $this->success(
            new CategoryResource($category->load('translations')),
            'Category created.',
            201
        );
    }

    public function update(string $id, Request $request): JsonResponse
    {
        $category = Category::find($id);

        if (! $category) {
            return $this->error('Category not found.', null, 404);
        }

        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:255', Rule::unique('categories', 'slug')->ignore($category->id)],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'translations' => ['required', 'array', 'min:1'],
            'translations.*.name' => ['required', 'string', 'max:255'],
            'translations.*.description' => ['nullable', 'string'],
        ]);

        $category->update(['slug' => $validated['slug'], 'status' => $validated['status']]);

        foreach ($validated['translations'] as $locale => $fields) {
            $category->translations()->updateOrCreate(['locale' => $locale], $fields);
        }

        return $this->success(
            new CategoryResource($category->load('translations')),
            'Category updated.'
        );
    }

    public function destroy(string $id): JsonResponse
    {
        $category = Category::find($id);

        if (! $category) {
            return $this->error('Category not found.', null, 404);
        }

        $category->delete();

        return $this->success(null, 'Category deleted.');
    }
}

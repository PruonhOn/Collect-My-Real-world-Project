<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $categories = Category::query()
            ->where('status', Category::STATUS_ACTIVE)
            ->with('translations')
            ->withCount(['books' => fn ($q) => $q->published()])
            ->orderByDesc('books_count')
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

    public function show(string $slug, Request $request): JsonResponse
    {
        $category = Category::query()
            ->where('slug', $slug)
            ->where('status', Category::STATUS_ACTIVE)
            ->with(['translations', 'books' => fn ($q) => $q->published()->with('translations', 'authors.translations', 'categories.translations')])
            ->first();

        if (! $category) {
            return $this->error('Category not found.', null, 404);
        }

        return $this->success(new CategoryResource($category), 'Category retrieved.');
    }
}

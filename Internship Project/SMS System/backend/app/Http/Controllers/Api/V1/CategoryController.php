<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends BaseApiController
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): JsonResponse
    {
        $query = Category::query()->orderBy('name');

        return response()->json([
            'data' => CategoryResource::collection($query->get()),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $category = Category::create($validated);
        $this->audit->record('category.created', $category, after: $category->toArray());

        return response()->json([
            'data' => new CategoryResource($category),
            'meta' => null,
            'errors' => null,
        ], 201);
    }

    public function show(Category $category): JsonResponse
    {
        return response()->json([
            'data' => new CategoryResource($category),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:191', Rule::unique('categories', 'name')->ignore($category->id)],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $category->fill($validated)->save();
        $this->audit->record('category.updated', $category, after: $category->toArray());

        return response()->json([
            'data' => new CategoryResource($category),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function destroy(Category $category): JsonResponse
    {
        if ($category->courses()->exists()) {
            return response()->json([
                'data' => ['message' => 'Category has courses and cannot be deleted.'],
                'meta' => null,
                'errors' => null,
            ], 409);
        }

        $this->audit->record('category.deleted', $category, before: $category->toArray());
        $category->delete();

        return response()->json([
            'data' => ['message' => 'Category deleted.'],
            'meta' => null,
            'errors' => null,
        ]);
    }
}

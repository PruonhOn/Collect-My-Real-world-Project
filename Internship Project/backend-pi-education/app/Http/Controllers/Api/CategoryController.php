<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $categories = Category::withCount('books')
            ->when($request->input('has_books') === '1', fn ($q) => $q->has('books'))
            ->orderBy('name')
            ->get();

        return response()->json(['categories' => $categories]);
    }

    public function show(Category $category): JsonResponse
    {
        $books = $category->books()->published()->get();

        return response()->json([
            'category' => $category->loadCount('books'),
            'books' => $books,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $category = Category::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']).'-'.Str::lower(Str::random(4)),
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json(['category' => $category], 201);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        if (isset($validated['name'])) {
            $category->name = $validated['name'];
            $category->slug = Str::slug($validated['name']).'-'.Str::lower(Str::random(4));
        }
        if (array_key_exists('description', $validated)) {
            $category->description = $validated['description'];
        }
        $category->save();

        return response()->json(['category' => $category->fresh()]);
    }

    public function destroy(Category $category): JsonResponse
    {
        if ($category->books()->exists()) {
            return response()->json([
                'message' => 'Cannot delete a category that still contains books.',
            ], 422);
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted.']);
    }
}

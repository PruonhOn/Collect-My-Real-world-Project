<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Category::with(['parent:id,name', 'children:id,name,parent_id']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $categories = $query->latest()->paginate($request->per_page ?? 15);
        return $this->paginatedSuccess($categories);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $category = Category::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name) . '-' . Str::random(4),
            'description' => $request->description,
            'parent_id' => $request->parent_id,
            'status' => $request->status,
            'created_by' => auth()->id(),
        ]);

        return $this->success($category->load(['parent:id,name', 'children:id,name,parent_id']), 'Category created successfully', 201);
    }

    public function show(int $id)
    {
        $category = Category::with(['parent', 'children', 'creator:id,name'])->find($id);

        if (!$category) {
            return $this->error('Category not found', 404);
        }

        return $this->success($category);
    }

    public function update(Request $request, int $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return $this->error('Category not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'status' => 'sometimes|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $data = $request->only(['name', 'description', 'parent_id', 'status']);

        if ($request->has('name') && $request->name !== $category->name) {
            $data['slug'] = Str::slug($request->name) . '-' . Str::random(4);
        }

        $category->update($data);

        return $this->success($category->fresh()->load(['parent:id,name', 'children:id,name,parent_id']), 'Category updated successfully');
    }

    public function destroy(int $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return $this->error('Category not found', 404);
        }

        if ($category->products()->count() > 0) {
            return $this->error('Cannot delete category with associated products', 409);
        }

        $category->delete();

        return $this->success(null, 'Category deleted successfully');
    }
}

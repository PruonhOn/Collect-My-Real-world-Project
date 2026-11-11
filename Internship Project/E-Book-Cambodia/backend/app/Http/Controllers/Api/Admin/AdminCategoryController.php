<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Api\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminCategoryController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $categories = Category::with(['translations'])
            ->withCount('books')
            ->when($request->has('search') && $request->search, function ($q) use ($request) {
                $q->whereHas('translations', function ($t) use ($request) {
                    $t->where('name', 'like', '%'.$request->search.'%');
                });
            })
            ->latest()
            ->paginate($request->per_page ?? 15);

        $mapped = $categories->getCollection()->map(function ($category) {
            $km = $category->translations()->where('locale', 'km')->first();
            $en = $category->translations()->where('locale', 'en')->first();

            return [
                'id' => $category->id,
                'slug' => $category->slug,
                'icon' => $category->icon,
                'is_active' => $category->is_active,
                'name_km' => $km->name ?? null,
                'name_en' => $en->name ?? null,
                'description_km' => $km->description ?? null,
                'description_en' => $en->description ?? null,
                'books_count' => $category->books_count ?? 0,
                'created_at' => $category->created_at->toDateTimeString(),
            ];
        });

        return $this->success([
            'categories' => $mapped,
            'pagination' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name_km' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'description_km' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $slug = Str::slug($request->name_en ?: $request->name_km);

        $originalSlug = $slug;
        $counter = 1;
        while (Category::where('slug', $slug)->exists()) {
            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        $category = Category::create([
            'slug' => $slug,
            'icon' => $request->icon,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $category->translations()->create([
            'locale' => 'km',
            'name' => $request->name_km,
            'description' => $request->description_km,
        ]);

        if ($request->name_en) {
            $category->translations()->create([
                'locale' => 'en',
                'name' => $request->name_en,
                'description' => $request->description_en,
            ]);
        }

        return $this->success($category->load('translations'), 'Category created successfully', 201);
    }

    public function update(Request $request, int $id)
    {
        $category = Category::find($id);

        if (! $category) {
            return $this->error('Category not found', 404);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name_km' => ['sometimes', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'description_km' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $category->update($request->only(['icon', 'is_active']));

        if ($request->has('name_km')) {
            $km = $category->translations()->where('locale', 'km')->first();
            if ($km) {
                $km->update(['name' => $request->name_km, 'description' => $request->description_km]);
            } else {
                $category->translations()->create([
                    'locale' => 'km',
                    'name' => $request->name_km,
                    'description' => $request->description_km,
                ]);
            }
        }

        if ($request->has('name_en')) {
            $en = $category->translations()->where('locale', 'en')->first();
            if ($request->name_en) {
                if ($en) {
                    $en->update(['name' => $request->name_en, 'description' => $request->description_en]);
                } else {
                    $category->translations()->create([
                        'locale' => 'en',
                        'name' => $request->name_en,
                        'description' => $request->description_en,
                    ]);
                }
            }
        }

        return $this->success($category->fresh('translations'), 'Category updated successfully');
    }

    public function destroy(Request $request, int $id)
    {
        $category = Category::find($id);

        if (! $category) {
            return $this->error('Category not found', 404);
        }

        if ($category->books()->exists()) {
            return $this->error('Cannot delete category that has books', 422);
        }

        $category->delete();

        return $this->success(null, 'Category deleted successfully');
    }

    public function toggleActive(Request $request, int $id)
    {
        $category = Category::find($id);

        if (! $category) {
            return $this->error('Category not found', 404);
        }

        $category->update(['is_active' => ! $category->is_active]);

        return $this->success(['is_active' => $category->is_active], 'Category status updated');
    }
}
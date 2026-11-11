<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::withCount('courses');

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $categories = $query->latest()->paginate(15)->withQueryString();

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        $parentCategories = Category::whereNull('parent_id')->orderBy('name')->get();

        return view('admin.categories.create', compact('parentCategories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:categories,slug'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'exists:categories,id'],
        ]);

        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);

        Category::create($data);

        return redirect()->route('admin.categories.index')
            ->with('success', __('messages.created_successfully'));
    }

    public function edit(Category $category)
    {
        $parentCategories = Category::whereNull('parent_id')
            ->where('id', '!=', $category->id)
            ->orderBy('name')
            ->get();

        return view('admin.categories.edit', compact('category', 'parentCategories'));
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:categories,slug,' . $category->id],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'exists:categories,id'],
        ]);

        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);

        $category->update($data);

        return redirect()->route('admin.categories.index')
            ->with('success', __('messages.updated_successfully'));
    }

    public function destroy(Category $category)
    {
        if ($category->courses()->count() > 0 || $category->children()->count() > 0) {
            return back()->withErrors(['error' => 'មិនអាចលុបប្រភេទនេះបានទេ ព្រោះវាមានវគ្គសិក្សា ឬប្រភេទកូនភ្ជាប់។']);
        }

        $category->delete();

        return back()->with('success', __('messages.deleted_successfully'));
    }
}

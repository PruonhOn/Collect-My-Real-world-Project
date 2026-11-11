<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Repositories\CategoryRepository;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    protected $categoryRepository;

    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function index()
    {
        $categories = $this->categoryRepository->getAll();
        return view('categories.index', compact('categories'));
    }

    public function create()
    {
        return view('categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories',
            'description' => 'nullable|string',
        ]);

        $this->categoryRepository->create($validated);

        return redirect()->route('categories.index')
            ->with('success', "Category '{$validated['name']}' created successfully.");
    }

    public function edit($id)
    {
        $category = $this->categoryRepository->findById($id);
        if (!$category) {
            return redirect()->route('categories.index')->with('error', 'Category not found.');
        }
        return view('categories.edit', compact('category'));
    }

    public function update(Request $request, $id)
    {
        $category = $this->categoryRepository->findById($id);
        if (!$category) {
            return redirect()->route('categories.index')->with('error', 'Category not found.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $id,
            'description' => 'nullable|string',
        ]);

        $this->categoryRepository->update($category, $validated);

        return redirect()->route('categories.index')
            ->with('success', "Category '{$category->name}' updated successfully.");
    }

    public function destroy($id)
    {
        try {
            $category = $this->categoryRepository->findById($id);
            if (!$category) {
                return redirect()->route('categories.index')->with('error', 'Category not found.');
            }
            $this->categoryRepository->delete($category);
            return redirect()->route('categories.index')
                ->with('success', "Category '{$category->name}' deleted successfully.");
        } catch (\Exception $e) {
            return redirect()->route('categories.index')
                ->with('error', $e->getMessage());
        }
    }
}

<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Pagination\LengthAwarePaginator;

class CategoryRepository
{
    public function getAll(): LengthAwarePaginator
    {
        return Category::withCount('products')->latest()->paginate(10);
    }

    public function getAllForDropdown()
    {
        return Category::orderBy('name')->get();
    }

    public function findById(int $id): ?Category
    {
        return Category::withCount('products')->find($id);
    }

    public function create(array $data): Category
    {
        return Category::create($data);
    }

    public function update(Category $category, array $data): bool
    {
        return $category->update($data);
    }

    public function delete(Category $category): bool
    {
        if ($category->products()->count() > 0) {
            throw new \Exception('Cannot delete category with associated products.');
        }
        return $category->delete();
    }
}

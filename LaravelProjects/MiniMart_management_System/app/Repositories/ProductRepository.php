<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductRepository
{
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $query = Product::with('category');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['low_stock'])) {
            $query->where('quantity', '<', 10);
        }

        return $query->latest()->paginate(10);
    }

    public function findById(int $id): ?Product
    {
        return Product::with('category')->find($id);
    }

    public function findByBarcode(string $barcode): ?Product
    {
        return Product::with('category')->where('barcode', $barcode)->first();
    }

    public function search(string $query)
    {
        return Product::with('category')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('barcode', 'like', "%{$query}%");
            })
            ->where('quantity', '>', 0)
            ->get();
    }

    public function create(array $data): Product
    {
        return Product::create($data);
    }

    public function update(Product $product, array $data): bool
    {
        return $product->update($data);
    }

    public function delete(Product $product): bool
    {
        return $product->delete();
    }

    public function getLowStock()
    {
        return Product::with('category')
            ->where('quantity', '<', 10)
            ->orderBy('quantity', 'asc')
            ->get();
    }
}

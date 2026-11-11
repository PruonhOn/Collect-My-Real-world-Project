<?php

namespace App\Repositories;

use App\Models\Product;

class ProductRepository extends BaseRepository
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    public function findByBarcode(string $barcode): ?Product
    {
        return $this->model->where('barcode', $barcode)->first();
    }

    public function findBySku(string $sku): ?Product
    {
        return $this->model->where('sku', $sku)->first();
    }

    public function findLowStock(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->where('status', 'active')
            ->whereColumn('current_stock', '<=', 'min_stock')
            ->get();
    }

    public function search(string $query)
    {
        return $this->model->where('status', 'active')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('sku', 'like', "%{$query}%")
                    ->orWhere('barcode', 'like', "%{$query}%");
            })
            ->paginate(15);
    }
}

<?php

namespace App\Repositories;

use App\Models\InventoryItem;
use Illuminate\Support\Collection;

class InventoryItemRepository extends BaseRepository
{
    public function __construct(InventoryItem $inventoryItem)
    {
        parent::__construct($inventoryItem);
    }

    public function model(): InventoryItem
    {
        return new InventoryItem();
    }

    public function findBySku(string $sku): ?InventoryItem
    {
        return $this->findBy('sku', $sku);
    }

    public function findByWarehouse(int $warehouseId): Collection
    {
        return $this->findAllBy('warehouse_id', $warehouseId);
    }

    public function findByCategory(string $category): Collection
    {
        return $this->findAllBy('category', $category);
    }

    public function findLowStock(): Collection
    {
        return $this->model
            ->where('quantity', '<=', 'min_quantity')
            ->get();
    }
}

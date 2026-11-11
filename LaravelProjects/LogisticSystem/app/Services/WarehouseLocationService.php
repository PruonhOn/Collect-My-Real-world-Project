<?php

namespace App\Services;

use App\Models\WarehouseLocation;

class WarehouseLocationService
{
    public function getAll(array $filters = [])
    {
        $query = WarehouseLocation::with('warehouse');

        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (!empty($filters['zone'])) {
            $query->where('zone', $filters['zone']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['is_occupied'])) {
            $query->where('is_occupied', $filters['is_occupied']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('code', 'like', "%{$filters['search']}%")
                    ->orWhere('name', 'like', "%{$filters['search']}%")
                    ->orWhere('zone', 'like', "%{$filters['search']}%");
            });
        }

        return $query->latest()->paginate(15);
    }

    public function getById(int $id): WarehouseLocation
    {
        return WarehouseLocation::with(['warehouse', 'inventoryItems'])->findOrFail($id);
    }

    public function create(array $data): WarehouseLocation
    {
        return WarehouseLocation::create($data);
    }

    public function update(int $id, array $data): WarehouseLocation
    {
        $location = $this->getById($id);
        $location->update($data);
        return $location;
    }

    public function delete(int $id): bool
    {
        return $this->getById($id)->delete();
    }

    public function getByWarehouse(int $warehouseId)
    {
        return WarehouseLocation::where('warehouse_id', $warehouseId)
            ->where('is_active', true)
            ->get();
    }
}

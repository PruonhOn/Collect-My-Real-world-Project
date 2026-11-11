<?php

namespace App\Repositories;

use App\Models\WarehouseLocation;
use Illuminate\Support\Collection;

class WarehouseLocationRepository extends BaseRepository
{
    public function __construct(WarehouseLocation $warehouseLocation)
    {
        parent::__construct($warehouseLocation);
    }

    public function model(): WarehouseLocation
    {
        return new WarehouseLocation();
    }

    public function findByCode(string $code): ?WarehouseLocation
    {
        return $this->findBy('code', $code);
    }

    public function findByWarehouse(int $warehouseId): Collection
    {
        return $this->findAllBy('warehouse_id', $warehouseId);
    }

    public function findAvailable(): Collection
    {
        return $this->findWhere([
            'is_occupied' => false,
            'is_active' => true,
        ]);
    }
}

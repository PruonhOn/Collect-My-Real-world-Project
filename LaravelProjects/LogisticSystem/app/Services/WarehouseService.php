<?php

namespace App\Services;

use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Repositories\BaseRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class WarehouseService extends BaseService
{
    public function __construct(BaseRepository $repository)
    {
        parent::__construct($repository);
    }

    public function createWarehouse(array $data): Warehouse
    {
        /** @var Warehouse */
        return $this->repository->create($data);
    }

    public function updateWarehouse(int $id, array $data): ?Warehouse
    {
        /** @var Warehouse|null */
        return $this->repository->update($id, $data);
    }

    public function findByCode(string $code): ?Warehouse
    {
        /** @var Warehouse|null */
        return $this->repository->findBy('code', $code);
    }

    public function getWarehousesByBranch(int $branchId): Collection
    {
        return $this->repository->findAllBy('branch_id', $branchId);
    }

    public function getAvailableCapacity(int $id): ?array
    {
        /** @var Warehouse|null $warehouse */
        $warehouse = $this->repository->find($id);

        if (!$warehouse) {
            return null;
        }

        return [
            'total_capacity' => $warehouse->total_capacity,
            'used_capacity' => $warehouse->used_capacity,
            'available_capacity' => $warehouse->total_capacity - $warehouse->used_capacity,
            'usage_percentage' => $warehouse->total_capacity > 0
                ? round(($warehouse->used_capacity / $warehouse->total_capacity) * 100, 2)
                : 0,
        ];
    }

    public function recordStockMovement(array $data): StockMovement
    {
        return StockMovement::create($data);
    }

    public function getStockMovements(int $warehouseId, ?int $perPage = 15): LengthAwarePaginator
    {
        return StockMovement::whereHas('inventoryItem', function ($query) use ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        })->with(['inventoryItem', 'createdBy'])->paginate($perPage);
    }

    public function toggleActive(int $id): ?Warehouse
    {
        /** @var Warehouse|null $warehouse */
        $warehouse = $this->repository->find($id);

        if (!$warehouse) {
            return null;
        }

        $warehouse->update(['is_active' => !$warehouse->is_active]);

        return $warehouse->fresh();
    }
}

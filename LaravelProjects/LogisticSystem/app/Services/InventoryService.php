<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Repositories\BaseRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;

class InventoryService extends BaseService
{
    public function __construct(BaseRepository $repository)
    {
        parent::__construct($repository);
    }

    public function getFilteredPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = InventoryItem::with(['warehouse', 'location']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (!empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($filters['low_stock'])) {
            $query->whereColumn('quantity', '<=', 'min_quantity');
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function createInventoryItem(array $data): InventoryItem
    {
        $data['quantity'] = $data['quantity'] ?? 0;

        /** @var InventoryItem $item */
        $item = $this->repository->create($data);

        if ($item->quantity > 0) {
            $this->recordMovement($item->id, [
                'type' => 'initial',
                'quantity' => $item->quantity,
                'quantity_before' => 0,
                'quantity_after' => $item->quantity,
                'reason' => 'Initial stock entry',
                'created_by' => $data['created_by'] ?? null,
            ]);
        }

        return $item->load(['warehouse', 'location']);
    }

    public function updateInventoryItem(int $id, array $data): ?InventoryItem
    {
        unset($data['quantity']);

        /** @var InventoryItem|null */
        return $this->repository->update($id, $data);
    }

    public function findBySku(string $sku): ?InventoryItem
    {
        /** @var InventoryItem|null */
        return $this->repository->findBy('sku', $sku);
    }

    public function adjustStock(int $id, float $quantity, string $reason, ?int $userId = null): ?InventoryItem
    {
        /** @var InventoryItem|null $item */
        $item = $this->repository->find($id);

        if (!$item) {
            return null;
        }

        $quantityBefore = $item->quantity;
        $newQuantity = $quantityBefore + $quantity;

        if ($newQuantity < 0) {
            throw new \InvalidArgumentException('Insufficient stock quantity.');
        }

        $item->update(['quantity' => $newQuantity]);

        $this->recordMovement($id, [
            'type' => $quantity >= 0 ? 'in' : 'out',
            'quantity' => abs($quantity),
            'quantity_before' => $quantityBefore,
            'quantity_after' => $newQuantity,
            'reason' => $reason,
            'created_by' => $userId,
        ]);

        return $item->fresh()->load(['warehouse', 'location']);
    }

    public function getCategories(): Collection
    {
        return InventoryItem::select('category')
            ->distinct()
            ->whereNotNull('category')
            ->orderBy('category')
            ->pluck('category');
    }

    public function getLowStockItems(): Collection
    {
        return InventoryItem::whereColumn('quantity', '<=', 'min_quantity')
            ->where('is_active', true)
            ->with(['warehouse', 'location'])
            ->get();
    }

    public function transferStock(int $itemId, int $fromLocationId, int $toLocationId, float $quantity, ?int $userId = null): ?InventoryItem
    {
        /** @var InventoryItem|null $item */
        $item = $this->repository->find($itemId);

        if (!$item) {
            return null;
        }

        if ($item->quantity < $quantity) {
            throw new \InvalidArgumentException('Insufficient stock quantity for transfer.');
        }

        $this->recordMovement($itemId, [
            'type' => 'transfer',
            'quantity' => $quantity,
            'quantity_before' => $item->quantity,
            'quantity_after' => $item->quantity - $quantity,
            'reason' => 'Stock transfer between locations',
            'from_location_id' => $fromLocationId,
            'to_location_id' => $toLocationId,
            'created_by' => $userId,
        ]);

        return $item->fresh();
    }

    public function getStockMovementHistory(int $itemId, ?int $perPage = 15): LengthAwarePaginator
    {
        return StockMovement::where('inventory_item_id', $itemId)
            ->with(['createdBy', 'fromLocation', 'toLocation'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getItemsByWarehouse(int $warehouseId): Collection
    {
        return $this->repository->findAllBy('warehouse_id', $warehouseId)
            ->load('location');
    }

    public function getInventorySummary(?int $warehouseId = null): array
    {
        $query = InventoryItem::query();

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $totalItems = $query->count();
        $totalQuantity = $query->sum('quantity');

        $lowStockCount = (clone $query)
            ->whereColumn('quantity', '<=', 'min_quantity')
            ->count();

        $totalValue = (clone $query)
            ->selectRaw('SUM(quantity * unit_price) as total_value')
            ->value('total_value');

        return [
            'total_items' => $totalItems,
            'total_quantity' => $totalQuantity,
            'low_stock_count' => $lowStockCount,
            'total_value' => round($totalValue ?? 0, 2),
        ];
    }

    private function recordMovement(int $itemId, array $data): StockMovement
    {
        return StockMovement::create(array_merge($data, [
            'inventory_item_id' => $itemId,
        ]));
    }
}

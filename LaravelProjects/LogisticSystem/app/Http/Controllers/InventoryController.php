<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use App\Services\InventoryService;
use App\Services\WarehouseLocationService;
use App\Services\WarehouseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected WarehouseService $warehouseService,
        protected WarehouseLocationService $locationService,
        protected AuditLogService $auditLogService
    ) {}

    public function index(Request $request): View
    {
        $filters = array_filter($request->only([
            'search', 'category', 'warehouse_id', 'location_id', 'is_active', 'low_stock',
        ]), function ($value) {
            return $value !== null && $value !== '';
        });
        $inventoryItems = $this->inventoryService->getFilteredPaginated($filters);
        $categories = $this->inventoryService->getCategories();
        $warehouses = $this->warehouseService->getAll(['is_active' => true]);
        return view('inventory.index', compact('inventoryItems', 'categories', 'warehouses'));
    }

    public function create(): View
    {
        $warehouses = $this->warehouseService->getAll(['is_active' => true]);
        $categories = $this->inventoryService->getCategories();
        return view('inventory.create', compact('warehouses', 'categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sku' => 'required|string|max:100|unique:inventory_items,sku',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'required|string|max:100',
            'quantity' => 'required|numeric|min:0',
            'min_quantity' => 'nullable|numeric|min:0',
            'max_quantity' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'weight_per_unit' => 'nullable|numeric|min:0',
            'volume_per_unit' => 'nullable|numeric|min:0',
            'unit_price' => 'nullable|numeric|min:0',
            'barcode' => 'nullable|string|max:255',
            'qr_code' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'warehouse_id' => 'required|exists:warehouses,id',
            'location_id' => 'nullable|exists:warehouse_locations,id',
        ]);

        try {
            $validated['is_active'] = $request->boolean('is_active');
            $validated['created_by'] = auth()->id();
            $item = $this->inventoryService->create($validated);

            $this->auditLogService->log('created', 'inventory', "Inventory item created: {$item->name}", $item, null, $validated, auth()->id());

            return redirect()->route('inventory.index')
                ->with('success', "Inventory item {$item->name} created successfully.");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create inventory item: ' . $e->getMessage())->withInput();
        }
    }

    public function show(int $id): View
    {
        $item = $this->inventoryService->getById($id);
        return view('inventory.show', compact('item'));
    }

    public function edit(int $id): View
    {
        $item = $this->inventoryService->getById($id);
        $warehouses = $this->warehouseService->getAll(['is_active' => true]);
        $locations = $this->locationService->getByWarehouse($item->warehouse_id);
        $categories = $this->inventoryService->getCategories();
        return view('inventory.edit', compact('item', 'warehouses', 'locations', 'categories'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'sku' => 'required|string|max:100|unique:inventory_items,sku,' . $id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'required|string|max:100',
            'quantity' => 'required|numeric|min:0',
            'min_quantity' => 'nullable|numeric|min:0',
            'max_quantity' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'weight_per_unit' => 'nullable|numeric|min:0',
            'volume_per_unit' => 'nullable|numeric|min:0',
            'unit_price' => 'nullable|numeric|min:0',
            'barcode' => 'nullable|string|max:255',
            'qr_code' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'warehouse_id' => 'required|exists:warehouses,id',
            'location_id' => 'nullable|exists:warehouse_locations,id',
        ]);

        try {
            $validated['is_active'] = $request->boolean('is_active');
            $item = $this->inventoryService->update($id, $validated);

            $this->auditLogService->log('updated', 'inventory', "Inventory item updated: {$item->name}", $item, null, $validated, auth()->id());

            return redirect()->route('inventory.index')
                ->with('success', "Inventory item {$item->name} updated successfully.");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update inventory item: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $item = $this->inventoryService->getById($id);
            $this->inventoryService->delete($id);

            $this->auditLogService->log('deleted', 'inventory', "Inventory item deleted: {$item->name}", $item, null, null, auth()->id());

            return redirect()->route('inventory.index')
                ->with('success', 'Inventory item deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete inventory item: ' . $e->getMessage());
        }
    }

    public function stockMovements(int $id): View
    {
        $item = $this->inventoryService->getById($id);
        $movements = $this->inventoryService->getStockMovements($id);
        return view('inventory.stock-movements', compact('item', 'movements'));
    }

    public function adjustStock(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|numeric',
            'reason' => 'required|string|max:1000',
            'reference' => 'nullable|string|max:255',
            'from_location_id' => 'nullable|exists:warehouse_locations,id',
            'to_location_id' => 'nullable|exists:warehouse_locations,id',
        ]);

        try {
            $this->inventoryService->adjustStock($id, $validated, auth()->id());

            $this->auditLogService->log('updated', 'inventory', "Stock adjusted for item #{$id}", null, null, $validated, auth()->id());

            return back()->with('success', 'Stock adjusted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to adjust stock: ' . $e->getMessage());
        }
    }
}

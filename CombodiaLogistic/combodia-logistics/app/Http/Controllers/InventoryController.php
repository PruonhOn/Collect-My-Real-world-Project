<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInventoryRequest;
use App\Http\Requests\UpdateInventoryRequest;
use App\Models\Inventory;
use App\Models\Warehouse;
use App\Models\WarehouseShelf;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Inventory::with(['warehouse', 'warehouseShelf']);

        if ($request->has('search') && $request->search !== null) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($request->has('warehouse_id') && $request->warehouse_id !== null) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->has('category') && $request->category !== null) {
            $query->where('category', $request->category);
        }

        $inventories = $query->latest()->paginate(15)->withQueryString();
        $warehouses = Warehouse::where('is_active', true)->get();
        $categories = Inventory::distinct()->pluck('category')->filter();

        if ($request->ajax()) {
            return response()->json(['inventories' => $inventories, 'warehouses' => $warehouses, 'categories' => $categories]);
        }

        return view('inventories.index', compact('inventories', 'warehouses', 'categories'));
    }

    public function create()
    {
        $warehouses = Warehouse::where('is_active', true)->get();
        $shelves = WarehouseShelf::where('is_active', true)->get();
        return view('inventories.create', compact('warehouses', 'shelves'));
    }

    public function store(StoreInventoryRequest $request)
    {
        $validated = $request->validated();

        $inventory = Inventory::create($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'Inventory item created successfully', 'inventory' => $inventory], 201);
        }

        return redirect()->route('inventories.index')->with('success', 'Inventory item created successfully');
    }

    public function show(Inventory $inventory)
    {
        $inventory->load(['warehouse', 'warehouseShelf', 'stockMovements']);

        if (request()->ajax()) {
            return response()->json(['inventory' => $inventory]);
        }

        return view('inventories.show', compact('inventory'));
    }

    public function edit(Inventory $inventory)
    {
        $warehouses = Warehouse::where('is_active', true)->get();
        $shelves = WarehouseShelf::where('is_active', true)->get();
        return view('inventories.edit', compact('inventory', 'warehouses', 'shelves'));
    }

    public function update(UpdateInventoryRequest $request, Inventory $inventory)
    {
        $validated = $request->validated();
        $inventory->update($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'Inventory item updated successfully', 'inventory' => $inventory]);
        }

        return redirect()->route('inventories.index')->with('success', 'Inventory item updated successfully');
    }

    public function destroy(Inventory $inventory)
    {
        $inventory->delete();

        if (request()->ajax()) {
            return response()->json(['message' => 'Inventory item deleted successfully']);
        }

        return redirect()->route('inventories.index')->with('success', 'Inventory item deleted successfully');
    }
}

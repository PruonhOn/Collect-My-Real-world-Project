<?php

namespace App\Http\Controllers;

use App\Models\WarehouseShelf;
use App\Models\WarehouseZone;
use Illuminate\Http\Request;

class WarehouseShelfController extends Controller
{
    public function index(Request $request)
    {
        $query = WarehouseShelf::with('warehouseZone');

        if ($request->has('warehouse_zone_id') && $request->warehouse_zone_id !== null) {
            $query->where('warehouse_zone_id', $request->warehouse_zone_id);
        }

        if ($request->has('search') && $request->search !== null) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $shelves = $query->latest()->paginate(15)->withQueryString();
        $zones = WarehouseZone::where('is_active', true)->get();

        if ($request->ajax()) {
            return response()->json(['shelves' => $shelves, 'zones' => $zones]);
        }

        return view('warehouse-shelves.index', compact('shelves', 'zones'));
    }

    public function create()
    {
        $zones = WarehouseZone::where('is_active', true)->get();
        return view('warehouse-shelves.create', compact('zones'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'warehouse_zone_id' => 'required|exists:warehouse_zones,id',
            'code' => 'required|string|max:50|unique:warehouse_shelves,code',
            'name' => 'required|string|max:255',
            'max_weight' => 'required|numeric|min:0',
            'current_weight' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);
        $validated['current_weight'] = $validated['current_weight'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active', true);

        $shelf = WarehouseShelf::create($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'Shelf created successfully', 'shelf' => $shelf], 201);
        }

        return redirect()->route('warehouse-shelves.index')->with('success', 'Shelf created successfully');
    }

    public function show(WarehouseShelf $warehouseShelf)
    {
        $warehouseShelf->load(['warehouseZone', 'packages', 'inventories']);

        if (request()->ajax()) {
            return response()->json(['shelf' => $warehouseShelf]);
        }

        return view('warehouse-shelves.show', ['shelf' => $warehouseShelf]);
    }

    public function edit(WarehouseShelf $warehouseShelf)
    {
        $zones = WarehouseZone::where('is_active', true)->get();
        return view('warehouse-shelves.edit', ['shelf' => $warehouseShelf, 'zones' => $zones]);
    }

    public function update(Request $request, WarehouseShelf $warehouseShelf)
    {
        $validated = $request->validate([
            'warehouse_zone_id' => 'required|exists:warehouse_zones,id',
            'code' => 'required|string|max:50|unique:warehouse_shelves,code,' . $warehouseShelf->id,
            'name' => 'required|string|max:255',
            'max_weight' => 'required|numeric|min:0',
            'current_weight' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);
        $validated['is_active'] = $request->boolean('is_active', $warehouseShelf->is_active);

        $warehouseShelf->update($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'Shelf updated successfully', 'shelf' => $warehouseShelf]);
        }

        return redirect()->route('warehouse-shelves.index')->with('success', 'Shelf updated successfully');
    }

    public function destroy(WarehouseShelf $warehouseShelf)
    {
        $warehouseShelf->delete();

        if (request()->ajax()) {
            return response()->json(['message' => 'Shelf deleted successfully']);
        }

        return redirect()->route('warehouse-shelves.index')->with('success', 'Shelf deleted successfully');
    }
}

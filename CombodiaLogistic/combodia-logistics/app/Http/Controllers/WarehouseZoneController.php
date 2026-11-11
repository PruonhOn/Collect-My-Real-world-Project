<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Models\WarehouseZone;
use Illuminate\Http\Request;

class WarehouseZoneController extends Controller
{
    public function index(Request $request)
    {
        $query = WarehouseZone::with('warehouse');

        if ($request->has('warehouse_id') && $request->warehouse_id !== null) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->has('search') && $request->search !== null) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $zones = $query->latest()->paginate(15)->withQueryString();
        $warehouses = Warehouse::where('is_active', true)->get();

        if ($request->ajax()) {
            return response()->json(['zones' => $zones, 'warehouses' => $warehouses]);
        }

        return view('warehouse-zones.index', compact('zones', 'warehouses'));
    }

    public function create()
    {
        $warehouses = Warehouse::where('is_active', true)->get();
        return view('warehouse-zones.create', compact('warehouses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'code' => 'required|string|max:50|unique:warehouse_zones,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'capacity' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);
        $validated['is_active'] = $request->boolean('is_active', true);

        $zone = WarehouseZone::create($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'Zone created successfully', 'zone' => $zone], 201);
        }

        return redirect()->route('warehouse-zones.index')->with('success', 'Zone created successfully');
    }

    public function show(WarehouseZone $warehouseZone)
    {
        $warehouseZone->load(['warehouse', 'warehouseShelves']);

        if (request()->ajax()) {
            return response()->json(['zone' => $warehouseZone]);
        }

        return view('warehouse-zones.show', ['zone' => $warehouseZone]);
    }

    public function edit(WarehouseZone $warehouseZone)
    {
        $warehouses = Warehouse::where('is_active', true)->get();
        return view('warehouse-zones.edit', ['zone' => $warehouseZone, 'warehouses' => $warehouses]);
    }

    public function update(Request $request, WarehouseZone $warehouseZone)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'code' => 'required|string|max:50|unique:warehouse_zones,code,' . $warehouseZone->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'capacity' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);
        $validated['is_active'] = $request->boolean('is_active', $warehouseZone->is_active);

        $warehouseZone->update($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'Zone updated successfully', 'zone' => $warehouseZone]);
        }

        return redirect()->route('warehouse-zones.index')->with('success', 'Zone updated successfully');
    }

    public function destroy(WarehouseZone $warehouseZone)
    {
        $warehouseZone->delete();

        if (request()->ajax()) {
            return response()->json(['message' => 'Zone deleted successfully']);
        }

        return redirect()->route('warehouse-zones.index')->with('success', 'Zone deleted successfully');
    }
}

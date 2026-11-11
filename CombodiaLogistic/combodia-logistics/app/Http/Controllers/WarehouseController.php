<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Models\Branch;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $query = Warehouse::with('branch');

        if ($request->has('search') && $request->search !== null) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            });
        }

        if ($request->has('branch_id') && $request->branch_id !== null) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('is_active') && $request->is_active !== null) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $warehouses = $query->latest()->paginate(15)->withQueryString();
        $branches = Branch::where('is_active', true)->get();

        if ($request->ajax()) {
            return response()->json(['warehouses' => $warehouses, 'branches' => $branches]);
        }

        return view('warehouses.index', compact('warehouses', 'branches'));
    }

    public function create()
    {
        $branches = Branch::where('is_active', true)->get();
        return view('warehouses.create', compact('branches'));
    }

    public function store(StoreWarehouseRequest $request)
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', true);

        $warehouse = Warehouse::create($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'Warehouse created successfully', 'warehouse' => $warehouse], 201);
        }

        return redirect()->route('warehouses.index')->with('success', 'Warehouse created successfully');
    }

    public function show(Warehouse $warehouse)
    {
        $warehouse->load(['branch', 'warehouseZones.warehouseShelves']);

        if (request()->ajax()) {
            return response()->json(['warehouse' => $warehouse]);
        }

        return view('warehouses.show', compact('warehouse'));
    }

    public function edit(Warehouse $warehouse)
    {
        $branches = Branch::where('is_active', true)->get();
        return view('warehouses.edit', compact('warehouse', 'branches'));
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse)
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', $warehouse->is_active);

        $warehouse->update($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'Warehouse updated successfully', 'warehouse' => $warehouse]);
        }

        return redirect()->route('warehouses.index')->with('success', 'Warehouse updated successfully');
    }

    public function destroy(Warehouse $warehouse)
    {
        $warehouse->delete();

        if (request()->ajax()) {
            return response()->json(['message' => 'Warehouse deleted successfully']);
        }

        return redirect()->route('warehouses.index')->with('success', 'Warehouse deleted successfully');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function index(Request $request)
    {
        $query = StockMovement::with(['inventory', 'warehouse', 'user']);

        if ($request->has('inventory_id') && $request->inventory_id !== null) {
            $query->where('inventory_id', $request->inventory_id);
        }

        if ($request->has('warehouse_id') && $request->warehouse_id !== null) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->has('type') && $request->type !== null) {
            $query->where('type', $request->type);
        }

        if ($request->has('search') && $request->search !== null) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference_type', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhere('from_location', 'like', "%{$search}%")
                    ->orWhere('to_location', 'like', "%{$search}%");
            });
        }

        $movements = $query->latest()->paginate(15)->withQueryString();
        $warehouses = Warehouse::where('is_active', true)->get();
        $inventories = Inventory::latest()->get();

        if ($request->ajax()) {
            return response()->json(['movements' => $movements, 'warehouses' => $warehouses, 'inventories' => $inventories]);
        }

        return view('stock-movements.index', compact('movements', 'warehouses', 'inventories'));
    }

    public function create()
    {
        $inventories = Inventory::latest()->get();
        $warehouses = Warehouse::where('is_active', true)->get();
        return view('stock-movements.create', compact('inventories', 'warehouses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'inventory_id' => 'required|exists:inventories,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'type' => 'required|string|in:inbound,outbound,transfer,adjustment',
            'quantity' => 'required|integer|min:1',
            'unit_cost' => 'required|numeric|min:0',
            'from_location' => 'nullable|string|max:255',
            'to_location' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $validated['total_cost'] = $validated['quantity'] * $validated['unit_cost'];
        $validated['moved_by'] = auth()->id();

        $movement = StockMovement::create($validated);

        $inventory = Inventory::find($validated['inventory_id']);
        if ($validated['type'] === 'inbound') {
            $inventory->increment('quantity', $validated['quantity']);
        } elseif ($validated['type'] === 'outbound') {
            $inventory->decrement('quantity', $validated['quantity']);
        }

        if ($request->ajax()) {
            return response()->json(['message' => 'Stock movement recorded successfully', 'movement' => $movement], 201);
        }

        return redirect()->route('stock-movements.index')->with('success', 'Stock movement recorded successfully');
    }

    public function show(StockMovement $stockMovement)
    {
        $stockMovement->load(['inventory', 'warehouse', 'user']);

        if (request()->ajax()) {
            return response()->json(['movement' => $stockMovement]);
        }

        return view('stock-movements.show', ['movement' => $stockMovement]);
    }

    public function edit(StockMovement $stockMovement)
    {
        $inventories = Inventory::latest()->get();
        $warehouses = Warehouse::where('is_active', true)->get();
        return view('stock-movements.edit', ['movement' => $stockMovement, 'inventories' => $inventories, 'warehouses' => $warehouses]);
    }

    public function update(Request $request, StockMovement $stockMovement)
    {
        $validated = $request->validate([
            'inventory_id' => 'required|exists:inventories,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'type' => 'required|string|in:inbound,outbound,transfer,adjustment',
            'quantity' => 'required|integer|min:1',
            'unit_cost' => 'required|numeric|min:0',
            'from_location' => 'nullable|string|max:255',
            'to_location' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $validated['total_cost'] = $validated['quantity'] * $validated['unit_cost'];

        $stockMovement->update($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'Stock movement updated successfully', 'movement' => $stockMovement]);
        }

        return redirect()->route('stock-movements.index')->with('success', 'Stock movement updated successfully');
    }

    public function destroy(StockMovement $stockMovement)
    {
        $stockMovement->delete();

        if (request()->ajax()) {
            return response()->json(['message' => 'Stock movement deleted successfully']);
        }

        return redirect()->route('stock-movements.index')->with('success', 'Stock movement deleted successfully');
    }
}

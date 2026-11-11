<?php

namespace App\Http\Controllers;

use App\Models\VehicleType;
use Illuminate\Http\Request;

class VehicleTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = VehicleType::query();

        if ($request->has('search') && $request->search !== null) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        }

        $vehicleTypes = $query->latest()->paginate(15)->withQueryString();

        if ($request->ajax()) {
            return response()->json(['vehicleTypes' => $vehicleTypes]);
        }

        return view('vehicle-types.index', compact('vehicleTypes'));
    }

    public function create()
    {
        return view('vehicle-types.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:vehicle_types,name',
            'description' => 'nullable|string|max:1000',
            'max_weight' => 'required|numeric|min:0',
            'max_volume' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);
        $validated['is_active'] = $request->boolean('is_active', true);

        $vehicleType = VehicleType::create($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'Vehicle type created successfully', 'vehicleType' => $vehicleType], 201);
        }

        return redirect()->route('vehicle-types.index')->with('success', 'Vehicle type created successfully');
    }

    public function show(VehicleType $vehicleType)
    {
        $vehicleType->load('vehicles');

        if (request()->ajax()) {
            return response()->json(['vehicleType' => $vehicleType]);
        }

        return view('vehicle-types.show', compact('vehicleType'));
    }

    public function edit(VehicleType $vehicleType)
    {
        return view('vehicle-types.edit', compact('vehicleType'));
    }

    public function update(Request $request, VehicleType $vehicleType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:vehicle_types,name,' . $vehicleType->id,
            'description' => 'nullable|string|max:1000',
            'max_weight' => 'required|numeric|min:0',
            'max_volume' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);
        $validated['is_active'] = $request->boolean('is_active', $vehicleType->is_active);

        $vehicleType->update($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'Vehicle type updated successfully', 'vehicleType' => $vehicleType]);
        }

        return redirect()->route('vehicle-types.index')->with('success', 'Vehicle type updated successfully');
    }

    public function destroy(VehicleType $vehicleType)
    {
        $vehicleType->delete();

        if (request()->ajax()) {
            return response()->json(['message' => 'Vehicle type deleted successfully']);
        }

        return redirect()->route('vehicle-types.index')->with('success', 'Vehicle type deleted successfully');
    }
}

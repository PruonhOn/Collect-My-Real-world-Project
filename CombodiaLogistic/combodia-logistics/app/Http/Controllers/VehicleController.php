<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Models\Branch;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index(Request $request)
    {
        $query = Vehicle::with(['branch', 'vehicleType']);

        if ($request->has('search') && $request->search !== null) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('plate_number', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%");
            });
        }

        if ($request->has('branch_id') && $request->branch_id !== null) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('vehicle_type_id') && $request->vehicle_type_id !== null) {
            $query->where('vehicle_type_id', $request->vehicle_type_id);
        }

        if ($request->has('status') && $request->status !== null) {
            $query->where('status', $request->status);
        }

        $vehicles = $query->latest()->paginate(15)->withQueryString();
        $branches = Branch::where('is_active', true)->get();
        $vehicleTypes = VehicleType::where('is_active', true)->get();

        if ($request->ajax()) {
            return response()->json(['vehicles' => $vehicles, 'branches' => $branches, 'vehicleTypes' => $vehicleTypes]);
        }

        return view('vehicles.index', compact('vehicles', 'branches', 'vehicleTypes'));
    }

    public function create()
    {
        $branches = Branch::where('is_active', true)->get();
        $vehicleTypes = VehicleType::where('is_active', true)->get();
        return view('vehicles.create', compact('branches', 'vehicleTypes'));
    }

    public function store(StoreVehicleRequest $request)
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', true);

        $vehicle = Vehicle::create($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'Vehicle created successfully', 'vehicle' => $vehicle], 201);
        }

        return redirect()->route('vehicles.index')->with('success', 'Vehicle created successfully');
    }

    public function show(Vehicle $vehicle)
    {
        $vehicle->load(['branch', 'vehicleType', 'driver', 'vehicleMaintenances']);

        if (request()->ajax()) {
            return response()->json(['vehicle' => $vehicle]);
        }

        return view('vehicles.show', compact('vehicle'));
    }

    public function edit(Vehicle $vehicle)
    {
        $branches = Branch::where('is_active', true)->get();
        $vehicleTypes = VehicleType::where('is_active', true)->get();
        return view('vehicles.edit', compact('vehicle', 'branches', 'vehicleTypes'));
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle)
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', $vehicle->is_active);

        $vehicle->update($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'Vehicle updated successfully', 'vehicle' => $vehicle]);
        }

        return redirect()->route('vehicles.index')->with('success', 'Vehicle updated successfully');
    }

    public function destroy(Vehicle $vehicle)
    {
        $vehicle->delete();

        if (request()->ajax()) {
            return response()->json(['message' => 'Vehicle deleted successfully']);
        }

        return redirect()->route('vehicles.index')->with('success', 'Vehicle deleted successfully');
    }
}

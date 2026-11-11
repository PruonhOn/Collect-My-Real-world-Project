<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\VehicleMaintenance;
use Illuminate\Http\Request;

class VehicleMaintenanceController extends Controller
{
    public function index(Request $request)
    {
        $query = VehicleMaintenance::with(['vehicle', 'driver']);

        if ($request->has('vehicle_id') && $request->vehicle_id !== null) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        if ($request->has('status') && $request->status !== null) {
            $query->where('status', $request->status);
        }

        if ($request->has('search') && $request->search !== null) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('maintenance_type', 'like', "%{$search}%");
            });
        }

        $maintenances = $query->latest()->paginate(15)->withQueryString();
        $vehicles = Vehicle::where('is_active', true)->get();
        $drivers = Driver::where('is_active', true)->get();

        if ($request->ajax()) {
            return response()->json(['maintenances' => $maintenances, 'vehicles' => $vehicles, 'drivers' => $drivers]);
        }

        return view('vehicle-maintenances.index', compact('maintenances', 'vehicles', 'drivers'));
    }

    public function create()
    {
        $vehicles = Vehicle::where('is_active', true)->get();
        $drivers = Driver::where('is_active', true)->get();
        return view('vehicle-maintenances.create', compact('vehicles', 'drivers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'driver_id' => 'nullable|exists:drivers,id',
            'maintenance_type' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'scheduled_date' => 'required|date',
            'completed_date' => 'nullable|date|after_or_equal:scheduled_date',
            'cost' => 'required|numeric|min:0',
            'status' => 'required|string|in:pending,in_progress,completed,cancelled',
            'notes' => 'nullable|string|max:1000',
            'performed_by' => 'nullable|string|max:255',
        ]);

        $maintenance = VehicleMaintenance::create($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'Maintenance record created successfully', 'maintenance' => $maintenance], 201);
        }

        return redirect()->route('vehicle-maintenances.index')->with('success', 'Maintenance record created successfully');
    }

    public function show(VehicleMaintenance $vehicleMaintenance)
    {
        $vehicleMaintenance->load(['vehicle', 'driver']);

        if (request()->ajax()) {
            return response()->json(['maintenance' => $vehicleMaintenance]);
        }

        return view('vehicle-maintenances.show', ['maintenance' => $vehicleMaintenance]);
    }

    public function edit(VehicleMaintenance $vehicleMaintenance)
    {
        $vehicles = Vehicle::where('is_active', true)->get();
        $drivers = Driver::where('is_active', true)->get();
        return view('vehicle-maintenances.edit', ['maintenance' => $vehicleMaintenance, 'vehicles' => $vehicles, 'drivers' => $drivers]);
    }

    public function update(Request $request, VehicleMaintenance $vehicleMaintenance)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'driver_id' => 'nullable|exists:drivers,id',
            'maintenance_type' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'scheduled_date' => 'required|date',
            'completed_date' => 'nullable|date|after_or_equal:scheduled_date',
            'cost' => 'required|numeric|min:0',
            'status' => 'required|string|in:pending,in_progress,completed,cancelled',
            'notes' => 'nullable|string|max:1000',
            'performed_by' => 'nullable|string|max:255',
        ]);

        $vehicleMaintenance->update($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'Maintenance record updated successfully', 'maintenance' => $vehicleMaintenance]);
        }

        return redirect()->route('vehicle-maintenances.index')->with('success', 'Maintenance record updated successfully');
    }

    public function destroy(VehicleMaintenance $vehicleMaintenance)
    {
        $vehicleMaintenance->delete();

        if (request()->ajax()) {
            return response()->json(['message' => 'Maintenance record deleted successfully']);
        }

        return redirect()->route('vehicle-maintenances.index')->with('success', 'Maintenance record deleted successfully');
    }
}

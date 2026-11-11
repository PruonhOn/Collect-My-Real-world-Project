<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use App\Services\VehicleMaintenanceService;
use App\Services\VehicleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VehicleMaintenanceController extends Controller
{
    public function __construct(
        protected VehicleMaintenanceService $maintenanceService,
        protected VehicleService $vehicleService,
        protected AuditLogService $auditLogService
    ) {}

    public function index(Request $request): View
    {
        $maintenances = $this->maintenanceService->getAll($request->only([
            'vehicle_id', 'status', 'maintenance_type', 'search',
        ]));
        $vehicles = $this->vehicleService->getActiveVehicles();
        return view('fleet.maintenance.index', compact('maintenances', 'vehicles'));
    }

    public function create(Request $request): View
    {
        $vehicleId = $request->get('vehicle_id');
        $vehicles = $this->vehicleService->getActiveVehicles();
        return view('fleet.maintenance.create', compact('vehicles', 'vehicleId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'reference_no' => 'nullable|string|max:255',
            'maintenance_type' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
            'maintenance_date' => 'required|date',
            'next_maintenance_date' => 'nullable|date|after:maintenance_date',
            'cost' => 'required|numeric|min:0',
            'vendor' => 'nullable|string|max:255',
            'status' => 'required|string|max:50',
            'notes' => 'nullable|string|max:1000',
            'vehicle_id' => 'required|exists:vehicles,id',
        ]);

        try {
            $validated['created_by'] = auth()->id();
            $maintenance = $this->maintenanceService->create($validated);

            $this->auditLogService->log('created', 'vehicle_maintenances', "Maintenance created for vehicle #{$maintenance->vehicle_id}", $maintenance, null, $validated, auth()->id());

            return redirect()->route('vehicle-maintenances.index')
                ->with('success', 'Vehicle maintenance record created successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create maintenance record: ' . $e->getMessage())->withInput();
        }
    }

    public function show(int $id): View
    {
        $maintenance = $this->maintenanceService->getById($id);
        return view('fleet.maintenance.show', compact('maintenance'));
    }

    public function edit(int $id): View
    {
        $maintenance = $this->maintenanceService->getById($id);
        $vehicles = $this->vehicleService->getActiveVehicles();
        return view('fleet.maintenance.edit', compact('maintenance', 'vehicles'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'reference_no' => 'nullable|string|max:255',
            'maintenance_type' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
            'maintenance_date' => 'required|date',
            'next_maintenance_date' => 'nullable|date|after:maintenance_date',
            'cost' => 'required|numeric|min:0',
            'vendor' => 'nullable|string|max:255',
            'status' => 'required|string|max:50',
            'notes' => 'nullable|string|max:1000',
            'vehicle_id' => 'required|exists:vehicles,id',
        ]);

        try {
            $maintenance = $this->maintenanceService->update($id, $validated);

            $this->auditLogService->log('updated', 'vehicle_maintenances', "Maintenance updated for vehicle #{$maintenance->vehicle_id}", $maintenance, null, $validated, auth()->id());

            return redirect()->route('vehicle-maintenances.index')
                ->with('success', 'Vehicle maintenance record updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update maintenance record: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $maintenance = $this->maintenanceService->getById($id);
            $this->maintenanceService->delete($id);

            $this->auditLogService->log('deleted', 'vehicle_maintenances', "Maintenance deleted for vehicle #{$maintenance->vehicle_id}", $maintenance, null, null, auth()->id());

            return redirect()->route('vehicle-maintenances.index')
                ->with('success', 'Vehicle maintenance record deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete maintenance record: ' . $e->getMessage());
        }
    }
}

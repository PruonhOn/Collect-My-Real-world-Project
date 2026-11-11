<?php

namespace App\Http\Controllers;

use App\Models\VehicleType;
use App\Services\AuditLogService;
use App\Services\BranchService;
use App\Services\VehicleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function __construct(
        protected VehicleService $vehicleService,
        protected BranchService $branchService,
        protected AuditLogService $auditLogService
    ) {}

    public function index(Request $request): View
    {
        $vehicles = $this->vehicleService->getAll($request->only(['search', 'status', 'branch_id', 'is_active']));
        $branches = $this->branchService->getActiveBranches();
        return view('fleet.index', compact('vehicles', 'branches'));
    }

    public function create(): View
    {
        $branches = $this->branchService->getActiveBranches();
        $vehicleTypes = VehicleType::all();
        return view('fleet.create', compact('branches', 'vehicleTypes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:vehicles,code',
            'plate_number' => 'required|string|max:50|unique:vehicles,plate_number',
            'make' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'year' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'color' => 'nullable|string|max:50',
            'capacity_weight' => 'nullable|numeric|min:0',
            'capacity_volume' => 'nullable|numeric|min:0',
            'fuel_type' => 'nullable|string|max:50',
            'fuel_consumption' => 'nullable|numeric|min:0',
            'insurance_number' => 'nullable|string|max:255',
            'insurance_expiry' => 'nullable|date',
            'registration_number' => 'nullable|string|max:255',
            'registration_expiry' => 'nullable|date',
            'status' => 'required|string|max:50',
            'ownership' => 'nullable|string|max:50',
            'purchase_price' => 'nullable|numeric|min:0',
            'purchase_date' => 'nullable|date',
            'is_active' => 'boolean',
            'vehicle_type_id' => 'nullable|exists:vehicle_types,id',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        try {
            $validated['is_active'] = $request->boolean('is_active');
            $validated['created_by'] = auth()->id();
            $vehicle = $this->vehicleService->create($validated);

            $this->auditLogService->log('created', 'vehicles', "Vehicle created: {$vehicle->plate_number}", $vehicle, null, $validated, auth()->id());

            return redirect()->route('vehicles.index')
                ->with('success', 'Vehicle created successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create vehicle: ' . $e->getMessage())->withInput();
        }
    }

    public function show(int $id): View
    {
        $vehicle = $this->vehicleService->getById($id);
        return view('fleet.show', compact('vehicle'));
    }

    public function edit(int $id): View
    {
        $vehicle = $this->vehicleService->getById($id);
        $branches = $this->branchService->getActiveBranches();
        $vehicleTypes = VehicleType::all();
        return view('fleet.edit', compact('vehicle', 'branches', 'vehicleTypes'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:vehicles,code,' . $id,
            'plate_number' => 'required|string|max:50|unique:vehicles,plate_number,' . $id,
            'make' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'year' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'color' => 'nullable|string|max:50',
            'capacity_weight' => 'nullable|numeric|min:0',
            'capacity_volume' => 'nullable|numeric|min:0',
            'fuel_type' => 'nullable|string|max:50',
            'fuel_consumption' => 'nullable|numeric|min:0',
            'insurance_number' => 'nullable|string|max:255',
            'insurance_expiry' => 'nullable|date',
            'registration_number' => 'nullable|string|max:255',
            'registration_expiry' => 'nullable|date',
            'status' => 'required|string|max:50',
            'ownership' => 'nullable|string|max:50',
            'purchase_price' => 'nullable|numeric|min:0',
            'purchase_date' => 'nullable|date',
            'is_active' => 'boolean',
            'vehicle_type_id' => 'nullable|exists:vehicle_types,id',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        try {
            $validated['is_active'] = $request->boolean('is_active');
            $vehicle = $this->vehicleService->update($id, $validated);

            $this->auditLogService->log('updated', 'vehicles', "Vehicle updated: {$vehicle->plate_number}", $vehicle, null, $validated, auth()->id());

            return redirect()->route('vehicles.index')
                ->with('success', 'Vehicle updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update vehicle: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $vehicle = $this->vehicleService->getById($id);
            $this->vehicleService->delete($id);

            $this->auditLogService->log('deleted', 'vehicles', "Vehicle deleted: {$vehicle->plate_number}", $vehicle, null, null, auth()->id());

            return redirect()->route('vehicles.index')
                ->with('success', 'Vehicle deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete vehicle: ' . $e->getMessage());
        }
    }
}

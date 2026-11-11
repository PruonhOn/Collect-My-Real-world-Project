<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use App\Services\WarehouseLocationService;
use App\Services\WarehouseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WarehouseLocationController extends Controller
{
    public function __construct(
        protected WarehouseLocationService $locationService,
        protected WarehouseService $warehouseService,
        protected AuditLogService $auditLogService
    ) {}

    public function index(Request $request): View
    {
        $locations = $this->locationService->getAll($request->only([
            'warehouse_id', 'zone', 'is_active', 'is_occupied', 'search',
        ]));
        $warehouses = $this->warehouseService->getAll(['is_active' => true]);
        return view('warehouses.locations.index', compact('locations', 'warehouses'));
    }

    public function create(Request $request): View
    {
        $warehouseId = $request->get('warehouse_id');
        $warehouses = $this->warehouseService->getAll(['is_active' => true]);
        return view('warehouses.locations.create', compact('warehouses', 'warehouseId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:warehouse_locations,code',
            'name' => 'required|string|max:255',
            'zone' => 'nullable|string|max:100',
            'aisle' => 'nullable|string|max:50',
            'rack' => 'nullable|string|max:50',
            'shelf' => 'nullable|string|max:50',
            'bin' => 'nullable|string|max:50',
            'max_weight' => 'nullable|numeric|min:0',
            'max_volume' => 'nullable|numeric|min:0',
            'is_occupied' => 'boolean',
            'is_active' => 'boolean',
            'warehouse_id' => 'required|exists:warehouses,id',
        ]);

        try {
            $validated['is_occupied'] = $request->boolean('is_occupied');
            $validated['is_active'] = $request->boolean('is_active');
            $location = $this->locationService->create($validated);

            $this->auditLogService->log('created', 'warehouse_locations', "Location created: {$location->code}", $location, null, $validated, auth()->id());

            return redirect()->route('warehouse-locations.index')
                ->with('success', 'Warehouse location created successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create location: ' . $e->getMessage())->withInput();
        }
    }

    public function show(int $id): View
    {
        $location = $this->locationService->getById($id);
        return view('warehouses.locations.show', compact('location'));
    }

    public function edit(int $id): View
    {
        $location = $this->locationService->getById($id);
        $warehouses = $this->warehouseService->getAll(['is_active' => true]);
        return view('warehouses.locations.edit', compact('location', 'warehouses'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:warehouse_locations,code,' . $id,
            'name' => 'required|string|max:255',
            'zone' => 'nullable|string|max:100',
            'aisle' => 'nullable|string|max:50',
            'rack' => 'nullable|string|max:50',
            'shelf' => 'nullable|string|max:50',
            'bin' => 'nullable|string|max:50',
            'max_weight' => 'nullable|numeric|min:0',
            'max_volume' => 'nullable|numeric|min:0',
            'is_occupied' => 'boolean',
            'is_active' => 'boolean',
            'warehouse_id' => 'required|exists:warehouses,id',
        ]);

        try {
            $validated['is_occupied'] = $request->boolean('is_occupied');
            $validated['is_active'] = $request->boolean('is_active');
            $location = $this->locationService->update($id, $validated);

            $this->auditLogService->log('updated', 'warehouse_locations', "Location updated: {$location->code}", $location, null, $validated, auth()->id());

            return redirect()->route('warehouse-locations.index')
                ->with('success', 'Warehouse location updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update location: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $location = $this->locationService->getById($id);
            $this->locationService->delete($id);

            $this->auditLogService->log('deleted', 'warehouse_locations', "Location deleted: {$location->code}", $location, null, null, auth()->id());

            return redirect()->route('warehouse-locations.index')
                ->with('success', 'Warehouse location deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete location: ' . $e->getMessage());
        }
    }
}

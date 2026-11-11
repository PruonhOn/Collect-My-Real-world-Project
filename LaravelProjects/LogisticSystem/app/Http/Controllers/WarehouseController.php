<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use App\Services\BranchService;
use App\Services\WarehouseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    public function __construct(
        protected WarehouseService $warehouseService,
        protected BranchService $branchService,
        protected AuditLogService $auditLogService
    ) {}

    public function index(Request $request): View
    {
        $warehouses = $this->warehouseService->getAll($request->only(['search', 'branch_id', 'is_active']));
        $branches = $this->branchService->getActiveBranches();
        return view('warehouses.index', compact('warehouses', 'branches'));
    }

    public function create(): View
    {
        $branches = $this->branchService->getActiveBranches();
        return view('warehouses.create', compact('branches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:warehouses,code',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'manager_name' => 'nullable|string|max:255',
            'total_capacity' => 'nullable|numeric|min:0',
            'used_capacity' => 'nullable|numeric|min:0',
            'total_area' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        try {
            $validated['is_active'] = $request->boolean('is_active');
            $validated['created_by'] = auth()->id();
            $warehouse = $this->warehouseService->create($validated);

            $this->auditLogService->log('created', 'warehouses', "Warehouse created: {$warehouse->name}", $warehouse, null, $validated, auth()->id());

            return redirect()->route('warehouses.index')
                ->with('success', 'Warehouse created successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create warehouse: ' . $e->getMessage())->withInput();
        }
    }

    public function show(int $id): View
    {
        $warehouse = $this->warehouseService->getById($id);
        return view('warehouses.show', compact('warehouse'));
    }

    public function edit(int $id): View
    {
        $warehouse = $this->warehouseService->getById($id);
        $branches = $this->branchService->getActiveBranches();
        return view('warehouses.edit', compact('warehouse', 'branches'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:warehouses,code,' . $id,
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'manager_name' => 'nullable|string|max:255',
            'total_capacity' => 'nullable|numeric|min:0',
            'used_capacity' => 'nullable|numeric|min:0',
            'total_area' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        try {
            $validated['is_active'] = $request->boolean('is_active');
            $warehouse = $this->warehouseService->update($id, $validated);

            $this->auditLogService->log('updated', 'warehouses', "Warehouse updated: {$warehouse->name}", $warehouse, null, $validated, auth()->id());

            return redirect()->route('warehouses.index')
                ->with('success', 'Warehouse updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update warehouse: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $warehouse = $this->warehouseService->getById($id);
            $this->warehouseService->delete($id);

            $this->auditLogService->log('deleted', 'warehouses', "Warehouse deleted: {$warehouse->name}", $warehouse, null, null, auth()->id());

            return redirect()->route('warehouses.index')
                ->with('success', 'Warehouse deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete warehouse: ' . $e->getMessage());
        }
    }
}

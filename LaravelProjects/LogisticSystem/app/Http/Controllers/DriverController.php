<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use App\Services\BranchService;
use App\Services\DriverService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DriverController extends Controller
{
    public function __construct(
        protected DriverService $driverService,
        protected BranchService $branchService,
        protected AuditLogService $auditLogService
    ) {}

    public function index(Request $request): View
    {
        $drivers = $this->driverService->getAll($request->only(['search', 'status', 'branch_id', 'is_active']));
        $branches = $this->branchService->getActiveBranches();
        return view('drivers.index', compact('drivers', 'branches'));
    }

    public function create(): View
    {
        $branches = $this->branchService->getActiveBranches();
        return view('drivers.create', compact('branches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:drivers,code',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255|unique:drivers,email',
            'license_number' => 'required|string|max:100|unique:drivers,license_number',
            'license_type' => 'nullable|string|max:50',
            'license_expiry' => 'nullable|date',
            'date_of_birth' => 'nullable|date',
            'national_id' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'emergency_contact' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:20',
            'status' => 'required|string|max:50',
            'hire_date' => 'nullable|date',
            'salary' => 'nullable|numeric|min:0',
            'photo' => 'nullable|image|max:2048',
            'is_active' => 'boolean',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        try {
            $validated['is_active'] = $request->boolean('is_active');
            $validated['created_by'] = auth()->id();
            $driver = $this->driverService->create($validated);

            $this->auditLogService->log('created', 'drivers', "Driver created: {$driver->name}", $driver, null, $validated, auth()->id());

            return redirect()->route('drivers.index')
                ->with('success', 'Driver created successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create driver: ' . $e->getMessage())->withInput();
        }
    }

    public function show(int $id): View
    {
        $driver = $this->driverService->getById($id);
        return view('drivers.show', compact('driver'));
    }

    public function edit(int $id): View
    {
        $driver = $this->driverService->getById($id);
        $branches = $this->branchService->getActiveBranches();
        return view('drivers.edit', compact('driver', 'branches'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:drivers,code,' . $id,
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255|unique:drivers,email,' . $id,
            'license_number' => 'required|string|max:100|unique:drivers,license_number,' . $id,
            'license_type' => 'nullable|string|max:50',
            'license_expiry' => 'nullable|date',
            'date_of_birth' => 'nullable|date',
            'national_id' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'emergency_contact' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:20',
            'status' => 'required|string|max:50',
            'hire_date' => 'nullable|date',
            'salary' => 'nullable|numeric|min:0',
            'photo' => 'nullable|image|max:2048',
            'is_active' => 'boolean',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        try {
            $validated['is_active'] = $request->boolean('is_active');
            $driver = $this->driverService->update($id, $validated);

            $this->auditLogService->log('updated', 'drivers', "Driver updated: {$driver->name}", $driver, null, $validated, auth()->id());

            return redirect()->route('drivers.index')
                ->with('success', 'Driver updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update driver: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $driver = $this->driverService->getById($id);
            $this->driverService->delete($id);

            $this->auditLogService->log('deleted', 'drivers', "Driver deleted: {$driver->name}", $driver, null, null, auth()->id());

            return redirect()->route('drivers.index')
                ->with('success', 'Driver deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete driver: ' . $e->getMessage());
        }
    }
}

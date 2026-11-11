<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use App\Services\RoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function __construct(
        protected RoleService $roleService,
        protected AuditLogService $auditLogService
    ) {}

    public function index(Request $request): View
    {
        $roles = $this->roleService->getAll($request->only(['search']));
        return view('admin.roles.index', compact('roles'));
    }

    public function create(): View
    {
        $permissions = $this->roleService->getAllPermissions();
        return view('admin.roles.create', compact('permissions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'slug' => 'nullable|string|max:255|unique:roles,slug',
            'description' => 'nullable|string|max:1000',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        try {
            $role = $this->roleService->create($validated);

            $this->auditLogService->log('created', 'roles', "Role created: {$role->name}", $role, null, $validated, auth()->id());

            return redirect()->route('admin.roles.index')
                ->with('success', 'Role created successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create role: ' . $e->getMessage())->withInput();
        }
    }

    public function edit(int $id): View
    {
        $role = $this->roleService->getById($id);
        $permissions = $this->roleService->getAllPermissions();
        return view('admin.roles.edit', compact('role', 'permissions'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $id,
            'slug' => 'nullable|string|max:255|unique:roles,slug,' . $id,
            'description' => 'nullable|string|max:1000',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        try {
            $role = $this->roleService->update($id, $validated);

            $this->auditLogService->log('updated', 'roles', "Role updated: {$role->name}", $role, null, $validated, auth()->id());

            return redirect()->route('admin.roles.index')
                ->with('success', 'Role updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update role: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $role = $this->roleService->getById($id);
            $this->roleService->delete($id);

            $this->auditLogService->log('deleted', 'roles', "Role deleted: {$role->name}", $role, null, null, auth()->id());

            return redirect()->route('admin.roles.index')
                ->with('success', 'Role deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete role: ' . $e->getMessage());
        }
    }
}

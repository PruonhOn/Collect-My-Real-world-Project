<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $roles = Role::withCount('permissions')->latest()->paginate(15);

        if ($request->ajax()) {
            return response()->json(['roles' => $roles]);
        }

        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        $permissions = Permission::all()->groupBy(function ($permission) {
            $parts = explode('.', $permission->name);
            return $parts[0] ?? 'general';
        });

        return view('roles.create', compact('permissions'));
    }

    public function store(StoreRoleRequest $request)
    {
        $validated = $request->validated();
        $role = Role::create(['name' => $validated['name'], 'description' => $validated['description'] ?? null]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        if ($request->ajax()) {
            return response()->json(['message' => 'Role created successfully', 'role' => $role], 201);
        }

        return redirect()->route('roles.index')->with('success', 'Role created successfully');
    }

    public function show(Role $role)
    {
        $role->load('permissions');

        if (request()->ajax()) {
            return response()->json(['role' => $role]);
        }

        return view('roles.show', compact('role'));
    }

    public function edit(Role $role)
    {
        $role->load('permissions');
        $permissions = Permission::all()->groupBy(function ($permission) {
            $parts = explode('.', $permission->name);
            return $parts[0] ?? 'general';
        });
        $rolePermissions = $role->permissions->pluck('name')->toArray();

        return view('roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    public function update(UpdateRoleRequest $request, Role $role)
    {
        $validated = $request->validated();
        $role->update(['name' => $validated['name'], 'description' => $validated['description'] ?? $role->description]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        } else {
            $role->syncPermissions([]);
        }

        if ($request->ajax()) {
            return response()->json(['message' => 'Role updated successfully', 'role' => $role]);
        }

        return redirect()->route('roles.index')->with('success', 'Role updated successfully');
    }

    public function destroy(Role $role)
    {
        $role->delete();

        if (request()->ajax()) {
            return response()->json(['message' => 'Role deleted successfully']);
        }

        return redirect()->route('roles.index')->with('success', 'Role deleted successfully');
    }
}

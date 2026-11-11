<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('permissions')->get();

        return view('admin.roles.index', compact('roles'));
    }

    public function edit(Role $role)
    {
        $role->load('permissions');
        $permissions = Permission::all()->groupBy(fn ($p) => explode('.', $p->name)[0] ?? 'other');

        return view('admin.roles.edit', compact('role', 'permissions'));
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('admin.roles.index')
            ->with('success', __('messages.updated_successfully'));
    }
}

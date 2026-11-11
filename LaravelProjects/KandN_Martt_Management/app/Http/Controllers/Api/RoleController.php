<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $roles = Role::with('permissions')->get();
        return $this->success($roles);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles,name',
            'guard_name' => 'nullable|string|max:50',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $role = Role::create([
            'name' => $request->name,
            'guard_name' => $request->guard_name ?? 'web',
        ]);

        if ($request->has('permissions')) {
            $permissions = Permission::whereIn('name', $request->permissions)->get();
            $role->syncPermissions($permissions);
        }

        return $this->success($role->load('permissions'), 'Role created successfully', 201);
    }

    public function show(int $id)
    {
        $role = Role::with('permissions')->find($id);

        if (!$role) {
            return $this->error('Role not found', 404);
        }

        return $this->success($role);
    }

    public function update(Request $request, int $id)
    {
        $role = Role::find($id);

        if (!$role) {
            return $this->error('Role not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255|unique:roles,name,' . $id,
            'guard_name' => 'nullable|string|max:50',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $data = $request->only(['name', 'guard_name']);
        if (!empty($data)) {
            $role->update($data);
        }

        if ($request->has('permissions')) {
            $permissions = Permission::whereIn('name', $request->permissions)->get();
            $role->syncPermissions($permissions);
        }

        return $this->success($role->fresh()->load('permissions'), 'Role updated successfully');
    }

    public function destroy(int $id)
    {
        $role = Role::find($id);

        if (!$role) {
            return $this->error('Role not found', 404);
        }

        if ($role->users()->count() > 0) {
            return $this->error('Cannot delete role with associated users', 409);
        }

        $role->delete();

        return $this->success(null, 'Role deleted successfully');
    }

    public function assignRole(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'role' => 'required|string|exists:roles,name',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = User::findOrFail($request->user_id);
        $user->assignRole($request->role);

        return $this->success($user->load('roles.permissions'), 'Role assigned successfully');
    }

    public function removeRole(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'role' => 'required|string|exists:roles,name',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = User::findOrFail($request->user_id);
        $user->removeRole($request->role);

        return $this->success($user->load('roles.permissions'), 'Role removed successfully');
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\RoleResource;
use App\Support\ApiResponse;
use App\Support\PermissionCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Roles and their grants: what a role is called and which permissions it holds.
 *
 * The roles and permissions tables are owned by Spatie, so this controller is
 * thin: it lists, creates, updates and deletes roles, and syncs the permission
 * set each one holds. Deleting a role that users hold is refused by Spatie's
 * own database constraints rather than by code here.
 */
class RoleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $roles = Role::query()
            ->with('permissions')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role): array => new RoleResource($role)->resolve());

        return ApiResponse::success($roles->values()->all(), 'Roles retrieved.');
    }

    public function show(Request $request, string $role): JsonResponse
    {
        $record = Role::query()->with('permissions')->where('name', $role)->firstOrFail();

        return ApiResponse::success(new RoleResource($record)->resolve(), 'Role retrieved.');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'guard_name' => ['sometimes', 'string', 'max:255'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::query()->create([
            'name' => $validated['name'],
            'guard_name' => $validated['guard_name'] ?? PermissionCatalog::guard(),
        ]);

        if (array_key_exists('permissions', $validated)) {
            $role->syncPermissions($validated['permissions']);
        }

        return ApiResponse::created(
            new RoleResource($role->load('permissions'))->resolve(),
            'Role created.',
            '/api/v1/admin/roles/'.$role->name,
        );
    }

    public function update(Request $request, string $role): JsonResponse
    {
        $record = Role::query()->where('name', $role)->firstOrFail();

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255', 'unique:roles,name,'.$record->getKey().',id'],
            'guard_name' => ['sometimes', 'string', 'max:255'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        if (array_key_exists('name', $validated)) {
            $record->name = $validated['name'];
        }

        if (array_key_exists('guard_name', $validated)) {
            $record->guard_name = $validated['guard_name'];
        }

        $record->save();

        if (array_key_exists('permissions', $validated)) {
            $record->syncPermissions($validated['permissions']);
        }

        return ApiResponse::success(
            new RoleResource($record->load('permissions'))->resolve(),
            'Role updated.',
        );
    }

    public function destroy(Request $request, string $role): JsonResponse
    {
        $record = Role::query()->where('name', $role)->firstOrFail();

        // Refuse to delete a role that is still assigned to any user.
        if ($record->users()->exists()) {
            return ApiResponse::conflict(
                'This role is still assigned to one or more users and cannot be deleted.',
                ['role' => 'This role is still assigned to one or more users.'],
            );
        }

        $record->delete();

        return ApiResponse::success(null, 'Role deleted.');
    }
}

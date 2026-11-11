<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminUserResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->with('roles')
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $search = '%'.$request->string('search').'%';
                    $q->where('name', 'like', $search)
                        ->orWhere('email', 'like', $search);
                });
            })
            ->latest('id')
            ->paginate($request->integer('per_page', 15));

        return $this->success([
            'users' => AdminUserResource::collection($users),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ], 'Users retrieved.');
    }

    public function update(string $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['sometimes', Rule::in([User::STATUS_ACTIVE, User::STATUS_INACTIVE])],
            'vip_until' => ['sometimes', 'nullable', 'date'],
            'role' => ['sometimes', 'nullable', Rule::in([User::ROLE_ADMIN, User::ROLE_USER])],
        ]);

        $user = User::find($id);

        if (! $user) {
            return $this->error('User not found.', null, 404);
        }

        if (array_key_exists('status', $validated)) {
            $user->status = $validated['status'];
        }

        if (array_key_exists('vip_until', $validated)) {
            $user->vip_until = $validated['vip_until'];
        }

        if (array_key_exists('role', $validated) && $validated['role']) {
            $role = Role::firstOrCreate(['name' => $validated['role']]);
            $user->roles()->sync([$role->id]);
        }

        $user->save();

        return $this->success(
            new AdminUserResource($user->load('roles')),
            'User updated.'
        );
    }
}

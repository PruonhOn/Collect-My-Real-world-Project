<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query()->withCount(['ownedBusinesses', 'memberships']);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->query('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->query('status') === 'disabled') {
            $query->where('is_active', false);
        }

        $users = $query->orderBy('created_at', 'desc')
            ->paginate(min(100, (int) $request->query('per_page', 20)));

        return response()->json($users);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json([
            'data' => $user->load(['ownedBusinesses', 'memberships.business']),
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'is_active' => ['sometimes', 'boolean'],
            'is_super_admin' => ['sometimes', 'boolean'],
        ]);

        if (($validated['is_super_admin'] ?? false) && ! $user->is_super_admin) {
            $this->audit($request, $user, 'grant_super_admin', 'Super admin rights granted');
        }

        if (array_key_exists('is_super_admin', $validated) && ! $validated['is_super_admin'] && $user->is_super_admin) {
            if ($user->id === $request->user()->id) {
                return response()->json(['message' => 'You cannot remove your own super admin rights.'], 422);
            }
            $this->audit($request, $user, 'revoke_super_admin', 'Super admin rights revoked');
        }

        $user->fill($validated)->save();

        return response()->json(['data' => $user->fresh()]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'You cannot delete your own account.'], 422);
        }

        $this->audit($request, $user, 'delete_user', 'User deleted by administrator');

        $user->delete();

        return response()->json(['message' => 'User deleted.']);
    }

    protected function audit(Request $request, User $target, string $action, string $reason): void
    {
        \App\Models\AdminAuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'admin.'.$action,
            'target_type' => User::class,
            'target_id' => $target->id,
            'reason' => $reason,
            'old_values' => ['id' => $target->id, 'is_super_admin' => $target->is_super_admin, 'is_active' => $target->is_active],
            'new_values' => ['id' => $target->id],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}

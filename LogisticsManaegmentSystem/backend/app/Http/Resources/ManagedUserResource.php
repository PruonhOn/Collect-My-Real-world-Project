<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A user as the administration screen sees it.
 *
 * Deliberately not UserResource. That one is the *session* contract - it carries
 * the signed-in user's full permission list - and it is what the SPA's
 * CurrentUser type is built on. Reusing it for a paginated list would emit every
 * permission of every row, which is both wasteful and, because
 * `preventLazyLoading` is on outside production, a relation read the listing did
 * not declare.
 *
 * @mixin User
 */
class ManagedUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar_path' => $user->avatar_path,

            'is_active' => $user->is_active,
            'last_login_at' => $user->last_login_at?->toIso8601String(),

            'roles' => $user->roleNames(),

            // The super-admin account is the break-glass identity and the server
            // refuses to deactivate, demote or delete it. Surfaced so the screen
            // can disable those controls rather than offer an action that always
            // answers 422.
            'is_protected' => $user->hasRole('super-admin'),

            'created_at' => $user->created_at?->toIso8601String(),
            'updated_at' => $user->updated_at?->toIso8601String(),
        ];
    }
}

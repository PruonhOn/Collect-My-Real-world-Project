<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The session payload: who is signed in, and what they may do.
 *
 * This is the one resource the frontend treats as a contract - it is
 * `CurrentUser` in frontend/src/types/api.ts. Adding a field here means adding
 * it there in the same commit, or the SPA will read `undefined` for it.
 *
 * Hand to ApiResponse with `->resolve()`. The response envelope is applied by
 * App\Support\ApiResponse, so letting the resource wrap itself in `data` as
 * well would nest the payload one level too deep.
 */
class UserResource extends JsonResource
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
            'last_login_at' => $user->last_login_at,

            // A user account may later be scoped to a single customer, so the
            // field is part of the contract from the start. Until the customers
            // table exists there is nothing to point at and this is always null.
            'customer' => null,

            // Flat name lists rather than role and permission objects. The SPA
            // only ever asks "does this list contain X", and shipping the full
            // permission table to every browser to answer that would be wasteful
            // as the inventory grows.
            'roles' => $user->roleNames(),
            'permissions' => $user->permissionNames(),
        ];
    }
}

<?php

namespace App\Services\Tenancy;

use App\Models\Business;
use App\Models\BusinessMembership;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class BusinessContextService
{
    /**
     * Resolve the active business for a user from an explicit identifier.
     * Membership must be active; ownership always grants access.
     */
    public function resolve(User $user, int $businessId): ?Business
    {
        $business = Business::find($businessId);

        if (! $business) {
            return null;
        }

        if ($business->owner_id === $user->id) {
            return $business;
        }

        $membership = $business->memberships()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        return $membership ? $business : null;
    }

    public function assertMembership(User $user, Business $business): BusinessMembership
    {
        if ($business->owner_id === $user->id) {
            $membership = $business->memberships()->where('user_id', $user->id)->first();
            if ($membership) {
                return $membership;
            }
            throw new AuthorizationException('Owner membership record missing.');
        }

        $membership = $business->memberships()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (! $membership) {
            throw new AuthorizationException('Not an active member of this business.');
        }

        return $membership;
    }

    /**
     * Run a callback with a specific business bound as the tenant context.
     * Used by queued jobs, console commands and scheduled tasks so that
     * the tenant scope applies inside the callback.
     */
    public function runIn(Business $business, callable $callback): mixed
    {
        $previous = app()->bound('current.business') ? app('current.business') : null;

        app()->instance('current.business', $business);

        try {
            return $callback();
        } finally {
            if ($previous) {
                app()->instance('current.business', $previous);
            } else {
                app()->forgetInstance('current.business');
            }
        }
    }
}

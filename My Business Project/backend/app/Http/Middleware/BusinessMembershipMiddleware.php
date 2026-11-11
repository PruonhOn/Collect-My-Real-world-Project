<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BusinessMembershipMiddleware
{
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        $user = $request->user();
        $business = app('current.business') ?? $request->attributes->get('current_business');

        if (! $user || ! $business) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        // Owner has full access
        if ($business->owner_id === $user->id) {
            return $next($request);
        }

        $membership = $business->memberships()->where('user_id', $user->id)->where('status', 'active')->first();

        if (! $membership) {
            return response()->json(['message' => 'Not a member of this business.'], 403);
        }

        if (in_array($membership->role, ['admin'])) {
            // Admins get broad access by default; specific permissions can be enforced via policies
            if (empty($permissions)) {
                return $next($request);
            }
        }

        if (! empty($permissions)) {
            // For now, rely on policies for granular permission checks
        }

        return $next($request);
    }
}

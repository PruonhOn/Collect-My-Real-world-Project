<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBusinessContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $businessId = $request->header('X-Business-Id') ?? $request->input('business_id');

        if (! $businessId) {
            return response()->json(['message' => 'Business context required.'], 400);
        }

        $business = $user->businesses()
            ->where('businesses.id', $businessId)
            ->wherePivot('status', 'active')
            ->first();

        if (! $business && $user->ownedBusinesses()->where('id', $businessId)->exists()) {
            $business = $user->ownedBusinesses()->find($businessId);
        }

        if (! $business) {
            return response()->json(['message' => 'Forbidden or invalid business.'], 403);
        }

        if ($business->is_suspended) {
            return response()->json(['message' => 'Business is suspended.'], 403);
        }

        app()->instance('current.business', $business);
        $request->attributes->set('current_business', $business);

        return $next($request);
    }
}

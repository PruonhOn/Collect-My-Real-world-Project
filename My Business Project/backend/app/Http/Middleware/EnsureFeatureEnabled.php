<?php

namespace App\Http\Middleware;

use App\Services\Features\FeatureGate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeatureEnabled
{
    public function __construct(protected FeatureGate $gate)
    {
    }

    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $business = app('current.business') ?? $request->attributes->get('current_business');

        if (! $business) {
            return response()->json(['message' => 'Business context required.'], 400);
        }

        if (! $this->gate->allows($business, $feature)) {
            return response()->json([
                'message' => 'This feature is not available on your current plan.',
                'feature' => $feature,
                'upgrade_required' => true,
            ], 403);
        }

        return $next($request);
    }
}

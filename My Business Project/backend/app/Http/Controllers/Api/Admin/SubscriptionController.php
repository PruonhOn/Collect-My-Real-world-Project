<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Models\BusinessSubscription;
use App\Services\Billing\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
{
    public function __construct(protected SubscriptionService $subscriptions) {}

    public function index(Request $request): JsonResponse
    {
        $query = BusinessSubscription::query()->with(['business', 'plan']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($plan = $request->query('plan_id')) {
            $query->where('plan_id', $plan);
        }

        return response()->json($query->orderBy('created_at', 'desc')
            ->paginate(min(100, (int) $request->query('per_page', 20))));
    }

    public function show(BusinessSubscription $subscription): JsonResponse
    {
        return response()->json([
            'data' => $subscription->load(['business', 'plan']),
        ]);
    }

    public function update(Request $request, BusinessSubscription $subscription): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['trialing', 'active', 'past_due', 'canceled', 'expired', 'suspended'])],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $business = $subscription->business;
        $plan = $subscription->plan;

        $attributes = [];

        if ($validated['status'] === 'active') {
            $attributes = [
                'billing_cycle' => $subscription->billing_cycle ?? 'monthly',
                'starts_at' => $subscription->starts_at ?? now(),
                'ends_at' => $subscription->ends_at ?? now()->addMonth(),
            ];
        }

        $updated = $this->subscriptions->transition(
            $business,
            $plan,
            $validated['status'],
            $attributes,
            $request->user()->id,
            $validated['reason']
        );

        return response()->json(['data' => $updated]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vip\SubscribeVipRequest;
use App\Http\Resources\VipPlanResource;
use App\Models\VipPlan;
use App\Models\VipSubscription;
use App\Services\NotificationService;
use App\Services\VipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VipController extends Controller
{
    public function __construct(
        private readonly VipService $vipService,
        private readonly NotificationService $notifications,
    ) {}

    public function plans(Request $request): JsonResponse
    {
        $plans = VipPlan::query()
            ->active()
            ->with('translations')
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();

        return $this->success([
            'plans' => VipPlanResource::collection($plans),
        ], 'VIP plans retrieved.');
    }

    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        $subscription = $user->vipSubscriptions()
            ->where('status', VipSubscription::STATUS_ACTIVE)
            ->where(function ($query) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->latest('id')
            ->with('plan.translations')
            ->first();

        return $this->success([
            'is_vip' => $user->isVipActive(),
            'vip_until' => $user->vip_until?->toIso8601String(),
            'subscription' => $subscription ? new VipPlanResource($subscription->plan) : null,
        ], 'VIP status retrieved.');
    }

    public function subscribe(SubscribeVipRequest $request): JsonResponse
    {
        $plan = VipPlan::findOrFail($request->integer('plan_id'));

        $subscription = $this->vipService->subscribe($request->user(), $plan);

        $plan->load('translations');

        $this->notifications->vipSubscribed($request->user(), $plan, $subscription);

        return $this->success([
            'subscription' => [
                'id' => $subscription->id,
                'status' => $subscription->status,
                'starts_at' => $subscription->started_at?->toIso8601String(),
                'ends_at' => $subscription->ends_at?->toIso8601String(),
            ],
            'plan' => new VipPlanResource($plan),
            'is_vip' => $request->user()->isVipActive(),
            'vip_until' => $request->user()->vip_until?->toIso8601String(),
        ], 'Subscribed to VIP successfully.', 201);
    }
}

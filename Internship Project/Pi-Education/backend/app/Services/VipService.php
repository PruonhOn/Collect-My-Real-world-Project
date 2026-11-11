<?php

namespace App\Services;

use App\Models\User;
use App\Models\VipPlan;
use App\Models\VipSubscription;
use Illuminate\Support\Carbon;

class VipService
{
    public function subscribe(User $user, VipPlan $plan): VipSubscription
    {
        $base = $user->vip_until?->isFuture() ? $user->vip_until : Carbon::now();

        $endsAt = $base->copy()->addDays($plan->duration_days);

        $user->forceFill(['vip_until' => $endsAt])->save();

        return $user->vipSubscriptions()->create([
            'vip_plan_id' => $plan->id,
            'status' => VipSubscription::STATUS_ACTIVE,
            'started_at' => now(),
            'ends_at' => $endsAt,
        ]);
    }
}

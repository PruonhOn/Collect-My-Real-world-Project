<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\User;
use App\Models\VipPlan;
use Illuminate\Support\Carbon;

class VipService
{
    public const ITEM_KEY_PREFIX = 'vip:';

    public static function isActive(User $user): bool
    {
        return $user->vip_until !== null && $user->vip_until->isFuture();
    }

    public static function expiresAt(User $user): ?Carbon
    {
        return $user->vip_until instanceof Carbon ? $user->vip_until : null;
    }

    /**
     * Activate (or extend) VIP membership for a user.
     */
    public static function activate(User $user, VipPlan $plan): void
    {
        $base = self::isActive($user) ? $user->vip_until : now();

        $user->update([
            'vip_until' => $base->copy()->addDays($plan->duration_days),
            'vip_plan' => $plan->slug,
        ]);
    }

    /**
     * Resolve a VIP plan from a purchase item key (e.g. "vip:monthly").
     */
    public static function planForItemKey(string $itemKey): ?VipPlan
    {
        if (! str_starts_with($itemKey, self::ITEM_KEY_PREFIX)) {
            return null;
        }

        $slug = substr($itemKey, strlen(self::ITEM_KEY_PREFIX));

        return VipPlan::where('slug', $slug)->first();
    }

    public static function isVipPurchase(Purchase $purchase): bool
    {
        if (! is_string($purchase->external_item_key) || $purchase->external_item_key === '') {
            return false;
        }

        return str_starts_with($purchase->external_item_key, self::ITEM_KEY_PREFIX);
    }
}

<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\User;
use App\Models\VipPlan;
use Illuminate\Support\Carbon;

class VipService
{
    public const ITEM_KEY_PREFIX = 'vip:';

    /**
     * A VIP membership always runs for exactly one year.
     */
    public const SUBSCRIPTION_YEARS = 1;

    /**
     * Day count mirroring the fixed one-year term, kept on the plan record
     * so pricing pages can describe the term.
     */
    public const SUBSCRIPTION_DAYS = 365;

    /**
     * Development-only test card: $0.01 for 5 hours via the real KHQR flow.
     * Hidden and unpurchasable unless VIP_TEST_MODE=true.
     */
    public const TEST_PLAN_SLUG = 'vip-test-5h';

    public const TEST_SUBSCRIPTION_HOURS = 5;

    public const TEST_PLAN_PRICE = 0.01;

    public static function testModeEnabled(): bool
    {
        return (bool) config('vip.test_mode', false);
    }

    public static function isTestPlan(VipPlan $plan): bool
    {
        return (bool) $plan->is_test;
    }

    public static function isActive(User $user): bool
    {
        return $user->vip_until !== null && $user->vip_until->isFuture();
    }

    public static function expiresAt(User $user): ?Carbon
    {
        return $user->vip_until instanceof Carbon ? $user->vip_until : null;
    }

    /**
     * The expiry a membership purchased right now gets: one year out.
     */
    public static function renewalExpiry(): Carbon
    {
        return now()->addYears(self::SUBSCRIPTION_YEARS);
    }

    /**
     * The expiry a plan grants right now. Test cards last the plan's
     * configured hours; the production VIP always runs one year.
     */
    public static function expiryFor(VipPlan $plan): Carbon
    {
        if (self::isTestPlan($plan)) {
            return now()->addHours((int) ($plan->duration_hours ?: self::TEST_SUBSCRIPTION_HOURS));
        }

        return self::renewalExpiry();
    }

    /**
     * Activate a VIP membership for a user.
     *
     * The term comes from the plan (one year for production VIP, the plan's
     * hours for test cards) and never stacks: renewing a live membership
     * restarts it from today, so every book unlocked by VIP expires exactly
     * when the purchase that unlocked it does.
     */
    public static function activate(User $user, VipPlan $plan): void
    {
        abort_if(self::isTestPlan($plan) && ! self::testModeEnabled(), 403, 'Test VIP cards are disabled outside test mode.');

        $user->update([
            'vip_until' => self::expiryFor($plan),
            'vip_plan' => $plan->slug,
        ]);
    }

    /**
     * Purchase item keys (e.g. "vip:vip-test-5h") for every test plan.
     * Used to keep test revenue out of production revenue reports.
     *
     * @return array<int, string>
     */
    public static function testItemKeys(): array
    {
        return VipPlan::query()
            ->where('is_test', true)
            ->pluck('slug')
            ->map(fn (string $slug) => self::ITEM_KEY_PREFIX.$slug)
            ->values()
            ->all();
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

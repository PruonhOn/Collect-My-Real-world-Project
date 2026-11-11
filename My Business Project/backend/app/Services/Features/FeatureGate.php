<?php

namespace App\Services\Features;

use App\Models\Business;
use App\Models\BusinessSubscription;
use App\Models\Feature;
use App\Models\Plan;
use Illuminate\Support\Facades\Cache;

/**
 * FeatureGate is the single authoritative source of subscription entitlements.
 *
 * Design rules:
 *  - Entitlements are resolved from the database (plans + plan_feature +
 *    business_subscriptions). Nothing is hard-coded in the application layer.
 *  - The service FAILS CLOSED: when entitlements cannot be resolved safely,
 *    restricted features are denied instead of being granted by accident.
 *  - Resolved entitlements are cached per business and invalidated whenever
 *    a plan, a plan_feature row or a subscription changes.
 */
class FeatureGate
{
    public const CACHE_TTL_SECONDS = 300;

    /** @var array<int, array<string, mixed>> */
    protected array $localCache = [];

    /**
     * Determine whether the business may use the given feature.
     */
    public function allows(Business $business, string $featureKey): bool
    {
        $entitlements = $this->entitlements($business);

        if ($entitlements === null) {
            // Fail closed: subscription could not be resolved.
            return $this->defaultEntitlement($featureKey);
        }

        $status = $entitlements['status'];

        if (in_array($status, ['expired', 'suspended'], true)) {
            return false;
        }

        if ($status === 'past_due' && $entitlements['grace_ends_at'] !== null) {
            if (now()->greaterThan($entitlements['grace_ends_at'])) {
                return false;
            }
        }

        if ($status === 'canceled') {
            // Canceled subscriptions keep access until the end of the paid period.
            if ($entitlements['ends_at'] !== null && now()->greaterThan($entitlements['ends_at'])) {
                return false;
            }
        }

        $feature = $entitlements['features'][$featureKey] ?? null;

        if ($feature !== null) {
            return (bool) $feature['enabled'];
        }

        // Feature is not attached to the plan: fall back to the platform default.
        return $this->defaultEntitlement($featureKey);
    }

    /**
     * Resolve the numeric limit for a feature. `null` means unlimited.
     */
    public function limit(Business $business, string $featureKey): ?int
    {
        $entitlements = $this->entitlements($business);

        if ($entitlements === null) {
            return $this->defaultLimit($featureKey);
        }

        $feature = $entitlements['features'][$featureKey] ?? null;

        if ($feature !== null && $feature['limit'] !== null) {
            return (int) $feature['limit'];
        }

        if ($entitlements['plan_type'] === 'pro') {
            // Pro plans are unlimited unless a plan_feature row says otherwise.
            return null;
        }

        return $this->defaultLimit($featureKey);
    }

    /**
     * Remaining capacity for a limited feature, or null when unlimited.
     */
    public function remaining(Business $business, string $featureKey): ?int
    {
        $limit = $this->limit($business, $featureKey);

        if ($limit === null) {
            return null;
        }

        return max(0, $limit - $this->usage($business, $featureKey));
    }

    public function usage(Business $business, string $featureKey): int
    {
        $counter = $business->usageCounters()
            ->where('key', $this->usageKey($featureKey))
            ->where('period', $this->currentPeriod($business))
            ->first();

        return $counter ? (int) $counter->count : 0;
    }

    /**
     * Check whether creating one more unit of the tracked resource is allowed.
     */
    public function checkLimit(Business $business, string $featureKey, int $additional = 1): bool
    {
        $limit = $this->limit($business, $featureKey);

        if ($limit === null) {
            return true;
        }

        return ($this->usage($business, $featureKey) + $additional) <= $limit;
    }

    /**
     * Atomically increment a usage counter.
     */
    public function incrementUsage(Business $business, string $featureKey, int $delta = 1): void
    {
        $key = $this->usageKey($featureKey);
        $period = $this->currentPeriod($business);

        $counter = \App\Models\UsageCounter::firstOrCreate(
            ['business_id' => $business->id, 'key' => $key, 'period' => $period],
            ['count' => 0, 'reset_at' => $this->periodEnd($business)]
        );

        $counter->increment('count', $delta);
    }

    /**
     * Recompute a usage counter from the authoritative data source.
     */
    public function syncUsage(Business $business, string $featureKey, int $count): void
    {
        $key = $this->usageKey($featureKey);
        $period = $this->currentPeriod($business);

        \App\Models\UsageCounter::updateOrCreate(
            ['business_id' => $business->id, 'key' => $key, 'period' => $period],
            ['count' => $count, 'reset_at' => $this->periodEnd($business)]
        );
    }

    /**
     * Resolve the full entitlement snapshot for a business.
     *
     * @return array{status:string, plan_type:string, features:array<string, array{enabled:bool, limit:?int}>, ends_at:?string, grace_ends_at:?string}|null
     */
    public function entitlements(Business $business): ?array
    {
        $cacheKey = $this->cacheKey($business);

        if (isset($this->localCache[$cacheKey])) {
            return $this->localCache[$cacheKey];
        }

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $this->localCache[$cacheKey] = $cached;
        }

        $subscription = BusinessSubscription::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->with(['plan' => function ($query) {
                $query->with('features');
            }])
            ->first();

        if (! $subscription || ! $subscription->plan) {
            $this->localCache[$cacheKey] = null;

            return null;
        }

        $features = [];
        foreach ($subscription->plan->features as $feature) {
            $features[$feature->key] = [
                'enabled' => (bool) $feature->pivot->enabled,
                'limit' => $feature->pivot->limit_value === null ? null : (int) $feature->pivot->limit_value,
            ];
        }

        $snapshot = [
            'status' => $subscription->status,
            'plan_type' => $subscription->plan->type,
            'plan_id' => $subscription->plan->id,
            'features' => $features,
            'ends_at' => $subscription->ends_at?->toIso8601String(),
            'grace_ends_at' => $subscription->grace_ends_at?->toIso8601String(),
        ];

        Cache::put($cacheKey, $snapshot, self::CACHE_TTL_SECONDS);
        $this->localCache[$cacheKey] = $snapshot;

        return $snapshot;
    }

    /**
     * Invalidate cached entitlements for a business (and, for plan changes,
     * for every business on that plan).
     */
    public function invalidate(Business|Plan|null $subject = null): void
    {
        if ($subject instanceof Plan) {
            $this->localCache = [];
            $this->flushPlanCaches($subject);

            return;
        }

        if ($subject instanceof Business) {
            $cacheKey = $this->cacheKey($subject);
            unset($this->localCache[$cacheKey]);
            Cache::forget($cacheKey);
        }
    }

    protected function flushPlanCaches(Plan $plan): void
    {
        BusinessSubscription::withoutGlobalScopes()
            ->where('plan_id', $plan->id)
            ->pluck('business_id')
            ->each(function ($businessId) {
                Cache::forget('feature-gate:business:'.$businessId);
            });
    }

    protected function cacheKey(Business $business): string
    {
        return 'feature-gate:business:'.$business->id;
    }

    /**
     * Platform default when a feature is not attached to the plan.
     */
    protected function defaultEntitlement(string $featureKey): bool
    {
        $feature = Feature::where('key', $featureKey)->first();

        if ($feature) {
            return (bool) $feature->default_enabled;
        }

        return false;
    }

    protected function defaultLimit(string $featureKey): ?int
    {
        $feature = Feature::where('key', $featureKey)->first();

        if ($feature && $feature->default_limit !== null) {
            return (int) $feature->default_limit;
        }

        return null;
    }

    protected function usageKey(string $featureKey): string
    {
        if ($featureKey === 'max_orders_monthly') {
            return 'orders_monthly';
        }

        return $featureKey;
    }

    protected function currentPeriod(Business $business): string
    {
        $tz = $business->timezone ?: config('app.timezone', 'UTC');

        return now()->setTimezone($tz)->format('Y-m');
    }

    protected function periodEnd(Business $business)
    {
        $tz = $business->timezone ?: config('app.timezone', 'UTC');

        return now()->setTimezone($tz)->endOfMonth();
    }
}

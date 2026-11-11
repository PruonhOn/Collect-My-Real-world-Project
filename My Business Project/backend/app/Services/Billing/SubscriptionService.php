<?php

namespace App\Services\Billing;

use App\Models\Business;
use App\Models\BusinessSubscription;
use App\Models\Plan;
use App\Models\SubscriptionAuditLog;
use App\Services\Features\FeatureGate;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public function __construct(protected FeatureGate $gate) {}

    /**
     * Move a business onto a plan. This is the single authoritative entry
     * point for entitlement changes; every other path (webhooks, admin
     * overrides, scheduled tasks) routes through here.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function transition(Business $business, Plan $plan, string $status, array $attributes = [], ?int $actorId = null, ?string $reason = null): BusinessSubscription
    {
        return DB::transaction(function () use ($business, $plan, $status, $attributes, $actorId, $reason) {
            $subscription = $business->subscription()->lockForUpdate()->first();

            if (! $subscription) {
                $subscription = BusinessSubscription::create([
                    'business_id' => $business->id,
                    'plan_id' => $plan->id,
                    'status' => $status,
                ]);
            }

            $old = $subscription->only(['plan_id', 'status', 'billing_cycle', 'starts_at', 'ends_at', 'trial_ends_at']);

            $subscription->fill(array_merge([
                'plan_id' => $plan->id,
                'status' => $status,
            ], $attributes));
            $subscription->save();

            $this->audit($business, 'subscription.'.$status, $actorId, $reason, $old, $subscription->only(array_keys($old)));

            $this->gate->invalidate($business);

            return $subscription->fresh();
        });
    }

    /**
     * Start a trial for a business if it is eligible.
     *
     * Trial eligibility is recorded on the business via the trial_ends_at /
     * trial history in subscription_audit_logs so that a cancel-and-resubscribe
     * cycle cannot be used to farm new trials.
     */
    public function startTrial(Business $business, Plan $plan, string $cycle = 'monthly', ?int $actorId = null): ?BusinessSubscription
    {
        if (! $plan->trial_enabled || $plan->trial_days <= 0) {
            return null;
        }

        if (! $this->isTrialEligible($business)) {
            return null;
        }

        $trialEndsAt = now()->addDays($plan->trial_days);

        return $this->transition(
            $business,
            $plan,
            'trialing',
            [
                'trial_starts_at' => now(),
                'trial_ends_at' => $trialEndsAt,
                'starts_at' => now(),
                'ends_at' => $trialEndsAt,
                'billing_cycle' => $cycle,
            ],
            $actorId,
            'Trial started'
        );
    }

    public function isTrialEligible(Business $business): bool
    {
        $hadTrial = SubscriptionAuditLog::where('business_id', $business->id)
            ->where('action', 'subscription.trialing')
            ->exists();

        return ! $hadTrial;
    }

    /**
     * Apply a confirmed, server-side verified payment.
     */
    public function activateFromPayment(Business $business, Plan $plan, string $cycle, array $attributes = [], ?int $actorId = null): BusinessSubscription
    {
        return $this->transition(
            $business,
            $plan,
            'active',
            array_merge([
                'billing_cycle' => $cycle,
                'starts_at' => now(),
                'ends_at' => $this->periodEnd(now(), $cycle),
                'trial_starts_at' => null,
                'trial_ends_at' => null,
                'canceled_at' => null,
                'cancel_at_period_end' => false,
                'grace_ends_at' => null,
            ], $attributes),
            $actorId,
            'Payment confirmed'
        );
    }

    public function cancel(Business $business, bool $atPeriodEnd = true, ?int $actorId = null, ?string $reason = null): BusinessSubscription
    {
        $subscription = $business->subscription()->firstOrFail();

        return $this->transition(
            $business,
            $subscription->plan,
            'canceled',
            [
                'canceled_at' => now(),
                'cancel_at_period_end' => $atPeriodEnd,
                'ends_at' => $atPeriodEnd ? $subscription->ends_at : now(),
            ],
            $actorId,
            $reason ?? 'Subscription canceled'
        );
    }

    public function resume(Business $business, ?int $actorId = null): BusinessSubscription
    {
        $subscription = $business->subscription()->firstOrFail();

        return $this->transition(
            $business,
            $subscription->plan,
            'active',
            [
                'canceled_at' => null,
                'cancel_at_period_end' => false,
                'starts_at' => $subscription->starts_at ?? now(),
                'ends_at' => $subscription->ends_at && $subscription->ends_at->isFuture()
                    ? $subscription->ends_at
                    : $this->periodEnd(now(), $subscription->billing_cycle ?? 'monthly'),
            ],
            $actorId,
            'Subscription resumed'
        );
    }

    public function markPastDue(Business $business, ?int $actorId = null): BusinessSubscription
    {
        $subscription = $business->subscription()->firstOrFail();

        return $this->transition(
            $business,
            $subscription->plan,
            'past_due',
            [
                'grace_ends_at' => now()->addDays(config('billing.grace_period_days', 3)),
            ],
            $actorId,
            'Payment failed'
        );
    }

    public function expire(Business $business, ?int $actorId = null, ?string $reason = null): BusinessSubscription
    {
        $subscription = $business->subscription()->firstOrFail();

        return $this->transition(
            $business,
            $subscription->plan,
            'expired',
            [
                'ends_at' => now(),
                'grace_ends_at' => null,
            ],
            $actorId,
            $reason ?? 'Subscription expired'
        );
    }

    public function suspend(Business $business, ?int $actorId = null, ?string $reason = null): BusinessSubscription
    {
        $subscription = $business->subscription()->firstOrFail();

        return $this->transition(
            $business,
            $subscription->plan,
            'suspended',
            [],
            $actorId,
            $reason ?? 'Subscription suspended'
        );
    }

    /**
     * The end of a paid billing period.
     */
    protected function periodEnd(\DateTimeInterface $from, string $cycle): \DateTimeInterface
    {
        return $cycle === 'yearly'
            ? \Carbon\Carbon::instance($from)->addYear()
            : \Carbon\Carbon::instance($from)->addMonth();
    }

    public function audit(
        Business $business,
        string $action,
        ?int $actorId = null,
        ?string $reason = null,
        ?array $old = null,
        ?array $new = null
    ): SubscriptionAuditLog {
        return SubscriptionAuditLog::create([
            'business_id' => $business->id,
            'actor_id' => $actorId,
            'action' => $action,
            'reason' => $reason,
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}

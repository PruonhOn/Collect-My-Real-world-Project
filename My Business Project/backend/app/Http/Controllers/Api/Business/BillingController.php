<?php

namespace App\Http\Controllers\Api\Business;

use App\Http\Controllers\Api\Controller;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Plan;
use App\Services\Billing\BillingService;
use App\Services\Billing\SubscriptionService;
use App\Services\Features\FeatureGate;
use App\Services\Payments\Exceptions\PaymentFailed;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BillingController extends Controller
{
    public function __construct(
        protected PaymentGatewayManager $gateways,
        protected BillingService $billing,
        protected SubscriptionService $subscriptions,
        protected FeatureGate $gate,
    ) {}

    public function index(): JsonResponse
    {
        $business = app('current.business');
        $subscription = $business->subscription()->with('plan.features')->first();

        $plans = Plan::where('is_active', true)->with('features')->orderBy('sort_order')->get()
            ->map(fn ($plan) => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'type' => $plan->type,
                'description' => $plan->description,
                'price_monthly' => $plan->price_monthly,
                'price_yearly' => $plan->price_yearly,
                'currency' => $plan->currency,
                'trial_enabled' => $plan->trial_enabled,
                'trial_days' => $plan->trial_days,
                'features' => $plan->features->map(fn ($f) => [
                    'key' => $f->key,
                    'name' => $f->name,
                    'type' => $f->type,
                    'enabled' => (bool) $f->pivot->enabled,
                    'limit' => $f->pivot->limit_value === null ? null : (int) $f->pivot->limit_value,
                ]),
            ]);

        return response()->json([
            'data' => [
                'subscription' => $subscription ? [
                    'status' => $subscription->status,
                    'plan' => $subscription->plan->name,
                    'plan_slug' => $subscription->plan->slug,
                    'billing_cycle' => $subscription->billing_cycle,
                    'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
                    'starts_at' => $subscription->starts_at?->toIso8601String(),
                    'ends_at' => $subscription->ends_at?->toIso8601String(),
                    'cancel_at_period_end' => $subscription->cancel_at_period_end,
                ] : null,
                'plans' => $plans,
                'gateways' => array_keys($this->gateways->availableGateways()),
                'usage' => $this->usageSummary($business),
            ],
        ]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $business = app('current.business');

        $validated = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'billing_cycle' => ['required', Rule::in(['monthly', 'yearly'])],
            'gateway' => ['nullable', 'string', Rule::in(array_keys($this->gateways->availableGateways()))],
            'coupon_code' => ['nullable', 'string', 'max:64'],
        ]);

        $plan = Plan::findOrFail($validated['plan_id']);

        if (! $plan->is_active) {
            return response()->json(['message' => 'This plan is not available.'], 422);
        }

        if ($plan->type === 'free') {
            return response()->json(['message' => 'The free plan does not require checkout.'], 422);
        }

        $gatewayName = $validated['gateway'] ?? config('billing.default_gateway', 'fake');

        try {
            $gateway = $this->gateways->gateway($gatewayName);
        } catch (\InvalidArgumentException) {
            return response()->json([
                'message' => "Unknown payment gateway [{$gatewayName}].",
                'available' => array_keys($this->gateways->availableGateways()),
            ], 422);
        }

        if (! $gateway->isConfigured()) {
            return response()->json([
                'message' => "The [{$gatewayName}] payment gateway is not enabled in this environment.",
            ], 422);
        }

        $couponCode = $validated['coupon_code'] ?? null;
        $coupon = null;
        $discount = 0;

        if ($couponCode) {
            if (! $this->gate->allows($business, 'discounts_coupons')) {
                return response()->json(['message' => 'Coupons are not available on your current plan.'], 422);
            }

            $coupon = Coupon::where('code', $couponCode)->first();

            if (! $coupon || ! $coupon->isRedeemable()) {
                return response()->json(['message' => 'This coupon is invalid or has expired.'], 422);
            }

            if (CouponRedemption::where('coupon_id', $coupon->id)->where('business_id', $business->id)->exists()) {
                return response()->json(['message' => 'This coupon has already been redeemed by your business.'], 422);
            }

            $baseAmount = $validated['billing_cycle'] === 'yearly' ? $plan->price_yearly : $plan->price_monthly;

            if ($coupon->min_amount !== null && $baseAmount < $coupon->min_amount) {
                return response()->json(['message' => 'This coupon does not meet the minimum order amount.'], 422);
            }

            if ($coupon->type === 'percentage') {
                $discount = (int) round($baseAmount * ($coupon->value / 100));
            } else {
                $discount = (int) $coupon->value;
            }

            $discount = max(0, min($discount, $baseAmount));
        }

        $created = $this->billing->createPendingCharge(
            $business,
            $plan,
            $validated['billing_cycle'],
            $gateway->name(),
            null,
            null,
            $discount,
            $coupon?->id
        );

        try {
            $checkout = $gateway->createCheckout($business, $plan, $validated['billing_cycle'], [
                'success_url' => config('app.frontend_url').'/billing/success',
                'cancel_url' => config('app.frontend_url').'/billing/cancel',
            ]);
        } catch (PaymentFailed $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $created['payment']->update([
            'provider_payment_id' => $checkout['reference'],
            'metadata' => array_merge((array) $created['payment']->metadata, ['checkout' => $checkout['payload']]),
        ]);

        return response()->json([
            'data' => [
                'checkout_url' => $checkout['checkout_url'],
                'reference' => $checkout['reference'],
                'payment' => $created['payment'],
                'invoice' => $created['invoice'],
            ],
        ], 201);
    }

    /**
     * Confirm a checkout using an authoritative server-side gateway lookup.
     * A client may request confirmation, but can never assert the outcome.
     */
    public function confirm(Request $request): JsonResponse
    {
        $business = app('current.business');

        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:255'],
        ]);

        $payment = \App\Models\Payment::where('business_id', $business->id)
            ->where('provider_payment_id', $validated['reference'])
            ->first();

        if (! $payment) {
            return response()->json(['message' => 'Payment reference not found.'], 404);
        }

        $plan = Plan::find($payment->metadata['plan_id'] ?? null);

        if (! $plan) {
            return response()->json(['message' => 'The plan for this payment could not be resolved.'], 422);
        }

        $gateway = $this->gateways->gateway($payment->provider ?? config('billing.default_gateway', 'fake'));

        try {
            $this->billing->confirmAndActivate(
                $business,
                $plan,
                $payment->metadata['billing_cycle'] ?? 'monthly',
                $gateway,
                $validated['reference']
            );
        } catch (PaymentFailed $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $business->subscription()->with('plan')->first()]);
    }

    public function portal(Request $request): JsonResponse
    {
        $business = app('current.business');
        $subscription = $business->subscription()->first();

        if (! $subscription) {
            return response()->json(['message' => 'No subscription found.'], 404);
        }

        if ($request->input('action') === 'cancel') {
            $this->subscriptions->cancel($business, (bool) $request->input('at_period_end', true), $request->user()->id);

            return response()->json(['data' => $business->subscription()->first()]);
        }

        if ($request->input('action') === 'resume') {
            $this->subscriptions->resume($business, $request->user()->id);

            return response()->json(['data' => $business->subscription()->first()]);
        }

        return response()->json(['message' => 'Unsupported action.'], 422);
    }

    public function invoices(Request $request): JsonResponse
    {
        $business = app('current.business');

        $perPage = min(100, max(1, (int) $request->query('per_page', 15)));

        $invoices = \App\Models\Invoice::where('business_id', $business->id)
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json($invoices);
    }

    protected function usageSummary(\App\Models\Business $business): array
    {
        $summary = [];

        foreach (['max_products', 'max_customers', 'max_members', 'max_orders_monthly'] as $key) {
            $limit = $this->gate->limit($business, $key);
            $used = $this->gate->usage($business, $key);
            $summary[] = [
                'key' => $key,
                'used' => $used,
                'limit' => $limit,
                'remaining' => $limit === null ? null : max(0, $limit - $used),
            ];
        }

        return $summary;
    }
}

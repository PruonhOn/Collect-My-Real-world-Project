<?php

namespace App\Services\Billing;

use App\Models\Business;
use App\Models\BusinessSubscription;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Plan;
use App\Notifications\PaymentReceipt;
use App\Notifications\SubscriptionActivated;
use App\Services\Payments\Contracts\PaymentGateway;
use App\Services\Payments\Exceptions\PaymentFailed;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Throwable;

class BillingService
{
    public function __construct(protected SubscriptionService $subscriptions) {}

    /**
     * Create a pending payment + draft billing invoice for a plan checkout.
     *
     * The returned invoice number and payment number are the immutable
     * references used later by the gateway to correlate the settlement.
     */
    public function createPendingCharge(
        Business $business,
        Plan $plan,
        string $cycle,
        string $provider,
        ?int $amount = null,
        ?string $providerReference = null,
        int $discount = 0,
        ?int $couponId = null
    ): array {
        return DB::transaction(function () use ($business, $plan, $cycle, $provider, $amount, $providerReference, $discount, $couponId) {
            $baseAmount = $amount ?? ($cycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly);
            $discount = max(0, (int) $discount);
            $chargeAmount = max(0, $baseAmount - $discount);

            $payment = Payment::create([
                'business_id' => $business->id,
                'payment_number' => $this->nextPaymentNumber(),
                'provider' => $provider,
                'provider_payment_id' => $providerReference,
                'amount' => $chargeAmount,
                'currency' => $plan->currency,
                'status' => 'pending',
                'description' => "{$plan->name} ({$cycle})",
                'metadata' => ['plan_id' => $plan->id, 'billing_cycle' => $cycle, 'coupon_id' => $couponId, 'discount' => $discount, 'base_amount' => $baseAmount],
            ]);

            $invoice = $this->createInvoice($business, $payment, $plan, $cycle, $baseAmount, $plan->currency, $discount);

            return ['payment' => $payment, 'invoice' => $invoice];
        });
    }

    public function createInvoice(
        Business $business,
        ?Payment $payment,
        Plan $plan,
        string $cycle,
        int $amount,
        string $currency,
        int $discount = 0
    ): Invoice {
        $discount = max(0, (int) $discount);
        $total = max(0, $amount - $discount);

        $invoice = Invoice::create([
            'business_id' => $business->id,
            'payment_id' => $payment?->id,
            'invoice_number' => $this->nextInvoiceNumber(),
            'type' => 'subscription',
            'subtotal' => $amount,
            'tax' => 0,
            'discount' => $discount,
            'total' => $total,
            'currency' => $currency,
            'status' => 'open',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->toDateString(),
            'billing_details' => [
                'merchant' => config('app.name'),
                'business' => ['id' => $business->id, 'name' => $business->name],
                'plan' => ['id' => $plan->id, 'name' => $plan->name],
                'billing_cycle' => $cycle,
                'discount' => $discount,
            ],
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => "{$plan->name} subscription ({$cycle})",
            'quantity' => 1,
            'unit_amount' => $amount,
            'amount' => $amount,
        ]);

        if ($discount > 0) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => 'Coupon discount',
                'quantity' => 1,
                'unit_amount' => -$discount,
                'amount' => -$discount,
            ]);
        }

        return $invoice;
    }

    /**
     * Mark a payment as settled after an authoritative server-side confirmation.
     */
    public function settlePayment(Payment $payment): Payment
    {
        if ($payment->status === 'succeeded') {
            return $payment;
        }

        $payment->update([
            'status' => 'succeeded',
            'paid_at' => now(),
        ]);

        $payment->invoice()->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->notifyOwner($payment->business, new PaymentReceipt($payment->fresh()));

        return $payment->fresh();
    }

    /**
     * Activate a plan for a business and tell the owner about it.
     */
    public function activateFromSettledPayment(Business $business, Plan $plan, string $cycle, Payment $payment, array $attributes = []): BusinessSubscription
    {
        $subscription = $this->subscriptions->activateFromPayment($business, $plan, $cycle, array_merge([
            'provider' => $payment->provider,
        ], $attributes));

        $this->notifyOwner($business, new SubscriptionActivated($subscription));

        return $subscription;
    }

    /**
     * Send a notification to the owner of a business.
     *
     * Delivery problems never break the business operation that triggered the
     * notification, so failures are logged instead of being thrown.
     */
    protected function notifyOwner(Business $business, BaseNotification $notification): void
    {
        $owner = $business->owner;

        if (! $owner) {
            return;
        }

        try {
            Notification::send($owner, $notification->afterCommit());
        } catch (Throwable $e) {
            Log::warning('Notification could not be sent.', [
                'business_id' => $business->id,
                'notification' => $notification::class,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failPayment(Payment $payment, string $reason = 'Payment failed'): Payment
    {
        $payment->update([
            'status' => 'failed',
            'metadata' => array_merge((array) $payment->metadata, ['failure_reason' => $reason]),
        ]);

        $payment->invoice()->update(['status' => 'void']);

        return $payment->fresh();
    }

    /**
     * Confirm a payment with the gateway and activate the subscription.
     */
    public function confirmAndActivate(
        Business $business,
        Plan $plan,
        string $cycle,
        PaymentGateway $gateway,
        string $reference
    ): Payment {
        $status = $gateway->fetchTransaction($reference);

        if (($status['status'] ?? null) !== 'succeeded') {
            throw new PaymentFailed('Payment has not been confirmed by the payment provider.');
        }

        $baseAmount = $cycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly;
        $discount = 0;
        $couponId = null;

        $payment = Payment::where('business_id', $business->id)
            ->where(function ($q) use ($reference) {
                $q->where('provider_payment_id', $reference)
                    ->orWhere('provider_transaction_id', $reference);
            })
            ->first();

        if ($payment) {
            $discount = (int) ($payment->metadata['discount'] ?? 0);
            $couponId = $payment->metadata['coupon_id'] ?? null;
        }

        $expected = max(0, $baseAmount - $discount);

        if ($status['amount'] !== null && (int) $status['amount'] !== (int) $expected) {
            throw new PaymentFailed('Payment amount does not match the plan price.');
        }

        if ($status['currency'] !== null && strtoupper($status['currency']) !== strtoupper($plan->currency)) {
            throw new PaymentFailed('Payment currency does not match the plan currency.');
        }

        if (! $payment) {
            $created = $this->createPendingCharge($business, $plan, $cycle, $gateway->name(), $expected, $reference, $discount, $couponId);
            $payment = $created['payment'];
        }

        if ($payment->status === 'succeeded') {
            // Idempotent: never double-activate.
            return $payment;
        }

        $this->settlePayment($payment);

        if ($couponId && $payment->id) {
            $alreadyRedeemed = CouponRedemption::where('coupon_id', $couponId)->where('business_id', $business->id)->exists();
            if (! $alreadyRedeemed) {
                CouponRedemption::create([
                    'coupon_id' => $couponId,
                    'business_id' => $business->id,
                    'payment_id' => $payment->id,
                ]);
                $coupon = Coupon::find($couponId);
                if ($coupon) {
                    $coupon->increment('uses_count');
                }
            }
        }

        $this->activateFromSettledPayment($business, $plan, $cycle, $payment, [
            'provider_customer_id' => $status['raw']['customer'] ?? $payment->metadata['customer'] ?? null,
        ]);

        return $payment->fresh();
    }

    protected function nextPaymentNumber(): string
    {
        $sequence = Payment::count() + 1;

        return 'PAY-'.now()->format('Ymd').'-'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }

    protected function nextInvoiceNumber(): string
    {
        $sequence = Invoice::count() + 1;

        return 'BILL-'.now()->format('Ymd').'-'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }
}

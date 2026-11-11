<?php

namespace App\Http\Controllers\Api;

use App\Models\Business;
use App\Models\BusinessSubscription;
use App\Models\Payment;
use App\Models\PaymentWebhookEvent;
use App\Models\Plan;
use App\Services\Billing\BillingService;
use App\Services\Billing\SubscriptionService;
use App\Services\Payments\Exceptions\InvalidWebhookSignature;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class WebhookController extends Controller
{
    public function __construct(
        protected PaymentGatewayManager $gateways,
        protected BillingService $billing,
        protected SubscriptionService $subscriptions,
    ) {}

    public function stripe(Request $request): JsonResponse
    {
        return $this->handle('stripe', $request);
    }

    public function bakong(Request $request): JsonResponse
    {
        return $this->handle('bakong_khqr', $request);
    }

    public function aba(Request $request): JsonResponse
    {
        return $this->handle('aba_payway', $request);
    }

    /**
     * Development/testing endpoint for the deterministic fake gateway.
     */
    public function fake(Request $request): JsonResponse
    {
        abort_unless(app()->environment(['local', 'testing']), 404);

        return $this->handle('fake', $request);
    }

    protected function handle(string $gatewayName, Request $request): JsonResponse
    {
        $gateway = $this->gateways->gateway($gatewayName);

        try {
            $payload = $gateway->verifyWebhook($request);
        } catch (InvalidWebhookSignature $e) {
            Log::warning('Webhook signature verification failed', ['gateway' => $gatewayName]);

            return response()->json(['message' => 'Invalid signature.'], 400);
        } catch (Throwable $e) {
            Log::error('Webhook verification error', ['gateway' => $gatewayName, 'error' => $e->getMessage()]);

            return response()->json(['message' => 'Unable to verify webhook.'], 400);
        }

        $event = $gateway->normalizeEvent($payload);

        if (empty($event['event_id'])) {
            return response()->json(['message' => 'Missing event id.'], 400);
        }

        // Idempotency: the unique provider event id prevents duplicate processing.
        $existing = PaymentWebhookEvent::where('event_id', $event['event_id'])->first();
        if ($existing && $existing->status === 'processed') {
            return response()->json(['message' => 'Event already processed.'], 200);
        }

        $record = PaymentWebhookEvent::updateOrCreate(
            ['event_id' => $event['event_id']],
            [
                'provider' => $gatewayName,
                'event_type' => $event['event_type'],
                'payload' => $this->redact($event['raw']),
                'status' => 'received',
                'attempts' => ($existing->attempts ?? 0) + 1,
            ]
        );

        try {
            $this->process($gatewayName, $event, $record);
        } catch (Throwable $e) {
            $record->update([
                'status' => 'failed',
                'last_error' => mb_substr($e->getMessage(), 0, 1000),
            ]);

            Log::error('Webhook processing failed', [
                'gateway' => $gatewayName,
                'event_id' => $event['event_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Webhook processing failed.'], 500);
        }

        return response()->json(['message' => 'Webhook processed.']);
    }

    protected function process(string $gatewayName, array $event, PaymentWebhookEvent $record): void
    {
        DB::transaction(function () use ($gatewayName, $event, $record) {
            $business = $this->resolveBusiness($event);

            if (! $business) {
                $record->update(['status' => 'ignored', 'processed_at' => now()]);

                return;
            }

            switch ($event['event_type']) {
                case 'checkout.session.completed':
                case 'payment_succeeded':
                case 'succeeded':
                    $this->handlePaymentSucceeded($business, $event, $record);
                    break;

                case 'customer.subscription.deleted':
                case 'subscription_canceled':
                case 'canceled':
                    $this->subscriptions->cancel($business, true);
                    break;

                case 'invoice.payment_failed':
                case 'payment_failed':
                case 'failed':
                    $this->subscriptions->markPastDue($business);
                    break;

                default:
                    $record->update(['status' => 'ignored', 'processed_at' => now()]);

                    return;
            }

            $record->update(['status' => 'processed', 'processed_at' => now()]);
        });
    }

    /**
     * Settle a payment and activate the plan that was actually charged.
     *
     * The plan is resolved from the payment itself, never from the business's
     * current subscription: the customer may still be on the free plan at the
     * moment the provider confirms the upgrade.
     */
    protected function handlePaymentSucceeded(Business $business, array $event, PaymentWebhookEvent $record): void
    {
        $payment = Payment::where('business_id', $business->id)
            ->where(function ($q) use ($event) {
                $q->where('provider_payment_id', $event['reference'])
                    ->orWhere('provider_transaction_id', $event['reference']);
            })
            ->first();

        if (! $payment) {
            $record->update(['status' => 'ignored', 'processed_at' => now()]);

            return;
        }

        if ($payment->status === 'succeeded') {
            // Idempotent: never settle or activate twice.
            return;
        }

        if ($event['amount'] !== null && (int) $event['amount'] !== (int) $payment->amount) {
            throw new \RuntimeException('Webhook amount does not match the charged amount.');
        }

        if ($event['currency'] !== null && strtoupper($event['currency']) !== strtoupper($payment->currency)) {
            throw new \RuntimeException('Webhook currency does not match the charged currency.');
        }

        $plan = Plan::find($payment->metadata['plan_id'] ?? null)
            ?? $business->subscription()->with('plan')->first()?->plan;

        if (! $plan) {
            $record->update(['status' => 'ignored', 'processed_at' => now()]);

            return;
        }

        $this->billing->settlePayment($payment);

        $this->billing->activateFromSettledPayment($business, $plan, $payment->metadata['billing_cycle'] ?? 'monthly', $payment);
    }

    protected function resolveBusiness(array $event): ?Business
    {
        if (! empty($event['business_reference'])) {
            $business = Business::find($event['business_reference']);
            if ($business) {
                return $business;
            }
        }

        if (! empty($event['reference'])) {
            $payment = Payment::where('provider_payment_id', $event['reference'])
                ->orWhere('provider_transaction_id', $event['reference'])
                ->first();
            if ($payment) {
                return $payment->business;
            }
        }

        return null;
    }

    /**
     * Remove sensitive values before persisting a provider payload.
     */
    protected function redact(array $payload): array
    {
        $sensitive = ['client_secret', 'client_secret_basic', 'authorization', 'card', 'cvc', 'number', 'private_key', 'secret_key'];

        foreach ($payload as $key => $value) {
            if (in_array(strtolower((string) $key), $sensitive, true)) {
                $payload[$key] = '[redacted]';
            } elseif (is_array($value)) {
                $payload[$key] = $this->redact($value);
            }
        }

        return $payload;
    }
}

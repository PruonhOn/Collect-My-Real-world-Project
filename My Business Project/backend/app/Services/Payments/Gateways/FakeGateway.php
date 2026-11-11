<?php

namespace App\Services\Payments\Gateways;

use App\Models\Business;
use App\Models\Plan;
use App\Services\Payments\Contracts\PaymentGateway;
use App\Services\Payments\Exceptions\InvalidWebhookSignature;
use Illuminate\Http\Request;

/**
 * Deterministic in-memory gateway used by the automated test-suite and by
 * local development when no real provider credentials are configured.
 *
 * It never contacts an external service. Payment confirmation is always
 * driven by an explicit server-side call (simulatePayment) — never by a
 * client-supplied "success" flag.
 */
class FakeGateway implements PaymentGateway
{
    /** @var array<string, array<string, mixed>> */
    public static array $transactions = [];

    /** @var string */
    public static string $webhookSecret = 'fake-webhook-secret';

    public function name(): string
    {
        return 'fake';
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function ensureCustomer(Business $business): array
    {
        return ['gateway_customer_id' => 'fake_cust_'.$business->id];
    }

    public function createCheckout(Business $business, Plan $plan, string $cycle, array $context = []): array
    {
        $reference = 'fake_chk_'.bin2hex(random_bytes(8));
        $amount = $cycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly;

        self::$transactions[$reference] = [
            'status' => 'pending',
            'amount' => $amount,
            'currency' => $plan->currency,
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'billing_cycle' => $cycle,
        ];

        return [
            'checkout_url' => 'https://fake-gateway.test/checkout/'.$reference,
            'reference' => $reference,
            'payload' => [
                'reference' => $reference,
                'amount' => $amount,
                'currency' => $plan->currency,
            ],
        ];
    }

    /**
     * Server-side helper used in tests to mark a fake transaction as settled.
     */
    public static function simulatePayment(string $reference, string $status = 'succeeded'): void
    {
        if (isset(self::$transactions[$reference])) {
            self::$transactions[$reference]['status'] = $status;
        }
    }

    public function fetchTransaction(string $reference): array
    {
        $txn = self::$transactions[$reference] ?? null;

        if (! $txn) {
            return [
                'status' => 'unknown',
                'amount' => null,
                'currency' => null,
                'reference' => $reference,
                'raw' => [],
            ];
        }

        return [
            'status' => $txn['status'],
            'amount' => $txn['amount'],
            'currency' => $txn['currency'],
            'reference' => $reference,
            'raw' => $txn,
        ];
    }

    public function verifyWebhook(Request $request): array
    {
        $signature = $request->header('X-Fake-Signature');

        if ($signature !== self::$webhookSecret) {
            throw new InvalidWebhookSignature('Invalid fake webhook signature.');
        }

        return $request->json()->all();
    }

    public function normalizeEvent(array $payload): array
    {
        return [
            'event_id' => (string) ($payload['event_id'] ?? ''),
            'event_type' => (string) ($payload['event_type'] ?? 'unknown'),
            'reference' => $payload['reference'] ?? null,
            'amount' => isset($payload['amount']) ? (int) $payload['amount'] : null,
            'currency' => $payload['currency'] ?? null,
            'business_reference' => isset($payload['business_id']) ? (string) $payload['business_id'] : null,
            'raw' => $payload,
        ];
    }

    public function supportsManagedSubscriptions(): bool
    {
        return false;
    }

    public function cancelSubscription(string $providerSubscriptionId): bool
    {
        return true;
    }

    public function fetchSubscription(string $providerSubscriptionId): ?array
    {
        return null;
    }

    public function refund(string $providerPaymentId, ?int $amount = null): bool
    {
        return true;
    }
}

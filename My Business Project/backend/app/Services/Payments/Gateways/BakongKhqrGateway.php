<?php

namespace App\Services\Payments\Gateways;

use App\Models\Business;
use App\Models\Plan;
use App\Services\Payments\Contracts\PaymentGateway;
use App\Services\Payments\Exceptions\InvalidWebhookSignature;
use App\Services\Payments\Exceptions\PaymentFailed;
use Illuminate\Http\Request;

/**
 * Bakong KHQR adapter.
 *
 * STATUS: ADAPTER STUB (NOT PRODUCTION READY).
 *
 * Bakong's KHQR products require merchant onboarding and per-product API
 * credentials from the Bakong business unit. The endpoints below reflect the
 * publicly documented KHQR shape only. Until real merchant credentials are
 * provisioned and the responses are validated against the live/sandbox API,
 * this adapter MUST NOT be enabled for paid subscriptions.
 *
 * `isConfigured()` returns false unless BAKONG_* credentials are present, and
 * `createCheckout()` deliberately refuses to activate entitlements.
 */
class BakongKhqrGateway implements PaymentGateway
{
    public function __construct(protected array $config) {}

    public function name(): string
    {
        return 'bakong_khqr';
    }

    public function isConfigured(): bool
    {
        return ! empty($this->config['merchant_id']) && ! empty($this->config['secret_key']);
    }

    public function ensureCustomer(Business $business): array
    {
        if (! $this->isConfigured()) {
            throw new PaymentFailed('Bakong KHQR is not configured for this environment.');
        }

        // Bakong KHQR does not maintain a portable customer object for
        // merchants; the business id is used as the stable merchant reference.
        return ['gateway_customer_id' => 'biz_'.$business->id];
    }

    public function createCheckout(Business $business, Plan $plan, string $cycle, array $context = []): array
    {
        if (! $this->isConfigured()) {
            throw new PaymentFailed('Bakong KHQR is not configured for this environment.');
        }

        throw new PaymentFailed(
            'Bakong KHQR checkout is not enabled in this deployment. '
            .'Complete merchant onboarding and verify the sandbox responses before enabling this gateway.'
        );
    }

    public function fetchTransaction(string $reference): array
    {
        throw new PaymentFailed('Bakong KHQR transaction lookup is not enabled in this deployment.');
    }

    public function verifyWebhook(Request $request): array
    {
        throw new InvalidWebhookSignature('Bakong KHQR webhook verification is not implemented.');
    }

    public function normalizeEvent(array $payload): array
    {
        return [
            'event_id' => (string) ($payload['transaction_id'] ?? $payload['id'] ?? ''),
            'event_type' => (string) ($payload['status'] ?? 'unknown'),
            'reference' => $payload['transaction_id'] ?? null,
            'amount' => isset($payload['amount']) ? (int) $payload['amount'] : null,
            'currency' => $payload['currency'] ?? null,
            'business_reference' => $payload['merchant_id'] ?? null,
            'raw' => $payload,
        ];
    }

    public function supportsManagedSubscriptions(): bool
    {
        return false;
    }

    public function cancelSubscription(string $providerSubscriptionId): bool
    {
        return false;
    }

    public function fetchSubscription(string $providerSubscriptionId): ?array
    {
        return null;
    }

    public function refund(string $providerPaymentId, ?int $amount = null): bool
    {
        return false;
    }
}

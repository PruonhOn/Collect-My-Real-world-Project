<?php

namespace App\Services\Payments\Gateways;

use App\Models\Business;
use App\Models\Plan;
use App\Services\Payments\Contracts\PaymentGateway;
use App\Services\Payments\Exceptions\InvalidWebhookSignature;
use App\Services\Payments\Exceptions\PaymentFailed;
use Illuminate\Http\Request;

/**
 * ABA PayWay adapter.
 *
 * STATUS: ADAPTER STUB (NOT PRODUCTION READY).
 *
 * ABA PayWay requires a registered merchant account with a merchant ID,
 * a private key and a public/IPN hash. The endpoints referenced here follow
 * the publicly documented PayWay shape but have NOT been validated against a
 * live or sandbox PayWay account in this environment.
 *
 * Until merchant credentials are provisioned and the checkout + verification
 * responses are verified, `isConfigured()` returns false and checkout refuses
 * to run, so no paid entitlement can ever be activated by this adapter.
 */
class AbaPaywayGateway implements PaymentGateway
{
    public function __construct(protected array $config) {}

    public function name(): string
    {
        return 'aba_payway';
    }

    public function isConfigured(): bool
    {
        return ! empty($this->config['merchant_id'])
            && ! empty($this->config['private_key'])
            && ! empty($this->config['ipn_hash']);
    }

    public function ensureCustomer(Business $business): array
    {
        if (! $this->isConfigured()) {
            throw new PaymentFailed('ABA PayWay is not configured for this environment.');
        }

        return ['gateway_customer_id' => 'biz_'.$business->id];
    }

    public function createCheckout(Business $business, Plan $plan, string $cycle, array $context = []): array
    {
        if (! $this->isConfigured()) {
            throw new PaymentFailed('ABA PayWay is not configured for this environment.');
        }

        throw new PaymentFailed(
            'ABA PayWay checkout is not enabled in this deployment. '
            .'Provision merchant credentials and verify the sandbox responses before enabling this gateway.'
        );
    }

    public function fetchTransaction(string $reference): array
    {
        throw new PaymentFailed('ABA PayWay transaction lookup is not enabled in this deployment.');
    }

    public function verifyWebhook(Request $request): array
    {
        throw new InvalidWebhookSignature('ABA PayWay IPN verification is not implemented.');
    }

    public function normalizeEvent(array $payload): array
    {
        return [
            'event_id' => (string) ($payload['tran_id'] ?? $payload['reference'] ?? ''),
            'event_type' => (string) ($payload['status'] ?? 'unknown'),
            'reference' => $payload['tran_id'] ?? null,
            'amount' => isset($payload['amount']) ? (int) round((float) $payload['amount'] * 100) : null,
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

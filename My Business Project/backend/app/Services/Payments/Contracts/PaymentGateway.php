<?php

namespace App\Services\Payments\Contracts;

interface PaymentGateway
{
    /**
     * Machine name of the gateway (e.g. "stripe", "bakong_khqr").
     */
    public function name(): string;

    /**
     * Whether the gateway is configured with usable credentials.
     */
    public function isConfigured(): bool;

    /**
     * Create or retrieve the gateway-side customer for a business.
     *
     * @return array{gateway_customer_id: string}
     */
    public function ensureCustomer(\App\Models\Business $business): array;

    /**
     * Initiate a checkout for a subscription plan.
     *
     * @param  array<string, mixed>  $context
     * @return array{checkout_url: ?string, reference: string, payload: array<string, mixed>}
     */
    public function createCheckout(\App\Models\Business $business, \App\Models\Plan $plan, string $cycle, array $context = []): array;

    /**
     * Fetch the authoritative status of a charge/transaction from the provider.
     *
     * @return array{status: string, amount: ?int, currency: ?string, reference: ?string, raw: array<string, mixed>}
     */
    public function fetchTransaction(string $reference): array;

    /**
     * Verify a provider webhook payload. Implementations must throw
     * \App\Services\Payments\Exceptions\InvalidWebhookSignature when the
     * signature cannot be validated.
     */
    public function verifyWebhook(\Illuminate\Http\Request $request): array;

    /**
     * Normalize a provider payload into a provider-agnostic event structure.
     *
     * @param  array<string, mixed>  $payload
     * @return array{event_id: string, event_type: string, reference: ?string, amount: ?int, currency: ?string, business_reference: ?string, raw: array<string, mixed>}
     */
    public function normalizeEvent(array $payload): array;

    /**
     * Whether the gateway supports provider-managed subscription lifecycle.
     */
    public function supportsManagedSubscriptions(): bool;

    /**
     * Cancel a provider-side subscription.
     */
    public function cancelSubscription(string $providerSubscriptionId): bool;

    /**
     * Fetch the authoritative state of a provider-managed subscription.
     *
     * Gateways that do not manage subscriptions on the provider side return
     * null. A null result must never be interpreted as "canceled".
     *
     * @return array{status: string, cancel_at_period_end: bool, ends_at: ?string}|null
     */
    public function fetchSubscription(string $providerSubscriptionId): ?array;

    /**
     * Refund a settled payment. Returns false when unsupported.
     */
    public function refund(string $providerPaymentId, ?int $amount = null): bool;
}

<?php

namespace App\Services\Payments\Gateways;

use App\Models\Business;
use App\Models\Plan;
use App\Services\Payments\Contracts\PaymentGateway;
use App\Services\Payments\Exceptions\InvalidWebhookSignature;
use App\Services\Payments\Exceptions\PaymentFailed;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Stripe\Refund;
use Stripe\Stripe;
use Stripe\StripeClient;
use Stripe\Subscription;
use Stripe\Webhook;

class StripeGateway implements PaymentGateway
{
    protected ?StripeClient $client = null;

    public function __construct(protected array $config)
    {
        if ($this->isConfigured()) {
            Stripe::setApiKey($this->config['secret_key']);
            $this->client = new StripeClient($this->config['secret_key']);
        }
    }

    public function name(): string
    {
        return 'stripe';
    }

    public function isConfigured(): bool
    {
        return ! empty($this->config['secret_key']);
    }

    public function ensureCustomer(Business $business): array
    {
        if (! $this->isConfigured()) {
            throw new PaymentFailed('Stripe is not configured for this environment.');
        }

        $existing = $business->subscription()->first();

        if ($existing?->provider_customer_id) {
            return ['gateway_customer_id' => $existing->provider_customer_id];
        }

        try {
            $customer = Customer::create([
                'name' => $business->name,
                'email' => $business->email,
                'metadata' => [
                    'business_id' => (string) $business->id,
                ],
            ]);
        } catch (ApiErrorException $e) {
            throw new PaymentFailed('Stripe customer creation failed: '.$e->getMessage());
        }

        return ['gateway_customer_id' => $customer->id];
    }

    public function createCheckout(Business $business, Plan $plan, string $cycle, array $context = []): array
    {
        if (! $this->isConfigured()) {
            throw new PaymentFailed('Stripe is not configured for this environment.');
        }

        $customer = $this->ensureCustomer($business);

        $priceId = $this->resolvePriceId($plan, $cycle);
        if (! $priceId) {
            throw new PaymentFailed('No Stripe price configured for this plan and billing cycle.');
        }

        $amount = $cycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly;
        $reference = 'chk_'.bin2hex(random_bytes(10));

        try {
            $session = Session::create([
                'mode' => 'subscription',
                'customer' => $customer['gateway_customer_id'],
                'line_items' => [[
                    'price' => $priceId,
                    'quantity' => 1,
                ]],
                'success_url' => $context['success_url'] ?? url('/billing/success?session_id={CHECKOUT_SESSION_ID}'),
                'cancel_url' => $context['cancel_url'] ?? url('/billing/cancel'),
                'client_reference_id' => $reference,
                'subscription_data' => [
                    'metadata' => [
                        'business_id' => (string) $business->id,
                        'plan_id' => (string) $plan->id,
                        'billing_cycle' => $cycle,
                        'client_reference_id' => $reference,
                    ],
                ],
                'metadata' => [
                    'business_id' => (string) $business->id,
                    'plan_id' => (string) $plan->id,
                    'billing_cycle' => $cycle,
                    'client_reference_id' => $reference,
                ],
            ]);
        } catch (ApiErrorException $e) {
            throw new PaymentFailed('Stripe checkout session creation failed: '.$e->getMessage());
        }

        return [
            'checkout_url' => $session->url,
            'reference' => $reference,
            'payload' => [
                'session_id' => $session->id,
                'amount' => $amount,
                'currency' => $plan->currency,
                'client_reference_id' => $reference,
            ],
        ];
    }

    public function fetchTransaction(string $reference): array
    {
        if (! $this->isConfigured()) {
            throw new PaymentFailed('Stripe is not configured for this environment.');
        }

        try {
            $session = $this->resolveSession($reference);
        } catch (ApiErrorException $e) {
            throw new PaymentFailed('Stripe session lookup failed: '.$e->getMessage());
        }

        return [
            'status' => $this->mapSessionStatus($session->status),
            'amount' => $session->amount_total,
            'currency' => $session->currency ? strtoupper($session->currency) : null,
            'reference' => $session->client_reference_id,
            'raw' => $session->toArray(),
        ];
    }

    /**
     * Resolve a Checkout Session from either a session id (`cs_...`) or from
     * the client reference id that this application stored on the payment.
     */
    protected function resolveSession(string $reference): Session
    {
        if (str_starts_with($reference, 'cs_')) {
            return Session::retrieve($reference);
        }

        $sessions = Session::all(['client_reference_id' => $reference, 'limit' => 1]);

        if (count($sessions->data) === 0) {
            throw new ApiErrorException('No Checkout Session found for reference ['.$reference.'].');
        }

        return $sessions->data[0];
    }

    /**
     * Resolve the Stripe price for a plan / billing cycle.
     *
     * Order of precedence: explicit context, plan metadata (per plan pricing
     * stored in the database), then the environment level gateway defaults.
     */
    protected function resolvePriceId(Plan $plan, string $cycle): ?string
    {
        $metadata = (array) $plan->metadata;

        $planPrices = $metadata['stripe_price_ids'] ?? [];

        return $planPrices[$cycle]
            ?? $metadata['stripe_price_id'] ?? null
            ?? ($this->config['price_ids'][$cycle] ?? null);
    }

    public function verifyWebhook(Request $request): array
    {
        $secret = $this->config['webhook_secret'] ?? null;

        if (! $secret) {
            throw new InvalidWebhookSignature('Stripe webhook secret is not configured.');
        }

        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        if (! $signature) {
            throw new InvalidWebhookSignature('Missing Stripe-Signature header.');
        }

        try {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } catch (\Throwable $e) {
            throw new InvalidWebhookSignature('Stripe signature verification failed: '.$e->getMessage());
        }

        return $event->toArray();
    }

    public function normalizeEvent(array $payload): array
    {
        $object = $payload['data']['object'] ?? [];

        return [
            'event_id' => (string) ($payload['id'] ?? ''),
            'event_type' => (string) ($payload['type'] ?? 'unknown'),
            'reference' => $object['client_reference_id'] ?? ($object['id'] ?? null),
            'amount' => isset($object['amount_total']) ? (int) $object['amount_total'] : null,
            'currency' => isset($object['currency']) ? strtoupper($object['currency']) : null,
            'business_reference' => isset($object['metadata']['business_id']) ? (string) $object['metadata']['business_id'] : null,
            'raw' => $payload,
        ];
    }

    public function supportsManagedSubscriptions(): bool
    {
        return true;
    }

    public function cancelSubscription(string $providerSubscriptionId): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        try {
            Subscription::update($providerSubscriptionId, ['cancel_at_period_end' => true]);
        } catch (ApiErrorException $e) {
            return false;
        }

        return true;
    }

    public function fetchSubscription(string $providerSubscriptionId): ?array
    {
        if (! $this->isConfigured() || ! $providerSubscriptionId) {
            return null;
        }

        try {
            $subscription = Subscription::retrieve($providerSubscriptionId);
        } catch (ApiErrorException $e) {
            return null;
        }

        return [
            'status' => (string) $subscription->status,
            'cancel_at_period_end' => (bool) $subscription->cancel_at_period_end,
            'ends_at' => $subscription->current_period_end
                ? date('c', (int) $subscription->current_period_end)
                : null,
        ];
    }

    public function refund(string $providerPaymentId, ?int $amount = null): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        $params = ['payment_intent' => $providerPaymentId];
        if ($amount !== null) {
            $params['amount'] = $amount;
        }

        try {
            Refund::create($params);
        } catch (ApiErrorException $e) {
            return false;
        }

        return true;
    }

    protected function mapSessionStatus(?string $status): string
    {
        return match ($status) {
            'complete' => 'succeeded',
            'open' => 'pending',
            'expired' => 'canceled',
            default => 'pending',
        };
    }
}

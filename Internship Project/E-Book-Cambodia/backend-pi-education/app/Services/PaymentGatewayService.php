<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Purchase;
use App\Services\Payments\BakongPaymentGateway;
use App\Services\Payments\MockPaymentGateway;
use App\Services\Payments\StripePaymentGateway;

/**
 * Routes checkout through the payment gateway configured in config/payments.php.
 * Defaults to the mock gateway, which preserves the original demo behaviour.
 */
class PaymentGatewayService
{
    public function __construct(
        private MockPaymentGateway $mock,
        private StripePaymentGateway $stripe,
        private BakongPaymentGateway $bakong,
    ) {}

    public function driver(): string
    {
        return (string) config('payments.driver', 'mock');
    }

    public function isStripe(): bool
    {
        return $this->driver() === 'stripe';
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{status: string, provider_ref: string|null, checkout_url: string|null, payment: Payment}
     */
    public function checkout(Purchase $purchase, array $payload = []): array
    {
        if (($payload['method'] ?? null) === 'bakong') {
            return $this->bakong->checkout($purchase, $payload);
        }

        return $this->isStripe()
            ? $this->stripe->checkout($purchase, $payload)
            : $this->mock->checkout($purchase, $payload);
    }

    public function checkBakong(Payment $payment): array
    {
        return $this->bakong->check($payment);
    }
}

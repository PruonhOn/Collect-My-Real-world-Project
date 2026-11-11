<?php

namespace App\Services;

use App\Models\Purchase;
use App\Services\Payments\BakongPaymentGateway;
use App\Services\Payments\MockPaymentGateway;
use App\Services\Payments\PaymentGatewayInterface;

class PaymentGatewayService
{
    public function gateway(?string $method = null): PaymentGatewayInterface
    {
        $method = $method ?: config('payments.driver', 'mock');

        return match ($method) {
            'bakong' => new BakongPaymentGateway(),
            default => new MockPaymentGateway(),
        };
    }

    public function createPayment(Purchase $purchase, string $method, array $payload = []): array
    {
        $gateway = $this->gateway($method);

        return $gateway->checkout($purchase, $payload);
    }

    public function verifyPayment(string $reference, string $method = null): array
    {
        $gateway = $this->gateway($method);

        return $gateway->verify($reference);
    }
}

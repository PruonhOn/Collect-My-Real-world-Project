<?php

namespace App\Services\Payments;

use App\Models\Purchase;
use App\Services\MockPaymentService;

/**
 * Demo gateway. Keeps the existing behavior of MockPaymentService while
 * exposing the same result shape as real gateways.
 */
class MockPaymentGateway implements PaymentGatewayInterface
{
    public function __construct(private MockPaymentService $mock) {}

    public function checkout(Purchase $purchase, array $payload = []): array
    {
        $payment = $this->mock->checkout($purchase, $payload);

        return [
            'status' => $payment->status,
            'provider_ref' => $payment->provider_ref,
            'checkout_url' => null,
            'payment' => $payment,
        ];
    }
}

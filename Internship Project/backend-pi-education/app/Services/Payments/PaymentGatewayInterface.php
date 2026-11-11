<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\Purchase;

interface PaymentGatewayInterface
{
    /**
     * Start the checkout flow for a purchase.
     *
     * @param  array<string, mixed>  $payload
     * @return array{status: string, provider_ref: string|null, checkout_url: string|null, payment: Payment}
     */
    public function checkout(Purchase $purchase, array $payload = []): array;
}

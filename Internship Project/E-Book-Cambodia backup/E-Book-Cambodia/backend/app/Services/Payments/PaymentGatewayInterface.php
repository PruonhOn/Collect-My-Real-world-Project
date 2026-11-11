<?php

namespace App\Services\Payments;

use App\Models\Purchase;

interface PaymentGatewayInterface
{
    /**
     * Initiate a checkout for the given purchase.
     *
     * @return array{status: string, provider_ref: ?string, checkout_url: ?string, qr_code: ?string, payment: \App\Models\Payment}
     */
    public function checkout(Purchase $purchase, array $payload = []): array;

    /**
     * Verify/confirm a payment by reference or provider reference.
     *
     * @return array{status: string, provider_ref: ?string}
     */
    public function verify(string $reference): array;

    /**
     * Check if a purchase has been paid.
     */
    public function isPaid(string $reference): bool;
}

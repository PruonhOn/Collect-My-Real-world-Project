<?php

namespace App\Services\PaymentGateways;

use App\Enums\PaymentStatus;

interface PaymentGateway
{
    /**
     * Unique machine identifier for the gateway driver (e.g. "manual", "bakong").
     */
    public function identifier(): string;

    /**
     * @param  array{amount: float, parent_name: string, student_name: string, invoice_no: string}  $payload
     */
    public function charge(array $payload, ?string $reference = null): PaymentResult;

    public function verify(string $transactionId): PaymentStatus;
}

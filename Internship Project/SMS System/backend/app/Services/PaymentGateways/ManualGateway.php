<?php

namespace App\Services\PaymentGateways;

use App\Enums\PaymentStatus;

/**
 * Offline / manual gateway: payments are confirmed by an admin or the parent
 * submitting proof. No external provider is contacted.
 */
final class ManualGateway implements PaymentGateway
{
    public function __construct(private readonly string $name = 'manual') {}

    public function identifier(): string
    {
        return $this->name;
    }

    public function charge(array $payload, ?string $reference = null): PaymentResult
    {
        return new PaymentResult(
            successful: true,
            transactionId: $reference ?? 'manual-'.now()->format('YmdHis').'-'.bin2hex(random_bytes(4)),
            status: PaymentStatus::Confirmed,
            message: 'Payment recorded manually.',
        );
    }

    public function verify(string $transactionId): PaymentStatus
    {
        return PaymentStatus::Confirmed;
    }
}

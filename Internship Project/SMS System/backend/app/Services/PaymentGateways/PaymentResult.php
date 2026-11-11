<?php

namespace App\Services\PaymentGateways;

use App\Enums\PaymentStatus;

final readonly class PaymentResult
{
    public function __construct(
        public bool $successful,
        public string $transactionId,
        public PaymentStatus $status,
        public ?string $message = null,
        public array $raw = [],
    ) {}
}

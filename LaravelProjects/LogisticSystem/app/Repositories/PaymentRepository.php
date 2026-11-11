<?php

namespace App\Repositories;

use App\Models\Payment;
use Illuminate\Support\Collection;

class PaymentRepository extends BaseRepository
{
    public function __construct(Payment $payment)
    {
        parent::__construct($payment);
    }

    public function model(): Payment
    {
        return new Payment();
    }

    public function findByPaymentNumber(string $paymentNumber): ?Payment
    {
        return $this->findBy('payment_number', $paymentNumber);
    }

    public function findByInvoice(int $invoiceId): Collection
    {
        return $this->findAllBy('invoice_id', $invoiceId);
    }

    public function findByMethod(string $method): Collection
    {
        return $this->findAllBy('payment_method', $method);
    }
}

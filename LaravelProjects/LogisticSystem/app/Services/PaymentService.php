<?php

namespace App\Services;

use App\Models\Payment;
use App\Repositories\BaseRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PaymentService extends BaseService
{
    public function __construct(BaseRepository $repository)
    {
        parent::__construct($repository);
    }

    public function generatePaymentNumber(): string
    {
        $prefix = 'PAY';
        $timestamp = now()->format('ymdHis');
        $random = strtoupper(Str::random(4));

        return "{$prefix}-{$timestamp}-{$random}";
    }

    public function processPayment(array $data): Payment
    {
        if (!isset($data['payment_number'])) {
            $data['payment_number'] = $this->generatePaymentNumber();
        }

        /** @var Payment $payment */
        $payment = $this->repository->create($data);

        if (isset($data['invoice_id'])) {
            $invoiceService = app(InvoiceService::class);
            $invoice = $invoiceService->find($data['invoice_id']);

            if ($invoice) {
                $totalPaid = Payment::where('invoice_id', $data['invoice_id'])->sum('amount');
                $invoiceService->markPaid($data['invoice_id'], $totalPaid);
            }
        }

        if (isset($data['customer_id'])) {
            $customerService = app(CustomerService::class);
            $customerService->updateOutstandingBalance($data['customer_id']);
        }

        return $payment->load(['invoice', 'customer', 'createdBy']);
    }

    public function getPaymentsByInvoice(int $invoiceId): Collection
    {
        return $this->repository->findAllBy('invoice_id', $invoiceId);
    }

    public function getPaymentsByCustomer(int $customerId): Collection
    {
        return $this->repository->findAllBy('customer_id', $customerId);
    }

    public function getPaymentsByDateRange(string $startDate, string $endDate): Collection
    {
        return Payment::whereBetween('payment_date', [$startDate, $endDate])
            ->with(['invoice', 'customer'])
            ->orderByDesc('payment_date')
            ->get();
    }
}

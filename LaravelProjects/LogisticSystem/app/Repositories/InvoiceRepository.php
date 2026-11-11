<?php

namespace App\Repositories;

use App\Models\Invoice;
use Illuminate\Support\Collection;

class InvoiceRepository extends BaseRepository
{
    public function __construct(Invoice $invoice)
    {
        parent::__construct($invoice);
    }

    public function model(): Invoice
    {
        return new Invoice();
    }

    public function findByInvoiceNumber(string $invoiceNumber): ?Invoice
    {
        return $this->findBy('invoice_number', $invoiceNumber);
    }

    public function findByStatus(string $status): Collection
    {
        return $this->findAllBy('status', $status);
    }

    public function findByCustomer(int $customerId): Collection
    {
        return $this->findAllBy('customer_id', $customerId);
    }
}

<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Repositories\BaseRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class InvoiceService extends BaseService
{
    public function __construct(BaseRepository $repository)
    {
        parent::__construct($repository);
    }

    public function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $year = now()->format('Y');
        $month = now()->format('m');
        $lastInvoice = Invoice::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->orderByDesc('id')
            ->first();

        $sequence = $lastInvoice ? ((int) substr($lastInvoice->invoice_number, -4)) + 1 : 1;

        return "{$prefix}-{$year}{$month}-" . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    public function createInvoice(array $data): Invoice
    {
        if (!isset($data['invoice_number'])) {
            $data['invoice_number'] = $this->generateInvoiceNumber();
        }

        $data['status'] = $data['status'] ?? 'draft';
        $data['paid_amount'] = $data['paid_amount'] ?? 0;

        /** @var Invoice $invoice */
        $invoice = $this->repository->create($data);

        if (isset($data['items']) && is_array($data['items'])) {
            $invoice->invoiceItems()->createMany($data['items']);
        }

        $this->calculateTotals($invoice->id);

        return $invoice->fresh()->load(['customer', 'branch', 'invoiceItems']);
    }

    public function calculateTotals(int $id): ?Invoice
    {
        /** @var Invoice|null $invoice */
        $invoice = $this->repository->find($id);

        if (!$invoice) {
            return null;
        }

        $items = $invoice->invoiceItems;

        $subtotal = $items->sum('total');
        $discount = $invoice->discount ?? 0;
        $taxableAmount = $subtotal - $discount;
        $tax = $taxableAmount * ($invoice->tax_rate ?? 0) / 100;
        $total = $taxableAmount + $tax;
        $balance = $total - ($invoice->paid_amount ?? 0);

        $invoice->update([
            'subtotal' => $subtotal,
            'tax' => round($tax, 2),
            'total' => round($total, 2),
            'balance' => round(max(0, $balance), 2),
        ]);

        return $invoice->fresh();
    }

    public function markPaid(int $id, ?float $amount = null): ?Invoice
    {
        /** @var Invoice|null $invoice */
        $invoice = $this->repository->find($id);

        if (!$invoice) {
            return null;
        }

        $paidAmount = $amount ?? $invoice->total;

        $invoice->update([
            'paid_amount' => $paidAmount,
            'balance' => round(max(0, $invoice->total - $paidAmount), 2),
            'status' => $paidAmount >= $invoice->total ? 'paid' : 'partial',
        ]);

        return $invoice->fresh();
    }

    public function updateInvoice(int $id, array $data): ?Invoice
    {
        /** @var Invoice|null $invoice */
        $invoice = $this->repository->find($id);

        if (!$invoice) {
            return null;
        }

        if (isset($data['items']) && is_array($data['items'])) {
            $invoice->invoiceItems()->delete();
            $invoice->invoiceItems()->createMany($data['items']);
            unset($data['items']);
        }

        $invoice = $this->repository->update($id, $data);

        if ($invoice && (isset($data['items']) || isset($data['discount']) || isset($data['tax_rate']))) {
            $this->calculateTotals($id);
        }

        return $invoice?->fresh()->load(['customer', 'branch', 'invoiceItems', 'payments']);
    }

    public function addInvoiceItem(int $invoiceId, array $data): ?InvoiceItem
    {
        /** @var Invoice|null $invoice */
        $invoice = $this->repository->find($invoiceId);

        if (!$invoice) {
            return null;
        }

        $data['invoice_id'] = $invoiceId;
        $data['total'] = ($data['quantity'] ?? 1) * ($data['unit_price'] ?? 0);

        $item = $invoice->invoiceItems()->create($data);

        $this->calculateTotals($invoiceId);

        return $item;
    }

    public function getInvoiceWithItems(int $id): ?Invoice
    {
        /** @var Invoice|null */
        return $this->repository->find($id)?->load([
            'customer', 'branch', 'invoiceItems', 'shipment', 'payments', 'createdBy',
        ]);
    }

    public function getInvoicesByCustomer(int $customerId): Collection
    {
        return $this->repository->findAllBy('customer_id', $customerId);
    }

    public function getInvoicesByStatus(string $status): Collection
    {
        return $this->repository->findAllBy('status', $status);
    }

    public function getOverdueInvoices(): Collection
    {
        return Invoice::where('status', '!=', 'paid')
            ->where('due_date', '<', now())
            ->where('balance', '>', 0)
            ->with(['customer'])
            ->orderBy('due_date')
            ->get();
    }

    public function getInvoicesByDateRange(string $startDate, string $endDate): Collection
    {
        return Invoice::whereBetween('invoice_date', [$startDate, $endDate])
            ->with(['customer'])
            ->orderByDesc('invoice_date')
            ->get();
    }
}

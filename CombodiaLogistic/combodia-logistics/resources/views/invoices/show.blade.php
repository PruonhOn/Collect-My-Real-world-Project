@extends('layouts.admin')

@section('title', 'Invoice Details')
@section('subtitle', 'Invoice #' . $invoice->invoice_number)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Invoice #{{ $invoice->invoice_number }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('invoices.index') }}">Invoices</a></li>
                <li class="breadcrumb-item active">Details</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-primary" onclick="printInvoice()">
            <i class="bi bi-printer me-1"></i> Print
        </button>
        <a href="{{ route('invoices.download-pdf', $invoice) }}" class="btn btn-outline-danger">
            <i class="bi bi-file-pdf me-1"></i> Download PDF
        </a>
        <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm" id="printableInvoice">
    <div class="card-body p-5">
        <div class="row mb-5">
            <div class="col-md-6">
                <div class="d-flex align-items-center mb-3">
                    <div class="rounded bg-primary bg-opacity-10 p-2 me-3">
                        <i class="bi bi-box-seam text-primary" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <h4 class="mb-0 fw-bold">Combodia Logistics</h4>
                        <small class="text-muted">Logistics & Transportation</small>
                    </div>
                </div>
                <p class="text-muted mb-1">123 Logistics Street</p>
                <p class="text-muted mb-1">Phnom Penh, Cambodia</p>
                <p class="text-muted mb-0">Phone: +855 12 345 6789</p>
            </div>
            <div class="col-md-6 text-md-end">
                <h3 class="text-primary fw-bold">INVOICE</h3>
                <p class="mb-1"><strong>Invoice #:</strong> {{ $invoice->invoice_number }}</p>
                <p class="mb-1"><strong>Issue Date:</strong> {{ $invoice->issue_date ? \Carbon\Carbon::parse($invoice->issue_date)->format('d M Y') : '-' }}</p>
                <p class="mb-1"><strong>Due Date:</strong> {{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') : '-' }}</p>
                <p class="mb-0">
                    <strong>Status:</strong>
                    @switch($invoice->status)
                        @case('draft')
                            <span class="badge bg-secondary">Draft</span>
                            @break
                        @case('pending')
                            <span class="badge bg-warning text-dark">Pending</span>
                            @break
                        @case('paid')
                            <span class="badge bg-success">Paid</span>
                            @break
                        @case('overdue')
                            <span class="badge bg-danger">Overdue</span>
                            @break
                    @endswitch
                </p>
            </div>
        </div>

        <div class="row mb-5">
            <div class="col-md-6">
                <h6 class="text-muted text-uppercase mb-2">Bill To</h6>
                <h5 class="fw-bold">{{ $invoice->customer->name ?? 'N/A' }}</h5>
                <p class="text-muted mb-1">{{ $invoice->customer->email ?? '' }}</p>
                <p class="text-muted mb-1">{{ $invoice->customer->phone ?? '' }}</p>
                <p class="text-muted mb-0">{{ $invoice->customer->address ?? '' }}</p>
            </div>
            @if($invoice->shipment)
            <div class="col-md-6 text-md-end">
                <h6 class="text-muted text-uppercase mb-2">Related Shipment</h6>
                <p class="mb-1"><strong>{{ $invoice->shipment->shipment_number }}</strong></p>
                <p class="text-muted mb-0">{{ $invoice->shipment->recipient_city }}</p>
            </div>
            @endif
        </div>

        <div class="table-responsive mb-4">
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width: 50px;">#</th>
                        <th>Description</th>
                        <th class="text-center" style="width: 80px;">Qty</th>
                        <th class="text-end" style="width: 120px;">Unit Price</th>
                        <th class="text-end" style="width: 120px;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoice->items as $idx => $item)
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td>{{ $item->description }}</td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-end">${{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-end">${{ number_format($item->quantity * $item->unit_price, 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted py-3">No items</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="row">
            <div class="col-md-6">
                @if($invoice->terms)
                <div class="bg-light rounded p-3">
                    <h6 class="text-muted text-uppercase mb-2">Terms & Conditions</h6>
                    <p class="text-muted small mb-0">{{ $invoice->terms }}</p>
                </div>
                @endif
            </div>
            <div class="col-md-6">
                <div class="bg-light rounded p-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Subtotal:</span>
                        <span>${{ number_format($invoice->subtotal ?? 0, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Tax ({{ $invoice->tax_rate ?? 0 }}%):</span>
                        <span>${{ number_format($invoice->tax_amount ?? 0, 2) }}</span>
                    </div>
                    @if(($invoice->discount ?? 0) > 0)
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Discount:</span>
                        <span class="text-danger">-${{ number_format($invoice->discount, 2) }}</span>
                    </div>
                    @endif
                    <hr class="my-2">
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold fs-5">Total:</span>
                        <span class="fw-bold fs-5 text-primary">${{ number_format($invoice->total_amount, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <span class="text-success fw-semibold">Paid:</span>
                        <span class="text-success fw-semibold">${{ number_format($invoice->paid_amount ?? 0, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-danger fw-semibold">Balance Due:</span>
                        <span class="text-danger fw-bold">${{ number_format($invoice->total_amount - ($invoice->paid_amount ?? 0), 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    @media print {
        .no-print { display: none !important; }
        .card { border: none !important; box-shadow: none !important; }
    }
</style>
@endpush

@push('scripts')
<script>
    function printInvoice() {
        window.print();
    }
</script>
@endpush

@extends('layouts.app')

@section('title', 'Sale #' . $sale->invoice_number)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="fas fa-receipt me-2"></i>Sale Details</h4>
    <div>
        <a href="{{ route('sales.receipt', $sale->id) }}" class="btn btn-secondary" target="_blank">
            <i class="fas fa-print me-1"></i>Print Receipt
        </a>
        <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Back
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0 fw-bold">Items</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Barcode</th>
                                <th>Qty</th>
                                <th>Unit Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sale->items as $item)
                            <tr>
                                <td>{{ $item->product?->name ?? 'N/A' }}</td>
                                <td><span class="badge bg-secondary">{{ $item->product?->barcode ?? 'N/A' }}</span></td>
                                <td>{{ $item->quantity }}</td>
                                <td>${{ number_format($item->unit_price, 2) }}</td>
                                <td class="fw-bold">${{ number_format($item->total_price, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0 fw-bold">Summary</h6>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <td class="text-muted">Invoice #</td>
                        <td class="fw-bold">{{ $sale->invoice_number }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Date</td>
                        <td>{{ $sale->created_at->format('M d, Y h:i A') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Cashier</td>
                        <td>{{ $sale->user?->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Payment Method</td>
                        <td><span class="badge bg-info">{{ ucfirst($sale->payment_method) }}</span></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Total Amount</td>
                        <td class="fw-bold fs-5">${{ number_format($sale->total_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Paid Amount</td>
                        <td>${{ number_format($sale->paid_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Change</td>
                        <td>${{ number_format($sale->change_amount, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

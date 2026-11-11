@extends('layouts.admin')

@section('title', 'Invoice PDF - ' . $invoice->invoice_number)

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-white border-0 pt-4">
                    <div class="row">
                        <div class="col-6">
                            <h3 class="fw-bold text-primary">{{ config('app.name', 'LogiPro') }}</h3>
                            <small class="text-muted">Logistics Solutions</small>
                        </div>
                        <div class="col-6 text-end">
                            <h4 class="fw-bold">INVOICE</h4>
                            <span class="badge bg-primary fs-6">{{ $invoice->invoice_number }}</span>
                        </div>
                    </div>
                    <hr>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-6">
                            <small class="text-muted d-block fw-bold">BILL TO</small>
                            <strong>{{ $invoice->customer?->name ?? 'N/A' }}</strong>
                            <small class="d-block">{{ $invoice->customer?->address ?? '' }}</small>
                            <small class="d-block">{{ $invoice->customer?->city ?? '' }}{{ $invoice->customer?->province ? ', ' . $invoice->customer->province : '' }}</small>
                            <small class="d-block">{{ $invoice->customer?->phone ?? '' }}</small>
                        </div>
                        <div class="col-6 text-end">
                            <table class="table-sm ms-auto">
                                <tr><td class="text-muted">Invoice Date:</td><td class="fw-medium">{{ $invoice->invoice_date?->format('d M Y') ?? '—' }}</td></tr>
                                <tr><td class="text-muted">Due Date:</td><td class="fw-medium">{{ $invoice->due_date?->format('d M Y') ?? '—' }}</td></tr>
                                <tr><td class="text-muted">Status:</td>
                                    <td>
                                        <span class="badge bg-{{ $invoice->status == 'paid' ? 'success' : ($invoice->status == 'overdue' ? 'danger' : 'warning') }}">
                                            {{ ucfirst($invoice->status ?? 'unpaid') }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Item</th>
                                <th class="text-end">Quantity</th>
                                <th class="text-end">Rate</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoice->items ?? [] as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $item->description ?? $item->name ?? '—' }}</td>
                                    <td class="text-end">{{ $item->quantity ?? 1 }}</td>
                                    <td class="text-end">${{ number_format($item->rate ?? $item->unit_price ?? 0, 2) }}</td>
                                    <td class="text-end">${{ number_format(($item->quantity ?? 1) * ($item->rate ?? $item->unit_price ?? 0), 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">No items</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-end fw-bold">Subtotal:</td>
                                <td class="text-end">${{ number_format($invoice->subtotal ?? $invoice->total ?? 0, 2) }}</td>
                            </tr>
                            @if($invoice->tax > 0)
                                <tr>
                                    <td colspan="4" class="text-end fw-bold">Tax ({{ $invoice->tax_rate ?? 0 }}%):</td>
                                    <td class="text-end">${{ number_format($invoice->tax ?? 0, 2) }}</td>
                                </tr>
                            @endif
                            <tr class="fw-bold">
                                <td colspan="4" class="text-end fs-5">Total:</td>
                                <td class="text-end fs-5">${{ number_format($invoice->total ?? 0, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>

                    @if($invoice->notes)
                        <div class="mt-3">
                            <small class="text-muted fw-bold">Notes:</small>
                            <p class="mb-0">{{ $invoice->notes }}</p>
                        </div>
                    @endif
                </div>
                <div class="card-footer text-center text-muted small py-3">
                    {{ config('app.name', 'LogiPro') }} | Thank you for your business!
                </div>
            </div>
        </div>
    </div>
    <div class="text-center mt-3">
        <button onclick="window.print()" class="btn btn-primary"><i class="bi bi-printer"></i> Print</button>
        <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>
@endsection

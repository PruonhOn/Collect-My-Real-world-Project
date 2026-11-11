@extends('layouts.admin')

@section('title', $payment->payment_number . ' - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('payments.index') }}">Payments</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $payment->payment_number ?? $payment->id }}</li>
@endsection

@section('page-title', 'Payment ' . ($payment->payment_number ?? ''))
@section('page-subtitle', 'Invoice: ' . ($payment->invoice?->invoice_number ?? '—'))

@section('page-actions')
    <a href="{{ route('payments.edit', $payment) }}" class="btn btn-primary btn-sm">
        <i class="bi bi-pencil"></i> Edit
    </a>
    <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Back
    </a>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0 fw-bold">Payment Details</h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5 text-muted small">Payment #</dt>
                        <dd class="col-sm-7"><span class="badge bg-secondary">{{ $payment->payment_number ?? $payment->id }}</span></dd>

                        <dt class="col-sm-5 text-muted small">Invoice</dt>
                        <dd class="col-sm-7">
                            <a href="{{ route('invoices.show', $payment->invoice) }}" class="text-decoration-none">
                                {{ $payment->invoice?->invoice_number ?? '—' }}
                            </a>
                        </dd>

                        <dt class="col-sm-5 text-muted small">Customer</dt>
                        <dd class="col-sm-7">{{ $payment->customer?->name ?? $payment->invoice?->customer?->name ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted small">Amount</dt>
                        <dd class="col-sm-7 fw-bold fs-5">${{ number_format($payment->amount ?? 0, 2) }}</dd>

                        <dt class="col-sm-5 text-muted small">Method</dt>
                        <dd class="col-sm-7">{{ ucwords(str_replace('_', ' ', $payment->payment_method ?? '')) }}</dd>

                        <dt class="col-sm-5 text-muted small">Payment Date</dt>
                        <dd class="col-sm-7">{{ $payment->payment_date?->format('d M Y') ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted small">Reference</dt>
                        <dd class="col-sm-7">{{ $payment->reference_number ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted small">Currency</dt>
                        <dd class="col-sm-7">{{ $payment->currency ?? 'USD' }}</dd>

                        <dt class="col-sm-5 text-muted small">Received By</dt>
                        <dd class="col-sm-7">{{ $payment->createdBy?->name ?? '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0 fw-bold">Invoice Information</h6>
                </div>
                <div class="card-body">
                    @if($payment->invoice)
                        <dl class="row mb-0">
                            <dt class="col-sm-5 text-muted small">Invoice #</dt>
                            <dd class="col-sm-7">
                                <a href="{{ route('invoices.show', $payment->invoice) }}" class="text-decoration-none">
                                    {{ $payment->invoice->invoice_number }}
                                </a>
                            </dd>

                            <dt class="col-sm-5 text-muted small">Total</dt>
                            <dd class="col-sm-7">${{ number_format($payment->invoice->total ?? 0, 2) }}</dd>

                            <dt class="col-sm-5 text-muted small">Balance</dt>
                            <dd class="col-sm-7">${{ number_format($payment->invoice->balance ?? 0, 2) }}</dd>

                            <dt class="col-sm-5 text-muted small">Status</dt>
                            <dd class="col-sm-7">
                                @php
                                    $isc = ['paid' => 'bg-success', 'unpaid' => 'bg-warning text-dark', 'overdue' => 'bg-danger', 'cancelled' => 'bg-secondary'];
                                @endphp
                                <span class="badge {{ $isc[$payment->invoice->status] ?? 'bg-secondary' }}">
                                    {{ ucfirst($payment->invoice->status ?? 'unknown') }}
                                </span>
                            </dd>
                        </dl>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-receipt fs-2 d-block mb-2"></i>
                            <p class="mb-0">Invoice information not available</p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0 fw-bold">Notes</h6>
                </div>
                <div class="card-body">
                    @if($payment->notes)
                        <p class="mb-0">{{ $payment->notes }}</p>
                    @else
                        <p class="text-muted mb-0">No notes</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

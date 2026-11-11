@extends('layouts.admin')

@section('title', 'Record Payment - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('invoices.index') }}">Invoices</a></li>
    <li class="breadcrumb-item"><a href="{{ route('invoices.show', $invoice) }}">#{{ $invoice->invoice_number }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">Record Payment</li>
@endsection

@section('page-title', 'Record Payment')
@section('page-subtitle', 'Invoice #' . $invoice->invoice_number . ' — Balance: $' . number_format($invoice->balance, 2))

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Invoice Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <small class="text-muted d-block">Invoice #</small>
                            <strong>#{{ $invoice->invoice_number }}</strong>
                        </div>
                        <div class="col-md-3 mb-2">
                            <small class="text-muted d-block">Customer</small>
                            <strong>{{ $invoice->customer?->name ?? '—' }}</strong>
                        </div>
                        <div class="col-md-2 mb-2">
                            <small class="text-muted d-block">Total</small>
                            <strong>${{ number_format($invoice->total, 2) }}</strong>
                        </div>
                        <div class="col-md-2 mb-2">
                            <small class="text-muted d-block">Paid</small>
                            <strong class="text-success">${{ number_format($invoice->paid, 2) }}</strong>
                        </div>
                        <div class="col-md-2 mb-2">
                            <small class="text-muted d-block">Balance</small>
                            <strong class="text-danger">${{ number_format($invoice->balance, 2) }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Payment Details</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('invoices.payments.store', $invoice) }}" method="POST">
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="payment_number" class="form-label">Payment Number</label>
                                <input type="text" id="payment_number" name="payment_number" class="form-control @error('payment_number') is-invalid @enderror"
                                    value="{{ old('payment_number', $paymentNumber ?? 'PAY-' . date('Ymd') . '-' . str_pad(($invoice->payments->count() ?? 0) + 1, 4, '0', STR_PAD_LEFT)) }}" readonly>
                                @error('payment_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="payment_date" class="form-label">Payment Date <span class="text-danger">*</span></label>
                                <input type="date" id="payment_date" name="payment_date" class="form-control @error('payment_date') is-invalid @enderror"
                                    value="{{ old('payment_date', date('Y-m-d')) }}" required>
                                @error('payment_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="amount" class="form-label">Amount <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" id="amount" name="amount" class="form-control @error('amount') is-invalid @enderror"
                                        value="{{ old('amount', $invoice->balance) }}" min="0.01" max="{{ $invoice->balance }}" required>
                                    @error('amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="payment_method" class="form-label">Payment Method <span class="text-danger">*</span></label>
                                <select id="payment_method" name="payment_method" class="form-select @error('payment_method') is-invalid @enderror" required>
                                    <option value="">Select Method...</option>
                                    <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>Cash</option>
                                    <option value="bank_transfer" {{ old('payment_method') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                    <option value="credit_card" {{ old('payment_method') == 'credit_card' ? 'selected' : '' }}>Credit Card</option>
                                    <option value="check" {{ old('payment_method') == 'check' ? 'selected' : '' }}>Check</option>
                                    <option value="aba" {{ old('payment_method') == 'aba' ? 'selected' : '' }}>ABA Pay</option>
                                    <option value="wing" {{ old('payment_method') == 'wing' ? 'selected' : '' }}>Wing</option>
                                    <option value="mobile_payment" {{ old('payment_method') == 'mobile_payment' ? 'selected' : '' }}>Other Mobile Payment</option>
                                </select>
                                @error('payment_method')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="currency" class="form-label">Currency</label>
                                <select id="currency" name="currency" class="form-select @error('currency') is-invalid @enderror">
                                    <option value="USD" {{ old('currency', 'USD') == 'USD' ? 'selected' : '' }}>USD ($)</option>
                                    <option value="KHR" {{ old('currency') == 'KHR' ? 'selected' : '' }}>KHR (៛)</option>
                                    <option value="THB" {{ old('currency') == 'THB' ? 'selected' : '' }}>THB (฿)</option>
                                </select>
                                @error('currency')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="reference_number" class="form-label">Reference Number</label>
                                <input type="text" id="reference_number" name="reference_number" class="form-control @error('reference_number') is-invalid @enderror"
                                    value="{{ old('reference_number') }}" placeholder="Transaction ID, check #, etc.">
                                @error('reference_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea id="notes" name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2" placeholder="Optional payment notes...">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-lg"></i> Record Payment
                            </button>
                            <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Payment History</h5>
                </div>
                <div class="card-body p-0">
                    @if($invoice->payments && $invoice->payments->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($invoice->payments as $payment)
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <strong>#{{ $payment->payment_number }}</strong>
                                            <small class="d-block text-muted">{{ $payment->payment_date->format('d M Y') }}</small>
                                            <small class="d-block text-muted">{{ ucfirst($payment->payment_method) }}</small>
                                        </div>
                                        <div class="text-end">
                                            <strong class="text-success">${{ number_format($payment->amount, 2) }}</strong>
                                            <small class="d-block text-muted">{{ $payment->reference_number ?? '' }}</small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-cash-stack fs-2 d-block mb-2"></i>
                            <p class="mb-0">No previous payments</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

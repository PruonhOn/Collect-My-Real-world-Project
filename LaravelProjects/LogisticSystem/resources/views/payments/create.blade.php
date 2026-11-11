@extends('layouts.admin')

@section('title', 'Record Payment - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('payments.index') }}">Payments</a></li>
    <li class="breadcrumb-item active" aria-current="page">Record Payment</li>
@endsection

@section('page-title', 'Record Payment')
@section('page-subtitle', 'Enter payment details')

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('payments.store') }}" method="POST">
                        @csrf

                        @if($invoice)
                            <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">
                        @endif

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Invoice <span class="text-danger">*</span></label>
                                <select name="invoice_id" class="form-select @error('invoice_id') is-invalid @enderror" {{ $invoice ? 'disabled' : '' }}>
                                    <option value="">Select Invoice</option>
                                    @foreach($invoices as $inv)
                                        <option value="{{ $inv->id }}" {{ ($invoiceId == $inv->id) ? 'selected' : '' }}>
                                            {{ $inv->invoice_number }} - ${{ number_format($inv->total, 2) }}
                                        </option>
                                    @endforeach
                                </select>
                                @if($invoice)
                                    <div class="form-text">Invoice: {{ $invoice->invoice_number }} - Balance: ${{ number_format($invoice->balance ?? $invoice->total, 2) }}</div>
                                @endif
                                @error('invoice_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                                <input type="date" name="payment_date" class="form-control @error('payment_date') is-invalid @enderror"
                                       value="{{ old('payment_date', date('Y-m-d')) }}">
                                @error('payment_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Amount <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" min="0" name="amount"
                                           class="form-control @error('amount') is-invalid @enderror"
                                           value="{{ old('amount', $invoice?->balance ?? '') }}">
                                    @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                                <select name="payment_method" class="form-select @error('payment_method') is-invalid @enderror">
                                    <option value="">Select Method</option>
                                    <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>Cash</option>
                                    <option value="bank_transfer" {{ old('payment_method') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                    <option value="credit_card" {{ old('payment_method') == 'credit_card' ? 'selected' : '' }}>Credit Card</option>
                                    <option value="check" {{ old('payment_method') == 'check' ? 'selected' : '' }}>Check</option>
                                    <option value="mobile_money" {{ old('payment_method') == 'mobile_money' ? 'selected' : '' }}>Mobile Money</option>
                                </select>
                                @error('payment_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Currency</label>
                                <input type="text" name="currency" class="form-control @error('currency') is-invalid @enderror"
                                       value="{{ old('currency', 'USD') }}">
                                @error('currency') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Exchange Rate</label>
                                <input type="number" step="0.0001" min="0" name="exchange_rate"
                                       class="form-control @error('exchange_rate') is-invalid @enderror"
                                       value="{{ old('exchange_rate', '1.0000') }}">
                                @error('exchange_rate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Reference Number</label>
                                <input type="text" name="reference_number" class="form-control @error('reference_number') is-invalid @enderror"
                                       value="{{ old('reference_number') }}">
                                @error('reference_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                                @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Record Payment
                            </button>
                            <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

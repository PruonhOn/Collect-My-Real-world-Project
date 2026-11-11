@extends('layouts.admin')

@section('title', 'Edit Payment - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('payments.index') }}">Payments</a></li>
    <li class="breadcrumb-item active" aria-current="page">Edit #{{ $payment->payment_number ?? $payment->id }}</li>
@endsection

@section('page-title', 'Edit Payment')
@section('page-subtitle', $payment->payment_number ?? 'Payment #' . $payment->id)

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('payments.update', $payment) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Invoice <span class="text-danger">*</span></label>
                                <select name="invoice_id" class="form-select @error('invoice_id') is-invalid @enderror">
                                    <option value="">Select Invoice</option>
                                    @foreach($invoices as $inv)
                                        <option value="{{ $inv->id }}" {{ old('invoice_id', $payment->invoice_id) == $inv->id ? 'selected' : '' }}>
                                            {{ $inv->invoice_number }} - ${{ number_format($inv->total, 2) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('invoice_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                                <input type="date" name="payment_date" class="form-control @error('payment_date') is-invalid @enderror"
                                       value="{{ old('payment_date', $payment->payment_date?->format('Y-m-d')) }}">
                                @error('payment_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Amount <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" min="0" name="amount"
                                           class="form-control @error('amount') is-invalid @enderror"
                                           value="{{ old('amount', $payment->amount) }}">
                                    @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                                <select name="payment_method" class="form-select @error('payment_method') is-invalid @enderror">
                                    <option value="">Select Method</option>
                                    @foreach(['cash', 'bank_transfer', 'credit_card', 'check', 'mobile_money'] as $method)
                                        <option value="{{ $method }}" {{ old('payment_method', $payment->payment_method) == $method ? 'selected' : '' }}>
                                            {{ ucwords(str_replace('_', ' ', $method)) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('payment_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Reference Number</label>
                                <input type="text" name="reference_number" class="form-control @error('reference_number') is-invalid @enderror"
                                       value="{{ old('reference_number', $payment->reference_number) }}">
                                @error('reference_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $payment->notes) }}</textarea>
                                @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Update Payment
                            </button>
                            <a href="{{ route('payments.show', $payment) }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

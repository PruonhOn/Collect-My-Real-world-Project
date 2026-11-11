@extends('layouts.admin')

@section('title', 'Edit Payment')
@section('subtitle', 'Update payment information')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Edit Payment</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('payments.index') }}">Payments</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<form action="{{ route('payments.update', $payment) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-credit-card me-2 text-primary"></i>Payment Details</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Customer <span class="text-danger">*</span></label>
                            <select class="form-select @error('customer_id') is-invalid @enderror" name="customer_id" required>
                                <option value="">Select Customer</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" {{ old('customer_id', $payment->customer_id) == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                                @endforeach
                            </select>
                            @error('customer_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Invoice <span class="text-danger">*</span></label>
                            <select class="form-select @error('invoice_id') is-invalid @enderror" name="invoice_id" required>
                                <option value="">Select Invoice</option>
                                @foreach($invoices as $invoice)
                                    <option value="{{ $invoice->id }}" {{ old('invoice_id', $payment->invoice_id) == $invoice->id ? 'selected' : '' }}>{{ $invoice->invoice_number }}</option>
                                @endforeach
                            </select>
                            @error('invoice_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                            <select class="form-select @error('payment_method_id') is-invalid @enderror" name="payment_method_id" required>
                                <option value="">Select Payment Method</option>
                                @foreach($paymentMethods as $method)
                                    <option value="{{ $method->id }}" {{ old('payment_method_id', $payment->payment_method_id) == $method->id ? 'selected' : '' }}>{{ $method->name }}</option>
                                @endforeach
                            </select>
                            @error('payment_method_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Amount ($) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('amount') is-invalid @enderror" name="amount" step="0.01" value="{{ old('amount', $payment->amount) }}" required>
                            @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Currency <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('currency') is-invalid @enderror" name="currency" value="{{ old('currency', $payment->currency) }}" required>
                            @error('currency') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Reference Number</label>
                            <input type="text" class="form-control" name="reference_number" value="{{ old('reference_number', $payment->reference_number) }}" placeholder="Transaction ID, Check #, etc.">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="payment_date" value="{{ old('payment_date', $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('Y-m-d') : '') }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="2">{{ old('notes', $payment->notes) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2"></i>Payment Info</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3 pb-3 border-bottom">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Payment Number</label>
                        <p class="fw-medium mb-0">{{ $payment->payment_number }}</p>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Status</label>
                        <p class="mb-0">
                            @switch($payment->status)
                                @case('completed')
                                    <span class="badge bg-success">Completed</span>
                                    @break
                                @case('pending')
                                    <span class="badge bg-warning text-dark">Pending</span>
                                    @break
                                @case('failed')
                                    <span class="badge bg-danger">Failed</span>
                                    @break
                                @case('refunded')
                                    <span class="badge bg-info">Refunded</span>
                                    @break
                                @default
                                    <span class="badge bg-secondary">{{ ucfirst($payment->status) }}</span>
                            @endswitch
                        </p>
                    </div>
                    <div class="mb-0">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Created</label>
                        <p class="fw-medium mb-0">{{ $payment->created_at->format('d M Y, h:i A') }}</p>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-1"></i> Update Payment
                </button>
                <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg me-1"></i> Cancel
                </a>
            </div>
        </div>
    </div>
</form>
@endsection

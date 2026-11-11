@extends('layouts.admin')

@section('title', 'Record Payment')
@section('subtitle', 'Record a new payment')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Record Payment</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('payments.index') }}">Payments</a></li>
                <li class="breadcrumb-item active">Record</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<form action="{{ route('payments.store') }}" method="POST">
    @csrf
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
                            <select class="form-select @error('customer_id') is-invalid @enderror" name="customer_id" id="customerSelect" required>
                                <option value="">Select Customer</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                                @endforeach
                            </select>
                            @error('customer_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Invoice <span class="text-danger">*</span></label>
                            <select class="form-select @error('invoice_id') is-invalid @enderror" name="invoice_id" id="invoiceSelect" required>
                                <option value="">Select Invoice (Unpaid/Partial)</option>
                            </select>
                            @error('invoice_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                            <select class="form-select @error('method') is-invalid @enderror" name="method" required>
                                <option value="cash" {{ old('method') == 'cash' ? 'selected' : '' }}>Cash</option>
                                <option value="bank_transfer" {{ old('method') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                <option value="credit_card" {{ old('method') == 'credit_card' ? 'selected' : '' }}>Credit Card</option>
                                <option value="check" {{ old('method') == 'check' ? 'selected' : '' }}>Check</option>
                                <option value="mobile_payment" {{ old('method') == 'mobile_payment' ? 'selected' : '' }}>Mobile Payment</option>
                            </select>
                            @error('method') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Amount ($) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('amount') is-invalid @enderror" name="amount" id="paymentAmount" step="0.01" value="{{ old('amount') }}" required>
                            @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Reference Number</label>
                            <input type="text" class="form-control" name="reference_number" value="{{ old('reference_number') }}" placeholder="Transaction ID, Check #, etc.">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="payment_date" value="{{ old('payment_date', date('Y-m-d')) }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="2">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold">Invoice Balance</h6>
                </div>
                <div class="card-body" id="invoiceBalance">
                    <p class="text-muted mb-0">Select a customer and invoice to view balance.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle me-1"></i> Record Payment
        </button>
    </div>
</form>
@endsection

@push('scripts')
<script>
    $('#customerSelect').on('change', function() {
        const customerId = $(this).val();
        const invoiceSelect = $('#invoiceSelect');
        invoiceSelect.html('<option value="">Loading...</option>');

        if (customerId) {
            fetch(`/admin/api/invoices/unpaid?customer_id=${customerId}`)
                .then(response => response.json())
                .then(data => {
                    let options = '<option value="">Select Invoice (Unpaid/Partial)</option>';
                    data.forEach(function(invoice) {
                        const balance = invoice.total_amount - (invoice.paid_amount || 0);
                        options += `<option value="${invoice.id}" data-balance="${balance}">${invoice.invoice_number} - Balance: $${balance.toFixed(2)}</option>`;
                    });
                    invoiceSelect.html(options);
                })
                .catch(() => {
                    invoiceSelect.html('<option value="">Error loading invoices</option>');
                });
        } else {
            invoiceSelect.html('<option value="">Select Invoice (Unpaid/Partial)</option>');
        }
    });

    $('#invoiceSelect').on('change', function() {
        const option = $(this).find(':selected');
        const balance = option.data('balance');
        if (balance !== undefined) {
            $('#invoiceBalance').html(`
                <div class="text-center">
                    <h3 class="text-primary fw-bold">$${parseFloat(balance).toFixed(2)}</h3>
                    <small class="text-muted">Amount Due</small>
                </div>
            `);
            $('#paymentAmount').attr('max', balance);
        } else {
            $('#invoiceBalance').html('<p class="text-muted mb-0">Select an invoice to view balance.</p>');
        }
    });
</script>
@endpush

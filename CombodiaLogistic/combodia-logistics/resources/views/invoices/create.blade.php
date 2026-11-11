@extends('layouts.admin')

@section('title', 'Create Invoice')
@section('subtitle', 'Generate a new invoice')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Create Invoice</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('invoices.index') }}">Invoices</a></li>
                <li class="breadcrumb-item active">Create</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<form action="{{ route('invoices.store') }}" method="POST" id="invoiceForm">
    @csrf
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-receipt me-2 text-primary"></i>Invoice Details</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Customer <span class="text-danger">*</span></label>
                            <select class="form-select @error('customer_id') is-invalid @enderror" name="customer_id" required>
                                <option value="">Select Customer</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                                @endforeach
                            </select>
                            @error('customer_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Shipment (Optional)</label>
                            <select class="form-select" name="shipment_id">
                                <option value="">Select Shipment</option>
                                @foreach($shipments as $shipment)
                                    <option value="{{ $shipment->id }}" {{ old('shipment_id') == $shipment->id ? 'selected' : '' }}>
                                        {{ $shipment->shipment_number }} - ${{ number_format($shipment->total_amount ?? 0, 2) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Issue Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="issue_date" value="{{ old('issue_date', date('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Due Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="due_date" value="{{ old('due_date', date('Y-m-d', strtotime('+30 days'))) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tax Rate (%)</label>
                            <input type="number" class="form-control" name="tax_rate" id="taxRate" step="0.01" value="{{ old('tax_rate', '10.00') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Discount ($)</label>
                            <input type="number" class="form-control" name="discount" id="discount" step="0.01" value="{{ old('discount', '0.00') }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-list-ul me-2 text-success"></i>Invoice Items</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addItem">
                        <i class="bi bi-plus-circle me-1"></i> Add Item
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="itemsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Description</th>
                                    <th>Qty</th>
                                    <th>Unit Price ($)</th>
                                    <th>Total ($)</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody">
                                <tr class="item-row">
                                    <td><input type="text" class="form-control form-control-sm" name="items[0][description]" required></td>
                                    <td><input type="number" class="form-control form-control-sm item-qty" name="items[0][quantity]" min="1" value="1" required></td>
                                    <td><input type="number" class="form-control form-control-sm item-price" name="items[0][unit_price]" step="0.01" value="0" required></td>
                                    <td><span class="item-total fw-semibold">$0.00</span></td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger remove-item"><i class="bi bi-trash"></i></button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold">Terms & Conditions</h6>
                </div>
                <div class="card-body">
                    <textarea class="form-control" name="terms" rows="3" placeholder="Payment terms, late fees, etc.">{{ old('terms', 'Payment is due within 30 days of invoice date. Late payments are subject to a 1.5% monthly interest charge.') }}</textarea>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold">Invoice Summary</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Subtotal:</span>
                        <span id="subtotal">$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Tax (<span id="taxRateDisplay">10</span>%):</span>
                        <span id="taxAmount">$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Discount:</span>
                        <span class="text-danger" id="discountAmount">-$0.00</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold fs-5">Total:</span>
                        <span class="fw-bold fs-5 text-primary" id="grandTotal">$0.00</span>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" name="action" value="save_draft" class="btn btn-outline-secondary">
                    <i class="bi bi-save me-1"></i> Save as Draft
                </button>
                <button type="submit" name="action" value="save_send" class="btn btn-primary">
                    <i class="bi bi-send me-1"></i> Save & Send
                </button>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    let itemIndex = 1;

    $('#addItem').on('click', function() {
        const row = `
            <tr class="item-row">
                <td><input type="text" class="form-control form-control-sm" name="items[${itemIndex}][description]" required></td>
                <td><input type="number" class="form-control form-control-sm item-qty" name="items[${itemIndex}][quantity]" min="1" value="1" required></td>
                <td><input type="number" class="form-control form-control-sm item-price" name="items[${itemIndex}][unit_price]" step="0.01" value="0" required></td>
                <td><span class="item-total fw-semibold">$0.00</span></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger remove-item"><i class="bi bi-trash"></i></button></td>
            </tr>`;
        $('#itemsBody').append(row);
        itemIndex++;
        calculateTotals();
    });

    $(document).on('click', '.remove-item', function() {
        if ($('.item-row').length > 1) {
            $(this).closest('tr').remove();
            calculateTotals();
        }
    });

    $(document).on('input', '.item-qty, .item-price, #taxRate, #discount', function() {
        calculateTotals();
    });

    function calculateTotals() {
        let subtotal = 0;
        $('.item-row').each(function() {
            const qty = parseFloat($(this).find('.item-qty').val()) || 0;
            const price = parseFloat($(this).find('.item-price').val()) || 0;
            const total = qty * price;
            $(this).find('.item-total').text('$' + total.toFixed(2));
            subtotal += total;
        });

        const taxRate = parseFloat($('#taxRate').val()) || 0;
        const discount = parseFloat($('#discount').val()) || 0;
        const tax = subtotal * (taxRate / 100);
        const grandTotal = subtotal + tax - discount;

        $('#subtotal').text('$' + subtotal.toFixed(2));
        $('#taxRateDisplay').text(taxRate);
        $('#taxAmount').text('$' + tax.toFixed(2));
        $('#discountAmount').text('-$' + discount.toFixed(2));
        $('#grandTotal').text('$' + grandTotal.toFixed(2));
    }

    $(document).ready(function() { calculateTotals(); });
</script>
@endpush

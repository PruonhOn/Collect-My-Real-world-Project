@extends('layouts.admin')

@section('title', 'Create Invoice - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('invoices.index') }}">Invoices</a></li>
    <li class="breadcrumb-item active" aria-current="page">Create</li>
@endsection

@section('page-title', 'Create Invoice')
@section('page-subtitle', 'Generate a new invoice')

@section('content')
    <div class="row">
        <div class="col-lg-10">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('invoices.store') }}" method="POST">
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="customer_id" class="form-label">Customer <span class="text-danger">*</span></label>
                                <select id="customer_id" name="customer_id" class="form-select select2 @error('customer_id') is-invalid @enderror" required>
                                    <option value="">Select Customer...</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                            {{ $customer->name }} ({{ $customer->code ?? $customer->email }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('customer_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="shipment_id" class="form-label">Shipment (Optional)</label>
                                <select id="shipment_id" name="shipment_id" class="form-select select2 @error('shipment_id') is-invalid @enderror">
                                    <option value="">No Shipment</option>
                                    @foreach($shipments as $shipment)
                                        <option value="{{ $shipment->id }}" {{ old('shipment_id') == $shipment->id ? 'selected' : '' }}>
                                            #{{ $shipment->shipment_number }} - {{ $shipment->customer?->name ?? 'N/A' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('shipment_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="invoice_date" class="form-label">Invoice Date <span class="text-danger">*</span></label>
                                <input type="date" id="invoice_date" name="invoice_date" class="form-control @error('invoice_date') is-invalid @enderror"
                                    value="{{ old('invoice_date', date('Y-m-d')) }}" required>
                                @error('invoice_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="due_date" class="form-label">Due Date <span class="text-danger">*</span></label>
                                <input type="date" id="due_date" name="due_date" class="form-control @error('due_date') is-invalid @enderror"
                                    value="{{ old('due_date', date('Y-m-d', strtotime('+30 days'))) }}" required>
                                @error('due_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
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

                            <div class="col-12">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea id="notes" name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2" placeholder="Optional invoice notes...">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0">Invoice Items</h6>
                            <button type="button" id="addItemBtn" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-plus-lg"></i> Add Item
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered" id="itemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 30%">Description</th>
                                        <th style="width: 15%">Type</th>
                                        <th style="width: 10%">Qty</th>
                                        <th style="width: 15%">Unit Price</th>
                                        <th style="width: 15%">Total</th>
                                        <th style="width: 5%"></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsContainer">
                                    <tr class="item-row">
                                        <td>
                                            <input type="text" name="items[0][description]" class="form-control form-control-sm" placeholder="Item description" required>
                                        </td>
                                        <td>
                                            <select name="items[0][type]" class="form-select form-select-sm">
                                                <option value="service">Service</option>
                                                <option value="shipping">Shipping</option>
                                                <option value="product">Product</option>
                                                <option value="fee">Fee</option>
                                                <option value="other">Other</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" name="items[0][quantity]" class="form-control form-control-sm item-qty" value="1" min="1" required>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" name="items[0][unit_price]" class="form-control form-control-sm item-price" value="0.00" min="0" required>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm item-total" value="$0.00" readonly>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-outline-danger btn-sm remove-item" disabled>
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="text-end fw-bold">Subtotal:</td>
                                        <td><strong id="subtotal">$0.00</strong></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="text-end fw-bold">Total:</td>
                                        <td><strong id="totalDisplay">$0.00</strong></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Create Invoice
                            </button>
                            <a href="{{ route('invoices.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });

            calculateTotals();
        });

        let itemIndex = 1;

        document.getElementById('addItemBtn').addEventListener('click', function() {
            const container = document.getElementById('itemsContainer');
            const row = `
                <tr class="item-row">
                    <td>
                        <input type="text" name="items[${itemIndex}][description]" class="form-control form-control-sm" placeholder="Item description" required>
                    </td>
                    <td>
                        <select name="items[${itemIndex}][type]" class="form-select form-select-sm">
                            <option value="service">Service</option>
                            <option value="shipping">Shipping</option>
                            <option value="product">Product</option>
                            <option value="fee">Fee</option>
                            <option value="other">Other</option>
                        </select>
                    </td>
                    <td>
                        <input type="number" name="items[${itemIndex}][quantity]" class="form-control form-control-sm item-qty" value="1" min="1" required>
                    </td>
                    <td>
                        <input type="number" step="0.01" name="items[${itemIndex}][unit_price]" class="form-control form-control-sm item-price" value="0.00" min="0" required>
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm item-total" value="$0.00" readonly>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-outline-danger btn-sm remove-item">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            container.insertAdjacentHTML('beforeend', row);
            itemIndex++;
            updateRemoveButtons();
            attachItemEvents();
        });

        document.addEventListener('click', function(e) {
            if (e.target.closest('.remove-item')) {
                const row = e.target.closest('.item-row');
                if (document.querySelectorAll('.item-row').length > 1) {
                    row.remove();
                    updateRemoveButtons();
                    calculateTotals();
                }
            }
        });

        function attachItemEvents() {
            document.querySelectorAll('.item-qty, .item-price').forEach(input => {
                input.removeEventListener('input', calculateTotals);
                input.addEventListener('input', calculateTotals);
            });
        }

        function calculateTotals() {
            let subtotal = 0;
            document.querySelectorAll('.item-row').forEach(row => {
                const qty = parseFloat(row.querySelector('.item-qty')?.value) || 0;
                const price = parseFloat(row.querySelector('.item-price')?.value) || 0;
                const total = qty * price;
                const totalInput = row.querySelector('.item-total');
                if (totalInput) {
                    totalInput.value = '$' + total.toFixed(2);
                }
                subtotal += total;
            });
            document.getElementById('subtotal').textContent = '$' + subtotal.toFixed(2);
            document.getElementById('totalDisplay').textContent = '$' + subtotal.toFixed(2);
        }

        function updateRemoveButtons() {
            const rows = document.querySelectorAll('.item-row');
            rows.forEach((row, index) => {
                const btn = row.querySelector('.remove-item');
                if (btn) {
                    btn.disabled = rows.length <= 1;
                }
            });
        }

        attachItemEvents();
    </script>
@endsection

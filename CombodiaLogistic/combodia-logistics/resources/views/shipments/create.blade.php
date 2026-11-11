@extends('layouts.admin')

@section('title', 'Create Shipment')
@section('subtitle', 'Create a new shipment')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Create Shipment</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('shipments.index') }}">Shipments</a></li>
                <li class="breadcrumb-item active">Create</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('shipments.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<form action="{{ route('shipments.store') }}" method="POST" id="shipmentForm">
    @csrf

    <div class="row">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-person-fill me-2 text-primary"></i>Sender Information</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Sender Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('sender_name') is-invalid @enderror" name="sender_name" value="{{ old('sender_name') }}" required>
                            @error('sender_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sender Phone <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('sender_phone') is-invalid @enderror" name="sender_phone" value="{{ old('sender_phone') }}" required>
                            @error('sender_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Sender Address <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('sender_address') is-invalid @enderror" name="sender_address" value="{{ old('sender_address') }}" required>
                            @error('sender_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">City <span class="text-danger">*</span></label>
                            <select class="form-select @error('sender_city') is-invalid @enderror" name="sender_city" required>
                                <option value="">Select City</option>
                                @foreach($cities as $city)
                                    <option value="{{ $city }}" {{ old('sender_city') == $city ? 'selected' : '' }}>{{ $city }}</option>
                                @endforeach
                            </select>
                            @error('sender_city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-geo-alt-fill me-2 text-success"></i>Recipient Information</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Recipient Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('recipient_name') is-invalid @enderror" name="recipient_name" value="{{ old('recipient_name') }}" required>
                            @error('recipient_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Recipient Phone <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('recipient_phone') is-invalid @enderror" name="recipient_phone" value="{{ old('recipient_phone') }}" required>
                            @error('recipient_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Recipient Address <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('recipient_address') is-invalid @enderror" name="recipient_address" value="{{ old('recipient_address') }}" required>
                            @error('recipient_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">City <span class="text-danger">*</span></label>
                            <select class="form-select @error('recipient_city') is-invalid @enderror" name="recipient_city" required>
                                <option value="">Select City</option>
                                @foreach($cities as $city)
                                    <option value="{{ $city }}" {{ old('recipient_city') == $city ? 'selected' : '' }}>{{ $city }}</option>
                                @endforeach
                            </select>
                            @error('recipient_city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-box-seam me-2 text-info"></i>Shipment Details</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Customer <span class="text-danger">*</span></label>
                    <select class="form-select @error('customer_id') is-invalid @enderror" name="customer_id" required>
                        <option value="">Select Customer</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                        @endforeach
                    </select>
                    @error('customer_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Type <span class="text-danger">*</span></label>
                    <select class="form-select @error('type') is-invalid @enderror" name="type" required>
                        <option value="">Select Type</option>
                        <option value="express" {{ old('type') == 'express' ? 'selected' : '' }}>Express</option>
                        <option value="standard" {{ old('type') == 'standard' ? 'selected' : '' }}>Standard</option>
                        <option value="economy" {{ old('type') == 'economy' ? 'selected' : '' }}>Economy</option>
                        <option value="freight" {{ old('type') == 'freight' ? 'selected' : '' }}>Freight</option>
                    </select>
                    @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Priority <span class="text-danger">*</span></label>
                    <select class="form-select @error('priority') is-invalid @enderror" name="priority" required>
                        <option value="normal" {{ old('priority') == 'normal' ? 'selected' : '' }}>Normal</option>
                        <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Low</option>
                        <option value="medium" {{ old('priority') == 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>High</option>
                    </select>
                    @error('priority') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Special Instructions</label>
                    <input type="text" class="form-control @error('special_instructions') is-invalid @enderror" name="special_instructions" value="{{ old('special_instructions') }}">
                    @error('special_instructions') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" rows="2">{{ old('notes') }}</textarea>
                    @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-packages me-2 text-warning"></i>Items</h6>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addItem">
                <i class="bi bi-plus-circle me-1"></i> Add Item
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table" id="itemsTable">
                    <thead class="table-light">
                        <tr>
                            <th>Name <span class="text-danger">*</span></th>
                            <th>Description</th>
                            <th>Qty <span class="text-danger">*</span></th>
                            <th>Weight (kg)</th>
                            <th>Volume (m³)</th>
                            <th>L (cm)</th>
                            <th>W (cm)</th>
                            <th>H (cm)</th>
                            <th>Unit</th>
                            <th>Value ($)</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody">
                        <tr class="item-row">
                            <td><input type="text" class="form-control form-control-sm" name="items[0][name]" required></td>
                            <td><input type="text" class="form-control form-control-sm" name="items[0][description]"></td>
                            <td><input type="number" class="form-control form-control-sm item-qty" name="items[0][quantity]" min="1" value="1" required></td>
                            <td><input type="number" class="form-control form-control-sm item-weight" name="items[0][weight]" step="0.01" value="0"></td>
                            <td><input type="number" class="form-control form-control-sm item-volume" name="items[0][volume]" step="0.001" value="0"></td>
                            <td><input type="number" class="form-control form-control-sm" name="items[0][length]" step="0.01" value="0"></td>
                            <td><input type="number" class="form-control form-control-sm" name="items[0][width]" step="0.01" value="0"></td>
                            <td><input type="number" class="form-control form-control-sm" name="items[0][height]" step="0.01" value="0"></td>
                            <td>
                                <select class="form-select form-select-sm" name="items[0][unit]">
                                    <option value="pcs">Pcs</option>
                                    <option value="kg">Kg</option>
                                    <option value="box">Box</option>
                                    <option value="carton">Carton</option>
                                </select>
                            </td>
                            <td><input type="number" class="form-control form-control-sm item-value" name="items[0][declared_value]" step="0.01" value="0"></td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger remove-item"><i class="bi bi-trash"></i></button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-calculator me-2 text-danger"></i>Financial</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Freight Charge ($)</label>
                    <input type="number" class="form-control" name="freight_charge" id="freightCharge" step="0.01" value="{{ old('freight_charge', '0.00') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Insurance ($)</label>
                    <input type="number" class="form-control" name="insurance" id="insurance" step="0.01" value="{{ old('insurance', '0.00') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tax Rate (%)</label>
                    <input type="number" class="form-control" name="tax_rate" id="taxRate" step="0.01" value="{{ old('tax_rate', '0.00') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Discount ($)</label>
                    <input type="number" class="form-control" name="discount" id="discount" step="0.01" value="{{ old('discount', '0.00') }}">
                </div>
                <div class="col-12">
                    <div class="card bg-light border-0 mt-2">
                        <div class="card-body">
                            <div class="row text-end">
                                <div class="col-md-3">
                                    <small class="text-muted">Subtotal:</small>
                                    <h5 class="mb-0" id="subtotal">$0.00</h5>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted">Tax:</small>
                                    <h5 class="mb-0" id="taxAmount">$0.00</h5>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted">Discount:</small>
                                    <h5 class="mb-0 text-danger" id="discountAmount">-$0.00</h5>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted">Grand Total:</small>
                                    <h4 class="mb-0 text-primary" id="grandTotal">$0.00</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('shipments.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle me-1"></i> Create Shipment
        </button>
    </div>
</form>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    let itemIndex = 1;

    $('#addItem').on('click', function() {
        const row = `
            <tr class="item-row">
                <td><input type="text" class="form-control form-control-sm" name="items[${itemIndex}][name]" required></td>
                <td><input type="text" class="form-control form-control-sm" name="items[${itemIndex}][description]"></td>
                <td><input type="number" class="form-control form-control-sm item-qty" name="items[${itemIndex}][quantity]" min="1" value="1" required></td>
                <td><input type="number" class="form-control form-control-sm item-weight" name="items[${itemIndex}][weight]" step="0.01" value="0"></td>
                <td><input type="number" class="form-control form-control-sm item-volume" name="items[${itemIndex}][volume]" step="0.001" value="0"></td>
                <td><input type="number" class="form-control form-control-sm" name="items[${itemIndex}][length]" step="0.01" value="0"></td>
                <td><input type="number" class="form-control form-control-sm" name="items[${itemIndex}][width]" step="0.01" value="0"></td>
                <td><input type="number" class="form-control form-control-sm" name="items[${itemIndex}][height]" step="0.01" value="0"></td>
                <td>
                    <select class="form-select form-select-sm" name="items[${itemIndex}][unit]">
                        <option value="pcs">Pcs</option>
                        <option value="kg">Kg</option>
                        <option value="box">Box</option>
                        <option value="carton">Carton</option>
                    </select>
                </td>
                <td><input type="number" class="form-control form-control-sm item-value" name="items[${itemIndex}][declared_value]" step="0.01" value="0"></td>
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
        } else {
            Swal.fire('Warning', 'At least one item is required.', 'warning');
        }
    });

    $(document).on('input', '.item-value, .item-qty, .item-weight, .item-volume, #freightCharge, #insurance, #taxRate, #discount', function() {
        calculateTotals();
    });

    function calculateTotals() {
        let totalDeclaredValue = 0;
        let totalWeight = 0;
        let totalVolume = 0;

        $('.item-row').each(function() {
            let qty = parseFloat($(this).find('.item-qty').val()) || 0;
            let value = parseFloat($(this).find('.item-value').val()) || 0;
            let weight = parseFloat($(this).find('.item-weight').val()) || 0;
            let volume = parseFloat($(this).find('.item-volume').val()) || 0;
            totalDeclaredValue += qty * value;
            totalWeight += qty * weight;
            totalVolume += qty * volume;
        });

        let freight = parseFloat($('#freightCharge').val()) || 0;
        let insurance = parseFloat($('#insurance').val()) || 0;
        let taxRate = parseFloat($('#taxRate').val()) || 0;
        let discount = parseFloat($('#discount').val()) || 0;

        let subtotal = totalDeclaredValue + freight + insurance;
        let tax = subtotal * (taxRate / 100);
        let grandTotal = subtotal + tax - discount;

        $('#subtotal').text('$' + subtotal.toFixed(2));
        $('#taxAmount').text('$' + tax.toFixed(2));
        $('#discountAmount').text('-$' + discount.toFixed(2));
        $('#grandTotal').text('$' + grandTotal.toFixed(2));
    }

    $(document).ready(function() { calculateTotals(); });
</script>
@endpush

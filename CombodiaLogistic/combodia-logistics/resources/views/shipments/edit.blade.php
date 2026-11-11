@extends('layouts.admin')

@section('title', 'Edit Shipment')
@section('subtitle', 'Edit shipment #' . $shipment->shipment_number)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Edit Shipment</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('shipments.index') }}">Shipments</a></li>
                <li class="breadcrumb-item active">Edit #{{ $shipment->shipment_number }}</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('shipments.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<form action="{{ route('shipments.update', $shipment) }}" method="POST" id="shipmentForm">
    @csrf
    @method('PUT')

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
                            <input type="text" class="form-control @error('sender_name') is-invalid @enderror" name="sender_name" value="{{ old('sender_name', $shipment->sender_name) }}" required>
                            @error('sender_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sender Phone <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('sender_phone') is-invalid @enderror" name="sender_phone" value="{{ old('sender_phone', $shipment->sender_phone) }}" required>
                            @error('sender_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Sender Address <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('sender_address') is-invalid @enderror" name="sender_address" value="{{ old('sender_address', $shipment->sender_address) }}" required>
                            @error('sender_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">City <span class="text-danger">*</span></label>
                            <select class="form-select @error('sender_city') is-invalid @enderror" name="sender_city" required>
                                <option value="">Select City</option>
                                @foreach($cities as $city)
                                    <option value="{{ $city }}" {{ old('sender_city', $shipment->sender_city) == $city ? 'selected' : '' }}>{{ $city }}</option>
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
                            <input type="text" class="form-control @error('recipient_name') is-invalid @enderror" name="recipient_name" value="{{ old('recipient_name', $shipment->recipient_name) }}" required>
                            @error('recipient_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Recipient Phone <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('recipient_phone') is-invalid @enderror" name="recipient_phone" value="{{ old('recipient_phone', $shipment->recipient_phone) }}" required>
                            @error('recipient_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Recipient Address <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('recipient_address') is-invalid @enderror" name="recipient_address" value="{{ old('recipient_address', $shipment->recipient_address) }}" required>
                            @error('recipient_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">City <span class="text-danger">*</span></label>
                            <select class="form-select @error('recipient_city') is-invalid @enderror" name="recipient_city" required>
                                <option value="">Select City</option>
                                @foreach($cities as $city)
                                    <option value="{{ $city }}" {{ old('recipient_city', $shipment->recipient_city) == $city ? 'selected' : '' }}>{{ $city }}</option>
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
                            <option value="{{ $customer->id }}" {{ old('customer_id', $shipment->customer_id) == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                        @endforeach
                    </select>
                    @error('customer_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Type <span class="text-danger">*</span></label>
                    <select class="form-select @error('type') is-invalid @enderror" name="type" required>
                        <option value="express" {{ old('type', $shipment->type) == 'express' ? 'selected' : '' }}>Express</option>
                        <option value="standard" {{ old('type', $shipment->type) == 'standard' ? 'selected' : '' }}>Standard</option>
                        <option value="economy" {{ old('type', $shipment->type) == 'economy' ? 'selected' : '' }}>Economy</option>
                        <option value="freight" {{ old('type', $shipment->type) == 'freight' ? 'selected' : '' }}>Freight</option>
                    </select>
                    @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Priority</label>
                    <select class="form-select @error('priority') is-invalid @enderror" name="priority">
                        <option value="normal" {{ old('priority', $shipment->priority) == 'normal' ? 'selected' : '' }}>Normal</option>
                        <option value="low" {{ old('priority', $shipment->priority) == 'low' ? 'selected' : '' }}>Low</option>
                        <option value="medium" {{ old('priority', $shipment->priority) == 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="high" {{ old('priority', $shipment->priority) == 'high' ? 'selected' : '' }}>High</option>
                    </select>
                    @error('priority') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Special Instructions</label>
                    <input type="text" class="form-control" name="special_instructions" value="{{ old('special_instructions', $shipment->special_instructions) }}">
                </div>
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control" name="notes" rows="2">{{ old('notes', $shipment->notes) }}</textarea>
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
                            <th>Name</th>
                            <th>Description</th>
                            <th>Qty</th>
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
                        @forelse($shipment->items as $idx => $item)
                        <tr class="item-row">
                            <td><input type="text" class="form-control form-control-sm" name="items[{{ $idx }}][name]" value="{{ $item->name }}" required></td>
                            <td><input type="text" class="form-control form-control-sm" name="items[{{ $idx }}][description]" value="{{ $item->description }}"></td>
                            <td><input type="number" class="form-control form-control-sm item-qty" name="items[{{ $idx }}][quantity]" min="1" value="{{ $item->quantity }}" required></td>
                            <td><input type="number" class="form-control form-control-sm item-weight" name="items[{{ $idx }}][weight]" step="0.01" value="{{ $item->weight }}"></td>
                            <td><input type="number" class="form-control form-control-sm item-volume" name="items[{{ $idx }}][volume]" step="0.001" value="{{ $item->volume }}"></td>
                            <td><input type="number" class="form-control form-control-sm" name="items[{{ $idx }}][length]" step="0.01" value="{{ $item->length }}"></td>
                            <td><input type="number" class="form-control form-control-sm" name="items[{{ $idx }}][width]" step="0.01" value="{{ $item->width }}"></td>
                            <td><input type="number" class="form-control form-control-sm" name="items[{{ $idx }}][height]" step="0.01" value="{{ $item->height }}"></td>
                            <td>
                                <select class="form-select form-select-sm" name="items[{{ $idx }}][unit]">
                                    <option value="pcs" {{ $item->unit == 'pcs' ? 'selected' : '' }}>Pcs</option>
                                    <option value="kg" {{ $item->unit == 'kg' ? 'selected' : '' }}>Kg</option>
                                    <option value="box" {{ $item->unit == 'box' ? 'selected' : '' }}>Box</option>
                                    <option value="carton" {{ $item->unit == 'carton' ? 'selected' : '' }}>Carton</option>
                                </select>
                            </td>
                            <td><input type="number" class="form-control form-control-sm item-value" name="items[{{ $idx }}][declared_value]" step="0.01" value="{{ $item->declared_value }}"></td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger remove-item"><i class="bi bi-trash"></i></button></td>
                        </tr>
                        @empty
                        <tr class="item-row">
                            <td><input type="text" class="form-control form-control-sm" name="items[0][name]" required></td>
                            <td><input type="text" class="form-control form-control-sm" name="items[0][description]"></td>
                            <td><input type="number" class="form-control form-control-sm item-qty" name="items[0][quantity]" min="1" value="1" required></td>
                            <td><input type="number" class="form-control form-control-sm item-weight" name="items[0][weight]" step="0.01" value="0"></td>
                            <td><input type="number" class="form-control form-control-sm item-volume" name="items[0][volume]" step="0.001" value="0"></td>
                            <td><input type="number" class="form-control form-control-sm" name="items[0][length]" step="0.01" value="0"></td>
                            <td><input type="number" class="form-control form-control-sm" name="items[0][width]" step="0.01" value="0"></td>
                            <td><input type="number" class="form-control form-control-sm" name="items[0][height]" step="0.01" value="0"></td>
                            <td><select class="form-select form-select-sm" name="items[0][unit]"><option value="pcs">Pcs</option></select></td>
                            <td><input type="number" class="form-control form-control-sm item-value" name="items[0][declared_value]" step="0.01" value="0"></td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger remove-item"><i class="bi bi-trash"></i></button></td>
                        </tr>
                        @endforelse
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
                    <input type="number" class="form-control" name="freight_charge" id="freightCharge" step="0.01" value="{{ old('freight_charge', $shipment->freight_charge) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Insurance ($)</label>
                    <input type="number" class="form-control" name="insurance" id="insurance" step="0.01" value="{{ old('insurance', $shipment->insurance) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tax Rate (%)</label>
                    <input type="number" class="form-control" name="tax_rate" id="taxRate" step="0.01" value="{{ old('tax_rate', $shipment->tax_rate) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Discount ($)</label>
                    <input type="number" class="form-control" name="discount" id="discount" step="0.01" value="{{ old('discount', $shipment->discount) }}">
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
            <i class="bi bi-check-circle me-1"></i> Update Shipment
        </button>
    </div>
</form>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    let itemIndex = {{ count($shipment->items) ?: 1 }};

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
                <td><select class="form-select form-select-sm" name="items[${itemIndex}][unit]"><option value="pcs">Pcs</option><option value="kg">Kg</option><option value="box">Box</option><option value="carton">Carton</option></select></td>
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
        }
    });

    $(document).on('input', '.item-value, .item-qty, .item-weight, .item-volume, #freightCharge, #insurance, #taxRate, #discount', function() {
        calculateTotals();
    });

    function calculateTotals() {
        let totalDeclaredValue = 0;
        $('.item-row').each(function() {
            let qty = parseFloat($(this).find('.item-qty').val()) || 0;
            let value = parseFloat($(this).find('.item-value').val()) || 0;
            totalDeclaredValue += qty * value;
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

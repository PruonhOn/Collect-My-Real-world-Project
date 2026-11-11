@extends('layouts.admin')

@section('title', 'Create Stock Movement')
@section('subtitle', 'Record a new stock movement')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Create Stock Movement</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('stock-movements.index') }}">Stock Movements</a></li>
                <li class="breadcrumb-item active">Create</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('stock-movements.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<form action="{{ route('stock-movements.store') }}" method="POST">
    @csrf
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-arrow-left-right me-2 text-primary"></i>Movement Details</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Inventory Item <span class="text-danger">*</span></label>
                            <select class="form-select @error('inventory_id') is-invalid @enderror" name="inventory_id" id="inventorySelect" required>
                                <option value="">Select Item</option>
                                @foreach($inventoryItems as $item)
                                    <option value="{{ $item->id }}" data-qty="{{ $item->quantity }}" data-cost="{{ $item->unit_cost }}" {{ old('inventory_id') == $item->id ? 'selected' : '' }}>
                                        {{ $item->sku }} - {{ $item->product_name }} (Qty: {{ $item->quantity }})
                                    </option>
                                @endforeach
                            </select>
                            @error('inventory_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Type <span class="text-danger">*</span></label>
                            <select class="form-select @error('type') is-invalid @enderror" name="type" required>
                                <option value="in" {{ old('type') == 'in' ? 'selected' : '' }}>Stock In</option>
                                <option value="out" {{ old('type') == 'out' ? 'selected' : '' }}>Stock Out</option>
                                <option value="transfer" {{ old('type') == 'transfer' ? 'selected' : '' }}>Transfer</option>
                                <option value="adjustment" {{ old('type') == 'adjustment' ? 'selected' : '' }}>Adjustment</option>
                            </select>
                            @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Quantity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('quantity') is-invalid @enderror" name="quantity" min="1" value="{{ old('quantity', 1) }}" required>
                            @error('quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Unit Cost ($)</label>
                            <input type="number" class="form-control" name="unit_cost" id="unitCost" step="0.01" value="{{ old('unit_cost', '0.00') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Total Value</label>
                            <input type="text" class="form-control" id="totalValue" readonly value="$0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">From Location</label>
                            <select class="form-select" name="from_warehouse_id">
                                <option value="">Select Warehouse</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" {{ old('from_warehouse_id') == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">To Location</label>
                            <select class="form-select" name="to_warehouse_id">
                                <option value="">Select Warehouse</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" {{ old('to_warehouse_id') == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Reference Type</label>
                            <select class="form-select" name="reference_type">
                                <option value="">None</option>
                                <option value="shipment" {{ old('reference_type') == 'shipment' ? 'selected' : '' }}>Shipment</option>
                                <option value="purchase_order" {{ old('reference_type') == 'purchase_order' ? 'selected' : '' }}>Purchase Order</option>
                                <option value="return" {{ old('reference_type') == 'return' ? 'selected' : '' }}>Return</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Reference ID</label>
                            <input type="text" class="form-control" name="reference_id" value="{{ old('reference_id') }}">
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
                <div class="card-body text-center">
                    <i class="bi bi-arrow-left-right text-primary" style="font-size: 3rem;"></i>
                    <h6 class="mt-3">Stock Movement</h6>
                    <p class="text-muted small mb-0">Record stock in, out, transfers, or adjustments.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('stock-movements.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle me-1"></i> Create Movement
        </button>
    </div>
</form>
@endsection

@push('scripts')
<script>
    $('#inventorySelect').on('change', function() {
        const option = $(this).find(':selected');
        const cost = option.data('cost') || 0;
        $('#unitCost').val(cost.toFixed(2));
        calculateTotal();
    });

    $('input[name="quantity"], #unitCost').on('input', function() {
        calculateTotal();
    });

    function calculateTotal() {
        const qty = parseInt($('input[name="quantity"]').val()) || 0;
        const cost = parseFloat($('#unitCost').val()) || 0;
        $('#totalValue').val('$' + (qty * cost).toFixed(2));
    }
</script>
@endpush

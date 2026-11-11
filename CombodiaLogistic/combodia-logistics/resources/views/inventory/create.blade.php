@extends('layouts.admin')

@section('title', 'Add Inventory Item')
@section('subtitle', 'Add a new inventory item')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Add Inventory Item</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}">Inventory</a></li>
                <li class="breadcrumb-item active">Add</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<form action="{{ route('inventory.store') }}" method="POST">
    @csrf
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-inboxes me-2 text-primary"></i>Product Information</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Product Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('product_name') is-invalid @enderror" name="product_name" value="{{ old('product_name') }}" required>
                            @error('product_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">SKU <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('sku') is-invalid @enderror" name="sku" value="{{ old('sku') }}" required>
                            @error('sku') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Barcode</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="barcode" id="barcodeInput" value="{{ old('barcode') }}">
                                <button type="button" class="btn btn-outline-secondary" onclick="generateBarcode()">
                                    <i class="bi bi-shuffle"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <input type="text" class="form-control" name="category" value="{{ old('category') }}" placeholder="e.g., Electronics, Clothing">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="2">{{ old('description') }}</textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Unit</label>
                            <select class="form-select" name="unit">
                                <option value="pcs">Pieces</option>
                                <option value="kg">Kilograms</option>
                                <option value="box">Box</option>
                                <option value="carton">Carton</option>
                                <option value="set">Set</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-warehouse me-2 text-success"></i>Location</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Warehouse <span class="text-danger">*</span></label>
                            <select class="form-select @error('warehouse_id') is-invalid @enderror" name="warehouse_id" required>
                                <option value="">Select Warehouse</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" {{ old('warehouse_id') == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                                @endforeach
                            </select>
                            @error('warehouse_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Shelf Location</label>
                            <input type="text" class="form-control" name="shelf" value="{{ old('shelf') }}" placeholder="e.g., A-12-3">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-calculator me-2 text-warning"></i>Stock Levels & Pricing</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Quantity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="quantity" value="{{ old('quantity', 0) }}" min="0" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Min Quantity</label>
                            <input type="number" class="form-control" name="min_quantity" value="{{ old('min_quantity', 0) }}" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Max Quantity</label>
                            <input type="number" class="form-control" name="max_quantity" value="{{ old('max_quantity') }}" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Reorder Point</label>
                            <input type="number" class="form-control" name="reorder_point" value="{{ old('reorder_point', 0) }}" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Unit Cost ($) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="unit_cost" step="0.01" value="{{ old('unit_cost', '0.00') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Selling Price ($) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="selling_price" step="0.01" value="{{ old('selling_price', '0.00') }}" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold">Summary</h6>
                </div>
                <div class="card-body text-center">
                    <div class="bg-light rounded p-4">
                        <i class="bi bi-inboxes text-primary" style="font-size: 3rem;"></i>
                        <p class="mt-2 mb-0 small text-muted">New inventory item will be added with <strong>0</strong> initial stock.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle me-1"></i> Add Item
        </button>
    </div>
</form>
@endsection

@push('scripts')
<script>
    function generateBarcode() {
        const code = 'SKU-' + Date.now().toString(36).toUpperCase().slice(-6);
        $('#barcodeInput').val(code);
    }
</script>
@endpush

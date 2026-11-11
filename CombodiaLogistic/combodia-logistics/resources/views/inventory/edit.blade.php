@extends('layouts.admin')

@section('title', 'Edit Inventory Item')
@section('subtitle', 'Edit ' . $inventory->product_name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Edit Inventory Item</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}">Inventory</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<form action="{{ route('inventory.update', $inventory) }}" method="POST">
    @csrf
    @method('PUT')
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
                            <input type="text" class="form-control" name="product_name" value="{{ old('product_name', $inventory->product_name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">SKU <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="sku" value="{{ old('sku', $inventory->sku) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Barcode</label>
                            <input type="text" class="form-control" name="barcode" value="{{ old('barcode', $inventory->barcode) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <input type="text" class="form-control" name="category" value="{{ old('category', $inventory->category) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="2">{{ old('description', $inventory->description) }}</textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Unit</label>
                            <select class="form-select" name="unit">
                                <option value="pcs" {{ old('unit', $inventory->unit) == 'pcs' ? 'selected' : '' }}>Pieces</option>
                                <option value="kg" {{ old('unit', $inventory->unit) == 'kg' ? 'selected' : '' }}>Kilograms</option>
                                <option value="box" {{ old('unit', $inventory->unit) == 'box' ? 'selected' : '' }}>Box</option>
                                <option value="carton" {{ old('unit', $inventory->unit) == 'carton' ? 'selected' : '' }}>Carton</option>
                                <option value="set" {{ old('unit', $inventory->unit) == 'set' ? 'selected' : '' }}>Set</option>
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
                            <select class="form-select" name="warehouse_id" required>
                                <option value="">Select Warehouse</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" {{ old('warehouse_id', $inventory->warehouse_id) == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Shelf Location</label>
                            <input type="text" class="form-control" name="shelf" value="{{ old('shelf', $inventory->shelf) }}">
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
                            <input type="number" class="form-control" name="quantity" value="{{ old('quantity', $inventory->quantity) }}" min="0" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Min Quantity</label>
                            <input type="number" class="form-control" name="min_quantity" value="{{ old('min_quantity', $inventory->min_quantity) }}" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Max Quantity</label>
                            <input type="number" class="form-control" name="max_quantity" value="{{ old('max_quantity', $inventory->max_quantity) }}" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Reorder Point</label>
                            <input type="number" class="form-control" name="reorder_point" value="{{ old('reorder_point', $inventory->reorder_point) }}" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Unit Cost ($)</label>
                            <input type="number" class="form-control" name="unit_cost" step="0.01" value="{{ old('unit_cost', $inventory->unit_cost) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Selling Price ($)</label>
                            <input type="number" class="form-control" name="selling_price" step="0.01" value="{{ old('selling_price', $inventory->selling_price) }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold">Current Status</h6>
                </div>
                <div class="card-body text-center">
                    @if($inventory->quantity <= 0)
                        <span class="badge bg-danger fs-6">Out of Stock</span>
                    @elseif($inventory->quantity <= $inventory->reorder_point)
                        <span class="badge bg-warning text-dark fs-6">Below Reorder Point</span>
                    @else
                        <span class="badge bg-success fs-6">In Stock</span>
                    @endif
                    <p class="mt-2 mb-0 small text-muted">Current quantity: <strong>{{ $inventory->quantity }}</strong></p>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle me-1"></i> Update Item
        </button>
    </div>
</form>
@endsection

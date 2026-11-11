@extends('layouts.admin')

@section('title', 'Edit Stock Movement')
@section('subtitle', 'Update stock movement')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Edit Stock Movement</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('stock-movements.index') }}">Stock Movements</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('stock-movements.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<form action="{{ route('stock-movements.update', $stockMovement) }}" method="POST">
    @csrf
    @method('PUT')
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
                            <select class="form-select @error('inventory_id') is-invalid @enderror" name="inventory_id" required>
                                <option value="">Select Item</option>
                                @foreach($inventories as $item)
                                    <option value="{{ $item->id }}" {{ old('inventory_id', $stockMovement->inventory_id) == $item->id ? 'selected' : '' }}>
                                        {{ $item->sku ?? '' }} - {{ $item->product_name ?? $item->name ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('inventory_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Type <span class="text-danger">*</span></label>
                            <select class="form-select @error('type') is-invalid @enderror" name="type" required>
                                <option value="in" {{ old('type', $stockMovement->type) == 'in' ? 'selected' : '' }}>Stock In</option>
                                <option value="out" {{ old('type', $stockMovement->type) == 'out' ? 'selected' : '' }}>Stock Out</option>
                                <option value="transfer" {{ old('type', $stockMovement->type) == 'transfer' ? 'selected' : '' }}>Transfer</option>
                            </select>
                            @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Quantity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('quantity') is-invalid @enderror" name="quantity" min="1" value="{{ old('quantity', $stockMovement->quantity) }}" required>
                            @error('quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Reference Number</label>
                            <input type="text" class="form-control" name="reference_number" value="{{ old('reference_number', $stockMovement->reference_number) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="2">{{ old('notes', $stockMovement->notes) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2"></i>Movement Info</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3 pb-3 border-bottom">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Current Inventory</label>
                        <p class="fw-medium mb-0">{{ $stockMovement->inventory->product_name ?? $stockMovement->inventory->name ?? '-' }}</p>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Current Quantity</label>
                        <p class="fw-medium mb-0">{{ $stockMovement->inventory->quantity ?? '-' }}</p>
                    </div>
                    <div class="mb-0">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Movement Date</label>
                        <p class="fw-medium mb-0">{{ $stockMovement->movement_date ? \Carbon\Carbon::parse($stockMovement->movement_date)->format('d M Y, h:i A') : $stockMovement->created_at->format('d M Y, h:i A') }}</p>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-1"></i> Update Movement
                </button>
                <a href="{{ route('stock-movements.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg me-1"></i> Cancel
                </a>
            </div>
        </div>
    </div>
</form>
@endsection

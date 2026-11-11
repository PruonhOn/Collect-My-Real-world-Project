@extends('layouts.admin')

@section('title', 'Edit Package')
@section('subtitle', 'Edit package #' . $package->package_number)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Edit Package</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('packages.index') }}">Packages</a></li>
                <li class="breadcrumb-item active">Edit #{{ $package->package_number }}</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('packages.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<form action="{{ route('packages.update', $package) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-box me-2 text-primary"></i>Package Details</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Shipment <span class="text-danger">*</span></label>
                            <select class="form-select @error('shipment_id') is-invalid @enderror" name="shipment_id" required>
                                <option value="">Select Shipment</option>
                                @foreach($shipments as $shipment)
                                    <option value="{{ $shipment->id }}" {{ old('shipment_id', $package->shipment_id) == $shipment->id ? 'selected' : '' }}>
                                        {{ $shipment->shipment_number }} - {{ $shipment->recipient_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('shipment_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Type <span class="text-danger">*</span></label>
                            <select class="form-select @error('type') is-invalid @enderror" name="type" required>
                                <option value="standard" {{ old('type', $package->type) == 'standard' ? 'selected' : '' }}>Standard</option>
                                <option value="fragile" {{ old('type', $package->type) == 'fragile' ? 'selected' : '' }}>Fragile</option>
                                <option value="oversized" {{ old('type', $package->type) == 'oversized' ? 'selected' : '' }}>Oversized</option>
                                <option value="hazardous" {{ old('type', $package->type) == 'hazardous' ? 'selected' : '' }}>Hazardous</option>
                                <option value="perishable" {{ old('type', $package->type) == 'perishable' ? 'selected' : '' }}>Perishable</option>
                            </select>
                            @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Weight (kg) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('weight') is-invalid @enderror" name="weight" step="0.01" value="{{ old('weight', $package->weight) }}" required>
                            @error('weight') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Volume (m³)</label>
                            <input type="number" class="form-control" name="volume" step="0.001" value="{{ old('volume', $package->volume) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="pending" {{ old('status', $package->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="in_warehouse" {{ old('status', $package->status) == 'in_warehouse' ? 'selected' : '' }}>In Warehouse</option>
                                <option value="in_transit" {{ old('status', $package->status) == 'in_transit' ? 'selected' : '' }}>In Transit</option>
                                <option value="delivered" {{ old('status', $package->status) == 'delivered' ? 'selected' : '' }}>Delivered</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Length (cm)</label>
                            <input type="number" class="form-control" name="length" step="0.01" value="{{ old('length', $package->length) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Width (cm)</label>
                            <input type="number" class="form-control" name="width" step="0.01" value="{{ old('width', $package->width) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Height (cm)</label>
                            <input type="number" class="form-control" name="height" step="0.01" value="{{ old('height', $package->height) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Warehouse</label>
                            <select class="form-select" name="warehouse_id">
                                <option value="">Select Warehouse</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" {{ old('warehouse_id', $package->warehouse_id) == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Shelf Location</label>
                            <input type="text" class="form-control" name="shelf" value="{{ old('shelf', $package->shelf) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Barcode</label>
                            <input type="text" class="form-control" name="barcode" value="{{ old('barcode', $package->barcode) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="2">{{ old('notes', $package->notes) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold">Current Barcode</h6>
                </div>
                <div class="card-body text-center">
                    <div class="bg-light rounded p-4">
                        <i class="bi bi-qr-code" style="font-size: 4rem; color: #333;"></i>
                        <p class="mt-2 mb-0 fw-semibold">{{ $package->barcode }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('packages.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle me-1"></i> Update Package
        </button>
    </div>
</form>
@endsection

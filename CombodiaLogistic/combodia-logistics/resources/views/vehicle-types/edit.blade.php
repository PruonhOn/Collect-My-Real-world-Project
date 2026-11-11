@extends('layouts.admin')

@section('title', 'Edit Vehicle Type')
@section('subtitle', 'Update vehicle type information')

@section('content')
<x-breadcrumb :items="[['label' => 'Vehicle Types', 'url' => route('vehicle-types.index')], ['label' => 'Edit Vehicle Type']]" />

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Edit Vehicle Type</h4>
        <p class="text-muted mb-0">Update details for {{ $vehicleType->name }}</p>
    </div>
    <a href="{{ route('vehicle-types.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to List
    </a>
</div>

<form action="{{ route('vehicle-types.update', $vehicleType->id) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-truck me-2"></i>Vehicle Type Information</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Type Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $vehicleType->name) }}" placeholder="e.g. Heavy Truck" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="max_weight" class="form-label">Max Weight (kg) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('max_weight') is-invalid @enderror" id="max_weight" name="max_weight" value="{{ old('max_weight', $vehicleType->max_weight) }}" placeholder="e.g. 10000" step="0.01" min="0" required>
                            @error('max_weight')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="max_volume" class="form-label">Max Volume (cbm) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('max_volume') is-invalid @enderror" id="max_volume" name="max_volume" value="{{ old('max_volume', $vehicleType->max_volume) }}" placeholder="e.g. 50" step="0.01" min="0" required>
                            @error('max_volume')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" placeholder="Describe this vehicle type...">{{ old('description', $vehicleType->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-gear me-2"></i>Status</h6>
                </div>
                <div class="card-body">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $vehicleType->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                    <small class="text-muted">Inactive types are hidden from selection</small>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> Update Vehicle Type
                </button>
                <a href="{{ route('vehicle-types.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg me-1"></i> Cancel
                </a>
            </div>
        </div>
    </div>
</form>
@endsection

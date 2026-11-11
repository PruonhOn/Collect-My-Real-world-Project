@extends('layouts.admin')

@section('title', 'Edit Shelf')
@section('subtitle', 'Update warehouse shelf information')

@section('content')
<x-breadcrumb :items="[['label' => 'Warehouse Shelves', 'url' => route('warehouse-shelves.index')], ['label' => 'Edit Shelf']]" />

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Edit Shelf</h4>
        <p class="text-muted mb-0">Update details for {{ $shelf->name }}</p>
    </div>
    <a href="{{ route('warehouse-shelves.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to List
    </a>
</div>

<form action="{{ route('warehouse-shelves.update', $shelf->id) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-grid me-2"></i>Shelf Information</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="warehouse_zone_id" class="form-label">Warehouse Zone <span class="text-danger">*</span></label>
                            <select class="form-select @error('warehouse_zone_id') is-invalid @enderror" id="warehouse_zone_id" name="warehouse_zone_id" required>
                                <option value="">Select Zone</option>
                                @foreach($zones as $zone)
                                    <option value="{{ $zone->id }}" {{ old('warehouse_zone_id', $shelf->warehouse_zone_id) == $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                                @endforeach
                            </select>
                            @error('warehouse_zone_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="code" class="form-label">Shelf Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $shelf->code) }}" required>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="name" class="form-label">Shelf Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $shelf->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="max_weight" class="form-label">Max Weight (kg) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control @error('max_weight') is-invalid @enderror" id="max_weight" name="max_weight" value="{{ old('max_weight', $shelf->max_weight) }}" min="0" required>
                            @error('max_weight')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="current_weight" class="form-label">Current Weight (kg)</label>
                            <input type="number" step="0.01" class="form-control @error('current_weight') is-invalid @enderror" id="current_weight" name="current_weight" value="{{ old('current_weight', $shelf->current_weight) }}" min="0">
                            @error('current_weight')
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
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $shelf->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> Update Shelf
                </button>
                <a href="{{ route('warehouse-shelves.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg me-1"></i> Cancel
                </a>
            </div>
        </div>
    </div>
</form>
@endsection

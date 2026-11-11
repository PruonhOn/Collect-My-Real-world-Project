@extends('layouts.admin')

@section('title', 'Edit Vehicle')
@section('subtitle', 'Update vehicle information')

@section('content')
<x-breadcrumb :items="[['label' => 'Vehicles', 'url' => route('admin.vehicles.index')], ['label' => 'Edit Vehicle']]" />

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Edit Vehicle</h4>
        <p class="text-muted mb-0">Update details for {{ $vehicle->code }}</p>
    </div>
    <a href="{{ route('admin.vehicles.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to List
    </a>
</div>

<form action="{{ route('admin.vehicles.update', $vehicle->id) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-car-front me-2"></i>Vehicle Information</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="code" class="form-label">Vehicle Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $vehicle->code) }}" required>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="plate_number" class="form-label">Plate Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('plate_number') is-invalid @enderror" id="plate_number" name="plate_number" value="{{ old('plate_number', $vehicle->plate_number) }}" required>
                            @error('plate_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="type" class="form-label">Vehicle Type <span class="text-danger">*</span></label>
                            <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                                <option value="">Select Type</option>
                                <option value="truck" {{ old('type', $vehicle->type) === 'truck' ? 'selected' : '' }}>Truck</option>
                                <option value="van" {{ old('type', $vehicle->type) === 'van' ? 'selected' : '' }}>Van</option>
                                <option value="pickup" {{ old('type', $vehicle->type) === 'pickup' ? 'selected' : '' }}>Pickup</option>
                                <option value="motorcycle" {{ old('type', $vehicle->type) === 'motorcycle' ? 'selected' : '' }}>Motorcycle</option>
                                <option value="trailer" {{ old('type', $vehicle->type) === 'trailer' ? 'selected' : '' }}>Trailer</option>
                                <option value="bus" {{ old('type', $vehicle->type) === 'bus' ? 'selected' : '' }}>Bus</option>
                                <option value="other" {{ old('type', $vehicle->type) === 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="brand" class="form-label">Brand <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('brand') is-invalid @enderror" id="brand" name="brand" value="{{ old('brand', $vehicle->brand) }}" required>
                            @error('brand')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="model" class="form-label">Model <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('model') is-invalid @enderror" id="model" name="model" value="{{ old('model', $vehicle->model) }}" required>
                            @error('model')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="year" class="form-label">Year</label>
                            <input type="number" class="form-control @error('year') is-invalid @enderror" id="year" name="year" value="{{ old('year', $vehicle->year) }}" min="1900" max="{{ date('Y') + 1 }}">
                            @error('year')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="color" class="form-label">Color</label>
                            <input type="text" class="form-control @error('color') is-invalid @enderror" id="color" name="color" value="{{ old('color', $vehicle->color) }}">
                            @error('color')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="fuel_type" class="form-label">Fuel Type</label>
                            <select class="form-select @error('fuel_type') is-invalid @enderror" id="fuel_type" name="fuel_type">
                                <option value="">Select Fuel Type</option>
                                <option value="diesel" {{ old('fuel_type', $vehicle->fuel_type) === 'diesel' ? 'selected' : '' }}>Diesel</option>
                                <option value="gasoline" {{ old('fuel_type', $vehicle->fuel_type) === 'gasoline' ? 'selected' : '' }}>Gasoline</option>
                                <option value="electric" {{ old('fuel_type', $vehicle->fuel_type) === 'electric' ? 'selected' : '' }}>Electric</option>
                                <option value="hybrid" {{ old('fuel_type', $vehicle->fuel_type) === 'hybrid' ? 'selected' : '' }}>Hybrid</option>
                                <option value="cng" {{ old('fuel_type', $vehicle->fuel_type) === 'cng' ? 'selected' : '' }}>CNG</option>
                            </select>
                            @error('fuel_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="branch_id" class="form-label">Branch <span class="text-danger">*</span></label>
                            <select class="form-select @error('branch_id') is-invalid @enderror" id="branch_id" name="branch_id" required>
                                <option value="">Select Branch</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id', $vehicle->branch_id) == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            @error('branch_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-speedometer me-2"></i>Capacity & Documents</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="capacity_weight" class="form-label">Weight Capacity (kg)</label>
                            <input type="number" class="form-control @error('capacity_weight') is-invalid @enderror" id="capacity_weight" name="capacity_weight" value="{{ old('capacity_weight', $vehicle->capacity_weight) }}" min="0">
                            @error('capacity_weight')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="capacity_volume" class="form-label">Volume Capacity (cbm)</label>
                            <input type="number" class="form-control @error('capacity_volume') is-invalid @enderror" id="capacity_volume" name="capacity_volume" value="{{ old('capacity_volume', $vehicle->capacity_volume) }}" step="0.01" min="0">
                            @error('capacity_volume')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
                                <option value="available" {{ old('status', $vehicle->status) === 'available' ? 'selected' : '' }}>Available</option>
                                <option value="in_use" {{ old('status', $vehicle->status) === 'in_use' ? 'selected' : '' }}>In Use</option>
                                <option value="maintenance" {{ old('status', $vehicle->status) === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                                <option value="inactive" {{ old('status', $vehicle->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="insurance_expiry" class="form-label">Insurance Expiry</label>
                            <input type="date" class="form-control @error('insurance_expiry') is-invalid @enderror" id="insurance_expiry" name="insurance_expiry" value="{{ old('insurance_expiry', $vehicle->insurance_expiry ? \Carbon\Carbon::parse($vehicle->insurance_expiry)->format('Y-m-d') : '') }}">
                            @error('insurance_expiry')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="registration_expiry" class="form-label">Registration Expiry</label>
                            <input type="date" class="form-control @error('registration_expiry') is-invalid @enderror" id="registration_expiry" name="registration_expiry" value="{{ old('registration_expiry', $vehicle->registration_expiry ? \Carbon\Carbon::parse($vehicle->registration_expiry)->format('Y-m-d') : '') }}">
                            @error('registration_expiry')
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
                    <h6 class="mb-0 fw-bold"><i class="bi bi-gear me-2"></i>Settings</h6>
                </div>
                <div class="card-body">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $vehicle->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> Update Vehicle
                </button>
                <a href="{{ route('admin.vehicles.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg me-1"></i> Cancel
                </a>
            </div>
        </div>
    </div>
</form>
@endsection

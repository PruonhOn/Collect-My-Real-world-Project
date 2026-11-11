@extends('layouts.admin')

@section('title', 'Create Vehicle')
@section('subtitle', 'Add a new vehicle to the fleet')

@section('content')
<x-breadcrumb :items="[['label' => 'Vehicles', 'url' => route('admin.vehicles.index')], ['label' => 'Create Vehicle']]" />

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Create Vehicle</h4>
        <p class="text-muted mb-0">Fill in the details to add a new vehicle</p>
    </div>
    <a href="{{ route('admin.vehicles.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to List
    </a>
</div>

<form action="{{ route('admin.vehicles.store') }}" method="POST">
    @csrf

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
                            <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code') }}" placeholder="e.g. VEH-001" required>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="plate_number" class="form-label">Plate Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('plate_number') is-invalid @enderror" id="plate_number" name="plate_number" value="{{ old('plate_number') }}" placeholder="e.g. 1A-2345" required>
                            @error('plate_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="type" class="form-label">Vehicle Type <span class="text-danger">*</span></label>
                            <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                                <option value="">Select Type</option>
                                <option value="truck" {{ old('type') === 'truck' ? 'selected' : '' }}>Truck</option>
                                <option value="van" {{ old('type') === 'van' ? 'selected' : '' }}>Van</option>
                                <option value="pickup" {{ old('type') === 'pickup' ? 'selected' : '' }}>Pickup</option>
                                <option value="motorcycle" {{ old('type') === 'motorcycle' ? 'selected' : '' }}>Motorcycle</option>
                                <option value="trailer" {{ old('type') === 'trailer' ? 'selected' : '' }}>Trailer</option>
                                <option value="bus" {{ old('type') === 'bus' ? 'selected' : '' }}>Bus</option>
                                <option value="other" {{ old('type') === 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="brand" class="form-label">Brand <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('brand') is-invalid @enderror" id="brand" name="brand" value="{{ old('brand') }}" placeholder="e.g. Toyota" required>
                            @error('brand')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="model" class="form-label">Model <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('model') is-invalid @enderror" id="model" name="model" value="{{ old('model') }}" placeholder="e.g. Hilux" required>
                            @error('model')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="year" class="form-label">Year</label>
                            <input type="number" class="form-control @error('year') is-invalid @enderror" id="year" name="year" value="{{ old('year') }}" placeholder="e.g. 2024" min="1900" max="{{ date('Y') + 1 }}">
                            @error('year')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="color" class="form-label">Color</label>
                            <input type="text" class="form-control @error('color') is-invalid @enderror" id="color" name="color" value="{{ old('color') }}" placeholder="e.g. White">
                            @error('color')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="fuel_type" class="form-label">Fuel Type</label>
                            <select class="form-select @error('fuel_type') is-invalid @enderror" id="fuel_type" name="fuel_type">
                                <option value="">Select Fuel Type</option>
                                <option value="diesel" {{ old('fuel_type') === 'diesel' ? 'selected' : '' }}>Diesel</option>
                                <option value="gasoline" {{ old('fuel_type') === 'gasoline' ? 'selected' : '' }}>Gasoline</option>
                                <option value="electric" {{ old('fuel_type') === 'electric' ? 'selected' : '' }}>Electric</option>
                                <option value="hybrid" {{ old('fuel_type') === 'hybrid' ? 'selected' : '' }}>Hybrid</option>
                                <option value="cng" {{ old('fuel_type') === 'cng' ? 'selected' : '' }}>CNG</option>
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
                                    <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
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
                            <input type="number" class="form-control @error('capacity_weight') is-invalid @enderror" id="capacity_weight" name="capacity_weight" value="{{ old('capacity_weight') }}" placeholder="e.g. 5000" min="0">
                            @error('capacity_weight')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="capacity_volume" class="form-label">Volume Capacity (cbm)</label>
                            <input type="number" class="form-control @error('capacity_volume') is-invalid @enderror" id="capacity_volume" name="capacity_volume" value="{{ old('capacity_volume') }}" placeholder="e.g. 20" step="0.01" min="0">
                            @error('capacity_volume')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
                                <option value="available" {{ old('status', 'available') === 'available' ? 'selected' : '' }}>Available</option>
                                <option value="in_use" {{ old('status') === 'in_use' ? 'selected' : '' }}>In Use</option>
                                <option value="maintenance" {{ old('status') === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="insurance_expiry" class="form-label">Insurance Expiry</label>
                            <input type="date" class="form-control @error('insurance_expiry') is-invalid @enderror" id="insurance_expiry" name="insurance_expiry" value="{{ old('insurance_expiry') }}">
                            @error('insurance_expiry')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="registration_expiry" class="form-label">Registration Expiry</label>
                            <input type="date" class="form-control @error('registration_expiry') is-invalid @enderror" id="registration_expiry" name="registration_expiry" value="{{ old('registration_expiry') }}">
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
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', '1') === '1' ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> Create Vehicle
                </button>
                <a href="{{ route('admin.vehicles.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg me-1"></i> Cancel
                </a>
            </div>
        </div>
    </div>
</form>
@endsection

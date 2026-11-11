@extends('layouts.admin')

@section('title', 'Create Warehouse')
@section('subtitle', 'Add a new warehouse facility')

@section('content')
<x-breadcrumb :items="[['label' => 'Warehouses', 'url' => route('admin.warehouses.index')], ['label' => 'Create Warehouse']]" />

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Create Warehouse</h4>
        <p class="text-muted mb-0">Fill in the details to add a new warehouse</p>
    </div>
    <a href="{{ route('admin.warehouses.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to List
    </a>
</div>

<form action="{{ route('admin.warehouses.store') }}" method="POST">
    @csrf

    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-warehouse me-2"></i>Warehouse Information</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="code" class="form-label">Warehouse Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code') }}" placeholder="e.g. WH-001" required>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="name" class="form-label">Warehouse Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="Enter warehouse name" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
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
                        <div class="col-md-6">
                            <label for="capacity" class="form-label">Capacity (sqm) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('capacity') is-invalid @enderror" id="capacity" name="capacity" value="{{ old('capacity') }}" placeholder="Total capacity in sqm" min="0" required>
                            @error('capacity')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="2" placeholder="Enter warehouse address">{{ old('address') }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="city" class="form-label">City</label>
                            <input type="text" class="form-control @error('city') is-invalid @enderror" id="city" name="city" value="{{ old('city') }}" placeholder="Enter city">
                            @error('city')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-person me-2"></i>Manager Information</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="manager_name" class="form-label">Manager Name</label>
                            <input type="text" class="form-control @error('manager_name') is-invalid @enderror" id="manager_name" name="manager_name" value="{{ old('manager_name') }}" placeholder="Enter manager name">
                            @error('manager_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="manager_phone" class="form-label">Manager Phone</label>
                            <input type="text" class="form-control @error('manager_phone') is-invalid @enderror" id="manager_phone" name="manager_phone" value="{{ old('manager_phone') }}" placeholder="Enter manager phone">
                            @error('manager_phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="manager_email" class="form-label">Manager Email</label>
                            <input type="email" class="form-control @error('manager_email') is-invalid @enderror" id="manager_email" name="manager_email" value="{{ old('manager_email') }}" placeholder="Enter manager email">
                            @error('manager_email')
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
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', '1') === '1' ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                    <small class="text-muted">Inactive warehouses are hidden from selection</small>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> Create Warehouse
                </button>
                <a href="{{ route('admin.warehouses.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg me-1"></i> Cancel
                </a>
            </div>
        </div>
    </div>
</form>
@endsection

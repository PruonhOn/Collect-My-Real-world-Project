@extends('layouts.admin')

@section('title', 'Edit Supplier')
@section('subtitle', 'Update supplier information')

@section('content')
<x-breadcrumb :items="[['label' => 'Suppliers', 'url' => route('admin.suppliers.index')], ['label' => 'Edit Supplier']]" />

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Edit Supplier</h4>
        <p class="text-muted mb-0">Update details for {{ $supplier->name }}</p>
    </div>
    <a href="{{ route('admin.suppliers.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to List
    </a>
</div>

<form action="{{ route('admin.suppliers.update', $supplier->id) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-truck me-2"></i>Supplier Information</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="code" class="form-label">Supplier Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $supplier->code) }}" required>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="name" class="form-label">Contact Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $supplier->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="company_name" class="form-label">Company Name</label>
                            <input type="text" class="form-control @error('company_name') is-invalid @enderror" id="company_name" name="company_name" value="{{ old('company_name', $supplier->company_name) }}">
                            @error('company_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $supplier->email) }}">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $supplier->phone) }}" required>
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="2">{{ old('address', $supplier->address) }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="city" class="form-label">City</label>
                            <input type="text" class="form-control @error('city') is-invalid @enderror" id="city" name="city" value="{{ old('city', $supplier->city) }}">
                            @error('city')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="tax_number" class="form-label">Tax Number</label>
                            <input type="text" class="form-control @error('tax_number') is-invalid @enderror" id="tax_number" name="tax_number" value="{{ old('tax_number', $supplier->tax_number) }}">
                            @error('tax_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="payment_terms" class="form-label">Payment Terms</label>
                            <select class="form-select @error('payment_terms') is-invalid @enderror" id="payment_terms" name="payment_terms">
                                <option value="">Select Terms</option>
                                <option value="net_15" {{ old('payment_terms', $supplier->payment_terms) === 'net_15' ? 'selected' : '' }}>Net 15</option>
                                <option value="net_30" {{ old('payment_terms', $supplier->payment_terms) === 'net_30' ? 'selected' : '' }}>Net 30</option>
                                <option value="net_45" {{ old('payment_terms', $supplier->payment_terms) === 'net_45' ? 'selected' : '' }}>Net 45</option>
                                <option value="net_60" {{ old('payment_terms', $supplier->payment_terms) === 'net_60' ? 'selected' : '' }}>Net 60</option>
                                <option value="cod" {{ old('payment_terms', $supplier->payment_terms) === 'cod' ? 'selected' : '' }}>Cash on Delivery</option>
                                <option value="prepaid" {{ old('payment_terms', $supplier->payment_terms) === 'prepaid' ? 'selected' : '' }}>Prepaid</option>
                            </select>
                            @error('payment_terms')
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
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $supplier->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> Update Supplier
                </button>
                <a href="{{ route('admin.suppliers.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg me-1"></i> Cancel
                </a>
            </div>
        </div>
    </div>
</form>
@endsection

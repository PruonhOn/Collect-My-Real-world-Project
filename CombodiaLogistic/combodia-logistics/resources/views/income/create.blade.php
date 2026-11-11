@extends('layouts.admin')

@section('title', 'Record Income')
@section('subtitle', 'Record a new income')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Record Income</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('income.index') }}">Income</a></li>
                <li class="breadcrumb-item active">Record</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('income.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<form action="{{ route('income.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-graph-up-arrow me-2 text-primary"></i>Income Details</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select @error('category') is-invalid @enderror" name="category" required>
                                <option value="">Select Category</option>
                                <option value="freight_charges" {{ old('category') == 'freight_charges' ? 'selected' : '' }}>Freight Charges</option>
                                <option value="delivery_fees" {{ old('category') == 'delivery_fees' ? 'selected' : '' }}>Delivery Fees</option>
                                <option value="warehousing" {{ old('category') == 'warehousing' ? 'selected' : '' }}>Warehousing</option>
                                <option value="packaging" {{ old('category') == 'packaging' ? 'selected' : '' }}>Packaging</option>
                                <option value="insurance" {{ old('category') == 'insurance' ? 'selected' : '' }}>Insurance</option>
                                <option value="other" {{ old('category') == 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Customer (Optional)</label>
                            <select class="form-select" name="customer_id">
                                <option value="">Select Customer</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Branch</label>
                            <select class="form-select" name="branch_id">
                                <option value="">Select Branch</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Amount ($) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('amount') is-invalid @enderror" name="amount" step="0.01" value="{{ old('amount') }}" required>
                            @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="2" required>{{ old('description') }}</textarea>
                            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tax ($)</label>
                            <input type="number" class="form-control" name="tax" step="0.01" value="{{ old('tax', '0.00') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Income Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="income_date" value="{{ old('income_date', date('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Reference Number</label>
                            <input type="text" class="form-control" name="reference_number" value="{{ old('reference_number') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Receipt</label>
                            <input type="file" class="form-control" name="receipt" accept="image/*,.pdf">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="2">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body text-center">
                    <i class="bi bi-graph-up-arrow text-success" style="font-size: 3rem;"></i>
                    <h6 class="mt-3">Record Income</h6>
                    <p class="text-muted small mb-0">Track revenue from various sources.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('income.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle me-1"></i> Save Income
        </button>
    </div>
</form>
@endsection

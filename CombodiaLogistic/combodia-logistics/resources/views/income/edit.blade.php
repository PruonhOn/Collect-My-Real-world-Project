@extends('layouts.admin')

@section('title', 'Edit Income')
@section('subtitle', 'Update income record')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Edit Income</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('income.index') }}">Income</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('income.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<form action="{{ route('income.update', $income) }}" method="POST">
    @csrf
    @method('PUT')
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
                            <select class="form-select @error('income_category_id') is-invalid @enderror" name="income_category_id" required>
                                <option value="">Select Category</option>
                                @foreach($incomeCategories as $category)
                                    <option value="{{ $category->id }}" {{ old('income_category_id', $income->income_category_id) == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                @endforeach
                            </select>
                            @error('income_category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Amount ($) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('amount') is-invalid @enderror" name="amount" step="0.01" value="{{ old('amount', $income->amount) }}" required>
                            @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="2" required>{{ old('description', $income->description) }}</textarea>
                            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Income Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="income_date" value="{{ old('income_date', $income->income_date ? \Carbon\Carbon::parse($income->income_date)->format('Y-m-d') : '') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Reference Number</label>
                            <input type="text" class="form-control" name="reference_number" value="{{ old('reference_number', $income->reference_number) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="2">{{ old('notes', $income->notes) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-gear me-2"></i>Status</h6>
                </div>
                <div class="card-body">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select class="form-select @error('status') is-invalid @enderror" name="status" required>
                        <option value="received" {{ old('status', $income->status) == 'received' ? 'selected' : '' }}>Received</option>
                        <option value="pending" {{ old('status', $income->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="cancelled" {{ old('status', $income->status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                    @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2"></i>Income Info</h6>
                </div>
                <div class="card-body">
                    <div class="mb-0">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Created</label>
                        <p class="fw-medium mb-0">{{ $income->created_at->format('d M Y, h:i A') }}</p>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-1"></i> Update Income
                </button>
                <a href="{{ route('income.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg me-1"></i> Cancel
                </a>
            </div>
        </div>
    </div>
</form>
@endsection

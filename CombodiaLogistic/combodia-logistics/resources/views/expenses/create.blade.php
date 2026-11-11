@extends('layouts.admin')

@section('title', 'Add Expense')
@section('subtitle', 'Record a new expense')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Add Expense</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('expenses.index') }}">Expenses</a></li>
                <li class="breadcrumb-item active">Add</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<form action="{{ route('expenses.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-cash-stack me-2 text-primary"></i>Expense Details</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select @error('category') is-invalid @enderror" name="category" required>
                                <option value="">Select Category</option>
                                <option value="fuel" {{ old('category') == 'fuel' ? 'selected' : '' }}>Fuel</option>
                                <option value="maintenance" {{ old('category') == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                                <option value="salaries" {{ old('category') == 'salaries' ? 'selected' : '' }}>Salaries</option>
                                <option value="rent" {{ old('category') == 'rent' ? 'selected' : '' }}>Rent</option>
                                <option value="utilities" {{ old('category') == 'utilities' ? 'selected' : '' }}>Utilities</option>
                                <option value="office" {{ old('category') == 'office' ? 'selected' : '' }}>Office Supplies</option>
                                <option value="insurance" {{ old('category') == 'insurance' ? 'selected' : '' }}>Insurance</option>
                                <option value="taxes" {{ old('category') == 'taxes' ? 'selected' : '' }}>Taxes</option>
                                <option value="other" {{ old('category') == 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
                        <div class="col-12">
                            <label class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="2" required>{{ old('description') }}</textarea>
                            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Amount ($) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('amount') is-invalid @enderror" name="amount" step="0.01" value="{{ old('amount') }}" required>
                            @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tax Amount ($)</label>
                            <input type="number" class="form-control" name="tax_amount" step="0.01" value="{{ old('tax_amount', '0.00') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Expense Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="expense_date" value="{{ old('expense_date', date('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Payment Method</label>
                            <select class="form-select" name="payment_method">
                                <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>Cash</option>
                                <option value="bank_transfer" {{ old('payment_method') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                <option value="credit_card" {{ old('payment_method') == 'credit_card' ? 'selected' : '' }}>Credit Card</option>
                                <option value="check" {{ old('payment_method') == 'check' ? 'selected' : '' }}>Check</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Reference Number</label>
                            <input type="text" class="form-control" name="reference_number" value="{{ old('reference_number') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Receipt Upload</label>
                            <input type="file" class="form-control" name="receipt" accept="image/*,.pdf">
                            <small class="text-muted">Accepted: JPG, PNG, PDF (Max 5MB)</small>
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
                    <i class="bi bi-cash-stack text-primary" style="font-size: 3rem;"></i>
                    <h6 class="mt-3">Expense Summary</h6>
                    <p class="text-muted small mb-0">Submit for approval after recording.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle me-1"></i> Save Expense
        </button>
    </div>
</form>
@endsection

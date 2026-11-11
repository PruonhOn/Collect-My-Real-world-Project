@extends('layouts.app')

@section('title', 'Add Expense')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="fas fa-plus-circle me-2"></i>Add Expense</h4>
    <a href="{{ route('expenses.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i>Back
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('expenses.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Description <span class="text-danger">*</span></label>
                    <input type="text" name="description" class="form-control @error('description') is-invalid @enderror"
                        value="{{ old('description') }}" required>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Amount <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" step="0.01" min="0" name="amount" class="form-control @error('amount') is-invalid @enderror"
                            value="{{ old('amount') }}" required>
                        @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date <span class="text-danger">*</span></label>
                    <input type="date" name="date" class="form-control @error('date') is-invalid @enderror"
                        value="{{ old('date', date('Y-m-d')) }}" required>
                    @error('date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Save Expense
                    </button>
                    <a href="{{ route('expenses.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@extends('layouts.admin')

@section('title', 'Edit Expense - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('expenses.index') }}">Expenses</a></li>
    <li class="breadcrumb-item active" aria-current="page">Edit #{{ $expense->expense_number ?? $expense->id }}</li>
@endsection

@section('page-title', 'Edit Expense')
@section('page-subtitle', $expense->expense_number ?? 'Expense #' . $expense->id)

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('expenses.update', $expense) }}" method="POST" enctype="multipart/form-data">
                        @csrf @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Expense Date <span class="text-danger">*</span></label>
                                <input type="date" name="expense_date" class="form-control @error('expense_date') is-invalid @enderror"
                                       value="{{ old('expense_date', $expense->expense_date?->format('Y-m-d')) }}">
                                @error('expense_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Category <span class="text-danger">*</span></label>
                                <select name="category" class="form-select @error('category') is-invalid @enderror">
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat }}" {{ old('category', $expense->category) == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                    @endforeach
                                </select>
                                @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Amount <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" min="0" name="amount"
                                           class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount', $expense->amount) }}">
                                    @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Vendor</label>
                                <input type="text" name="vendor" class="form-control" value="{{ old('vendor', $expense->vendor) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Branch</label>
                                <select name="branch_id" class="form-select">
                                    <option value="">All Branches</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ old('branch_id', $expense->branch_id) == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Vehicle</label>
                                <select name="vehicle_id" class="form-select">
                                    <option value="">Select Vehicle</option>
                                    @foreach($vehicles as $vehicle)
                                        <option value="{{ $vehicle->id }}" {{ old('vehicle_id', $expense->vehicle_id) == $vehicle->id ? 'selected' : '' }}>
                                            {{ $vehicle->plate_number }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select">
                                    <option value="pending" {{ old('status', $expense->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="approved" {{ old('status', $expense->status) == 'approved' ? 'selected' : '' }}>Approved</option>
                                    <option value="rejected" {{ old('status', $expense->status) == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mt-4 pt-2">
                                    <input type="checkbox" name="is_recurring" value="1" class="form-check-input" id="isRecurring"
                                        {{ old('is_recurring', $expense->is_recurring) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="isRecurring">Recurring Expense</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" rows="3" class="form-control">{{ old('description', $expense->description) }}</textarea>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Update Expense</button>
                            <a href="{{ route('expenses.show', $expense) }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

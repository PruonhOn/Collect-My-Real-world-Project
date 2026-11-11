@extends('layouts.admin')

@section('title', 'Edit Expense')
@section('subtitle', 'Edit expense #' . $expense->expense_number)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Edit Expense</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('expenses.index') }}">Expenses</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

@if($expense->status !== 'pending')
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle me-2"></i> Only pending expenses can be edited.
</div>
@endif

<form action="{{ route('expenses.update', $expense) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
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
                            <select class="form-select" name="category" required {{ $expense->status !== 'pending' ? 'disabled' : '' }}>
                                <option value="fuel" {{ old('category', $expense->category) == 'fuel' ? 'selected' : '' }}>Fuel</option>
                                <option value="maintenance" {{ old('category', $expense->category) == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                                <option value="salaries" {{ old('category', $expense->category) == 'salaries' ? 'selected' : '' }}>Salaries</option>
                                <option value="rent" {{ old('category', $expense->category) == 'rent' ? 'selected' : '' }}>Rent</option>
                                <option value="utilities" {{ old('category', $expense->category) == 'utilities' ? 'selected' : '' }}>Utilities</option>
                                <option value="office" {{ old('category', $expense->category) == 'office' ? 'selected' : '' }}>Office Supplies</option>
                                <option value="insurance" {{ old('category', $expense->category) == 'insurance' ? 'selected' : '' }}>Insurance</option>
                                <option value="taxes" {{ old('category', $expense->category) == 'taxes' ? 'selected' : '' }}>Taxes</option>
                                <option value="other" {{ old('category', $expense->category) == 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Branch</label>
                            <select class="form-select" name="branch_id" {{ $expense->status !== 'pending' ? 'disabled' : '' }}>
                                <option value="">Select Branch</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id', $expense->branch_id) == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="2" {{ $expense->status !== 'pending' ? 'disabled' : '' }}>{{ old('description', $expense->description) }}</textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Amount ($)</label>
                            <input type="number" class="form-control" name="amount" step="0.01" value="{{ old('amount', $expense->amount) }}" {{ $expense->status !== 'pending' ? 'disabled' : '' }}>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tax Amount ($)</label>
                            <input type="number" class="form-control" name="tax_amount" step="0.01" value="{{ old('tax_amount', $expense->tax_amount) }}" {{ $expense->status !== 'pending' ? 'disabled' : '' }}>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Expense Date</label>
                            <input type="date" class="form-control" name="expense_date" value="{{ old('expense_date', $expense->expense_date ? \Carbon\Carbon::parse($expense->expense_date)->format('Y-m-d') : '') }}" {{ $expense->status !== 'pending' ? 'disabled' : '' }}>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Payment Method</label>
                            <select class="form-select" name="payment_method" {{ $expense->status !== 'pending' ? 'disabled' : '' }}>
                                <option value="cash" {{ old('payment_method', $expense->payment_method) == 'cash' ? 'selected' : '' }}>Cash</option>
                                <option value="bank_transfer" {{ old('payment_method', $expense->payment_method) == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                <option value="credit_card" {{ old('payment_method', $expense->payment_method) == 'credit_card' ? 'selected' : '' }}>Credit Card</option>
                                <option value="check" {{ old('payment_method', $expense->payment_method) == 'check' ? 'selected' : '' }}>Check</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Reference Number</label>
                            <input type="text" class="form-control" name="reference_number" value="{{ old('reference_number', $expense->reference_number) }}" {{ $expense->status !== 'pending' ? 'disabled' : '' }}>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Receipt Upload</label>
                            <input type="file" class="form-control" name="receipt" accept="image/*,.pdf" {{ $expense->status !== 'pending' ? 'disabled' : '' }}>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="2" {{ $expense->status !== 'pending' ? 'disabled' : '' }}>{{ old('notes', $expense->notes) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold">Status</h6>
                </div>
                <div class="card-body text-center">
                    @switch($expense->status)
                        @case('pending')
                            <span class="badge bg-warning text-dark fs-6">Pending</span>
                            @break
                        @case('approved')
                            <span class="badge bg-success fs-6">Approved</span>
                            @break
                        @case('rejected')
                            <span class="badge bg-danger fs-6">Rejected</span>
                            @break
                    @endswitch
                    <p class="mt-2 mb-0 small text-muted">Created: {{ $expense->created_at->format('d M Y') }}</p>
                </div>
            </div>

            <div class="d-grid gap-2">
                @if($expense->status === 'pending')
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-1"></i> Update Expense
                </button>
                <a href="{{ route('expenses.approve', $expense) }}" class="btn btn-success" onclick="event.preventDefault(); document.getElementById('approveForm').submit();">
                    <i class="bi bi-check2-all me-1"></i> Approve
                </a>
                <form id="approveForm" action="{{ route('expenses.approve', $expense) }}" method="POST" class="d-none">@csrf</form>
                <a href="{{ route('expenses.reject', $expense) }}" class="btn btn-danger" onclick="event.preventDefault(); document.getElementById('rejectForm').submit();">
                    <i class="bi bi-x-circle me-1"></i> Reject
                </a>
                <form id="rejectForm" action="{{ route('expenses.reject', $expense) }}" method="POST" class="d-none">@csrf</form>
                @endif
            </div>
        </div>
    </div>
</form>
@endsection

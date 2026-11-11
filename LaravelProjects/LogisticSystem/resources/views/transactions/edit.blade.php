@extends('layouts.admin')

@section('title', 'Edit Transaction - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('transactions.index') }}">Transactions</a></li>
    <li class="breadcrumb-item active" aria-current="page">Edit #{{ $transaction->transaction_number ?? $transaction->id }}</li>
@endsection

@section('page-title', 'Edit Transaction')
@section('page-subtitle', $transaction->transaction_number ?? 'Transaction #' . $transaction->id)

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('transactions.update', $transaction) }}" method="POST">
                        @csrf @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="date" name="transaction_date" class="form-control"
                                       value="{{ old('transaction_date', $transaction->transaction_date?->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Type <span class="text-danger">*</span></label>
                                <select name="type" class="form-select">
                                    @foreach($types as $type)
                                        <option value="{{ $type }}" {{ old('type', $transaction->type) == $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Category <span class="text-danger">*</span></label>
                                <select name="category" class="form-select">
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat }}" {{ old('category', $transaction->category) == $cat ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $cat)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Amount <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" min="0" name="amount" class="form-control"
                                           value="{{ old('amount', $transaction->amount) }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Payment Method</label>
                                <select name="payment_method" class="form-select">
                                    <option value="">Select</option>
                                    @foreach(['cash', 'bank_transfer', 'credit_card', 'check', 'mobile_money'] as $method)
                                        <option value="{{ $method }}" {{ old('payment_method', $transaction->payment_method) == $method ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $method)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select">
                                    <option value="pending" {{ old('status', $transaction->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="completed" {{ old('status', $transaction->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                                    <option value="failed" {{ old('status', $transaction->status) == 'failed' ? 'selected' : '' }}>Failed</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Branch</label>
                                <select name="branch_id" class="form-select">
                                    <option value="">All Branches</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ old('branch_id', $transaction->branch_id) == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Reference</label>
                                <input type="text" name="reference" class="form-control" value="{{ old('reference', $transaction->reference) }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" rows="3" class="form-control">{{ old('description', $transaction->description) }}</textarea>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Update Transaction</button>
                            <a href="{{ route('transactions.show', $transaction) }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

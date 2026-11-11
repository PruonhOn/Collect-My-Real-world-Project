@extends('layouts.admin')

@section('title', $expense->expense_number . ' - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('expenses.index') }}">Expenses</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $expense->expense_number ?? $expense->id }}</li>
@endsection

@section('page-title', 'Expense ' . ($expense->expense_number ?? ''))
@section('page-subtitle', $expense->category ? ucwords(str_replace('_', ' ', $expense->category)) : '')

@section('page-actions')
    <a href="{{ route('expenses.edit', $expense) }}" class="btn btn-primary btn-sm">
        <i class="bi bi-pencil"></i> Edit
    </a>
    <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Back
    </a>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0 fw-bold">Expense Details</h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5 text-muted small">Expense #</dt>
                        <dd class="col-sm-7"><span class="badge bg-secondary">{{ $expense->expense_number ?? $expense->id }}</span></dd>

                        <dt class="col-sm-5 text-muted small">Category</dt>
                        <dd class="col-sm-7">{{ ucwords(str_replace('_', ' ', $expense->category ?? '')) }}</dd>

                        <dt class="col-sm-5 text-muted small">Amount</dt>
                        <dd class="col-sm-7 fw-bold fs-5">${{ number_format($expense->amount ?? 0, 2) }}</dd>

                        <dt class="col-sm-5 text-muted small">Date</dt>
                        <dd class="col-sm-7">{{ $expense->expense_date?->format('d M Y') ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted small">Vendor</dt>
                        <dd class="col-sm-7">{{ $expense->vendor ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted small">Status</dt>
                        <dd class="col-sm-7">
                            @php
                                $esc = ['approved' => 'bg-success', 'pending' => 'bg-warning text-dark', 'rejected' => 'bg-danger'];
                            @endphp
                            <span class="badge {{ $esc[$expense->status] ?? 'bg-secondary' }}">{{ ucfirst($expense->status ?? 'pending') }}</span>
                        </dd>

                        <dt class="col-sm-5 text-muted small">Recurring</dt>
                        <dd class="col-sm-7">{{ $expense->is_recurring ? 'Yes' : 'No' }}</dd>

                        <dt class="col-sm-5 text-muted small">Branch</dt>
                        <dd class="col-sm-7">{{ $expense->branch?->name ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted small">Vehicle</dt>
                        <dd class="col-sm-7">{{ $expense->vehicle?->plate_number ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted small">Created By</dt>
                        <dd class="col-sm-7">{{ $expense->createdBy?->name ?? '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0 fw-bold">Description</h6>
                </div>
                <div class="card-body">
                    @if($expense->description)
                        <p class="mb-0">{{ $expense->description }}</p>
                    @else
                        <p class="text-muted mb-0">No description provided</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

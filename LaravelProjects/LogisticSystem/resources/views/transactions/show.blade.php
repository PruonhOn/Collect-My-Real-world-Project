@extends('layouts.admin')

@section('title', $transaction->transaction_number . ' - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('transactions.index') }}">Transactions</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $transaction->transaction_number ?? $transaction->id }}</li>
@endsection

@section('page-title', 'Transaction ' . ($transaction->transaction_number ?? ''))
@section('page-subtitle', ucfirst($transaction->type ?? '') . ' - ' . ucwords(str_replace('_', ' ', $transaction->category ?? '')))

@section('page-actions')
    <a href="{{ route('transactions.edit', $transaction) }}" class="btn btn-primary btn-sm"><i class="bi bi-pencil"></i> Edit</a>
    <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h6 class="card-title mb-0 fw-bold">Transaction Details</h6></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5 text-muted small">#</dt>
                        <dd class="col-sm-7"><span class="badge bg-secondary">{{ $transaction->transaction_number ?? $transaction->id }}</span></dd>
                        <dt class="col-sm-5 text-muted small">Type</dt>
                        <dd class="col-sm-7">
                            <span class="badge bg-{{ $transaction->type == 'income' ? 'success' : ($transaction->type == 'expense' ? 'danger' : 'info') }}">
                                {{ ucfirst($transaction->type ?? '') }}
                            </span>
                        </dd>
                        <dt class="col-sm-5 text-muted small">Category</dt>
                        <dd class="col-sm-7">{{ ucwords(str_replace('_', ' ', $transaction->category ?? '')) }}</dd>
                        <dt class="col-sm-5 text-muted small">Amount</dt>
                        <dd class="col-sm-7 fw-bold fs-5">${{ number_format($transaction->amount ?? 0, 2) }}</dd>
                        <dt class="col-sm-5 text-muted small">Date</dt>
                        <dd class="col-sm-7">{{ $transaction->transaction_date?->format('d M Y') ?? '—' }}</dd>
                        <dt class="col-sm-5 text-muted small">Payment Method</dt>
                        <dd class="col-sm-7">{{ ucwords(str_replace('_', ' ', $transaction->payment_method ?? '—')) }}</dd>
                        <dt class="col-sm-5 text-muted small">Status</dt>
                        <dd class="col-sm-7">
                            @php $tsc = ['completed' => 'bg-success', 'pending' => 'bg-warning text-dark', 'failed' => 'bg-danger']; @endphp
                            <span class="badge {{ $tsc[$transaction->status] ?? 'bg-secondary' }}">{{ ucfirst($transaction->status ?? 'pending') }}</span>
                        </dd>
                        <dt class="col-sm-5 text-muted small">Reference</dt>
                        <dd class="col-sm-7">{{ $transaction->reference ?? '—' }}</dd>
                        <dt class="col-sm-5 text-muted small">Branch</dt>
                        <dd class="col-sm-7">{{ $transaction->branch?->name ?? '—' }}</dd>
                        <dt class="col-sm-5 text-muted small">Created By</dt>
                        <dd class="col-sm-7">{{ $transaction->createdBy?->name ?? '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h6 class="card-title mb-0 fw-bold">Description</h6></div>
                <div class="card-body">
                    @if($transaction->description)
                        <p class="mb-0">{{ $transaction->description }}</p>
                    @else
                        <p class="text-muted mb-0">No description provided</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

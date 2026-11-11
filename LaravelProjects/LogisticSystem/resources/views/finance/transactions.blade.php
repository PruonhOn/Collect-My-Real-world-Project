@extends('layouts.admin')

@section('title', 'All Transactions - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('finance.index') }}">Finance</a></li>
    <li class="breadcrumb-item active" aria-current="page">Transactions</li>
@endsection

@section('page-title', 'All Transactions')
@section('page-subtitle', 'Complete transaction history')

@section('page-actions')
    <a href="{{ route('transactions.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> New Transaction
    </a>
@endsection

@section('content')
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('transactions.index') }}" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label for="type" class="form-label">Type</label>
                    <select id="type" name="type" class="form-select">
                        <option value="">All Types</option>
                        <option value="income" {{ request('type') == 'income' ? 'selected' : '' }}>Income</option>
                        <option value="expense" {{ request('type') == 'expense' ? 'selected' : '' }}>Expense</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="category" class="form-label">Category</label>
                    <select id="category" name="category" class="form-select">
                        <option value="">All Categories</option>
                        @foreach($categories ?? [] as $cat)
                            <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="from_date" class="form-label">From Date</label>
                    <input type="date" id="from_date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                </div>
                <div class="col-md-2">
                    <label for="to_date" class="form-label">To Date</label>
                    <input type="date" id="to_date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                    <a href="{{ route('transactions.index') }}" class="btn btn-secondary">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="table-container">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="transactionsTable">
                <thead class="table-light">
                    <tr>
                        <th>Transaction #</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Payment Method</th>
                        <th class="text-end">Amount</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        <tr>
                            <td><span class="badge bg-secondary">{{ $transaction->transaction_number }}</span></td>
                            <td>{{ $transaction->date->format('d M Y') }}</td>
                            <td>
                                @if($transaction->type == 'income')
                                    <span class="badge bg-success">Income</span>
                                @else
                                    <span class="badge bg-danger">Expense</span>
                                @endif
                            </td>
                            <td>{{ $transaction->category }}</td>
                            <td>{{ Str::limit($transaction->description, 40) }}</td>
                            <td><small class="text-muted">{{ $transaction->payment_method ?? '—' }}</small></td>
                            <td class="text-end fw-medium {{ $transaction->type == 'income' ? 'text-success' : 'text-danger' }}">
                                {{ $transaction->type == 'income' ? '+' : '-' }}${{ number_format($transaction->amount, 2) }}
                            </td>
                            <td>
                                @php
                                    $statusClass = match($transaction->status) {
                                        'completed' => 'success',
                                        'pending' => 'warning',
                                        'cancelled' => 'danger',
                                        default => 'secondary'
                                    };
                                @endphp
                                <span class="badge bg-{{ $statusClass }}">{{ ucfirst($transaction->status) }}</span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('transactions.show', $transaction) }}" class="btn btn-outline-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('transactions.edit', $transaction) }}" class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger" title="Delete"
                                            onclick="confirmDelete({{ $transaction->id }})">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                                <form id="delete-form-{{ $transaction->id }}" action="{{ route('transactions.destroy', $transaction) }}" method="POST" class="d-none">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-cash-stack fs-1 d-block mb-2"></i>
                                <p class="mb-0">No transactions found</p>
                                <a href="{{ route('transactions.create') }}" class="btn btn-sm btn-primary mt-2">Create Transaction</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($transactions, 'links'))
            <div class="d-flex justify-content-end mt-2">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>
@endsection

@section('scripts')
    <script>
        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this transaction?')) {
                document.getElementById('delete-form-' + id).submit();
            }
        }
    </script>
@endsection

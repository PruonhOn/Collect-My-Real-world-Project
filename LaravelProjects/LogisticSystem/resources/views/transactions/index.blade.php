@extends('layouts.admin')

@section('title', 'Transactions - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Transactions</li>
@endsection

@section('page-title', 'Transactions')
@section('page-subtitle', 'All financial transactions')

@section('page-actions')
    <a href="{{ route('transactions.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> Add Transaction
    </a>
@endsection

@section('content')
    <div class="table-container">
        <table class="table table-hover datatable" id="transactionsTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Type</th>
                    <th>Category</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $txn)
                    <tr>
                        <td><span class="badge bg-secondary">{{ $txn->transaction_number ?? $txn->id }}</span></td>
                        <td>
                            <span class="badge bg-{{ $txn->type == 'income' ? 'success' : ($txn->type == 'expense' ? 'danger' : 'info') }}">
                                {{ ucfirst($txn->type ?? '') }}
                            </span>
                        </td>
                        <td>{{ ucwords(str_replace('_', ' ', $txn->category ?? '')) }}</td>
                        <td class="fw-medium">${{ number_format($txn->amount ?? 0, 2) }}</td>
                        <td>{{ $txn->transaction_date?->format('d M Y') ?? '—' }}</td>
                        <td>
                            @php
                                $tsc = ['completed' => 'bg-success', 'pending' => 'bg-warning text-dark', 'failed' => 'bg-danger'];
                            @endphp
                            <span class="badge {{ $tsc[$txn->status] ?? 'bg-secondary' }}">{{ ucfirst($txn->status ?? 'pending') }}</span>
                        </td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">Actions</button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a href="{{ route('transactions.show', $txn) }}" class="dropdown-item"><i class="bi bi-eye me-2"></i>View</a></li>
                                    <li><a href="{{ route('transactions.edit', $txn) }}" class="dropdown-item"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="{{ route('transactions.destroy', $txn) }}" method="POST"
                                              onsubmit="return confirm('Delete this transaction?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i>Delete</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="bi bi-currency-exchange fs-2 d-block mb-2"></i>
                            <p class="mb-0">No transactions found</p>
                            <a href="{{ route('transactions.create') }}" class="btn btn-sm btn-primary mt-2">Add Transaction</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            $('#transactionsTable').DataTable({
                order: [[4, 'desc']],
                pageLength: 25,
                responsive: true,
                language: { search: '', searchPlaceholder: 'Search transactions...' }
            });
        });
    </script>
@endpush

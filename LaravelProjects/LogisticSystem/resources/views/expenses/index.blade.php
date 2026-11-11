@extends('layouts.admin')

@section('title', 'Expenses - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Expenses</li>
@endsection

@section('page-title', 'Expenses')
@section('page-subtitle', 'Track and manage operational expenses')

@section('page-actions')
    <a href="{{ route('expenses.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> Add Expense
    </a>
@endsection

@section('content')
    <div class="table-container">
        <table class="table table-hover datatable" id="expensesTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expenses as $expense)
                    <tr>
                        <td><span class="badge bg-secondary">{{ $expense->expense_number ?? $expense->id }}</span></td>
                        <td>{{ ucwords(str_replace('_', ' ', $expense->category ?? '')) }}</td>
                        <td>{{ Str::limit($expense->description ?? '—', 40) }}</td>
                        <td class="fw-medium">${{ number_format($expense->amount ?? 0, 2) }}</td>
                        <td>{{ $expense->expense_date?->format('d M Y') ?? '—' }}</td>
                        <td>
                            @php
                                $esc = ['approved' => 'bg-success', 'pending' => 'bg-warning text-dark', 'rejected' => 'bg-danger'];
                            @endphp
                            <span class="badge {{ $esc[$expense->status] ?? 'bg-secondary' }}">
                                {{ ucfirst($expense->status ?? 'pending') }}
                            </span>
                        </td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    Actions
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a href="{{ route('expenses.show', $expense) }}" class="dropdown-item"><i class="bi bi-eye me-2"></i>View</a></li>
                                    <li><a href="{{ route('expenses.edit', $expense) }}" class="dropdown-item"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="{{ route('expenses.destroy', $expense) }}" method="POST"
                                              onsubmit="return confirm('Delete this expense?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="bi bi-trash me-2"></i>Delete
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="bi bi-cash-coin fs-2 d-block mb-2"></i>
                            <p class="mb-0">No expenses found</p>
                            <a href="{{ route('expenses.create') }}" class="btn btn-sm btn-primary mt-2">Add Expense</a>
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
            $('#expensesTable').DataTable({
                order: [[4, 'desc']],
                pageLength: 25,
                responsive: true,
                language: { search: '', searchPlaceholder: 'Search expenses...' }
            });
        });
    </script>
@endpush

@extends('layouts.app')

@section('title', 'Expenses')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="fas fa-money-bill-wave me-2"></i>Expenses</h4>
    <a href="{{ route('expenses.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Add Expense
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search description..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-3">
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i></button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Recorded By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $expense)
                    <tr>
                        <td>{{ $expense->id }}</td>
                        <td>{{ $expense->description }}</td>
                        <td class="fw-bold text-danger">${{ number_format($expense->amount, 2) }}</td>
                        <td>{{ $expense->date->format('M d, Y') }}</td>
                        <td>{{ $expense->user?->name ?? 'N/A' }}</td>
                        <td>
                            <a href="{{ route('expenses.edit', $expense->id) }}" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form id="del-exp-{{ $expense->id }}" action="{{ route('expenses.destroy', $expense->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-sm btn-danger"
                                    onclick="confirmDelete('del-exp-{{ $expense->id }}', 'this expense')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="fas fa-money-bill-wave fa-3x mb-3 d-block"></i>
                            No expenses found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end">
            {{ $expenses->links() }}
        </div>
    </div>
</div>
@endsection

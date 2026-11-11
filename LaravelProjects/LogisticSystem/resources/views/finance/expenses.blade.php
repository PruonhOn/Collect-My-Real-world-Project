@extends('layouts.admin')

@section('title', 'Expenses - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('finance.index') }}">Finance</a></li>
    <li class="breadcrumb-item active" aria-current="page">Expenses</li>
@endsection

@section('page-title', 'Expenses')
@section('page-subtitle', 'Manage company expenses')

@section('page-actions')
    <a href="{{ route('expenses.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> New Expense
    </a>
@endsection

@section('content')
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('expenses.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label for="category" class="form-label">Category</label>
                    <select id="category" name="category" class="form-select">
                        <option value="">All Categories</option>
                        @foreach($categories ?? [] as $cat)
                            <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="branch_id" class="form-label">Branch</label>
                    <select id="branch_id" name="branch_id" class="form-select">
                        <option value="">All Branches</option>
                        @foreach($branches ?? [] as $branch)
                            <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                        @endforeach
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
                    <a href="{{ route('expenses.index') }}" class="btn btn-secondary">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="table-container">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="expensesTable">
                <thead class="table-light">
                    <tr>
                        <th>Expense #</th>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Vendor</th>
                        <th>Branch</th>
                        <th class="text-end">Amount</th>
                        <th>Receipt</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $expense)
                        <tr>
                            <td><span class="badge bg-secondary">{{ $expense->expense_number }}</span></td>
                            <td>{{ $expense->date->format('d M Y') }}</td>
                            <td><span class="badge bg-warning text-dark">{{ $expense->category }}</span></td>
                            <td>{{ Str::limit($expense->description, 40) }}</td>
                            <td><small>{{ $expense->vendor ?? '—' }}</small></td>
                            <td><small>{{ $expense->branch?->name ?? '—' }}</small></td>
                            <td class="text-end text-danger fw-medium">${{ number_format($expense->amount, 2) }}</td>
                            <td>
                                @if($expense->receipt_path)
                                    <a href="{{ asset('storage/' . $expense->receipt_path) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="View Receipt">
                                        <i class="bi bi-file-earmark"></i>
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-info" title="View" onclick="showExpense({{ $expense->id }})">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <a href="{{ route('expenses.edit', $expense) }}" class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger" title="Delete"
                                            onclick="confirmDelete({{ $expense->id }})">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                                <form id="delete-form-{{ $expense->id }}" action="{{ route('expenses.destroy', $expense) }}" method="POST" class="d-none">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-cash-stack fs-1 d-block mb-2"></i>
                                <p class="mb-0">No expenses found</p>
                                <a href="{{ route('expenses.create') }}" class="btn btn-sm btn-primary mt-2">Record Expense</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($expenses, 'links'))
            <div class="d-flex justify-content-end mt-2">
                {{ $expenses->links() }}
            </div>
        @endif
    </div>
@endsection

@section('scripts')
    <script>
        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this expense?')) {
                document.getElementById('delete-form-' + id).submit();
            }
        }

        function showExpense(id) {
            window.location.href = '{{ route('expenses.index') }}/' + id;
        }
    </script>
@endsection

@extends('layouts.admin')

@section('title', 'Expenses')
@section('subtitle', 'Manage all expenses')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Expenses</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Expenses</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('expenses.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Add Expense
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="dataTable">
                <thead class="table-light">
                    <tr>
                        <th>Expense #</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th>Approved By</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $expense)
                    <tr>
                        <td><span class="fw-semibold">{{ $expense->expense_number }}</span></td>
                        <td><span class="badge bg-light text-dark">{{ $expense->category ?? 'N/A' }}</span></td>
                        <td>{{ Str::limit($expense->description, 40) }}</td>
                        <td class="fw-semibold">${{ number_format($expense->amount, 2) }}</td>
                        <td>{{ $expense->expense_date ? \Carbon\Carbon::parse($expense->expense_date)->format('d M Y') : '-' }}</td>
                        <td>
                            @switch($expense->status)
                                @case('pending')
                                    <span class="badge bg-warning text-dark">Pending</span>
                                    @break
                                @case('approved')
                                    <span class="badge bg-success">Approved</span>
                                    @break
                                @case('rejected')
                                    <span class="badge bg-danger">Rejected</span>
                                    @break
                                @case('paid')
                                    <span class="badge bg-info">Paid</span>
                                    @break
                                @default
                                    <span class="badge bg-secondary">{{ ucfirst($expense->status) }}</span>
                            @endswitch
                        </td>
                        <td>{{ $expense->createdBy->name ?? 'N/A' }}</td>
                        <td>{{ $expense->approvedBy->name ?? '-' }}</td>
                        <td class="text-center">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('expenses.show', $expense) }}"><i class="bi bi-eye me-2"></i>View</a></li>
                                    @if($expense->status === 'pending')
                                    <li><a class="dropdown-item" href="{{ route('expenses.edit', $expense) }}"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="#" onclick="deleteExpense({{ $expense->id }})"><i class="bi bi-trash me-2"></i>Delete</a></li>
                                    @endif
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                            <p class="mt-2">No expenses found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<style>
    .table th { font-weight: 600; font-size: 0.813rem; text-transform: uppercase; letter-spacing: 0.5px; color: #6c757d; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        $('#dataTable').DataTable({
            order: [[0, 'desc']],
            pageLength: 25,
            responsive: true
        });
    });

    function deleteExpense(id) {
        Swal.fire({
            title: 'Delete Expense?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, Delete'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/admin/expenses/' + id;
                form.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="DELETE">';
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
</script>
@endpush

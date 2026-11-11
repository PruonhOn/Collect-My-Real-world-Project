@extends('layouts.admin')

@section('title', 'Income')
@section('subtitle', 'Income overview')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Income</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Income</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('income.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Record Income
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="dataTable">
                <thead class="table-light">
                    <tr>
                        <th>Income #</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($incomes as $income)
                    <tr>
                        <td><span class="fw-semibold">{{ $income->income_number }}</span></td>
                        <td><span class="badge bg-light text-dark">{{ $income->category ?? 'N/A' }}</span></td>
                        <td>{{ Str::limit($income->description, 40) }}</td>
                        <td class="fw-semibold text-success">${{ number_format($income->amount, 2) }}</td>
                        <td>{{ $income->income_date ? \Carbon\Carbon::parse($income->income_date)->format('d M Y') : '-' }}</td>
                        <td>{{ $income->customer->name ?? 'N/A' }}</td>
                        <td>
                            @switch($income->status)
                                @case('confirmed')
                                    <span class="badge bg-success">Confirmed</span>
                                    @break
                                @case('pending')
                                    <span class="badge bg-warning text-dark">Pending</span>
                                    @break
                                @default
                                    <span class="badge bg-secondary">{{ ucfirst($income->status) }}</span>
                            @endswitch
                        </td>
                        <td class="text-center">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('income.show', $income) }}"><i class="bi bi-eye me-2"></i>View</a></li>
                                    <li><a class="dropdown-item" href="{{ route('income.edit', $income) }}"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                            <p class="mt-2">No income records found</p>
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
<script>
    $(document).ready(function() {
        $('#dataTable').DataTable({
            order: [[0, 'desc']],
            pageLength: 25,
            responsive: true
        });
    });
</script>
@endpush

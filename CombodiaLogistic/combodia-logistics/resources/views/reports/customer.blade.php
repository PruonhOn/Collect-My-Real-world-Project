@extends('layouts.admin')

@section('title', 'Customer Report')
@section('subtitle', 'Customer activity and account overview')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Customer Report</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">Reports</a></li>
                <li class="breadcrumb-item active">Customer Report</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-4 col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="rounded bg-warning bg-opacity-10 p-3 me-3">
                        <i class="bi bi-people text-warning" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-1 small">Total Customers</p>
                        <h4 class="mb-0 fw-bold">{{ $totalCustomers }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="rounded bg-success bg-opacity-10 p-3 me-3">
                        <i class="bi bi-person-check text-success" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-1 small">Active</p>
                        <h4 class="mb-0 fw-bold">{{ $activeCustomers }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="rounded bg-primary bg-opacity-10 p-3 me-3">
                        <i class="bi bi-wallet2 text-primary" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-1 small">Total Balance</p>
                        <h4 class="mb-0 fw-bold">${{ number_format($totalBalance, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table id="customerReportTable" class="table table-bordered table-striped w-100">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Company</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Balance</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                    <tr>
                        <td><span class="badge bg-light text-dark border">{{ $customer->code }}</span></td>
                        <td class="fw-medium">{{ $customer->name }}</td>
                        <td>{{ $customer->company_name ?? '-' }}</td>
                        <td>{{ $customer->email ?? '-' }}</td>
                        <td>{{ $customer->phone ?? '-' }}</td>
                        <td class="text-end">
                            @php $balance = $customer->balance ?? 0; @endphp
                            <span class="{{ $balance > 0 ? 'text-danger' : 'text-success' }}">
                                ${{ number_format(abs($balance), 2) }}
                                @if($balance > 0) (Dr) @endif
                            </span>
                        </td>
                        <td>
                            @if($customer->is_active ?? true)
                                <span class="badge bg-success bg-opacity-10 text-success">Active</span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger">Inactive</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                            <p class="mt-2">No customers found</p>
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
        $('#customerReportTable').DataTable({
            responsive: true,
            order: [[1, 'asc']],
            pageLength: 25,
            language: {
                search: '<i class="bi bi-search"></i>',
                searchPlaceholder: 'Search customers...',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ customers',
                emptyTable: 'No customers found',
                paginate: { previous: '<i class="bi bi-chevron-left"></i>', next: '<i class="bi bi-chevron-right"></i>' }
            }
        });
    });
</script>
@endpush

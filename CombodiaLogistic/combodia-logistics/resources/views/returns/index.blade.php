@extends('layouts.admin')

@section('title', 'Returns')
@section('subtitle', 'Manage all returns')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Returns</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Returns</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('returns.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> New Return
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="dataTable">
                <thead class="table-light">
                    <tr>
                        <th>Return #</th>
                        <th>Shipment #</th>
                        <th>Customer</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Items</th>
                        <th>Refund Amount</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($returns as $return)
                    <tr>
                        <td><span class="fw-semibold">{{ $return->return_number }}</span></td>
                        <td><a href="{{ route('shipments.show', $return->shipment) }}" class="text-decoration-none">{{ $return->shipment->shipment_number ?? 'N/A' }}</a></td>
                        <td>{{ $return->customer->name ?? 'N/A' }}</td>
                        <td><span class="badge bg-light text-dark">{{ ucfirst(str_replace('_', ' ', $return->type)) }}</span></td>
                        <td>
                            @switch($return->status)
                                @case('pending')
                                    <span class="badge bg-warning text-dark">Pending</span>
                                    @break
                                @case('approved')
                                    <span class="badge bg-info">Approved</span>
                                    @break
                                @case('completed')
                                    <span class="badge bg-success">Completed</span>
                                    @break
                                @case('rejected')
                                    <span class="badge bg-danger">Rejected</span>
                                    @break
                                @default
                                    <span class="badge bg-secondary">{{ ucfirst($return->status) }}</span>
                            @endswitch
                        </td>
                        <td>{{ $return->items_count ?? 0 }}</td>
                        <td>${{ number_format($return->refund_amount ?? 0, 2) }}</td>
                        <td class="text-center">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('returns.show', $return) }}"><i class="bi bi-eye me-2"></i>View</a></li>
                                    <li><a class="dropdown-item" href="{{ route('returns.edit', $return) }}"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                            <p class="mt-2">No returns found</p>
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

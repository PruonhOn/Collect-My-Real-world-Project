@extends('layouts.admin')

@section('title', 'Shipment Report')
@section('subtitle', 'Shipment analytics and statistics')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Shipment Report</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">Reports</a></li>
                <li class="breadcrumb-item active">Shipment Report</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-3 col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="rounded bg-warning bg-opacity-10 p-3 me-3">
                        <i class="bi bi-clock-history text-warning" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-1 small">Pending</p>
                        <h4 class="mb-0 fw-bold">{{ $statusCounts['pending'] ?? 0 }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="rounded bg-info bg-opacity-10 p-3 me-3">
                        <i class="bi bi-check-circle text-info" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-1 small">Approved</p>
                        <h4 class="mb-0 fw-bold">{{ $statusCounts['approved'] ?? 0 }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="rounded bg-primary bg-opacity-10 p-3 me-3">
                        <i class="bi bi-truck text-primary" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-1 small">In Transit</p>
                        <h4 class="mb-0 fw-bold">{{ $statusCounts['in_transit'] ?? 0 }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="rounded bg-success bg-opacity-10 p-3 me-3">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-1 small">Delivered</p>
                        <h4 class="mb-0 fw-bold">{{ $statusCounts['delivered'] ?? 0 }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold"><i class="bi bi-funnel me-2"></i>Filters</h6>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('reports.shipment') }}">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="in_transit" {{ request('status') == 'in_transit' ? 'selected' : '' }}>In Transit</option>
                        <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Delivered</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date From</label>
                    <input type="date" class="form-control" name="date_from" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date To</label>
                    <input type="date" class="form-control" name="date_to" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search me-1"></i> Filter
                    </button>
                    <a href="{{ route('reports.shipment') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-body">
        <div class="table-responsive">
            <table id="shipmentReportTable" class="table table-bordered table-striped w-100">
                <thead>
                    <tr>
                        <th>Shipment Number</th>
                        <th>Customer</th>
                        <th>Branch</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Created Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($shipments as $shipment)
                    <tr>
                        <td><span class="fw-semibold">{{ $shipment->shipment_number }}</span></td>
                        <td>{{ $shipment->customer->name ?? 'N/A' }}</td>
                        <td>{{ $shipment->branch->name ?? 'N/A' }}</td>
                        <td>
                            @switch($shipment->status)
                                @case('pending')
                                    <span class="badge bg-warning text-dark">Pending</span>
                                    @break
                                @case('approved')
                                    <span class="badge bg-info">Approved</span>
                                    @break
                                @case('in_transit')
                                    <span class="badge bg-primary">In Transit</span>
                                    @break
                                @case('delivered')
                                    <span class="badge bg-success">Delivered</span>
                                    @break
                                @case('cancelled')
                                    <span class="badge bg-danger">Cancelled</span>
                                    @break
                                @default
                                    <span class="badge bg-secondary">{{ ucfirst($shipment->status) }}</span>
                            @endswitch
                        </td>
                        <td>
                            @switch($shipment->priority)
                                @case('high')
                                    <span class="badge bg-danger">High</span>
                                    @break
                                @case('medium')
                                    <span class="badge bg-warning text-dark">Medium</span>
                                    @break
                                @case('low')
                                    <span class="badge bg-success">Low</span>
                                    @break
                                @default
                                    <span class="badge bg-secondary">Normal</span>
                            @endswitch
                        </td>
                        <td>{{ $shipment->created_at->format('d M Y') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                            <p class="mt-2">No shipments found</p>
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
        $('#shipmentReportTable').DataTable({
            responsive: true,
            order: [[5, 'desc']],
            pageLength: 25,
            language: {
                search: '<i class="bi bi-search"></i>',
                searchPlaceholder: 'Search shipments...',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ shipments',
                emptyTable: 'No shipments found',
                paginate: { previous: '<i class="bi bi-chevron-left"></i>', next: '<i class="bi bi-chevron-right"></i>' }
            }
        });
    });
</script>
@endpush

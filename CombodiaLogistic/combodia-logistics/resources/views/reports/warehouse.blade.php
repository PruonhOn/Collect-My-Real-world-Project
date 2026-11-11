@extends('layouts.admin')

@section('title', 'Warehouse Report')
@section('subtitle', 'Warehouse utilization and capacity')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Warehouse Report</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">Reports</a></li>
                <li class="breadcrumb-item active">Warehouse Report</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-3 col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="rounded bg-info bg-opacity-10 p-3 me-3">
                        <i class="bi bi-warehouse text-info" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-1 small">Total Warehouses</p>
                        <h4 class="mb-0 fw-bold">{{ $warehouses->total() }}</h4>
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
                        <i class="bi bi-box-arrow-in-down text-primary" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-1 small">Total Capacity</p>
                        <h4 class="mb-0 fw-bold">{{ number_format($totalCapacity) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="rounded bg-warning bg-opacity-10 p-3 me-3">
                        <i class="bi bi-box-arrow-down text-warning" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-1 small">Used Capacity</p>
                        <h4 class="mb-0 fw-bold">{{ number_format($usedCapacity) }}</h4>
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
                        <i class="bi bi-box-arrow-up text-success" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-1 small">Available Capacity</p>
                        <h4 class="mb-0 fw-bold">{{ number_format($availableCapacity) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table id="warehouseReportTable" class="table table-bordered table-striped w-100">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Branch</th>
                        <th>Capacity</th>
                        <th>Used</th>
                        <th>Available</th>
                        <th>Zones</th>
                        <th>Usage</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($warehouses as $warehouse)
                    @php
                        $capacityPercent = $warehouse->capacity > 0 ? round(($warehouse->used_capacity / $warehouse->capacity) * 100) : 0;
                    @endphp
                    <tr>
                        <td><span class="badge bg-light text-dark border">{{ $warehouse->code }}</span></td>
                        <td class="fw-medium">{{ $warehouse->name }}</td>
                        <td>{{ $warehouse->branch->name ?? '-' }}</td>
                        <td>{{ number_format($warehouse->capacity) }}</td>
                        <td>{{ number_format($warehouse->used_capacity) }}</td>
                        <td>{{ number_format($warehouse->capacity - $warehouse->used_capacity) }}</td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $warehouse->warehouseZones->count() }} zones</span>
                        </td>
                        <td style="min-width: 150px;">
                            <div class="d-flex align-items-center">
                                <div class="progress flex-grow-1 me-2" style="height: 8px;">
                                    <div class="progress-bar {{ $capacityPercent > 90 ? 'bg-danger' : ($capacityPercent > 70 ? 'bg-warning' : 'bg-success') }}" style="width: {{ $capacityPercent }}%"></div>
                                </div>
                                <small class="text-muted">{{ $capacityPercent }}%</small>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                            <p class="mt-2">No warehouses found</p>
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
        $('#warehouseReportTable').DataTable({
            responsive: true,
            order: [[1, 'asc']],
            pageLength: 25,
            language: {
                search: '<i class="bi bi-search"></i>',
                searchPlaceholder: 'Search warehouses...',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ warehouses',
                emptyTable: 'No warehouses found',
                paginate: { previous: '<i class="bi bi-chevron-left"></i>', next: '<i class="bi bi-chevron-right"></i>' }
            }
        });
    });
</script>
@endpush

@extends('layouts.admin')

@section('title', 'Vehicle Report')
@section('subtitle', 'Fleet status and maintenance overview')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Vehicle Report</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">Reports</a></li>
                <li class="breadcrumb-item active">Vehicle Report</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-4 col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="rounded bg-dark bg-opacity-10 p-3 me-3">
                        <i class="bi bi-truck text-dark" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-1 small">Total Vehicles</p>
                        <h4 class="mb-0 fw-bold">{{ $totalVehicles }}</h4>
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
                        <i class="bi bi-check-circle text-success" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-1 small">Active</p>
                        <h4 class="mb-0 fw-bold">{{ $activeVehicles }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="rounded bg-warning bg-opacity-10 p-3 me-3">
                        <i class="bi bi-tools text-warning" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-1 small">In Maintenance</p>
                        <h4 class="mb-0 fw-bold">{{ $maintenanceVehicles }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table id="vehicleReportTable" class="table table-bordered table-striped w-100">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Plate Number</th>
                        <th>Brand</th>
                        <th>Model</th>
                        <th>Type</th>
                        <th>Branch</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vehicles as $vehicle)
                    <tr>
                        <td><span class="badge bg-light text-dark border">{{ $vehicle->code }}</span></td>
                        <td class="fw-medium">{{ $vehicle->plate_number }}</td>
                        <td>{{ $vehicle->brand }}</td>
                        <td>{{ $vehicle->model }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $vehicle->vehicleType->name ?? '-' }}</span></td>
                        <td>{{ $vehicle->branch->name ?? '-' }}</td>
                        <td>
                            @php
                                $statusColors = [
                                    'available' => 'success',
                                    'in_use' => 'primary',
                                    'maintenance' => 'warning',
                                    'inactive' => 'danger',
                                ];
                                $statusLabels = [
                                    'available' => 'Available',
                                    'in_use' => 'In Use',
                                    'maintenance' => 'Maintenance',
                                    'inactive' => 'Inactive',
                                ];
                                $color = $statusColors[$vehicle->status ?? 'available'] ?? 'secondary';
                                $label = $statusLabels[$vehicle->status ?? 'available'] ?? ucfirst($vehicle->status ?? 'Unknown');
                            @endphp
                            <span class="badge bg-{{ $color }} bg-opacity-10 text-{{ $color }}">{{ $label }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                            <p class="mt-2">No vehicles found</p>
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
        $('#vehicleReportTable').DataTable({
            responsive: true,
            order: [[1, 'asc']],
            pageLength: 25,
            language: {
                search: '<i class="bi bi-search"></i>',
                searchPlaceholder: 'Search vehicles...',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ vehicles',
                emptyTable: 'No vehicles found',
                paginate: { previous: '<i class="bi bi-chevron-left"></i>', next: '<i class="bi bi-chevron-right"></i>' }
            }
        });
    });
</script>
@endpush

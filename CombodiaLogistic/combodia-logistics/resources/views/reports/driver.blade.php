@extends('layouts.admin')

@section('title', 'Driver Report')
@section('subtitle', 'Driver performance and activity')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Driver Report</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">Reports</a></li>
                <li class="breadcrumb-item active">Driver Report</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-4 col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="rounded bg-secondary bg-opacity-10 p-3 me-3">
                        <i class="bi bi-person-badge text-secondary" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-1 small">Total Drivers</p>
                        <h4 class="mb-0 fw-bold">{{ $drivers->total() }}</h4>
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
                        <h4 class="mb-0 fw-bold">{{ $activeDrivers }}</h4>
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
                        <i class="bi bi-pause-circle text-warning" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-1 small">On Leave</p>
                        <h4 class="mb-0 fw-bold">{{ $onLeaveDrivers }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table id="driverReportTable" class="table table-bordered table-striped w-100">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>License Number</th>
                        <th>Branch</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($drivers as $driver)
                    <tr>
                        <td><span class="badge bg-light text-dark border">{{ $driver->code }}</span></td>
                        <td class="fw-medium">{{ $driver->name }}</td>
                        <td>{{ $driver->email ?? '-' }}</td>
                        <td>{{ $driver->phone ?? '-' }}</td>
                        <td>{{ $driver->license_number ?? '-' }}</td>
                        <td>{{ $driver->branch->name ?? '-' }}</td>
                        <td>
                            @if($driver->is_active ?? true)
                                <span class="badge bg-success bg-opacity-10 text-success">Active</span>
                            @else
                                <span class="badge bg-warning bg-opacity-10 text-warning">On Leave</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                            <p class="mt-2">No drivers found</p>
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
        $('#driverReportTable').DataTable({
            responsive: true,
            order: [[1, 'asc']],
            pageLength: 25,
            language: {
                search: '<i class="bi bi-search"></i>',
                searchPlaceholder: 'Search drivers...',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ drivers',
                emptyTable: 'No drivers found',
                paginate: { previous: '<i class="bi bi-chevron-left"></i>', next: '<i class="bi bi-chevron-right"></i>' }
            }
        });
    });
</script>
@endpush

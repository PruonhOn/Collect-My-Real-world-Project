@extends('layouts.admin')

@section('title', 'Vehicle Maintenance')
@section('subtitle', 'Manage vehicle maintenance records')

@section('content')
<x-breadcrumb :items="[['label' => 'Vehicle Maintenance']]" />

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Vehicle Maintenance</h4>
        <p class="text-muted mb-0">Track and manage all vehicle maintenance activities</p>
    </div>
    <a href="{{ route('vehicle-maintenances.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Add Maintenance
    </a>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom">
        <h6 class="mb-0 fw-bold"><i class="bi bi-funnel me-2"></i>Filters</h6>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('vehicle-maintenances.index') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="vehicle_id" class="form-label">Vehicle</label>
                <select class="form-select" id="vehicle_id" name="vehicle_id">
                    <option value="">All Vehicles</option>
                    @foreach($vehicles as $vehicle)
                        <option value="{{ $vehicle->id }}" {{ request('vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                            {{ $vehicle->plate_number }} - {{ $vehicle->brand }} {{ $vehicle->model }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search me-1"></i> Filter
                </button>
                <a href="{{ route('vehicle-maintenances.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table id="maintenanceTable" class="table table-bordered table-striped w-100">
                <thead>
                    <tr>
                        <th>Vehicle</th>
                        <th>Maintenance Type</th>
                        <th>Description</th>
                        <th>Scheduled Date</th>
                        <th>Completed Date</th>
                        <th>Cost</th>
                        <th>Status</th>
                        <th class="text-center" style="width: 150px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($maintenances as $maintenance)
                    <tr>
                        <td>
                            <span class="fw-medium">{{ $maintenance->vehicle->plate_number ?? '-' }}</span>
                            <br><small class="text-muted">{{ $maintenance->vehicle->brand ?? '' }} {{ $maintenance->vehicle->model ?? '' }}</small>
                        </td>
                        <td><span class="badge bg-light text-dark border">{{ ucfirst(str_replace('_', ' ', $maintenance->maintenance_type)) }}</span></td>
                        <td>{{ Str::limit($maintenance->description, 50) }}</td>
                        <td>
                            @if($maintenance->scheduled_date)
                                {{ \Carbon\Carbon::parse($maintenance->scheduled_date)->format('d M Y') }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if($maintenance->completed_date)
                                {{ \Carbon\Carbon::parse($maintenance->completed_date)->format('d M Y') }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="fw-medium">${{ number_format($maintenance->cost ?? 0, 2) }}</td>
                        <td>
                            @php
                                $statusColors = [
                                    'pending' => 'warning',
                                    'in_progress' => 'info',
                                    'completed' => 'success',
                                    'cancelled' => 'danger',
                                ];
                                $statusLabels = [
                                    'pending' => 'Pending',
                                    'in_progress' => 'In Progress',
                                    'completed' => 'Completed',
                                    'cancelled' => 'Cancelled',
                                ];
                                $color = $statusColors[$maintenance->status ?? 'pending'] ?? 'secondary';
                                $label = $statusLabels[$maintenance->status ?? 'pending'] ?? ucfirst($maintenance->status ?? 'Unknown');
                            @endphp
                            <span class="badge bg-{{ $color }} bg-opacity-10 text-{{ $color }}">{{ $label }}</span>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('vehicle-maintenances.show', $maintenance->id) }}" class="btn btn-outline-info" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('vehicle-maintenances.edit', $maintenance->id) }}" class="btn btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('vehicle-maintenances.destroy', $maintenance->id) }}" method="POST" class="d-inline delete-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        $('#maintenanceTable').DataTable({
            responsive: true,
            order: [[3, 'desc']],
            language: {
                search: '<i class="bi bi-search"></i>',
                searchPlaceholder: 'Search maintenance records...',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ records',
                emptyTable: 'No maintenance records found',
                paginate: { previous: '<i class="bi bi-chevron-left"></i>', next: '<i class="bi bi-chevron-right"></i>' }
            }
        });

        $('.delete-form').on('submit', function(e) {
            e.preventDefault();
            const form = this;
            Swal.fire({
                title: 'Are you sure?',
                text: 'This maintenance record will be permanently deleted.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) form.submit();
            });
        });
    });
</script>
@endpush

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
@endpush

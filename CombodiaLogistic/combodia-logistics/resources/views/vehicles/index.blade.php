@extends('layouts.admin')

@section('title', 'Vehicles')
@section('subtitle', 'Manage fleet vehicles')

@section('content')
<x-breadcrumb :items="[['label' => 'Vehicles']]" />

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Vehicles</h4>
        <p class="text-muted mb-0">Manage all fleet vehicles and their status</p>
    </div>
    <a href="{{ route('admin.vehicles.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Add Vehicle
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table id="vehiclesTable" class="table table-bordered table-striped w-100">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Plate #</th>
                        <th>Brand</th>
                        <th>Model</th>
                        <th>Type</th>
                        <th>Driver</th>
                        <th>Branch</th>
                        <th>Status</th>
                        <th>Insurance Expiry</th>
                        <th class="text-center" style="width: 150px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($vehicles as $vehicle)
                    <tr>
                        <td><span class="badge bg-light text-dark border">{{ $vehicle->code }}</span></td>
                        <td class="fw-medium">{{ $vehicle->plate_number }}</td>
                        <td>{{ $vehicle->brand }}</td>
                        <td>{{ $vehicle->model }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $vehicle->type ?? '-' }}</span></td>
                        <td>{{ $vehicle->driver->name ?? '-' }}</td>
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
                        <td>
                            @if($vehicle->insurance_expiry && \Carbon\Carbon::parse($vehicle->insurance_expiry)->isPast())
                                <span class="text-danger fw-medium">{{ \Carbon\Carbon::parse($vehicle->insurance_expiry)->format('d M Y') }}</span>
                                <i class="bi bi-exclamation-triangle text-danger ms-1" style="font-size: 0.75rem;"></i>
                            @elseif($vehicle->insurance_expiry)
                                <span>{{ \Carbon\Carbon::parse($vehicle->insurance_expiry)->format('d M Y') }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.vehicles.edit', $vehicle->id) }}" class="btn btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('admin.vehicles.destroy', $vehicle->id) }}" method="POST" class="d-inline delete-form">
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
        $('#vehiclesTable').DataTable({
            responsive: true,
            order: [[1, 'asc']],
            language: {
                search: '<i class="bi bi-search"></i>',
                searchPlaceholder: 'Search vehicles...',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ vehicles',
                emptyTable: 'No vehicles found',
                paginate: { previous: '<i class="bi bi-chevron-left"></i>', next: '<i class="bi bi-chevron-right"></i>' }
            }
        });

        $('.delete-form').on('submit', function(e) {
            e.preventDefault();
            const form = this;
            Swal.fire({
                title: 'Are you sure?',
                text: 'This vehicle will be permanently deleted.',
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

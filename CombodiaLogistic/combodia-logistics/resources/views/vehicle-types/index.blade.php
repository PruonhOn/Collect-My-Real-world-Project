@extends('layouts.admin')

@section('title', 'Vehicle Types')
@section('subtitle', 'Manage vehicle type categories')

@section('content')
<x-breadcrumb :items="[['label' => 'Vehicle Types']]" />

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Vehicle Types</h4>
        <p class="text-muted mb-0">Manage all vehicle type categories and their capacity limits</p>
    </div>
    <a href="{{ route('vehicle-types.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Add Vehicle Type
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table id="vehicleTypesTable" class="table table-bordered table-striped w-100">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Max Weight (kg)</th>
                        <th>Max Volume (cbm)</th>
                        <th>Status</th>
                        <th class="text-center" style="width: 150px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($vehicleTypes as $vehicleType)
                    <tr>
                        <td class="fw-medium">{{ $vehicleType->name }}</td>
                        <td>{{ Str::limit($vehicleType->description, 50) ?? '-' }}</td>
                        <td>{{ number_format($vehicleType->max_weight, 2) }}</td>
                        <td>{{ number_format($vehicleType->max_volume, 2) }}</td>
                        <td>
                            @if($vehicleType->is_active)
                                <span class="badge bg-success bg-opacity-10 text-success">Active</span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger">Inactive</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('vehicle-types.edit', $vehicleType->id) }}" class="btn btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('vehicle-types.destroy', $vehicleType->id) }}" method="POST" class="d-inline delete-form">
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
        $('#vehicleTypesTable').DataTable({
            responsive: true,
            order: [[0, 'asc']],
            language: {
                search: '<i class="bi bi-search"></i>',
                searchPlaceholder: 'Search vehicle types...',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ vehicle types',
                emptyTable: 'No vehicle types found',
                paginate: { previous: '<i class="bi bi-chevron-left"></i>', next: '<i class="bi bi-chevron-right"></i>' }
            }
        });

        $('.delete-form').on('submit', function(e) {
            e.preventDefault();
            const form = this;
            Swal.fire({
                title: 'Are you sure?',
                text: 'This vehicle type will be permanently deleted.',
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

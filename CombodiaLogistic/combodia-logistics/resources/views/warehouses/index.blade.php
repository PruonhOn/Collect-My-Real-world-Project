@extends('layouts.admin')

@section('title', 'Warehouses')
@section('subtitle', 'Manage warehouse facilities')

@section('content')
<x-breadcrumb :items="[['label' => 'Warehouses']]" />

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Warehouses</h4>
        <p class="text-muted mb-0">Manage all warehouse facilities and their capacity</p>
    </div>
    <a href="{{ route('admin.warehouses.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Add Warehouse
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table id="warehousesTable" class="table table-bordered table-striped w-100">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Branch</th>
                        <th>City</th>
                        <th>Capacity</th>
                        <th>Manager</th>
                        <th>Status</th>
                        <th class="text-center" style="width: 180px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($warehouses as $warehouse)
                    @php
                        $capacityPercent = $warehouse->capacity > 0 ? round(($warehouse->used_capacity / $warehouse->capacity) * 100) : 0;
                    @endphp
                    <tr>
                        <td><span class="badge bg-light text-dark border">{{ $warehouse->code }}</span></td>
                        <td class="fw-medium">{{ $warehouse->name }}</td>
                        <td>{{ $warehouse->branch->name ?? '-' }}</td>
                        <td>{{ $warehouse->city ?? '-' }}</td>
                        <td style="min-width: 150px;">
                            <div class="d-flex align-items-center">
                                <div class="progress flex-grow-1 me-2" style="height: 8px;">
                                    <div class="progress-bar {{ $capacityPercent > 90 ? 'bg-danger' : ($capacityPercent > 70 ? 'bg-warning' : 'bg-success') }}" style="width: {{ $capacityPercent }}%"></div>
                                </div>
                                <small class="text-muted">{{ $capacityPercent }}%</small>
                            </div>
                        </td>
                        <td>{{ $warehouse->manager_name ?? '-' }}</td>
                        <td>
                            @if($warehouse->is_active ?? true)
                                <span class="badge bg-success bg-opacity-10 text-success">Active</span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger">Inactive</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.warehouses.show', $warehouse->id) }}" class="btn btn-outline-info" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('admin.warehouses.edit', $warehouse->id) }}" class="btn btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('admin.warehouses.destroy', $warehouse->id) }}" method="POST" class="d-inline delete-form">
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
        $('#warehousesTable').DataTable({
            responsive: true,
            order: [[1, 'asc']],
            language: {
                search: '<i class="bi bi-search"></i>',
                searchPlaceholder: 'Search warehouses...',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ warehouses',
                emptyTable: 'No warehouses found',
                paginate: { previous: '<i class="bi bi-chevron-left"></i>', next: '<i class="bi bi-chevron-right"></i>' }
            }
        });

        $('.delete-form').on('submit', function(e) {
            e.preventDefault();
            const form = this;
            Swal.fire({
                title: 'Are you sure?',
                text: 'This warehouse will be permanently deleted.',
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

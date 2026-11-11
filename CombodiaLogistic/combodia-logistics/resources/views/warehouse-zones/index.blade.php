@extends('layouts.admin')

@section('title', 'Warehouse Zones')
@section('subtitle', 'Manage warehouse zone areas')

@section('content')
<x-breadcrumb :items="[['label' => 'Warehouse Zones']]" />

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Warehouse Zones</h4>
        <p class="text-muted mb-0">Manage all zones across your warehouse facilities</p>
    </div>
    <a href="{{ route('warehouse-zones.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Add Zone
    </a>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="warehouse_id" class="form-label">Filter by Warehouse</label>
                <select class="form-select" id="warehouse_id" name="warehouse_id" onchange="this.form.submit()">
                    <option value="">All Warehouses</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table id="zonesTable" class="table table-bordered table-striped w-100">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Warehouse</th>
                        <th>Description</th>
                        <th>Capacity</th>
                        <th>Status</th>
                        <th class="text-center" style="width: 180px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($zones as $zone)
                    <tr>
                        <td><span class="badge bg-light text-dark border">{{ $zone->code }}</span></td>
                        <td class="fw-medium">{{ $zone->name }}</td>
                        <td>{{ $zone->warehouse->name ?? '-' }}</td>
                        <td>{{ Str::limit($zone->description ?? '-', 50) }}</td>
                        <td>{{ $zone->capacity ? number_format($zone->capacity) . ' sqm' : '-' }}</td>
                        <td>
                            @if($zone->is_active)
                                <span class="badge bg-success bg-opacity-10 text-success">Active</span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger">Inactive</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('warehouse-zones.show', $zone->id) }}" class="btn btn-outline-info" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('warehouse-zones.edit', $zone->id) }}" class="btn btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('warehouse-zones.destroy', $zone->id) }}" method="POST" class="d-inline delete-form">
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
        $('#zonesTable').DataTable({
            responsive: true,
            order: [[1, 'asc']],
            language: {
                search: '<i class="bi bi-search"></i>',
                searchPlaceholder: 'Search zones...',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ zones',
                emptyTable: 'No warehouse zones found',
                paginate: { previous: '<i class="bi bi-chevron-left"></i>', next: '<i class="bi bi-chevron-right"></i>' }
            }
        });

        $('.delete-form').on('submit', function(e) {
            e.preventDefault();
            const form = this;
            Swal.fire({
                title: 'Are you sure?',
                text: 'This zone will be permanently deleted.',
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

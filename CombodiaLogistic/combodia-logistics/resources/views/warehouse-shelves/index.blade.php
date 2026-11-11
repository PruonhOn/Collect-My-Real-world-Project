@extends('layouts.admin')

@section('title', 'Warehouse Shelves')
@section('subtitle', 'Manage warehouse shelves')

@section('content')
<x-breadcrumb :items="[['label' => 'Warehouse Shelves']]" />

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Warehouse Shelves</h4>
        <p class="text-muted mb-0">Manage all warehouse shelves and their capacity</p>
    </div>
    <a href="{{ route('warehouse-shelves.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Add Shelf
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-3">
                <label for="zoneFilter" class="form-label">Filter by Zone</label>
                <select class="form-select" id="zoneFilter">
                    <option value="">All Zones</option>
                    @foreach($zones as $zone)
                        <option value="{{ $zone->name }}">{{ $zone->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table id="shelvesTable" class="table table-bordered table-striped w-100">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Zone</th>
                        <th>Max Weight (kg)</th>
                        <th>Current Weight (kg)</th>
                        <th>Usage %</th>
                        <th>Status</th>
                        <th class="text-center" style="width: 180px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($shelves as $shelf)
                    @php
                        $usagePercent = $shelf->max_weight > 0 ? round(($shelf->current_weight / $shelf->max_weight) * 100) : 0;
                    @endphp
                    <tr>
                        <td><span class="badge bg-light text-dark border">{{ $shelf->code }}</span></td>
                        <td class="fw-medium">{{ $shelf->name }}</td>
                        <td>{{ $shelf->warehouseZone->name ?? '-' }}</td>
                        <td>{{ number_format($shelf->max_weight, 2) }}</td>
                        <td>{{ number_format($shelf->current_weight, 2) }}</td>
                        <td style="min-width: 150px;">
                            <div class="d-flex align-items-center">
                                <div class="progress flex-grow-1 me-2" style="height: 8px;">
                                    <div class="progress-bar {{ $usagePercent > 90 ? 'bg-danger' : ($usagePercent > 70 ? 'bg-warning' : 'bg-success') }}" style="width: {{ min($usagePercent, 100) }}%"></div>
                                </div>
                                <small class="text-muted">{{ $usagePercent }}%</small>
                            </div>
                        </td>
                        <td>
                            @if($shelf->is_active)
                                <span class="badge bg-success bg-opacity-10 text-success">Active</span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger">Inactive</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('warehouse-shelves.show', $shelf->id) }}" class="btn btn-outline-info" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('warehouse-shelves.edit', $shelf->id) }}" class="btn btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('warehouse-shelves.destroy', $shelf->id) }}" method="POST" class="d-inline delete-form">
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

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        var table = $('#shelvesTable').DataTable({
            responsive: true,
            order: [[1, 'asc']],
            language: {
                search: '<i class="bi bi-search"></i>',
                searchPlaceholder: 'Search shelves...',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ shelves',
                emptyTable: 'No shelves found',
                paginate: { previous: '<i class="bi bi-chevron-left"></i>', next: '<i class="bi bi-chevron-right"></i>' }
            }
        });

        $('#zoneFilter').on('change', function() {
            var zone = $(this).val();
            if (zone) {
                table.column(2).search(zone).draw();
            } else {
                table.column(2).search('').draw();
            }
        });

        $('.delete-form').on('submit', function(e) {
            e.preventDefault();
            const form = this;
            Swal.fire({
                title: 'Are you sure?',
                text: 'This shelf will be permanently deleted.',
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

@extends('layouts.admin')

@section('title', 'Warehouses - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item active" aria-current="page">Warehouses</li>
@endsection

@section('page-title', 'Warehouses')
@section('page-subtitle', 'Manage warehouse locations and capacity')

@section('page-actions')
    <a href="{{ route('warehouses.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> Add Warehouse
    </a>
@endsection

@section('content')
    <div class="table-container">
        <table class="table table-hover datatable" id="warehousesTable">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>City</th>
                    <th>Branch</th>
                    <th>Manager</th>
                    <th>Capacity</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($warehouses as $warehouse)
                    <tr>
                        <td><span class="badge bg-secondary">{{ $warehouse->code }}</span></td>
                        <td>
                            <a href="{{ route('warehouses.show', $warehouse) }}" class="text-decoration-none fw-medium">
                                {{ $warehouse->name }}
                            </a>
                        </td>
                        <td>{{ $warehouse->city }}</td>
                        <td>{{ $warehouse->branch?->name ?? '—' }}</td>
                        <td>{{ $warehouse->manager_name ?? '—' }}</td>
                        <td>
                            @php
                                $used = $warehouse->used_capacity ?? 0;
                                $total = $warehouse->total_capacity ?? 1;
                                $pct = min(round(($used / $total) * 100), 100);
                            @endphp
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height: 6px;">
                                    <div class="progress-bar {{ $pct >= 90 ? 'bg-danger' : ($pct >= 70 ? 'bg-warning' : 'bg-success') }}"
                                         role="progressbar" style="width: {{ $pct }}%"></div>
                                </div>
                                <small class="text-muted">{{ $used }}/{{ $total }}</small>
                            </div>
                        </td>
                        <td>
                            @if($warehouse->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-danger">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    Actions
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a href="{{ route('warehouses.show', $warehouse) }}" class="dropdown-item">
                                            <i class="bi bi-eye me-2"></i>View
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('warehouses.edit', $warehouse) }}" class="dropdown-item">
                                            <i class="bi bi-pencil me-2"></i>Edit
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('warehouse-locations.index', ['warehouse_id' => $warehouse->id]) }}" class="dropdown-item">
                                            <i class="bi bi-geo-alt me-2"></i>Locations
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="{{ route('warehouses.destroy', $warehouse) }}" method="POST"
                                              onsubmit="return confirm('Delete this warehouse?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="bi bi-trash me-2"></i>Delete
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="bi bi-building fs-2 d-block mb-2"></i>
                            <p class="mb-0">No warehouses found</p>
                            <a href="{{ route('warehouses.create') }}" class="btn btn-sm btn-primary mt-2">Add Warehouse</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            $('#warehousesTable').DataTable({
                order: [[0, 'asc']],
                pageLength: 25,
                responsive: true,
                language: { search: '', searchPlaceholder: 'Search warehouses...' }
            });
        });
    </script>
@endpush

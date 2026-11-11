@extends('layouts.admin')

@section('title', 'Warehouse Locations - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('warehouses.index') }}">Warehouses</a></li>
    @if(isset($warehouse))
        <li class="breadcrumb-item"><a href="{{ route('warehouses.show', $warehouse) }}">{{ $warehouse->code }}</a></li>
    @endif
    <li class="breadcrumb-item active" aria-current="page">Locations</li>
@endsection

@section('page-title', 'Warehouse Locations')
@section('page-subtitle', isset($warehouse) ? $warehouse->name : 'All locations')

@section('page-actions')
    <a href="{{ route('warehouse-locations.create', isset($warehouse) ? ['warehouse_id' => $warehouse->id] : []) }}"
       class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> Add Location
    </a>
@endsection

@section('content')
    <div class="table-container">
        <table class="table table-hover datatable" id="locationsTable">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Zone</th>
                    <th>Aisle</th>
                    <th>Rack</th>
                    <th>Shelf</th>
                    <th>Bin</th>
                    <th>Occupied</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($locations as $location)
                    <tr>
                        <td><span class="badge bg-secondary">{{ $location->code }}</span></td>
                        <td>{{ $location->name }}</td>
                        <td>{{ $location->zone ?? '—' }}</td>
                        <td>{{ $location->aisle ?? '—' }}</td>
                        <td>{{ $location->rack ?? '—' }}</td>
                        <td>{{ $location->shelf ?? '—' }}</td>
                        <td>{{ $location->bin ?? '—' }}</td>
                        <td>
                            @if($location->is_occupied)
                                <span class="badge bg-warning">Occupied</span>
                            @else
                                <span class="badge bg-success">Available</span>
                            @endif
                        </td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    Actions
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a href="{{ route('warehouse-locations.edit', $location) }}"
                                           class="dropdown-item">
                                            <i class="bi bi-pencil me-2"></i>Edit
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="{{ route('warehouse-locations.destroy', $location) }}"
                                              method="POST" onsubmit="return confirm('Delete this location?')">
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
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="bi bi-geo-alt fs-2 d-block mb-2"></i>
                            <p class="mb-0">No locations found</p>
                            <a href="{{ route('warehouse-locations.create', isset($warehouse) ? ['warehouse_id' => $warehouse->id] : []) }}"
                               class="btn btn-sm btn-primary mt-2">Add Location</a>
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
            $('#locationsTable').DataTable({
                order: [[0, 'asc']],
                pageLength: 25,
                responsive: true,
                language: { search: '', searchPlaceholder: 'Search locations...' }
            });
        });
    </script>
@endpush

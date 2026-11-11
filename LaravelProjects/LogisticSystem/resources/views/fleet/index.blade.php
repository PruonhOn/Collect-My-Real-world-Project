@extends('layouts.admin')

@section('title', 'Fleet - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item active" aria-current="page">Fleet</li>
@endsection

@section('page-title', 'Fleet Vehicles')
@section('page-subtitle', 'Manage your vehicle fleet')

@section('page-actions')
    <a href="{{ route('vehicles.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> Add Vehicle
    </a>
@endsection

@section('content')
    <div class="table-container">
        <table class="table table-hover datatable" id="vehiclesTable">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Plate #</th>
                    <th>Type</th>
                    <th>Make / Model</th>
                    <th>Capacity</th>
                    <th>Status</th>
                    <th>Branch</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vehicles as $vehicle)
                    <tr>
                        <td><span class="badge bg-secondary">{{ $vehicle->code }}</span></td>
                        <td class="fw-medium">{{ $vehicle->plate_number }}</td>
                        <td>{{ $vehicle->vehicleType?->name ?? '—' }}</td>
                        <td>{{ $vehicle->make }} {{ $vehicle->model }}</td>
                        <td>
                            <small>{{ number_format($vehicle->capacity_weight ?? 0, 1) }} kg</small>
                        </td>
                        <td>
                            @php
                                $statusClasses = [
                                    'active' => 'bg-success',
                                    'in-maintenance' => 'bg-warning text-dark',
                                    'out-of-service' => 'bg-danger',
                                    'retired' => 'bg-secondary',
                                ];
                                $class = $statusClasses[$vehicle->status] ?? 'bg-secondary';
                            @endphp
                            <span class="badge {{ $class }}">
                                {{ ucwords(str_replace('-', ' ', $vehicle->status ?? 'active')) }}
                            </span>
                        </td>
                        <td>{{ $vehicle->branch?->name ?? '—' }}</td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    Actions
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a href="{{ route('vehicles.show', $vehicle) }}" class="dropdown-item">
                                            <i class="bi bi-eye me-2"></i>View
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('vehicles.edit', $vehicle) }}" class="dropdown-item">
                                            <i class="bi bi-pencil me-2"></i>Edit
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('vehicle-maintenances.index', ['vehicle_id' => $vehicle->id]) }}"
                                           class="dropdown-item">
                                            <i class="bi bi-tools me-2"></i>Maintenance
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="{{ route('vehicles.destroy', $vehicle) }}" method="POST"
                                              onsubmit="return confirm('Delete this vehicle?')">
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
                            <i class="bi bi-truck fs-2 d-block mb-2"></i>
                            <p class="mb-0">No vehicles found</p>
                            <a href="{{ route('vehicles.create') }}" class="btn btn-sm btn-primary mt-2">Add Vehicle</a>
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
            $('#vehiclesTable').DataTable({
                order: [[0, 'asc']],
                pageLength: 25,
                responsive: true,
                language: { search: '', searchPlaceholder: 'Search vehicles...' }
            });
        });
    </script>
@endpush

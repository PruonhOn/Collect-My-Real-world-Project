@extends('layouts.admin')

@section('title', 'Vehicle Maintenance - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('vehicles.index') }}">Fleet</a></li>
    <li class="breadcrumb-item active" aria-current="page">Maintenance</li>
@endsection

@section('page-title', 'Vehicle Maintenance')
@section('page-subtitle', 'Track and manage vehicle maintenance records')

@section('page-actions')
    <a href="{{ route('vehicle-maintenances.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> Add Record
    </a>
@endsection

@section('content')
    <div class="table-container">
        <table class="table table-hover datatable" id="maintenanceTable">
            <thead>
                <tr>
                    <th>Reference #</th>
                    <th>Vehicle</th>
                    <th>Type</th>
                    <th>Date</th>
                    <th>Cost</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($maintenances as $maint)
                    <tr>
                        <td><span class="badge bg-secondary">{{ $maint->reference_number ?? $maint->id }}</span></td>
                        <td>
                            <a href="{{ route('vehicles.show', $maint->vehicle) }}" class="text-decoration-none">
                                {{ $maint->vehicle->plate_number ?? '—' }}
                            </a>
                        </td>
                        <td>{{ ucwords(str_replace('_', ' ', $maint->maintenance_type ?? '')) }}</td>
                        <td>{{ $maint->maintenance_date?->format('d M Y') ?? '—' }}</td>
                        <td>${{ number_format($maint->cost ?? 0, 2) }}</td>
                        <td>
                            @php
                                $msc = ['pending' => 'bg-warning text-dark', 'in-progress' => 'bg-info', 'completed' => 'bg-success', 'cancelled' => 'bg-secondary'];
                            @endphp
                            <span class="badge {{ $msc[$maint->status] ?? 'bg-secondary' }}">
                                {{ ucfirst($maint->status ?? 'pending') }}
                            </span>
                        </td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    Actions
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a href="{{ route('vehicle-maintenances.edit', $maint) }}" class="dropdown-item">
                                            <i class="bi bi-pencil me-2"></i>Edit
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="{{ route('vehicle-maintenances.destroy', $maint) }}" method="POST"
                                              onsubmit="return confirm('Delete this maintenance record?')">
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
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="bi bi-tools fs-2 d-block mb-2"></i>
                            <p class="mb-0">No maintenance records found</p>
                            <a href="{{ route('vehicle-maintenances.create') }}" class="btn btn-sm btn-primary mt-2">Add Record</a>
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
            $('#maintenanceTable').DataTable({
                order: [[3, 'desc']],
                pageLength: 25,
                responsive: true,
                language: { search: '', searchPlaceholder: 'Search maintenance...' }
            });
        });
    </script>
@endpush

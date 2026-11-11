@extends('layouts.admin')

@section('title', 'Drivers - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item active" aria-current="page">Drivers</li>
@endsection

@section('page-title', 'Drivers')
@section('page-subtitle', 'Manage driver profiles and assignments')

@section('page-actions')
    <a href="{{ route('drivers.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> Add Driver
    </a>
@endsection

@section('content')
    <div class="table-container">
        <table class="table table-hover datatable" id="driversTable">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>License #</th>
                    <th>License Type</th>
                    <th>Status</th>
                    <th>Branch</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($drivers as $driver)
                    <tr>
                        <td><span class="badge bg-secondary">{{ $driver->code }}</span></td>
                        <td>
                            <a href="{{ route('drivers.show', $driver) }}" class="text-decoration-none fw-medium">
                                {{ $driver->name }}
                            </a>
                        </td>
                        <td>{{ $driver->phone }}</td>
                        <td>{{ $driver->license_number ?? '—' }}</td>
                        <td>{{ $driver->license_type ?? '—' }}</td>
                        <td>
                            @php
                                $sc = ['available' => 'bg-success', 'on-delivery' => 'bg-info', 'off-duty' => 'bg-warning text-dark', 'on-leave' => 'bg-secondary', 'terminated' => 'bg-danger'];
                            @endphp
                            <span class="badge {{ $sc[$driver->status] ?? 'bg-secondary' }}">
                                {{ ucwords(str_replace('-', ' ', $driver->status ?? 'available')) }}
                            </span>
                        </td>
                        <td>{{ $driver->branch?->name ?? '—' }}</td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    Actions
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a href="{{ route('drivers.show', $driver) }}" class="dropdown-item">
                                            <i class="bi bi-eye me-2"></i>View
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('drivers.edit', $driver) }}" class="dropdown-item">
                                            <i class="bi bi-pencil me-2"></i>Edit
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="{{ route('drivers.destroy', $driver) }}" method="POST"
                                              onsubmit="return confirm('Delete this driver?')">
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
                            <i class="bi bi-person-badge fs-2 d-block mb-2"></i>
                            <p class="mb-0">No drivers found</p>
                            <a href="{{ route('drivers.create') }}" class="btn btn-sm btn-primary mt-2">Add Driver</a>
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
            $('#driversTable').DataTable({
                order: [[0, 'asc']],
                pageLength: 25,
                responsive: true,
                language: { search: '', searchPlaceholder: 'Search drivers...' }
            });
        });
    </script>
@endpush

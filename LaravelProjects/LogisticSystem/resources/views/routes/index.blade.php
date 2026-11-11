@extends('layouts.admin')

@section('title', 'Routes - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Routes</li>
@endsection

@section('page-title', 'Routes')
@section('page-subtitle', 'Manage delivery routes')

@section('page-actions')
    <a href="{{ route('routes.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> New Route
    </a>
@endsection

@section('content')
    <div class="table-container">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="routesTable">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Origin → Destination</th>
                        <th>Distance</th>
                        <th>Est. Hours</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($routes as $route)
                        <tr>
                            <td><span class="badge bg-secondary">{{ $route->code }}</span></td>
                            <td>
                                <a href="{{ route('routes.show', $route) }}" class="text-decoration-none fw-medium">{{ $route->name }}</a>
                            </td>
                            <td>
                                <i class="bi bi-geo-alt text-primary"></i> {{ $route->origin_city }}
                                <i class="bi bi-arrow-right mx-1 text-muted"></i>
                                <i class="bi bi-geo-alt text-danger"></i> {{ $route->destination_city }}
                            </td>
                            <td>{{ number_format($route->distance_km, 1) }} km</td>
                            <td>{{ number_format($route->estimated_hours, 1) }}h</td>
                            <td>
                                @if($route->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('routes.show', $route) }}" class="btn btn-outline-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('routes.edit', $route) }}" class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger" title="Delete"
                                            onclick="confirmDelete({{ $route->id }})">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                                <form id="delete-form-{{ $route->id }}" action="{{ route('routes.destroy', $route) }}" method="POST" class="d-none">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-signpost-2 fs-1 d-block mb-2"></i>
                                <p class="mb-0">No routes found</p>
                                <a href="{{ route('routes.create') }}" class="btn btn-sm btn-primary mt-2">Create Route</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($routes, 'links'))
            <div class="d-flex justify-content-end mt-2">
                {{ $routes->links() }}
            </div>
        @endif
    </div>
@endsection

@section('scripts')
    <script>
        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this route?')) {
                document.getElementById('delete-form-' + id).submit();
            }
        }

        @if(count($routes) > 5)
            $(document).ready(function() {
                $('#routesTable').DataTable({
                    order: [[1, 'asc']],
                    pageLength: 25,
                    language: { search: '', searchPlaceholder: 'Search routes...' }
                });
            });
        @endif
    </script>
@endsection

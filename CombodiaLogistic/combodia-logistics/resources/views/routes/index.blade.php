@extends('layouts.admin')

@section('title', 'Routes')
@section('subtitle', 'Manage all routes')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Routes</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Routes</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('routes.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> New Route
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="dataTable">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Start Location</th>
                        <th>End Location</th>
                        <th>Distance</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($routes as $route)
                    <tr>
                        <td><code class="bg-light px-2 py-1">{{ $route->code }}</code></td>
                        <td><a href="{{ route('routes.show', $route) }}" class="fw-semibold text-decoration-none">{{ $route->name }}</a></td>
                        <td>{{ $route->start_location }}</td>
                        <td>{{ $route->end_location }}</td>
                        <td>{{ number_format($route->distance ?? 0, 1) }} km</td>
                        <td>{{ $route->duration ?? '-' }}</td>
                        <td>
                            @if($route->status === 'active')
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('routes.show', $route) }}"><i class="bi bi-eye me-2"></i>View</a></li>
                                    <li><a class="dropdown-item" href="{{ route('routes.edit', $route) }}"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="#" onclick="deleteRoute({{ $route->id }})"><i class="bi bi-trash me-2"></i>Delete</a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                            <p class="mt-2">No routes found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<style>
    .table th { font-weight: 600; font-size: 0.813rem; text-transform: uppercase; letter-spacing: 0.5px; color: #6c757d; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        $('#dataTable').DataTable({
            order: [[0, 'desc']],
            pageLength: 25,
            responsive: true
        });
    });

    function deleteRoute(id) {
        Swal.fire({
            title: 'Delete Route?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, Delete'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/admin/routes/' + id;
                form.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="DELETE">';
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
</script>
@endpush

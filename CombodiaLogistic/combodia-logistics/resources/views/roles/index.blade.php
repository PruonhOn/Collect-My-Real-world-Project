@extends('layouts.admin')

@section('title', 'Roles')
@section('subtitle', 'Manage system roles and permissions')

@section('content')
<x-breadcrumb :items="[['label' => 'Roles']]" />

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Roles</h4>
        <p class="text-muted mb-0">Manage roles and their assigned permissions</p>
    </div>
    <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Add Role
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table id="rolesTable" class="table table-bordered table-striped w-100">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Guard</th>
                        <th>Permissions</th>
                        <th>Users</th>
                        <th>Created</th>
                        <th class="text-center" style="width: 150px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($roles as $role)
                    <tr>
                        <td>{{ $role->id }}</td>
                        <td>
                            <span class="fw-medium">{{ $role->name }}</span>
                        </td>
                        <td><span class="badge bg-light text-dark border">{{ $role->guard_name }}</span></td>
                        <td>
                            <span class="badge bg-primary bg-opacity-10 text-primary">{{ $role->permissions->count() }} permissions</span>
                        </td>
                        <td>
                            <span class="badge bg-info bg-opacity-10 text-info">{{ $role->users->count() }} users</span>
                        </td>
                        <td>{{ $role->created_at->format('d M Y') }}</td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.roles.edit', $role->id) }}" class="btn btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('admin.roles.destroy', $role->id) }}" method="POST" class="d-inline delete-form">
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
        $('#rolesTable').DataTable({
            responsive: true,
            order: [[0, 'desc']],
            language: {
                search: '<i class="bi bi-search"></i>',
                searchPlaceholder: 'Search roles...',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ roles',
                emptyTable: 'No roles found',
                paginate: { previous: '<i class="bi bi-chevron-left"></i>', next: '<i class="bi bi-chevron-right"></i>' }
            }
        });

        $('.delete-form').on('submit', function(e) {
            e.preventDefault();
            const form = this;
            Swal.fire({
                title: 'Are you sure?',
                text: 'This role will be permanently deleted.',
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

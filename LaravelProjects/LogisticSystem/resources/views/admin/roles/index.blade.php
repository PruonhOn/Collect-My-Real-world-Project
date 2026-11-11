<x-admin-layout>
    @section('title', 'Manage Roles - ' . config('app.name'))

    @section('breadcrumbs')
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
        <li class="breadcrumb-item active" aria-current="page">Roles</li>
    @endsection

    @section('page-title', 'Manage Roles')
    @section('page-subtitle', 'Define and manage user roles and permissions')

    @section('page-actions')
        <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Create Role
        </a>
    @endsection

    <div class="table-container">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="rolesTable">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Description</th>
                        <th>Permissions</th>
                        <th>Users</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles ?? [] as $role)
                        <tr>
                            <td>
                                <span class="fw-medium">{{ $role->name }}</span>
                                @if($role->is_system_role)
                                    <span class="badge bg-warning bg-opacity-10 text-warning ms-1">System</span>
                                @endif
                            </td>
                            <td><code>{{ $role->slug }}</code></td>
                            <td><small class="text-muted">{{ Str::limit($role->description, 50) ?? '—' }}</small></td>
                            <td>
                                <span class="badge bg-primary bg-opacity-10 text-primary">{{ $role->permissions_count ?? $role->permissions()->count() }} permissions</span>
                            </td>
                            <td>
                                <span class="badge bg-info bg-opacity-10 text-info">{{ $role->users_count ?? $role->users()->count() }} users</span>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary border-0" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.roles.edit', $role) }}">
                                                <i class="bi bi-pencil me-2"></i>Edit
                                            </a>
                                        </li>
                                        @unless($role->is_system_role)
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" onsubmit="return confirm('Are you sure? This will affect all users with this role.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="bi bi-trash me-2"></i>Delete
                                                    </button>
                                                </form>
                                            </li>
                                        @endunless
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="bi bi-shield fs-1 d-block mb-2"></i>
                                <p class="mb-0">No roles found</p>
                                <a href="{{ route('admin.roles.create') }}" class="btn btn-sm btn-primary mt-2">Create First Role</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($roles ?? [], 'links'))
            <div class="p-3 border-top">
                {{ $roles->links() }}
            </div>
        @endif
    </div>

    @section('scripts')
    <script>
        $(document).ready(function() {
            if ($.fn.DataTable) {
                $('#rolesTable').DataTable({
                    pageLength: 25,
                    order: [[0, 'asc']],
                    columnDefs: [{ orderable: false, targets: [5] }],
                    language: { searchPlaceholder: 'Search roles...' }
                });
            }
        });
    </script>
    @endsection
</x-admin-layout>

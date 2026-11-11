<x-admin-layout>
    @section('title', 'Manage Users - ' . config('app.name'))

    @section('breadcrumbs')
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
        <li class="breadcrumb-item active" aria-current="page">Users</li>
    @endsection

    @section('page-title', 'Manage Users')
    @section('page-subtitle', 'View and manage system users')

    @section('page-actions')
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
            <i class="bi bi-person-plus me-1"></i> Create User
        </a>
    @endsection

    <div class="table-container">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="usersTable">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Roles</th>
                        <th>Branches</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users ?? [] as $user)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center text-primary fw-bold me-2" style="width: 36px; height: 36px; font-size: 14px;">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <a href="{{ route('admin.users.show', $user) }}" class="text-decoration-none fw-medium">{{ $user->name }}</a>
                                        <small class="d-block text-muted">@if($user->is_super_admin) Super Admin @endif</small>
                                    </div>
                                </div>
                            </td>
                            <td><small>{{ $user->email }}</small></td>
                            <td><small>{{ $user->phone ?? '—' }}</small></td>
                            <td>
                                @forelse($user->roles->take(2) as $role)
                                    <span class="badge bg-primary bg-opacity-10 text-primary">{{ $role->name }}</span>
                                @empty
                                    <span class="text-muted">—</span>
                                @endforelse
                                @if($user->roles->count() > 2)
                                    <span class="badge bg-secondary">+{{ $user->roles->count() - 2 }}</span>
                                @endif
                            </td>
                            <td>
                                @forelse($user->branches->take(2) as $branch)
                                    <span class="badge bg-info bg-opacity-10 text-info">{{ $branch->name }}</span>
                                @empty
                                    <span class="text-muted">All Branches</span>
                                @endforelse
                                @if($user->branches->count() > 2)
                                    <span class="badge bg-secondary">+{{ $user->branches->count() - 2 }}</span>
                                @endif
                            </td>
                            <td>
                                @if($user->is_active)
                                    <span class="badge bg-success bg-opacity-10 text-success">Active</span>
                                @else
                                    <span class="badge bg-danger bg-opacity-10 text-danger">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary border-0" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.users.show', $user) }}">
                                                <i class="bi bi-eye me-2"></i>View
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.users.edit', $user) }}">
                                                <i class="bi bi-pencil me-2"></i>Edit
                                            </a>
                                        </li>
                                        @if($user->id !== Auth::id())
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this user?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="bi bi-trash me-2"></i>Delete
                                                    </button>
                                                </form>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="bi bi-people fs-1 d-block mb-2"></i>
                                <p class="mb-0">No users found</p>
                                <a href="{{ route('admin.users.create') }}" class="btn btn-sm btn-primary mt-2">Create First User</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($users ?? [], 'links'))
            <div class="p-3 border-top">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    @section('scripts')
    <script>
        $(document).ready(function() {
            if ($.fn.DataTable) {
                $('#usersTable').DataTable({
                    pageLength: 25,
                    order: [[0, 'asc']],
                    columnDefs: [{ orderable: false, targets: [6] }],
                    language: { searchPlaceholder: 'Search users...' }
                });
            }
        });
    </script>
    @endsection
</x-admin-layout>

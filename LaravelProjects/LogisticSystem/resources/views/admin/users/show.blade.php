<x-admin-layout>
    @section('title', 'User Details - ' . config('app.name'))

    @section('breadcrumbs')
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $user->name }}</li>
    @endsection

    @section('page-title', $user->name)
    @section('page-subtitle', 'User details and activity')

    @section('page-actions')
        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary">
            <i class="bi bi-pencil me-1"></i> Edit User
        </a>
    @endsection

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="mx-auto rounded-circle bg-primary d-flex align-items-center justify-content-center text-white fw-bold mb-3" style="width: 80px; height: 80px; font-size: 32px;">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <h5 class="mb-1">{{ $user->name }}</h5>
                    <p class="text-muted mb-2">{{ $user->email }}</p>
                    @if($user->phone)
                        <p class="mb-2"><i class="bi bi-telephone me-1"></i>{{ $user->phone }}</p>
                    @endif
                    <div>
                        @if($user->is_active)
                            <span class="badge bg-success bg-opacity-10 text-success fs-6 px-3 py-2">Active</span>
                        @else
                            <span class="badge bg-danger bg-opacity-10 text-danger fs-6 px-3 py-2">Inactive</span>
                        @endif
                        @if($user->is_super_admin)
                            <span class="badge bg-warning bg-opacity-10 text-warning fs-6 px-3 py-2 ms-1">Super Admin</span>
                        @endif
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <small class="text-muted d-block">Created: {{ $user->created_at->format('M d, Y \a\t h:i A') }}</small>
                    <small class="text-muted d-block">Last Updated: {{ $user->updated_at->format('M d, Y \a\t h:i A') }}</small>
                    @if($user->last_login_at)
                        <small class="text-muted d-block">Last Login: {{ $user->last_login_at->format('M d, Y \a\t h:i A') }}</small>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-shield-check me-1"></i>Assigned Roles</h6>
                </div>
                <div class="card-body">
                    @forelse($user->roles as $role)
                        <span class="badge bg-primary fs-6 px-3 py-2 me-1 mb-1">{{ $role->name }}</span>
                    @empty
                        <p class="text-muted mb-0">No roles assigned.</p>
                    @endforelse
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-building me-1"></i>Assigned Branches</h6>
                </div>
                <div class="card-body">
                    @forelse($user->branches as $branch)
                        <span class="badge bg-info fs-6 px-3 py-2 me-1 mb-1">{{ $branch->name }}</span>
                    @empty
                        <p class="text-muted mb-0">Access to all branches.</p>
                    @endforelse
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-activity me-1"></i>Recent Activity</h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($activities ?? $user->activities()->latest()->take(10)->get() ?? [] as $activity)
                            <div class="list-group-item">
                                <div class="d-flex align-items-start">
                                    <div class="rounded-circle bg-{{ $activity->color ?? 'primary' }} bg-opacity-10 d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 34px; height: 34px;">
                                        <i class="bi bi-{{ $activity->icon ?? 'circle' }} text-{{ $activity->color ?? 'primary' }} small"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <p class="mb-0 small">{{ $activity->description }}</p>
                                        <small class="text-muted">{{ $activity->created_at->diffForHumans() }}</small>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="list-group-item text-center text-muted py-4">
                                <i class="bi bi-activity fs-3 d-block mb-2"></i>
                                <p class="mb-0">No activity recorded yet</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>

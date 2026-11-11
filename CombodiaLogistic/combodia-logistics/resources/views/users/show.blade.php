@extends('layouts.admin')

@section('title', 'User Details')
@section('subtitle', 'View user profile')

@section('content')
<x-breadcrumb :items="[['label' => 'Users', 'url' => route('admin.users.index')], ['label' => 'User Details']]" />

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">User Details</h4>
        <p class="text-muted mb-0">Profile information for {{ $user->name }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-primary">
            <i class="bi bi-pencil me-1"></i> Edit User
        </a>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to List
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body text-center py-4">
                <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px;">
                    @if($user->avatar ?? null)
                        <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}" class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover;">
                    @else
                        <i class="bi bi-person-fill text-primary" style="font-size: 2rem;"></i>
                    @endif
                </div>
                <h5 class="fw-bold mb-1">{{ $user->name }}</h5>
                <p class="text-muted mb-2">{{ $user->email }}</p>
                <div class="mb-3">
                    @forelse($user->roles as $role)
                        <span class="badge bg-primary me-1">{{ $role->name }}</span>
                    @empty
                        <span class="badge bg-secondary">No Role</span>
                    @endforelse
                </div>
                <div>
                    @if($user->is_active ?? true)
                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Active</span>
                    @else
                        <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Inactive</span>
                    @endif
                </div>
            </div>
            <div class="card-footer bg-white border-top">
                <div class="d-flex justify-content-between text-muted" style="font-size: 0.85rem;">
                    <span><i class="bi bi-calendar me-1"></i> Joined {{ $user->created_at->format('d M Y') }}</span>
                    <span><i class="bi bi-clock me-1"></i> {{ $user->created_at->diffForHumans() }}</span>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0 fw-bold"><i class="bi bi-activity me-2"></i>Activity Stats</h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                    <div>
                        <h6 class="mb-0" style="font-size: 0.875rem;">Last Login</h6>
                        <small class="text-muted">{{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}</small>
                    </div>
                    <i class="bi bi-box-arrow-in-right text-muted"></i>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                    <div>
                        <h6 class="mb-0" style="font-size: 0.875rem;">Last Activity</h6>
                        <small class="text-muted">{{ $user->last_activity_at ? $user->last_activity_at->diffForHumans() : 'N/A' }}</small>
                    </div>
                    <i class="bi bi-activity text-muted"></i>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                    <div>
                        <h6 class="mb-0" style="font-size: 0.875rem;">Total Logins</h6>
                        <small class="text-muted">{{ $user->total_logins ?? 0 }} times</small>
                    </div>
                    <i class="bi bi-arrow-counterclockwise text-muted"></i>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0" style="font-size: 0.875rem;">Account Status</h6>
                        <small class="text-muted">{{ ($user->is_active ?? true) ? 'Active' : 'Inactive' }}</small>
                    </div>
                    <i class="bi bi-shield-check text-muted"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0 fw-bold"><i class="bi bi-person-lines-fill me-2"></i>Personal Information</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted" style="font-size: 0.8rem;">Full Name</label>
                        <p class="fw-medium mb-0">{{ $user->name }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted" style="font-size: 0.8rem;">Email</label>
                        <p class="fw-medium mb-0">{{ $user->email }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted" style="font-size: 0.8rem;">Phone</label>
                        <p class="fw-medium mb-0">{{ $user->phone ?? '-' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted" style="font-size: 0.8rem;">Username</label>
                        <p class="fw-medium mb-0">{{ $user->username ?? '-' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted" style="font-size: 0.8rem;">Gender</label>
                        <p class="fw-medium mb-0">{{ ucfirst($user->gender ?? '-') }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted" style="font-size: 0.8rem;">Position</label>
                        <p class="fw-medium mb-0">{{ $user->position ?? '-' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted" style="font-size: 0.8rem;">Employee ID</label>
                        <p class="fw-medium mb-0">{{ $user->employee_id ?? '-' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted" style="font-size: 0.8rem;">Email Verified</label>
                        <p class="mb-0">
                            @if($user->email_verified_at)
                                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Verified</span>
                            @else
                                <span class="badge bg-warning"><i class="bi bi-clock me-1"></i>Not Verified</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0 fw-bold"><i class="bi bi-shield-lock me-2"></i>Assigned Roles & Permissions</h6>
            </div>
            <div class="card-body">
                @forelse($user->roles as $role)
                    <div class="mb-3 pb-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge bg-primary me-2">{{ $role->name }}</span>
                            <small class="text-muted">Guard: {{ $role->guard_name }}</small>
                        </div>
                        @if($role->permissions->count() > 0)
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($role->permissions->take(10) as $permission)
                                    <span class="badge bg-light text-dark border">{{ $permission->name }}</span>
                                @endforeach
                                @if($role->permissions->count() > 10)
                                    <span class="badge bg-secondary">+{{ $role->permissions->count() - 10 }} more</span>
                                @endif
                            </div>
                        @else
                            <small class="text-muted">No permissions assigned</small>
                        @endif
                    </div>
                @empty
                    <div class="text-center py-3">
                        <i class="bi bi-shield-x text-muted" style="font-size: 2rem;"></i>
                        <p class="text-muted mt-2 mb-0">No roles assigned to this user</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

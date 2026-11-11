<x-admin-layout>
    @section('title', 'Edit User - ' . config('app.name'))

    @section('breadcrumbs')
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
        <li class="breadcrumb-item active" aria-current="page">Edit {{ $user->name }}</li>
    @endsection

    @section('page-title', 'Edit User')
    @section('page-subtitle', 'Update user information and permissions')

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.users.update', $user) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="+855 12 345 678">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="Leave blank to keep current">
                        <small class="text-muted">Leave empty to keep the current password.</small>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="password_confirmation" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Confirm new password">
                    </div>

                    <div class="col-12">
                        <hr>
                        <h6 class="fw-bold mb-3"><i class="bi bi-shield-check me-1"></i>Assign Roles</h6>
                        <div class="row g-2">
                            @forelse($roles ?? [] as $role)
                                <div class="col-md-4 col-lg-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->id }}" id="role_{{ $role->id }}"
                                            {{ in_array($role->id, old('roles', $user->roles->pluck('id')->toArray())) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="role_{{ $role->id }}">
                                            {{ $role->name }}
                                            <small class="text-muted d-block">{{ $role->description ?? '' }}</small>
                                        </label>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12">
                                    <p class="text-muted mb-0">No roles available. <a href="{{ route('admin.roles.create') }}">Create one</a>.</p>
                                </div>
                            @endforelse
                        </div>
                        @error('roles')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <hr>
                        <h6 class="fw-bold mb-3"><i class="bi bi-building me-1"></i>Assign Branches</h6>
                        @if(auth()->user()->is_super_admin)
                            <div class="mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="allBranches" onchange="toggleAllBranches(this)"
                                        {{ count(old('branches', $user->branches->pluck('id')->toArray())) === ($branches->count() ?? 0) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-medium" for="allBranches">All Branches</label>
                                </div>
                            </div>
                            <div class="row g-2" id="branchCheckboxes">
                                @forelse($branches ?? [] as $branch)
                                    <div class="col-md-4 col-lg-3">
                                        <div class="form-check">
                                            <input class="form-check-input branch-checkbox" type="checkbox" name="branches[]" value="{{ $branch->id }}" id="branch_{{ $branch->id }}"
                                                {{ in_array($branch->id, old('branches', $user->branches->pluck('id')->toArray())) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="branch_{{ $branch->id }}">
                                                {{ $branch->name }}
                                                <small class="text-muted d-block">{{ $branch->location ?? '' }}</small>
                                            </label>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12">
                                        <p class="text-muted mb-0">No branches available.</p>
                                    </div>
                                @endforelse
                            </div>
                        @else
                            <p class="text-muted mb-0">User will have access to all branches by default.</p>
                            <input type="hidden" name="branches" value="">
                        @endif
                        @error('branches')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <hr>
                        <h6 class="fw-bold mb-3"><i class="bi bi-toggle-on me-1"></i>Account Status</h6>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Active Account</label>
                            <small class="d-block text-muted">Inactive users cannot log in to the system.</small>
                        </div>
                        @error('is_active')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Update User
                    </button>
                </div>
            </form>
        </div>
    </div>

    @section('scripts')
    <script>
        function toggleAllBranches(source) {
            document.querySelectorAll('.branch-checkbox').forEach(cb => cb.checked = source.checked);
        }
    </script>
    @endsection
</x-admin-layout>

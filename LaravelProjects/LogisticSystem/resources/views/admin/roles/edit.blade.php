<x-admin-layout>
    @section('title', 'Edit Role - ' . config('app.name'))

    @section('breadcrumbs')
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.roles.index') }}">Roles</a></li>
        <li class="breadcrumb-item active" aria-current="page">Edit {{ $role->name }}</li>
    @endsection

    @section('page-title', 'Edit Role')
    @section('page-subtitle', 'Update role name, description, and permissions')

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.roles.update', $role) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Role Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $role->name) }}" required>
                        <small class="text-muted">e.g., Warehouse Manager, Fleet Supervisor</small>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="slug" class="form-label">Slug <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('slug') is-invalid @enderror" id="slug" name="slug" value="{{ old('slug', $role->slug) }}" required placeholder="warehouse-manager">
                        <small class="text-muted">Lowercase, hyphenated. Used internally.</small>
                        @error('slug')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="2">{{ old('description', $role->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <hr class="my-4">
                <h6 class="fw-bold mb-3"><i class="bi bi-key me-1"></i>Permissions</h6>

                <div class="row g-3">
                    @forelse($permissionGroups ?? [] as $group => $permissions)
                        @php
                            $groupChecked = true;
                            $permIds = $role->permissions->pluck('id')->toArray();
                        @endphp
                        <div class="col-md-6 col-lg-4">
                            <div class="card border h-100">
                                <div class="card-header py-2 bg-light">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <span class="fw-medium small text-uppercase">{{ $group }}</span>
                                        <div class="form-check">
                                            <input class="form-check-input group-checkbox" type="checkbox" id="group_{{ Str::slug($group) }}" onchange="toggleGroup(this, '{{ Str::slug($group) }}')">
                                            <label class="form-check-label small" for="group_{{ Str::slug($group) }}">All</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body py-2">
                                    @foreach($permissions as $permission)
                                        @php $checked = in_array($permission->id ?? $permission, old('permissions', $permIds)); @endphp
                                        <div class="form-check mb-1">
                                            <input class="form-check-input permission-checkbox group-{{ Str::slug($group) }}" type="checkbox" name="permissions[]" value="{{ $permission->id ?? $permission }}" id="perm_{{ $permission->id ?? Str::slug($permission) }}"
                                                {{ $checked ? 'checked' : '' }}>
                                            <label class="form-check-label small" for="perm_{{ $permission->id ?? Str::slug($permission) }}">
                                                {{ $permission->label ?? $permission->name ?? $permission }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle me-1"></i> No permission groups defined. Permissions are managed through the system configuration.
                            </div>
                        </div>
                    @endforelse
                </div>
                @error('permissions')
                    <div class="text-danger small mt-2">{{ $message }}</div>
                @enderror

                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                    <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Update Role
                    </button>
                </div>
            </form>
        </div>
    </div>

    @section('scripts')
    <script>
        document.getElementById('name').addEventListener('input', function() {
            const slug = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
            if (document.getElementById('slug').dataset.autogenerated !== 'false') {
                document.getElementById('slug').value = slug;
            }
        });

        function toggleGroup(source, group) {
            document.querySelectorAll('.group-' + group).forEach(cb => cb.checked = source.checked);
        }
    </script>
    @endsection
</x-admin-layout>

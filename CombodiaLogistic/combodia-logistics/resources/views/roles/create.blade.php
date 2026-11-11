@extends('layouts.admin')

@section('title', 'Create Role')
@section('subtitle', 'Add a new role with permissions')

@section('content')
<x-breadcrumb :items="[['label' => 'Roles', 'url' => route('admin.roles.index')], ['label' => 'Create Role']]" />

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Create Role</h4>
        <p class="text-muted mb-0">Define a new role and assign permissions</p>
    </div>
    <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to List
    </a>
</div>

<form action="{{ route('admin.roles.store') }}" method="POST">
    @csrf

    <div class="row">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-shield me-2"></i>Role Information</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Role Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="e.g. Manager" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">A unique name for this role</small>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> Create Role
                </button>
                <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg me-1"></i> Cancel
                </a>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-key me-2"></i>Permissions</h6>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="selectAll">
                        <label class="form-check-label fw-medium" for="selectAll" style="font-size: 0.85rem;">Select All</label>
                    </div>
                </div>
                <div class="card-body">
                    @error('permissions')
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-circle me-2"></i>{{ $message }}
                        </div>
                    @enderror

                    @php
                        $groupedPermissions = $permissions->groupBy(function($p) {
                            $parts = explode('.', $p->name);
                            return ucfirst($parts[0]);
                        });
                    @endphp

                    <div class="accordion" id="permissionsAccordion">
                        @foreach($groupedPermissions as $module => $modulePermissions)
                            <div class="accordion-item border-0 mb-2">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed fw-medium" type="button" data-bs-toggle="collapse" data-bs-target="#module{{ $loop->iteration }}">
                                        <div class="form-check me-2">
                                            <input class="form-check-input module-check" type="checkbox" data-module="{{ $loop->iteration }}" onclick="event.stopPropagation()">
                                        </div>
                                        <i class="bi bi-grid me-2 text-primary"></i>
                                        {{ $module }} ({{ $modulePermissions->count() }})
                                    </button>
                                </h2>
                                <div id="module{{ $loop->iteration }}" class="accordion-collapse collapse" data-bs-parent="#permissionsAccordion">
                                    <div class="accordion-body bg-light bg-opacity-50">
                                        <div class="row g-2">
                                            @foreach($modulePermissions as $permission)
                                                <div class="col-md-6 col-lg-4">
                                                    <div class="form-check">
                                                        <input class="form-check-input permission-check" type="checkbox" name="permissions[]" value="{{ $permission->id }}" id="perm{{ $permission->id }}" {{ in_array($permission->id, old('permissions', [])) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="perm{{ $permission->id }}" style="font-size: 0.85rem;">
                                                            {{ $permission->name }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.permission-check');
        checkboxes.forEach(cb => cb.checked = this.checked);
    });

    document.querySelectorAll('.module-check').forEach(moduleCheck => {
        moduleCheck.addEventListener('change', function() {
            const module = this.dataset.module;
            const collapse = document.getElementById('module' + module);
            const checkboxes = collapse.querySelectorAll('.permission-check');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });
    });
</script>
@endpush

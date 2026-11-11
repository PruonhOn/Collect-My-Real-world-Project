@extends('layouts.app')

@section('title', 'My Profile')
@section('heading', 'My Profile')
@section('subheading', 'Update your profile and password')

@section('content')
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header bg-transparent"><h6 class="mb-0 fw-semibold"><i class="bi bi-person me-2"></i>Profile Information</h6></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('PATCH')

                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control @error('name', 'updateProfile') is-invalid @enderror" required>
                            @error('name', 'updateProfile')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control @error('email', 'updateProfile') is-invalid @enderror" required>
                            @error('email', 'updateProfile')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <span class="badge {{ $user->role->value === 'admin' ? 'bg-primary-subtle text-primary' : ($user->role->value === 'manager' ? 'bg-info-subtle text-info' : 'bg-secondary-subtle text-secondary') }}">{{ $user->roleLabel() }}</span>
                        </div>

                        @if (session('status') === 'profile-updated')
                            <div class="alert alert-success alert-soft py-2">Saved.</div>
                        @endif

                        <button type="submit" class="btn btn-primary rounded-pill px-4"><i class="bi bi-check-lg me-1"></i>Save</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card mb-3">
                <div class="card-header bg-transparent"><h6 class="mb-0 fw-semibold"><i class="bi bi-shield-lock me-2"></i>Update Password</h6></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('password.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">Current password</label>
                            <input type="password" name="current_password" class="form-control @error('current_password', 'updatePassword') is-invalid @enderror" required>
                            @error('current_password', 'updatePassword')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">New password</label>
                            <input type="password" name="password" class="form-control @error('password', 'updatePassword') is-invalid @enderror" required>
                            @error('password', 'updatePassword')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Confirm new password</label>
                            <input type="password" name="password_confirmation" class="form-control" required>
                        </div>

                        @if (session('status') === 'password-updated')
                            <div class="alert alert-success alert-soft py-2">Saved.</div>
                        @endif

                        <button type="submit" class="btn btn-primary rounded-pill px-4"><i class="bi bi-check-lg me-1"></i>Save</button>
                    </form>
                </div>
            </div>

            <div class="card border-danger-subtle">
                <div class="card-header bg-transparent"><h6 class="mb-0 fw-semibold text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Delete Account</h6></div>
                <div class="card-body">
                    <p class="small text-muted">Once your account is deleted, all of its resources and data will be permanently deleted. Please confirm your password to continue.</p>
                    <button class="btn btn-outline-danger rounded-pill" data-bs-toggle="modal" data-bs-target="#deleteAccountModal"><i class="bi bi-trash me-1"></i>Delete Account</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('modals')
    <div class="modal fade" id="deleteAccountModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('profile.destroy') }}" class="modal-content">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title text-danger">Delete Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-0">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control @error('password', 'userDeletion') is-invalid @enderror" required>
                        @error('password', 'userDeletion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger rounded-pill">Delete Account</button>
                </div>
            </form>
        </div>
    </div>
@endsection

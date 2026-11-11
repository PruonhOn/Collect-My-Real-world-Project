@extends('layouts.guest')

@section('title', 'Login')

@section('content')
<div class="login-page">
    <div class="login-card">
        <div class="login-logo">
            <i class="bi bi-box-seam" style="font-size: 3rem; color: var(--primary-color);"></i>
            <h1>Combodia Logistics</h1>
            <p>Sign in to your account</p>
        </div>

        <form method="POST" action="{{ route('login') }}" class="login-form" id="loginForm">
            @csrf

            @if($errors->any())
                <div class="alert alert-danger py-2" role="alert" style="font-size: 0.85rem;">
                    <i class="bi bi-exclamation-circle me-1"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text" style="background: transparent; border-right: none;">
                        <i class="bi bi-envelope" style="color: #6c757d;"></i>
                    </span>
                    <input type="email"
                           class="form-control @error('email') is-invalid @enderror"
                           id="email"
                           name="email"
                           value="{{ old('email') }}"
                           placeholder="Enter your email"
                           required
                           autofocus
                           style="border-left: none;">
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text" style="background: transparent; border-right: none;">
                        <i class="bi bi-lock" style="color: #6c757d;"></i>
                    </span>
                    <input type="password"
                           class="form-control @error('password') is-invalid @enderror"
                           id="password"
                           name="password"
                           placeholder="Enter your password"
                           required
                           style="border-left: none;">
                    <button class="btn btn-outline-secondary" type="button" id="togglePassword" style="border-left: none; background: transparent;">
                        <i class="bi bi-eye" id="togglePasswordIcon"></i>
                    </button>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label class="form-check-label" for="remember" style="font-size: 0.875rem;">
                        Remember me
                    </label>
                </div>
                @if(Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-decoration-none" style="font-size: 0.875rem; color: var(--primary-color);">
                        Forgot password?
                    </a>
                @endif
            </div>

            <button type="submit" class="btn btn-primary btn-login w-100" id="loginBtn">
                <span id="loginText">Sign In</span>
                <span id="loginSpinner" class="d-none">
                    <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                    Signing in...
                </span>
            </button>
        </form>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('loginForm').addEventListener('submit', function() {
        document.getElementById('loginText').classList.add('d-none');
        document.getElementById('loginSpinner').classList.remove('d-none');
        document.getElementById('loginBtn').disabled = true;
    });

    document.getElementById('togglePassword').addEventListener('click', function() {
        const password = document.getElementById('password');
        const icon = document.getElementById('togglePasswordIcon');
        if (password.type === 'password') {
            password.type = 'text';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            password.type = 'password';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    });
</script>
@endpush
@endsection

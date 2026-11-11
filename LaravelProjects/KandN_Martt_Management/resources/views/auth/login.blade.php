@php
    $hideSidebar = true;
    $hideTopbar = true;
@endphp

@extends('layouts.app')

@section('title', 'Login - K&N Mart')

@section('content')
<div class="min-vh-100 d-flex align-items-center justify-content-center bg-light">
    <div class="card shadow-sm border-0" style="width: 420px;">
        <div class="card-body p-4">
            <div class="text-center mb-4">
                <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:64px;height:64px;">
                    <i class="bi bi-shop text-white fs-2"></i>
                </div>
                <h4 class="fw-bold">K&N Mart</h4>
                <p class="text-muted small">Sign in to your account</p>
            </div>

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required autofocus>
                        @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="Enter your password" required>
                        @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                        <label class="form-check-label" for="remember">Remember Me</label>
                    </div>
                    @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="small">Forgot Password?</a>
                    @endif
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

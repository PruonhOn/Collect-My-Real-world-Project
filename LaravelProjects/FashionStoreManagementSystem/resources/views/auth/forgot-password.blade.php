@extends('layouts.guest')

@section('title', 'Forgot password')

@section('content')
    <div class="auth-card">
        <div class="auth-brand text-center mb-4">
            <div class="auth-icon mx-auto mb-2"><i class="bi bi-key-fill"></i></div>
            <h1 class="h4 mb-1">Forgot your password?</h1>
            <p class="text-muted mb-0">We'll email you a password reset link.</p>
        </div>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <div class="mb-3 text-muted small">
                    No problem. Just tell us your account email address and we will email you a password reset link.
                </div>

                @if (session('status'))
                    <div class="alert alert-success alert-soft">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}"
                               class="form-control form-control-lg @error('email') is-invalid @enderror"
                               required autofocus>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill">Email password reset link</button>
                </form>
            </div>
        </div>

        <p class="text-center mt-4">
            <a href="{{ route('login') }}" class="small"><i class="bi bi-arrow-left"></i> Back to sign in</a>
        </p>
    </div>
@endsection

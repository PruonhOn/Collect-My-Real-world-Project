@extends('layouts.guest')

@section('title', 'Sign in')

@section('content')
    <div class="auth-card">
        <div class="auth-brand text-center mb-4">
            @if (store_logo_url())
                <img src="{{ store_logo_url() }}" alt="{{ store_name() }}" class="auth-logo mb-2">
            @else
                <div class="auth-icon mx-auto mb-2"><i class="bi bi-bag-heart-fill"></i></div>
            @endif
            <h1 class="h4 mb-1">{{ store_name() }}</h1>
            <p class="text-muted mb-0">{{ $appSettings['store_tagline'] ?? 'Fashion Store Management' }}</p>
        </div>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <h2 class="h5 fw-semibold mb-1">Welcome back</h2>
                <p class="text-muted small mb-4">Sign in to continue to your store.</p>

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}"
                               class="form-control form-control-lg @error('email') is-invalid @enderror"
                               placeholder="you@store.com" required autofocus autocomplete="username">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <label for="password" class="form-label">Password</label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="small">Forgot password?</a>
                            @endif
                        </div>
                        <input id="password" type="password" name="password"
                               class="form-control form-control-lg @error('password') is-invalid @enderror"
                               required autocomplete="current-password">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign in
                    </button>
                </form>
            </div>
        </div>

        <p class="text-center text-muted small mt-4">
            Demo accounts — Admin / Manager / Cashier<br>
            <span class="font-monospace">admin@fashionstore.test · manager@fashionstore.test · cashier@fashionstore.test</span><br>
            Password: <span class="font-monospace">password</span>
        </p>
    </div>
@endsection

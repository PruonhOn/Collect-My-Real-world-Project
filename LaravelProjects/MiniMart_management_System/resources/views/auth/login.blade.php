@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="container">
    <div class="row justify-content-center align-items-center" style="min-height: 80vh;">
        <div class="col-md-5">
            <div class="card shadow-lg">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <div class="mb-3">
                            <i class="fas fa-store text-primary" style="font-size: 3rem;"></i>
                        </div>
                        <h4 class="fw-bold">MiniMart Management</h4>
                        <p class="text-muted">Sign in to your account</p>
                    </div>

                    <form method="POST" action="{{ route('login') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                    value="{{ old('email') }}" placeholder="admin@minimart.com" required autofocus>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                                    placeholder="Enter password" required>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" name="remember" class="form-check-input" id="remember">
                            <label class="form-check-label" for="remember">Remember Me</label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>Sign In
                        </button>
                    </form>

                    <div class="text-center mt-4">
                        <small class="text-muted">
                            Demo: admin@minimart.com / password<br>
                            Cashier: cashier@minimart.com / password
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@extends('layouts.guest')

@section('title', 'Confirm password')

@section('content')
    <div class="auth-card">
        <div class="auth-brand text-center mb-4">
            <div class="auth-icon mx-auto mb-2"><i class="bi bi-shield-lock-fill"></i></div>
            <h1 class="h4 mb-1">Confirm your password</h1>
            <p class="text-muted mb-0">This is a secure area. Please confirm your password before continuing.</p>
        </div>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('password.confirm') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input id="password" type="password" name="password"
                               class="form-control form-control-lg @error('password') is-invalid @enderror" required autofocus>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill">Confirm</button>
                </form>
            </div>
        </div>
    </div>
@endsection

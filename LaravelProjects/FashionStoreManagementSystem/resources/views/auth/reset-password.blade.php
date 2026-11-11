@extends('layouts.guest')

@section('title', 'Reset password')

@section('content')
    <div class="auth-card">
        <div class="auth-brand text-center mb-4">
            <div class="auth-icon mx-auto mb-2"><i class="bi bi-shield-lock-fill"></i></div>
            <h1 class="h4 mb-1">Set a new password</h1>
        </div>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('password.store') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $request->route('token') }}">

                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}"
                               class="form-control form-control-lg @error('email') is-invalid @enderror" required autofocus>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">New password</label>
                        <input id="password" type="password" name="password"
                               class="form-control form-control-lg @error('password') is-invalid @enderror" required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label">Confirm password</label>
                        <input id="password_confirmation" type="password" name="password_confirmation"
                               class="form-control form-control-lg" required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill">Reset password</button>
                </form>
            </div>
        </div>
    </div>
@endsection

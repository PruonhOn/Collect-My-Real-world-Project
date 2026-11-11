@extends('layouts.guest')

@section('title', 'Verify email')

@section('content')
    <div class="auth-card">
        <div class="auth-brand text-center mb-4">
            <div class="auth-icon mx-auto mb-2"><i class="bi bi-envelope-check-fill"></i></div>
            <h1 class="h4 mb-1">Verify your email</h1>
        </div>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4 text-center">
                <div class="text-muted small mb-4">
                    Thanks for signing up! Before getting started, could you verify your email address by clicking the link we just emailed to you?
                    If you didn't receive the email, we will gladly send you another.
                </div>

                @if (session('status') == 'verification-link-sent')
                    <div class="alert alert-success alert-soft">
                        A new verification link has been sent to the email address you provided.
                    </div>
                @endif

                <form method="POST" action="{{ route('verification.send') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-primary rounded-pill">Resend verification email</button>
                </form>
            </div>
        </div>
    </div>
@endsection

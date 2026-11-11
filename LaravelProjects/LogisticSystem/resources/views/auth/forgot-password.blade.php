<x-guest-layout>
    <h1 class="fs-4 fw-bold text-center mb-1" style="color: #1a1a2e;">{{ __('Reset Password') }}</h1>
    <p class="text-muted small text-center mb-4">{{ __('Enter your email and we\'ll send you a reset link') }}</p>

    <x-auth-session-status class="mb-3" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="mb-4">
            <label for="email" class="form-label small fw-medium text-secondary">{{ __('Email') }}</label>
            <input id="email" type="email" class="form-control form-control-lg @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autofocus placeholder="name@company.com">
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">{{ __('Send Reset Link') }}</button>
        <p class="text-center text-muted small mt-3 mb-0">{{ __('Remember your password?') }} <a class="text-primary text-decoration-none fw-medium" href="{{ route('login') }}">{{ __('Sign in') }}</a></p>
    </form>
</x-guest-layout>

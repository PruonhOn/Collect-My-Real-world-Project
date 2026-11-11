<x-guest-layout>
    <h1 class="fs-4 fw-bold text-center mb-1" style="color: #1a1a2e;">{{ __('Create Account') }}</h1>
    <p class="text-muted small text-center mb-4">{{ __('Register to get started') }}</p>

    <form method="POST" action="{{ route('register') }}">
        @csrf
        <div class="mb-3">
            <label for="name" class="form-label small fw-medium text-secondary">{{ __('Full Name') }}</label>
            <input id="name" type="text" class="form-control form-control-lg @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="Your name">
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label for="email" class="form-label small fw-medium text-secondary">{{ __('Email') }}</label>
            <input id="email" type="email" class="form-control form-control-lg @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="username" placeholder="name@company.com">
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label for="password" class="form-label small fw-medium text-secondary">{{ __('Password') }}</label>
            <input id="password" type="password" class="form-control form-control-lg @error('password') is-invalid @enderror" name="password" required autocomplete="new-password" placeholder="Password">
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
            <label for="password_confirmation" class="form-label small fw-medium text-secondary">{{ __('Confirm Password') }}</label>
            <input id="password_confirmation" type="password" class="form-control form-control-lg" name="password_confirmation" required autocomplete="new-password" placeholder="Confirm password">
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">{{ __('Create Account') }}</button>
        <p class="text-center text-muted small mt-3 mb-0">{{ __('Already have an account?') }} <a class="text-primary text-decoration-none fw-medium" href="{{ route('login') }}">{{ __('Sign in') }}</a></p>
    </form>
</x-guest-layout>

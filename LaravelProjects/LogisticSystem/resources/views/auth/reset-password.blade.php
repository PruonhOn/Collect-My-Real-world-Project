<x-guest-layout>
    <h1 class="fs-4 fw-bold text-center mb-1" style="color: #1a1a2e;">{{ __('Set New Password') }}</h1>
    <p class="text-muted small text-center mb-4">{{ __('Choose a strong password') }}</p>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <div class="mb-3">
            <label for="email" class="form-label small fw-medium text-secondary">{{ __('Email') }}</label>
            <input id="email" type="email" class="form-control form-control-lg" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username" readonly>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label small fw-medium text-secondary">{{ __('New Password') }}</label>
            <input id="password" type="password" class="form-control form-control-lg @error('password') is-invalid @enderror" name="password" required autocomplete="new-password" placeholder="New password">
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
            <label for="password_confirmation" class="form-label small fw-medium text-secondary">{{ __('Confirm Password') }}</label>
            <input id="password_confirmation" type="password" class="form-control form-control-lg" name="password_confirmation" required autocomplete="new-password" placeholder="Confirm password">
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">{{ __('Reset Password') }}</button>
    </form>
</x-guest-layout>

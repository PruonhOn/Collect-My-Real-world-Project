<x-guest-layout>
    <h1 class="fs-4 fw-bold text-center mb-1" style="color: #1a1a2e;">{{ __('Confirm Password') }}</h1>
    <p class="text-muted small text-center mb-4">{{ __('Please confirm your password to continue') }}</p>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf
        <div class="mb-4">
            <label for="password" class="form-label small fw-medium text-secondary">{{ __('Password') }}</label>
            <input id="password" type="password" class="form-control form-control-lg @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" placeholder="Enter your password">
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">{{ __('Confirm') }}</button>
    </form>
</x-guest-layout>

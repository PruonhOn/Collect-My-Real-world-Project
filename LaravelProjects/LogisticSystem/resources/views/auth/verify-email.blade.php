<x-guest-layout>
    <h1 class="fs-4 fw-bold text-center mb-1" style="color: #1a1a2e;">{{ __('Verify Email') }}</h1>
    <p class="text-muted small text-center mb-4">{{ __('Please verify your email address to get started.') }}</p>

    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success border-0 small py-2 mb-3">{{ __('A new verification link has been sent.') }}</div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}" class="mb-2">
        @csrf
        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">{{ __('Resend Verification Email') }}</button>
    </form>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn btn-link text-decoration-none text-muted small w-100">{{ __('Log Out') }}</button>
    </form>
</x-guest-layout>

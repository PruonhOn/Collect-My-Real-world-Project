<x-guest-layout>
    <h1 class="fs-4 fw-bold text-center mb-1" style="color: #1a1a2e;">{{ __('Welcome Back') }}</h1>
    <p class="text-muted small text-center mb-4">{{ __('Sign in to your logistics account') }}</p>

    <x-auth-session-status class="mb-3" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" id="loginForm">
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label small fw-medium text-secondary">{{ __('Email') }}</label>
            <input id="email" type="email" class="form-control form-control-lg @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="name@company.com">
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label small fw-medium text-secondary">{{ __('Password') }}</label>
            <div class="input-group">
                <input id="password" type="password" class="form-control form-control-lg border-end-0 @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" placeholder="Enter your password">
                <button type="button" id="togglePassword" class="input-group-text bg-white" tabindex="-1">
                    <svg width="18" height="18" id="eyeIcon" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <svg width="18" height="18" id="eyeOffIcon" class="d-none" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                    </svg>
                </button>
                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="form-check">
                <input id="remember_me" type="checkbox" class="form-check-input" name="remember">
                <label for="remember_me" class="form-check-label small text-secondary">{{ __('Remember me') }}</label>
            </div>
            @if (Route::has('password.request'))
                <a class="small text-primary text-decoration-none fw-medium" href="{{ route('password.request') }}">{{ __('Forgot password?') }}</a>
            @endif
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold" id="loginButton">
            <span class="spinner-border spinner-border-sm d-none me-2" id="loginSpinner" role="status"></span>
            <span id="loginButtonText">{{ __('Sign In') }}</span>
        </button>
    </form>

    @push('scripts')
    <script>
        document.getElementById('togglePassword')?.addEventListener('click', function() {
            var p = document.getElementById('password');
            var eye = document.getElementById('eyeIcon');
            var eyeOff = document.getElementById('eyeOffIcon');
            if (p.type === 'password') { p.type = 'text'; eye.classList.add('d-none'); eyeOff.classList.remove('d-none'); }
            else { p.type = 'password'; eye.classList.remove('d-none'); eyeOff.classList.add('d-none'); }
        });
        document.getElementById('loginForm')?.addEventListener('submit', function() {
            var btn = document.getElementById('loginButton');
            btn.disabled = true;
            document.getElementById('loginSpinner').classList.remove('d-none');
            document.getElementById('loginButtonText').textContent = '{{ __("Signing in...") }}';
        });
    </script>
    @endpush
</x-guest-layout>

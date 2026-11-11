<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="mb-7">
        <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">Welcome back</h2>
        <p class="mt-1.5 text-sm text-slate-500">Sign in to your account to continue</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email address')" />
            <div class="relative mt-1.5">
                <span class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center text-slate-400">
                    <svg class="w-4.5 h-4.5 w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.96 8.96 0 01-4.5 1.207"/></svg>
                </span>
                <x-text-input id="email" class="block w-full pl-11 rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500/40" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="you@uams.edu" />
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" />
            <div class="relative mt-1.5">
                <span class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center text-slate-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.03 5.92A9.2 9.2 0 0012 21a9 9 0 116.36-15.36 6 6 0 013.39 3.61z"/></svg>
                </span>
                <x-text-input id="password" class="block w-full pl-11 rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500/40"
                                type="password"
                                name="password"
                                required autocomplete="current-password" placeholder="••••••••" />
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center cursor-pointer select-none">
                <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500/40" name="remember">
                <span class="ms-2 text-sm text-slate-600">{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm font-medium text-indigo-600 hover:text-indigo-800" href="{{ route('password.request') }}">
                    {{ __('Forgot password?') }}
                </a>
            @endif
        </div>

        <button type="submit"
                class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 bg-gradient-to-r from-indigo-600 to-violet-600 text-white text-sm font-semibold rounded-xl shadow-lg shadow-indigo-500/30 hover:from-indigo-700 hover:to-violet-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:ring-offset-2 transition-all">
            {{ __('Sign in') }}
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12"/></svg>
        </button>
    </form>

    @if (Route::has('register'))
        <p class="mt-6 text-center text-sm text-slate-500">
            Don't have an account?
            <a href="{{ route('register') }}" class="font-semibold text-indigo-600 hover:text-indigo-800">Create one</a>
        </p>
    @endif
</x-guest-layout>

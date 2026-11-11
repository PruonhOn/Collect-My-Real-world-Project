{{-- resources/views/auth/login.blade.php --}}
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login — MaintainPro</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-950 min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-sm">
    {{-- Logo --}}
    <div class="flex items-center justify-center gap-3 mb-8">
        <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center text-white font-bold text-lg">M</div>
        <span class="text-lg font-semibold text-gray-100">MaintainPro</span>
    </div>

    <div class="card p-8">
        <h1 class="text-base font-semibold text-center mb-1">Sign in to your account</h1>
        <p class="text-sm text-gray-500 text-center mb-6">Maintenance Management System</p>

        @if (session('status'))
        <div class="mb-4 text-sm text-green-400 bg-green-500/10 border border-green-500/20 rounded-lg px-4 py-3">
            {{ session('status') }}
        </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Email</label>
                <input type="email" name="email" value="{{ old('email') }}"
                       class="input w-full @error('email') border-red-500/50 @enderror"
                       placeholder="you@company.com" required autofocus autocomplete="username">
                @error('email')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Password</label>
                <input type="password" name="password"
                       class="input w-full @error('password') border-red-500/50 @enderror"
                       required autocomplete="current-password">
                @error('password')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="remember" class="rounded border-gray-600 bg-gray-800 text-blue-500">
                    <span class="text-xs text-gray-400">Remember me</span>
                </label>
                @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-xs text-blue-400 hover:text-blue-300">
                    Forgot password?
                </a>
                @endif
            </div>

            <button type="submit" class="btn-primary w-full text-center">Sign In</button>
        </form>

        <div class="mt-6 pt-5 border-t border-white/[0.06] text-center">
            <p class="text-xs text-gray-600">Demo credentials:</p>
            <p class="text-xs text-gray-500 mt-1">admin@mms.local / password</p>
        </div>
    </div>
</div>

</body>
</html>

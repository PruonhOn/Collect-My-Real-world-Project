<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'LogiPro'))</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    <style>
        body { font-family: 'Figtree', sans-serif; background: #f0f2f5; }
        .login-card { max-width: 420px; width: 100%; }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body>
    @hasSection('content')
        @yield('content')
    @else
        <div class="min-vh-100 d-flex align-items-center justify-content-center px-3 py-5">
            <div class="login-card">
                <div class="text-center mb-4">
                    <a href="/" class="text-decoration-none">
                        <span class="fs-3 fw-bold" style="color: #1a1a2e;">Logi<span style="color: #0d6efd;">Pro</span></span>
                    </a>
                </div>
                <div class="card border-0 shadow-sm rounded-3 p-4">
                    {{ $slot }}
                </div>
                <p class="text-center text-muted small mt-3 mb-0">&copy; {{ date('Y') }} LogiPro</p>
            </div>
        </div>
    @endif
    @stack('scripts')
</body>
</html>

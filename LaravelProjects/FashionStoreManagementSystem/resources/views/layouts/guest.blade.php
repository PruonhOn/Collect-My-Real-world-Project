<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', store_name()) — {{ store_name() }}</title>
    <script>
        (function () {
            var theme;
            try {
                theme = localStorage.getItem('theme');
            } catch (e) {
                theme = null;
            }
            theme = theme || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-bs-theme', theme);
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="guest-body">
<div class="guest-wrap">
    <div class="position-fixed top-0 end-0 p-3 z-3">
        <x-theme-toggle />
    </div>
    <main>
        @yield('content')
    </main>
</div>
</body>
</html>

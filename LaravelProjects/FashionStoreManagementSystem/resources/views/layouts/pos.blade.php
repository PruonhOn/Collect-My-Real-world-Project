<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'POS') — {{ store_name() }}</title>
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
<body class="pos-body">
@yield('content')

<div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer"></div>

@stack('modals')

<script>
    window.FlashMessages = {
        success: @json(session('success')),
        error: @json(session('error')),
        warning: @json(session('warning')),
        info: @json(session('info')),
        errors: @json($errors->all()),
    };
</script>
@stack('scripts')
</body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('messages.learning_system')) - {{ __('messages.school_name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kantumruy+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 font-sans text-gray-800 antialiased">
    <div class="min-h-screen flex flex-col">
        <main class="flex-1">
            {{ $slot }}
        </main>
        <footer class="py-4 text-center text-sm text-gray-500">
            {{ __('messages.school_name') }} — {{ __('messages.tagline') }}
        </footer>
    </div>
</body>
</html>

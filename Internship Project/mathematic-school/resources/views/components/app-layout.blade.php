<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? __('messages.dashboard') }} - {{ __('messages.school_name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kantumruy+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 font-sans text-gray-800 antialiased" x-data="{ sidebarOpen: false }">
    <div class="min-h-screen flex">

        <!-- Sidebar -->
        @include('layouts.partials.sidebar', ['active' => $active ?? ''])

        <!-- Mobile overlay -->
        <div x-show="sidebarOpen" x-cloak x-transition.opacity
            class="fixed inset-0 bg-gray-900/50 z-30 lg:hidden" @click="sidebarOpen = false"></div>

        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top bar -->
            @include('layouts.partials.topbar', ['title' => $title ?? __('messages.dashboard')])

            <!-- Page content -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                {{ $slot }}
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>

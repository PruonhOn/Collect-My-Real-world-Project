<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'UAMS') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }
            [x-cloak] { display: none !important; }
        </style>
    </head>
    <body class="font-sans text-slate-800 antialiased">
        <div class="relative min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-slate-950 overflow-hidden">
            <!-- Ambient gradient blobs -->
            <div class="pointer-events-none absolute -top-32 -left-32 w-[480px] h-[480px] rounded-full bg-indigo-600/25 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-40 -right-24 w-[520px] h-[520px] rounded-full bg-violet-600/20 blur-3xl"></div>
            <div class="pointer-events-none absolute top-1/3 right-1/4 w-[280px] h-[280px] rounded-full bg-sky-500/10 blur-3xl"></div>

            <!-- Brand -->
            <a href="/" class="relative z-10 flex flex-col items-center mb-8 group">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center shadow-xl shadow-indigo-500/30 group-hover:scale-105 transition-transform">
                    <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3L2 8l10 5 10-5-10-5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M5 10.5V15c0 1.7 3.1 3 7 3s7-1.3 7-3v-4.5"/><path stroke-linecap="round" stroke-linejoin="round" d="M22 8v6"/></svg>
                </div>
                <h1 class="mt-4 text-2xl font-extrabold tracking-tight text-white">UAMS</h1>
                <p class="mt-1 text-xs uppercase tracking-[0.22em] text-indigo-300/70">University Attendance System</p>
            </a>

            <!-- Card -->
            <div class="relative z-10 w-full sm:max-w-md px-6 py-8 bg-white rounded-3xl shadow-2xl shadow-indigo-950/40 border border-white/10 mx-4">
                {{ $slot }}
            </div>

            <p class="relative z-10 mt-8 text-xs text-slate-500">&copy; {{ date('Y') }} {{ config('app.name', 'UAMS') }} · All rights reserved</p>
        </div>
    </body>
</html>

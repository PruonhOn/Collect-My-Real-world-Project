<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true', sidebarOpen: window.innerWidth > 768 }" x-init="
    if (darkMode) document.documentElement.classList.add('dark');
    $watch('darkMode', val => {
        localStorage.setItem('darkMode', val);
        document.documentElement.classList.toggle('dark', val);
    });
    window.addEventListener('resize', () => { if (window.innerWidth > 768) sidebarOpen = true; });
">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — K & N AutoCar</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800&display=swap" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100 transition-colors duration-200">

    {{-- Overlay for mobile sidebar --}}
    <div x-show="sidebarOpen" x-cloak x-transition.opacity @click="sidebarOpen = false" class="fixed inset-0 z-30 bg-black/40 md:hidden"></div>

    {{-- SIDEBAR --}}
    <aside x-show="sidebarOpen" x-cloak x-transition:enter="transition-transform duration-300" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition-transform duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" class="fixed top-0 left-0 z-40 h-full w-64 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 shadow-lg flex flex-col">
        {{-- Logo --}}
        <div class="flex items-center h-16 px-6 border-b border-gray-200 dark:border-gray-800 flex-shrink-0">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                <div class="w-8 h-8 bg-gradient-to-br from-red-600 to-red-700 rounded-lg flex items-center justify-center text-white font-bold text-sm">K</div>
                <div>
                    <span class="font-bold text-base text-gray-900 dark:text-white">K & N AutoCar</span>
                    <span class="block text-[10px] uppercase tracking-widest text-gray-500 dark:text-gray-400 -mt-0.5">Owner Panel</span>
                </div>
            </a>
        </div>

        {{-- Scrollable nav --}}
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
            {{-- Main --}}
            <p class="px-3 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2">Main</p>
            <x-admin-nav-link href="{{ route('admin.dashboard') }}" :active="request()->routeIs('admin.dashboard')" icon="grid">Dashboard</x-admin-nav-link>

            {{-- Inventory --}}
            <p class="px-3 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mt-6 mb-2">Inventory</p>
            <x-admin-nav-link href="{{ route('admin.cars.index') }}" :active="request()->routeIs('admin.cars.*')" icon="car">Cars</x-admin-nav-link>
            <x-admin-nav-link href="{{ route('admin.categories.index') }}" :active="request()->routeIs('admin.categories.*')" icon="folder">Categories</x-admin-nav-link>

            {{-- Sales --}}
            <p class="px-3 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mt-6 mb-2">Sales</p>
            <x-admin-nav-link href="{{ route('admin.transactions.index') }}" :active="request()->routeIs('admin.transactions.*')" icon="receipt">Transactions</x-admin-nav-link>

            {{-- Content --}}
            <p class="px-3 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mt-6 mb-2">Content</p>
            <x-admin-nav-link href="{{ route('admin.pages.index') }}" :active="request()->routeIs('admin.pages.*')" icon="file">Pages</x-admin-nav-link>
            <x-admin-nav-link href="{{ route('admin.testimonials.index') }}" :active="request()->routeIs('admin.testimonials.*')" icon="star">Testimonials</x-admin-nav-link>
            <x-admin-nav-link href="{{ route('admin.contacts.index') }}" :active="request()->routeIs('admin.contacts.*')" icon="mail">Messages</x-admin-nav-link>

            {{-- Settings --}}
            <p class="px-3 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mt-6 mb-2">Settings</p>
            <x-admin-nav-link href="{{ route('admin.settings.index') }}" :active="request()->routeIs('admin.settings.*')" icon="settings">Website Settings</x-admin-nav-link>

            {{-- Public link --}}
            <div class="pt-4 mt-4 border-t border-gray-200 dark:border-gray-800">
                <a href="{{ route('home') }}" target="_blank" class="flex items-center gap-3 px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-red-600 dark:hover:text-red-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    Visit Website
                </a>
            </div>
        </nav>

        {{-- User footer --}}
        <div class="border-t border-gray-200 dark:border-gray-800 p-4 flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-gradient-to-br from-red-500 to-red-700 rounded-full flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                    {{ substr(auth()->user()->name, 0, 1) }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ auth()->user()->email }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="flex-shrink-0">
                    @csrf
                    <button type="submit" class="p-1.5 text-gray-400 hover:text-red-500 transition-colors rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800" title="Sign out">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- MAIN CONTENT --}}
    <div :class="sidebarOpen ? 'md:ml-64' : ''" class="transition-all duration-300 min-h-screen flex flex-col">

        {{-- TOP NAVBAR --}}
        <header class="sticky top-0 z-20 bg-white/80 dark:bg-gray-900/80 backdrop-blur-md border-b border-gray-200 dark:border-gray-800 flex-shrink-0">
            <div class="flex items-center justify-between h-16 px-4 md:px-6">
                <div class="flex items-center gap-3">
                    <button @click="sidebarOpen = !sidebarOpen" class="p-2 rounded-lg text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <h1 class="text-lg font-semibold text-gray-900 dark:text-white hidden sm:block">@yield('header', 'Dashboard')</h1>
                </div>

                <div class="flex items-center gap-2">
                    {{-- Dark mode toggle --}}
                    <button @click="darkMode = !darkMode" class="p-2 rounded-lg text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors" title="Toggle theme">
                        <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                        <svg x-show="darkMode" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </button>

                    {{-- Profile dropdown --}}
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" @click.outside="open = false" class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                            <div class="w-8 h-8 bg-gradient-to-br from-red-500 to-red-700 rounded-full flex items-center justify-center text-white font-bold text-xs">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </div>
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-cloak x-transition @click.outside="open = false" class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-50">
                            <div class="px-4 py-2 border-b border-gray-100 dark:border-gray-700">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-gray-500">{{ auth()->user()->email }}</p>
                            </div>
                            <a href="{{ route('profile') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                Profile
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                    Sign Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        {{-- ALERTS --}}
        @if (session('success'))
            <div class="mx-4 md:mx-6 mt-4" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
                <div class="flex items-center justify-between bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 rounded-xl px-4 py-3">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="text-sm font-medium">{{ session('success') }}</span>
                    </div>
                    <button @click="show = false" class="text-emerald-500 hover:text-emerald-700">&times;</button>
                </div>
            </div>
        @endif
        @if (session('error'))
            <div class="mx-4 md:mx-6 mt-4" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
                <div class="flex items-center justify-between bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 rounded-xl px-4 py-3">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="text-sm font-medium">{{ session('error') }}</span>
                    </div>
                    <button @click="show = false" class="text-red-500 hover:text-red-700">&times;</button>
                </div>
            </div>
        @endif

        {{-- PAGE CONTENT --}}
        <main class="flex-1 p-4 md:p-6">
            @yield('content')
        </main>

        {{-- FOOTER --}}
        <footer class="border-t border-gray-200 dark:border-gray-800 px-4 md:px-6 py-4 text-center text-xs text-gray-400 dark:text-gray-600">
            &copy; {{ date('Y') }} K & N AutoCar. All rights reserved. v2.0
        </footer>
    </div>

    @stack('scripts')
</body>
</html>

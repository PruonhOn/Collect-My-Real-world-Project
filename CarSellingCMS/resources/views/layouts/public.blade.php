<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'K & N AutoCar'))</title>
    <meta name="description" content="@yield('meta_description', 'K & N AutoCar - Your trusted car dealership in Cambodia')">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-50 text-gray-900">
    <header class="bg-white shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-3">
                <a href="{{ route('home') }}" class="flex items-center space-x-2">
                    <div class="bg-red-600 text-white font-bold text-xl px-3 py-1 rounded">K & N</div>
                    <span class="text-xl font-bold text-gray-800">AutoCar</span>
                </a>
                <nav class="hidden md:flex space-x-8">
                    <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'text-red-600 font-semibold' : 'text-gray-700 hover:text-red-600' }} transition">Home</a>
                    <a href="{{ route('cars.index') }}" class="nav-link {{ request()->routeIs('cars.*') ? 'text-red-600 font-semibold' : 'text-gray-700 hover:text-red-600' }} transition">Cars</a>
                    <a href="{{ route('pages.show', 'about') }}" class="nav-link {{ request()->routeIs('pages.show') && request()->route('slug') === 'about' ? 'text-red-600 font-semibold' : 'text-gray-700 hover:text-red-600' }} transition">About</a>
                    <a href="{{ route('contact.index') }}" class="nav-link {{ request()->routeIs('contact.*') ? 'text-red-600 font-semibold' : 'text-gray-700 hover:text-red-600' }} transition">Contact</a>
                </nav>
                <div class="flex items-center space-x-4">
                    <a href="{{ route('search') }}" class="text-gray-600 hover:text-red-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </a>
                    @auth
                        <a href="{{ route('admin.dashboard') }}" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition text-sm font-medium">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition text-sm font-medium">Login</a>
                    @endauth
                    <button id="mobile-menu-btn" class="md:hidden text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                </div>
            </div>
            <div id="mobile-menu" class="hidden md:hidden pb-4 space-y-2">
                <a href="{{ route('home') }}" class="block px-3 py-2 text-gray-700 hover:bg-gray-100 rounded">Home</a>
                <a href="{{ route('cars.index') }}" class="block px-3 py-2 text-gray-700 hover:bg-gray-100 rounded">Cars</a>
                <a href="{{ route('pages.show', 'about') }}" class="block px-3 py-2 text-gray-700 hover:bg-gray-100 rounded">About</a>
                <a href="{{ route('contact.index') }}" class="block px-3 py-2 text-gray-700 hover:bg-gray-100 rounded">Contact</a>
            </div>
        </div>
    </header>

    @if (session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 max-w-7xl mx-auto mt-4 rounded" role="alert">
            <p>{{ session('success') }}</p>
        </div>
    @endif
    @if (session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 max-w-7xl mx-auto mt-4 rounded" role="alert">
            <p>{{ session('error') }}</p>
        </div>
    @endif

    @hasSection('hero')
        @yield('hero')
    @endif

    <main class="min-h-screen">
        @yield('content')
    </main>

    <footer class="bg-gray-900 text-gray-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center space-x-2 mb-4">
                        <div class="bg-red-600 text-white font-bold text-lg px-2 py-1 rounded">K & N</div>
                        <span class="text-lg font-bold text-white">AutoCar</span>
                    </div>
                    <p class="text-sm leading-relaxed">{{ App\Models\Setting::get('site_description', 'Your trusted partner in finding the perfect vehicle. Quality cars, competitive prices, and exceptional service.') }}</p>
                </div>
                <div>
                    <h3 class="text-white font-semibold mb-4 uppercase tracking-wider text-sm">Quick Links</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="{{ route('home') }}" class="hover:text-red-400 transition">Home</a></li>
                        <li><a href="{{ route('cars.index') }}" class="hover:text-red-400 transition">Inventory</a></li>
                        <li><a href="{{ route('pages.show', 'about') }}" class="hover:text-red-400 transition">About Us</a></li>
                        <li><a href="{{ route('contact.index') }}" class="hover:text-red-400 transition">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-white font-semibold mb-4 uppercase tracking-wider text-sm">Car Types</h3>
                    <ul class="space-y-2 text-sm">
                        @foreach(App\Models\Category::where('is_active', true)->get() as $cat)
                            <li><a href="{{ route('cars.index', ['category_id' => $cat->id]) }}" class="hover:text-red-400 transition">{{ $cat->name }}</a></li>
                        @endforeach
                    </ul>
                </div>
                <div>
                    <h3 class="text-white font-semibold mb-4 uppercase tracking-wider text-sm">Contact Info</h3>
                    <ul class="space-y-3 text-sm">
                        <li class="flex items-start space-x-2">
                            <svg class="w-5 h-5 text-red-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span>{{ App\Models\Setting::get('address', 'Phnom Penh, Cambodia') }}</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <svg class="w-5 h-5 text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            <span>{{ App\Models\Setting::get('phone', '+855 12 345 678') }}</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <svg class="w-5 h-5 text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <span>{{ App\Models\Setting::get('email', 'info@knautocar.com') }}</span>
                        </li>
                    </ul>
                    <div class="flex space-x-3 mt-4">
                        @php $fb = App\Models\Setting::get('facebook_url'); @endphp
                        @if($fb)
                            <a href="{{ $fb }}" target="_blank" class="w-8 h-8 bg-gray-700 rounded-full flex items-center justify-center hover:bg-red-600 transition"><span class="text-xs font-bold">fb</span></a>
                        @endif
                        @php $ig = App\Models\Setting::get('instagram_url'); @endphp
                        @if($ig)
                            <a href="{{ $ig }}" target="_blank" class="w-8 h-8 bg-gray-700 rounded-full flex items-center justify-center hover:bg-red-600 transition"><span class="text-xs font-bold">ig</span></a>
                        @endif
                        @php $yt = App\Models\Setting::get('youtube_url'); @endphp
                        @if($yt)
                            <a href="{{ $yt }}" target="_blank" class="w-8 h-8 bg-gray-700 rounded-full flex items-center justify-center hover:bg-red-600 transition"><span class="text-xs font-bold">yt</span></a>
                        @endif
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-sm">
                <p>&copy; {{ date('Y') }} K & N AutoCar. All rights reserved. {{ App\Models\Setting::get('footer_text', '') }}</p>
            </div>
        </div>
    </footer>

    <script>
        document.getElementById('mobile-menu-btn')?.addEventListener('click', function() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });
    </script>
    @stack('scripts')
</body>
</html>
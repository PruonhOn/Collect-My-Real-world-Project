{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title', 'MaintainPro') — MMS</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    {{-- Real-time notifications via Laravel Reverb --}}
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
</head>
<body
    x-data="{ sidebarOpen: true, notifications: [] }"
    @notify.window="notifications.push($event.detail); setTimeout(() => notifications.shift(), 4000)"
    class="bg-gray-950 text-gray-100 font-sans antialiased"
>

    {{-- Toast notifications --}}
    <div class="fixed top-4 right-4 z-50 space-y-2" x-cloak>
        <template x-for="(n, i) in notifications" :key="i">
            <div
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-x-8"
                x-transition:enter-end="opacity-100 translate-x-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-end="opacity-0 translate-x-8"
                :class="{
                    'border-green-500/30 bg-green-950/80': n.type === 'success',
                    'border-red-500/30 bg-red-950/80': n.type === 'error',
                    'border-yellow-500/30 bg-yellow-950/80': n.type === 'warning',
                    'border-blue-500/30 bg-blue-950/80': !n.type || n.type === 'info',
                }"
                class="flex items-center gap-3 px-4 py-3 rounded-xl border backdrop-blur-md
                       shadow-xl text-sm max-w-xs"
            >
                <span x-text="n.message" class="flex-1"></span>
                <button @click="notifications.splice(i, 1)" class="text-gray-400 hover:text-white">✕</button>
            </div>
        </template>
    </div>

    <div class="flex h-screen overflow-hidden">

        {{-- Sidebar --}}
        <aside
            :class="sidebarOpen ? 'w-56' : 'w-16'"
            class="bg-gray-900 border-r border-white/8 flex flex-col transition-all duration-300 overflow-hidden"
        >
            {{-- Logo --}}
            <div class="flex items-center gap-3 px-4 py-5 border-b border-white/8">
                <div class="w-7 h-7 rounded-lg bg-blue-600 flex items-center justify-center text-xs font-bold flex-shrink-0">M</div>
                <span x-show="sidebarOpen" x-transition class="font-semibold text-sm tracking-tight">MaintainPro</span>
            </div>

            {{-- Nav --}}
            <nav class="flex-1 p-2 space-y-0.5">
                @php
                    $navItems = [
                        ['route' => 'dashboard',       'icon' => '⊞', 'label' => 'Dashboard',   'badge' => null],
                        ['route' => 'work-orders.index','icon' => '⚙', 'label' => 'Work Orders', 'badge' => App\Models\WorkOrder::open()->count()],
                        ['route' => 'assets.index',     'icon' => '◈', 'label' => 'Assets',      'badge' => null],
                        ['route' => 'reports.index',    'icon' => '▤', 'label' => 'Reports',     'badge' => null],
                    ];
                @endphp

                @foreach ($navItems as $item)
                <a href="{{ route($item['route']) }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-all
                          {{ request()->routeIs(str_replace('.index', '', $item['route']) . '*')
                              ? 'bg-blue-600/15 text-blue-400'
                              : 'text-gray-400 hover:bg-white/5 hover:text-gray-100' }}">
                    <span class="text-base w-5 text-center flex-shrink-0">{{ $item['icon'] }}</span>
                    <span x-show="sidebarOpen" x-transition class="flex-1">{{ $item['label'] }}</span>
                    @if($item['badge'] ?? null)
                        <span x-show="sidebarOpen"
                              class="bg-red-500 text-white text-xs font-semibold px-1.5 py-0.5 rounded-full">
                            {{ $item['badge'] }}
                        </span>
                    @endif
                </a>
                @endforeach
            </nav>

            {{-- User --}}
            <div class="p-2 border-t border-white/8">
                <div class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/5 cursor-pointer transition">
                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-blue-500 to-purple-500
                                flex items-center justify-center text-xs font-semibold flex-shrink-0">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </div>
                    <div x-show="sidebarOpen" x-transition class="flex-1 min-w-0">
                        <p class="text-xs font-medium truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ ucfirst(auth()->user()->getRoleNames()->first() ?? 'User') }}</p>
                    </div>
                </div>
                {{-- Sidebar toggle --}}
                <button @click="sidebarOpen = !sidebarOpen"
                        class="w-full mt-1 flex items-center justify-center px-3 py-2 rounded-lg
                               text-gray-500 hover:bg-white/5 hover:text-gray-300 transition text-xs">
                    <span x-text="sidebarOpen ? '← Collapse' : '→'"></span>
                </button>
            </div>
        </aside>

        {{-- Main --}}
        <div class="flex-1 flex flex-col overflow-hidden">

            {{-- Topbar --}}
            <header class="h-14 bg-gray-900 border-b border-white/8 flex items-center px-6 gap-4 justify-between flex-shrink-0">
                <div class="flex items-center gap-3">
                    <h1 class="font-semibold text-sm">@yield('page-title', 'Dashboard')</h1>
                    @livewire('notifications.notification-bell')
                </div>
                <div class="flex items-center gap-3">
                    <input type="search"
                           placeholder="Search…"
                           class="bg-gray-800 border border-white/10 rounded-lg px-3 py-1.5 text-sm
                                  text-gray-100 placeholder-gray-500 outline-none focus:border-blue-500/60 w-48" />
                    @can('create work-orders')
                    <a href="{{ route('work-orders.create') }}"
                       class="bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium
                              px-4 py-1.5 rounded-lg transition">
                        + New Work Order
                    </a>
                    @endcan
                </div>
            </header>

            {{-- Page content --}}
            <main class="flex-1 overflow-y-auto p-6 bg-gray-950">
                @yield('content')
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>

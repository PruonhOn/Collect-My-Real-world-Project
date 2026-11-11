@php
    $user = auth()->user();
    $unreadCount = $user->unreadNotifications->count();
@endphp

<header class="sticky top-0 z-20 flex items-center justify-between h-16 bg-white border-b border-gray-200 px-4 sm:px-6">
    <div class="flex items-center gap-3">
        <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 rounded-lg text-gray-600 hover:bg-gray-100">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <h1 class="text-lg sm:text-xl font-bold text-gray-900">{{ $title }}</h1>
    </div>

    <div class="flex items-center gap-2 sm:gap-4">

        <!-- Notifications -->
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" class="relative p-2 rounded-lg text-gray-600 hover:bg-gray-100">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                @if ($unreadCount > 0)
                    <span class="absolute top-1 right-1 w-4 h-4 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center">{{ $unreadCount }}</span>
                @endif
            </button>

            <div x-show="open" x-cloak @click.away="open = false" x-transition
                class="absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                    <span class="font-semibold text-gray-800">{{ __('messages.notifications') }}</span>
                    @if ($unreadCount > 0)
                        <form method="POST" action="{{ route('notifications.mark-all-read') }}">
                            @csrf
                            <button class="text-xs text-primary-600 hover:underline">{{ __('messages.mark_all_read') }}</button>
                        </form>
                    @endif
                </div>
                <div class="max-h-80 overflow-y-auto">
                    @forelse ($user->notifications->take(10) as $notification)
                        <div class="px-4 py-3 border-b border-gray-50 {{ $notification->read_at ? '' : 'bg-primary-50' }}">
                            <div class="text-sm font-medium text-gray-800">{{ $notification->data['title'] ?? '' }}</div>
                            <div class="text-xs text-gray-500 mt-0.5">{{ $notification->data['message'] ?? '' }}</div>
                            <div class="text-xs text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</div>
                        </div>
                    @empty
                        <div class="px-4 py-8 text-center text-sm text-gray-500">{{ __('messages.no_notifications') }}</div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- User menu -->
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-gray-100">
                <div class="w-8 h-8 rounded-full bg-primary-600 text-white flex items-center justify-center font-bold text-sm">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <span class="hidden sm:block text-sm font-medium text-gray-700">{{ $user->name }}</span>
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>

            <div x-show="open" x-cloak @click.away="open = false" x-transition
                class="absolute right-0 mt-2 w-48 bg-white rounded-2xl shadow-xl border border-gray-200 py-1 overflow-hidden">
                <div class="px-4 py-2 border-b border-gray-100">
                    <div class="text-sm font-semibold text-gray-800 truncate">{{ $user->name }}</div>
                    <div class="text-xs text-gray-500">{{ $user->email }}</div>
                </div>
                <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">{{ __('messages.profile') }}</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">{{ __('messages.logout') }}</button>
                </form>
            </div>
        </div>
    </div>
</header>

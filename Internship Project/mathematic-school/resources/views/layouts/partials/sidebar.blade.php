<aside class="fixed inset-y-0 left-0 z-40 w-64 bg-gray-900 text-gray-100 transform transition-transform duration-200 lg:translate-x-0"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">

    <div class="flex items-center gap-3 px-5 h-16 border-b border-gray-800">
        <div class="w-9 h-9 rounded-xl bg-primary-600 flex items-center justify-center text-white font-bold">M&S</div>
        <div class="leading-tight min-w-0">
            <div class="font-bold text-sm truncate">{{ __('messages.school_name') }}</div>
            <div class="text-xs text-gray-400">{{ __('messages.tagline') }}</div>
        </div>
    </div>

    <nav class="p-3 space-y-1 overflow-y-auto h-[calc(100vh-4rem)]">
        @php
            $role = auth()->user()->role;
        @endphp

        @if ($role === 'admin')
            @include('layouts.partials.nav-admin')
        @elseif ($role === 'teacher')
            @include('layouts.partials.nav-teacher')
        @elseif ($role === 'parent')
            @include('layouts.partials.nav-parent')
        @else
            @include('layouts.partials.nav-student')
        @endif
    </nav>
</aside>

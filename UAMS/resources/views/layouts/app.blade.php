<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'UAMS') }} — @yield('title', 'Dashboard')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-thumb { background: rgba(100,116,139,.35); border-radius: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
    </style>
</head>
<body class="font-sans antialiased bg-slate-50 text-slate-800">
<div x-data="{ mobileSidebar: false }" class="min-h-screen flex">

    <!-- Mobile overlay -->
    <div x-cloak x-show="mobileSidebar" x-transition.opacity class="fixed inset-0 z-30 bg-slate-950/60 backdrop-blur-sm lg:hidden" @click="mobileSidebar = false"></div>

    <!-- Sidebar -->
    <aside :class="mobileSidebar ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
           class="fixed inset-y-0 left-0 z-40 w-72 transform transition-transform duration-300 ease-out lg:static lg:shrink-0
                  bg-slate-950 bg-gradient-to-b from-slate-900 via-slate-950 to-indigo-950/60 text-white flex flex-col">
        <!-- Brand -->
        <div class="flex items-center gap-3 h-[72px] px-6 border-b border-white/10 shrink-0">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center shadow-lg shadow-indigo-500/30 group-hover:scale-105 transition-transform">
                    <svg class="w-5.5 h-5.5 w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3L2 8l10 5 10-5-10-5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M5 10.5V15c0 1.7 3.1 3 7 3s7-1.3 7-3v-4.5"/></svg>
                </div>
                <div>
                    <p class="font-extrabold tracking-tight text-lg leading-none">UAMS</p>
                    <p class="text-[10px] uppercase tracking-[0.18em] text-indigo-300/80 mt-0.5">Attendance System</p>
                </div>
            </a>
            <button @click="mobileSidebar = false" class="lg:hidden ml-auto text-slate-400 hover:text-white transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        @php
            $role = auth()->user()->role->value ?? '';
            $roleLabels = ['super_admin' => 'Super Admin', 'admin' => 'Administrator', 'teacher' => 'Teacher', 'student' => 'Student'];
            $navItem = function (string $href, bool $active, string $label, string $path) {
                echo '<a href="' . $href . '" class="group flex items-center gap-3 px-3 py-2.5 mx-3 rounded-xl text-sm font-medium transition-all duration-200 '
                    . ($active
                        ? 'bg-gradient-to-r from-indigo-500/90 to-violet-500/90 text-white shadow-lg shadow-indigo-950/50'
                        : 'text-slate-400 hover:text-white hover:bg-white/5')
                    . '">';
                echo '<svg class="w-5 h-5 shrink-0 ' . ($active ? 'text-white' : 'text-slate-500 group-hover:text-indigo-300') . ' transition-colors" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="' . $path . '"/></svg>';
                echo '<span>' . $label . '</span>';
                if ($active) echo '<span class="ml-auto w-1.5 h-1.5 rounded-full bg-white/90"></span>';
                echo '</a>';
            };
            $navLabel = function (string $text) {
                echo '<p class="px-6 pt-5 pb-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">' . $text . '</p>';
            };
        @endphp

        <nav class="flex-1 overflow-y-auto pb-4">
            @if(in_array($role, ['super_admin', 'admin']))
                {!! $navLabel('Overview') !!}
                {!! $navItem(route('admin.dashboard'), request()->routeIs('admin.dashboard'), 'Dashboard', 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6') !!}

                {!! $navLabel('Academics') !!}
                {!! $navItem(route('admin.faculties.index'), request()->routeIs('admin.faculties.*'), 'Faculties', 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4') !!}
                {!! $navItem(route('admin.departments.index'), request()->routeIs('admin.departments.*'), 'Departments', 'M3 21V5a2 2 0 012-2h6v18M3 21h18M11 21V9h8a2 2 0 012 2v10M7 7h1m-1 4h1m4-4h1m-1 4h1') !!}
                {!! $navItem(route('admin.programs.index'), request()->routeIs('admin.programs.*'), 'Programs', 'M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.42A12 12 0 019 20.05L12 14z') !!}
                {!! $navItem(route('admin.courses.index'), request()->routeIs('admin.courses.*'), 'Courses', 'M4 19.5A2.5 2.5 0 016.5 17H20M4 19.5A2.5 2.5 0 016.5 22H20V2H6.5A2.5 2.5 0 004 4.5v15z') !!}
                {!! $navItem(route('admin.academic-terms.index'), request()->routeIs('admin.academic-terms.*'), 'Academic Terms', 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z') !!}
                {!! $navItem(route('admin.class-sections.index'), request()->routeIs('admin.class-sections.*'), 'Class Sections', 'M17 20h5v-2a3 3 0 00-5.36-1.86M17 20H7m10 0v-2c0-.66-.13-1.28-.36-1.86M7 20H2v-2a3 3 0 015.36-1.86M7 20v-2c0-.66.13-1.28.36-1.86m0 0a5 5 0 019.28 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z') !!}

                {!! $navLabel('People') !!}
                {!! $navItem(route('admin.students.index'), request()->routeIs('admin.students.*'), 'Students', 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z') !!}
                {!! $navItem(route('admin.teachers.index'), request()->routeIs('admin.teachers.*'), 'Teachers', 'M12 6.25v13m0-13C10.83 5.48 9.25 5 7.5 5S4.17 5.48 3 6.25v13C4.17 18.73 5.75 19 7.5 19s3.33.52 4.5 1.25m0-13C13.17 5.48 14.75 5 16.5 5c1.75 0 3.33.48 4.5 1.25v13C19.83 18.73 18.25 19 16.5 19s-3.33.52-4.5 1.25') !!}
                {!! $navItem(route('admin.enrollments.index'), request()->routeIs('admin.enrollments.*'), 'Enrollments', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4') !!}

                {!! $navLabel('Insights') !!}
                {!! $navItem(route('admin.reports.student'), request()->routeIs('admin.reports.*'), 'Reports', 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z') !!}
            @endif

            @if($role === 'teacher')
                {!! $navLabel('Teaching') !!}
                {!! $navItem(route('teacher.dashboard'), request()->routeIs('teacher.dashboard'), 'Dashboard', 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6') !!}
                {!! $navItem(route('teacher.sections.index'), request()->routeIs('teacher.sections.*'), 'My Sections', 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4') !!}
            @endif

            @if($role === 'student')
                {!! $navLabel('Learning') !!}
                {!! $navItem(route('student.dashboard'), request()->routeIs('student.dashboard'), 'Dashboard', 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6') !!}
                {!! $navItem(route('student.attendance.index'), request()->routeIs('student.attendance.*'), 'My Attendance', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4') !!}
                {!! $navItem(route('student.report'), request()->routeIs('student.report'), 'My Report', 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z') !!}
            @endif
        </nav>

        <!-- User card pinned at bottom -->
        <div class="border-t border-white/10 p-3 shrink-0">
            <div class="flex items-center gap-3 px-2 py-2 rounded-xl hover:bg-white/5 transition">
                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-indigo-400 to-violet-600 flex items-center justify-center text-sm font-bold text-white shadow-md shadow-indigo-950/40">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-semibold truncate">{{ auth()->user()->name }}</p>
                    <p class="text-[11px] text-indigo-300/80 truncate">{{ $roleLabels[$role] ?? $role }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="mt-1 w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-400 hover:text-white hover:bg-white/5 transition">
                    <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Sign out
                </button>
            </form>
        </div>
    </aside>

    <!-- Main -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Topbar -->
        <header class="sticky top-0 z-20 h-[72px] bg-white/80 backdrop-blur-xl border-b border-slate-200/70">
            <div class="flex items-center justify-between h-full px-4 sm:px-8">
                <button @click="mobileSidebar = true" class="lg:hidden -ml-1 p-2 rounded-lg text-slate-500 hover:bg-slate-100 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>

                <div class="hidden md:flex items-center gap-2 text-sm text-slate-500">
                    <span>{{ now()->format('D, M j Y') }}</span>
                </div>

                <div class="flex items-center gap-3 ml-auto">
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open"
                                class="flex items-center gap-2.5 pl-1.5 pr-3 py-1.5 rounded-full border border-slate-200 bg-white hover:border-indigo-200 hover:shadow-sm transition">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-xs font-bold text-white">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                            <span class="hidden sm:block text-sm font-medium text-slate-700 max-w-[160px] truncate">{{ auth()->user()->name }}</span>
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>

                        <div x-cloak x-show="open" @click.away="open = false" x-transition.opacity.duration.150ms
                             class="absolute right-0 mt-2 w-60 bg-white rounded-2xl shadow-xl shadow-slate-900/10 border border-slate-100 overflow-hidden z-50">
                            <div class="px-4 py-3.5 bg-gradient-to-r from-indigo-50 to-violet-50 border-b border-slate-100">
                                <p class="text-sm font-semibold text-slate-900 truncate">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-slate-500 truncate">{{ auth()->user()->email }}</p>
                                <span class="inline-flex mt-2 items-center rounded-full bg-indigo-600/10 px-2 py-0.5 text-[11px] font-semibold text-indigo-700">{{ $roleLabels[$role] ?? $role }}</span>
                            </div>
                            <div class="p-1.5">
                                <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-slate-700 hover:bg-slate-50 transition">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    Profile settings
                                </a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-red-600 hover:bg-red-50 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                        Sign out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Flash toasts -->
        <div class="fixed top-5 right-5 z-50 space-y-2 w-[calc(100%-2.5rem)] max-w-sm" x-data="{ toasts: [] }"
             x-init="
                @if(session('success')) toasts.push({ id: 1, type: 'success', text: @js(session('success')) }) @endif
                @if(session('error')) toasts.push({ id: 2, type: 'error', text: @js(session('error')) }) @endif
                @foreach($errors->all() as $__e) toasts.push({ id: {{ ($loop->index ?? 0) + 10 }}, type: 'error', text: @js($__e) }) @endforeach
                toasts.forEach(t => setTimeout(() => toasts = toasts.filter(x => x.id !== t.id), 6000))
             ">
            <template x-for="toast in toasts" :key="toast.id">
                <div x-transition.opacity.duration.300ms
                     class="flex items-start gap-3 rounded-2xl px-4 py-3.5 shadow-lg shadow-slate-900/10 border backdrop-blur text-sm font-medium"
                     :class="toast.type === 'success' ? 'bg-emerald-50/95 border-emerald-200 text-emerald-800' : 'bg-red-50/95 border-red-200 text-red-800'">
                    <svg x-show="toast.type === 'success'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <svg x-show="toast.type === 'error'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="flex-1" x-text="toast.text"></span>
                    <button @click="toasts = toasts.filter(x => x.id !== toast.id)" class="opacity-40 hover:opacity-80">&times;</button>
                </div>
            </template>
        </div>

        <!-- Page content -->
        <main class="flex-1 p-4 sm:p-8">
            <div class="max-w-7xl mx-auto">
                @if (isset($header))
                    <div class="mb-6">
                        <div class="rounded-2xl bg-white border border-slate-100 shadow-sm px-5 py-4">
                            {{ $header }}
                        </div>
                    </div>
                @endif
                {{ $slot }}
            </div>
        </main>

        <footer class="px-8 py-4 text-xs text-slate-400 border-t border-slate-100">
            {{ config('app.name', 'UAMS') }} · University Attendance Management System
        </footer>
    </div>
</div>
</body>
</html>

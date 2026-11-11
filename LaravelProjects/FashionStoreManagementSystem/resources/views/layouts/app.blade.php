<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', store_name()) — {{ store_name() }}</title>
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
<body>
<div class="app-shell">
    <aside class="sidebar" id="appSidebar">
        <div class="sidebar-brand">
            @if (store_logo_url())
                <img src="{{ store_logo_url() }}" alt="{{ store_name() }}" class="sidebar-logo">
            @else
                <span class="sidebar-brand-icon"><i class="bi bi-bag-heart-fill"></i></span>
            @endif
            <div>
                <strong>{{ store_name() }}</strong>
                <small>{{ $appSettings['store_tagline'] ?? 'Fashion Store' }}</small>
            </div>
        </div>

        <nav class="sidebar-nav">
            <span class="sidebar-label">Main</span>
            <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span>
            </a>
            <a href="{{ route('pos.index') }}" class="sidebar-link {{ request()->routeIs('pos.*') ? 'active' : '' }}">
                <i class="bi bi-shop"></i><span>Point of Sale</span>
            </a>
            <a href="{{ route('orders.index') }}" class="sidebar-link {{ request()->routeIs('orders.*') ? 'active' : '' }}">
                <i class="bi bi-receipt-cutoff"></i><span>Orders & Sales</span>
            </a>

            @if (auth()->user()->isAdmin() || auth()->user()->isManager())
                <span class="sidebar-label">Catalogue</span>
                <a href="{{ route('products.index') }}" class="sidebar-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
                    <i class="bi bi-box-seam-fill"></i><span>Products</span>
                </a>
                <a href="{{ route('categories.index') }}" class="sidebar-link {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                    <i class="bi bi-tags-fill"></i><span>Categories</span>
                </a>
                <a href="{{ route('customers.index') }}" class="sidebar-link {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                    <i class="bi bi-people-fill"></i><span>Customers</span>
                </a>

                <span class="sidebar-label">Operations</span>
                <a href="{{ route('inventory.index') }}" class="sidebar-link {{ request()->routeIs('inventory.*') ? 'active' : '' }}">
                    <i class="bi bi-boxes"></i><span>Inventory</span>
                </a>
                <a href="{{ route('reports.sales') }}" class="sidebar-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                    <i class="bi bi-graph-up-arrow"></i><span>Reports</span>
                </a>
            @endif

            @if (auth()->user()->isAdmin())
                <span class="sidebar-label">Administration</span>
                <a href="{{ route('staff.index') }}" class="sidebar-link {{ request()->routeIs('staff.*') ? 'active' : '' }}">
                    <i class="bi bi-person-badge-fill"></i><span>Staff</span>
                </a>
                <a href="{{ route('settings.index') }}" class="sidebar-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                    <i class="bi bi-gear-fill"></i><span>Settings</span>
                </a>
            @endif
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                <div class="flex-grow-1">
                    <strong>{{ auth()->user()->name }}</strong>
                    <small><span class="badge badge-role">{{ auth()->user()->roleLabel() }}</span></small>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn-icon" title="Log out" onclick="event.preventDefault(); if(confirm('Log out?')) this.closest('form').submit();">
                        <i class="bi bi-box-arrow-right"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="app-main">
        <header class="topbar">
            <button class="btn-icon d-lg-none" id="sidebarToggle" aria-label="Toggle menu"><i class="bi bi-list"></i></button>
            <div class="topbar-title">
                <h5 class="mb-0">@yield('heading', store_name())</h5>
                <small class="text-muted">@yield('subheading')</small>
            </div>
            <div class="ms-auto d-flex align-items-center gap-2">
                <a href="{{ route('pos.index') }}" class="btn btn-sm btn-outline-primary rounded-pill d-none d-sm-inline-flex">
                    <i class="bi bi-shop me-1"></i> Open POS
                </a>
                <x-theme-toggle />
                <a href="{{ route('profile.edit') }}" class="btn btn-sm btn-soft rounded-pill" title="My profile">
                    <i class="bi bi-person-circle me-1"></i>{{ auth()->user()->name }}
                </a>
            </div>
        </header>

        <main class="app-content">
            @yield('content')
        </main>
    </div>
</div>

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

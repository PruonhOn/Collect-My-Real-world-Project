<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Logistics ERP'))</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/js/admin.js'])
    @stack('styles')
</head>
<body>
    <div class="wrapper">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header d-flex align-items-center px-3 py-3">
                <a href="{{ route('admin.dashboard') }}" class="text-decoration-none d-flex align-items-center">
                    <div class="sidebar-logo d-flex align-items-center justify-content-center rounded bg-primary" style="width: 36px; height: 36px;">
                        <i class="bi bi-truck text-white fs-5"></i>
                    </div>
                    <span class="sidebar-brand-text ms-2 fw-bold fs-5 text-white">Logi<span class="text-primary">Pro</span></span>
                </a>
                <button class="btn btn-sm text-white ms-auto d-lg-none" id="sidebarClose" type="button">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <hr class="sidebar-divider my-0">

            <div class="sidebar-body flex-grow-1 overflow-auto">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                            <i class="bi bi-speedometer2"></i>
                            <span class="nav-text">Dashboard</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('shipments.index') }}" class="nav-link {{ request()->routeIs('shipments.*') ? 'active' : '' }}">
                            <i class="bi bi-box-seam"></i>
                            <span class="nav-text">Shipments</span>
                            @if(isset($pendingShipmentsCount) && $pendingShipmentsCount > 0)
                                <span class="badge bg-danger rounded-pill ms-auto">{{ $pendingShipmentsCount }}</span>
                            @endif
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('customers.index') }}" class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                            <i class="bi bi-people"></i>
                            <span class="nav-text">Customers</span>
                        </a>
                    </li>

                    @php $warehousesActive = request()->routeIs('warehouses.*') || request()->routeIs('inventory.*') || request()->routeIs('warehouse-locations.*'); @endphp
                    <li class="nav-item">
                        <a href="#warehousesMenu" class="nav-link {{ $warehousesActive ? 'active' : '' }}" data-bs-toggle="collapse" role="button" aria-expanded="{{ $warehousesActive ? 'true' : 'false' }}">
                            <i class="bi bi-building"></i>
                            <span class="nav-text">Warehouses</span>
                            <i class="bi bi-chevron-down nav-arrow ms-auto"></i>
                        </a>
                        <ul class="nav-submenu collapse {{ $warehousesActive ? 'show' : '' }}" id="warehousesMenu">
                            <li>
                                <a href="{{ route('warehouses.index') }}" class="nav-link {{ request()->routeIs('warehouses.*') && !request()->routeIs('warehouse-locations.*') ? 'active' : '' }}">
                                    <i class="bi bi-circle"></i>
                                    <span class="nav-text">Warehouses</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('inventory.index') }}" class="nav-link {{ request()->routeIs('inventory.*') ? 'active' : '' }}">
                                    <i class="bi bi-circle"></i>
                                    <span class="nav-text">Inventory</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('warehouse-locations.index') }}" class="nav-link {{ request()->routeIs('warehouse-locations.*') ? 'active' : '' }}">
                                    <i class="bi bi-circle"></i>
                                    <span class="nav-text">Locations</span>
                                </a>
                            </li>
                        </ul>
                    </li>

                    @php $fleetActive = request()->routeIs('vehicles.*') || request()->routeIs('vehicle-maintenances.*'); @endphp
                    <li class="nav-item">
                        <a href="#fleetMenu" class="nav-link {{ $fleetActive ? 'active' : '' }}" data-bs-toggle="collapse" role="button" aria-expanded="{{ $fleetActive ? 'true' : 'false' }}">
                            <i class="bi bi-truck"></i>
                            <span class="nav-text">Fleet</span>
                            <i class="bi bi-chevron-down nav-arrow ms-auto"></i>
                        </a>
                        <ul class="nav-submenu collapse {{ $fleetActive ? 'show' : '' }}" id="fleetMenu">
                            <li>
                                <a href="{{ route('vehicles.index') }}" class="nav-link {{ request()->routeIs('vehicles.*') ? 'active' : '' }}">
                                    <i class="bi bi-circle"></i>
                                    <span class="nav-text">Vehicles</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('vehicle-maintenances.index') }}" class="nav-link {{ request()->routeIs('vehicle-maintenances.*') ? 'active' : '' }}">
                                    <i class="bi bi-circle"></i>
                                    <span class="nav-text">Maintenance</span>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('drivers.index') }}" class="nav-link {{ request()->routeIs('drivers.*') ? 'active' : '' }}">
                            <i class="bi bi-person-badge"></i>
                            <span class="nav-text">Drivers</span>
                        </a>
                    </li>

                    @php $routesActive = request()->routeIs('routes.*') || request()->routeIs('deliveries.*'); @endphp
                    <li class="nav-item">
                        <a href="#routesMenu" class="nav-link {{ $routesActive ? 'active' : '' }}" data-bs-toggle="collapse" role="button" aria-expanded="{{ $routesActive ? 'true' : 'false' }}">
                            <i class="bi bi-signpost-2"></i>
                            <span class="nav-text">Routes & Deliveries</span>
                            <i class="bi bi-chevron-down nav-arrow ms-auto"></i>
                        </a>
                        <ul class="nav-submenu collapse {{ $routesActive ? 'show' : '' }}" id="routesMenu">
                            <li>
                                <a href="{{ route('routes.index') }}" class="nav-link {{ request()->routeIs('routes.*') ? 'active' : '' }}">
                                    <i class="bi bi-circle"></i>
                                    <span class="nav-text">Routes</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('deliveries.index') }}" class="nav-link {{ request()->routeIs('deliveries.*') ? 'active' : '' }}">
                                    <i class="bi bi-circle"></i>
                                    <span class="nav-text">Deliveries</span>
                                </a>
                            </li>
                        </ul>
                    </li>

                    @php $financeActive = request()->routeIs('invoices.*') || request()->routeIs('payments.*') || request()->routeIs('transactions.*') || request()->routeIs('expenses.*') || request()->routeIs('finance.*'); @endphp
                    <li class="nav-item">
                        <a href="#financeMenu" class="nav-link {{ $financeActive ? 'active' : '' }}" data-bs-toggle="collapse" role="button" aria-expanded="{{ $financeActive ? 'true' : 'false' }}">
                            <i class="bi bi-cash-stack"></i>
                            <span class="nav-text">Invoices & Finance</span>
                            <i class="bi bi-chevron-down nav-arrow ms-auto"></i>
                        </a>
                        <ul class="nav-submenu collapse {{ $financeActive ? 'show' : '' }}" id="financeMenu">
                            <li>
                                <a href="{{ route('invoices.index') }}" class="nav-link {{ request()->routeIs('invoices.*') ? 'active' : '' }}">
                                    <i class="bi bi-circle"></i>
                                    <span class="nav-text">Invoices</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('payments.index') }}" class="nav-link {{ request()->routeIs('payments.*') ? 'active' : '' }}">
                                    <i class="bi bi-circle"></i>
                                    <span class="nav-text">Payments</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('transactions.index') }}" class="nav-link {{ request()->routeIs('transactions.*') ? 'active' : '' }}">
                                    <i class="bi bi-circle"></i>
                                    <span class="nav-text">Transactions</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('expenses.index') }}" class="nav-link {{ request()->routeIs('expenses.*') ? 'active' : '' }}">
                                    <i class="bi bi-circle"></i>
                                    <span class="nav-text">Expenses</span>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                            <i class="bi bi-bar-chart"></i>
                            <span class="nav-text">Reports</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('notifications.index') }}" class="nav-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
                            <i class="bi bi-bell"></i>
                            <span class="nav-text">Notifications</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('audit-logs.index') }}" class="nav-link {{ request()->routeIs('audit-logs.*') ? 'active' : '' }}">
                            <i class="bi bi-journal-text"></i>
                            <span class="nav-text">Audit Logs</span>
                        </a>
                    </li>

                    <li class="nav-divider"><hr class="my-2"></li>

                    @php $adminActive = request()->routeIs('admin.*') || request()->routeIs('branches.*'); @endphp
                    <li class="nav-item">
                        <small class="nav-section-label px-3 text-muted text-uppercase"><span class="nav-text">Admin</span></small>
                    </li>
                    <li class="nav-item">
                        <a href="#adminMenu" class="nav-link {{ $adminActive ? 'active' : '' }}" data-bs-toggle="collapse" role="button" aria-expanded="{{ $adminActive ? 'true' : 'false' }}">
                            <i class="bi bi-gear"></i>
                            <span class="nav-text">Admin Section</span>
                            <i class="bi bi-chevron-down nav-arrow ms-auto"></i>
                        </a>
                        <ul class="nav-submenu collapse {{ $adminActive ? 'show' : '' }}" id="adminMenu">
                            <li>
                                <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                                    <i class="bi bi-circle"></i>
                                    <span class="nav-text">Users</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.roles.index') }}" class="nav-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                                    <i class="bi bi-circle"></i>
                                    <span class="nav-text">Roles</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('branches.index') }}" class="nav-link {{ request()->routeIs('branches.*') ? 'active' : '' }}">
                                    <i class="bi bi-circle"></i>
                                    <span class="nav-text">Branches</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.settings.index') }}" class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                                    <i class="bi bi-circle"></i>
                                    <span class="nav-text">Settings</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>

            <div class="sidebar-footer p-3 border-top border-secondary">
                <div class="d-flex align-items-center text-white">
                    <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center" style="width: 34px; height: 34px; font-size: 14px; flex-shrink: 0;">
                        {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="ms-2 nav-text overflow-hidden">
                        <small class="d-block text-truncate">{{ Auth::user()->name ?? 'User' }}</small>
                        <small class="text-muted" style="font-size: 11px;">{{ Auth::user()->email ?? '' }}</small>
                    </div>
                </div>
            </div>
        </aside>

        <div class="main-content" id="mainContent">
            <nav class="topbar d-flex align-items-center px-3 px-lg-4">
                <div class="d-flex align-items-center">
                    <button class="btn btn-link text-dark text-decoration-none d-none d-lg-flex p-1" id="sidebarToggle" type="button">
                        <i class="bi bi-list fs-5"></i>
                    </button>
                    <button class="btn btn-link text-dark text-decoration-none d-lg-none p-1" id="sidebarMobileToggle" type="button">
                        <i class="bi bi-list fs-5"></i>
                    </button>
                </div>

                <div class="mx-3 d-none d-md-block">
                    <form action="#" method="GET" class="d-flex">
                        <div class="input-group input-group-sm" style="width: 300px;">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="bi bi-search text-muted"></i>
                            </span>
                            <input type="text" class="form-control border-start-0 ps-0" placeholder="Search shipments, customers..." aria-label="Search">
                        </div>
                    </form>
                </div>

                <div class="ms-auto"></div>

                <div class="d-flex align-items-center gap-1 gap-lg-2">
                    <button class="btn btn-link text-dark text-decoration-none p-1 position-relative" id="darkModeToggle" type="button">
                        <i class="bi bi-moon-fill fs-5"></i>
                    </button>

                    <div class="dropdown">
                        <button class="btn btn-link text-dark text-decoration-none p-1 position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-bell fs-5"></i>
                            @if(isset($unreadNotificationsCount) && $unreadNotificationsCount > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 9px; padding: 3px 5px;">
                                    {{ $unreadNotificationsCount > 99 ? '99+' : $unreadNotificationsCount }}
                                </span>
                            @endif
                        </button>
                        <div class="dropdown-menu dropdown-menu-end shadow" style="width: 340px;">
                            <div class="dropdown-header d-flex justify-content-between align-items-center">
                                <strong>Notifications</strong>
                                @if(isset($unreadNotificationsCount) && $unreadNotificationsCount > 0)
                                    <a href="{{ route('notifications.mark-all-as-read') }}" class="small text-decoration-none">Mark all read</a>
                                @endif
                            </div>
                            <div class="dropdown-divider"></div>
                            <div class="px-3 py-4 text-muted text-center small">
                                <i class="bi bi-bell-slash d-block fs-3 mb-2"></i>
                                No new notifications
                            </div>
                            <div class="dropdown-divider"></div>
                            <a href="{{ route('notifications.index') }}" class="dropdown-item text-center small py-2">View all notifications</a>
                        </div>
                    </div>

                    <div class="dropdown">
                        <button class="btn btn-link text-dark text-decoration-none d-flex align-items-center p-1 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white" style="width: 32px; height: 32px; font-size: 13px; flex-shrink: 0;">
                                {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                            </div>
                            <span class="ms-2 d-none d-md-inline small">{{ Auth::user()->name ?? 'User' }}</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li><h6 class="dropdown-header">{{ Auth::user()->name ?? 'User' }}</h6></li>
                            <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2"></i>Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            @hasSection('breadcrumbs')
                <nav aria-label="breadcrumb" class="px-4 pt-3 pb-0">
                    <ol class="breadcrumb mb-0 small">
                        @yield('breadcrumbs')
                    </ol>
                </nav>
            @endif

            @hasSection('page-title')
                <div class="px-4 pt-3 pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h4 class="mb-0 fw-bold">@yield('page-title')</h4>
                        @hasSection('page-subtitle')
                            <small class="text-muted">@yield('page-subtitle')</small>
                        @endif
                    </div>
                    @hasSection('page-actions')
                        <div>
                            @yield('page-actions')
                        </div>
                    @endif
                </div>
            @endif

            <div class="px-4 pt-3">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if(session('warning'))
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-circle me-2"></i>{{ session('warning') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if(session('info'))
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <i class="bi bi-info-circle me-2"></i>{{ session('info') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
            </div>

            <main class="p-4">
                @yield('content')
            </main>

            <footer class="px-4 py-3 text-center text-muted small border-top bg-white">
                &copy; {{ date('Y') }} {{ config('app.name', 'Logistics Pro') }}. All rights reserved.
                <span class="d-none d-sm-inline">| Laravel v{{ Illuminate\Foundation\Application::VERSION }}</span>
            </footer>
        </div>
    </div>

    @stack('scripts')
</body>
</html>

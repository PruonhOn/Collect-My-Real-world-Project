<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'K&N Mart Management')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    @stack('styles')
</head>
<body>
    <div class="d-flex" style="min-height: 100vh;">
        <div class="d-flex flex-column flex-shrink-0 p-3 text-bg-dark" style="width: 260px; position: fixed; top: 0; left: 0; bottom: 0; z-index: 100; overflow-y: auto;">
            <a href="{{ route('dashboard') }}" class="d-flex align-items-center mb-3 text-white text-decoration-none">
                <i class="bi bi-shop fs-4 me-2"></i>
                <span class="fs-5 fw-semibold">K&N Mart</span>
            </a>
            <hr>
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item"><a href="{{ route('dashboard') }}" class="nav-link text-white {{ request()->routeIs('dashboard') ? 'active' : '' }}"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                <li><a href="{{ route('categories.index') }}" class="nav-link text-white {{ request()->routeIs('categories.*') ? 'active' : '' }}"><i class="bi bi-tags me-2"></i>Categories</a></li>
                <li><a href="{{ route('brands.index') }}" class="nav-link text-white {{ request()->routeIs('brands.*') ? 'active' : '' }}"><i class="bi bi-bookmark me-2"></i>Brands</a></li>
                <li><a href="{{ route('units.index') }}" class="nav-link text-white {{ request()->routeIs('units.*') ? 'active' : '' }}"><i class="bi bi-rulers me-2"></i>Units</a></li>
                <li><a href="{{ route('products.index') }}" class="nav-link text-white {{ request()->routeIs('products.*') ? 'active' : '' }}"><i class="bi bi-box me-2"></i>Products</a></li>
                <li><a href="{{ route('suppliers.index') }}" class="nav-link text-white {{ request()->routeIs('suppliers.*') ? 'active' : '' }}"><i class="bi bi-truck me-2"></i>Suppliers</a></li>
                <li><a href="{{ route('customers.index') }}" class="nav-link text-white {{ request()->routeIs('customers.*') ? 'active' : '' }}"><i class="bi bi-people me-2"></i>Customers</a></li>
                <li><a href="{{ route('employees.index') }}" class="nav-link text-white {{ request()->routeIs('employees.*') ? 'active' : '' }}"><i class="bi bi-person-badge me-2"></i>Employees</a></li>
                <li><a href="{{ route('purchases.index') }}" class="nav-link text-white {{ request()->routeIs('purchases.*') ? 'active' : '' }}"><i class="bi bi-cart-plus me-2"></i>Purchases</a></li>
                <li><a href="{{ route('sales.index') }}" class="nav-link text-white {{ request()->routeIs('sales.*') ? 'active' : '' }}"><i class="bi bi-cart-check me-2"></i>Sales</a></li>
                <li><a href="{{ route('financial.index') }}" class="nav-link text-white {{ request()->routeIs('financial.*') ? 'active' : '' }}"><i class="bi bi-cash-stack me-2"></i>Financial</a></li>
                <li><a href="{{ route('reports.index') }}" class="nav-link text-white {{ request()->routeIs('reports.*') ? 'active' : '' }}"><i class="bi bi-file-earmark-bar-graph me-2"></i>Reports</a></li>
                <li><a href="{{ route('settings.index') }}" class="nav-link text-white {{ request()->routeIs('settings.*') ? 'active' : '' }}"><i class="bi bi-gear me-2"></i>Settings</a></li>
            </ul>
            <hr>
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle fs-5 me-2"></i>
                    <strong>{{ Auth::user()->name ?? 'User' }}</strong>
                </a>
                <ul class="dropdown-menu dropdown-menu-dark text-small shadow">
                    <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><i class="bi bi-box-arrow-right me-2"></i>Sign out</a></li>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                </ul>
            </div>
        </div>

        <div class="flex-grow-1" style="margin-left: 260px;">
            <nav class="navbar navbar-expand navbar-light bg-light border-bottom px-3 shadow-sm">
                <div class="container-fluid">
                    <div class="d-flex align-items-center">
                        <span class="navbar-text fs-5 fw-semibold">@yield('page-title', 'Dashboard')</span>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <span class="text-muted small"><i class="bi bi-calendar3 me-1"></i>{{ now()->format('d M, Y') }}</span>
                    </div>
                </div>
            </nav>

            <main class="p-4">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    @stack('scripts')
</body>
</html>

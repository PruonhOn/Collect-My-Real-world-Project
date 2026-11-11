<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MiniMart Management System') - {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        :root {
            --sidebar-width: 260px;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bs-body-bg);
        }
        .wrapper {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: var(--sidebar-width);
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 100;
            transition: all 0.3s;
            overflow-y: auto;
        }
        .sidebar .nav-link {
            padding: 0.7rem 1rem;
            border-radius: 0.5rem;
            margin: 2px 0;
            color: var(--bs-nav-link-color);
        }
        .sidebar .nav-link:hover {
            background-color: rgba(var(--bs-primary-rgb), 0.1);
        }
        .sidebar .nav-link.active {
            background-color: var(--bs-primary);
            color: #fff;
        }
        .sidebar .nav-link i {
            width: 24px;
            text-align: center;
        }
        .sidebar .sub-menu {
            padding-left: 2rem;
        }
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            min-height: 100vh;
            transition: all 0.3s;
        }
        .navbar-top {
            position: sticky;
            top: 0;
            z-index: 99;
        }
        .page-content {
            padding: 1.5rem;
        }
        .stat-card {
            border-radius: 1rem;
            padding: 1.2rem;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-3px);
        }
        .stat-card .icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .card {
            border-radius: 1rem;
            border: none;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
        }
        .card-header {
            background-color: transparent;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 1rem 1.25rem;
        }
        .btn {
            border-radius: 0.5rem;
            padding: 0.5rem 1rem;
        }
        .form-control, .form-select {
            border-radius: 0.5rem;
            padding: 0.5rem 0.75rem;
        }
        .table th {
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .low-stock {
            background-color: rgba(220, 53, 69, 0.1);
        }
        .badge-low-stock {
            background-color: #dc3545;
            color: #fff;
        }
        .receipt {
            max-width: 320px;
            margin: 0 auto;
            font-size: 0.85rem;
        }
        .receipt .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 0.5rem;
            margin-bottom: 0.5rem;
        }
        .receipt table {
            width: 100%;
        }
        .receipt table td {
            padding: 2px 0;
        }
        .receipt .total {
            border-top: 1px dashed #000;
            padding-top: 0.5rem;
            margin-top: 0.5rem;
        }
        .theme-toggle {
            cursor: pointer;
        }
        .pos-container {
            height: calc(100vh - 80px);
        }
        .pos-products {
            max-height: calc(100vh - 350px);
            overflow-y: auto;
        }
        .pos-cart {
            max-height: calc(100vh - 300px);
            overflow-y: auto;
        }
        .product-card {
            cursor: pointer;
            transition: all 0.2s;
            border: 2px solid transparent;
        }
        .product-card:hover {
            border-color: var(--bs-primary);
            transform: scale(1.02);
        }
        @media (max-width: 768px) {
            .sidebar {
                margin-left: calc(var(--sidebar-width) * -1);
            }
            .sidebar.show {
                margin-left: 0;
            }
            .main-content {
                margin-left: 0;
            }
        }
        [data-bs-theme="dark"] .card {
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
        }
        [data-bs-theme="dark"] .sidebar {
            border-right: 1px solid rgba(255,255,255,0.1);
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="wrapper">
        @auth
        <nav class="sidebar bg-body-tertiary border-end" id="sidebar">
            <div class="p-3 border-bottom">
                <a href="{{ route('dashboard') }}" class="text-decoration-none">
                    <h5 class="mb-0 text-primary"><i class="fas fa-store me-2"></i>MiniMart</h5>
                    <small class="text-muted">Management System</small>
                </a>
            </div>
            <div class="p-3">
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                            <i class="fas fa-chart-pie"></i> Dashboard
                        </a>
                    </li>

                    @if(auth()->user()->isAdmin())
                    <li class="nav-item mt-2">
                        <small class="text-muted text-uppercase px-2">Management</small>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}">
                            <i class="fas fa-box"></i> Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}" href="{{ route('categories.index') }}">
                            <i class="fas fa-tags"></i> Categories
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('inventory.*') ? 'active' : '' }}" href="{{ route('inventory.index') }}">
                            <i class="fas fa-warehouse"></i> Inventory
                        </a>
                    </li>
                    @endif

                    <li class="nav-item mt-2">
                        <small class="text-muted text-uppercase px-2">Sales</small>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('sales.pos') ? 'active' : '' }}" href="{{ route('sales.pos') }}">
                            <i class="fas fa-shopping-cart"></i> POS
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('sales.index') ? 'active' : '' }}" href="{{ route('sales.index') }}">
                            <i class="fas fa-receipt"></i> Sales History
                        </a>
                    </li>

                    @if(auth()->user()->isAdmin())
                    <li class="nav-item mt-2">
                        <small class="text-muted text-uppercase px-2">Finance</small>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('expenses.*') ? 'active' : '' }}" href="{{ route('expenses.index') }}">
                            <i class="fas fa-money-bill-wave"></i> Expenses
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">
                            <i class="fas fa-file-alt"></i> Reports
                        </a>
                    </li>
                    @endif
                </ul>
            </div>
        </nav>
        @endauth

        <div class="main-content">
            @auth
            <nav class="navbar-top bg-body-tertiary border-bottom px-4 py-2 d-flex justify-content-between align-items-center">
                <div>
                    <button class="btn btn-sm btn-outline-secondary d-md-none" onclick="document.getElementById('sidebar').classList.toggle('show')">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="theme-toggle btn btn-sm btn-outline-secondary" onclick="toggleTheme()" id="themeToggle">
                        <i class="fas fa-moon"></i>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i> {{ auth()->user()->name }}
                            <span class="badge bg-primary ms-1">{{ auth()->user()->role?->name ?? 'N/A' }}</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header">{{ auth()->user()->email }}</h6></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item"><i class="fas fa-sign-out-alt me-2"></i>Logout</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
            @endauth

            <div class="page-content">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @yield('content')
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function toggleTheme() {
            const html = document.documentElement;
            const current = html.getAttribute('data-bs-theme');
            const next = current === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-bs-theme', next);
            localStorage.setItem('theme', next);
            document.getElementById('themeToggle').innerHTML = next === 'dark'
                ? '<i class="fas fa-sun"></i>'
                : '<i class="fas fa-moon"></i>';
        }

        (function() {
            const saved = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-bs-theme', saved);
        })();

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        function confirmDelete(formId, name = 'item') {
            Swal.fire({
                title: 'Are you sure?',
                text: `Delete "${name}"? This cannot be undone!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(formId).submit();
                }
            });
        }
    </script>
    @stack('scripts')
</body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ session('theme', 'light') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') - {{ config('app.name', 'Combodia Logistics') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body>
    <div class="wrapper">
        {{-- Sidebar --}}
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="{{ route('dashboard') }}" class="sidebar-logo">
                    <i class="bi bi-box-seam" style="font-size: 1.5rem; color: var(--primary-color);"></i>
                    <span>Combodia</span>
                </a>
            </div>

            <div class="sidebar-nav py-2">
                <div class="sidebar-section-title">Main</div>

                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <i class="bi bi-speedometer2"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                </ul>

                <div class="sidebar-section-title">Master Data</div>

                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('admin/branches*') ? 'active' : '' }}" href="#collapseBranches" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/branches*') ? 'true' : 'false' }}">
                            <i class="bi bi-building"></i>
                            <span>Branches</span>
                            <i class="bi bi-chevron-right menu-arrow"></i>
                        </a>
                        <div class="collapse {{ request()->is('admin/branches*') ? 'show' : '' }}" id="collapseBranches">
                            <ul class="sub-menu nav flex-column">
                                <li class="nav-item"><a class="nav-link" href="#">All Branches</a></li>
                                <li class="nav-item"><a class="nav-link" href="#">Add Branch</a></li>
                            </ul>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('admin/warehouses*') ? 'active' : '' }}" href="#collapseWarehouses" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/warehouses*') ? 'true' : 'false' }}">
                            <i class="bi bi-warehouse"></i>
                            <span>Warehouses</span>
                            <i class="bi bi-chevron-right menu-arrow"></i>
                        </a>
                        <div class="collapse {{ request()->is('admin/warehouses*') ? 'show' : '' }}" id="collapseWarehouses">
                            <ul class="sub-menu nav flex-column">
                                <li class="nav-item"><a class="nav-link" href="#">All Warehouses</a></li>
                                <li class="nav-item"><a class="nav-link" href="#">Add Warehouse</a></li>
                            </ul>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('admin/customers*') ? 'active' : '' }}" href="#collapseCustomers" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/customers*') ? 'true' : 'false' }}">
                            <i class="bi bi-people"></i>
                            <span>Customers</span>
                            <i class="bi bi-chevron-right menu-arrow"></i>
                        </a>
                        <div class="collapse {{ request()->is('admin/customers*') ? 'show' : '' }}" id="collapseCustomers">
                            <ul class="sub-menu nav flex-column">
                                <li class="nav-item"><a class="nav-link" href="#">All Customers</a></li>
                                <li class="nav-item"><a class="nav-link" href="#">Add Customer</a></li>
                            </ul>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('admin/suppliers*') ? 'active' : '' }}" href="#collapseSuppliers" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/suppliers*') ? 'true' : 'false' }}">
                            <i class="bi bi-truck"></i>
                            <span>Suppliers</span>
                            <i class="bi bi-chevron-right menu-arrow"></i>
                        </a>
                        <div class="collapse {{ request()->is('admin/suppliers*') ? 'show' : '' }}" id="collapseSuppliers">
                            <ul class="sub-menu nav flex-column">
                                <li class="nav-item"><a class="nav-link" href="#">All Suppliers</a></li>
                                <li class="nav-item"><a class="nav-link" href="#">Add Supplier</a></li>
                            </ul>
                        </div>
                    </li>
                </ul>

                <div class="sidebar-section-title">Fleet Management</div>

                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('admin/vehicles*') ? 'active' : '' }}" href="#collapseVehicles" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/vehicles*') ? 'true' : 'false' }}">
                            <i class="bi bi-car-front"></i>
                            <span>Vehicles</span>
                            <i class="bi bi-chevron-right menu-arrow"></i>
                        </a>
                        <div class="collapse {{ request()->is('admin/vehicles*') ? 'show' : '' }}" id="collapseVehicles">
                            <ul class="sub-menu nav flex-column">
                                <li class="nav-item"><a class="nav-link" href="#">All Vehicles</a></li>
                                <li class="nav-item"><a class="nav-link" href="#">Add Vehicle</a></li>
                            </ul>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('admin/drivers*') ? 'active' : '' }}" href="#collapseDrivers" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/drivers*') ? 'true' : 'false' }}">
                            <i class="bi bi-person-badge"></i>
                            <span>Drivers</span>
                            <i class="bi bi-chevron-right menu-arrow"></i>
                        </a>
                        <div class="collapse {{ request()->is('admin/drivers*') ? 'show' : '' }}" id="collapseDrivers">
                            <ul class="sub-menu nav flex-column">
                                <li class="nav-item"><a class="nav-link" href="#">All Drivers</a></li>
                                <li class="nav-item"><a class="nav-link" href="#">Add Driver</a></li>
                            </ul>
                        </div>
                    </li>
                </ul>

                <div class="sidebar-section-title">Shipments</div>

                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('admin/shipments*') ? 'active' : '' }}" href="#collapseShipments" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/shipments*') ? 'true' : 'false' }}">
                            <i class="bi bi-box-seam"></i>
                            <span>Shipments</span>
                            <i class="bi bi-chevron-right menu-arrow"></i>
                        </a>
                        <div class="collapse {{ request()->is('admin/shipments*') ? 'show' : '' }}" id="collapseShipments">
                            <ul class="sub-menu nav flex-column">
                                <li class="nav-item"><a class="nav-link" href="#">All Shipments</a></li>
                                <li class="nav-item"><a class="nav-link" href="#">Add Shipment</a></li>
                            </ul>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('admin/pickup-requests*') ? 'active' : '' }}" href="#collapsePickup" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/pickup-requests*') ? 'true' : 'false' }}">
                            <i class="bi bi-hand-index"></i>
                            <span>Pickup Requests</span>
                            <i class="bi bi-chevron-right menu-arrow"></i>
                        </a>
                        <div class="collapse {{ request()->is('admin/pickup-requests*') ? 'show' : '' }}" id="collapsePickup">
                            <ul class="sub-menu nav flex-column">
                                <li class="nav-item"><a class="nav-link" href="#">All Requests</a></li>
                                <li class="nav-item"><a class="nav-link" href="#">New Request</a></li>
                            </ul>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('admin/packages*') ? 'active' : '' }}" href="#collapsePackages" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/packages*') ? 'true' : 'false' }}">
                            <i class="bi bi-box"></i>
                            <span>Packages</span>
                            <i class="bi bi-chevron-right menu-arrow"></i>
                        </a>
                        <div class="collapse {{ request()->is('admin/packages*') ? 'show' : '' }}" id="collapsePackages">
                            <ul class="sub-menu nav flex-column">
                                <li class="nav-item"><a class="nav-link" href="#">All Packages</a></li>
                                <li class="nav-item"><a class="nav-link" href="#">Add Package</a></li>
                            </ul>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('admin/dispatching*') ? 'active' : '' }}" href="#collapseDispatching" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/dispatching*') ? 'true' : 'false' }}">
                            <i class="bi bi-diagram-3"></i>
                            <span>Dispatching</span>
                            <i class="bi bi-chevron-right menu-arrow"></i>
                        </a>
                        <div class="collapse {{ request()->is('admin/dispatching*') ? 'show' : '' }}" id="collapseDispatching">
                            <ul class="sub-menu nav flex-column">
                                <li class="nav-item"><a class="nav-link" href="#">Active Dispatches</a></li>
                                <li class="nav-item"><a class="nav-link" href="#">Create Dispatch</a></li>
                            </ul>
                        </div>
                    </li>
                </ul>

                <div class="sidebar-section-title">Routes & Tracking</div>

                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('admin/routes*') ? 'active' : '' }}" href="#collapseRoutes" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/routes*') ? 'true' : 'false' }}">
                            <i class="bi bi-signpost-2"></i>
                            <span>Routes</span>
                            <i class="bi bi-chevron-right menu-arrow"></i>
                        </a>
                        <div class="collapse {{ request()->is('admin/routes*') ? 'show' : '' }}" id="collapseRoutes">
                            <ul class="sub-menu nav flex-column">
                                <li class="nav-item"><a class="nav-link" href="#">All Routes</a></li>
                                <li class="nav-item"><a class="nav-link" href="#">Add Route</a></li>
                            </ul>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('admin/tracking*') ? 'active' : '' }}" href="#collapseTracking" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/tracking*') ? 'true' : 'false' }}">
                            <i class="bi bi-geo-alt"></i>
                            <span>Tracking</span>
                            <i class="bi bi-chevron-right menu-arrow"></i>
                        </a>
                        <div class="collapse {{ request()->is('admin/tracking*') ? 'show' : '' }}" id="collapseTracking">
                            <ul class="sub-menu nav flex-column">
                                <li class="nav-item"><a class="nav-link" href="#">Live Tracking</a></li>
                                <li class="nav-item"><a class="nav-link" href="#">History</a></li>
                            </ul>
                        </div>
                    </li>
                </ul>

                <div class="sidebar-section-title">Warehouse</div>

                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('admin/inventory*') ? 'active' : '' }}" href="#collapseInventory" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/inventory*') ? 'true' : 'false' }}">
                            <i class="bi bi-inboxes"></i>
                            <span>Inventory</span>
                            <i class="bi bi-chevron-right menu-arrow"></i>
                        </a>
                        <div class="collapse {{ request()->is('admin/inventory*') ? 'show' : '' }}" id="collapseInventory">
                            <ul class="sub-menu nav flex-column">
                                <li class="nav-item"><a class="nav-link" href="#">Stock Overview</a></li>
                                <li class="nav-item"><a class="nav-link" href="#">Add Item</a></li>
                            </ul>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('admin/stock-movements*') ? 'active' : '' }}" href="#collapseStockMovements" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/stock-movements*') ? 'true' : 'false' }}">
                            <i class="bi bi-arrow-left-right"></i>
                            <span>Stock Movements</span>
                            <i class="bi bi-chevron-right menu-arrow"></i>
                        </a>
                        <div class="collapse {{ request()->is('admin/stock-movements*') ? 'show' : '' }}" id="collapseStockMovements">
                            <ul class="sub-menu nav flex-column">
                                <li class="nav-item"><a class="nav-link" href="#">Movements Log</a></li>
                                <li class="nav-item"><a class="nav-link" href="#">New Movement</a></li>
                            </ul>
                        </div>
                    </li>
                </ul>

                <div class="sidebar-section-title">Finance</div>

                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('admin/invoices*') ? 'active' : '' }}" href="#collapseInvoices" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/invoices*') ? 'true' : 'false' }}">
                            <i class="bi bi-receipt"></i>
                            <span>Invoices</span>
                            <i class="bi bi-chevron-right menu-arrow"></i>
                        </a>
                        <div class="collapse {{ request()->is('admin/invoices*') ? 'show' : '' }}" id="collapseInvoices">
                            <ul class="sub-menu nav flex-column">
                                <li class="nav-item"><a class="nav-link" href="#">All Invoices</a></li>
                                <li class="nav-item"><a class="nav-link" href="#">Create Invoice</a></li>
                            </ul>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('admin/payments*') ? 'active' : '' }}" href="#collapsePayments" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/payments*') ? 'true' : 'false' }}">
                            <i class="bi bi-credit-card"></i>
                            <span>Payments</span>
                            <i class="bi bi-chevron-right menu-arrow"></i>
                        </a>
                        <div class="collapse {{ request()->is('admin/payments*') ? 'show' : '' }}" id="collapsePayments">
                            <ul class="sub-menu nav flex-column">
                                <li class="nav-item"><a class="nav-link" href="#">All Payments</a></li>
                                <li class="nav-item"><a class="nav-link" href="#">Record Payment</a></li>
                            </ul>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('admin/expenses*') ? 'active' : '' }}" href="#collapseExpenses" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/expenses*') ? 'true' : 'false' }}">
                            <i class="bi bi-cash-stack"></i>
                            <span>Expenses</span>
                            <i class="bi bi-chevron-right menu-arrow"></i>
                        </a>
                        <div class="collapse {{ request()->is('admin/expenses*') ? 'show' : '' }}" id="collapseExpenses">
                            <ul class="sub-menu nav flex-column">
                                <li class="nav-item"><a class="nav-link" href="#">All Expenses</a></li>
                                <li class="nav-item"><a class="nav-link" href="#">Add Expense</a></li>
                            </ul>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('admin/income*') ? 'active' : '' }}" href="#collapseIncome" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/income*') ? 'true' : 'false' }}">
                            <i class="bi bi-graph-up-arrow"></i>
                            <span>Income</span>
                            <i class="bi bi-chevron-right menu-arrow"></i>
                        </a>
                        <div class="collapse {{ request()->is('admin/income*') ? 'show' : '' }}" id="collapseIncome">
                            <ul class="sub-menu nav flex-column">
                                <li class="nav-item"><a class="nav-link" href="#">Income Overview</a></li>
                                <li class="nav-item"><a class="nav-link" href="#">Record Income</a></li>
                            </ul>
                        </div>
                    </li>
                </ul>

                <div class="sidebar-section-title">Reports</div>

                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('admin/reports/shipment*') ? 'active' : '' }}" href="#collapseShipmentReports" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/reports/shipment*') ? 'true' : 'false' }}">
                            <i class="bi bi-file-text"></i>
                            <span>Shipment Reports</span>
                            <i class="bi bi-chevron-right menu-arrow"></i>
                        </a>
                        <div class="collapse {{ request()->is('admin/reports/shipment*') ? 'show' : '' }}" id="collapseShipmentReports">
                            <ul class="sub-menu nav flex-column">
                                <li class="nav-item"><a class="nav-link" href="#">Daily Reports</a></li>
                                <li class="nav-item"><a class="nav-link" href="#">Monthly Reports</a></li>
                            </ul>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('admin/reports/financial*') ? 'active' : '' }}" href="#collapseFinancialReports" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/reports/financial*') ? 'true' : 'false' }}">
                            <i class="bi bi-file-earmark-bar-graph"></i>
                            <span>Financial Reports</span>
                            <i class="bi bi-chevron-right menu-arrow"></i>
                        </a>
                        <div class="collapse {{ request()->is('admin/reports/financial*') ? 'show' : '' }}" id="collapseFinancialReports">
                            <ul class="sub-menu nav flex-column">
                                <li class="nav-item"><a class="nav-link" href="#">Profit & Loss</a></li>
                                <li class="nav-item"><a class="nav-link" href="#">Revenue Analysis</a></li>
                            </ul>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('admin/reports/inventory*') ? 'active' : '' }}" href="#collapseInventoryReports" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/reports/inventory*') ? 'true' : 'false' }}">
                            <i class="bi bi-file-earmark-spreadsheet"></i>
                            <span>Inventory Reports</span>
                            <i class="bi bi-chevron-right menu-arrow"></i>
                        </a>
                        <div class="collapse {{ request()->is('admin/reports/inventory*') ? 'show' : '' }}" id="collapseInventoryReports">
                            <ul class="sub-menu nav flex-column">
                                <li class="nav-item"><a class="nav-link" href="#">Stock Summary</a></li>
                                <li class="nav-item"><a class="nav-link" href="#">Movement History</a></li>
                            </ul>
                        </div>
                    </li>
                </ul>

                <div class="sidebar-section-title">System</div>

                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('admin/users*') ? 'active' : '' }}" href="#collapseUsers" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/users*') ? 'true' : 'false' }}">
                            <i class="bi bi-people-fill"></i>
                            <span>Users</span>
                            <i class="bi bi-chevron-right menu-arrow"></i>
                        </a>
                        <div class="collapse {{ request()->is('admin/users*') ? 'show' : '' }}" id="collapseUsers">
                            <ul class="sub-menu nav flex-column">
                                <li class="nav-item"><a class="nav-link" href="#">All Users</a></li>
                                <li class="nav-item"><a class="nav-link" href="#">Add User</a></li>
                            </ul>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('admin/roles*') ? 'active' : '' }}" href="#collapseRoles" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/roles*') ? 'true' : 'false' }}">
                            <i class="bi bi-shield-lock"></i>
                            <span>Roles & Permissions</span>
                            <i class="bi bi-chevron-right menu-arrow"></i>
                        </a>
                        <div class="collapse {{ request()->is('admin/roles*') ? 'show' : '' }}" id="collapseRoles">
                            <ul class="sub-menu nav flex-column">
                                <li class="nav-item"><a class="nav-link" href="#">Roles</a></li>
                                <li class="nav-item"><a class="nav-link" href="#">Permissions</a></li>
                            </ul>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('admin/activity-log*') ? 'active' : '' }}" href="#collapseActivityLog" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/activity-log*') ? 'true' : 'false' }}">
                            <i class="bi bi-clock-history"></i>
                            <span>Activity Log</span>
                            <i class="bi bi-chevron-right menu-arrow"></i>
                        </a>
                        <div class="collapse {{ request()->is('admin/activity-log*') ? 'show' : '' }}" id="collapseActivityLog">
                            <ul class="sub-menu nav flex-column">
                                <li class="nav-item"><a class="nav-link" href="#">Log History</a></li>
                            </ul>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('admin/settings*') ? 'active' : '' }}" href="#collapseSettings" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->is('admin/settings*') ? 'true' : 'false' }}">
                            <i class="bi bi-gear"></i>
                            <span>Settings</span>
                            <i class="bi bi-chevron-right menu-arrow"></i>
                        </a>
                        <div class="collapse {{ request()->is('admin/settings*') ? 'show' : '' }}" id="collapseSettings">
                            <ul class="sub-menu nav flex-column">
                                <li class="nav-item"><a class="nav-link" href="#">General</a></li>
                                <li class="nav-item"><a class="nav-link" href="#">Company</a></li>
                                <li class="nav-item"><a class="nav-link" href="#">Email</a></li>
                            </ul>
                        </div>
                    </li>
                </ul>
            </div>
        </aside>

        {{-- Main Content --}}
        <div class="main-content" id="mainContent">
            {{-- Top Navbar --}}
            <nav class="topbar">
                <div class="topbar-left">
                    <button class="btn-toggle-sidebar" id="sidebar-toggle">
                        <i class="bi bi-list"></i>
                    </button>
                    <div>
                        <h5 class="page-title mb-0">@yield('title', 'Dashboard')</h5>
                        @hasSection('subtitle')
                            <p class="page-subtitle mb-0">@yield('subtitle')</p>
                        @endif
                    </div>
                </div>

                <div class="topbar-right">
                    {{-- Theme Toggle --}}
                    <button class="btn btn-link btn-toggle-sidebar" id="theme-toggle" title="Toggle Theme">
                        <i class="bi bi-moon"></i>
                    </button>

                    {{-- Notifications --}}
                    <div class="dropdown">
                        <button class="btn btn-link btn-toggle-sidebar position-relative" data-bs-toggle="dropdown">
                            <i class="bi bi-bell"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
                                3
                            </span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end notification-dropdown p-0">
                            <div class="p-3 border-bottom">
                                <h6 class="mb-0 fw-bold">Notifications</h6>
                            </div>
                            <div class="list-group list-group-flush">
                                <a href="#" class="list-group-item list-group-item-action py-3">
                                    <div class="d-flex align-items-start">
                                        <div class="rounded-circle bg-primary bg-opacity-10 p-2 me-3">
                                            <i class="bi bi-box-seam text-primary"></i>
                                        </div>
                                        <div>
                                            <p class="mb-1 fw-medium">New shipment created</p>
                                            <small class="text-muted">Shipment #SH-2024-001 has been created</small>
                                            <br><small class="text-muted">2 minutes ago</small>
                                        </div>
                                    </div>
                                </a>
                                <a href="#" class="list-group-item list-group-item-action py-3">
                                    <div class="d-flex align-items-start">
                                        <div class="rounded-circle bg-success bg-opacity-10 p-2 me-3">
                                            <i class="bi bi-check-circle text-success"></i>
                                        </div>
                                        <div>
                                            <p class="mb-1 fw-medium">Delivery completed</p>
                                            <small class="text-muted">Shipment #SH-2024-002 delivered</small>
                                            <br><small class="text-muted">15 minutes ago</small>
                                        </div>
                                    </div>
                                </a>
                                <a href="#" class="list-group-item list-group-item-action py-3">
                                    <div class="d-flex align-items-start">
                                        <div class="rounded-circle bg-warning bg-opacity-10 p-2 me-3">
                                            <i class="bi bi-exclamation-triangle text-warning"></i>
                                        </div>
                                        <div>
                                            <p class="mb-1 fw-medium">Payment overdue</p>
                                            <small class="text-muted">Invoice #INV-2024-003 is overdue</small>
                                            <br><small class="text-muted">1 hour ago</small>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="p-2 border-top text-center">
                                <a href="#" class="text-primary fw-medium" style="font-size: 0.85rem;">View all notifications</a>
                            </div>
                        </div>
                    </div>

                    {{-- User Dropdown --}}
                    <div class="dropdown user-dropdown">
                        <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                    <i class="bi bi-person-fill text-primary"></i>
                                </div>
                                <div class="ms-2 d-none d-md-block">
                                    <span class="fw-medium" style="font-size: 0.875rem;">{{ Auth::user()->name ?? 'Admin User' }}</span>
                                    <br><small class="text-muted" style="font-size: 0.75rem;">{{ Auth::user()->email ?? 'admin@combodia.com' }}</small>
                                </div>
                                <i class="bi bi-chevron-down ms-1" style="font-size: 0.7rem;"></i>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i>Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            {{-- Page Content --}}
            <div class="page-content fade-in">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @yield('content')
            </div>

            {{-- Footer --}}
            <footer class="py-3 px-4 text-center" style="color: #6c757d; font-size: 0.813rem; border-top: 1px solid rgba(0,0,0,0.05);">
                <div class="d-flex justify-content-between align-items-center">
                    <span>&copy; {{ date('Y') }} Combodia Logistics. All rights reserved.</span>
                    <span>Version 1.0.0</span>
                </div>
            </footer>
        </div>
    </div>

    @stack('scripts')
</body>
</html>

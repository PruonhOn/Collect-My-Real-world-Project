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
        <button class="btn btn-link btn-toggle-sidebar" id="theme-toggle" title="Toggle Theme">
            <i class="bi bi-moon"></i>
        </button>

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

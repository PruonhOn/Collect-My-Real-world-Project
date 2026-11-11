<x-admin-layout>
    @section('title', 'Admin Dashboard - ' . config('app.name'))

    @section('breadcrumbs')
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
        <li class="breadcrumb-item active" aria-current="page">Admin Dashboard</li>
    @endsection

    @section('page-title', 'Admin Dashboard')
    @section('page-subtitle', 'System-wide analytics and monitoring')

    <div class="card mb-4">
        <div class="card-body py-3">
            <div class="row align-items-center g-2">
                <div class="col-auto">
                    <span class="fw-semibold text-muted small text-uppercase"><i class="bi bi-funnel me-1"></i>Filters</span>
                </div>
                <div class="col-auto">
                    <select class="form-select form-select-sm" id="periodFilter" style="width: auto;">
                        <option value="7">Last 7 Days</option>
                        <option value="30" selected>Last 30 Days</option>
                        <option value="90">Last 90 Days</option>
                        <option value="365">Last Year</option>
                    </select>
                </div>
                <div class="col-auto">
                    <select class="form-select form-select-sm" id="branchFilter" style="width: auto;">
                        <option value="">All Branches</option>
                    </select>
                </div>
                <div class="col-auto">
                    <input type="date" class="form-control form-control-sm" id="dateFrom" style="width: auto;">
                </div>
                <div class="col-auto">
                    <span class="text-muted">to</span>
                </div>
                <div class="col-auto">
                    <input type="date" class="form-control form-control-sm" id="dateTo" style="width: auto;">
                </div>
                <div class="col-auto">
                    <button class="btn btn-sm btn-outline-primary" id="applyFiltersBtn">
                        <i class="bi bi-search me-1"></i>Apply
                    </button>
                </div>
                <div class="col-auto">
                    <button class="btn btn-sm btn-outline-secondary" id="refreshDashboardBtn">
                        <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                    </button>
                </div>
                <div class="col-auto ms-auto">
                    <span class="text-muted small" id="lastUpdated">
                        <i class="bi bi-clock me-1"></i>Updated: {{ now()->format('H:i:s') }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card stat-card border-start border-primary border-3" title="Total shipments in system">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small text-uppercase fw-semibold">Total Shipments</p>
                            <h3 class="mb-0 fw-bold">{{ $totalShipments ?? 0 }}</h3>
                            <small class="text-{{ ($shipmentsGrowth ?? 0) >= 0 ? 'success' : 'danger' }}">
                                <i class="bi bi-arrow-{{ ($shipmentsGrowth ?? 0) >= 0 ? 'up' : 'down' }}"></i> {{ abs($shipmentsGrowth ?? 0) }}%
                            </small>
                        </div>
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                            <i class="bi bi-box-seam text-primary fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card stat-card border-start border-warning border-3" title="Deliveries currently in progress">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small text-uppercase fw-semibold">Active Deliveries</p>
                            <h3 class="mb-0 fw-bold">{{ $activeDeliveries ?? 0 }}</h3>
                            <small class="text-warning"><i class="bi bi-truck"></i> In Progress</small>
                        </div>
                        <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                            <i class="bi bi-truck text-warning fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card stat-card border-start border-info border-3" title="Total inventory across all warehouses">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small text-uppercase fw-semibold">Warehouse Stock</p>
                            <h3 class="mb-0 fw-bold">{{ number_format($warehouseStock ?? 0) }}</h3>
                            <small class="text-info"><i class="bi bi-building"></i> {{ $warehouseCount ?? 0 }} Warehouses</small>
                        </div>
                        <div class="rounded-circle bg-info bg-opacity-10 p-3">
                            <i class="bi bi-building text-info fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card stat-card border-start border-success border-3" title="Revenue for current month">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small text-uppercase fw-semibold">Revenue (Month)</p>
                            <h3 class="mb-0 fw-bold">${{ number_format($monthlyRevenue ?? 0, 2) }}</h3>
                            <small class="text-{{ ($revenueGrowth ?? 0) >= 0 ? 'success' : 'danger' }}">
                                <i class="bi bi-arrow-{{ ($revenueGrowth ?? 0) >= 0 ? 'up' : 'down' }}"></i> {{ abs($revenueGrowth ?? 0) }}%
                            </small>
                        </div>
                        <div class="rounded-circle bg-success bg-opacity-10 p-3">
                            <i class="bi bi-cash-stack text-success fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card stat-card border-start border-danger border-3" title="Expenses for current month">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small text-uppercase fw-semibold">Expenses (Month)</p>
                            <h3 class="mb-0 fw-bold">${{ number_format($monthlyExpenses ?? 0, 2) }}</h3>
                            <small class="text-{{ ($expenseChange ?? 0) >= 0 ? 'danger' : 'success' }}">
                                <i class="bi bi-arrow-{{ ($expenseChange ?? 0) >= 0 ? 'up' : 'down' }}"></i> {{ abs($expenseChange ?? 0) }}%
                            </small>
                        </div>
                        <div class="rounded-circle bg-danger bg-opacity-10 p-3">
                            <i class="bi bi-currency-dollar text-danger fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card stat-card border-start border-secondary border-3" title="Active fleet vehicles">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small text-uppercase fw-semibold">Active Vehicles</p>
                            <h3 class="mb-0 fw-bold">{{ $activeVehicles ?? 0 }}</h3>
                            <small class="text-secondary"><i class="bi bi-truck"></i> / {{ $totalVehicles ?? 0 }} Total</small>
                        </div>
                        <div class="rounded-circle bg-secondary bg-opacity-10 p-3">
                            <i class="bi bi-truck-front text-secondary fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-2">
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card stat-card border-start border-success border-3" title="Drivers available for assignment">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small text-uppercase fw-semibold">Drivers</p>
                            <h3 class="mb-0 fw-bold">{{ $driverStats['available'] ?? 0 }}</h3>
                            <small class="text-success"><i class="bi bi-person-check"></i> Available / {{ $driverStats['total'] ?? 0 }} Total</small>
                        </div>
                        <div class="rounded-circle bg-success bg-opacity-10 p-3">
                            <i class="bi bi-people text-success fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card stat-card border-start border-warning border-3" title="Drivers currently on delivery">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small text-uppercase fw-semibold">On Delivery</p>
                            <h3 class="mb-0 fw-bold">{{ $driverStats['on_delivery'] ?? 0 }}</h3>
                            <small class="text-warning"><i class="bi bi-truck"></i> In Transit</small>
                        </div>
                        <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                            <i class="bi bi-person-wheelchair text-warning fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card stat-card border-start border-danger border-3" title="Vehicles currently in maintenance">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small text-uppercase fw-semibold">Maintenance</p>
                            <h3 class="mb-0 fw-bold">{{ $vehicleStatus['maintenance'] ?? 0 }}</h3>
                            <small class="text-danger"><i class="bi bi-tools"></i> Vehicles</small>
                        </div>
                        <div class="rounded-circle bg-danger bg-opacity-10 p-3">
                            <i class="bi bi-tools text-danger fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card stat-card border-start border-info border-3" title="Overdue invoices">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small text-uppercase fw-semibold">Overdue</p>
                            <h3 class="mb-0 fw-bold">${{ number_format($revenueStats['overdue'] ?? 0, 0) }}</h3>
                            <small class="text-info"><i class="bi bi-exclamation-triangle"></i> Outstanding</small>
                        </div>
                        <div class="rounded-circle bg-info bg-opacity-10 p-3">
                            <i class="bi bi-exclamation-triangle text-info fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card stat-card border-start border-primary border-3" title="Top customer by revenue">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small text-uppercase fw-semibold">Top Customer</p>
                            <h3 class="mb-0 fw-bold text-truncate" style="max-width: 120px;">{{ $topCustomersLabels[0] ?? 'N/A' }}</h3>
                            <small class="text-primary"><i class="bi bi-star"></i> Highest Revenue</small>
                        </div>
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                            <i class="bi bi-star text-primary fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card stat-card border-start border-dark border-3" title="Shipment delivery success rate">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small text-uppercase fw-semibold">Success Rate</p>
                            <h3 class="mb-0 fw-bold">{{ $successRate ?? 0 }}%</h3>
                            <small class="text-dark"><i class="bi bi-check-circle"></i> Delivery Rate</small>
                        </div>
                        <div class="rounded-circle bg-dark bg-opacity-10 p-3">
                            <i class="bi bi-check-circle text-dark fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-2">
        <div class="col-lg-6">
            <div class="table-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-bar-chart-line me-2"></i>Daily Shipments (Last 30 Days)</h5>
                </div>
                <canvas id="dailyShipmentsChart" height="200"></canvas>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="table-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-cash-coin me-2"></i>Monthly Revenue</h5>
                </div>
                <canvas id="monthlyRevenueChart" height="200"></canvas>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-2">
        <div class="col-lg-4">
            <div class="table-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-pie-chart me-2"></i>Delivery Success Rate</h5>
                </div>
                <div class="text-center py-3">
                    <canvas id="successRateChart" height="200"></canvas>
                </div>
                <div class="row text-center mt-3 g-2">
                    <div class="col-4">
                        <span class="badge bg-success d-block py-2">
                            <i class="bi bi-check-circle"></i> {{ $successRate ?? 0 }}%
                        </span>
                        <small class="text-muted text-uppercase fw-semibold small">Delivered</small>
                    </div>
                    <div class="col-4">
                        <span class="badge bg-warning d-block py-2">
                            <i class="bi bi-clock"></i> {{ $pendingRate ?? 0 }}%
                        </span>
                        <small class="text-muted text-uppercase fw-semibold small">Pending</small>
                    </div>
                    <div class="col-4">
                        <span class="badge bg-danger d-block py-2">
                            <i class="bi bi-x-circle"></i> {{ $failedRate ?? 0 }}%
                        </span>
                        <small class="text-muted text-uppercase fw-semibold small">Failed</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="table-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2"></i>Recent Shipments</h5>
                    <a href="{{ route('shipments.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tracking #</th>
                                <th>Customer</th>
                                <th>Origin</th>
                                <th>Destination</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentShipments ?? [] as $shipment)
                                <tr>
                                    <td>
                                        <a href="{{ route('shipments.show', $shipment) }}" class="text-decoration-none fw-medium">
                                            {{ $shipment->tracking_number }}
                                        </a>
                                    </td>
                                    <td>{{ $shipment->customer->name ?? 'N/A' }}</td>
                                    <td>{{ $shipment->origin_address ?? 'N/A' }}</td>
                                    <td>{{ $shipment->destination_address ?? 'N/A' }}</td>
                                    <td>
                                        @php
                                            $statusColors = ['pending' => 'warning', 'picked_up' => 'info', 'in_transit' => 'primary', 'out_for_delivery' => 'info', 'delivered' => 'success', 'cancelled' => 'danger'];
                                            $color = $statusColors[$shipment->status] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $color }}">
                                            {{ str_replace('_', ' ', ucfirst($shipment->status)) }}
                                        </span>
                                    </td>
                                    <td><small class="text-muted">{{ $shipment->created_at->format('M d, Y') }}</small></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                        No recent shipments found
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-2">
        <div class="col-lg-4">
            <div class="table-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-pie-chart me-2"></i>Shipments by Status</h5>
                </div>
                <canvas id="shipmentsByStatusChart" height="200"></canvas>
                <div class="row text-center mt-3 g-2" id="shipmentStatusLegend"></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="table-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-truck-front me-2"></i>Vehicle Fleet Status</h5>
                </div>
                <canvas id="vehicleStatusChart" height="200"></canvas>
                <div class="row text-center mt-3 g-2" id="vehicleStatusLegend"></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="table-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-boxes me-2"></i>Shipment Types</h5>
                </div>
                <canvas id="shipmentTypeChart" height="200"></canvas>
                <div class="row text-center mt-3 g-2" id="shipmentTypeLegend"></div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-2">
        <div class="col-lg-6">
            <div class="table-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-currency-exchange me-2"></i>Revenue vs Expenses</h5>
                </div>
                <canvas id="revenueExpenseChart" height="200"></canvas>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="table-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-graph-up-arrow me-2"></i>Weekly Shipments & Deliveries</h5>
                </div>
                <canvas id="weeklyTrendChart" height="200"></canvas>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-2">
        <div class="col-lg-6">
            <div class="table-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-person me-2"></i>Top Customers</h5>
                </div>
                <canvas id="topCustomersChart" height="250"></canvas>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="table-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-exclamation-triangle me-2"></i>Low Stock Alerts</h5>
                    <span class="badge bg-danger rounded-pill">{{ count($lowStockItems ?? []) }}</span>
                </div>
                <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th>SKU</th>
                                <th>Qty</th>
                                <th>Min</th>
                                <th>Warehouse</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lowStockItems ?? [] as $item)
                                <tr>
                                    <td class="fw-medium">{{ $item->name }}</td>
                                    <td><code>{{ $item->sku }}</code></td>
                                    <td><span class="badge bg-danger">{{ number_format($item->quantity) }}</span></td>
                                    <td>{{ number_format($item->min_quantity) }}</td>
                                    <td><small>{{ $item->warehouse->name ?? 'N/A' }}</small></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">
                                        <i class="bi bi-check-circle text-success me-1"></i> All items adequately stocked
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-2">
        <div class="col-12">
            <div class="table-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-activity me-2"></i>Recent Activities</h5>
                </div>
                <div class="timeline">
                    @forelse($recentActivities ?? [] as $activity)
                        <div class="d-flex mb-3 pb-3 border-bottom">
                            <div class="me-3">
                                <div class="rounded-circle bg-{{ $activity->color ?? 'primary' }} bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="bi bi-{{ $activity->icon ?? 'circle' }} text-{{ $activity->color ?? 'primary' }}"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <p class="mb-0">{{ $activity->description }}</p>
                                <small class="text-muted">
                                    <i class="bi bi-clock me-1"></i>{{ $activity->created_at->diffForHumans() }}
                                    @if($activity->user)
                                        &middot; {{ $activity->user->name }}
                                    @endif
                                </small>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-activity fs-1 d-block mb-2"></i>
                            <p class="mb-0">No recent activities</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @push('styles')
    <style>
        .table-container { transition: box-shadow 0.2s; }
        .table-container:hover { box-shadow: 0 0.125rem 0.5rem rgba(0,0,0,0.08); }
        .stat-card { transition: transform 0.15s, box-shadow 0.15s; cursor: default; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 0.25rem 0.75rem rgba(0,0,0,0.1); }
    </style>
    @endpush

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
            const textColor = isDark ? '#e4e6eb' : '#6c757d';
            const gridColor = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';
            const font = { family: "'Figtree', sans-serif" };

            new Chart(document.getElementById('dailyShipmentsChart'), {
                type: 'bar',
                data: {
                    labels: {!! json_encode($dailyShipmentsLabels ?? []) !!},
                    datasets: [{
                        label: 'Shipments',
                        data: {!! json_encode($dailyShipmentsData ?? []) !!},
                        backgroundColor: 'rgba(13, 110, 253, 0.5)',
                        borderColor: 'rgba(13, 110, 253, 1)',
                        borderWidth: 1,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false }, tooltip: { callbacks: { label: function(ctx) { return ctx.parsed.y + ' shipments'; } } } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textColor, stepSize: 1 } },
                        x: { grid: { display: false }, ticks: { color: textColor, maxTicksLimit: 15 } }
                    }
                }
            });

            new Chart(document.getElementById('monthlyRevenueChart'), {
                type: 'line',
                data: {
                    labels: {!! json_encode($monthlyRevenueLabels ?? []) !!},
                    datasets: [{
                        label: 'Revenue',
                        data: {!! json_encode($monthlyRevenueData ?? []) !!},
                        borderColor: 'rgba(25, 135, 84, 1)',
                        backgroundColor: 'rgba(25, 135, 84, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false }, tooltip: { callbacks: { label: function(ctx) { return '$' + Number(ctx.parsed.y).toLocaleString(); } } } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textColor, callback: function(v) { return '$' + (v >= 1000 ? (v/1000).toFixed(0) + 'k' : v); } } },
                        x: { grid: { display: false }, ticks: { color: textColor } }
                    }
                }
            });

            new Chart(document.getElementById('successRateChart'), {
                type: 'doughnut',
                data: {
                    labels: ['Delivered', 'Pending', 'Failed'],
                    datasets: [{
                        data: [{{ $successRate ?? 70 }}, {{ $pendingRate ?? 20 }}, {{ $failedRate ?? 10 }}],
                        backgroundColor: ['rgba(25, 135, 84, 0.8)', 'rgba(255, 193, 7, 0.8)', 'rgba(220, 53, 69, 0.8)'],
                        borderWidth: 0,
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false }, tooltip: { callbacks: { label: function(ctx) { return ctx.label + ': ' + ctx.parsed + '%'; } } } },
                    cutout: '70%'
                }
            });

            const statusLabels = {!! json_encode($shipmentsByStatus->keys() ?? []) !!};
            const statusData = {!! json_encode($shipmentsByStatus->values() ?? []) !!};
            const statusColors = {
                pending: 'rgba(255, 193, 7, 0.8)', confirmed: 'rgba(13, 202, 240, 0.8)',
                picked_up: 'rgba(13, 110, 253, 0.8)', in_transit: 'rgba(111, 66, 193, 0.8)',
                arrived_at_branch: 'rgba(108, 117, 125, 0.8)', out_for_delivery: 'rgba(13, 202, 240, 0.8)',
                delivered: 'rgba(25, 135, 84, 0.8)', failed_attempt: 'rgba(220, 53, 69, 0.6)',
                delayed: 'rgba(253, 126, 20, 0.8)', cancelled: 'rgba(220, 53, 69, 0.8)'
            };
            if (statusLabels.length) {
                new Chart(document.getElementById('shipmentsByStatusChart'), {
                    type: 'doughnut',
                    data: {
                        labels: statusLabels.map(function(l) { return l.replace(/_/g, ' ').replace(/\b\w/g, function(c) { return c.toUpperCase(); }); }),
                        datasets: [{
                            data: statusData,
                            backgroundColor: statusLabels.map(function(l) { return statusColors[l] || 'rgba(108, 117, 125, 0.8)'; }),
                            borderWidth: 1,
                            borderColor: isDark ? '#1e1e2d' : '#fff',
                            hoverOffset: 8
                        }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom', labels: { color: textColor, font: font, boxWidth: 12, padding: 12 } } },
                        cutout: '55%'
                    }
                });
            } else {
                document.getElementById('shipmentsByStatusChart').remove();
                document.getElementById('shipmentStatusLegend').innerHTML = '<p class="text-muted text-center py-3">No shipment data</p>';
            }

            const vehicleData = {!! json_encode($vehicleStatus ?? []) !!};
            if (vehicleData && vehicleData.total > 0) {
                new Chart(document.getElementById('vehicleStatusChart'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Active', 'Maintenance', 'Available', 'Inactive'],
                        datasets: [{
                            data: [vehicleData.active, vehicleData.maintenance, vehicleData.available, vehicleData.inactive],
                            backgroundColor: ['rgba(25, 135, 84, 0.8)', 'rgba(220, 53, 69, 0.8)', 'rgba(13, 110, 253, 0.8)', 'rgba(108, 117, 125, 0.5)'],
                            borderWidth: 1,
                            borderColor: isDark ? '#1e1e2d' : '#fff',
                            hoverOffset: 8
                        }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom', labels: { color: textColor, font: font, boxWidth: 12, padding: 12 } } },
                        cutout: '55%'
                    }
                });
            } else {
                document.getElementById('vehicleStatusChart').remove();
                document.getElementById('vehicleStatusLegend').innerHTML = '<p class="text-muted text-center py-3">No vehicle data</p>';
            }

            const shipmentTypes = {!! json_encode($shipmentTypeBreakdown ?? []) !!};
            const typeKeys = Object.keys(shipmentTypes);
            if (typeKeys.length) {
                const typeColors = ['rgba(13, 110, 253, 0.8)', 'rgba(25, 135, 84, 0.8)', 'rgba(255, 193, 7, 0.8)', 'rgba(220, 53, 69, 0.8)', 'rgba(111, 66, 193, 0.8)'];
                new Chart(document.getElementById('shipmentTypeChart'), {
                    type: 'doughnut',
                    data: {
                        labels: typeKeys.map(function(l) { return l.replace(/_/g, ' ').replace(/\b\w/g, function(c) { return c.toUpperCase(); }); }),
                        datasets: [{
                            data: Object.values(shipmentTypes),
                            backgroundColor: typeColors.slice(0, typeKeys.length),
                            borderWidth: 1,
                            borderColor: isDark ? '#1e1e2d' : '#fff',
                            hoverOffset: 8
                        }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom', labels: { color: textColor, font: font, boxWidth: 12, padding: 12 } } },
                        cutout: '55%'
                    }
                });
            } else {
                document.getElementById('shipmentTypeChart').remove();
                document.getElementById('shipmentTypeLegend').innerHTML = '<p class="text-muted text-center py-3">No shipment type data</p>';
            }

            const comparisonLabels = {!! json_encode($monthlyComparison['labels'] ?? []) !!};
            const revenueData = {!! json_encode($monthlyComparison['revenue'] ?? []) !!};
            const expenseData = {!! json_encode($monthlyComparison['expenses'] ?? []) !!};
            if (comparisonLabels.length) {
                new Chart(document.getElementById('revenueExpenseChart'), {
                    type: 'bar',
                    data: {
                        labels: comparisonLabels,
                        datasets: [
                            { label: 'Revenue', data: revenueData, backgroundColor: 'rgba(25, 135, 84, 0.7)', borderColor: 'rgba(25, 135, 84, 1)', borderWidth: 1, borderRadius: 4 },
                            { label: 'Expenses', data: expenseData, backgroundColor: 'rgba(220, 53, 69, 0.7)', borderColor: 'rgba(220, 53, 69, 1)', borderWidth: 1, borderRadius: 4 }
                        ]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { position: 'top', labels: { color: textColor, font: font, boxWidth: 12, padding: 12 } },
                            tooltip: { callbacks: { label: function(ctx) { return ctx.dataset.label + ': $' + Number(ctx.parsed.y).toLocaleString(); } } } },
                        scales: {
                            y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textColor, callback: function(v) { return '$' + (v >= 1000 ? (v/1000).toFixed(0) + 'k' : v); } } },
                            x: { grid: { display: false }, ticks: { color: textColor } }
                        }
                    }
                });
            }

            const weekLabels = {!! json_encode($weeklyTrend['labels'] ?? []) !!};
            const weekShipments = {!! json_encode($weeklyTrend['shipments'] ?? []) !!};
            const weekDeliveries = {!! json_encode($weeklyTrend['deliveries'] ?? []) !!};
            if (weekLabels.length) {
                new Chart(document.getElementById('weeklyTrendChart'), {
                    type: 'line',
                    data: {
                        labels: weekLabels,
                        datasets: [
                            { label: 'Shipments', data: weekShipments, borderColor: 'rgba(13, 110, 253, 1)', backgroundColor: 'rgba(13, 110, 253, 0.1)', fill: true, tension: 0.4, pointRadius: 4, pointHoverRadius: 6 },
                            { label: 'Deliveries', data: weekDeliveries, borderColor: 'rgba(255, 193, 7, 1)', backgroundColor: 'rgba(255, 193, 7, 0.1)', fill: true, tension: 0.4, pointRadius: 4, pointHoverRadius: 6 }
                        ]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { position: 'top', labels: { color: textColor, font: font, boxWidth: 12, padding: 12 } } },
                        scales: {
                            y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textColor, stepSize: 1 } },
                            x: { grid: { display: false }, ticks: { color: textColor } }
                        }
                    }
                });
            }

            const custLabels = {!! json_encode($topCustomersLabels ?? []) !!};
            const custData = {!! json_encode($topCustomersData ?? []) !!};
            if (custLabels.length) {
                new Chart(document.getElementById('topCustomersChart'), {
                    type: 'bar',
                    data: {
                        labels: custLabels,
                        datasets: [{
                            label: 'Revenue',
                            data: custData,
                            backgroundColor: custData.map(function(v, i) {
                                const colors = ['rgba(13, 110, 253, 0.7)', 'rgba(25, 135, 84, 0.7)', 'rgba(255, 193, 7, 0.7)', 'rgba(111, 66, 193, 0.7)', 'rgba(220, 53, 69, 0.7)'];
                                return colors[i % colors.length];
                            }),
                            borderWidth: 0,
                            borderRadius: 4
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { display: false },
                            tooltip: { callbacks: { label: function(ctx) { return '$' + Number(ctx.parsed.x).toLocaleString(); } } } },
                        scales: {
                            x: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textColor, callback: function(v) { return '$' + (v >= 1000 ? (v/1000).toFixed(0) + 'k' : v); } } },
                            y: { grid: { display: false }, ticks: { color: textColor, font: { size: 12 } } }
                        }
                    }
                });
            }

            document.getElementById('refreshDashboardBtn').addEventListener('click', function() {
                this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Loading...';
                this.disabled = true;
                location.reload();
            });

            document.getElementById('periodFilter').addEventListener('change', function() {
                const val = this.value;
                if (val === '7') {
                    document.getElementById('dateFrom').value = new Date(Date.now() - 7*86400000).toISOString().split('T')[0];
                    document.getElementById('dateTo').value = new Date().toISOString().split('T')[0];
                } else if (val === '30') {
                    document.getElementById('dateFrom').value = new Date(Date.now() - 30*86400000).toISOString().split('T')[0];
                    document.getElementById('dateTo').value = new Date().toISOString().split('T')[0];
                } else if (val === '90') {
                    document.getElementById('dateFrom').value = new Date(Date.now() - 90*86400000).toISOString().split('T')[0];
                    document.getElementById('dateTo').value = new Date().toISOString().split('T')[0];
                } else if (val === '365') {
                    document.getElementById('dateFrom').value = new Date(Date.now() - 365*86400000).toISOString().split('T')[0];
                    document.getElementById('dateTo').value = new Date().toISOString().split('T')[0];
                }
            });

            document.getElementById('applyFiltersBtn').addEventListener('click', function() {
                const from = document.getElementById('dateFrom').value;
                const to = document.getElementById('dateTo').value;
                const branch = document.getElementById('branchFilter').value;
                const params = new URLSearchParams(window.location.search);
                if (from) params.set('from', from);
                if (to) params.set('to', to);
                if (branch) params.set('branch', branch);
                window.location.search = params.toString();
            });

            setInterval(function() {
                document.getElementById('lastUpdated').innerHTML = '<i class="bi bi-clock me-1"></i>Updated: ' + new Date().toLocaleTimeString();
            }, 30000);
        });
    </script>
    @endpush
</x-admin-layout>
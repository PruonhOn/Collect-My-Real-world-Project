@extends('layouts.app')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')
@section('subheading', 'Welcome back, '.auth()->user()->name.'!')

@section('content')
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="card stat-card">
                <div class="stat-icon stat-primary"><i class="bi bi-cash-stack"></i></div>
                <div>
                    <div class="stat-value">{{ money($stats['todayRevenue']) }}</div>
                    <div class="stat-label">Today's Sales</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card">
                <div class="stat-icon stat-rose"><i class="bi bi-receipt"></i></div>
                <div>
                    <div class="stat-value">{{ $stats['todayOrders'] }}</div>
                    <div class="stat-label">Orders Today</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card">
                <div class="stat-icon stat-green"><i class="bi bi-bag-check"></i></div>
                <div>
                    <div class="stat-value">{{ $stats['todayItems'] }}</div>
                    <div class="stat-label">Items Sold</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card">
                <div class="stat-icon stat-gold"><i class="bi bi-calendar3"></i></div>
                <div>
                    <div class="stat-value">{{ money($stats['monthRevenue']) }}</div>
                    <div class="stat-label">This Month</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-bar-chart-line me-2"></i>Revenue — Last 30 Days</h6>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-secondary chart-range" data-days="7">7D</button>
                        <button class="btn btn-outline-secondary chart-range active" data-days="30">30D</button>
                        <button class="btn btn-outline-secondary chart-range" data-days="90">90D</button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" height="110"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-fire me-2"></i>Top Selling Products</h6>
                </div>
                <div class="card-body p-0">
                    @forelse ($topProducts as $i => $product)
                        <div class="list-item d-flex align-items-center gap-3">
                            <div class="top-rank">{{ $i + 1 }}</div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold text-truncate">{{ $product['name'] }}</div>
                                <small class="text-muted">{{ $product['quantity'] }} sold · {{ money($product['revenue']) }}</small>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <p>No sales yet. Head to the POS to get started!</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-exclamation-triangle me-2 text-warning"></i>Low Stock</h6>
                    @if (auth()->user()->isAdmin() || auth()->user()->isManager())
                        <a href="{{ route('inventory.index') }}" class="small">View inventory</a>
                    @endif
                </div>
                <div class="card-body p-0">
                    @forelse ($lowStock as $variant)
                        <div class="list-item d-flex align-items-center gap-3">
                            <div class="stock-dot {{ $variant->is_out_of_stock ? 'bg-danger' : 'bg-warning' }}"></div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold text-truncate">{{ $variant->product->name }} <small class="text-muted">{{ $variant->label }}</small></div>
                                <small class="text-muted">{{ $variant->sku_variant }}</small>
                            </div>
                            <span class="badge {{ $variant->is_out_of_stock ? 'bg-danger-subtle text-danger' : 'bg-warning-subtle text-warning' }}">
                                {{ $variant->is_out_of_stock ? 'Out of stock' : $variant->stock_quantity.' left' }}
                            </span>
                        </div>
                    @empty
                        <div class="empty-state"><i class="bi bi-check-circle text-success"></i><p>All stock levels are healthy.</p></div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-lightning-charge me-2"></i>Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <a href="{{ route('pos.index') }}" class="btn btn-primary btn-lg w-100 quick-action">
                                <i class="bi bi-shop"></i> New Sale
                            </a>
                        </div>
                        @if (auth()->user()->isAdmin() || auth()->user()->isManager())
                            <div class="col-6">
                                <a href="{{ route('products.create') }}" class="btn btn-outline-primary btn-lg w-100 quick-action">
                                    <i class="bi bi-plus-circle"></i> Add Product
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="{{ route('inventory.index') }}" class="btn btn-outline-success btn-lg w-100 quick-action">
                                    <i class="bi bi-box-arrow-in-down"></i> Receive Stock
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="{{ route('reports.sales') }}" class="btn btn-outline-secondary btn-lg w-100 quick-action">
                                    <i class="bi bi-graph-up"></i> Sales Report
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/dashboard.js')
    <script>
        window.RevenueChartData = @json($chart);
    </script>
@endpush

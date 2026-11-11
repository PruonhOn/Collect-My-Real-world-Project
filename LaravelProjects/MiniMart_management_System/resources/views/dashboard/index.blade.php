@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')
<style>
    .stat-card .icon {
        width: 55px;
        height: 55px;
    }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="fas fa-chart-pie me-2"></i>Dashboard</h4>
    <span class="text-muted">{{ now()->format('l, F d, Y') }}</span>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card bg-primary bg-opacity-10">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted text-uppercase">Total Products</small>
                    <h3 class="fw-bold mb-0">{{ $stats['total_products'] }}</h3>
                </div>
                <div class="icon bg-primary text-white">
                    <i class="fas fa-box"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card bg-success bg-opacity-10">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted text-uppercase">Total Categories</small>
                    <h3 class="fw-bold mb-0">{{ $stats['total_categories'] }}</h3>
                </div>
                <div class="icon bg-success text-white">
                    <i class="fas fa-tags"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card bg-info bg-opacity-10">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted text-uppercase">Total Stock</small>
                    <h3 class="fw-bold mb-0">{{ $stats['total_stock'] }}</h3>
                </div>
                <div class="icon bg-info text-white">
                    <i class="fas fa-cubes"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card bg-warning bg-opacity-10">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted text-uppercase">Low Stock Items</small>
                    <h3 class="fw-bold mb-0 {{ $stats['low_stock_count'] > 0 ? 'text-danger' : '' }}">{{ $stats['low_stock_count'] }}</h3>
                </div>
                <div class="icon bg-warning text-white">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card bg-success bg-opacity-10">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted text-uppercase">Today's Sales</small>
                    <h4 class="fw-bold mb-0 text-success">${{ number_format($stats['today_sales'], 2) }}</h4>
                </div>
                <div class="icon bg-success text-white">
                    <i class="fas fa-dollar-sign"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card bg-primary bg-opacity-10">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted text-uppercase">Weekly Sales</small>
                    <h4 class="fw-bold mb-0">${{ number_format($stats['weekly_sales'], 2) }}</h4>
                </div>
                <div class="icon bg-primary text-white">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card bg-info bg-opacity-10">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted text-uppercase">Monthly Sales</small>
                    <h4 class="fw-bold mb-0">${{ number_format($stats['monthly_sales'], 2) }}</h4>
                </div>
                <div class="icon bg-info text-white">
                    <i class="fas fa-calendar-alt"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card bg-danger bg-opacity-10">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted text-uppercase">Monthly Expenses</small>
                    <h4 class="fw-bold mb-0 text-danger">${{ number_format($stats['monthly_expenses'], 2) }}</h4>
                </div>
                <div class="icon bg-danger text-white">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">Sales & Expenses Overview (Last 7 Days)</h6>
            </div>
            <div class="card-body">
                <canvas id="salesChart" height="300"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">Net Profit</h6>
                <span class="badge bg-success">{{ now()->format('F') }}</span>
            </div>
            <div class="card-body text-center">
                <h3 class="fw-bold {{ $stats['net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                    ${{ number_format($stats['net_profit'], 2) }}
                </h3>
                <p class="text-muted mb-0">Monthly Net Profit</p>
                <hr>
                <div class="row text-start g-2">
                    <div class="col-6">
                        <small class="text-muted">Sales</small>
                        <h6 class="mb-0 text-success">${{ number_format($stats['monthly_sales'], 2) }}</h6>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">Expenses</small>
                        <h6 class="mb-0 text-danger">${{ number_format($stats['monthly_expenses'], 2) }}</h6>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-2">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0 fw-bold"><i class="fas fa-fire me-2"></i>Top Selling Products</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Qty Sold</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topProducts as $item)
                            <tr>
                                <td>{{ $item->product?->name ?? 'N/A' }}</td>
                                <td>{{ $item->total_qty }}</td>
                                <td>${{ number_format($item->total_sales, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">No sales data yet</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0 fw-bold"><i class="fas fa-clock me-2"></i>Recent Transactions</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Amount</th>
                                <th>Cashier</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stats['recent_sales'] as $sale)
                            <tr>
                                <td><a href="{{ route('sales.show', $sale->id) }}">{{ $sale->invoice_number }}</a></td>
                                <td>${{ number_format($sale->total_amount, 2) }}</td>
                                <td>{{ $sale->user?->name ?? 'N/A' }}</td>
                                <td>{{ $sale->created_at->diffForHumans() }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">No recent sales</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const ctx = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode($chartData['days']) !!},
            datasets: [{
                label: 'Sales',
                data: {!! json_encode($chartData['sales']) !!},
                borderColor: '#198754',
                backgroundColor: 'rgba(25, 135, 84, 0.1)',
                fill: true,
                tension: 0.4,
            }, {
                label: 'Expenses',
                data: {!! json_encode($chartData['expenses']) !!},
                borderColor: '#dc3545',
                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                fill: true,
                tension: 0.4,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) { return '$' + value; }
                    }
                }
            }
        }
    });
</script>
@endpush

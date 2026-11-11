@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">Dashboard</h4>
            <p class="text-muted small mb-0">Overview of your mart performance</p>
        </div>
        <div class="d-flex gap-2">
            <select class="form-select form-select-sm" id="dashboardPeriod" style="width:auto;">
                <option value="today">Today</option>
                <option value="week" selected>This Week</option>
                <option value="month">This Month</option>
                <option value="year">This Year</option>
            </select>
            <button class="btn btn-sm btn-outline-primary" onclick="location.reload()">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Total Sales</p>
                            <h5 class="fw-bold mb-0" id="totalSales">$0.00</h5>
                        </div>
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary rounded-3 p-2">
                            <i class="bi bi-cart-check fs-4"></i>
                        </div>
                    </div>
                    <small class="text-success" id="salesChange"><i class="bi bi-arrow-up"></i> 0% vs last period</small>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Revenue</p>
                            <h5 class="fw-bold mb-0" id="totalRevenue">$0.00</h5>
                        </div>
                        <div class="stat-icon bg-success bg-opacity-10 text-success rounded-3 p-2">
                            <i class="bi bi-currency-dollar fs-4"></i>
                        </div>
                    </div>
                    <small class="text-success" id="revenueChange"><i class="bi bi-arrow-up"></i> 0% vs last period</small>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Products</p>
                            <h5 class="fw-bold mb-0" id="totalProducts">0</h5>
                        </div>
                        <div class="stat-icon bg-info bg-opacity-10 text-info rounded-3 p-2">
                            <i class="bi bi-box-seam fs-4"></i>
                        </div>
                    </div>
                    <small class="text-muted" id="productsChange">Total inventory items</small>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Low Stock</p>
                            <h5 class="fw-bold mb-0 text-warning" id="lowStockCount">0</h5>
                        </div>
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning rounded-3 p-2">
                            <i class="bi bi-exclamation-triangle fs-4"></i>
                        </div>
                    </div>
                    <small class="text-danger" id="lowStockAlert">Needs immediate attention</small>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Customers</p>
                            <h5 class="fw-bold mb-0" id="totalCustomers">0</h5>
                        </div>
                        <div class="stat-icon bg-purple bg-opacity-10 text-purple rounded-3 p-2">
                            <i class="bi bi-people fs-4"></i>
                        </div>
                    </div>
                    <small class="text-muted" id="customersChange">Total registered</small>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Expenses</p>
                            <h5 class="fw-bold mb-0" id="totalExpenses">$0.00</h5>
                        </div>
                        <div class="stat-icon bg-danger bg-opacity-10 text-danger rounded-3 p-2">
                            <i class="bi bi-cash-stack fs-4"></i>
                        </div>
                    </div>
                    <small class="text-muted" id="expensesChange">This period</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-graph-up me-2 text-primary"></i>Sales Overview</h6>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-secondary chart-period active" data-period="7">7D</button>
                        <button class="btn btn-outline-secondary chart-period" data-period="30">30D</button>
                        <button class="btn btn-outline-secondary chart-period" data-period="90">90D</button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="salesChart" height="280"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-receipt me-2 text-primary"></i>Recent Transactions</h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush" id="recentTransactions">
                        <div class="text-center text-muted py-4">No recent transactions</div>
                    </div>
                </div>
                <div class="card-footer bg-white border-top text-center">
                    <a href="{{ route('sales.index') }}" class="btn btn-sm btn-outline-primary">View All Transactions</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-star me-2 text-warning"></i>Top Selling Products</h6>
                    <a href="{{ route('products.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Price</th>
                                    <th>Sold</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody id="topProducts">
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No data available</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const ctx = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
                label: 'Sales',
                data: [0, 0, 0, 0, 0, 0, 0],
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    $('.chart-period').on('click', function() {
        $('.chart-period').removeClass('active');
        $(this).addClass('active');
    });
});
</script>
@endpush

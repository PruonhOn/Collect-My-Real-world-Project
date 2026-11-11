@extends('layouts.admin')

@section('title', 'Finance Dashboard - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Finance</li>
@endsection

@section('page-title', 'Finance Dashboard')
@section('page-subtitle', 'Monthly financial overview')

@section('page-actions')
    <div class="btn-group">
        <a href="{{ route('transactions.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> New Transaction
        </a>
        <a href="{{ route('expenses.create') }}" class="btn btn-outline-danger">
            <i class="bi bi-plus-lg"></i> New Expense
        </a>
    </div>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Total Income <span class="text-muted">(This Month)</span></p>
                            <h3 class="mb-0 fw-bold text-success">${{ number_format($monthlyIncome ?? 0, 2) }}</h3>
                            <small class="text-muted">{{ date('F Y') }}</small>
                        </div>
                        <div class="rounded-circle bg-success bg-opacity-10 p-3">
                            <i class="bi bi-arrow-up-right text-success fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card stat-card border-danger">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Total Expenses <span class="text-muted">(This Month)</span></p>
                            <h3 class="mb-0 fw-bold text-danger">${{ number_format($monthlyExpenses ?? 0, 2) }}</h3>
                            <small class="text-muted">{{ date('F Y') }}</small>
                        </div>
                        <div class="rounded-circle bg-danger bg-opacity-10 p-3">
                            <i class="bi bi-arrow-down-right text-danger fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card stat-card border-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Net Profit</p>
                            <h3 class="mb-0 fw-bold text-primary">${{ number_format(($monthlyIncome ?? 0) - ($monthlyExpenses ?? 0), 2) }}</h3>
                            <small class="text-muted">{{ date('F Y') }}</small>
                        </div>
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                            <i class="bi bi-graph-up-arrow text-primary fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card stat-card border-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Pending Invoices</p>
                            <h3 class="mb-0 fw-bold text-warning">${{ number_format($pendingInvoicesTotal ?? 0, 2) }}</h3>
                            <small class="text-muted">{{ $pendingInvoicesCount ?? 0 }} invoices</small>
                        </div>
                        <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                            <i class="bi bi-receipt text-warning fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-2">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Income vs Expenses</h5>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary active" id="viewMonthly">Monthly</button>
                        <button type="button" class="btn btn-outline-secondary" id="viewYearly">Yearly</button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="incomeExpenseChart" style="height: 300px;"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Revenue Trend</h5>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" style="height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Recent Transactions</h5>
            <div class="d-flex gap-2">
                <a href="{{ route('transactions.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
                <a href="{{ route('transactions.create') }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-lg"></i> New
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Transaction #</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th class="text-end">Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTransactions ?? [] as $transaction)
                            <tr>
                                <td><span class="badge bg-secondary">{{ $transaction->transaction_number }}</span></td>
                                <td>{{ $transaction->date->format('d M Y') }}</td>
                                <td>
                                    @if($transaction->type == 'income')
                                        <span class="badge bg-success">Income</span>
                                    @else
                                        <span class="badge bg-danger">Expense</span>
                                    @endif
                                </td>
                                <td>{{ $transaction->category }}</td>
                                <td>{{ Str::limit($transaction->description, 30) }}</td>
                                <td class="text-end fw-medium {{ $transaction->type == 'income' ? 'text-success' : 'text-danger' }}">
                                    {{ $transaction->type == 'income' ? '+' : '-' }}${{ number_format($transaction->amount, 2) }}
                                </td>
                                <td>
                                    <span class="badge bg-{{ $transaction->status == 'completed' ? 'success' : ($transaction->status == 'pending' ? 'warning' : 'secondary') }}">
                                        {{ ucfirst($transaction->status) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="bi bi-cash-stack fs-2 d-block mb-2"></i>
                                    <p class="mb-0">No transactions this month</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const incomeData = @json($incomeData ?? [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]);
        const expenseData = @json($expenseData ?? [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]);
        const revenueData = @json($revenueData ?? [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]);
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        const incomeExpenseCtx = document.getElementById('incomeExpenseChart').getContext('2d');
        new Chart(incomeExpenseCtx, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [{
                    label: 'Income',
                    data: incomeData,
                    backgroundColor: 'rgba(40, 167, 69, 0.7)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 1
                }, {
                    label: 'Expenses',
                    data: expenseData,
                    backgroundColor: 'rgba(220, 53, 69, 0.7)',
                    borderColor: 'rgba(220, 53, 69, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) { return '$' + value.toLocaleString(); }
                        }
                    }
                }
            }
        });

        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    label: 'Net Revenue',
                    data: revenueData,
                    borderColor: 'rgba(13, 110, 253, 1)',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: 'rgba(13, 110, 253, 1)',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) { return '$' + value.toLocaleString(); }
                        }
                    }
                }
            }
        });
    </script>
@endsection

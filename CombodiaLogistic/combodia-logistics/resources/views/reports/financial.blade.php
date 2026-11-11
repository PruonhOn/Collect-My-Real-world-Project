@extends('layouts.admin')

@section('title', 'Financial Report')
@section('subtitle', 'Revenue and expense analysis')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Financial Report</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">Reports</a></li>
                <li class="breadcrumb-item active">Financial Report</li>
            </ol>
        </nav>
    </div>
    <form method="GET" action="{{ route('reports.financial') }}" class="d-flex gap-2 align-items-end">
        <div>
            <label class="form-label small text-muted">Select Year</label>
            <select class="form-select form-select-sm" name="year">
                @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">
            <i class="bi bi-search me-1"></i> Filter
        </button>
    </form>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-3 col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="rounded bg-success bg-opacity-10 p-3 me-3">
                        <i class="bi bi-cash-stack text-success" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-1 small">Total Revenue</p>
                        <h4 class="mb-0 fw-bold">${{ number_format($totalRevenue, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="rounded bg-danger bg-opacity-10 p-3 me-3">
                        <i class="bi bi-arrow-down-circle text-danger" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-1 small">Total Expenses</p>
                        <h4 class="mb-0 fw-bold">${{ number_format($totalExpenses, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="rounded bg-info bg-opacity-10 p-3 me-3">
                        <i class="bi bi-arrow-up-circle text-info" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-1 small">Total Income</p>
                        <h4 class="mb-0 fw-bold">${{ number_format($totalIncome, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="rounded bg-primary bg-opacity-10 p-3 me-3">
                        <i class="bi bi-graph-up-arrow text-primary" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-1 small">Net Profit</p>
                        <h4 class="mb-0 fw-bold {{ $profit >= 0 ? 'text-success' : 'text-danger' }}">
                            ${{ number_format($profit, 2) }}
                        </h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold"><i class="bi bi-bar-chart me-2"></i>Monthly Revenue vs Expenses - {{ $year }}</h6>
    </div>
    <div class="card-body">
        <canvas id="financialChart" height="300"></canvas>
    </div>
</div>
@endsection

@push('styles')
<style>
    .table th { font-weight: 600; font-size: 0.813rem; text-transform: uppercase; letter-spacing: 0.5px; color: #6c757d; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    $(document).ready(function() {
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        const monthlyRevenue = @json($monthlyRevenue);
        const monthlyExpenses = @json($monthlyExpenses);

        const revenueData = months.map((_, i) => monthlyRevenue[i + 1] || 0);
        const expenseData = months.map((_, i) => monthlyExpenses[i + 1] || 0);

        const ctx = document.getElementById('financialChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [
                    {
                        label: 'Revenue',
                        data: revenueData,
                        backgroundColor: 'rgba(6, 214, 160, 0.7)',
                        borderColor: '#06d6a0',
                        borderWidth: 1,
                        borderRadius: 4,
                    },
                    {
                        label: 'Expenses',
                        data: expenseData,
                        backgroundColor: 'rgba(239, 71, 111, 0.7)',
                        borderColor: '#ef476f',
                        borderWidth: 1,
                        borderRadius: 4,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            pointStyleWidth: 10,
                            font: { size: 13 }
                        }
                    },
                    tooltip: {
                        backgroundColor: '#1a1a2e',
                        titleFont: { size: 13 },
                        bodyFont: { size: 12 },
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': $' + context.parsed.y.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 12 } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        ticks: {
                            font: { size: 12 },
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    });
</script>
@endpush

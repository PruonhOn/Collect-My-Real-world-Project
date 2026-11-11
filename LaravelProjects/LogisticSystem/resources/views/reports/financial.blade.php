@extends('layouts.admin')

@section('title', 'Financial Report')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">Reports</a></li>
    <li class="breadcrumb-item active" aria-current="page">Financial Report</li>
@endsection

@section('page-title', 'Financial Report')
@section('page-subtitle', 'Income, expenses, and profit analysis')

@section('page-actions')
    <div class="btn-group">
        <button type="button" class="btn btn-outline-danger btn-sm" onclick="event.preventDefault(); document.getElementById('reportForm').querySelector('[name=export]').value='pdf'; document.getElementById('reportForm').submit();">
            <i class="bi bi-filetype-pdf me-1"></i> PDF
        </button>
        <button type="button" class="btn btn-outline-success btn-sm" onclick="event.preventDefault(); document.getElementById('reportForm').querySelector('[name=export]').value='excel'; document.getElementById('reportForm').submit();">
            <i class="bi bi-file-earmark-excel me-1"></i> Excel
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print
        </button>
    </div>
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <form id="reportForm" method="GET" action="{{ route('reports.financial') }}">
            <input type="hidden" name="export" value="">
            <div class="row g-3 align-items-end">
                <div class="col-lg-2 col-md-4">
                    <label class="form-label small fw-semibold">From Date</label>
                    <input type="date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date', date('Y-m-01')) }}">
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label small fw-semibold">To Date</label>
                    <input type="date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date', date('Y-m-d')) }}">
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label small fw-semibold">Type</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">All Types</option>
                        <option value="income" @selected(request('type') === 'income')>Income</option>
                        <option value="expense" @selected(request('type') === 'expense')>Expense</option>
                    </select>
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="form-label small fw-semibold">Category</label>
                    <select name="category" class="form-select form-select-sm">
                        <option value="">All Categories</option>
                        <option value="shipping_fees" @selected(request('category') === 'shipping_fees')>Shipping Fees</option>
                        <option value="fuel" @selected(request('category') === 'fuel')>Fuel</option>
                        <option value="maintenance" @selected(request('category') === 'maintenance')>Maintenance</option>
                        <option value="salary" @selected(request('category') === 'salary')>Salary</option>
                        <option value="utilities" @selected(request('category') === 'utilities')>Utilities</option>
                        <option value="rent" @selected(request('category') === 'rent')>Rent</option>
                        <option value="insurance" @selected(request('category') === 'insurance')>Insurance</option>
                        <option value="taxes" @selected(request('category') === 'taxes')>Taxes</option>
                        <option value="other" @selected(request('category') === 'other')>Other</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-6">
                    <label class="form-label small fw-semibold">Branch</label>
                    <select name="branch_id" class="form-select form-select-sm">
                        <option value="">All Branches</option>
                        @isset($branches)
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected(request('branch_id') == $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        @endisset
                    </select>
                </div>
                <div class="col-lg-1 col-md-4">
                    <button type="submit" class="btn btn-warning btn-sm w-100">
                        <i class="bi bi-funnel"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm bg-success text-white">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-white-50 text-uppercase fw-semibold">Total Income</small>
                        <h3 class="mb-0 fw-bold mt-1">${{ number_format($summary['total_income'] ?? 0, 2) }}</h3>
                    </div>
                    <div class="bg-white bg-opacity-25 rounded-3 p-2">
                        <i class="bi bi-graph-up-arrow fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm bg-danger text-white">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-white-50 text-uppercase fw-semibold">Total Expenses</small>
                        <h3 class="mb-0 fw-bold mt-1">${{ number_format($summary['total_expenses'] ?? 0, 2) }}</h3>
                    </div>
                    <div class="bg-white bg-opacity-25 rounded-3 p-2">
                        <i class="bi bi-graph-down-arrow fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm {{ (($summary['net_profit'] ?? 0) >= 0) ? 'bg-primary' : 'bg-danger' }} text-white">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-white-50 text-uppercase fw-semibold">Net Profit</small>
                        <h3 class="mb-0 fw-bold mt-1">${{ number_format($summary['net_profit'] ?? 0, 2) }}</h3>
                    </div>
                    <div class="bg-white bg-opacity-25 rounded-3 p-2">
                        <i class="bi bi-{{ (($summary['net_profit'] ?? 0) >= 0) ? 'currency-dollar' : 'exclamation-triangle' }} fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm bg-info text-white">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-white-50 text-uppercase fw-semibold">Transactions</small>
                        <h3 class="mb-0 fw-bold mt-1">{{ $summary['total_transactions'] ?? 0 }}</h3>
                    </div>
                    <div class="bg-white bg-opacity-25 rounded-3 p-2">
                        <i class="bi bi-arrow-left-right fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Chart --}}
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold">
                    <i class="bi bi-bar-chart-line me-1"></i>
                    Financial Overview
                </h6>
            </div>
            <div class="card-body">
                <canvas id="financialChart" height="280"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold">
                    <i class="bi bi-pie-chart me-1"></i>
                    Expense Breakdown
                </h6>
            </div>
            <div class="card-body">
                <canvas id="expensePieChart" height="250"></canvas>
            </div>
        </div>
    </div>
</div>

{{-- Results Table --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold">
            <i class="bi bi-list-columns me-1"></i>
            Transaction Details
            @isset($transactions)
                <span class="badge bg-secondary ms-1">{{ $transactions->total() ?? count($transactions) }}</span>
            @endisset
        </h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="small fw-semibold">#</th>
                    <th class="small fw-semibold">Date</th>
                    <th class="small fw-semibold">Reference</th>
                    <th class="small fw-semibold">Description</th>
                    <th class="small fw-semibold">Category</th>
                    <th class="small fw-semibold">Type</th>
                    <th class="small fw-semibold text-end">Amount</th>
                    <th class="small fw-semibold">Branch</th>
                    <th class="small fw-semibold">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions ?? [] as $txn)
                    <tr>
                        <td class="small">{{ $loop->iteration }}</td>
                        <td class="small">{{ $txn->transaction_date ? $txn->transaction_date->format('d/m/Y') : ($txn->created_at ? $txn->created_at->format('d/m/Y') : 'N/A') }}</td>
                        <td class="small">{{ $txn->reference ?? $txn->invoice_number ?? '-' }}</td>
                        <td class="small">{{ $txn->description ?? $txn->notes ?? 'N/A' }}</td>
                        <td class="small">{{ ucfirst(str_replace('_', ' ', $txn->category ?? 'N/A')) }}</td>
                        <td>
                            @if(($txn->type ?? $txn->transaction_type) === 'income' || ($txn->type ?? $txn->transaction_type) === 'credit')
                                <span class="badge bg-success rounded-pill">Income</span>
                            @else
                                <span class="badge bg-danger rounded-pill">Expense</span>
                            @endif
                        </td>
                        <td class="small text-end fw-semibold {{ ($txn->type === 'income' || ($txn->credit ?? 0) > 0) ? 'text-success' : 'text-danger' }}">
                            @if($txn->type === 'income' || ($txn->credit ?? 0) > 0)
                                +${{ number_format($txn->amount ?? $txn->credit ?? 0, 2) }}
                            @else
                                -${{ number_format($txn->amount ?? $txn->debit ?? 0, 2) }}
                            @endif
                        </td>
                        <td class="small">{{ $txn->branch->name ?? 'N/A' }}</td>
                        <td>
                            <a href="{{ route('transactions.show', $txn) }}" class="btn btn-sm btn-outline-primary" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="bi bi-cash-stack fs-2 d-block mb-2"></i>
                            <p class="mb-0">No transactions found for the selected criteria.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if(isset($transactions) && count($transactions) > 0)
                <tfoot class="table-light">
                    <tr>
                        <td colspan="6" class="small fw-semibold text-end">Totals:</td>
                        <td class="small fw-bold text-success text-end">${{ number_format($summary['total_income'] ?? 0, 2) }}</td>
                        <td colspan="2" class="small fw-bold text-danger">Expenses: ${{ number_format($summary['total_expenses'] ?? 0, 2) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
    @isset($transactions)
        @if(method_exists($transactions, 'links'))
            <div class="card-footer bg-white">
                {{ $transactions->links() }}
            </div>
        @endif
    @endisset
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const chartEl = document.getElementById('financialChart');
    if (chartEl) {
        new Chart(chartEl, {
            type: 'bar',
            data: {
                labels: {!! json_encode($chartLabels ?? ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun']) !!},
                datasets: [
                    {
                        label: 'Income',
                        data: {!! json_encode($chartIncome ?? [0, 0, 0, 0, 0, 0]) !!},
                        backgroundColor: 'rgba(25, 135, 84, 0.7)',
                        borderColor: '#198754',
                        borderWidth: 1,
                        borderRadius: 4,
                    },
                    {
                        label: 'Expenses',
                        data: {!! json_encode($chartExpenses ?? [0, 0, 0, 0, 0, 0]) !!},
                        backgroundColor: 'rgba(220, 53, 69, 0.7)',
                        borderColor: '#dc3545',
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
                        labels: { usePointStyle: true, boxWidth: 6 }
                    }
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
    }

    const pieEl = document.getElementById('expensePieChart');
    if (pieEl) {
        new Chart(pieEl, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($expenseCategories ?? ['Fuel', 'Salary', 'Maintenance', 'Utilities', 'Other']) !!},
                datasets: [{
                    data: {!! json_encode($expenseCategoryAmounts ?? [0, 0, 0, 0, 0]) !!},
                    backgroundColor: [
                        '#0d6efd', '#dc3545', '#ffc107', '#198754', '#6f42c1',
                        '#fd7e14', '#20c997', '#e83e8c', '#0dcaf0', '#6610f2'
                    ],
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 12,
                            usePointStyle: true,
                            boxWidth: 8,
                            font: { size: 11 }
                        }
                    }
                },
                cutout: '65%'
            }
        });
    }
});
</script>
@endpush

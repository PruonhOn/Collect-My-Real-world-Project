@extends('layouts.app')

@section('title', 'Reports')

@push('styles')
<style>
    .report-period-btn.active {
        background-color: var(--bs-primary);
        color: #fff;
    }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="fas fa-file-alt me-2"></i>Reports & Analytics</h4>
    <div class="d-flex gap-2">
        <a href="{{ route('reports.export-pdf', request()->all()) }}" class="btn btn-danger">
            <i class="fas fa-file-pdf me-1"></i>PDF
        </a>
        <a href="{{ route('reports.export-excel', request()->all()) }}" class="btn btn-success">
            <i class="fas fa-file-excel me-1"></i>Excel
        </a>
        <button onclick="window.print()" class="btn btn-secondary">
            <i class="fas fa-print me-1"></i>Print
        </button>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-auto">
                        <div class="btn-group" role="group">
                            <a href="{{ route('reports.index', ['period' => 'daily']) }}" class="btn btn-outline-primary {{ $period == 'daily' ? 'active' : '' }}">Daily</a>
                            <a href="{{ route('reports.index', ['period' => 'weekly']) }}" class="btn btn-outline-primary {{ $period == 'weekly' ? 'active' : '' }}">Weekly</a>
                            <a href="{{ route('reports.index', ['period' => 'monthly']) }}" class="btn btn-outline-primary {{ $period == 'monthly' ? 'active' : '' }}">Monthly</a>
                            <a href="{{ route('reports.index', ['period' => 'custom']) }}" class="btn btn-outline-primary {{ $period == 'custom' ? 'active' : '' }}">Custom</a>
                        </div>
                    </div>
                    @if($period == 'custom')
                    <div class="col-auto">
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate ?? '' }}" placeholder="Start date">
                    </div>
                    <div class="col-auto">
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate ?? '' }}" placeholder="End date">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">Apply</button>
                    </div>
                    @else
                    <input type="hidden" name="period" value="{{ $period }}">
                    @endif
                </form>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="stat-card bg-success bg-opacity-10 text-center">
                            <small class="text-muted text-uppercase">Total Sales</small>
                            <h3 class="fw-bold text-success">${{ number_format($report['sales'], 2) }}</h3>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card bg-danger bg-opacity-10 text-center">
                            <small class="text-muted text-uppercase">Total Expenses</small>
                            <h3 class="fw-bold text-danger">${{ number_format($report['expenses'], 2) }}</h3>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card bg-primary bg-opacity-10 text-center">
                            <small class="text-muted text-uppercase">Net Profit</small>
                            <h3 class="fw-bold {{ $report['profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                ${{ number_format($report['profit'], 2) }}
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0 fw-bold">Sales & Expenses Chart (Last 7 Days)</h6>
            </div>
            <div class="card-body">
                <canvas id="reportChart" height="250"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0 fw-bold">Recent Sales</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentSales as $sale)
                            <tr>
                                <td>{{ $sale->invoice_number }}</td>
                                <td>{{ $sale->items->sum('quantity') }}</td>
                                <td>${{ number_format($sale->total_amount, 2) }}</td>
                                <td>{{ $sale->created_at->format('M d') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">No sales data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-2">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0 fw-bold">Recent Expenses</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($expenses as $expense)
                            <tr>
                                <td>{{ $expense->description }}</td>
                                <td class="text-danger">${{ number_format($expense->amount, 2) }}</td>
                                <td>{{ $expense->date->format('M d, Y') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">No expenses</td></tr>
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
                <h6 class="mb-0 fw-bold">Summary Statistics</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="text-center">
                            <div class="h4 fw-bold text-success">{{ number_format($report['sales_count']) }}</div>
                            <small class="text-muted">Transactions</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <div class="h4 fw-bold">{{ ucfirst($period) }}</div>
                            <small class="text-muted">Report Period</small>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="d-flex justify-content-between">
                    <span>Profit Margin:</span>
                    <span class="fw-bold {{ $report['sales'] > 0 ? 'text-success' : '' }}">
                        {{ $report['sales'] > 0 ? round(($report['profit'] / $report['sales']) * 100, 1) : 0 }}%
                    </span>
                </div>
                <div class="d-flex justify-content-between mt-2">
                    <span>Expense Ratio:</span>
                    <span class="fw-bold text-danger">
                        {{ $report['sales'] > 0 ? round(($report['expenses'] / $report['sales']) * 100, 1) : 0 }}%
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    new Chart(document.getElementById('reportChart'), {
        type: 'bar',
        data: {
            labels: {!! json_encode($chartData['days']) !!},
            datasets: [{
                label: 'Sales',
                data: {!! json_encode($chartData['sales']) !!},
                backgroundColor: 'rgba(25, 135, 84, 0.6)',
                borderColor: '#198754',
                borderWidth: 1
            }, {
                label: 'Expenses',
                data: {!! json_encode($chartData['expenses']) !!},
                backgroundColor: 'rgba(220, 53, 69, 0.6)',
                borderColor: '#dc3545',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(v) { return '$' + v; }
                    }
                }
            }
        }
    });
</script>
@endpush

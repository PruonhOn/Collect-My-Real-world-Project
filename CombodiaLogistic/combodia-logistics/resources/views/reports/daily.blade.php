@extends('layouts.admin')

@section('title', 'Daily Report')
@section('subtitle', 'Daily operations summary')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Daily Report</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('reports.daily') }}">Reports</a></li>
                <li class="breadcrumb-item active">Daily Report</li>
            </ol>
        </nav>
    </div>
    <div>
        <form action="{{ route('reports.daily') }}" method="GET" class="d-flex align-items-center gap-2">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                <input type="date" class="form-control" name="date" value="{{ $date }}">
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-search me-1"></i> Filter
            </button>
        </form>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-2 col-lg-4 col-md-6">
        <div class="card stat-card">
            <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-box-seam"></i>
            </div>
            <div class="stat-label">Total Shipments</div>
            <div class="stat-value">{{ number_format($shipments) }}</div>
        </div>
    </div>

    <div class="col-xl-2 col-lg-4 col-md-6">
        <div class="card stat-card">
            <div class="stat-icon bg-success bg-opacity-10 text-success">
                <i class="bi bi-check-circle"></i>
            </div>
            <div class="stat-label">Delivered</div>
            <div class="stat-value">{{ number_format($delivered) }}</div>
        </div>
    </div>

    <div class="col-xl-2 col-lg-4 col-md-6">
        <div class="card stat-card">
            <div class="stat-icon bg-info bg-opacity-10 text-info">
                <i class="bi bi-currency-dollar"></i>
            </div>
            <div class="stat-label">Revenue</div>
            <div class="stat-value">${{ number_format($revenue, 2) }}</div>
        </div>
    </div>

    <div class="col-xl-2 col-lg-4 col-md-6">
        <div class="card stat-card">
            <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div class="stat-label">Expenses</div>
            <div class="stat-value">${{ number_format($expenses, 2) }}</div>
        </div>
    </div>

    <div class="col-xl-2 col-lg-4 col-md-6">
        <div class="card stat-card">
            <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                <i class="bi bi-wallet2"></i>
            </div>
            <div class="stat-label">Income</div>
            <div class="stat-value">${{ number_format($income, 2) }}</div>
        </div>
    </div>

    <div class="col-xl-2 col-lg-4 col-md-6">
        @php
            $netProfit = $income - $expenses;
        @endphp
        <div class="card stat-card">
            <div class="stat-icon bg-{{ $netProfit >= 0 ? 'success' : 'danger' }} bg-opacity-10 text-{{ $netProfit >= 0 ? 'success' : 'danger' }}">
                <i class="bi bi-graph-up-arrow"></i>
            </div>
            <div class="stat-label">Net Profit</div>
            <div class="stat-value text-{{ $netProfit >= 0 ? 'success' : 'danger' }}">
                @if($netProfit >= 0) +@endif${{ number_format($netProfit, 2) }}
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">Daily Breakdown - {{ \Carbon\Carbon::parse($date)->format('F d, Y') }}</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Metric</th>
                                <th class="text-end">Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><i class="bi bi-box-seam text-primary me-2"></i>Total Shipments</td>
                                <td class="text-end fw-medium">{{ number_format($shipments) }}</td>
                            </tr>
                            <tr>
                                <td><i class="bi bi-check-circle text-success me-2"></i>Delivered</td>
                                <td class="text-end fw-medium">{{ number_format($delivered) }}</td>
                            </tr>
                            <tr>
                                <td><i class="bi bi-currency-dollar text-info me-2"></i>Revenue</td>
                                <td class="text-end fw-medium">${{ number_format($revenue, 2) }}</td>
                            </tr>
                            <tr>
                                <td><i class="bi bi-cash-stack text-danger me-2"></i>Expenses</td>
                                <td class="text-end fw-medium">${{ number_format($expenses, 2) }}</td>
                            </tr>
                            <tr>
                                <td><i class="bi bi-wallet2 text-warning me-2"></i>Income</td>
                                <td class="text-end fw-medium">${{ number_format($income, 2) }}</td>
                            </tr>
                            <tr class="table-light">
                                <td class="fw-bold"><i class="bi bi-graph-up-arrow text-success me-2"></i>Net Profit</td>
                                <td class="text-end fw-bold text-{{ $netProfit >= 0 ? 'success' : 'danger' }}">
                                    @if($netProfit >= 0) +@endif${{ number_format($netProfit, 2) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header">
                <h6 class="mb-0 fw-bold">Delivery Rate</h6>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="deliveryRateChart" height="250"></canvas>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const delivered = {{ $delivered }};
    const shipments = {{ $shipments }};
    const pending = shipments - delivered;

    const deliveryCtx = document.getElementById('deliveryRateChart').getContext('2d');
    new Chart(deliveryCtx, {
        type: 'doughnut',
        data: {
            labels: ['Delivered', 'Pending'],
            datasets: [{
                data: [delivered, pending > 0 ? pending : 0],
                backgroundColor: ['#06d6a0', '#ffd166'],
                borderWidth: 0,
                hoverOffset: 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        usePointStyle: true,
                        pointStyleWidth: 10,
                        font: { size: 12 }
                    }
                },
                tooltip: {
                    backgroundColor: '#1a1a2e',
                    titleFont: { size: 13 },
                    bodyFont: { size: 12 },
                    padding: 12,
                    cornerRadius: 8,
                }
            }
        }
    });
});
</script>
@endpush

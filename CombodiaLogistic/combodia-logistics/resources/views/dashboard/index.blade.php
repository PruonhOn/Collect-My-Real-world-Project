@extends('layouts.admin')

@section('title', 'Dashboard')
@section('subtitle', 'Welcome back! Here\'s what\'s happening today.')

@section('content')
<div class="row g-4 mb-4">
    {{-- Total Shipments Today --}}
    <div class="col-xl-2 col-lg-4 col-md-6">
        <div class="card stat-card">
            <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-box-seam"></i>
            </div>
            <div class="stat-label">Total Shipments (Today)</div>
            <div class="stat-value">{{ $stats['total_shipments_today'] }}</div>
        </div>
    </div>

    {{-- Pending --}}
    <div class="col-xl-2 col-lg-4 col-md-6">
        <div class="card stat-card">
            <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                <i class="bi bi-clock-history"></i>
            </div>
            <div class="stat-label">Pending</div>
            <div class="stat-value">{{ $stats['pending_shipments'] }}</div>
        </div>
    </div>

    {{-- Delivered --}}
    <div class="col-xl-2 col-lg-4 col-md-6">
        <div class="card stat-card">
            <div class="stat-icon bg-success bg-opacity-10 text-success">
                <i class="bi bi-check-circle"></i>
            </div>
            <div class="stat-label">Delivered</div>
            <div class="stat-value">{{ $stats['delivered_shipments'] }}</div>
        </div>
    </div>

    {{-- Revenue (USD) --}}
    <div class="col-xl-2 col-lg-4 col-md-6">
        <div class="card stat-card">
            <div class="stat-icon bg-info bg-opacity-10 text-info">
                <i class="bi bi-currency-dollar"></i>
            </div>
            <div class="stat-label">Revenue (USD)</div>
            <div class="stat-value">${{ number_format($stats['total_revenue'], 2) }}</div>
        </div>
    </div>

    {{-- Expenses (KHR) --}}
    <div class="col-xl-2 col-lg-4 col-md-6">
        <div class="card stat-card">
            <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div class="stat-label">Expenses (USD)</div>
            <div class="stat-value">${{ number_format($stats['total_expenses'], 2) }}</div>
        </div>
    </div>

    {{-- Active Shipments --}}
    <div class="col-xl-2 col-lg-4 col-md-6">
        <div class="card stat-card">
            <div class="stat-icon bg-success bg-opacity-10 text-success">
                <i class="bi bi-graph-up-arrow"></i>
            </div>
            <div class="stat-label">Active Shipments</div>
            <div class="stat-value">{{ $stats['active_shipments'] }}</div>
        </div>
    </div>
</div>

{{-- Charts Row --}}
<div class="row g-4 mb-4">
    {{-- Line Chart --}}
    <div class="col-xl-8 col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Monthly Shipments</h6>
                <div class="dropdown">
                    <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">This Year</button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#">This Month</a></li>
                        <li><a class="dropdown-item" href="#">This Quarter</a></li>
                        <li><a class="dropdown-item" href="#">This Year</a></li>
                    </ul>
                </div>
            </div>
            <div class="card-body">
                <canvas id="shipmentsChart" height="280"></canvas>
            </div>
        </div>
    </div>

    {{-- Doughnut Chart --}}
    <div class="col-xl-4 col-lg-5">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Shipment Status</h6>
                <button class="btn btn-sm btn-light">Today</button>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="statusChart" height="250"></canvas>
            </div>
        </div>
    </div>
</div>

{{-- Recent Shipments --}}
<div class="row g-4">
    <div class="col-xl-8 col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Recent Shipments</h6>
                <a href="{{ route('shipments.index') }}" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Tracking ID</th>
                                <th>Customer</th>
                                <th>Destination</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentShipments as $shipment)
                            <tr>
                                <td><span class="fw-medium">{{ $shipment->shipment_number }}</span></td>
                                <td>{{ $shipment->customer->name ?? 'N/A' }}</td>
                                <td>{{ $shipment->sender_city ?? '' }} &rarr; {{ $shipment->recipient_city ?? '' }}</td>
                                <td>
                                    @switch($shipment->status)
                                        @case('delivered')
                                            <span class="badge bg-success">Delivered</span>
                                            @break
                                        @case('in_transit')
                                            <span class="badge bg-primary">In Transit</span>
                                            @break
                                        @case('pending')
                                            <span class="badge bg-warning text-dark">Pending</span>
                                            @break
                                        @case('processing')
                                            <span class="badge bg-info">Processing</span>
                                            @break
                                        @case('cancelled')
                                            <span class="badge bg-danger">Cancelled</span>
                                            @break
                                        @case('returned')
                                            <span class="badge bg-secondary">Returned</span>
                                            @break
                                        @case('dispatched')
                                            <span class="badge bg-primary">Dispatched</span>
                                            @break
                                        @default
                                            <span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $shipment->status)) }}</span>
                                    @endswitch
                                </td>
                                <td>{{ $shipment->created_at->format('d M Y') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No recent shipments</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="col-xl-4 col-lg-5">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('shipments.create') }}" class="btn btn-primary d-flex align-items-center justify-content-start gap-2">
                        <i class="bi bi-plus-circle"></i>
                        New Shipment
                    </a>
                    <a href="{{ route('pickup-requests.create') }}" class="btn btn-outline-primary d-flex align-items-center justify-content-start gap-2">
                        <i class="bi bi-hand-index"></i>
                        Create Pickup Request
                    </a>
                    <a href="{{ route('invoices.create') }}" class="btn btn-outline-primary d-flex align-items-center justify-content-start gap-2">
                        <i class="bi bi-receipt"></i>
                        Generate Invoice
                    </a>
                    <a href="{{ route('payments.create') }}" class="btn btn-outline-primary d-flex align-items-center justify-content-start gap-2">
                        <i class="bi bi-credit-card"></i>
                        Record Payment
                    </a>
                    <a href="{{ route('tracking.index') }}" class="btn btn-outline-primary d-flex align-items-center justify-content-start gap-2">
                        <i class="bi bi-geo-alt"></i>
                        Track Shipment
                    </a>
                    <a href="{{ route('reports.daily') }}" class="btn btn-outline-primary d-flex align-items-center justify-content-start gap-2">
                        <i class="bi bi-file-earmark-bar-graph"></i>
                        View Reports
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Line Chart - Monthly Shipments
    const monthlyLabels = @json($monthlyShipments->pluck('month')->toArray());
    const monthlyData = @json($monthlyShipments->pluck('total')->toArray());

    const shipmentsCtx = document.getElementById('shipmentsChart').getContext('2d');
    const shipmentsChart = new Chart(shipmentsCtx, {
        type: 'line',
        data: {
            labels: monthlyLabels.length ? monthlyLabels : ['No Data'],
            datasets: [{
                label: 'Shipments',
                data: monthlyData.length ? monthlyData : [0],
                borderColor: '#4361ee',
                backgroundColor: 'rgba(67, 97, 238, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#4361ee',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    backgroundColor: '#1a1a2e',
                    titleFont: { size: 13 },
                    bodyFont: { size: 12 },
                    padding: 12,
                    cornerRadius: 8,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return 'Shipments: ' + context.parsed.y;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                    },
                    ticks: {
                        font: { size: 12 }
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0,0,0,0.05)',
                    },
                    ticks: {
                        font: { size: 12 }
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index',
            }
        }
    });

    // Doughnut Chart - Shipment Status
    const statusLabels = @json($statusDistribution->keys()->map(fn($s) => ucfirst(str_replace('_', ' ', $s)))->toArray());
    const statusData = @json($statusDistribution->values()->toArray());
    const statusColors = ['#06d6a0', '#4361ee', '#ffd166', '#118ab2', '#ef476f', '#6c757d', '#0dcaf0', '#ffc107'];

    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const statusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: statusLabels.length ? statusLabels : ['No Data'],
            datasets: [{
                data: statusData.length ? statusData : [1],
                backgroundColor: statusColors.slice(0, statusLabels.length || 1),
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
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.parsed;
                        }
                    }
                }
            }
        }
    });
});
</script>
@endpush

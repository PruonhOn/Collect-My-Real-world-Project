@extends('layouts.admin')

@section('title', 'Delivery Report')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">Reports</a></li>
    <li class="breadcrumb-item active" aria-current="page">Delivery Report</li>
@endsection

@section('page-title', 'Delivery Report')
@section('page-subtitle', 'Delivery performance, routes, and driver metrics')

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
        <form id="reportForm" method="GET" action="{{ route('reports.deliveries') }}">
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
                    <label class="form-label small fw-semibold">Delivery Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="assigned" @selected(request('status') === 'assigned')>Assigned</option>
                        <option value="picked_up" @selected(request('status') === 'picked_up')>Picked Up</option>
                        <option value="in_transit" @selected(request('status') === 'in_transit')>In Transit</option>
                        <option value="out_for_delivery" @selected(request('status') === 'out_for_delivery')>Out for Delivery</option>
                        <option value="delivered" @selected(request('status') === 'delivered')>Delivered</option>
                        <option value="failed" @selected(request('status') === 'failed')>Failed</option>
                        <option value="returned" @selected(request('status') === 'returned')>Returned</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label small fw-semibold">Driver</label>
                    <select name="driver_id" class="form-select form-select-sm">
                        <option value="">All Drivers</option>
                        @isset($drivers)
                            @foreach($drivers as $driver)
                                <option value="{{ $driver->id }}" @selected(request('driver_id') == $driver->id)>{{ $driver->name }}</option>
                            @endforeach
                        @endisset
                    </select>
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label small fw-semibold">Route</label>
                    <select name="route_id" class="form-select form-select-sm">
                        <option value="">All Routes</option>
                        @isset($routes)
                            @foreach($routes as $route)
                                <option value="{{ $route->id }}" @selected(request('route_id') == $route->id)>{{ $route->name }}</option>
                            @endforeach
                        @endisset
                    </select>
                </div>
                <div class="col-lg-2 col-md-4">
                    <button type="submit" class="btn btn-success btn-sm w-100">
                        <i class="bi bi-funnel me-1"></i> Generate
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="rounded-3 bg-success bg-opacity-10 p-2 me-3">
                        <i class="bi bi-check-circle text-success fs-4"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-semibold">Delivered</small>
                        <h4 class="mb-0 fw-bold">{{ $stats['delivered'] ?? 0 }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="rounded-3 bg-warning bg-opacity-10 p-2 me-3">
                        <i class="bi bi-clock-history text-warning fs-4"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-semibold">In Transit</small>
                        <h4 class="mb-0 fw-bold">{{ $stats['in_transit'] ?? 0 }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="rounded-3 bg-danger bg-opacity-10 p-2 me-3">
                        <i class="bi bi-exclamation-triangle text-danger fs-4"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-semibold">Failed</small>
                        <h4 class="mb-0 fw-bold">{{ $stats['failed'] ?? 0 }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="rounded-3 bg-primary bg-opacity-10 p-2 me-3">
                        <i class="bi bi-truck text-primary fs-4"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-semibold">On-Time Rate</small>
                        <h4 class="mb-0 fw-bold">{{ $stats['on_time_rate'] ?? 0 }}%</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold">
            <i class="bi bi-truck me-1"></i>
            Delivery Results
            @isset($deliveries)
                <span class="badge bg-secondary ms-1">{{ $deliveries->total() ?? count($deliveries) }}</span>
            @endisset
        </h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="small fw-semibold">#</th>
                    <th class="small fw-semibold">Delivery #</th>
                    <th class="small fw-semibold">Tracking #</th>
                    <th class="small fw-semibold">Driver</th>
                    <th class="small fw-semibold">Route</th>
                    <th class="small fw-semibold">Consignee</th>
                    <th class="small fw-semibold">Status</th>
                    <th class="small fw-semibold">Scheduled</th>
                    <th class="small fw-semibold">Delivered</th>
                    <th class="small fw-semibold">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($deliveries ?? [] as $delivery)
                    <tr>
                        <td class="small">{{ $loop->iteration }}</td>
                        <td>
                            <a href="{{ route('deliveries.show', $delivery) }}" class="fw-semibold text-decoration-none small">
                                {{ $delivery->delivery_number ?? 'DLV-' . str_pad($delivery->id, 6, '0', STR_PAD_LEFT) }}
                            </a>
                        </td>
                        <td class="small">{{ $delivery->shipment->tracking_number ?? 'N/A' }}</td>
                        <td class="small">{{ $delivery->driver->name ?? 'N/A' }}</td>
                        <td class="small">{{ $delivery->route->name ?? 'N/A' }}</td>
                        <td class="small">{{ $delivery->consignee_name ?? 'N/A' }}</td>
                        <td>
                            @php
                                $dStatusMap = ['assigned'=>'secondary','picked_up'=>'info','in_transit'=>'primary','out_for_delivery'=>'info','delivered'=>'success','failed'=>'danger','returned'=>'dark'];
                            @endphp
                            <span class="badge bg-{{ $dStatusMap[$delivery->status] ?? 'secondary' }} rounded-pill">
                                {{ ucfirst(str_replace('_', ' ', $delivery->status)) }}
                            </span>
                        </td>
                        <td class="small">{{ $delivery->scheduled_date ? $delivery->scheduled_date->format('d/m/Y') : 'N/A' }}</td>
                        <td class="small">{{ $delivery->delivered_at ? $delivery->delivered_at->format('d/m/Y h:i A') : '-' }}</td>
                        <td>
                            <a href="{{ route('deliveries.show', $delivery) }}" class="btn btn-sm btn-outline-primary" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-5 text-muted">
                            <i class="bi bi-truck fs-2 d-block mb-2"></i>
                            <p class="mb-0">No deliveries found for the selected criteria.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @isset($deliveries)
        @if(method_exists($deliveries, 'links'))
            <div class="card-footer bg-white">
                {{ $deliveries->links() }}
            </div>
        @endif
    @endisset
</div>
@endsection

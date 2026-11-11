@extends('layouts.admin')

@section('title', 'Shipment Report')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">Reports</a></li>
    <li class="breadcrumb-item active" aria-current="page">Shipment Report</li>
@endsection

@section('page-title', 'Shipment Report')
@section('page-subtitle', 'Comprehensive shipment data and analysis')

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
        <form id="reportForm" method="GET" action="{{ route('reports.shipments') }}">
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
                    <label class="form-label small fw-semibold">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                        <option value="picked_up" @selected(request('status') === 'picked_up')>Picked Up</option>
                        <option value="in_transit" @selected(request('status') === 'in_transit')>In Transit</option>
                        <option value="at_hub" @selected(request('status') === 'at_hub')>At Hub</option>
                        <option value="out_for_delivery" @selected(request('status') === 'out_for_delivery')>Out for Delivery</option>
                        <option value="delivered" @selected(request('status') === 'delivered')>Delivered</option>
                        <option value="failed" @selected(request('status') === 'failed')>Failed</option>
                        <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label small fw-semibold">Type</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">All Types</option>
                        <option value="import" @selected(request('type') === 'import')>Import</option>
                        <option value="export" @selected(request('type') === 'export')>Export</option>
                        <option value="domestic" @selected(request('type') === 'domestic')>Domestic</option>
                        <option value="transit" @selected(request('type') === 'transit')>Transit</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-4">
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
                <div class="col-lg-2 col-md-4">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-funnel me-1"></i> Generate
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold">
            <i class="bi bi-box-seam me-1"></i>
            Shipment Results
            @isset($shipments)
                <span class="badge bg-secondary ms-1">{{ $shipments->total() ?? count($shipments) }}</span>
            @endisset
        </h6>
        <div>
            <small class="text-muted">
                @isset($fromDate) {{ $fromDate }} @endisset
                @isset($toDate) — {{ $toDate }} @endisset
            </small>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="small fw-semibold">#</th>
                    <th class="small fw-semibold">Tracking #</th>
                    <th class="small fw-semibold">Customer</th>
                    <th class="small fw-semibold">Origin</th>
                    <th class="small fw-semibold">Destination</th>
                    <th class="small fw-semibold">Status</th>
                    <th class="small fw-semibold">Type</th>
                    <th class="small fw-semibold">Pieces</th>
                    <th class="small fw-semibold">Weight</th>
                    <th class="small fw-semibold">Created</th>
                    <th class="small fw-semibold">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($shipments ?? [] as $shipment)
                    <tr>
                        <td class="small">{{ $loop->iteration }}</td>
                        <td>
                            <a href="{{ route('shipments.show', $shipment) }}" class="fw-semibold text-decoration-none small">
                                {{ $shipment->tracking_number }}
                            </a>
                        </td>
                        <td class="small">{{ $shipment->customer->name ?? $shipment->shipper_name ?? 'N/A' }}</td>
                        <td class="small">{{ $shipment->origin ?? 'N/A' }}</td>
                        <td class="small">{{ $shipment->destination ?? 'N/A' }}</td>
                        <td>
                            @php
                                $badgeMap = ['pending'=>'warning','picked_up'=>'info','in_transit'=>'primary','at_hub'=>'secondary','out_for_delivery'=>'info','delivered'=>'success','failed'=>'danger','cancelled'=>'dark'];
                            @endphp
                            <span class="badge bg-{{ $badgeMap[$shipment->status] ?? 'secondary' }} rounded-pill">
                                {{ ucfirst(str_replace('_', ' ', $shipment->status)) }}
                            </span>
                        </td>
                        <td class="small">{{ ucfirst($shipment->type ?? 'N/A') }}</td>
                        <td class="small">{{ $shipment->pieces ?? 0 }}</td>
                        <td class="small">{{ $shipment->weight ?? 0 }} kg</td>
                        <td class="small">{{ $shipment->created_at ? $shipment->created_at->format('d/m/Y') : 'N/A' }}</td>
                        <td>
                            <a href="{{ route('shipments.show', $shipment) }}" class="btn btn-sm btn-outline-primary" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            <p class="mb-0">No shipments found for the selected criteria.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @isset($shipments)
        @if(method_exists($shipments, 'links'))
            <div class="card-footer bg-white">
                {{ $shipments->links() }}
            </div>
        @endif
    @endisset
</div>
@endsection

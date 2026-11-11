@extends('layouts.admin')

@section('title', 'Customer Report')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">Reports</a></li>
    <li class="breadcrumb-item active" aria-current="page">Customer Report</li>
@endsection

@section('page-title', 'Customer Report')
@section('page-subtitle', 'Customer activity, shipment volume, and revenue analysis')

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
        <form id="reportForm" method="GET" action="{{ route('reports.customers') }}">
            <input type="hidden" name="export" value="">
            <div class="row g-3 align-items-end">
                <div class="col-lg-3 col-md-6">
                    <label class="form-label small fw-semibold">From Date</label>
                    <input type="date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date', date('Y-m-01')) }}">
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="form-label small fw-semibold">To Date</label>
                    <input type="date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date', date('Y-m-d')) }}">
                </div>
                <div class="col-lg-4 col-md-8">
                    <label class="form-label small fw-semibold">Customer</label>
                    <select name="customer_id" class="form-select form-select-sm">
                        <option value="">All Customers</option>
                        @isset($customers)
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" @selected(request('customer_id') == $customer->id)>{{ $customer->name }} ({{ $customer->code ?? $customer->id }})</option>
                            @endforeach
                        @endisset
                    </select>
                </div>
                <div class="col-lg-2 col-md-4">
                    <button type="submit" class="btn btn-info btn-sm w-100 text-white">
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
                    <div class="rounded-3 bg-primary bg-opacity-10 p-2 me-3">
                        <i class="bi bi-people text-primary fs-4"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-semibold">Total Customers</small>
                        <h4 class="mb-0 fw-bold">{{ $stats['total_customers'] ?? 0 }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="rounded-3 bg-success bg-opacity-10 p-2 me-3">
                        <i class="bi bi-box-seam text-success fs-4"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-semibold">Total Shipments</small>
                        <h4 class="mb-0 fw-bold">{{ $stats['total_shipments'] ?? 0 }}</h4>
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
                        <i class="bi bi-cash-stack text-warning fs-4"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-semibold">Total Revenue</small>
                        <h4 class="mb-0 fw-bold">${{ number_format($stats['total_revenue'] ?? 0, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="rounded-3 bg-info bg-opacity-10 p-2 me-3">
                        <i class="bi bi-graph-up-arrow text-info fs-4"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-semibold">Avg / Customer</small>
                        <h4 class="mb-0 fw-bold">${{ number_format($stats['avg_revenue_per_customer'] ?? 0, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold">
            <i class="bi bi-people me-1"></i>
            Customer Results
            @isset($results)
                <span class="badge bg-secondary ms-1">{{ $results->total() ?? count($results) }}</span>
            @endisset
        </h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="small fw-semibold">#</th>
                    <th class="small fw-semibold">Customer</th>
                    <th class="small fw-semibold">Code</th>
                    <th class="small fw-semibold">Email / Phone</th>
                    <th class="small fw-semibold text-end">Total Shipments</th>
                    <th class="small fw-semibold text-end">Total Revenue</th>
                    <th class="small fw-semibold text-end">Avg per Shipment</th>
                    <th class="small fw-semibold text-end">Last Shipment</th>
                    <th class="small fw-semibold">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($results ?? [] as $row)
                    <tr>
                        <td class="small">{{ $loop->iteration }}</td>
                        <td>
                            <a href="{{ route('customers.show', $row->customer ?? $row->id) }}" class="fw-semibold text-decoration-none small">
                                {{ $row->name ?? $row->customer->name ?? 'N/A' }}
                            </a>
                        </td>
                        <td class="small text-muted">{{ $row->code ?? $row->customer->code ?? '-' }}</td>
                        <td class="small">{{ $row->email ?? $row->customer->email ?? '-' }}<br><span class="text-muted">{{ $row->phone ?? $row->customer->phone ?? '' }}</span></td>
                        <td class="small text-end fw-semibold">{{ $row->total_shipments ?? 0 }}</td>
                        <td class="small text-end fw-semibold text-success">${{ number_format($row->total_revenue ?? 0, 2) }}</td>
                        <td class="small text-end">${{ number_format($row->avg_per_shipment ?? 0, 2) }}</td>
                        <td class="small text-end text-muted">
                            @if(isset($row->last_shipment_date))
                                {{ $row->last_shipment_date instanceof \Carbon\Carbon ? $row->last_shipment_date->format('d/m/Y') : $row->last_shipment_date }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('customers.show', $row->customer ?? $row->id) }}" class="btn btn-sm btn-outline-info" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="bi bi-people fs-2 d-block mb-2"></i>
                            <p class="mb-0">No customer data found for the selected criteria.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @isset($results)
        @if(method_exists($results, 'links'))
            <div class="card-footer bg-white">
                {{ $results->links() }}
            </div>
        @endif
    @endisset
</div>
@endsection

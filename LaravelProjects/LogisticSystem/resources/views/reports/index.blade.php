@extends('layouts.admin')

@section('title', 'Reports')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Reports</li>
@endsection

@section('page-title', 'Reports Dashboard')
@section('page-subtitle', 'Generate and export detailed reports')

@section('content')
<div class="row g-4">
    {{-- Shipment Report --}}
    <div class="col-xl-4 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-3 bg-primary bg-opacity-10 p-3">
                        <i class="bi bi-box-seam fs-3 text-primary"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1">Shipment Report</h5>
                        <p class="text-muted small mb-0">Track all shipments, volumes, and delivery performance</p>
                    </div>
                </div>
                <form action="{{ route('reports.shipments') }}" method="GET">
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">From</label>
                            <input type="date" name="from_date" class="form-control form-control-sm" value="{{ date('Y-m-01') }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">To</label>
                            <input type="date" name="to_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}">
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                            <i class="bi bi-eye me-1"></i> View Report
                        </button>
                        <div class="btn-group btn-group-sm">
                            <button type="submit" name="export" value="pdf" class="btn btn-outline-danger" title="Export PDF">
                                <i class="bi bi-filetype-pdf"></i>
                            </button>
                            <button type="submit" name="export" value="excel" class="btn btn-outline-success" title="Export Excel">
                                <i class="bi bi-file-earmark-excel"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Delivery Report --}}
    <div class="col-xl-4 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-3 bg-success bg-opacity-10 p-3">
                        <i class="bi bi-truck fs-3 text-success"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1">Delivery Report</h5>
                        <p class="text-muted small mb-0">Monitor delivery times, routes, and driver performance</p>
                    </div>
                </div>
                <form action="{{ route('reports.deliveries') }}" method="GET">
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">From</label>
                            <input type="date" name="from_date" class="form-control form-control-sm" value="{{ date('Y-m-01') }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">To</label>
                            <input type="date" name="to_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}">
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success btn-sm flex-grow-1">
                            <i class="bi bi-eye me-1"></i> View Report
                        </button>
                        <div class="btn-group btn-group-sm">
                            <button type="submit" name="export" value="pdf" class="btn btn-outline-danger" title="Export PDF">
                                <i class="bi bi-filetype-pdf"></i>
                            </button>
                            <button type="submit" name="export" value="excel" class="btn btn-outline-success" title="Export Excel">
                                <i class="bi bi-file-earmark-excel"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Customer Report --}}
    <div class="col-xl-4 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-3 bg-info bg-opacity-10 p-3">
                        <i class="bi bi-people fs-3 text-info"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1">Customer Report</h5>
                        <p class="text-muted small mb-0">View customer activity, shipments, and revenue data</p>
                    </div>
                </div>
                <form action="{{ route('reports.customers') }}" method="GET">
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">From</label>
                            <input type="date" name="from_date" class="form-control form-control-sm" value="{{ date('Y-m-01') }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">To</label>
                            <input type="date" name="to_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}">
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-info btn-sm flex-grow-1 text-white">
                            <i class="bi bi-eye me-1"></i> View Report
                        </button>
                        <div class="btn-group btn-group-sm">
                            <button type="submit" name="export" value="pdf" class="btn btn-outline-danger" title="Export PDF">
                                <i class="bi bi-filetype-pdf"></i>
                            </button>
                            <button type="submit" name="export" value="excel" class="btn btn-outline-success" title="Export Excel">
                                <i class="bi bi-file-earmark-excel"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Financial Report --}}
    <div class="col-xl-4 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-3 bg-warning bg-opacity-10 p-3">
                        <i class="bi bi-cash-stack fs-3 text-warning"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1">Financial Report</h5>
                        <p class="text-muted small mb-0">Income, expenses, profit analysis, and financial summaries</p>
                    </div>
                </div>
                <form action="{{ route('reports.financial') }}" method="GET">
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">From</label>
                            <input type="date" name="from_date" class="form-control form-control-sm" value="{{ date('Y-m-01') }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">To</label>
                            <input type="date" name="to_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}">
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-warning btn-sm flex-grow-1">
                            <i class="bi bi-eye me-1"></i> View Report
                        </button>
                        <div class="btn-group btn-group-sm">
                            <button type="submit" name="export" value="pdf" class="btn btn-outline-danger" title="Export PDF">
                                <i class="bi bi-filetype-pdf"></i>
                            </button>
                            <button type="submit" name="export" value="excel" class="btn btn-outline-success" title="Export Excel">
                                <i class="bi bi-file-earmark-excel"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Warehouse / Inventory Report --}}
    <div class="col-xl-4 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-3 bg-secondary bg-opacity-10 p-3">
                        <i class="bi bi-building fs-3 text-secondary"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1">Warehouse Report</h5>
                        <p class="text-muted small mb-0">Inventory levels, warehouse capacity, and stock movements</p>
                    </div>
                </div>
                <form action="{{ route('reports.warehouse') }}" method="GET">
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">From</label>
                            <input type="date" name="from_date" class="form-control form-control-sm" value="{{ date('Y-m-01') }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">To</label>
                            <input type="date" name="to_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}">
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-secondary btn-sm flex-grow-1">
                            <i class="bi bi-eye me-1"></i> View Report
                        </button>
                        <div class="btn-group btn-group-sm">
                            <button type="submit" name="export" value="pdf" class="btn btn-outline-danger" title="Export PDF">
                                <i class="bi bi-filetype-pdf"></i>
                            </button>
                            <button type="submit" name="export" value="excel" class="btn btn-outline-success" title="Export Excel">
                                <i class="bi bi-file-earmark-excel"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Driver Performance Report --}}
    <div class="col-xl-4 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-3 bg-danger bg-opacity-10 p-3">
                        <i class="bi bi-person-badge fs-3 text-danger"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1">Driver Performance</h5>
                        <p class="text-muted small mb-0">Driver delivery stats, efficiency, and performance metrics</p>
                    </div>
                </div>
                <form action="{{ route('reports.drivers') }}" method="GET">
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">From</label>
                            <input type="date" name="from_date" class="form-control form-control-sm" value="{{ date('Y-m-01') }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">To</label>
                            <input type="date" name="to_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}">
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-danger btn-sm flex-grow-1">
                            <i class="bi bi-eye me-1"></i> View Report
                        </button>
                        <div class="btn-group btn-group-sm">
                            <button type="submit" name="export" value="pdf" class="btn btn-outline-danger" title="Export PDF">
                                <i class="bi bi-filetype-pdf"></i>
                            </button>
                            <button type="submit" name="export" value="excel" class="btn btn-outline-success" title="Export Excel">
                                <i class="bi bi-file-earmark-excel"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

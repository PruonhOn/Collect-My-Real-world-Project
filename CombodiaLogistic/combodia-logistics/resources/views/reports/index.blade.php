@extends('layouts.admin')

@section('title', 'Reports')
@section('subtitle', 'Generate and export reports')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Reports</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Reports</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row g-4">
    <!-- Daily Report -->
    <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="rounded bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                    <i class="bi bi-calendar-day text-primary" style="font-size: 1.5rem;"></i>
                </div>
                <h5 class="fw-bold">Daily Report</h5>
                <p class="text-muted small">Daily operations summary</p>
                <div class="mb-3">
                    <input type="date" class="form-control" id="dailyDate" value="{{ date('Y-m-d') }}">
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('reports.daily') }}" class="btn btn-primary flex-grow-1">
                        <i class="bi bi-eye me-1"></i> Generate
                    </a>
                    <button class="btn btn-outline-success" onclick="exportReport('daily', 'pdf')">
                        <i class="bi bi-file-pdf"></i>
                    </button>
                    <button class="btn btn-outline-info" onclick="exportReport('daily', 'excel')">
                        <i class="bi bi-file-excel"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Weekly Report -->
    <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="rounded bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                    <i class="bi bi-calendar-week text-success" style="font-size: 1.5rem;"></i>
                </div>
                <h5 class="fw-bold">Weekly Report</h5>
                <p class="text-muted small">Weekly performance overview</p>
                <div class="mb-3">
                    <input type="week" class="form-control" id="weeklyDate">
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('reports.weekly') }}" class="btn btn-success flex-grow-1">
                        <i class="bi bi-eye me-1"></i> Generate
                    </a>
                    <button class="btn btn-outline-success" onclick="exportReport('weekly', 'pdf')">
                        <i class="bi bi-file-pdf"></i>
                    </button>
                    <button class="btn btn-outline-info" onclick="exportReport('weekly', 'excel')">
                        <i class="bi bi-file-excel"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Report -->
    <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="rounded bg-info bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                    <i class="bi bi-calendar-month text-info" style="font-size: 1.5rem;"></i>
                </div>
                <h5 class="fw-bold">Monthly Report</h5>
                <p class="text-muted small">Monthly business analysis</p>
                <div class="mb-3">
                    <input type="month" class="form-control" id="monthlyDate" value="{{ date('Y-m') }}">
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('reports.monthly') }}" class="btn btn-info flex-grow-1">
                        <i class="bi bi-eye me-1"></i> Generate
                    </a>
                    <button class="btn btn-outline-success" onclick="exportReport('monthly', 'pdf')">
                        <i class="bi bi-file-pdf"></i>
                    </button>
                    <button class="btn btn-outline-info" onclick="exportReport('monthly', 'excel')">
                        <i class="bi bi-file-excel"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Annual Report -->
    <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="rounded bg-warning bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                    <i class="bi bi-calendar-range text-warning" style="font-size: 1.5rem;"></i>
                </div>
                <h5 class="fw-bold">Annual Report</h5>
                <p class="text-muted small">Yearly comprehensive report</p>
                <div class="mb-3">
                    <select class="form-select" id="annualYear">
                        @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('reports.annual') }}" class="btn btn-warning flex-grow-1">
                        <i class="bi bi-eye me-1"></i> Generate
                    </a>
                    <button class="btn btn-outline-success" onclick="exportReport('annual', 'pdf')">
                        <i class="bi bi-file-pdf"></i>
                    </button>
                    <button class="btn btn-outline-info" onclick="exportReport('annual', 'excel')">
                        <i class="bi bi-file-excel"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Shipment Report -->
    <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="rounded bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                    <i class="bi bi-box-seam text-primary" style="font-size: 1.5rem;"></i>
                </div>
                <h5 class="fw-bold">Shipment Report</h5>
                <p class="text-muted small">Shipment analytics & stats</p>
                <div class="mb-3">
                    <input type="date" class="form-control mb-2" id="shipFrom" placeholder="From">
                    <input type="date" class="form-control" id="shipTo" placeholder="To">
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('reports.shipment') }}" class="btn btn-primary flex-grow-1">
                        <i class="bi bi-eye me-1"></i> Generate
                    </a>
                    <button class="btn btn-outline-success" onclick="exportReport('shipment', 'pdf')">
                        <i class="bi bi-file-pdf"></i>
                    </button>
                    <button class="btn btn-outline-info" onclick="exportReport('shipment', 'excel')">
                        <i class="bi bi-file-excel"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Financial Report -->
    <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="rounded bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                    <i class="bi bi-cash-stack text-success" style="font-size: 1.5rem;"></i>
                </div>
                <h5 class="fw-bold">Financial Report</h5>
                <p class="text-muted small">Revenue & expense analysis</p>
                <div class="mb-3">
                    <input type="date" class="form-control mb-2" id="finFrom">
                    <input type="date" class="form-control" id="finTo">
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('reports.financial') }}" class="btn btn-success flex-grow-1">
                        <i class="bi bi-eye me-1"></i> Generate
                    </a>
                    <button class="btn btn-outline-success" onclick="exportReport('financial', 'pdf')">
                        <i class="bi bi-file-pdf"></i>
                    </button>
                    <button class="btn btn-outline-info" onclick="exportReport('financial', 'excel')">
                        <i class="bi bi-file-excel"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Inventory Report -->
    <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="rounded bg-danger bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                    <i class="bi bi-inboxes text-danger" style="font-size: 1.5rem;"></i>
                </div>
                <h5 class="fw-bold">Inventory Report</h5>
                <p class="text-muted small">Stock levels & movements</p>
                <div class="mb-3">&nbsp;</div>
                <div class="d-flex gap-2">
                    <a href="{{ route('reports.inventory') }}" class="btn btn-danger flex-grow-1">
                        <i class="bi bi-eye me-1"></i> Generate
                    </a>
                    <button class="btn btn-outline-success" onclick="exportReport('inventory', 'pdf')">
                        <i class="bi bi-file-pdf"></i>
                    </button>
                    <button class="btn btn-outline-info" onclick="exportReport('inventory', 'excel')">
                        <i class="bi bi-file-excel"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Driver Report -->
    <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="rounded bg-secondary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                    <i class="bi bi-person-badge text-secondary" style="font-size: 1.5rem;"></i>
                </div>
                <h5 class="fw-bold">Driver Report</h5>
                <p class="text-muted small">Driver performance & activity</p>
                <div class="mb-3">
                    <select class="form-select" id="driverSelect">
                        <option value="">All Drivers</option>
                        @foreach($drivers as $driver)
                            <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('reports.driver') }}" class="btn btn-secondary flex-grow-1">
                        <i class="bi bi-eye me-1"></i> Generate
                    </a>
                    <button class="btn btn-outline-success" onclick="exportReport('driver', 'pdf')">
                        <i class="bi bi-file-pdf"></i>
                    </button>
                    <button class="btn btn-outline-info" onclick="exportReport('driver', 'excel')">
                        <i class="bi bi-file-excel"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Warehouse Report -->
    <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="rounded bg-info bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                    <i class="bi bi-warehouse text-info" style="font-size: 1.5rem;"></i>
                </div>
                <h5 class="fw-bold">Warehouse Report</h5>
                <p class="text-muted small">Warehouse utilization & capacity</p>
                <div class="mb-3">
                    <select class="form-select" id="warehouseSelect">
                        <option value="">All Warehouses</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('reports.warehouse') }}" class="btn btn-info flex-grow-1">
                        <i class="bi bi-eye me-1"></i> Generate
                    </a>
                    <button class="btn btn-outline-success" onclick="exportReport('warehouse', 'pdf')">
                        <i class="bi bi-file-pdf"></i>
                    </button>
                    <button class="btn btn-outline-info" onclick="exportReport('warehouse', 'excel')">
                        <i class="bi bi-file-excel"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Report -->
    <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="rounded bg-warning bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                    <i class="bi bi-people text-warning" style="font-size: 1.5rem;"></i>
                </div>
                <h5 class="fw-bold">Customer Report</h5>
                <p class="text-muted small">Customer activity & spending</p>
                <div class="mb-3">
                    <select class="form-select" id="customerSelect">
                        <option value="">All Customers</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('reports.customer') }}" class="btn btn-warning flex-grow-1">
                        <i class="bi bi-eye me-1"></i> Generate
                    </a>
                    <button class="btn btn-outline-success" onclick="exportReport('customer', 'pdf')">
                        <i class="bi bi-file-pdf"></i>
                    </button>
                    <button class="btn btn-outline-info" onclick="exportReport('customer', 'excel')">
                        <i class="bi bi-file-excel"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Vehicle Report -->
    <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="rounded bg-dark bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                    <i class="bi bi-truck text-dark" style="font-size: 1.5rem;"></i>
                </div>
                <h5 class="fw-bold">Vehicle Report</h5>
                <p class="text-muted small">Fleet status & maintenance</p>
                <div class="mb-3">
                    <select class="form-select" id="vehicleSelect">
                        <option value="">All Vehicles</option>
                        @foreach($vehicles as $vehicle)
                            <option value="{{ $vehicle->id }}">{{ $vehicle->plate_number }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('reports.vehicle') }}" class="btn btn-dark flex-grow-1">
                        <i class="bi bi-eye me-1"></i> Generate
                    </a>
                    <button class="btn btn-outline-success" onclick="exportReport('vehicle', 'pdf')">
                        <i class="bi bi-file-pdf"></i>
                    </button>
                    <button class="btn btn-outline-info" onclick="exportReport('vehicle', 'excel')">
                        <i class="bi bi-file-excel"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Profit & Loss -->
    <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="rounded bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                    <i class="bi bi-graph-up-arrow text-success" style="font-size: 1.5rem;"></i>
                </div>
                <h5 class="fw-bold">Profit & Loss</h5>
                <p class="text-muted small">P&L statement with date range</p>
                <div class="mb-3">
                    <input type="date" class="form-control mb-2" id="plFrom">
                    <input type="date" class="form-control" id="plTo">
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('reports.profit-loss') }}" class="btn btn-success flex-grow-1">
                        <i class="bi bi-eye me-1"></i> Generate
                    </a>
                    <button class="btn btn-outline-success" onclick="exportReport('profit-loss', 'pdf')">
                        <i class="bi bi-file-pdf"></i>
                    </button>
                    <button class="btn btn-outline-info" onclick="exportReport('profit-loss', 'excel')">
                        <i class="bi bi-file-excel"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function exportReport(type, format) {
        Swal.fire({
            title: 'Export Report',
            text: `Exporting ${type} report as ${format.toUpperCase()}...`,
            icon: 'info',
            timer: 2000,
            showConfirmButton: false
        });
    }
</script>
@endpush

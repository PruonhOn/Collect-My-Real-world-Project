@extends('layouts.admin')

@section('title', 'Packages')
@section('subtitle', 'Manage all packages')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Packages</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Packages</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-info" onclick="bulkGenerateBarcode()">
            <i class="bi bi-upc-scan me-1"></i> Generate Barcodes
        </button>
        <button type="button" class="btn btn-outline-primary" onclick="bulkPrintLabels()">
            <i class="bi bi-printer me-1"></i> Print Labels
        </button>
        <a href="{{ route('packages.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> New Package
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="dataTable">
                <thead class="table-light">
                    <tr>
                        <th><input type="checkbox" class="form-check-input" id="selectAll"></th>
                        <th>Package #</th>
                        <th>Barcode</th>
                        <th>Shipment</th>
                        <th>Type</th>
                        <th>Weight (kg)</th>
                        <th>Status</th>
                        <th>Location</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($packages as $package)
                    <tr>
                        <td><input type="checkbox" class="form-check-input package-check" value="{{ $package->id }}"></td>
                        <td><a href="{{ route('packages.show', $package) }}" class="fw-semibold text-decoration-none">{{ $package->package_number }}</a></td>
                        <td><code class="bg-light px-2 py-1">{{ $package->barcode }}</code></td>
                        <td><a href="{{ route('shipments.show', $package->shipment) }}" class="text-decoration-none">{{ $package->shipment->shipment_number ?? 'N/A' }}</a></td>
                        <td><span class="badge bg-light text-dark">{{ ucfirst($package->type) }}</span></td>
                        <td>{{ number_format($package->weight, 2) }}</td>
                        <td>
                            @switch($package->status)
                                @case('pending')
                                    <span class="badge bg-warning text-dark">Pending</span>
                                    @break
                                @case('in_warehouse')
                                    <span class="badge bg-info">In Warehouse</span>
                                    @break
                                @case('in_transit')
                                    <span class="badge bg-primary">In Transit</span>
                                    @break
                                @case('delivered')
                                    <span class="badge bg-success">Delivered</span>
                                    @break
                                @default
                                    <span class="badge bg-secondary">{{ ucfirst($package->status) }}</span>
                            @endswitch
                        </td>
                        <td>
                            @if($package->warehouse)
                                <span>{{ $package->warehouse->name }}</span>
                                @if($package->shelf)
                                    <span class="text-muted">/ {{ $package->shelf }}</span>
                                @endif
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('packages.show', $package) }}"><i class="bi bi-eye me-2"></i>View</a></li>
                                    <li><a class="dropdown-item" href="{{ route('packages.edit', $package) }}"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="generateBarcode('{{ $package->barcode }}')"><i class="bi bi-upc-scan me-2"></i>Barcode</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="printLabel('{{ $package->package_number }}')"><i class="bi bi-printer me-2"></i>Print Label</a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                            <p class="mt-2">No packages found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<style>
    .table th { font-weight: 600; font-size: 0.813rem; text-transform: uppercase; letter-spacing: 0.5px; color: #6c757d; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        $('#dataTable').DataTable({
            order: [[0, 'desc']],
            pageLength: 25,
            responsive: true
        });

        $('#selectAll').on('change', function() {
            $('.package-check').prop('checked', this.checked);
        });
    });

    function generateBarcode(code) {
        Swal.fire({
            title: 'Barcode',
            html: '<div class="text-center"><i class="bi bi-upc-scan" style="font-size: 4rem;"></i><p class="mt-2 fw-bold">' + code + '</p></div>',
            confirmButtonText: 'Close'
        });
    }

    function printLabel(packageNumber) {
        Swal.fire({
            title: 'Print Label',
            text: 'Print label for ' + packageNumber,
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Print'
        });
    }

    function bulkGenerateBarcode() {
        const selected = $('.package-check:checked');
        if (selected.length === 0) {
            Swal.fire('Warning', 'Please select packages first.', 'warning');
            return;
        }
        Swal.fire('Success', 'Barcodes generated for ' + selected.length + ' packages.', 'success');
    }

    function bulkPrintLabels() {
        const selected = $('.package-check:checked');
        if (selected.length === 0) {
            Swal.fire('Warning', 'Please select packages first.', 'warning');
            return;
        }
        window.print();
    }
</script>
@endpush

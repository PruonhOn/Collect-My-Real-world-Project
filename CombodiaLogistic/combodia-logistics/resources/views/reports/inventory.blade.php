@extends('layouts.admin')

@section('title', 'Inventory Report')
@section('subtitle', 'Stock levels and inventory status')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Inventory Report</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">Reports</a></li>
                <li class="breadcrumb-item active">Inventory Report</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-3 col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="rounded bg-primary bg-opacity-10 p-3 me-3">
                        <i class="bi bi-inboxes text-primary" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-1 small">Total Items</p>
                        <h4 class="mb-0 fw-bold">{{ $inventories->total() }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="rounded bg-warning bg-opacity-10 p-3 me-3">
                        <i class="bi bi-exclamation-triangle text-warning" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-1 small">Low Stock</p>
                        <h4 class="mb-0 fw-bold">{{ $lowStockItems }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="rounded bg-danger bg-opacity-10 p-3 me-3">
                        <i class="bi bi-x-circle text-danger" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-1 small">Out of Stock</p>
                        <h4 class="mb-0 fw-bold">{{ $outOfStock }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="rounded bg-success bg-opacity-10 p-3 me-3">
                        <i class="bi bi-currency-dollar text-success" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-1 small">Total Value</p>
                        <h4 class="mb-0 fw-bold">${{ number_format($totalValue, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold"><i class="bi bi-funnel me-2"></i>Filters</h6>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('reports.inventory') }}">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Warehouse</label>
                    <select class="form-select" name="warehouse_id">
                        <option value="">All Warehouses</option>
                        @foreach(\App\Models\Warehouse::orderBy('name')->get() as $wh)
                            <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-8">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search me-1"></i> Filter
                    </button>
                    <a href="{{ route('reports.inventory') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-body">
        <div class="table-responsive">
            <table id="inventoryReportTable" class="table table-bordered table-striped w-100">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Warehouse</th>
                        <th>Shelf</th>
                        <th>Quantity</th>
                        <th>Reorder Point</th>
                        <th>Unit Cost</th>
                        <th>Stock Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($inventories as $item)
                    <tr>
                        <td><span class="fw-semibold">{{ $item->product_name }}</span></td>
                        <td><code class="bg-light px-2 py-1">{{ $item->sku }}</code></td>
                        <td>{{ $item->warehouse->name ?? 'N/A' }}</td>
                        <td>{{ $item->warehouseShelf->name ?? '-' }}</td>
                        <td>
                            <span class="fw-semibold">{{ $item->quantity }}</span>
                        </td>
                        <td>{{ $item->reorder_point }}</td>
                        <td>${{ number_format($item->unit_cost, 2) }}</td>
                        <td>
                            @if($item->quantity <= 0)
                                <span class="badge bg-danger">Out of Stock</span>
                            @elseif($item->quantity <= $item->reorder_point)
                                <span class="badge bg-warning text-dark">Low Stock</span>
                            @else
                                <span class="badge bg-success">In Stock</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                            <p class="mt-2">No inventory items found</p>
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
<script>
    $(document).ready(function() {
        $('#inventoryReportTable').DataTable({
            responsive: true,
            order: [[0, 'asc']],
            pageLength: 25,
            language: {
                search: '<i class="bi bi-search"></i>',
                searchPlaceholder: 'Search inventory...',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ items',
                emptyTable: 'No inventory items found',
                paginate: { previous: '<i class="bi bi-chevron-left"></i>', next: '<i class="bi bi-chevron-right"></i>' }
            }
        });
    });
</script>
@endpush

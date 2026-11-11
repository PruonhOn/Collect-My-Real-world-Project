@extends('layouts.admin')

@section('title', 'Inventory')
@section('subtitle', 'Stock overview')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Inventory</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Inventory</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-warning" onclick="stockAdjustment()">
            <i class="bi bi-sliders me-1"></i> Stock Adjustment
        </button>
        <a href="{{ route('inventory.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Add Item
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="dataTable">
                <thead class="table-light">
                    <tr>
                        <th>SKU</th>
                        <th>Product</th>
                        <th>Warehouse</th>
                        <th>Shelf</th>
                        <th>Qty</th>
                        <th>Min Qty</th>
                        <th>Reorder Point</th>
                        <th>Unit Cost</th>
                        <th>Selling Price</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($inventory as $item)
                    <tr>
                        <td><code class="bg-light px-2 py-1">{{ $item->sku }}</code></td>
                        <td>
                            <span class="fw-semibold">{{ $item->product_name }}</span>
                            @if($item->barcode)
                                <br><small class="text-muted">Barcode: {{ $item->barcode }}</small>
                            @endif
                        </td>
                        <td>{{ $item->warehouse->name ?? 'N/A' }}</td>
                        <td>{{ $item->shelf ?? '-' }}</td>
                        <td>
                            <span class="fw-semibold">{{ $item->quantity }}</span>
                        </td>
                        <td>{{ $item->min_quantity }}</td>
                        <td>{{ $item->reorder_point }}</td>
                        <td>${{ number_format($item->unit_cost, 2) }}</td>
                        <td>${{ number_format($item->selling_price, 2) }}</td>
                        <td>
                            @if($item->quantity <= 0)
                                <span class="badge bg-danger">Out of Stock</span>
                            @elseif($item->quantity <= $item->reorder_point)
                                <span class="badge bg-warning text-dark">Below Reorder</span>
                            @elseif($item->quantity > ($item->max_quantity ?? PHP_INT_MAX))
                                <span class="badge bg-info">Overstocked</span>
                            @else
                                <span class="badge bg-success">In Stock</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('inventory.show', $item) }}"><i class="bi bi-eye me-2"></i>View</a></li>
                                    <li><a class="dropdown-item" href="{{ route('inventory.edit', $item) }}"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="#" onclick="deleteItem({{ $item->id }})"><i class="bi bi-trash me-2"></i>Delete</a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="text-center py-4 text-muted">
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

<!-- Stock Adjustment Modal -->
<div class="modal fade" id="stockAdjustmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Stock Adjustment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Select Item</label>
                    <select class="form-select" id="adjustItem">
                        <option value="">Select Item</option>
                        @foreach($inventory as $item)
                            <option value="{{ $item->id }}">{{ $item->sku }} - {{ $item->product_name }} (Current: {{ $item->quantity }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Adjustment Type</label>
                        <select class="form-select" id="adjustType">
                            <option value="add">Add Stock</option>
                            <option value="subtract">Remove Stock</option>
                            <option value="set">Set Quantity</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="adjustQty" min="0">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Reason</label>
                        <textarea class="form-control" id="adjustReason" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitAdjustment()">Apply Adjustment</button>
            </div>
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
    });

    function stockAdjustment() {
        new bootstrap.Modal(document.getElementById('stockAdjustmentModal')).show();
    }

    function submitAdjustment() {
        const item = $('#adjustItem').val();
        const type = $('#adjustType').val();
        const qty = $('#adjustQty').val();
        const reason = $('#adjustReason').val();

        if (!item || !qty) {
            Swal.fire('Warning', 'Please fill in all required fields.', 'warning');
            return;
        }

        Swal.fire({
            title: 'Apply Adjustment?',
            text: 'Stock will be adjusted accordingly.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Apply'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire('Success', 'Stock adjustment applied.', 'success');
                bootstrap.Modal.getInstance(document.getElementById('stockAdjustmentModal')).hide();
            }
        });
    }

    function deleteItem(id) {
        Swal.fire({
            title: 'Delete Item?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, Delete'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/admin/inventory/' + id;
                form.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="DELETE">';
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
</script>
@endpush

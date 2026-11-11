@extends('layouts.app')

@section('title', 'Inventory Management')

@push('styles')
<style>
    .low-stock td { background-color: #fff0f0 !important; }
    .out-of-stock td { background-color: #ffe0e0 !important; }
    .overstock td { background-color: #f0fff0 !important; }
    .stock-badge { min-width: 60px; text-align: center; }
    .action-btn-group { white-space: nowrap; }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0"><i class="bi bi-archive me-2 text-primary"></i>Inventory Management</h4>
            <p class="text-muted small mb-0">Track and manage stock levels across warehouses</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#stockInModal">
                <i class="bi bi-box-arrow-in-down me-1"></i>Stock In
            </button>
            <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#stockOutModal">
                <i class="bi bi-box-arrow-up me-1"></i>Stock Out
            </button>
            <button class="btn btn-info text-white" data-bs-toggle="modal" data-bs-target="#adjustModal">
                <i class="bi bi-sliders me-1"></i>Adjust
            </button>
            <button class="btn btn-outline-secondary" onclick="location.reload()">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
        </div>
    </div>

    <ul class="nav nav-tabs mb-4" id="inventoryTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="stock-tab" data-bs-toggle="tab" data-bs-target="#stockTab" type="button" role="tab">
                <i class="bi bi-boxes me-1"></i>Stock Levels
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="movements-tab" data-bs-toggle="tab" data-bs-target="#movementsTab" type="button" role="tab">
                <i class="bi bi-arrow-left-right me-1"></i>Stock Movements
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="alerts-tab" data-bs-toggle="tab" data-bs-target="#alertsTab" type="button" role="tab">
                <i class="bi bi-exclamation-triangle me-1"></i>Alerts <span class="badge bg-danger ms-1" id="alertCount">0</span>
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="stockTab" role="tabpanel">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" id="stockFilterWarehouse">
                                <option value="">All Warehouses</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" id="stockFilterCategory">
                                <option value="">All Categories</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select form-select-sm" id="stockFilterStatus">
                                <option value="">All Status</option>
                                <option value="low">Low Stock</option>
                                <option value="out">Out of Stock</option>
                                <option value="over">Overstock</option>
                                <option value="normal">Normal</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="text" class="form-control form-control-sm" id="stockFilterSearch" placeholder="Search product...">
                        </div>
                        <div class="col-md-2 d-flex gap-1">
                            <button class="btn btn-sm btn-outline-primary flex-fill" id="stockApplyFilters"><i class="bi bi-search"></i></button>
                            <button class="btn btn-sm btn-outline-secondary" id="stockResetFilters"><i class="bi bi-x-lg"></i></button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="inventoryTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Warehouse</th>
                                    <th>Quantity</th>
                                    <th>Min Stock</th>
                                    <th>Max Stock</th>
                                    <th>Status</th>
                                    <th>Last Updated</th>
                                    <th style="width:120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">Loading inventory...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="movementsTab" role="tabpanel">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" id="movementType">
                                <option value="">All Types</option>
                                <option value="in">Stock In</option>
                                <option value="out">Stock Out</option>
                                <option value="adjustment">Adjustment</option>
                                <option value="transfer">Transfer</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="date" class="form-control form-control-sm" id="movementFrom">
                        </div>
                        <div class="col-md-3">
                            <input type="date" class="form-control form-control-sm" id="movementTo">
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-sm btn-outline-primary" id="movementFilterBtn"><i class="bi bi-funnel me-1"></i>Filter</button>
                            <button class="btn btn-sm btn-outline-secondary" id="movementResetBtn"><i class="bi bi-arrow-counterclockwise"></i></button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="movementsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Product</th>
                                    <th>Type</th>
                                    <th>Quantity</th>
                                    <th>Reference</th>
                                    <th>Notes</th>
                                    <th>By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No movements recorded yet</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white border-top text-center">
                    <a href="{{ route('inventory.movements') }}" class="btn btn-sm btn-outline-primary">View All Movements</a>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="alertsTab" role="tabpanel">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-exclamation-triangle me-2 text-danger"></i>Stock Alerts</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="alertsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Current Qty</th>
                                    <th>Min Stock</th>
                                    <th>Alert Type</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No alerts</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="stockInModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="stockInForm">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-box-arrow-in-down me-2 text-success"></i>Stock In</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Product <span class="text-danger">*</span></label>
                        <select class="form-select" name="product_id" required>
                            <option value="">Select Product</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Warehouse <span class="text-danger">*</span></label>
                        <select class="form-select" name="warehouse_id" required>
                            <option value="">Select Warehouse</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantity <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="quantity" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Unit Cost</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.01" min="0" class="form-control" name="unit_cost">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reference</label>
                        <input type="text" class="form-control" name="reference" placeholder="PO-001, Invoice #...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Optional notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i>Add Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="stockOutModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="stockOutForm">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-box-arrow-up me-2 text-warning"></i>Stock Out</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Product <span class="text-danger">*</span></label>
                        <select class="form-select" name="product_id" required>
                            <option value="">Select Product</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Warehouse <span class="text-danger">*</span></label>
                        <select class="form-select" name="warehouse_id" required>
                            <option value="">Select Warehouse</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantity <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="quantity" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason <span class="text-danger">*</span></label>
                        <select class="form-select" name="reason" required>
                            <option value="">Select Reason</option>
                            <option value="sale">Sale</option>
                            <option value="damage">Damage / Expired</option>
                            <option value="return">Return to Supplier</option>
                            <option value="transfer">Transfer</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reference</label>
                        <input type="text" class="form-control" name="reference" placeholder="Sale #, RMA #...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-check-lg me-1"></i>Remove Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="adjustModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="adjustForm">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-sliders me-2 text-info"></i>Stock Adjustment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Product <span class="text-danger">*</span></label>
                        <select class="form-select" name="product_id" required>
                            <option value="">Select Product</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Warehouse <span class="text-danger">*</span></label>
                        <select class="form-select" name="warehouse_id" required>
                            <option value="">Select Warehouse</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Adjustment Type</label>
                        <select class="form-select" name="adjustment_type" required>
                            <option value="add">Add (Increase Stock)</option>
                            <option value="subtract">Subtract (Decrease Stock)</option>
                            <option value="set">Set to Exact Value</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantity <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="quantity" min="0" step="1" required>
                        <small class="text-muted">For "Add" or "Subtract", this is the delta. For "Set to Exact", this is the new quantity.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="reason" placeholder="Count discrepancy, damaged, etc." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info text-white"><i class="bi bi-check-lg me-1"></i>Apply Adjustment</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {

    const invTable = $('#inventoryTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("inventory.data") }}',
            data: function(d) {
                d.warehouse = $('#stockFilterWarehouse').val();
                d.category = $('#stockFilterCategory').val();
                d.status = $('#stockFilterStatus').val();
                d.search = $('#stockFilterSearch').val();
            }
        },
        columns: [
            { data: 'product', render: d => d ? d.name : '-' },
            { data: 'product', render: d => d ? d.sku : '-' },
            { data: 'warehouse', render: d => d ? d.name : 'Main' },
            {
                data: 'quantity',
                render: function(d, t, r) {
                    const min = r.min_stock || 5;
                    const max = r.max_stock || 100;
                    let cls = 'bg-success';
                    let icon = 'bi-check-circle';
                    if (d <= 0) { cls = 'bg-danger'; icon = 'bi-x-circle'; }
                    else if (d <= min) { cls = 'bg-warning text-dark'; icon = 'bi-exclamation-triangle'; }
                    else if (d >= max) { cls = 'bg-info'; icon = 'bi-arrow-up-circle'; }
                    return `<span class="badge ${cls} stock-badge"><i class="bi ${icon} me-1"></i>${d}</span>`;
                }
            },
            { data: 'min_stock', render: d => d ?? '-' },
            { data: 'max_stock', render: d => d ?? '-' },
            {
                data: null,
                render: function(d) {
                    const qty = d.quantity || 0;
                    const min = d.min_stock || 5;
                    if (qty <= 0) return '<span class="badge bg-danger">Out of Stock</span>';
                    if (qty <= min) return '<span class="badge bg-warning text-dark">Low Stock</span>';
                    if (qty >= (d.max_stock || 100)) return '<span class="badge bg-info">Overstock</span>';
                    return '<span class="badge bg-success">In Stock</span>';
                }
            },
            { data: 'updated_at', render: d => d ? new Date(d).toLocaleDateString() : '-' },
            {
                data: 'id',
                orderable: false,
                render: function(d) {
                    return `
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-success stock-inline-in" data-id="${d}" title="Stock In"><i class="bi bi-plus-circle"></i></button>
                            <button class="btn btn-outline-warning stock-inline-out" data-id="${d}" title="Stock Out"><i class="bi bi-dash-circle"></i></button>
                            <button class="btn btn-outline-info stock-inline-history" data-id="${d}" title="History"><i class="bi bi-clock-history"></i></button>
                        </div>
                    `;
                }
            }
        ],
        order: [[3, 'asc']],
        rowCallback: function(row, data) {
            const qty = data.quantity || 0;
            const min = data.min_stock || 5;
            if (qty <= 0) $(row).addClass('out-of-stock');
            else if (qty <= min) $(row).addClass('low-stock');
            else if (qty >= (data.max_stock || 100)) $(row).addClass('overstock');
        },
        pageLength: 25
    });

    $('#stockApplyFilters').on('click', function() { invTable.draw(); });
    $('#stockResetFilters').on('click', function() {
        $('#stockFilterWarehouse, #stockFilterCategory, #stockFilterStatus').val('');
        $('#stockFilterSearch').val('');
        invTable.draw();
    });
    $('#stockFilterSearch').on('keyup', function(e) { if (e.key === 'Enter') invTable.draw(); });

    const movTable = $('#movementsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("inventory.movements.data") }}',
            data: function(d) {
                d.type = $('#movementType').val();
                d.from = $('#movementFrom').val();
                d.to = $('#movementTo').val();
            }
        },
        columns: [
            { data: 'created_at', render: d => d ? new Date(d).toLocaleString() : '-' },
            { data: 'product', render: d => d ? d.name : '-' },
            {
                data: 'type',
                render: function(d) {
                    const map = { in: 'success', out: 'danger', adjustment: 'info', transfer: 'secondary' };
                    return `<span class="badge bg-${map[d] || 'secondary'}">${d.toUpperCase()}</span>`;
                }
            },
            { data: 'quantity', render: d => d ?? '-' },
            { data: 'reference', render: d => d || '-' },
            { data: 'notes', render: d => d || '-' },
            { data: 'user', render: d => d ? d.name : '-' }
        ],
        order: [[0, 'desc']],
        pageLength: 15
    });

    $('#movementFilterBtn').on('click', function() { movTable.draw(); });
    $('#movementResetBtn').on('click', function() {
        $('#movementType, #movementFrom, #movementTo').val('');
        movTable.draw();
    });

    const alertTable = $('#alertsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("inventory.alerts") }}',
            data: function(d) {
                d.search = $('#alertSearch').val();
            }
        },
        columns: [
            { data: 'product', render: d => d ? d.name : '-' },
            { data: 'product', render: d => d ? d.sku : '-' },
            { data: 'quantity' },
            { data: 'min_stock', render: d => d ?? '-' },
            {
                data: null,
                render: function(d) {
                    const qty = d.quantity || 0;
                    return qty <= 0
                        ? '<span class="badge bg-danger">Out of Stock</span>'
                        : '<span class="badge bg-warning text-dark">Low Stock</span>';
                }
            },
            {
                data: 'id',
                orderable: false,
                render: function(d) {
                    return `<button class="btn btn-sm btn-outline-primary restock-btn" data-id="${d}"><i class="bi bi-cart-plus me-1"></i>Restock</button>`;
                }
            }
        ],
        order: [[2, 'asc']],
        pageLength: 25
    });

    function loadSelectOptions(url, select, placeholder) {
        $.get(url, function(data) {
            const el = $(select);
            el.empty().append('<option value="">' + placeholder + '</option>');
            data.forEach(function(item) {
                el.append('<option value="' + item.id + '">' + (item.name || item.product_name) + '</option>');
            });
        });
    }

    loadSelectOptions('{{ route("categories.list") }}', '#stockFilterCategory, select[name="category_id"]', 'Select Category');
    loadSelectOptions('{{ route("warehouses.list") }}', '#stockFilterWarehouse, select[name="warehouse_id"]', 'Select Warehouse');
    loadSelectOptions('{{ route("products.list") }}', '#stockInForm select[name="product_id"], #stockOutForm select[name="product_id"], #adjustForm select[name="product_id"]', 'Select Product');

    $('#stockInForm').on('submit', function(e) {
        e.preventDefault();
        const data = $(this).serialize() + '&_token={{ csrf_token() }}';
        $.post('{{ route("inventory.stock-in") }}', data, function(resp) {
            $('#stockInModal').modal('hide');
            $('#stockInForm')[0].reset();
            invTable.draw();
            toastr.success('Stock added successfully');
        }).fail(function(xhr) {
            toastr.error(xhr.responseJSON?.message || 'Error adding stock');
        });
    });

    $('#stockOutForm').on('submit', function(e) {
        e.preventDefault();
        const data = $(this).serialize() + '&_token={{ csrf_token() }}';
        $.post('{{ route("inventory.stock-out") }}', data, function(resp) {
            $('#stockOutModal').modal('hide');
            $('#stockOutForm')[0].reset();
            invTable.draw();
            toastr.success('Stock removed successfully');
        }).fail(function(xhr) {
            toastr.error(xhr.responseJSON?.message || 'Error removing stock');
        });
    });

    $('#adjustForm').on('submit', function(e) {
        e.preventDefault();
        const data = $(this).serialize() + '&_token={{ csrf_token() }}';
        $.post('{{ route("inventory.adjust") }}', data, function(resp) {
            $('#adjustModal').modal('hide');
            $('#adjustForm')[0].reset();
            invTable.draw();
            toastr.success('Stock adjusted successfully');
        }).fail(function(xhr) {
            toastr.error(xhr.responseJSON?.message || 'Error adjusting stock');
        });
    });

    $(document).on('click', '.stock-inline-in', function() {
        const id = $(this).data('id');
        $('#stockInForm select[name="product_id"]').val(id).trigger('change');
        $('#stockInModal').modal('show');
    });

    $(document).on('click', '.stock-inline-out', function() {
        const id = $(this).data('id');
        $('#stockOutForm select[name="product_id"]').val(id).trigger('change');
        $('#stockOutModal').modal('show');
    });

    $(document).on('click', '.stock-inline-history', function() {
        const id = $(this).data('id');
        $('#movements-tab').tab('show');
        $('#movementFilterBtn').trigger('click');
    });

    $(document).on('click', '.restock-btn', function() {
        const id = $(this).data('id');
        $('#stockInForm select[name="product_id"]').val(id).trigger('change');
        $('#stockInModal').modal('show');
    });

    $('#stock-tab').on('shown.bs.tab', function() { invTable.draw(); });
    $('#movements-tab').on('shown.bs.tab', function() { movTable.draw(); });
    $('#alerts-tab').on('shown.bs.tab', function() { alertTable.draw(); });

    function updateAlertCount() {
        $.get('{{ route("inventory.alerts.count") }}', function(resp) {
            $('#alertCount').text(resp.count || 0);
        });
    }
    updateAlertCount();
    setInterval(updateAlertCount, 60000);
});
</script>
@endpush

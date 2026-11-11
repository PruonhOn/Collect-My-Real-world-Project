@extends('layouts.app')
@section('title', 'Purchases')
@section('page-title', 'Purchases')

@push('styles')
<style>
    .product-row td:first-child { border-top: none; }
    .subtotal-display { font-weight: 600; }
    .remove-row { cursor: pointer; }
</style>
@endpush

@section('content')
<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center bg-white">
        <h5 class="mb-0"><i class="bi bi-cart-plus me-2 text-primary"></i>All Purchases</h5>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#purchaseModal" onclick="resetPurchaseForm()">
            <i class="bi bi-plus-lg me-1"></i>New Purchase
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="purchasesTable">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Reference No</th>
                        <th>Supplier</th>
                        <th>Date</th>
                        <th>Grand Total</th>
                        <th>Paid</th>
                        <th>Due</th>
                        <th>Payment Status</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchases ?? [] as $purchase)
                    @php
                        $due = $purchase->grand_total - $purchase->paid;
                        if ($due <= 0) $ps = '<span class="badge bg-success">Paid</span>';
                        elseif ($due < $purchase->grand_total) $ps = '<span class="badge bg-warning text-dark">Partial</span>';
                        else $ps = '<span class="badge bg-danger">Unpaid</span>';
                    @endphp
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><code>{{ $purchase->reference_no }}</code></td>
                        <td>{{ $purchase->supplier->name ?? '—' }}</td>
                        <td>{{ $purchase->date ? $purchase->date->format('Y-m-d') : '—' }}</td>
                        <td>{{ number_format($purchase->grand_total, 2) }}</td>
                        <td>{{ number_format($purchase->paid, 2) }}</td>
                        <td class="text-{{ $due > 0 ? 'danger' : 'success' }}">{{ number_format($due, 2) }}</td>
                        <td>{!! $ps !!}</td>
                        <td>{!! $purchase->status ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>' !!}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-info" title="View" onclick="viewPurchase({{ $purchase->id }})">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-primary" title="Edit" onclick="editPurchase({{ $purchase->id }})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" title="Delete" onclick="confirmDelete({{ $purchase->id }}, '{{ $purchase->reference_no }}')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="10" class="text-center text-muted py-4">No purchases found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="purchaseModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl">
        <form id="purchaseForm" method="POST" action="{{ route('purchases.store') }}" class="modal-content">
            @csrf
            <input type="hidden" name="_method" id="methodField" value="POST">
            <input type="hidden" name="id" id="purchaseId">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle"><i class="bi bi-cart-plus me-2"></i>New Purchase</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Supplier <span class="text-danger">*</span></label>
                        <select name="supplier_id" id="supplierId" class="form-select" required>
                            <option value="">Select Supplier</option>
                            @foreach($suppliers ?? [] as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date <span class="text-danger">*</span></label>
                        <input type="date" name="date" id="date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Reference No</label>
                        <input type="text" name="reference_no" id="referenceNo" class="form-control" readonly>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>

                <h6 class="fw-semibold mb-2"><i class="bi bi-box me-1"></i>Products</h6>
                <div class="table-responsive mb-3">
                    <table class="table table-bordered" id="productsTable">
                        <thead class="table-secondary">
                            <tr>
                                <th style="width:35%">Product</th>
                                <th style="width:15%">Quantity</th>
                                <th style="width:15%">Cost</th>
                                <th style="width:15%">Subtotal</th>
                                <th style="width:20%">Action</th>
                            </tr>
                        </thead>
                        <tbody id="productRows">
                            <tr class="product-row">
                                <td>
                                    <select name="products[0][product_id]" class="form-select product-select" required>
                                        <option value="">Select Product</option>
                                        @foreach($products ?? [] as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" name="products[0][quantity]" class="form-control qty" min="1" value="1" required></td>
                                <td><input type="number" step="0.01" name="products[0][cost]" class="form-control cost" min="0" value="0" required></td>
                                <td><span class="subtotal-display">0.00</span></td>
                                <td><button type="button" class="btn btn-sm btn-outline-danger remove-row" onclick="removeRow(this)"><i class="bi bi-x-lg"></i></button></td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5">
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addRow()">
                                        <i class="bi bi-plus-lg me-1"></i>Add Product
                                    </button>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="row justify-content-end">
                    <div class="col-md-4">
                        <table class="table table-sm">
                            <tr>
                                <td class="fw-semibold">Grand Total</td>
                                <td class="text-end"><span id="grandTotal">0.00</span></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Paid Amount</td>
                                <td><input type="number" step="0.01" name="paid" id="paid" class="form-control form-control-sm text-end" value="0" min="0"></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Due</td>
                                <td class="text-end"><span id="dueDisplay" class="text-danger">0.00</span></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Purchase</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-eye me-2"></i>Purchase Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewContent">
                <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h6 class="modal-title text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Confirm Delete</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-3">
                <p class="mb-0">Delete purchase <strong id="deleteItemName"></strong>?</p>
                <small class="text-muted">This action cannot be undone.</small>
            </div>
            <div class="modal-footer border-0 justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" class="d-inline">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger"><i class="bi bi-trash me-1"></i>Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let table, rowIndex = 1;
    $(document).ready(function () {
        table = $('#purchasesTable').DataTable({ pageLength: 25, order: [[3, 'desc']] });
    });

    function resetPurchaseForm() {
        $('#purchaseForm')[0].reset();
        $('#methodField').val('POST');
        $('#purchaseForm').attr('action', '{{ route("purchases.store") }}');
        $('#modalTitle').html('<i class="bi bi-cart-plus me-2"></i>New Purchase');
        $('#purchaseId').val('');
        $('#referenceNo').val('PO-' + Date.now());
        $('#date').val('{{ date("Y-m-d") }}');
        $('#productRows').html(`
            <tr class="product-row">
                <td><select name="products[0][product_id]" class="form-select product-select" required><option value="">Select Product</option>@foreach($products ?? [] as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select></td>
                <td><input type="number" name="products[0][quantity]" class="form-control qty" min="1" value="1" required></td>
                <td><input type="number" step="0.01" name="products[0][cost]" class="form-control cost" min="0" value="0" required></td>
                <td><span class="subtotal-display">0.00</span></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger remove-row" onclick="removeRow(this)"><i class="bi bi-x-lg"></i></button></td>
            </tr>
        `);
        rowIndex = 1;
        recalcTotals();
    }

    function addRow() {
        const idx = rowIndex++;
        const html = `
            <tr class="product-row">
                <td><select name="products[${idx}][product_id]" class="form-select product-select" required><option value="">Select Product</option>@foreach($products ?? [] as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select></td>
                <td><input type="number" name="products[${idx}][quantity]" class="form-control qty" min="1" value="1" required></td>
                <td><input type="number" step="0.01" name="products[${idx}][cost]" class="form-control cost" min="0" value="0" required></td>
                <td><span class="subtotal-display">0.00</span></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger remove-row" onclick="removeRow(this)"><i class="bi bi-x-lg"></i></button></td>
            </tr>`;
        $('#productRows').append(html);
    }

    function removeRow(btn) {
        if ($('#productRows .product-row').length > 1) {
            $(btn).closest('tr').remove();
            recalcTotals();
        }
    }

    $(document).on('input', '.qty, .cost', function () {
        const row = $(this).closest('tr');
        const qty = parseFloat(row.find('.qty').val()) || 0;
        const cost = parseFloat(row.find('.cost').val()) || 0;
        row.find('.subtotal-display').text((qty * cost).toFixed(2));
        recalcTotals();
    });

    function recalcTotals() {
        let total = 0;
        $('.subtotal-display').each(function () {
            total += parseFloat($(this).text()) || 0;
        });
        $('#grandTotal').text(total.toFixed(2));
        const paid = parseFloat($('#paid').val()) || 0;
        $('#dueDisplay').text(Math.max(0, total - paid).toFixed(2));
    }

    $('#paid').on('input', recalcTotals);

    function editPurchase(id) {
        $.get('{{ url("purchases") }}/' + id, function (data) {
            $('#modalTitle').html('<i class="bi bi-pencil me-2"></i>Edit Purchase');
            $('#methodField').val('PUT');
            $('#purchaseForm').attr('action', '{{ url("purchases") }}/' + id);
            $('#purchaseId').val(data.id);
            $('#supplierId').val(data.supplier_id);
            $('#date').val(data.date.substring(0, 10));
            $('#referenceNo').val(data.reference_no);
            $('#status').val(data.status);
            $('#paid').val(data.paid);
            $('#productRows').empty();
            rowIndex = 0;
            if (data.products && data.products.length) {
                data.products.forEach(function (p, i) {
                    const idx = i;
                    const html = `
                        <tr class="product-row">
                            <td><select name="products[${idx}][product_id]" class="form-select product-select" required><option value="">Select Product</option>@foreach($products ?? [] as $product)<option value="{{ $product->id }}">${p.product ? p.product.name : 'Product'}</option>@endforeach</select></td>
                            <td><input type="number" name="products[${idx}][quantity]" class="form-control qty" min="1" value="${p.quantity}" required></td>
                            <td><input type="number" step="0.01" name="products[${idx}][cost]" class="form-control cost" min="0" value="${p.cost}" required></td>
                            <td><span class="subtotal-display">${(p.quantity * p.cost).toFixed(2)}</span></td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger remove-row" onclick="removeRow(this)"><i class="bi bi-x-lg"></i></button></td>
                        </tr>`;
                    $('#productRows').append(html);
                    rowIndex = idx + 1;
                });
            }
            recalcTotals();
            $('#purchaseModal').modal('show');
        });
    }

    function viewPurchase(id) {
        $('#viewContent').html('<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>');
        $('#viewModal').modal('show');
        $.get('{{ url("purchases") }}/' + id, function (data) {
            let ps = 'Unpaid';
            let cls = 'danger';
            const due = data.grand_total - data.paid;
            if (due <= 0) { ps = 'Paid'; cls = 'success'; }
            else if (due < data.grand_total) { ps = 'Partial'; cls = 'warning text-dark'; }

            let productsHtml = '';
            if (data.products && data.products.length) {
                data.products.forEach(function (p) {
                    productsHtml += `<tr><td>${p.product ? p.product.name : '—'}</td><td>${p.quantity}</td><td class="text-end">${parseFloat(p.cost).toFixed(2)}</td><td class="text-end">${(p.quantity * p.cost).toFixed(2)}</td></tr>`;
                });
            }

            let html = `
                <div class="row g-3 mb-3">
                    <div class="col-md-4"><strong>Reference:</strong><br><code>${data.reference_no}</code></div>
                    <div class="col-md-4"><strong>Supplier:</strong><br>${data.supplier ? data.supplier.name : '—'}</div>
                    <div class="col-md-4"><strong>Date:</strong><br>${data.date ? data.date.substring(0,10) : '—'}</div>
                </div>
                <h6 class="fw-semibold mb-2">Products</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr><th>Product</th><th>Qty</th><th class="text-end">Cost</th><th class="text-end">Total</th></tr>
                        </thead>
                        <tbody>${productsHtml}</tbody>
                        <tfoot>
                            <tr class="fw-semibold"><td colspan="3" class="text-end">Grand Total</td><td class="text-end">${parseFloat(data.grand_total).toFixed(2)}</td></tr>
                            <tr><td colspan="3" class="text-end">Paid</td><td class="text-end">${parseFloat(data.paid).toFixed(2)}</td></tr>
                            <tr class="text-${cls}"><td colspan="3" class="text-end">Due</td><td class="text-end">${due.toFixed(2)}</td></tr>
                            <tr><td colspan="3" class="text-end">Payment Status</td><td class="text-end"><span class="badge bg-${cls}">${ps}</span></td></tr>
                        </tfoot>
                    </table>
                </div>`;
            $('#viewContent').html(html);
        }).fail(function () {
            $('#viewContent').html('<div class="alert alert-danger mb-0">Failed to load purchase details.</div>');
        });
    }

    function confirmDelete(id, name) {
        $('#deleteItemName').text(name);
        $('#deleteForm').attr('action', '{{ url("purchases") }}/' + id);
        $('#deleteModal').modal('show');
    }
</script>
@endpush

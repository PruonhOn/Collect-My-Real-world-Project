@extends('layouts.app')

@section('title', 'Products')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0"><i class="bi bi-box-seam me-2 text-primary"></i>Products</h4>
            <p class="text-muted small mb-0">Manage your product catalog</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal">
            <i class="bi bi-plus-lg me-1"></i>Add Product
        </button>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small">Category</label>
                    <select class="form-select form-select-sm" id="filterCategory">
                        <option value="">All Categories</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Status</label>
                    <select class="form-select form-select-sm" id="filterStatus">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="discontinued">Discontinued</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Stock Status</label>
                    <select class="form-select form-select-sm" id="filterStock">
                        <option value="">All</option>
                        <option value="low">Low Stock</option>
                        <option value="out">Out of Stock</option>
                        <option value="in">In Stock</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-sm btn-outline-primary flex-fill" id="applyFilters"><i class="bi bi-funnel me-1"></i>Filter</button>
                    <button class="btn btn-sm btn-outline-secondary" id="resetFilters"><i class="bi bi-arrow-counterclockwise"></i></button>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="productsTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width:60px;">Image</th>
                            <th>Name</th>
                            <th>SKU</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th style="width:160px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Loading products...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="productForm">
                <input type="hidden" name="id" id="productId">
                <div class="modal-header">
                    <h5 class="modal-title" id="productModalLabel"><i class="bi bi-box-seam me-2 text-primary"></i>Add Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Product Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">SKU <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="sku" required>
                                <button class="btn btn-outline-secondary" type="button" id="generateSku"><i class="bi bi-arrow-repeat"></i></button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select" name="category_id" required>
                                <option value="">Select Category</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Supplier</label>
                            <select class="form-select" name="supplier_id">
                                <option value="">Select Supplier</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Unit</label>
                            <select class="form-select" name="unit">
                                <option value="pcs">Pieces (pcs)</option>
                                <option value="kg">Kilogram (kg)</option>
                                <option value="g">Gram (g)</option>
                                <option value="l">Liter (l)</option>
                                <option value="ml">Milliliter (ml)</option>
                                <option value="box">Box</option>
                                <option value="pack">Pack</option>
                                <option value="dozen">Dozen</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Purchase Price <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" min="0" class="form-control" name="purchase_price" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Selling Price <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" min="0" class="form-control" name="selling_price" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Wholesale Price</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" min="0" class="form-control" name="wholesale_price">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Stock Quantity <span class="text-danger">*</span></label>
                            <input type="number" min="0" class="form-control" name="stock_quantity" value="0" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Min Stock Level</label>
                            <input type="number" min="0" class="form-control" name="min_stock" value="5">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Max Stock Level</label>
                            <input type="number" min="0" class="form-control" name="max_stock" value="100">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tax (%)</label>
                            <input type="number" step="0.01" min="0" max="100" class="form-control" name="tax" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="discontinued">Discontinued</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Barcode</label>
                            <input type="text" class="form-control" name="barcode" placeholder="UPC/EAN/Code128">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Product Image</label>
                            <input type="file" class="form-control" name="image" accept="image/*">
                            <div id="imagePreview" class="mt-2 d-none">
                                <img src="" alt="Preview" class="img-thumbnail" style="max-height:100px;">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="productFormSubmit"><i class="bi bi-check-lg me-1"></i>Save Product</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let table = $('#productsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("products.data") }}',
            data: function(d) {
                d.category = $('#filterCategory').val();
                d.status = $('#filterStatus').val();
                d.stock_status = $('#filterStock').val();
            }
        },
        columns: [
            {
                data: 'image',
                render: function(data) {
                    return data ? '<img src="' + data + '" class="rounded" style="width:40px;height:40px;object-fit:cover;">' : '<div class="rounded bg-light d-flex align-items-center justify-content-center" style="width:40px;height:40px;"><i class="bi bi-image text-muted"></i></div>';
                }
            },
            { data: 'name' },
            { data: 'sku' },
            { data: 'category', render: d => d ? d.name : '-' },
            { data: 'selling_price', render: d => '$' + parseFloat(d).toFixed(2) },
            {
                data: 'stock_quantity',
                render: function(d, t, r) {
                    const cls = d <= 0 ? 'danger' : (d <= (r.min_stock || 5) ? 'warning' : 'success');
                    return '<span class="badge bg-' + cls + '">' + d + '</span>';
                }
            },
            {
                data: 'status',
                render: function(d) {
                    const map = { active: 'success', inactive: 'secondary', discontinued: 'danger' };
                    return '<span class="badge bg-' + (map[d] || 'secondary') + '">' + d + '</span>';
                }
            },
            {
                data: 'id',
                orderable: false,
                render: function(d) {
                    return `
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary edit-product" data-id="${d}"><i class="bi bi-pencil"></i></button>
                            <button class="btn btn-outline-info barcode-product" data-id="${d}"><i class="bi bi-upc-scan"></i></button>
                            <button class="btn btn-outline-danger delete-product" data-id="${d}"><i class="bi bi-trash3"></i></button>
                        </div>
                    `;
                }
            }
        ],
        order: [[1, 'asc']],
        pageLength: 25
    });

    $('#applyFilters').on('click', function() { table.draw(); });
    $('#resetFilters').on('click', function() {
        $('#filterCategory, #filterStatus, #filterStock').val('');
        table.draw();
    });

    $('#generateSku').on('click', function() {
        const ts = Date.now().toString(36).toUpperCase();
        $('input[name="sku"]').val('SKU-' + ts);
    });

    $('#productForm').on('submit', function(e) {
        e.preventDefault();
        const id = $('#productId').val();
        const url = id ? '{{ route("products.update", "") }}/' + id : '{{ route("products.store") }}';
        const method = id ? 'PUT' : 'POST';
        const formData = new FormData(this);
        if (id) formData.append('_method', 'PUT');
        $.ajax({
            url: url,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function() {
                $('#productModal').modal('hide');
                table.draw();
                toastr.success('Product saved successfully');
                $('#productForm')[0].reset();
                $('#productId').val('');
                $('#imagePreview').addClass('d-none');
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error saving product');
            }
        });
    });

    $(document).on('click', '.edit-product', function() {
        const id = $(this).data('id');
        $.get('{{ route("products.show", "") }}/' + id, function(data) {
            $('#productId').val(data.id);
            $('#productModalLabel').text('Edit Product');
            $('input[name="name"]').val(data.name);
            $('input[name="sku"]').val(data.sku);
            $('select[name="category_id"]').val(data.category_id);
            $('select[name="supplier_id"]').val(data.supplier_id);
            $('select[name="unit"]').val(data.unit);
            $('input[name="purchase_price"]').val(data.purchase_price);
            $('input[name="selling_price"]').val(data.selling_price);
            $('input[name="wholesale_price"]').val(data.wholesale_price);
            $('input[name="stock_quantity"]').val(data.stock_quantity);
            $('input[name="min_stock"]').val(data.min_stock);
            $('input[name="max_stock"]').val(data.max_stock);
            $('input[name="tax"]').val(data.tax);
            $('select[name="status"]').val(data.status);
            $('input[name="barcode"]').val(data.barcode);
            $('textarea[name="description"]').val(data.description);
            if (data.image) {
                $('#imagePreview img').attr('src', data.image);
                $('#imagePreview').removeClass('d-none');
            }
            $('#productModal').modal('show');
        });
    });

    $('#productModal').on('hidden.bs.modal', function() {
        $('#productId').val('');
        $('#productModalLabel').text('Add Product');
        $('#productForm')[0].reset();
        $('#imagePreview').addClass('d-none');
    });

    $(document).on('click', '.delete-product', function() {
        const id = $(this).data('id');
        if (!confirm('Delete this product?')) return;
        $.ajax({
            url: '{{ route("products.destroy", "") }}/' + id,
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function() { table.draw(); toastr.success('Product deleted'); },
            error: function(xhr) { toastr.error(xhr.responseJSON?.message || 'Error deleting'); }
        });
    });

    $(document).on('click', '.barcode-product', function() {
        const id = $(this).data('id');
        window.open('{{ route("products.barcode", "") }}/' + id, '_blank', 'width=400,height=300');
    });
});
</script>
@endpush

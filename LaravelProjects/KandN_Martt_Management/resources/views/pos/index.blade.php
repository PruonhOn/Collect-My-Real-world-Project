@extends('layouts.app')

@section('title', 'POS - Point of Sale')

@push('styles')
<style>
    .pos-wrapper { height: calc(100vh - 120px); }
    .product-grid { height: 100%; overflow-y: auto; }
    .product-grid .product-card { cursor: pointer; transition: all 0.15s; }
    .product-grid .product-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    .product-grid .product-card .card-img-top { height: 100px; object-fit: contain; padding: 8px; }
    .cart-sidebar { height: 100%; display: flex; flex-direction: column; }
    .cart-items { flex: 1; overflow-y: auto; }
    .cart-item { padding: 10px 0; border-bottom: 1px solid #eee; }
    .cart-item:last-child { border-bottom: none; }
    .cart-summary { border-top: 2px solid #dee2e6; padding-top: 12px; }
    .qty-btn { width: 28px; height: 28px; padding: 0; display: inline-flex; align-items: center; justify-content: center; }
    .barcode-input { font-family: monospace; font-size: 1.1rem; letter-spacing: 2px; }
    .payment-section { background: #f8f9fa; border-radius: 8px; padding: 16px; }
    .category-filter .btn { font-size: 0.8rem; }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0"><i class="bi bi-credit-card me-2 text-primary"></i>Point of Sale</h4>
        <div>
            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#customerModal">
                <i class="bi bi-person me-1"></i>Customer
                <span class="badge bg-primary ms-1" id="selectedCustomer">Walk-in</span>
            </button>
            <button class="btn btn-sm btn-outline-info ms-1" id="holdSaleBtn">
                <i class="bi bi-pause-circle me-1"></i>Hold
            </button>
        </div>
    </div>

    <div class="row g-3 pos-wrapper">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-2">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-5">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control" id="productSearch" placeholder="Search products...">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white"><i class="bi bi-upc-scan"></i></span>
                                <input type="text" class="form-control barcode-input" id="barcodeInput" placeholder="Scan barcode...">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" id="categoryFilter">
                                <option value="">All Categories</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-body product-grid">
                    <div class="row g-2" id="productGrid">
                        <div class="col-12 text-center text-muted py-5">
                            <i class="bi bi-box-seam fs-1 d-block mb-2"></i>
                            <span>Search or scan a product to begin</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100 cart-sidebar">
                <div class="card-header bg-white border-bottom py-2">
                    <h6 class="fw-bold mb-0">
                        <i class="bi bi-cart me-2 text-primary"></i>Cart
                        <span class="badge bg-secondary float-end" id="cartCount">0</span>
                    </h6>
                </div>
                <div class="card-body cart-items" id="cartItems">
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-cart-x fs-1 d-block mb-2"></i>
                        <span>Cart is empty</span>
                    </div>
                </div>
                <div class="card-footer bg-white">
                    <div class="cart-summary">
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-muted">Subtotal</small>
                            <small class="fw-bold" id="cartSubtotal">$0.00</small>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-muted">Discount</small>
                            <small class="text-danger" id="cartDiscount">-$0.00</small>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-muted">Tax (10%)</small>
                            <small class="" id="cartTax">$0.00</small>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between">
                            <strong>Total</strong>
                            <strong class="fs-5 text-primary" id="cartTotal">$0.00</strong>
                        </div>
                    </div>

                    <div class="mt-3 payment-section">
                        <div class="row g-2">
                            <div class="col-7">
                                <select class="form-select form-select-sm" id="paymentMethod">
                                    <option value="cash">Cash</option>
                                    <option value="card">Card</option>
                                    <option value="mobile">Mobile Payment</option>
                                    <option value="credit">Credit</option>
                                </select>
                            </div>
                            <div class="col-5">
                                <input type="number" class="form-control form-control-sm" id="amountPaid" placeholder="Amount" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <small class="text-muted">Change Due:</small>
                            <small class="fw-bold text-success" id="changeDisplay">$0.00</small>
                        </div>
                    </div>

                    <div class="d-grid gap-2 mt-3">
                        <button class="btn btn-primary" id="payBtn">
                            <i class="bi bi-check-circle me-1"></i>Pay
                        </button>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-secondary flex-fill" id="holdBtn">
                                <i class="bi bi-pause me-1"></i>Hold
                            </button>
                            <button class="btn btn-outline-danger flex-fill" id="cancelBtn">
                                <i class="bi bi-x-circle me-1"></i>Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="customerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person me-2"></i>Select Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="text" class="form-control" id="customerSearchInput" placeholder="Search by name or phone...">
                </div>
                <div class="list-group" id="customerList">
                    <div class="text-center text-muted py-3">Type to search customers</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Skip</button>
                <button type="button" class="btn btn-primary" id="addWalkinCustomer" data-bs-dismiss="modal">Walk-in Customer</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="receiptModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">Sale Complete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <i class="bi bi-check-circle-fill text-success fs-1 mb-3 d-block"></i>
                <h5>Payment Successful!</h5>
                <p class="text-muted small" id="receiptTotal">Total: $0.00</p>
            </div>
            <div class="modal-footer justify-content-center border-0">
                <button class="btn btn-primary" id="printReceiptBtn">
                    <i class="bi bi-printer me-1"></i>Print Receipt
                </button>
                <button class="btn btn-outline-secondary" data-bs-dismiss="modal">New Sale</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let cart = [];
    let selectedCustomer = { id: null, name: 'Walk-in' };

    $('#productSearch, #barcodeInput').on('keyup', function() {
        filterProducts();
    });

    $('#categoryFilter').on('change', function() {
        filterProducts();
    });

    function filterProducts() {
        const search = $('#productSearch').val();
        const category = $('#categoryFilter').val();
        const barcode = $('#barcodeInput').val();
        $.ajax({
            url: '{{ route("pos.products") }}',
            method: 'GET',
            data: { search, category, barcode },
            success: function(response) {
                renderProducts(response);
            }
        });
    }

    function renderProducts(products) {
        const grid = $('#productGrid');
        grid.empty();
        if (!products || products.length === 0) {
            grid.html('<div class="col-12 text-center text-muted py-5"><i class="bi bi-box-seam fs-1 d-block mb-2"></i><span>No products found</span></div>');
            return;
        }
        products.forEach(function(p) {
            grid.append(`
                <div class="col-md-4 col-sm-6">
                    <div class="card product-card h-100 border" onclick="addToCart(${p.id}, '${p.name.replace(/'/g, "\\'")}', '${p.sku}', ${p.price}, ${p.stock ?? 0})">
                        <img src="${p.image || 'https://placehold.co/150x150?text=No+Image'}" class="card-img-top" alt="${p.name}">
                        <div class="card-body p-2 text-center">
                            <small class="fw-bold d-block text-truncate">${p.name}</small>
                            <small class="text-muted">$${parseFloat(p.price).toFixed(2)}</small>
                            <small class="d-block ${p.stock > 0 ? 'text-success' : 'text-danger'}">${p.stock > 0 ? p.stock + ' in stock' : 'Out of stock'}</small>
                        </div>
                    </div>
                </div>
            `);
        });
    }

    window.addToCart = function(id, name, sku, price, stock) {
        if (stock <= 0) { toastr.error('Product is out of stock'); return; }
        const existing = cart.find(c => c.id === id);
        if (existing) {
            if (existing.qty >= stock) { toastr.warning('Not enough stock'); return; }
            existing.qty++;
        } else {
            cart.push({ id, name, sku, price: parseFloat(price), qty: 1, stock });
        }
        renderCart();
    };

    function renderCart() {
        const container = $('#cartItems');
        const count = cart.reduce((s, i) => s + i.qty, 0);
        $('#cartCount').text(count);
        if (cart.length === 0) {
            container.html('<div class="text-center text-muted py-5"><i class="bi bi-cart-x fs-1 d-block mb-2"></i><span>Cart is empty</span></div>');
            updateSummary();
            return;
        }
        let html = '';
        cart.forEach(function(item, idx) {
            html += `
                <div class="cart-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1 me-2">
                            <small class="fw-bold d-block">${item.name}</small>
                            <small class="text-muted">$${item.price.toFixed(2)}</small>
                        </div>
                        <div class="text-end">
                            <small class="fw-bold d-block">$${(item.price * item.qty).toFixed(2)}</small>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-1">
                        <div>
                            <button class="btn btn-sm btn-outline-secondary qty-btn" onclick="changeQty(${idx}, -1)"><i class="bi bi-dash"></i></button>
                            <span class="mx-2 fw-bold">${item.qty}</span>
                            <button class="btn btn-sm btn-outline-secondary qty-btn" onclick="changeQty(${idx}, 1)"><i class="bi bi-plus"></i></button>
                        </div>
                        <button class="btn btn-sm btn-outline-danger" onclick="removeFromCart(${idx})"><i class="bi bi-trash3"></i></button>
                    </div>
                </div>
            `;
        });
        container.html(html);
        updateSummary();
    }

    window.changeQty = function(idx, delta) {
        const item = cart[idx];
        const newQty = item.qty + delta;
        if (newQty <= 0) { removeFromCart(idx); return; }
        if (newQty > item.stock) { toastr.warning('Not enough stock'); return; }
        item.qty = newQty;
        renderCart();
    };

    window.removeFromCart = function(idx) {
        cart.splice(idx, 1);
        renderCart();
    };

    function updateSummary() {
        const subtotal = cart.reduce((s, i) => s + i.price * i.qty, 0);
        const discount = 0;
        const tax = subtotal * 0.10;
        const total = subtotal + tax - discount;
        $('#cartSubtotal').text('$' + subtotal.toFixed(2));
        $('#cartDiscount').text('-' + '$' + discount.toFixed(2));
        $('#cartTax').text('$' + tax.toFixed(2));
        $('#cartTotal').text('$' + total.toFixed(2));
    }

    $('#amountPaid').on('input', function() {
        const total = parseFloat($('#cartTotal').text().replace('$', '')) || 0;
        const paid = parseFloat($(this).val()) || 0;
        const change = Math.max(0, paid - total);
        $('#changeDisplay').text('$' + change.toFixed(2));
    });

    $('#payBtn').on('click', function() {
        const total = parseFloat($('#cartTotal').text().replace('$', '')) || 0;
        const paid = parseFloat($('#amountPaid').val()) || 0;
        if (cart.length === 0) { toastr.warning('Cart is empty'); return; }
        if (paid < total) { toastr.error('Insufficient amount'); return; }
        const method = $('#paymentMethod').val();
        const data = {
            items: cart,
            payment_method: method,
            amount_paid: paid,
            customer_id: selectedCustomer.id,
            _token: '{{ csrf_token() }}'
        };
        $.ajax({
            url: '{{ route("pos.store") }}',
            method: 'POST',
            data: data,
            success: function(resp) {
                $('#receiptTotal').text('Total: $' + total.toFixed(2));
                $('#receiptModal').modal('show');
                cart = [];
                renderCart();
                $('#amountPaid').val('');
                $('#changeDisplay').text('$0.00');
                filterProducts();
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Payment failed');
            }
        });
    });

    $('#holdBtn, #holdSaleBtn').on('click', function() {
        if (cart.length === 0) { toastr.warning('Cart is empty'); return; }
        toastr.success('Sale held successfully');
        cart = [];
        renderCart();
        $('#amountPaid').val('');
        $('#changeDisplay').text('$0.00');
    });

    $('#cancelBtn').on('click', function() {
        if (cart.length === 0) return;
        if (confirm('Clear the entire cart?')) {
            cart = [];
            renderCart();
            $('#amountPaid').val('');
            $('#changeDisplay').text('$0.00');
            toastr.info('Cart cleared');
        }
    });

    $('#customerSearchInput').on('keyup', function() {
        const q = $(this).val();
        if (q.length < 1) { $('#customerList').html('<div class="text-center text-muted py-3">Type to search customers</div>'); return; }
        $.ajax({
            url: '{{ route("customers.search") }}',
            method: 'GET',
            data: { q },
            success: function(resp) {
                let html = '';
                resp.forEach(function(c) {
                    html += `<a href="#" class="list-group-item list-group-item-action customer-select" data-id="${c.id}" data-name="${c.name}"><strong>${c.name}</strong><small class="text-muted d-block">${c.phone ?? ''}</small></a>`;
                });
                $('#customerList').html(html || '<div class="text-muted text-center py-2">No customers found</div>');
            }
        });
    });

    $(document).on('click', '.customer-select', function(e) {
        e.preventDefault();
        selectedCustomer = { id: $(this).data('id'), name: $(this).data('name') };
        $('#selectedCustomer').text(selectedCustomer.name);
        $('#customerModal').modal('hide');
        toastr.success('Customer selected: ' + selectedCustomer.name);
    });

    $('#addWalkinCustomer').on('click', function() {
        selectedCustomer = { id: null, name: 'Walk-in' };
        $('#selectedCustomer').text('Walk-in');
    });

    $('#printReceiptBtn').on('click', function() {
        window.print();
        $('#receiptModal').modal('hide');
    });

    filterProducts();
});
</script>
@endpush

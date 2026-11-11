@extends('layouts.app')

@section('title', 'POS')

@push('styles')
<style>
    .pos-product-card {
        cursor: pointer;
        transition: all 0.2s;
        border: 2px solid transparent;
    }
    .pos-product-card:hover {
        border-color: var(--bs-primary);
        transform: scale(1.02);
    }
    .pos-product-card .card-img-top {
        height: 100px;
        object-fit: cover;
    }
    #barcodeInput {
        font-size: 1.2rem;
        letter-spacing: 2px;
        font-weight: 600;
    }
    .cart-qty {
        width: 60px;
        text-align: center;
    }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="fas fa-shopping-cart me-2"></i>Point of Sale</h4>
</div>

<div class="row g-3">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header">
                <div class="row g-2">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                            <input type="text" id="barcodeInput" class="form-control" placeholder="Scan or type barcode...">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" id="productSearch" class="form-control" placeholder="Search products...">
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-2 pos-products" id="productGrid">
                    @foreach(\App\Models\Product::with('category')->where('quantity', '>', 0)->get() as $product)
                    <div class="col-md-4 col-6 product-item" data-name="{{ strtolower($product->name) }}" data-barcode="{{ $product->barcode }}">
                        <div class="card pos-product-card" onclick="addToCart({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $product->selling_price }}, {{ $product->quantity }})">
                            <div class="card-body text-center p-2">
                                @if($product->image)
                                    <img src="{{ asset('storage/' . $product->image) }}" class="card-img-top" alt="">
                                @else
                                    <div style="height: 100px;" class="d-flex align-items-center justify-content-center bg-light rounded mb-2">
                                        <i class="fas fa-box fa-2x text-muted"></i>
                                    </div>
                                @endif
                                <h6 class="mb-0 small">{{ $product->name }}</h6>
                                <small class="text-muted">${{ number_format($product->selling_price, 2) }}</small>
                                <br>
                                <small class="text-muted">Stock: {{ $product->quantity }}</small>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fas fa-shopping-basket me-2"></i>Cart</h6>
                <button class="btn btn-sm btn-danger" onclick="clearCart()">
                    <i class="fas fa-trash me-1"></i>Clear All
                </button>
            </div>
            <div class="card-body">
                <div class="pos-cart" id="cartItems">
                    <div class="text-center text-muted py-5" id="emptyCart">
                        <i class="fas fa-cart-plus fa-3x mb-3 d-block"></i>
                        <p>Cart is empty. Scan or search products.</p>
                    </div>
                    <table class="table table-sm d-none" id="cartTable">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Qty</th>
                                <th>Price</th>
                                <th>Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="cartBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between mb-2">
                    <span class="fw-bold">Total:</span>
                    <h4 class="fw-bold text-primary mb-0" id="cartTotal">$0.00</h4>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" id="paidAmount" class="form-control" placeholder="Amount paid" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <select id="paymentMethod" class="form-select">
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Change:</span>
                    <span class="fw-bold" id="changeAmount">$0.00</span>
                </div>
                <button class="btn btn-success w-100 btn-lg" id="checkoutBtn" disabled onclick="checkout()">
                    <i class="fas fa-check-circle me-2"></i>Checkout
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let cart = [];

    $('#barcodeInput').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            const barcode = $(this).val().trim();
            if (barcode) {
                $.get('{{ route("products.search.barcode") }}', { barcode: barcode }, function(product) {
                    if (product) {
                        addToCart(product.id, product.name, product.selling_price, product.quantity);
                        $('#barcodeInput').val('');
                    } else {
                        Swal.fire('Not Found', 'No product found with this barcode.', 'error');
                    }
                });
            }
        }
    });

    $('#productSearch').on('keyup', function() {
        const query = $(this).val().toLowerCase();
        $('.product-item').each(function() {
            const name = $(this).data('name');
            const barcode = $(this).data('barcode').toString();
            $(this).toggle(name.includes(query) || barcode.includes(query));
        });
    });

    $('#paidAmount').on('keyup change', function() {
        updateChange();
    });

    function addToCart(id, name, price, stock) {
        const existing = cart.find(c => c.product_id === id);
        if (existing) {
            if (existing.quantity >= stock) {
                Swal.fire('Limit Reached', 'Not enough stock available.', 'warning');
                return;
            }
            existing.quantity++;
        } else {
            cart.push({ product_id: id, name: name, price: price, quantity: 1, stock: stock });
        }
        renderCart();
    }

    function removeFromCart(index) {
        cart.splice(index, 1);
        renderCart();
    }

    function updateQty(index, qty) {
        qty = parseInt(qty);
        if (qty < 1) {
            removeFromCart(index);
            return;
        }
        if (qty > cart[index].stock) {
            Swal.fire('Limit Reached', 'Not enough stock available.', 'warning');
            return;
        }
        cart[index].quantity = qty;
        renderCart();
    }

    function clearCart() {
        cart = [];
        renderCart();
    }

    function renderCart() {
        const tbody = $('#cartBody');
        const empty = $('#emptyCart');
        const table = $('#cartTable');
        const total = $('#cartTotal');
        const checkoutBtn = $('#checkoutBtn');

        tbody.empty();

        if (cart.length === 0) {
            empty.removeClass('d-none');
            table.addClass('d-none');
            total.text('$0.00');
            checkoutBtn.prop('disabled', true);
            updateChange();
            return;
        }

        empty.addClass('d-none');
        table.removeClass('d-none');
        checkoutBtn.prop('disabled', false);

        let grandTotal = 0;
        $.each(cart, function(i, item) {
            const lineTotal = item.quantity * item.price;
            grandTotal += lineTotal;
            tbody.append(`
                <tr>
                    <td class="small">${item.name}</td>
                    <td>
                        <input type="number" class="form-control form-control-sm cart-qty" value="${item.quantity}" min="1" max="${item.stock}"
                            onchange="updateQty(${i}, this.value)">
                    </td>
                    <td>$${item.price.toFixed(2)}</td>
                    <td>$${lineTotal.toFixed(2)}</td>
                    <td>
                        <button class="btn btn-sm btn-danger" onclick="removeFromCart(${i})">
                            <i class="fas fa-times"></i>
                        </button>
                    </td>
                </tr>
            `);
        });

        total.text('$' + grandTotal.toFixed(2));
        updateChange();
    }

    function updateChange() {
        const total = parseFloat($('#cartTotal').text().replace('$', '')) || 0;
        const paid = parseFloat($('#paidAmount').val()) || 0;
        const change = paid - total;
        $('#changeAmount').text('$' + (change > 0 ? change.toFixed(2) : '0.00'));
    }

    function checkout() {
        const paidAmount = parseFloat($('#paidAmount').val());
        const total = parseFloat($('#cartTotal').text().replace('$', ''));
        const paymentMethod = $('#paymentMethod').val();

        if (!paidAmount || paidAmount < total) {
            Swal.fire('Invalid Amount', 'Paid amount must be at least $' + total.toFixed(2), 'error');
            return;
        }

        const cartData = cart.map(c => ({
            product_id: c.product_id,
            quantity: c.quantity
        }));

        Swal.fire({
            title: 'Confirm Sale?',
            html: `Total: <strong>$${total.toFixed(2)}</strong><br>
                   Paid: <strong>$${paidAmount.toFixed(2)}</strong><br>
                   Change: <strong>$${(paidAmount - total).toFixed(2)}</strong>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Complete Sale',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("sales.checkout") }}',
                    method: 'POST',
                    data: {
                        cart: JSON.stringify(cartData),
                        paid_amount: paidAmount,
                        payment_method: paymentMethod
                    },
                    success: function(res) {
                        if (res.success) {
                            Swal.fire({
                                title: 'Sale Complete!',
                                text: 'Invoice: ' + res.invoice_number,
                                icon: 'success',
                                showCancelButton: true,
                                confirmButtonText: 'Print Receipt',
                                cancelButtonText: 'Done'
                            }).then((action) => {
                                if (action.isConfirmed) {
                                    window.open('{{ url("sales") }}/' + res.sale_id + '/receipt', '_blank');
                                }
                                cart = [];
                                renderCart();
                            });
                        }
                    },
                    error: function(xhr) {
                        const msg = xhr.responseJSON?.message || 'Something went wrong.';
                        Swal.fire('Error', msg, 'error');
                    }
                });
            }
        });
    }
</script>
@endpush

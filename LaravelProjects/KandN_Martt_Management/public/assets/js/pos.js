$(document).ready(function () {
    // Initialize POS
    loadProducts('');

    // Search products
    var searchTimer;
    $('#posSearch').on('input', function () {
        clearTimeout(searchTimer);
        var query = $(this).val();
        searchTimer = setTimeout(function () {
            loadProducts(query);
        }, 300);
    });

    // Category filter
    $('#categoryFilter').on('change', function () {
        loadProducts($('#posSearch').val(), $(this).val());
    });

    // Quantity controls
    $(document).on('click', '.qty-minus', function () {
        var input = $(this).siblings('.cart-qty');
        var val = parseFloat(input.val()) || 0;
        if (val > 0.01) {
            input.val((val - 1).toFixed(input.data('step') === 'any' ? 2 : 0));
            input.trigger('change');
        }
    });

    $(document).on('click', '.qty-plus', function () {
        var input = $(this).siblings('.cart-qty');
        var val = parseFloat(input.val()) || 0;
        input.val((val + 1).toFixed(input.data('step') === 'any' ? 2 : 0));
        input.trigger('change');
    });

    // Checkout
    $('#checkoutBtn').on('click', function () {
        processCheckout();
    });

    // Hold sale
    $('#holdBtn').on('click', function () {
        if (confirm('Hold this sale?')) {
            clearCart();
            toastr.success('Sale held successfully');
        }
    });

    // Cancel sale
    $('#cancelBtn').on('click', function () {
        if (confirm('Cancel this sale?')) {
            clearCart();
            toastr.info('Sale cancelled');
        }
    });
});

function loadProducts(query, categoryId) {
    var url = '/api/pos/search?q=' + encodeURIComponent(query || '');
    if (categoryId) {
        url += '&category_id=' + categoryId;
    }

    $.ajax({
        url: url,
        method: 'GET',
        headers: { 'Authorization': 'Bearer ' + localStorage.getItem('token') },
        beforeSend: function () {
            $('.pos-product-grid').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>');
        },
        success: function (res) {
            if (res.success && res.data) {
                var html = '';
                res.data.forEach(function (product) {
                    html += `
                        <div class="pos-product-item pos-add-item" data-id="${product.id}" data-name="${product.name}" data-price="${product.selling_price}">
                            <div class="product-image mb-2">
                                <i class="bi bi-box-seam" style="font-size:32px;color:#4e73df;"></i>
                            </div>
                            <div class="product-name">${product.name}</div>
                            <div class="product-price">$${parseFloat(product.selling_price).toFixed(2)}</div>
                            <small class="text-muted">Stock: ${product.current_stock}</small>
                        </div>`;
                });
                if (res.data.length === 0) {
                    html = '<div class="text-center py-5 text-muted"><i class="bi bi-search" style="font-size:48px;"></i><p class="mt-2">No products found</p></div>';
                }
                $('.pos-product-grid').html(html);
            }
        },
        error: function () {
            $('.pos-product-grid').html('<div class="text-center py-5 text-danger">Error loading products</div>');
        }
    });
}

function processCheckout() {
    var items = [];
    $('.pos-cart-item').each(function () {
        items.push({
            product_id: $(this).data('product-id'),
            quantity: parseFloat($(this).find('.cart-qty').val()) || 1,
        });
    });

    if (items.length === 0) {
        toastr.warning('Cart is empty');
        return;
    }

    var data = {
        items: items,
        paid_amount: parseFloat($('#paidAmount').val()) || 0,
        payment_method: $('#paymentMethod').val() || 'cash',
    };

    var customerId = $('#customerSelect').val();
    if (customerId) {
        data.customer_id = customerId;
    }

    $('#checkoutBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Processing...');

    $.ajax({
        url: '/api/pos/checkout',
        method: 'POST',
        data: JSON.stringify(data),
        contentType: 'application/json',
        headers: { 'Authorization': 'Bearer ' + localStorage.getItem('token') },
        success: function (res) {
            if (res.success) {
                toastr.success('Sale completed successfully');
                clearCart();
                if (res.data && res.data.invoice_no) {
                    $('#receiptModal').modal('show');
                    $('#receiptContent').html(generateReceipt(res.data));
                }
            }
        },
        error: function (xhr) {
            var msg = 'Checkout failed';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            }
            toastr.error(msg);
        },
        complete: function () {
            $('#checkoutBtn').prop('disabled', false).html('<i class="bi bi-credit-card me-1"></i> Pay');
        }
    });
}

function clearCart() {
    $('.pos-cart-items').empty();
    updateCartTotal();
    $('#paidAmount').val('');
    $('#changeAmount').text('0.00');
    $('#cartDiscount').text('$0.00');
    $('#cartTax').text('$0.00');
}

function generateReceipt(sale) {
    var html = `
        <div style="font-family:monospace;font-size:12px;max-width:300px;margin:auto;">
            <div class="text-center mb-3">
                <h5 class="mb-1">K&N Mart</h5>
                <small>123 Main Street, City</small><br>
                <small>Phone: +1234567890</small>
                <hr>
            </div>
            <div class="mb-2">
                <div>Invoice: ${sale.invoice_no}</div>
                <div>Date: ${new Date(sale.sale_date).toLocaleString()}</div>
                <div>Cashier: ${sale.creator ? sale.creator.name : 'N/A'}</div>
                ${sale.customer ? '<div>Customer: ' + sale.customer.name + '</div>' : ''}
                <hr>
            </div>
            <table style="width:100%;">
                <thead>
                    <tr>
                        <th style="text-align:left;">Item</th>
                        <th style="text-align:center;">Qty</th>
                        <th style="text-align:right;">Price</th>
                        <th style="text-align:right;">Total</th>
                    </tr>
                </thead>
                <tbody>`;
    if (sale.details) {
        sale.details.forEach(function (item) {
            html += `
                <tr>
                    <td>${item.product ? item.product.name : 'Product'}</td>
                    <td style="text-align:center;">${item.quantity}</td>
                    <td style="text-align:right;">$${parseFloat(item.unit_price).toFixed(2)}</td>
                    <td style="text-align:right;">$${parseFloat(item.subtotal).toFixed(2)}</td>
                </tr>`;
        });
    }
    html += `
                </tbody>
            </table>
            <hr>
            <div style="display:flex;justify-content:space-between;">
                <span>Subtotal:</span><span>$${parseFloat(sale.subtotal).toFixed(2)}</span>
            </div>
            <div style="display:flex;justify-content:space-between;">
                <span>Discount:</span><span>$${parseFloat(sale.discount_amount).toFixed(2)}</span>
            </div>
            <div style="display:flex;justify-content:space-between;">
                <span>Tax:</span><span>$${parseFloat(sale.tax_amount).toFixed(2)}</span>
            </div>
            <div style="display:flex;justify-content:space-between;font-weight:bold;font-size:16px;border-top:2px dashed #000;padding-top:5px;">
                <span>Total:</span><span>$${parseFloat(sale.grand_total).toFixed(2)}</span>
            </div>
            <div style="display:flex;justify-content:space-between;">
                <span>Paid:</span><span>$${parseFloat(sale.paid_amount).toFixed(2)}</span>
            </div>
            <div style="display:flex;justify-content:space-between;">
                <span>Change:</span><span>$${parseFloat(sale.change_amount).toFixed(2)}</span>
            </div>
            <hr>
            <div class="text-center">
                <small>Payment: ${sale.payment_method.toUpperCase()}</small><br>
                <small>Thank you for shopping!</small>
            </div>
        </div>`;
    return html;
}

$(document).ready(function () {

    // Sidebar Toggle
    $('#sidebarToggle').on('click', function () {
        $('#sidebar').toggleClass('collapsed');
        $('.main-content').toggleClass('expanded');
    });

    // Auto-hide alerts
    setTimeout(function () {
        $('.alert').alert('close');
    }, 5000);

    // Initialize Select2
    $('.select2').each(function () {
        $(this).select2({
            theme: 'bootstrap-5',
            width: '100%',
            dropdownParent: $(this).closest('.modal') || 'body'
        });
    });

    // Initialize DataTables
    $('.datatable').each(function () {
        if (!$.fn.DataTable.isDataTable(this)) {
            $(this).DataTable({
                responsive: true,
                pageLength: 25,
                language: {
                    search: 'Search:',
                    lengthMenu: 'Show _MENU_ entries',
                    info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                    paginate: {
                        first: '<i class="bi bi-chevron-double-left"></i>',
                        previous: '<i class="bi bi-chevron-left"></i>',
                        next: '<i class="bi bi-chevron-right"></i>',
                        last: '<i class="bi bi-chevron-double-right"></i>'
                    }
                }
            });
        }
    });

    // Global search
    $('#globalSearch').on('keypress', function (e) {
        if (e.which === 13) {
            e.preventDefault();
            var query = $(this).val();
            if (query.length > 0) {
                window.location.href = '/products?search=' + encodeURIComponent(query);
            }
        }
    });

    // Delete confirmation
    $(document).on('click', '.btn-delete', function (e) {
        e.preventDefault();
        var form = $(this).closest('form');
        var name = $(this).data('name') || 'this item';

        if (confirm('Are you sure you want to delete ' + name + '?')) {
            form.submit();
        }
    });

    // POS: Add product to cart
    $(document).on('click', '.pos-add-item', function () {
        var id = $(this).data('id');
        var name = $(this).data('name');
        var price = $(this).data('price');
        addToCart(id, name, price);
    });

    // POS: Update quantity
    $(document).on('change', '.cart-qty', function () {
        updateCartTotal();
    });

    // POS: Remove item
    $(document).on('click', '.cart-remove', function () {
        $(this).closest('.pos-cart-item').remove();
        updateCartTotal();
    });

    // POS: Barcode scan
    var barcodeInput = $('#barcodeInput');
    if (barcodeInput.length) {
        var barcodeTimer;
        barcodeInput.on('keypress', function (e) {
            if (e.which === 13) {
                e.preventDefault();
                var barcode = $(this).val();
                if (barcode) {
                    scanBarcode(barcode);
                    $(this).val('');
                }
            }
        });
    }

    // POS: Calculate change
    $('#paidAmount').on('input', function () {
        var total = parseFloat($('#cartTotal').text()) || 0;
        var paid = parseFloat($(this).val()) || 0;
        var change = paid - total;
        $('#changeAmount').text(change >= 0 ? change.toFixed(2) : '0.00');
    });

    // POS: Payment method change
    $('#paymentMethod').on('change', function () {
        var method = $(this).val();
        if (method === 'cash') {
            $('#paidAmount').prop('readonly', false);
        } else {
            $('#paidAmount').prop('readonly', true).val($('#cartTotal').text());
            $('#changeAmount').text('0.00');
        }
    });

    // Load notifications
    loadNotifications();

});

function addToCart(id, name, price) {
    var existing = $('.pos-cart-item[data-product-id="' + id + '"]');
    if (existing.length) {
        var qtyInput = existing.find('.cart-qty');
        qtyInput.val(parseFloat(qtyInput.val()) + 1);
    } else {
        var item = `
            <div class="pos-cart-item" data-product-id="${id}">
                <div class="item-name">${name}</div>
                <div class="item-qty">
                    <button class="btn btn-sm btn-outline-secondary qty-minus">-</button>
                    <input type="number" class="form-control form-control-sm cart-qty" value="1" min="0.01" step="1">
                    <button class="btn btn-sm btn-outline-secondary qty-plus">+</button>
                </div>
                <div class="item-total">$${(price).toFixed(2)}</div>
                <button class="btn btn-sm btn-outline-danger cart-remove"><i class="bi bi-x"></i></button>
            </div>`;
        $('.pos-cart-items').append(item);
    }
    updateCartTotal();
    toastr.success(name + ' added to cart');
}

function updateCartTotal() {
    var subtotal = 0;
    $('.pos-cart-item').each(function () {
        var price = parseFloat($(this).find('.item-total').text().replace('$', '')) || 0;
        var qty = parseFloat($(this).find('.cart-qty').val()) || 0;
        var unitPrice = price > 0 && $(this).data('unit-price') ? parseFloat($(this).data('unit-price')) : price;
        var lineTotal = unitPrice * qty;
        $(this).find('.item-total').text('$' + lineTotal.toFixed(2));
        subtotal += lineTotal;
    });
    $('#cartSubtotal').text('$' + subtotal.toFixed(2));
    var discount = parseFloat($('#cartDiscount').text().replace('$', '')) || 0;
    var tax = parseFloat($('#cartTax').text().replace('$', '')) || 0;
    var total = subtotal - discount + tax;
    $('#cartTotal').text(total.toFixed(2));
}

function scanBarcode(barcode) {
    $.ajax({
        url: '/api/pos/scan',
        method: 'POST',
        data: { barcode: barcode },
        headers: { 'Authorization': 'Bearer ' + localStorage.getItem('token') },
        success: function (res) {
            if (res.success && res.data) {
                addToCart(res.data.id, res.data.name, res.data.selling_price);
            } else {
                toastr.error('Product not found');
            }
        },
        error: function () {
            toastr.error('Error scanning barcode');
        }
    });
}

function loadNotifications() {
    $.ajax({
        url: '/api/pos/low-stock',
        method: 'GET',
        headers: { 'Authorization': 'Bearer ' + localStorage.getItem('token') },
        success: function (res) {
            if (res.success && res.data && res.data.length > 0) {
                $('#notificationCount').text(res.data.length);
                var html = '';
                res.data.forEach(function (item) {
                    html += '<li><a class="dropdown-item" href="/inventory"><small class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>' + item.name + ' - Stock: ' + item.current_stock + '</small></a></li>';
                });
                $('.notification-dropdown .dropdown-header').after(html);
            }
        }
    });
}

function printReceipt(saleId) {
    var printWindow = window.open('/sales/' + saleId + '/receipt', '_blank', 'width=400,height=600');
    printWindow.focus();
}

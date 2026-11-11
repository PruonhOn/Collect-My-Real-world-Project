const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

const state = {
    cart: new Map(),
    search: '',
    category: '',
    loading: false,
    checkoutLocked: false,
};

const els = {
    grid: document.getElementById('posGrid'),
    loader: document.getElementById('posLoader'),
    count: document.getElementById('posResultsCount'),
    search: document.getElementById('posSearch'),
    cartItems: document.getElementById('cartItems'),
    emptyCart: document.getElementById('emptyCart'),
    clearCart: document.getElementById('clearCart'),
    checkoutBtn: document.getElementById('checkoutBtn'),
    customer: document.getElementById('cartCustomer'),
    discountType: document.getElementById('discountType'),
    discountValue: document.getElementById('discountValue'),
    paymentMethod: () => document.querySelector('input[name="payment_method"]:checked')?.value || 'cash',
    tendered: document.getElementById('amountTendered'),
    changeDue: document.getElementById('changeDue'),
    totals: {
        subtotal: document.getElementById('totSubtotal'),
        discount: document.getElementById('totDiscount'),
        tax: document.getElementById('totTax'),
        total: document.getElementById('totTotal'),
    },
};

function money(value) {
    return Number(value || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function renderCart() {
    const items = [...state.cart.values()];
    els.emptyCart.style.display = items.length ? 'none' : 'block';
    els.checkoutBtn.disabled = items.length === 0 || state.checkoutLocked;

    if (!items.length) {
        els.cartItems.innerHTML = '';
        updateTotals();
        return;
    }

    els.cartItems.innerHTML = items.map(item => `
        <div class="cart-line" data-variant="${item.variant_id}">
            ${item.image
                ? `<img src="${item.image}" class="cart-line-thumb" alt="">`
                : `<div class="cart-line-thumb cart-line-thumb-empty"><i class="bi bi-image"></i></div>`}
            <div class="cart-line-info">
                <div class="cart-line-name" title="${item.name}">${item.name}</div>
                <div class="cart-line-meta">${item.label} · ${money(item.price)}</div>
            </div>
            <div class="qty-control">
                <button type="button" data-action="dec">−</button>
                <span class="qty">${item.qty}</span>
                <button type="button" data-action="inc">+</button>
            </div>
            <div class="cart-line-total">${money(item.price * item.qty)}</div>
            <button type="button" class="cart-line-remove" data-action="remove" title="Remove"><i class="bi bi-x-lg"></i></button>
        </div>
    `).join('');

    updateTotals();
}

function updateTotals() {
    const items = [...state.cart.values()];
    const subtotal = items.reduce((sum, item) => sum + item.price * item.qty, 0);

    const discountType = els.discountType.value;
    let discountValue = parseFloat(els.discountValue.value) || 0;
    let discountAmount = 0;

    if (discountType === 'percent') {
        discountValue = Math.min(Math.max(discountValue, 0), 100);
        discountAmount = subtotal * discountValue / 100;
    } else if (discountType === 'fixed') {
        discountAmount = Math.min(Math.max(discountValue, 0), subtotal);
    }

    const taxable = subtotal - discountAmount;
    const tax = taxable * (window.TaxRate || 0) / 100;
    const total = taxable + tax;

    els.totals.subtotal.textContent = money(subtotal);
    els.totals.discount.textContent = discountAmount > 0 ? '− ' + money(discountAmount) : '—';
    els.totals.tax.textContent = money(tax);
    els.totals.total.textContent = money(total);

    const tendered = parseFloat(els.tendered.value) || 0;
    els.changeDue.value = tendered > 0 ? money(Math.max(0, tendered - total)) : '—';
}

function addToCart(variant) {
    const existing = state.cart.get(variant.variant_id);
    const current = existing?.qty || 0;

    if (current + 1 > variant.stock) {
        window.showToast(`Only ${variant.stock} in stock for ${variant.label}.`, 'warning');
        return;
    }

    state.cart.set(variant.variant_id, {
        variant_id: variant.variant_id,
        name: variant.product_name,
        label: variant.label,
        sku: variant.sku,
        price: variant.price,
        stock: variant.stock,
        image: variant.image || null,
        qty: current + 1,
    });

    renderCart();
    window.showToast(`${variant.product_name} (${variant.label}) added to cart`, 'success');
}

function changeQty(variantId, delta) {
    const item = state.cart.get(variantId);
    if (!item) return;

    const next = item.qty + delta;
    if (next < 1) {
        state.cart.delete(variantId);
    } else if (next > item.stock) {
        window.showToast(`Only ${item.stock} in stock.`, 'warning');
        return;
    } else {
        item.qty = next;
    }
    renderCart();
}

function searchProducts() {
    if (state.loading) return;
    state.loading = true;
    els.loader.classList.remove('d-none');
    els.grid.innerHTML = '';

    const params = new URLSearchParams();
    if (state.search) params.set('q', state.search);
    if (state.category) params.set('category', state.category);

    fetch(`/pos/search?${params.toString()}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
    })
        .then(res => res.json())
        .then(data => {
            els.grid.innerHTML = data.html;
            els.count.textContent = data.count ? `${data.count} product(s) found` : '';
        })
        .catch(() => {
            els.grid.innerHTML = '<div class="empty-state w-100"><i class="bi bi-emoji-frown"></i><p>Something went wrong. Please try again.</p></div>';
        })
        .finally(() => {
            state.loading = false;
            els.loader.classList.add('d-none');
        });
}

function checkout() {
    if (state.checkoutLocked) return;

    const items = [...state.cart.values()].map(item => ({
        variant_id: item.variant_id,
        quantity: item.qty,
    }));

    if (!items.length) return;

    state.checkoutLocked = true;
    els.checkoutBtn.disabled = true;
    els.checkoutBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Processing...';

    fetch('/pos/checkout', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
            items,
            customer_id: els.customer.value || null,
            payment_method: els.paymentMethod(),
            discount_type: els.discountType.value,
            discount_value: parseFloat(els.discountValue.value) || 0,
            amount_tendered: els.tendered.value || null,
        }),
    })
        .then(res => res.json().then(data => ({ ok: res.ok, data })))
        .then(({ ok, data }) => {
            if (!ok || !data.success) {
                window.showToast(data.message || 'Checkout failed.', 'error');
                return;
            }
            window.showToast(`Sale completed! Total ${money(data.total)}`, 'success');
            const receiptWindow = window.open(data.receipt_url, '_blank');
            if (receiptWindow) receiptWindow.focus();
            clearCart();
        })
        .catch(() => window.showToast('Could not complete the sale. Check your connection.', 'error'))
        .finally(() => {
            state.checkoutLocked = false;
            els.checkoutBtn.disabled = state.cart.size === 0;
            els.checkoutBtn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Complete Sale';
        });
}

function clearCart() {
    state.cart.clear();
    els.tendered.value = '';
    els.discountType.value = 'none';
    els.discountValue.value = '0';
    renderCart();
}

// ---------- Event bindings ----------
document.addEventListener('DOMContentLoaded', () => {
    renderCart();

    els.grid.addEventListener('click', (e) => {
        const chip = e.target.closest('.variant-chip');
        if (!chip) return;

        fetch(`/pos/variant/${chip.dataset.variantId}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
            .then(res => res.json())
            .then(variant => addToCart(variant))
            .catch(() => window.showToast('Could not load variant.', 'error'));
    });

    els.cartItems.addEventListener('click', (e) => {
        const button = e.target.closest('button[data-action]');
        if (!button) return;
        const line = button.closest('.cart-line');
        const variantId = line?.dataset.variant;
        const action = button.dataset.action;
        if (!variantId) return;

        if (action === 'inc') changeQty(variantId, 1);
        if (action === 'dec') changeQty(variantId, -1);
        if (action === 'remove') { state.cart.delete(variantId); renderCart(); }
    });

    els.clearCart.addEventListener('click', () => {
        if (state.cart.size && confirm('Clear the entire cart?')) clearCart();
    });

    let debounce;
    els.search.addEventListener('input', () => {
        clearTimeout(debounce);
        debounce = setTimeout(() => {
            state.search = els.search.value.trim();
            searchProducts();
        }, 300);
    });

    document.querySelectorAll('.pos-categories .chip').forEach(chip => {
        chip.addEventListener('click', () => {
            document.querySelectorAll('.pos-categories .chip').forEach(c => c.classList.remove('chip-active'));
            chip.classList.add('chip-active');
            state.category = chip.dataset.category;
            searchProducts();
        });
    });

    els.discountType.addEventListener('change', updateTotals);
    els.discountValue.addEventListener('input', updateTotals);
    els.tendered.addEventListener('input', updateTotals);

    els.checkoutBtn.addEventListener('click', () => {
        const method = els.paymentMethod();
        const total = parseFloat(els.totals.total.textContent.replace(/[^0-9.-]/g, '')) || 0;
        const tendered = parseFloat(els.tendered.value) || 0;

        if (method === 'cash' && tendered < total) {
            window.showToast(`Amount tendered (${money(tendered)}) is less than total (${money(total)}).`, 'warning');
            return;
        }
        if (!confirm(`Complete this sale?\nTotal: ${money(total)}`)) return;
        checkout();
    });
});

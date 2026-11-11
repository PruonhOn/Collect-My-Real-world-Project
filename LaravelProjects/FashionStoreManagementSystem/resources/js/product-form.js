function rowTemplate(index) {
    return `
        <tr class="variant-row">
            <td>
                <input type="text" name="variants[${index}][id]" class="variant-id" hidden>
                <input type="text" name="variants[${index}][size]" class="form-control form-control-sm" list="sizesList" placeholder="e.g. M">
            </td>
            <td>
                <input type="text" name="variants[${index}][color]" class="form-control form-control-sm" list="colorsList" placeholder="e.g. Black">
            </td>
            <td>
                <input type="text" name="variants[${index}][sku_variant]" class="form-control form-control-sm" required>
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <input type="text" name="variants[${index}][barcode]" class="form-control" placeholder="Auto">
                    <button type="button" class="btn btn-outline-secondary btn-gen-barcode" title="Generate barcode"><i class="bi bi-upc-scan"></i></button>
                </div>
            </td>
            <td>
                <input type="number" name="variants[${index}][stock_quantity]" value="0" class="form-control form-control-sm" min="0" required>
            </td>
            <td>
                <input type="number" name="variants[${index}][price_override]" step="0.01" min="0" class="form-control form-control-sm" placeholder="Use base">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-soft text-danger btn-remove-variant" title="Remove"><i class="bi bi-x-lg"></i></button>
            </td>
        </tr>`;
}

function generateSku(prefix = 'FST') {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let out = '';
    for (let i = 0; i < 6; i++) out += chars[Math.floor(Math.random() * chars.length)];
    return `${prefix}-${out}`;
}

function generateBarcode() {
    let digits = '';
    for (let i = 0; i < 12; i++) digits += Math.floor(Math.random() * 10);
    return digits;
}

document.addEventListener('DOMContentLoaded', () => {
    const tbody = document.getElementById('variantRows');
    const addBtn = document.getElementById('addVariantRow');
    const generateSkuBtn = document.getElementById('generateSku');

    function nextIndex() {
        const rows = tbody.querySelectorAll('.variant-row');
        return rows.length;
    }

    if (addBtn && tbody) {
        addBtn.addEventListener('click', () => {
            tbody.insertAdjacentHTML('beforeend', rowTemplate(nextIndex()));
        });
    }

    if (generateSkuBtn) {
        generateSkuBtn.addEventListener('click', () => {
            const input = document.querySelector('input[name="sku"]');
            if (input) input.value = generateSku();
        });
    }

    tbody.addEventListener('click', (e) => {
        const removeBtn = e.target.closest('.btn-remove-variant');
        const genBtn = e.target.closest('.btn-gen-barcode');

        if (removeBtn) {
            const row = removeBtn.closest('.variant-row');
            const rows = tbody.querySelectorAll('.variant-row');
            if (rows.length <= 1) {
                window.showToast('A product needs at least one variant.', 'warning');
                return;
            }
            row.remove();
        }

        if (genBtn) {
            const barcodeInput = genBtn.closest('.input-group').querySelector('input[name$="[barcode]"]');
            if (barcodeInput) barcodeInput.value = generateBarcode();
        }
    });

    // Auto-generate variant SKUs on submit when empty
    const form = document.getElementById('productForm');
    if (form) {
        form.addEventListener('submit', () => {
            const productSku = form.querySelector('input[name="sku"]').value || generateSku();
            tbody.querySelectorAll('.variant-row').forEach((row, i) => {
                const skuInput = row.querySelector('input[name*="[sku_variant]"]');
                if (!skuInput.value.trim()) {
                    skuInput.value = `${productSku}-V${i + 1}`;
                }
                const barcodeInput = row.querySelector('input[name*="[barcode]"]');
                if (!barcodeInput.value.trim()) {
                    barcodeInput.value = generateBarcode();
                }
            });
        });
    }
});

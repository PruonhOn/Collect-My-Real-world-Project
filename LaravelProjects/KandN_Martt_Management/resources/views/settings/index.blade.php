@extends('layouts.app')
@section('title', 'Settings')
@section('page-title', 'Settings')

@push('styles')
<style>
    .setting-item { display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid #eee; }
    .setting-item:last-child { border-bottom: none; }
    .setting-value { max-width: 400px; }
    .setting-value input, .setting-value select { width: 100%; }
</style>
@endpush

@section('content')
<ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
            <i class="bi bi-gear me-1"></i>General
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="pos-tab" data-bs-toggle="tab" data-bs-target="#pos" type="button" role="tab">
            <i class="bi bi-display me-1"></i>POS
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="receipt-tab" data-bs-toggle="tab" data-bs-target="#receipt" type="button" role="tab">
            <i class="bi bi-receipt me-1"></i>Receipt
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="inventory-tab" data-bs-toggle="tab" data-bs-target="#invSettings" type="button" role="tab">
            <i class="bi bi-boxes me-1"></i>Inventory
        </button>
    </li>
</ul>

<div class="tab-content" id="settingsTabContent">
    <div class="tab-pane fade show active" id="general" role="tabpanel">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-gear me-2 text-primary"></i>General Settings</h5>
                <button class="btn btn-sm btn-primary" onclick="saveSettings('general')"><i class="bi bi-check-lg me-1"></i>Save</button>
            </div>
            <div class="card-body" id="generalSettings">
                <div class="setting-item">
                    <div><label class="fw-semibold">Store Name</label><small class="d-block text-muted">Your business name</small></div>
                    <div class="setting-value">
                        <input type="text" class="form-control form-control-sm" name="store_name" value="{{ $settings['store_name'] ?? 'K&N Mart' }}" data-group="general">
                    </div>
                </div>
                <div class="setting-item">
                    <div><label class="fw-semibold">Store Email</label><small class="d-block text-muted">Primary contact email</small></div>
                    <div class="setting-value">
                        <input type="email" class="form-control form-control-sm" name="store_email" value="{{ $settings['store_email'] ?? '' }}" data-group="general">
                    </div>
                </div>
                <div class="setting-item">
                    <div><label class="fw-semibold">Store Phone</label><small class="d-block text-muted">Primary contact number</small></div>
                    <div class="setting-value">
                        <input type="text" class="form-control form-control-sm" name="store_phone" value="{{ $settings['store_phone'] ?? '' }}" data-group="general">
                    </div>
                </div>
                <div class="setting-item">
                    <div><label class="fw-semibold">Address</label><small class="d-block text-muted">Store location</small></div>
                    <div class="setting-value">
                        <textarea class="form-control form-control-sm" name="store_address" rows="2" data-group="general">{{ $settings['store_address'] ?? '' }}</textarea>
                    </div>
                </div>
                <div class="setting-item">
                    <div><label class="fw-semibold">Currency</label><small class="d-block text-muted">Default currency</small></div>
                    <div class="setting-value">
                        <select class="form-select form-select-sm" name="currency" data-group="general">
                            <option value="PHP" {{ (($settings['currency'] ?? 'PHP') == 'PHP') ? 'selected' : '' }}>PHP (₱)</option>
                            <option value="USD" {{ (($settings['currency'] ?? '') == 'USD') ? 'selected' : '' }}>USD ($)</option>
                            <option value="EUR" {{ (($settings['currency'] ?? '') == 'EUR') ? 'selected' : '' }}>EUR (€)</option>
                            <option value="GBP" {{ (($settings['currency'] ?? '') == 'GBP') ? 'selected' : '' }}>GBP (£)</option>
                        </select>
                    </div>
                </div>
                <div class="setting-item">
                    <div><label class="fw-semibold">Time Zone</label><small class="d-block text-muted">System time zone</small></div>
                    <div class="setting-value">
                        <select class="form-select form-select-sm" name="timezone" data-group="general">
                            <option value="Asia/Manila" {{ (($settings['timezone'] ?? 'Asia/Manila') == 'Asia/Manila') ? 'selected' : '' }}>Asia/Manila (UTC+8)</option>
                            <option value="UTC" {{ (($settings['timezone'] ?? '') == 'UTC') ? 'selected' : '' }}>UTC</option>
                            <option value="America/New_York" {{ (($settings['timezone'] ?? '') == 'America/New_York') ? 'selected' : '' }}>America/New York</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="pos" role="tabpanel">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-display me-2 text-primary"></i>POS Settings</h5>
                <button class="btn btn-sm btn-primary" onclick="saveSettings('pos')"><i class="bi bi-check-lg me-1"></i>Save</button>
            </div>
            <div class="card-body" id="posSettings">
                <div class="setting-item">
                    <div><label class="fw-semibold">Default Customer</label><small class="d-block text-muted">Walk-in customer ID</small></div>
                    <div class="setting-value">
                        <input type="number" class="form-control form-control-sm" name="default_customer_id" value="{{ $settings['default_customer_id'] ?? '1' }}" data-group="pos">
                    </div>
                </div>
                <div class="setting-item">
                    <div><label class="fw-semibold">Tax Rate (%)</label><small class="d-block text-muted">Default tax percentage</small></div>
                    <div class="setting-value">
                        <input type="number" step="0.01" class="form-control form-control-sm" name="tax_rate" value="{{ $settings['tax_rate'] ?? '12' }}" data-group="pos">
                    </div>
                </div>
                <div class="setting-item">
                    <div><label class="fw-semibold">Enable Discount</label><small class="d-block text-muted">Allow discounts at POS</small></div>
                    <div class="setting-value">
                        <select class="form-select form-select-sm" name="enable_discount" data-group="pos">
                            <option value="1" {{ (($settings['enable_discount'] ?? '1') == '1') ? 'selected' : '' }}>Yes</option>
                            <option value="0" {{ (($settings['enable_discount'] ?? '') == '0') ? 'selected' : '' }}>No</option>
                        </select>
                    </div>
                </div>
                <div class="setting-item">
                    <div><label class="fw-semibold">Barcode Scanner</label><small class="d-block text-muted">Enable barcode scanning</small></div>
                    <div class="setting-value">
                        <select class="form-select form-select-sm" name="barcode_scanner" data-group="pos">
                            <option value="1" {{ (($settings['barcode_scanner'] ?? '1') == '1') ? 'selected' : '' }}>Enabled</option>
                            <option value="0" {{ (($settings['barcode_scanner'] ?? '') == '0') ? 'selected' : '' }}>Disabled</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="receipt" role="tabpanel">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-receipt me-2 text-primary"></i>Receipt Settings</h5>
                <button class="btn btn-sm btn-primary" onclick="saveSettings('receipt')"><i class="bi bi-check-lg me-1"></i>Save</button>
            </div>
            <div class="card-body" id="receiptSettings">
                <div class="setting-item">
                    <div><label class="fw-semibold">Receipt Header</label><small class="d-block text-muted">Text printed at top</small></div>
                    <div class="setting-value">
                        <input type="text" class="form-control form-control-sm" name="receipt_header" value="{{ $settings['receipt_header'] ?? 'K&N Mart' }}" data-group="receipt">
                    </div>
                </div>
                <div class="setting-item">
                    <div><label class="fw-semibold">Receipt Footer</label><small class="d-block text-muted">Text printed at bottom</small></div>
                    <div class="setting-value">
                        <textarea class="form-control form-control-sm" name="receipt_footer" rows="2" data-group="receipt">{{ $settings['receipt_footer'] ?? 'Thank you for your purchase!' }}</textarea>
                    </div>
                </div>
                <div class="setting-item">
                    <div><label class="fw-semibold">Show Logo</label><small class="d-block text-muted">Display store logo on receipt</small></div>
                    <div class="setting-value">
                        <select class="form-select form-select-sm" name="receipt_show_logo" data-group="receipt">
                            <option value="1" {{ (($settings['receipt_show_logo'] ?? '1') == '1') ? 'selected' : '' }}>Yes</option>
                            <option value="0" {{ (($settings['receipt_show_logo'] ?? '') == '0') ? 'selected' : '' }}>No</option>
                        </select>
                    </div>
                </div>
                <div class="setting-item">
                    <div><label class="fw-semibold">Show Tax Details</label><small class="d-block text-muted">Display tax breakdown</small></div>
                    <div class="setting-value">
                        <select class="form-select form-select-sm" name="receipt_show_tax" data-group="receipt">
                            <option value="1" {{ (($settings['receipt_show_tax'] ?? '1') == '1') ? 'selected' : '' }}>Yes</option>
                            <option value="0" {{ (($settings['receipt_show_tax'] ?? '') == '0') ? 'selected' : '' }}>No</option>
                        </select>
                    </div>
                </div>
                <div class="setting-item">
                    <div><label class="fw-semibold">Receipt Width (mm)</label><small class="d-block text-muted">Thermal printer width</small></div>
                    <div class="setting-value">
                        <input type="number" class="form-control form-control-sm" name="receipt_width" value="{{ $settings['receipt_width'] ?? '80' }}" data-group="receipt">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="invSettings" role="tabpanel">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-boxes me-2 text-primary"></i>Inventory Settings</h5>
                <button class="btn btn-sm btn-primary" onclick="saveSettings('inventory')"><i class="bi bi-check-lg me-1"></i>Save</button>
            </div>
            <div class="card-body" id="inventorySettings">
                <div class="setting-item">
                    <div><label class="fw-semibold">Low Stock Threshold</label><small class="d-block text-muted">Quantity that triggers low stock warning</small></div>
                    <div class="setting-value">
                        <input type="number" class="form-control form-control-sm" name="low_stock_threshold" value="{{ $settings['low_stock_threshold'] ?? '10' }}" data-group="inventory">
                    </div>
                </div>
                <div class="setting-item">
                    <div><label class="fw-semibold">Enable Stock Alerts</label><small class="d-block text-muted">Notify when stock is low</small></div>
                    <div class="setting-value">
                        <select class="form-select form-select-sm" name="stock_alerts" data-group="inventory">
                            <option value="1" {{ (($settings['stock_alerts'] ?? '1') == '1') ? 'selected' : '' }}>Enabled</option>
                            <option value="0" {{ (($settings['stock_alerts'] ?? '') == '0') ? 'selected' : '' }}>Disabled</option>
                        </select>
                    </div>
                </div>
                <div class="setting-item">
                    <div><label class="fw-semibold">Auto Generate SKU</label><small class="d-block text-muted">Automatically generate SKU for new products</small></div>
                    <div class="setting-value">
                        <select class="form-select form-select-sm" name="auto_sku" data-group="inventory">
                            <option value="1" {{ (($settings['auto_sku'] ?? '1') == '1') ? 'selected' : '' }}>Yes</option>
                            <option value="0" {{ (($settings['auto_sku'] ?? '') == '0') ? 'selected' : '' }}>No</option>
                        </select>
                    </div>
                </div>
                <div class="setting-item">
                    <div><label class="fw-semibold">SKU Prefix</label><small class="d-block text-muted">Prefix for auto-generated SKUs</small></div>
                    <div class="setting-value">
                        <input type="text" class="form-control form-control-sm" name="sku_prefix" value="{{ $settings['sku_prefix'] ?? 'KN' }}" data-group="inventory">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function saveSettings(group) {
        const form = new FormData();
        form.append('group', group);
        form.append('_token', '{{ csrf_token() }}');

        $(`[data-group="${group}"]`).each(function () {
            form.append($(this).attr('name'), $(this).val());
        });

        $.ajax({
            url: '{{ route("settings.update") }}',
            method: 'POST',
            data: form,
            processData: false,
            contentType: false,
            success: function (res) {
                const alert = `<div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>${res.message || 'Settings saved successfully.'}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>`;
                $('main').prepend(alert);
                setTimeout(function () { $('.alert').alert('close'); }, 3000);
            },
            error: function () {
                const alert = `<div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>Failed to save settings.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>`;
                $('main').prepend(alert);
            }
        });
    }
</script>
@endpush

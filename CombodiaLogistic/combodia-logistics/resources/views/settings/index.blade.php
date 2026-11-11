@extends('layouts.admin')

@section('title', 'Settings')
@section('subtitle', 'System configuration')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Settings</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Settings</li>
            </ol>
        </nav>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#general" type="button">
                    <i class="bi bi-gear me-1"></i> General
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#shipment" type="button">
                    <i class="bi bi-box-seam me-1"></i> Shipment
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#invoice" type="button">
                    <i class="bi bi-receipt me-1"></i> Invoice
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#payment" type="button">
                    <i class="bi bi-credit-card me-1"></i> Payment
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#notification" type="button">
                    <i class="bi bi-bell me-1"></i> Notification
                </button>
            </li>
        </ul>

        <div class="tab-content p-4">
            <!-- General Settings -->
            <div class="tab-pane fade show active" id="general">
                <form action="{{ route('settings.update', 'general') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <h5 class="fw-bold mb-4">General Settings</h5>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Company Name</label>
                            <input type="text" class="form-control" name="company_name" value="{{ $settings['company_name'] ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Company Email</label>
                            <input type="email" class="form-control" name="company_email" value="{{ $settings['company_email'] ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Company Phone</label>
                            <input type="text" class="form-control" name="company_phone" value="{{ $settings['company_phone'] ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Company Website</label>
                            <input type="url" class="form-control" name="company_website" value="{{ $settings['company_website'] ?? '' }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Company Address</label>
                            <textarea class="form-control" name="company_address" rows="2">{{ $settings['company_address'] ?? '' }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Timezone</label>
                            <select class="form-select" name="timezone">
                                <option value="Asia/Phnom_Penh" {{ ($settings['timezone'] ?? '') == 'Asia/Phnom_Penh' ? 'selected' : '' }}>Asia/Phnom Penh (ICT)</option>
                                <option value="Asia/Bangkok" {{ ($settings['timezone'] ?? '') == 'Asia/Bangkok' ? 'selected' : '' }}>Asia/Bangkok (ICT)</option>
                                <option value="UTC" {{ ($settings['timezone'] ?? '') == 'UTC' ? 'selected' : '' }}>UTC</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Currency</label>
                            <select class="form-select" name="currency">
                                <option value="USD" {{ ($settings['currency'] ?? '') == 'USD' ? 'selected' : '' }}>USD ($)</option>
                                <option value="KHR" {{ ($settings['currency'] ?? '') == 'KHR' ? 'selected' : '' }}>KHR (៛)</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i> Save General Settings
                        </button>
                    </div>
                </form>
            </div>

            <!-- Shipment Settings -->
            <div class="tab-pane fade" id="shipment">
                <form action="{{ route('settings.update', 'shipment') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <h5 class="fw-bold mb-4">Shipment Settings</h5>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Default Shipment Type</label>
                            <select class="form-select" name="default_shipment_type">
                                <option value="standard" {{ ($settings['default_shipment_type'] ?? '') == 'standard' ? 'selected' : '' }}>Standard</option>
                                <option value="express" {{ ($settings['default_shipment_type'] ?? '') == 'express' ? 'selected' : '' }}>Express</option>
                                <option value="economy" {{ ($settings['default_shipment_type'] ?? '') == 'economy' ? 'selected' : '' }}>Economy</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Default Tax Rate (%)</label>
                            <input type="number" class="form-control" name="default_tax_rate" step="0.01" value="{{ $settings['default_tax_rate'] ?? '10.00' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Shipment Number Prefix</label>
                            <input type="text" class="form-control" name="shipment_prefix" value="{{ $settings['shipment_prefix'] ?? 'SH' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Auto-assign Driver</label>
                            <select class="form-select" name="auto_assign_driver">
                                <option value="1" {{ ($settings['auto_assign_driver'] ?? '0') == '1' ? 'selected' : '' }}>Yes</option>
                                <option value="0" {{ ($settings['auto_assign_driver'] ?? '0') == '0' ? 'selected' : '' }}>No</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Shipment Instructions (Default)</label>
                            <textarea class="form-control" name="default_instructions" rows="2">{{ $settings['default_instructions'] ?? '' }}</textarea>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i> Save Shipment Settings
                        </button>
                    </div>
                </form>
            </div>

            <!-- Invoice Settings -->
            <div class="tab-pane fade" id="invoice">
                <form action="{{ route('settings.update', 'invoice') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <h5 class="fw-bold mb-4">Invoice Settings</h5>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Invoice Number Prefix</label>
                            <input type="text" class="form-control" name="invoice_prefix" value="{{ $settings['invoice_prefix'] ?? 'INV' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Default Payment Terms (Days)</label>
                            <input type="number" class="form-control" name="payment_terms_days" value="{{ $settings['payment_terms_days'] ?? '30' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Default Tax Rate (%)</label>
                            <input type="number" class="form-control" name="invoice_tax_rate" step="0.01" value="{{ $settings['invoice_tax_rate'] ?? '10.00' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Late Fee Rate (% per month)</label>
                            <input type="number" class="form-control" name="late_fee_rate" step="0.01" value="{{ $settings['late_fee_rate'] ?? '1.50' }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Default Terms & Conditions</label>
                            <textarea class="form-control" name="default_terms" rows="3">{{ $settings['default_terms'] ?? '' }}</textarea>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i> Save Invoice Settings
                        </button>
                    </div>
                </form>
            </div>

            <!-- Payment Settings -->
            <div class="tab-pane fade" id="payment">
                <form action="{{ route('settings.update', 'payment') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <h5 class="fw-bold mb-4">Payment Settings</h5>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Payment Number Prefix</label>
                            <input type="text" class="form-control" name="payment_prefix" value="{{ $settings['payment_prefix'] ?? 'PAY' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Auto-confirm Payments</label>
                            <select class="form-select" name="auto_confirm_payments">
                                <option value="1" {{ ($settings['auto_confirm_payments'] ?? '0') == '1' ? 'selected' : '' }}>Yes</option>
                                <option value="0" {{ ($settings['auto_confirm_payments'] ?? '0') == '0' ? 'selected' : '' }}>No</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Default Payment Method</label>
                            <select class="form-select" name="default_payment_method">
                                <option value="cash" {{ ($settings['default_payment_method'] ?? '') == 'cash' ? 'selected' : '' }}>Cash</option>
                                <option value="bank_transfer" {{ ($settings['default_payment_method'] ?? '') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                <option value="credit_card" {{ ($settings['default_payment_method'] ?? '') == 'credit_card' ? 'selected' : '' }}>Credit Card</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Bank Account Details</label>
                            <textarea class="form-control" name="bank_details" rows="3">{{ $settings['bank_details'] ?? '' }}</textarea>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i> Save Payment Settings
                        </button>
                    </div>
                </form>
            </div>

            <!-- Notification Settings -->
            <div class="tab-pane fade" id="notification">
                <form action="{{ route('settings.update', 'notification') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <h5 class="fw-bold mb-4">Notification Settings</h5>
                    <div class="row g-4">
                        <div class="col-12">
                            <h6 class="fw-semibold">Email Notifications</h6>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="notify_shipment_created" id="notify1" value="1" {{ ($settings['notify_shipment_created'] ?? '1') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="notify1">Shipment Created</label>
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="notify_shipment_delivered" id="notify2" value="1" {{ ($settings['notify_shipment_delivered'] ?? '1') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="notify2">Shipment Delivered</label>
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="notify_payment_received" id="notify3" value="1" {{ ($settings['notify_payment_received'] ?? '1') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="notify3">Payment Received</label>
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="notify_invoice_overdue" id="notify4" value="1" {{ ($settings['notify_invoice_overdue'] ?? '1') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="notify4">Invoice Overdue</label>
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="notify_low_stock" id="notify5" value="1" {{ ($settings['notify_low_stock'] ?? '1') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="notify5">Low Stock Alert</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Admin Email (for notifications)</label>
                            <input type="email" class="form-control" name="admin_email" value="{{ $settings['admin_email'] ?? '' }}">
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i> Save Notification Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

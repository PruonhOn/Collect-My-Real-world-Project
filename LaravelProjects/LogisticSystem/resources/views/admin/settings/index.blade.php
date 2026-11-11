<x-admin-layout>
    @section('title', 'System Settings - ' . config('app.name'))

    @section('breadcrumbs')
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
        <li class="breadcrumb-item active" aria-current="page">Settings</li>
    @endsection

    @section('page-title', 'System Settings')
    @section('page-subtitle', 'Configure system-wide settings')

    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="settingsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">
                        <i class="bi bi-gear me-1"></i> General
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="company-tab" data-bs-toggle="tab" data-bs-target="#company" type="button" role="tab" aria-controls="company" aria-selected="false">
                        <i class="bi bi-building me-1"></i> Company
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="shipping-tab" data-bs-toggle="tab" data-bs-target="#shipping" type="button" role="tab" aria-controls="shipping" aria-selected="false">
                        <i class="bi bi-box-seam me-1"></i> Shipping
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="financial-tab" data-bs-toggle="tab" data-bs-target="#financial" type="button" role="tab" aria-controls="financial" aria-selected="false">
                        <i class="bi bi-cash-coin me-1"></i> Financial
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="system-tab" data-bs-toggle="tab" data-bs-target="#system" type="button" role="tab" aria-controls="system" aria-selected="false">
                        <i class="bi bi-terminal me-1"></i> System
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="tab-content" id="settingsTabsContent">
                    <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="app_name" class="form-label">Application Name</label>
                                <input type="text" class="form-control @error('app_name') is-invalid @enderror" id="app_name" name="app_name" value="{{ old('app_name', $settings['app_name'] ?? config('app.name')) }}">
                                @error('app_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="app_url" class="form-label">Application URL</label>
                                <input type="url" class="form-control @error('app_url') is-invalid @enderror" id="app_url" name="app_url" value="{{ old('app_url', $settings['app_url'] ?? config('app.url')) }}">
                                @error('app_url')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="default_timezone" class="form-label">Default Timezone</label>
                                <select class="form-select @error('default_timezone') is-invalid @enderror" id="default_timezone" name="default_timezone">
                                    <option value="Asia/Phnom_Penh" {{ old('default_timezone', $settings['default_timezone'] ?? config('app.timezone')) == 'Asia/Phnom_Penh' ? 'selected' : '' }}>Asia/Phnom Penh (UTC+7)</option>
                                    <option value="Asia/Bangkok" {{ old('default_timezone', $settings['default_timezone'] ?? config('app.timezone')) == 'Asia/Bangkok' ? 'selected' : '' }}>Asia/Bangkok (UTC+7)</option>
                                    <option value="Asia/Ho_Chi_Minh" {{ old('default_timezone', $settings['default_timezone'] ?? config('app.timezone')) == 'Asia/Ho_Chi_Minh' ? 'selected' : '' }}>Asia/Ho Chi Minh (UTC+7)</option>
                                    <option value="UTC" {{ old('default_timezone', $settings['default_timezone'] ?? config('app.timezone')) == 'UTC' ? 'selected' : '' }}>UTC</option>
                                </select>
                                @error('default_timezone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="default_language" class="form-label">Default Language</label>
                                <select class="form-select @error('default_language') is-invalid @enderror" id="default_language" name="default_language">
                                    <option value="en" {{ old('default_language', $settings['default_language'] ?? 'en') == 'en' ? 'selected' : '' }}>English</option>
                                    <option value="km" {{ old('default_language', $settings['default_language'] ?? 'en') == 'km' ? 'selected' : '' }}>Khmer (ភាសាខ្មែរ)</option>
                                </select>
                                @error('default_language')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode" value="1" {{ old('maintenance_mode', $settings['maintenance_mode'] ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="maintenance_mode">Maintenance Mode</label>
                                    <small class="d-block text-muted">When enabled, only admin users can access the system.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="company" role="tabpanel" aria-labelledby="company-tab">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="company_name" class="form-label">Company Name</label>
                                <input type="text" class="form-control @error('company_name') is-invalid @enderror" id="company_name" name="company_name" value="{{ old('company_name', $settings['company_name'] ?? '') }}">
                                @error('company_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="company_phone" class="form-label">Company Phone</label>
                                <input type="text" class="form-control @error('company_phone') is-invalid @enderror" id="company_phone" name="company_phone" value="{{ old('company_phone', $settings['company_phone'] ?? '') }}">
                                @error('company_phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="company_email" class="form-label">Company Email</label>
                                <input type="email" class="form-control @error('company_email') is-invalid @enderror" id="company_email" name="company_email" value="{{ old('company_email', $settings['company_email'] ?? '') }}">
                                @error('company_email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="company_website" class="form-label">Company Website</label>
                                <input type="url" class="form-control @error('company_website') is-invalid @enderror" id="company_website" name="company_website" value="{{ old('company_website', $settings['company_website'] ?? '') }}">
                                @error('company_website')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label for="company_address" class="form-label">Company Address</label>
                                <textarea class="form-control @error('company_address') is-invalid @enderror" id="company_address" name="company_address" rows="2">{{ old('company_address', $settings['company_address'] ?? '') }}</textarea>
                                @error('company_address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="company_city" class="form-label">City</label>
                                <input type="text" class="form-control @error('company_city') is-invalid @enderror" id="company_city" name="company_city" value="{{ old('company_city', $settings['company_city'] ?? '') }}">
                                @error('company_city')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="company_country" class="form-label">Country</label>
                                <input type="text" class="form-control @error('company_country') is-invalid @enderror" id="company_country" name="company_country" value="{{ old('company_country', $settings['company_country'] ?? 'Cambodia') }}">
                                @error('company_country')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label for="company_logo" class="form-label">Company Logo</label>
                                @if(!empty($settings['company_logo']))
                                    <div class="mb-2">
                                        <img src="{{ asset('storage/' . $settings['company_logo']) }}" alt="Company Logo" class="img-thumbnail" style="max-height: 80px;">
                                    </div>
                                @endif
                                <input type="file" class="form-control @error('company_logo') is-invalid @enderror" id="company_logo" name="company_logo" accept="image/*">
                                <small class="text-muted">Recommended size: 200x200px. Max 2MB.</small>
                                @error('company_logo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="shipping" role="tabpanel" aria-labelledby="shipping-tab">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="default_weight_unit" class="form-label">Default Weight Unit</label>
                                <select class="form-select @error('default_weight_unit') is-invalid @enderror" id="default_weight_unit" name="default_weight_unit">
                                    <option value="kg" {{ old('default_weight_unit', $settings['default_weight_unit'] ?? 'kg') == 'kg' ? 'selected' : '' }}>Kilograms (kg)</option>
                                    <option value="lb" {{ old('default_weight_unit', $settings['default_weight_unit'] ?? 'kg') == 'lb' ? 'selected' : '' }}>Pounds (lb)</option>
                                    <option value="g" {{ old('default_weight_unit', $settings['default_weight_unit'] ?? 'kg') == 'g' ? 'selected' : '' }}>Grams (g)</option>
                                </select>
                                @error('default_weight_unit')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="default_dimension_unit" class="form-label">Default Dimension Unit</label>
                                <select class="form-select @error('default_dimension_unit') is-invalid @enderror" id="default_dimension_unit" name="default_dimension_unit">
                                    <option value="cm" {{ old('default_dimension_unit', $settings['default_dimension_unit'] ?? 'cm') == 'cm' ? 'selected' : '' }}>Centimeters (cm)</option>
                                    <option value="in" {{ old('default_dimension_unit', $settings['default_dimension_unit'] ?? 'cm') == 'in' ? 'selected' : '' }}>Inches (in)</option>
                                    <option value="m" {{ old('default_dimension_unit', $settings['default_dimension_unit'] ?? 'cm') == 'm' ? 'selected' : '' }}>Meters (m)</option>
                                </select>
                                @error('default_dimension_unit')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="auto_tracking_number" class="form-label">Auto-generate Tracking Numbers</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="auto_tracking_number" name="auto_tracking_number" value="1" {{ old('auto_tracking_number', $settings['auto_tracking_number'] ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="auto_tracking_number">Enabled</label>
                                </div>
                                @error('auto_tracking_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="tracking_prefix" class="form-label">Tracking Number Prefix</label>
                                <input type="text" class="form-control @error('tracking_prefix') is-invalid @enderror" id="tracking_prefix" name="tracking_prefix" value="{{ old('tracking_prefix', $settings['tracking_prefix'] ?? 'KHM-') }}">
                                <small class="text-muted">e.g., KHM-, PNH-, REP-</small>
                                @error('tracking_prefix')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="default_shipment_status" class="form-label">Default Shipment Status</label>
                                <select class="form-select @error('default_shipment_status') is-invalid @enderror" id="default_shipment_status" name="default_shipment_status">
                                    <option value="pending" {{ old('default_shipment_status', $settings['default_shipment_status'] ?? 'pending') == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="processing" {{ old('default_shipment_status', $settings['default_shipment_status'] ?? 'pending') == 'processing' ? 'selected' : '' }}>Processing</option>
                                </select>
                                @error('default_shipment_status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="max_shipment_weight" class="form-label">Max Shipment Weight ({{ old('default_weight_unit', $settings['default_weight_unit'] ?? 'kg') }})</label>
                                <input type="number" step="0.01" class="form-control @error('max_shipment_weight') is-invalid @enderror" id="max_shipment_weight" name="max_shipment_weight" value="{{ old('max_shipment_weight', $settings['max_shipment_weight'] ?? 1000) }}">
                                @error('max_shipment_weight')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="financial" role="tabpanel" aria-labelledby="financial-tab">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="currency" class="form-label">Base Currency</label>
                                <select class="form-select @error('currency') is-invalid @enderror" id="currency" name="currency">
                                    <option value="USD" {{ old('currency', $settings['currency'] ?? 'USD') == 'USD' ? 'selected' : '' }}>USD - US Dollar</option>
                                    <option value="KHR" {{ old('currency', $settings['currency'] ?? 'USD') == 'KHR' ? 'selected' : '' }}>KHR - Cambodian Riel</option>
                                    <option value="THB" {{ old('currency', $settings['currency'] ?? 'USD') == 'THB' ? 'selected' : '' }}>THB - Thai Baht</option>
                                    <option value="VND" {{ old('currency', $settings['currency'] ?? 'USD') == 'VND' ? 'selected' : '' }}>VND - Vietnamese Dong</option>
                                </select>
                                @error('currency')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="tax_rate" class="form-label">Default Tax Rate (%)</label>
                                <input type="number" step="0.01" class="form-control @error('tax_rate') is-invalid @enderror" id="tax_rate" name="tax_rate" value="{{ old('tax_rate', $settings['tax_rate'] ?? 10) }}">
                                <small class="text-muted">VAT / GST percentage applied to invoices.</small>
                                @error('tax_rate')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="tax_label" class="form-label">Tax Label</label>
                                <input type="text" class="form-control @error('tax_label') is-invalid @enderror" id="tax_label" name="tax_label" value="{{ old('tax_label', $settings['tax_label'] ?? 'VAT (10%)') }}">
                                @error('tax_label')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="invoice_prefix" class="form-label">Invoice Number Prefix</label>
                                <input type="text" class="form-control @error('invoice_prefix') is-invalid @enderror" id="invoice_prefix" name="invoice_prefix" value="{{ old('invoice_prefix', $settings['invoice_prefix'] ?? 'INV-') }}">
                                @error('invoice_prefix')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="payment_terms" class="form-label">Default Payment Terms (Days)</label>
                                <input type="number" class="form-control @error('payment_terms') is-invalid @enderror" id="payment_terms" name="payment_terms" value="{{ old('payment_terms', $settings['payment_terms'] ?? 30) }}">
                                <small class="text-muted">Net payment due in days.</small>
                                @error('payment_terms')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="currency_position" class="form-label">Currency Symbol Position</label>
                                <select class="form-select @error('currency_position') is-invalid @enderror" id="currency_position" name="currency_position">
                                    <option value="before" {{ old('currency_position', $settings['currency_position'] ?? 'before') == 'before' ? 'selected' : '' }}>$100 (Before)</option>
                                    <option value="after" {{ old('currency_position', $settings['currency_position'] ?? 'before') == 'after' ? 'selected' : '' }}>100$ (After)</option>
                                </select>
                                @error('currency_position')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label for="exchange_rate_khr" class="form-label">Exchange Rate (1 USD to KHR)</label>
                                <input type="number" step="1" class="form-control @error('exchange_rate_khr') is-invalid @enderror" id="exchange_rate_khr" name="exchange_rate_khr" value="{{ old('exchange_rate_khr', $settings['exchange_rate_khr'] ?? 4100) }}">
                                <small class="text-muted">Current exchange rate for reference and reporting.</small>
                                @error('exchange_rate_khr')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="system" role="tabpanel" aria-labelledby="system-tab">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="pagination_per_page" class="form-label">Items Per Page</label>
                                <input type="number" class="form-control @error('pagination_per_page') is-invalid @enderror" id="pagination_per_page" name="pagination_per_page" value="{{ old('pagination_per_page', $settings['pagination_per_page'] ?? 25) }}">
                                @error('pagination_per_page')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="session_lifetime" class="form-label">Session Lifetime (Minutes)</label>
                                <input type="number" class="form-control @error('session_lifetime') is-invalid @enderror" id="session_lifetime" name="session_lifetime" value="{{ old('session_lifetime', $settings['session_lifetime'] ?? 120) }}">
                                @error('session_lifetime')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="date_format" class="form-label">Date Format</label>
                                <select class="form-select @error('date_format') is-invalid @enderror" id="date_format" name="date_format">
                                    <option value="Y-m-d" {{ old('date_format', $settings['date_format'] ?? 'Y-m-d') == 'Y-m-d' ? 'selected' : '' }}>2025-01-15</option>
                                    <option value="d/m/Y" {{ old('date_format', $settings['date_format'] ?? 'Y-m-d') == 'd/m/Y' ? 'selected' : '' }}>15/01/2025</option>
                                    <option value="m/d/Y" {{ old('date_format', $settings['date_format'] ?? 'Y-m-d') == 'm/d/Y' ? 'selected' : '' }}>01/15/2025</option>
                                    <option value="d M, Y" {{ old('date_format', $settings['date_format'] ?? 'Y-m-d') == 'd M, Y' ? 'selected' : '' }}>15 Jan, 2025</option>
                                </select>
                                @error('date_format')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="time_format" class="form-label">Time Format</label>
                                <select class="form-select @error('time_format') is-invalid @enderror" id="time_format" name="time_format">
                                    <option value="H:i" {{ old('time_format', $settings['time_format'] ?? 'H:i') == 'H:i' ? 'selected' : '' }}>14:30 (24-hour)</option>
                                    <option value="h:i A" {{ old('time_format', $settings['time_format'] ?? 'H:i') == 'h:i A' ? 'selected' : '' }}>02:30 PM (12-hour)</option>
                                </select>
                                @error('time_format')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <hr>
                                <h6 class="fw-bold"><i class="bi bi-envelope me-1"></i> Email Configuration</h6>
                            </div>
                            <div class="col-md-6">
                                <label for="mail_from_address" class="form-label">From Address</label>
                                <input type="email" class="form-control @error('mail_from_address') is-invalid @enderror" id="mail_from_address" name="mail_from_address" value="{{ old('mail_from_address', $settings['mail_from_address'] ?? 'noreply@logisticpro.com') }}">
                                @error('mail_from_address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="mail_from_name" class="form-label">From Name</label>
                                <input type="text" class="form-control @error('mail_from_name') is-invalid @enderror" id="mail_from_name" name="mail_from_name" value="{{ old('mail_from_name', $settings['mail_from_name'] ?? 'Logistics Pro') }}">
                                @error('mail_from_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <hr>
                                <h6 class="fw-bold"><i class="bi bi-shield-lock me-1"></i> Security</h6>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="two_factor_auth" name="two_factor_auth" value="1" {{ old('two_factor_auth', $settings['two_factor_auth'] ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="two_factor_auth">Require Two-Factor Authentication</label>
                                    <small class="d-block text-muted">Enforce 2FA for all admin users.</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="password_expiry" name="password_expiry" value="1" {{ old('password_expiry', $settings['password_expiry'] ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="password_expiry">Enforce Password Expiry</label>
                                    <small class="d-block text-muted">Force password change every 90 days.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                    <button type="reset" class="btn btn-outline-secondary">Reset</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    @section('scripts')
    <script>
        document.querySelector('button[type="reset"]').addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Reset all changes on this page?')) {
                this.closest('form').reset();
            }
        });
    </script>
    @endsection
</x-admin-layout>

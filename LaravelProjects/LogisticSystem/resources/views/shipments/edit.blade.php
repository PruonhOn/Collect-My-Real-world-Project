<x-admin-layout>
    @section('title', 'Edit Shipment - ' . config('app.name'))

    @section('breadcrumbs')
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('shipments.index') }}">Shipments</a></li>
        <li class="breadcrumb-item"><a href="{{ route('shipments.show', $shipment) }}">{{ $shipment->tracking_number }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">Edit</li>
    @endsection

    @section('page-title', 'Edit Shipment: ' . $shipment->tracking_number)
    @section('page-subtitle', 'Update shipment information')

    <form action="{{ route('shipments.update', $shipment) }}" method="POST" id="editShipmentForm">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-12">
                <ul class="nav nav-tabs mb-4" id="shipmentTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab">
                            <i class="bi bi-info-circle me-1"></i> Shipment Info
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="origin-tab" data-bs-toggle="tab" data-bs-target="#origin" type="button" role="tab">
                            <i class="bi bi-geo-alt me-1"></i> Origin
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="destination-tab" data-bs-toggle="tab" data-bs-target="#destination" type="button" role="tab">
                            <i class="bi bi-geo me-1"></i> Destination
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="shipper-tab" data-bs-toggle="tab" data-bs-target="#shipper" type="button" role="tab">
                            <i class="bi bi-person me-1"></i> Shipper
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="consignee-tab" data-bs-toggle="tab" data-bs-target="#consignee" type="button" role="tab">
                            <i class="bi bi-person-badge me-1"></i> Consignee
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="cargo-tab" data-bs-toggle="tab" data-bs-target="#cargo" type="button" role="tab">
                            <i class="bi bi-box me-1"></i> Cargo
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="options-tab" data-bs-toggle="tab" data-bs-target="#options" type="button" role="tab">
                            <i class="bi bi-gear me-1"></i> Options
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="info" role="tabpanel">
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="tracking_number" class="form-label">Tracking Number</label>
                                        <input type="text" id="tracking_number" name="tracking_number" class="form-control @error('tracking_number') is-invalid @enderror" value="{{ old('tracking_number', $shipment->tracking_number) }}" readonly>
                                        <x-form-error name="tracking_number" />
                                    </div>
                                    <div class="col-md-4">
                                        <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
                                        <select id="type" name="type" class="form-select @error('type') is-invalid @enderror" required>
                                            <option value="">Select Type</option>
                                            <option value="import" {{ old('type', $shipment->type) === 'import' ? 'selected' : '' }}>Import</option>
                                            <option value="export" {{ old('type', $shipment->type) === 'export' ? 'selected' : '' }}>Export</option>
                                            <option value="domestic" {{ old('type', $shipment->type) === 'domestic' ? 'selected' : '' }}>Domestic</option>
                                            <option value="transit" {{ old('type', $shipment->type) === 'transit' ? 'selected' : '' }}>Transit</option>
                                        </select>
                                        <x-form-error name="type" />
                                    </div>
                                    <div class="col-md-4">
                                        <label for="shipment_date" class="form-label">Shipment Date <span class="text-danger">*</span></label>
                                        <input type="date" id="shipment_date" name="shipment_date" class="form-control @error('shipment_date') is-invalid @enderror" value="{{ old('shipment_date', $shipment->shipment_date ? $shipment->shipment_date->format('Y-m-d') : date('Y-m-d')) }}" required>
                                        <x-form-error name="shipment_date" />
                                    </div>
                                    <div class="col-12">
                                        <label for="customer_id" class="form-label">Customer <span class="text-danger">*</span></label>
                                        <select id="customer_id" name="customer_id" class="form-select select2 @error('customer_id') is-invalid @enderror" required>
                                            <option value="">Select Customer</option>
                                            @foreach($customers as $customer)
                                                <option value="{{ $customer->id }}" {{ old('customer_id', $shipment->customer_id) == $customer->id ? 'selected' : '' }}>
                                                    {{ $customer->name }} ({{ $customer->code }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <x-form-error name="customer_id" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="origin" role="tabpanel">
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label for="origin_address" class="form-label">Address</label>
                                        <textarea id="origin_address" name="origin_address" class="form-control @error('origin_address') is-invalid @enderror" rows="2">{{ old('origin_address', $shipment->origin_address) }}</textarea>
                                        <x-form-error name="origin_address" />
                                    </div>
                                    <div class="col-md-4">
                                        <label for="origin_branch_id" class="form-label">Origin Branch</label>
                                        <select id="origin_branch_id" name="origin_branch_id" class="form-select select2 @error('origin_branch_id') is-invalid @enderror">
                                            <option value="">Select Branch</option>
                                            @foreach($branches as $branch)
                                                <option value="{{ $branch->id }}" {{ old('origin_branch_id', $shipment->origin_branch_id) == $branch->id ? 'selected' : '' }}>{{ $branch->name }} ({{ $branch->code }})</option>
                                            @endforeach
                                        </select>
                                        <x-form-error name="origin_branch_id" />
                                    </div>
                                    <div class="col-md-4">
                                        <label for="origin_city" class="form-label">City</label>
                                        <input type="text" id="origin_city" name="origin_city" class="form-control @error('origin_city') is-invalid @enderror" value="{{ old('origin_city', $shipment->origin_city) }}">
                                        <x-form-error name="origin_city" />
                                    </div>
                                    <div class="col-md-4">
                                        <label for="origin_province" class="form-label">Province</label>
                                        <input type="text" id="origin_province" name="origin_province" class="form-control @error('origin_province') is-invalid @enderror" value="{{ old('origin_province', $shipment->origin_province) }}">
                                        <x-form-error name="origin_province" />
                                    </div>
                                    <div class="col-md-4">
                                        <label for="origin_country" class="form-label">Country</label>
                                        <input type="text" id="origin_country" name="origin_country" class="form-control @error('origin_country') is-invalid @enderror" value="{{ old('origin_country', $shipment->origin_country ?? 'Cambodia') }}">
                                        <x-form-error name="origin_country" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="destination" role="tabpanel">
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label for="destination_address" class="form-label">Address</label>
                                        <textarea id="destination_address" name="destination_address" class="form-control @error('destination_address') is-invalid @enderror" rows="2">{{ old('destination_address', $shipment->destination_address) }}</textarea>
                                        <x-form-error name="destination_address" />
                                    </div>
                                    <div class="col-md-4">
                                        <label for="destination_branch_id" class="form-label">Destination Branch</label>
                                        <select id="destination_branch_id" name="destination_branch_id" class="form-select select2 @error('destination_branch_id') is-invalid @enderror">
                                            <option value="">Select Branch</option>
                                            @foreach($branches as $branch)
                                                <option value="{{ $branch->id }}" {{ old('destination_branch_id', $shipment->destination_branch_id) == $branch->id ? 'selected' : '' }}>{{ $branch->name }} ({{ $branch->code }})</option>
                                            @endforeach
                                        </select>
                                        <x-form-error name="destination_branch_id" />
                                    </div>
                                    <div class="col-md-4">
                                        <label for="destination_city" class="form-label">City</label>
                                        <input type="text" id="destination_city" name="destination_city" class="form-control @error('destination_city') is-invalid @enderror" value="{{ old('destination_city', $shipment->destination_city) }}">
                                        <x-form-error name="destination_city" />
                                    </div>
                                    <div class="col-md-4">
                                        <label for="destination_province" class="form-label">Province</label>
                                        <input type="text" id="destination_province" name="destination_province" class="form-control @error('destination_province') is-invalid @enderror" value="{{ old('destination_province', $shipment->destination_province) }}">
                                        <x-form-error name="destination_province" />
                                    </div>
                                    <div class="col-md-4">
                                        <label for="destination_country" class="form-label">Country</label>
                                        <input type="text" id="destination_country" name="destination_country" class="form-control @error('destination_country') is-invalid @enderror" value="{{ old('destination_country', $shipment->destination_country ?? 'Cambodia') }}">
                                        <x-form-error name="destination_country" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="shipper" role="tabpanel">
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="shipper_name" class="form-label">Shipper Name <span class="text-danger">*</span></label>
                                        <input type="text" id="shipper_name" name="shipper_name" class="form-control @error('shipper_name') is-invalid @enderror" value="{{ old('shipper_name', $shipment->shipper_name) }}" required>
                                        <x-form-error name="shipper_name" />
                                    </div>
                                    <div class="col-md-4">
                                        <label for="shipper_phone" class="form-label">Phone</label>
                                        <input type="text" id="shipper_phone" name="shipper_phone" class="form-control @error('shipper_phone') is-invalid @enderror" value="{{ old('shipper_phone', $shipment->shipper_phone) }}">
                                        <x-form-error name="shipper_phone" />
                                    </div>
                                    <div class="col-md-4">
                                        <label for="shipper_email" class="form-label">Email</label>
                                        <input type="email" id="shipper_email" name="shipper_email" class="form-control @error('shipper_email') is-invalid @enderror" value="{{ old('shipper_email', $shipment->shipper_email) }}">
                                        <x-form-error name="shipper_email" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="consignee" role="tabpanel">
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="consignee_name" class="form-label">Consignee Name <span class="text-danger">*</span></label>
                                        <input type="text" id="consignee_name" name="consignee_name" class="form-control @error('consignee_name') is-invalid @enderror" value="{{ old('consignee_name', $shipment->consignee_name) }}" required>
                                        <x-form-error name="consignee_name" />
                                    </div>
                                    <div class="col-md-4">
                                        <label for="consignee_phone" class="form-label">Phone</label>
                                        <input type="text" id="consignee_phone" name="consignee_phone" class="form-control @error('consignee_phone') is-invalid @enderror" value="{{ old('consignee_phone', $shipment->consignee_phone) }}">
                                        <x-form-error name="consignee_phone" />
                                    </div>
                                    <div class="col-md-4">
                                        <label for="consignee_email" class="form-label">Email</label>
                                        <input type="email" id="consignee_email" name="consignee_email" class="form-control @error('consignee_email') is-invalid @enderror" value="{{ old('consignee_email', $shipment->consignee_email) }}">
                                        <x-form-error name="consignee_email" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="cargo" role="tabpanel">
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label for="weight" class="form-label">Weight (kg)</label>
                                        <input type="number" step="0.01" min="0" id="weight" name="weight" class="form-control @error('weight') is-invalid @enderror" value="{{ old('weight', $shipment->weight) }}">
                                        <x-form-error name="weight" />
                                    </div>
                                    <div class="col-md-3">
                                        <label for="volume" class="form-label">Volume (m³)</label>
                                        <input type="number" step="0.01" min="0" id="volume" name="volume" class="form-control @error('volume') is-invalid @enderror" value="{{ old('volume', $shipment->volume) }}">
                                        <x-form-error name="volume" />
                                    </div>
                                    <div class="col-md-3">
                                        <label for="pieces" class="form-label">Pieces</label>
                                        <input type="number" min="1" id="pieces" name="pieces" class="form-control @error('pieces') is-invalid @enderror" value="{{ old('pieces', $shipment->pieces ?? 1) }}">
                                        <x-form-error name="pieces" />
                                    </div>
                                    <div class="col-md-3">
                                        <label for="declared_value" class="form-label">Declared Value</label>
                                        <input type="number" step="0.01" min="0" id="declared_value" name="declared_value" class="form-control @error('declared_value') is-invalid @enderror" value="{{ old('declared_value', $shipment->declared_value) }}">
                                        <x-form-error name="declared_value" />
                                    </div>
                                    <div class="col-md-3">
                                        <label for="currency" class="form-label">Currency</label>
                                        <select id="currency" name="currency" class="form-select @error('currency') is-invalid @enderror">
                                            <option value="USD" {{ old('currency', $shipment->currency ?? 'USD') === 'USD' ? 'selected' : '' }}>USD</option>
                                            <option value="KHR" {{ old('currency', $shipment->currency) === 'KHR' ? 'selected' : '' }}>KHR (Riel)</option>
                                            <option value="THB" {{ old('currency', $shipment->currency) === 'THB' ? 'selected' : '' }}>THB (Baht)</option>
                                        </select>
                                        <x-form-error name="currency" />
                                    </div>
                                    <div class="col-12">
                                        <label for="description" class="form-label">Cargo Description</label>
                                        <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="2">{{ old('description', $shipment->description) }}</textarea>
                                        <x-form-error name="description" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="options" role="tabpanel">
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input type="checkbox" id="is_express" name="is_express" class="form-check-input" value="1" {{ old('is_express', $shipment->is_express) ? 'checked' : '' }}>
                                            <label for="is_express" class="form-check-label fw-medium">Express Delivery</label>
                                            <small class="d-block text-muted">Priority handling and faster delivery</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input type="checkbox" id="is_insured" name="is_insured" class="form-check-input" value="1" {{ old('is_insured', $shipment->is_insured) ? 'checked' : '' }}>
                                            <label for="is_insured" class="form-check-label fw-medium">Insurance</label>
                                            <small class="d-block text-muted">Cargo insurance coverage</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4" id="insurance_amount_group" style="{{ old('is_insured', $shipment->is_insured) ? '' : 'display: none;' }}">
                                        <label for="insurance_amount" class="form-label">Insurance Amount ($)</label>
                                        <input type="number" step="0.01" min="0" id="insurance_amount" name="insurance_amount" class="form-control @error('insurance_amount') is-invalid @enderror" value="{{ old('insurance_amount', $shipment->insurance_amount) }}">
                                        <x-form-error name="insurance_amount" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg me-1"></i> Update Shipment
            </button>
            <a href="{{ route('shipments.show', $shipment) }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>

    @push('scripts')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            $(document).ready(function() {
                $('.select2').select2({
                    theme: 'bootstrap-5',
                    width: '100%'
                });

                $('#is_insured').change(function() {
                    $('#insurance_amount_group').toggle(this.checked);
                });
            });
        </script>
    @endpush
</x-admin-layout>

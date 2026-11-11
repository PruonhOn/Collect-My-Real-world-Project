@extends('layouts.admin')

@section('title', 'Create Package')
@section('subtitle', 'Add a new package')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Create Package</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('packages.index') }}">Packages</a></li>
                <li class="breadcrumb-item active">Create</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('packages.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<form action="{{ route('packages.store') }}" method="POST">
    @csrf
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-box me-2 text-primary"></i>Package Details</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Shipment <span class="text-danger">*</span></label>
                            <select class="form-select @error('shipment_id') is-invalid @enderror" name="shipment_id" id="shipmentSelect" required>
                                <option value="">Select Shipment</option>
                                @foreach($shipments as $shipment)
                                    <option value="{{ $shipment->id }}" {{ old('shipment_id') == $shipment->id ? 'selected' : '' }}>
                                        {{ $shipment->shipment_number }} - {{ $shipment->recipient_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('shipment_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Type <span class="text-danger">*</span></label>
                            <select class="form-select @error('type') is-invalid @enderror" name="type" required>
                                <option value="standard" {{ old('type') == 'standard' ? 'selected' : '' }}>Standard</option>
                                <option value="fragile" {{ old('type') == 'fragile' ? 'selected' : '' }}>Fragile</option>
                                <option value="oversized" {{ old('type') == 'oversized' ? 'selected' : '' }}>Oversized</option>
                                <option value="hazardous" {{ old('type') == 'hazardous' ? 'selected' : '' }}>Hazardous</option>
                                <option value="perishable" {{ old('type') == 'perishable' ? 'selected' : '' }}>Perishable</option>
                            </select>
                            @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Weight (kg) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('weight') is-invalid @enderror" name="weight" step="0.01" value="{{ old('weight') }}" required>
                            @error('weight') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Volume (m³)</label>
                            <input type="number" class="form-control @error('volume') is-invalid @enderror" name="volume" step="0.001" value="{{ old('volume') }}">
                            @error('volume') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Length (cm)</label>
                            <input type="number" class="form-control" name="length" step="0.01" value="{{ old('length') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Width (cm)</label>
                            <input type="number" class="form-control" name="width" step="0.01" value="{{ old('width') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Height (cm)</label>
                            <input type="number" class="form-control" name="height" step="0.01" value="{{ old('height') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Warehouse</label>
                            <select class="form-select" name="warehouse_id">
                                <option value="">Select Warehouse</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" {{ old('warehouse_id') == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Shelf Location</label>
                            <input type="text" class="form-control" name="shelf" value="{{ old('shelf') }}" placeholder="e.g., A-12-3">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Barcode</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="barcode" id="barcodeInput" value="{{ old('barcode') }}" placeholder="Auto-generated if empty">
                                <button type="button" class="btn btn-outline-secondary" onclick="generateBarcode()">
                                    <i class="bi bi-shuffle me-1"></i> Generate
                                </button>
                            </div>
                            <small class="text-muted">Leave blank to auto-generate on save.</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="2">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold">Preview</h6>
                </div>
                <div class="card-body text-center">
                    <div class="bg-light rounded p-4 mb-3">
                        <i class="bi bi-qr-code" style="font-size: 4rem; color: #333;" id="barcodePreview"></i>
                        <p class="mt-2 mb-0 fw-semibold" id="barcodeText">New Package</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('packages.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle me-1"></i> Create Package
        </button>
    </div>
</form>
@endsection

@push('scripts')
<script>
    function generateBarcode() {
        const code = 'PKG-' + Date.now().toString(36).toUpperCase() + Math.random().toString(36).substring(2, 6).toUpperCase();
        $('#barcodeInput').val(code);
        $('#barcodeText').text(code);
    }
</script>
@endpush

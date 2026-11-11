@extends('layouts.admin')

@section('title', 'Create Return')
@section('subtitle', 'Process a new return')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Create Return</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('returns.index') }}">Returns</a></li>
                <li class="breadcrumb-item active">Create</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('returns.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<form action="{{ route('returns.store') }}" method="POST">
    @csrf
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-arrow-return-left me-2 text-primary"></i>Return Details</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Shipment <span class="text-danger">*</span></label>
                            <select class="form-select @error('shipment_id') is-invalid @enderror" name="shipment_id" id="shipmentSelect" required>
                                <option value="">Select Shipment</option>
                                @foreach($shipments as $shipment)
                                    <option value="{{ $shipment->id }}" {{ old('shipment_id') == $shipment->id ? 'selected' : '' }}>{{ $shipment->shipment_number }}</option>
                                @endforeach
                            </select>
                            @error('shipment_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Customer</label>
                            <select class="form-select" name="customer_id" id="customerSelect">
                                <option value="">Select Customer</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Return Type <span class="text-danger">*</span></label>
                            <select class="form-select @error('type') is-invalid @enderror" name="type" required>
                                <option value="damaged" {{ old('type') == 'damaged' ? 'selected' : '' }}>Damaged</option>
                                <option value="wrong_item" {{ old('type') == 'wrong_item' ? 'selected' : '' }}>Wrong Item</option>
                                <option value="defective" {{ old('type') == 'defective' ? 'selected' : '' }}>Defective</option>
                                <option value="not_needed" {{ old('type') == 'not_needed' ? 'selected' : '' }}>Not Needed</option>
                                <option value="other" {{ old('type') == 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Refund Amount ($)</label>
                            <input type="number" class="form-control" name="refund_amount" step="0.01" value="{{ old('refund_amount') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Reason <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('reason') is-invalid @enderror" name="reason" rows="2" required>{{ old('reason') }}</textarea>
                            @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3">{{ old('description') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold">Return Items</h6>
                </div>
                <div class="card-body">
                    <div id="shipmentItems" class="text-muted">
                        Select a shipment to load items.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body text-center">
                    <i class="bi bi-arrow-return-left text-warning" style="font-size: 3rem;"></i>
                    <h6 class="mt-3">Process Return</h6>
                    <p class="text-muted small mb-0">Select a shipment and fill in return details.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('returns.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle me-1"></i> Create Return
        </button>
    </div>
</form>
@endsection

@push('scripts')
<script>
    $('#shipmentSelect').on('change', function() {
        const shipmentId = $(this).val();
        if (shipmentId) {
            fetch(`/admin/api/shipments/${shipmentId}/items`)
                .then(response => response.json())
                .then(data => {
                    let html = '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Name</th><th>Qty</th><th>Select</th></tr></thead><tbody>';
                    data.items.forEach(function(item) {
                        html += `<tr><td>${item.name}</td><td>${item.quantity}</td><td><input type="checkbox" name="return_items[]" value="${item.id}" class="form-check-input"></td></tr>`;
                    });
                    html += '</tbody></table></div>';
                    $('#shipmentItems').html(html);
                });
        } else {
            $('#shipmentItems').html('<p class="text-muted">Select a shipment to load items.</p>');
        }
    });
</script>
@endpush

@extends('layouts.admin')

@section('title', 'Create Pickup Request')
@section('subtitle', 'Schedule a new pickup')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Create Pickup Request</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('pickup-requests.index') }}">Pickup Requests</a></li>
                <li class="breadcrumb-item active">Create</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('pickup-requests.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<form action="{{ route('pickup-requests.store') }}" method="POST">
    @csrf
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-hand-index me-2 text-primary"></i>Pickup Details</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Customer <span class="text-danger">*</span></label>
                            <select class="form-select @error('customer_id') is-invalid @enderror" name="customer_id" id="customerSelect" required>
                                <option value="">Select Customer</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                                @endforeach
                            </select>
                            @error('customer_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Shipment (Optional)</label>
                            <select class="form-select" name="shipment_id" id="shipmentSelect">
                                <option value="">Select Shipment (optional)</option>
                                @foreach($shipments as $shipment)
                                    <option value="{{ $shipment->id }}" {{ old('shipment_id') == $shipment->id ? 'selected' : '' }}>
                                        {{ $shipment->shipment_number }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Pickup Address <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('pickup_address') is-invalid @enderror" name="pickup_address" value="{{ old('pickup_address') }}" required>
                            @error('pickup_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">City <span class="text-danger">*</span></label>
                            <select class="form-select @error('city') is-invalid @enderror" name="city" required>
                                <option value="">Select City</option>
                                @foreach($cities as $city)
                                    <option value="{{ $city }}" {{ old('city') == $city ? 'selected' : '' }}>{{ $city }}</option>
                                @endforeach
                            </select>
                            @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('contact_name') is-invalid @enderror" name="contact_name" value="{{ old('contact_name') }}" required>
                            @error('contact_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Phone <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('contact_phone') is-invalid @enderror" name="contact_phone" value="{{ old('contact_phone') }}" required>
                            @error('contact_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Scheduled Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('scheduled_date') is-invalid @enderror" name="scheduled_date" value="{{ old('scheduled_date') }}" required>
                            @error('scheduled_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Scheduled Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control @error('scheduled_time') is-invalid @enderror" name="scheduled_time" value="{{ old('scheduled_time') }}" required>
                            @error('scheduled_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="3" placeholder="Additional instructions...">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold">Summary</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">Fill in the details and submit to create a pickup request. A driver will be assigned automatically or manually.</p>
                    <div class="bg-light rounded p-3 text-center">
                        <i class="bi bi-hand-index text-primary" style="font-size: 2rem;"></i>
                        <p class="mt-2 mb-0 small text-muted">New pickup request will be created in <strong>Pending</strong> status</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('pickup-requests.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle me-1"></i> Create Request
        </button>
    </div>
</form>
@endsection

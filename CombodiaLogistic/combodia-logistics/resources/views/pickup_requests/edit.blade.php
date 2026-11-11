@extends('layouts.admin')

@section('title', 'Edit Pickup Request')
@section('subtitle', 'Edit request #' . $pickupRequest->request_number)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Edit Pickup Request</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('pickup-requests.index') }}">Pickup Requests</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('pickup-requests.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<form action="{{ route('pickup-requests.update', $pickupRequest) }}" method="POST">
    @csrf
    @method('PUT')
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
                            <select class="form-select" name="customer_id" required>
                                <option value="">Select Customer</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" {{ old('customer_id', $pickupRequest->customer_id) == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Shipment (Optional)</label>
                            <select class="form-select" name="shipment_id">
                                <option value="">Select Shipment</option>
                                @foreach($shipments as $shipment)
                                    <option value="{{ $shipment->id }}" {{ old('shipment_id', $pickupRequest->shipment_id) == $shipment->id ? 'selected' : '' }}>{{ $shipment->shipment_number }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Pickup Address <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="pickup_address" value="{{ old('pickup_address', $pickupRequest->pickup_address) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">City <span class="text-danger">*</span></label>
                            <select class="form-select" name="city" required>
                                <option value="">Select City</option>
                                @foreach($cities as $city)
                                    <option value="{{ $city }}" {{ old('city', $pickupRequest->city) == $city ? 'selected' : '' }}>{{ $city }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="contact_name" value="{{ old('contact_name', $pickupRequest->contact_name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Phone <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="contact_phone" value="{{ old('contact_phone', $pickupRequest->contact_phone) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Scheduled Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="scheduled_date" value="{{ old('scheduled_date', $pickupRequest->scheduled_date ? \Carbon\Carbon::parse($pickupRequest->scheduled_date)->format('Y-m-d') : '') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Scheduled Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" name="scheduled_time" value="{{ old('scheduled_time', $pickupRequest->scheduled_time) }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="3">{{ old('notes', $pickupRequest->notes) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold">Assignment</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Assign Driver</label>
                        <select class="form-select" name="driver_id">
                            <option value="">Select Driver</option>
                            @foreach($drivers as $driver)
                                <option value="{{ $driver->id }}" {{ old('driver_id', $pickupRequest->driver_id) == $driver->id ? 'selected' : '' }}>{{ $driver->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Assign Vehicle</label>
                        <select class="form-select" name="vehicle_id">
                            <option value="">Select Vehicle</option>
                            @foreach($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" {{ old('vehicle_id', $pickupRequest->vehicle_id) == $vehicle->id ? 'selected' : '' }}>{{ $vehicle->plate_number }} - {{ $vehicle->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="pending" {{ old('status', $pickupRequest->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="confirmed" {{ old('status', $pickupRequest->status) == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                            <option value="in_progress" {{ old('status', $pickupRequest->status) == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="completed" {{ old('status', $pickupRequest->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ old('status', $pickupRequest->status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-1"></i> Update Request
                </button>
                @if($pickupRequest->status !== 'cancelled')
                <a href="{{ route('pickup-requests.confirm', $pickupRequest) }}" class="btn btn-success" onclick="event.preventDefault(); document.getElementById('confirmForm').submit();">
                    <i class="bi bi-check2-all me-1"></i> Confirm Pickup
                </a>
                <form id="confirmForm" action="{{ route('pickup-requests.confirm', $pickupRequest) }}" method="POST" class="d-none">@csrf</form>
                <a href="{{ route('pickup-requests.cancel', $pickupRequest) }}" class="btn btn-danger" onclick="event.preventDefault(); document.getElementById('cancelForm').submit();">
                    <i class="bi bi-x-circle me-1"></i> Cancel Request
                </a>
                <form id="cancelForm" action="{{ route('pickup-requests.cancel', $pickupRequest) }}" method="POST" class="d-none">@csrf</form>
                @endif
            </div>
        </div>
    </div>
</form>
@endsection

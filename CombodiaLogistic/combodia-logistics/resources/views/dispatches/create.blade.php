@extends('layouts.admin')

@section('title', 'Create Dispatch')
@section('subtitle', 'Create a new dispatch')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Create Dispatch</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('dispatches.index') }}">Dispatches</a></li>
                <li class="breadcrumb-item active">Create</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('dispatches.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<form action="{{ route('dispatches.store') }}" method="POST">
    @csrf
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-diagram-3 me-2 text-primary"></i>Dispatch Details</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Shipment <span class="text-danger">*</span></label>
                            <select class="form-select @error('shipment_id') is-invalid @enderror" name="shipment_id" required>
                                <option value="">Select Shipment</option>
                                @foreach($shipments as $shipment)
                                    <option value="{{ $shipment->id }}" {{ old('shipment_id') == $shipment->id ? 'selected' : '' }}>
                                        {{ $shipment->shipment_number }} - {{ $shipment->recipient_city }}
                                    </option>
                                @endforeach
                            </select>
                            @error('shipment_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Route <span class="text-danger">*</span></label>
                            <select class="form-select @error('route_id') is-invalid @enderror" name="route_id" required>
                                <option value="">Select Route</option>
                                @foreach($routes as $route)
                                    <option value="{{ $route->id }}" {{ old('route_id') == $route->id ? 'selected' : '' }}>
                                        {{ $route->code }} - {{ $route->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('route_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Scheduled Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('scheduled_date') is-invalid @enderror" name="scheduled_date" value="{{ old('scheduled_date', date('Y-m-d')) }}" required>
                            @error('scheduled_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Scheduled Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control @error('scheduled_time') is-invalid @enderror" name="scheduled_time" value="{{ old('scheduled_time') }}" required>
                            @error('scheduled_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="3" placeholder="Dispatch notes...">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold">Driver Assignment</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">Select one or more drivers for this dispatch.</p>
                    <div class="mb-3">
                        <label class="form-label">Available Drivers</label>
                        @foreach($drivers as $driver)
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="driver_ids[]" value="{{ $driver->id }}" id="driver{{ $driver->id }}" {{ in_array($driver->id, old('driver_ids', [])) ? 'checked' : '' }}>
                            <label class="form-check-label" for="driver{{ $driver->id }}">
                                <strong>{{ $driver->name }}</strong>
                                <br><small class="text-muted">{{ $driver->phone ?? '' }}</small>
                            </label>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('dispatches.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle me-1"></i> Create Dispatch
        </button>
    </div>
</form>
@endsection

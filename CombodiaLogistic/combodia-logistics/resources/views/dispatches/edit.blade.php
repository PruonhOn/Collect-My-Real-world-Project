@extends('layouts.admin')

@section('title', 'Edit Dispatch')
@section('subtitle', 'Edit dispatch #' . $dispatch->dispatch_number)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Edit Dispatch</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('dispatches.index') }}">Dispatches</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('dispatches.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<form action="{{ route('dispatches.update', $dispatch) }}" method="POST">
    @csrf
    @method('PUT')
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
                            <select class="form-select" name="shipment_id" required>
                                <option value="">Select Shipment</option>
                                @foreach($shipments as $shipment)
                                    <option value="{{ $shipment->id }}" {{ old('shipment_id', $dispatch->shipment_id) == $shipment->id ? 'selected' : '' }}>
                                        {{ $shipment->shipment_number }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Route <span class="text-danger">*</span></label>
                            <select class="form-select" name="route_id" required>
                                <option value="">Select Route</option>
                                @foreach($routes as $route)
                                    <option value="{{ $route->id }}" {{ old('route_id', $dispatch->route_id) == $route->id ? 'selected' : '' }}>
                                        {{ $route->code }} - {{ $route->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Scheduled Date</label>
                            <input type="date" class="form-control" name="scheduled_date" value="{{ old('scheduled_date', $dispatch->scheduled_date ? \Carbon\Carbon::parse($dispatch->scheduled_date)->format('Y-m-d') : '') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Scheduled Time</label>
                            <input type="time" class="form-control" name="scheduled_time" value="{{ old('scheduled_time', $dispatch->scheduled_time) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="pending" {{ old('status', $dispatch->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="assigned" {{ old('status', $dispatch->status) == 'assigned' ? 'selected' : '' }}>Assigned</option>
                                <option value="in_transit" {{ old('status', $dispatch->status) == 'in_transit' ? 'selected' : '' }}>In Transit</option>
                                <option value="completed" {{ old('status', $dispatch->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="cancelled" {{ old('status', $dispatch->status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="3">{{ old('notes', $dispatch->notes) }}</textarea>
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
                    @foreach($drivers as $driver)
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="driver_ids[]" value="{{ $driver->id }}" id="driver{{ $driver->id }}" {{ $dispatch->drivers->contains($driver->id) ? 'checked' : '' }}>
                        <label class="form-check-label" for="driver{{ $driver->id }}">
                            <strong>{{ $driver->name }}</strong>
                        </label>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-1"></i> Update Dispatch
                </button>
                @if($dispatch->status === 'assigned' || $dispatch->status === 'pending')
                <a href="{{ route('dispatches.load', $dispatch) }}" class="btn btn-warning" onclick="event.preventDefault(); document.getElementById('loadForm').submit();">
                    <i class="bi bi-truck me-1"></i> Load & Dispatch
                </a>
                <form id="loadForm" action="{{ route('dispatches.load', $dispatch) }}" method="POST" class="d-none">@csrf</form>
                @endif
                @if($dispatch->status === 'in_transit')
                <a href="{{ route('dispatches.complete', $dispatch) }}" class="btn btn-success" onclick="event.preventDefault(); document.getElementById('completeForm').submit();">
                    <i class="bi bi-check2-all me-1"></i> Mark Complete
                </a>
                <form id="completeForm" action="{{ route('dispatches.complete', $dispatch) }}" method="POST" class="d-none">@csrf</form>
                @endif
            </div>
        </div>
    </div>
</form>
@endsection

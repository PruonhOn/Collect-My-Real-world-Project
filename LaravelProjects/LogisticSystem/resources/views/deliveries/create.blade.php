@extends('layouts.admin')

@section('title', 'Create Delivery - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('deliveries.index') }}">Deliveries</a></li>
    <li class="breadcrumb-item active" aria-current="page">Create</li>
@endsection

@section('page-title', 'Create Delivery')
@section('page-subtitle', 'Schedule a new delivery')

@section('content')
    <div class="row">
        <div class="col-lg-10">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('deliveries.store') }}" method="POST">
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="shipment_id" class="form-label">Shipment <span class="text-danger">*</span></label>
                                <select id="shipment_id" name="shipment_id" class="form-select select2 @error('shipment_id') is-invalid @enderror" required>
                                    <option value="">Select Shipment...</option>
                                    @foreach($shipments as $shipment)
                                        <option value="{{ $shipment->id }}" {{ old('shipment_id') == $shipment->id ? 'selected' : '' }}>
                                            #{{ $shipment->shipment_number }} - {{ $shipment->customer?->name ?? 'N/A' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('shipment_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="route_id" class="form-label">Route <span class="text-danger">*</span></label>
                                <select id="route_id" name="route_id" class="form-select select2 @error('route_id') is-invalid @enderror" required>
                                    <option value="">Select Route...</option>
                                    @foreach($routes as $route)
                                        <option value="{{ $route->id }}" {{ old('route_id') == $route->id ? 'selected' : '' }}>
                                            {{ $route->code }} - {{ $route->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('route_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="driver_id" class="form-label">Driver <span class="text-danger">*</span></label>
                                <select id="driver_id" name="driver_id" class="form-select select2 @error('driver_id') is-invalid @enderror" required>
                                    <option value="">Select Driver...</option>
                                    @foreach($drivers as $driver)
                                        <option value="{{ $driver->id }}" {{ old('driver_id') == $driver->id ? 'selected' : '' }}>
                                            {{ $driver->name }} ({{ $driver->license_number ?? 'N/A' }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('driver_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="vehicle_id" class="form-label">Vehicle <span class="text-danger">*</span></label>
                                <select id="vehicle_id" name="vehicle_id" class="form-select select2 @error('vehicle_id') is-invalid @enderror" required>
                                    <option value="">Select Vehicle...</option>
                                    @foreach($vehicles as $vehicle)
                                        <option value="{{ $vehicle->id }}" {{ old('vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                                            {{ $vehicle->plate_number }} - {{ $vehicle->make }} {{ $vehicle->model }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('vehicle_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="scheduled_date" class="form-label">Scheduled Date <span class="text-danger">*</span></label>
                                <input type="datetime-local" id="scheduled_date" name="scheduled_date" class="form-control @error('scheduled_date') is-invalid @enderror" value="{{ old('scheduled_date') }}" required>
                                @error('scheduled_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="estimated_delivery_date" class="form-label">Estimated Delivery Date</label>
                                <input type="datetime-local" id="estimated_delivery_date" name="estimated_delivery_date" class="form-control @error('estimated_delivery_date') is-invalid @enderror" value="{{ old('estimated_delivery_date') }}">
                                @error('estimated_delivery_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea id="notes" name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2" placeholder="Optional delivery notes...">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <hr class="my-4">

                        <h6 class="fw-bold mb-3">Delivery Stops</h6>
                        <div id="stopsContainer">
                            <div class="stop-item card bg-light mb-2">
                                <div class="card-body py-2">
                                    <div class="row g-2 align-items-end">
                                        <div class="col-md-4">
                                            <label class="form-label small">Location</label>
                                            <input type="text" name="stops[0][location]" class="form-control form-control-sm" placeholder="e.g. 123 Street, PP" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small">Stop Type</label>
                                            <select name="stops[0][type]" class="form-select form-select-sm">
                                                <option value="pickup">Pickup</option>
                                                <option value="dropoff" selected>Dropoff</option>
                                                <option value="waypoint">Waypoint</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small">Scheduled Time</label>
                                            <input type="datetime-local" name="stops[0][scheduled_time]" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small">Notes</label>
                                            <input type="text" name="stops[0][notes]" class="form-control form-control-sm" placeholder="Optional">
                                        </div>
                                        <div class="col-md-1 d-flex align-items-center">
                                            <button type="button" class="btn btn-outline-danger btn-sm remove-stop" disabled>
                                                <i class="bi bi-dash-lg"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" id="addStopBtn" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-plus-lg"></i> Add Stop
                        </button>

                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Create Delivery
                            </button>
                            <a href="{{ route('deliveries.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });
        });

        let stopIndex = 1;
        document.getElementById('addStopBtn').addEventListener('click', function() {
            const container = document.getElementById('stopsContainer');
            const template = `
                <div class="stop-item card bg-light mb-2">
                    <div class="card-body py-2">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label small">Location</label>
                                <input type="text" name="stops[${stopIndex}][location]" class="form-control form-control-sm" placeholder="e.g. 123 Street, PP" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Stop Type</label>
                                <select name="stops[${stopIndex}][type]" class="form-select form-select-sm">
                                    <option value="pickup">Pickup</option>
                                    <option value="dropoff" selected>Dropoff</option>
                                    <option value="waypoint">Waypoint</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Scheduled Time</label>
                                <input type="datetime-local" name="stops[${stopIndex}][scheduled_time]" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Notes</label>
                                <input type="text" name="stops[${stopIndex}][notes]" class="form-control form-control-sm" placeholder="Optional">
                            </div>
                            <div class="col-md-1 d-flex align-items-center">
                                <button type="button" class="btn btn-outline-danger btn-sm remove-stop">
                                    <i class="bi bi-dash-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', template);
            stopIndex++;

            document.querySelectorAll('.remove-stop').forEach(btn => btn.disabled = false);
        });

        document.addEventListener('click', function(e) {
            if (e.target.closest('.remove-stop')) {
                const stopItem = e.target.closest('.stop-item');
                if (document.querySelectorAll('.stop-item').length > 1) {
                    stopItem.remove();
                }
            }
        });
    </script>
@endsection

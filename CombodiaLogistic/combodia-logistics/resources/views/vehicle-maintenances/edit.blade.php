@extends('layouts.admin')

@section('title', 'Edit Maintenance')
@section('subtitle', 'Update maintenance record')

@section('content')
<x-breadcrumb :items="[['label' => 'Vehicle Maintenance', 'url' => route('vehicle-maintenances.index')], ['label' => 'Edit Maintenance']]" />

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Edit Maintenance Record</h4>
        <p class="text-muted mb-0">Update details for {{ $maintenance->vehicle->plate_number ?? 'maintenance' }} #{{ $maintenance->id }}</p>
    </div>
    <a href="{{ route('vehicle-maintenances.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to List
    </a>
</div>

<form action="{{ route('vehicle-maintenances.update', $maintenance->id) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-car-front me-2"></i>Maintenance Information</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="vehicle_id" class="form-label">Vehicle <span class="text-danger">*</span></label>
                            <select class="form-select @error('vehicle_id') is-invalid @enderror" id="vehicle_id" name="vehicle_id" required>
                                <option value="">Select Vehicle</option>
                                @foreach($vehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}" {{ old('vehicle_id', $maintenance->vehicle_id) == $vehicle->id ? 'selected' : '' }}>
                                        {{ $vehicle->plate_number }} - {{ $vehicle->brand }} {{ $vehicle->model }}
                                    </option>
                                @endforeach
                            </select>
                            @error('vehicle_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="driver_id" class="form-label">Driver</label>
                            <select class="form-select @error('driver_id') is-invalid @enderror" id="driver_id" name="driver_id">
                                <option value="">Select Driver</option>
                                @foreach($drivers as $driver)
                                    <option value="{{ $driver->id }}" {{ old('driver_id', $maintenance->driver_id) == $driver->id ? 'selected' : '' }}>
                                        {{ $driver->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('driver_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="maintenance_type" class="form-label">Maintenance Type <span class="text-danger">*</span></label>
                            <select class="form-select @error('maintenance_type') is-invalid @enderror" id="maintenance_type" name="maintenance_type" required>
                                <option value="">Select Type</option>
                                <option value="oil_change" {{ old('maintenance_type', $maintenance->maintenance_type) === 'oil_change' ? 'selected' : '' }}>Oil Change</option>
                                <option value="tire_replacement" {{ old('maintenance_type', $maintenance->maintenance_type) === 'tire_replacement' ? 'selected' : '' }}>Tire Replacement</option>
                                <option value="brake_service" {{ old('maintenance_type', $maintenance->maintenance_type) === 'brake_service' ? 'selected' : '' }}>Brake Service</option>
                                <option value="engine_repair" {{ old('maintenance_type', $maintenance->maintenance_type) === 'engine_repair' ? 'selected' : '' }}>Engine Repair</option>
                                <option value="transmission" {{ old('maintenance_type', $maintenance->maintenance_type) === 'transmission' ? 'selected' : '' }}>Transmission</option>
                                <option value="battery_replacement" {{ old('maintenance_type', $maintenance->maintenance_type) === 'battery_replacement' ? 'selected' : '' }}>Battery Replacement</option>
                                <option value="alignment" {{ old('maintenance_type', $maintenance->maintenance_type) === 'alignment' ? 'selected' : '' }}>Wheel Alignment</option>
                                <option value="general_service" {{ old('maintenance_type', $maintenance->maintenance_type) === 'general_service' ? 'selected' : '' }}>General Service</option>
                                <option value="inspection" {{ old('maintenance_type', $maintenance->maintenance_type) === 'inspection' ? 'selected' : '' }}>Inspection</option>
                                <option value="other" {{ old('maintenance_type', $maintenance->maintenance_type) === 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('maintenance_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="pending" {{ old('status', $maintenance->status) === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="in_progress" {{ old('status', $maintenance->status) === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                <option value="completed" {{ old('status', $maintenance->status) === 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="cancelled" {{ old('status', $maintenance->status) === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" placeholder="Describe the maintenance work..." required>{{ old('description', $maintenance->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-calendar-event me-2"></i>Schedule & Cost</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="scheduled_date" class="form-label">Scheduled Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('scheduled_date') is-invalid @enderror" id="scheduled_date" name="scheduled_date" value="{{ old('scheduled_date', $maintenance->scheduled_date ? \Carbon\Carbon::parse($maintenance->scheduled_date)->format('Y-m-d') : '') }}" required>
                            @error('scheduled_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="completed_date" class="form-label">Completed Date</label>
                            <input type="date" class="form-control @error('completed_date') is-invalid @enderror" id="completed_date" name="completed_date" value="{{ old('completed_date', $maintenance->completed_date ? \Carbon\Carbon::parse($maintenance->completed_date)->format('Y-m-d') : '') }}">
                            @error('completed_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="cost" class="form-label">Cost ($)</label>
                            <input type="number" class="form-control @error('cost') is-invalid @enderror" id="cost" name="cost" value="{{ old('cost', $maintenance->cost) }}" placeholder="0.00" min="0" step="0.01">
                            @error('cost')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="performed_by" class="form-label">Performed By</label>
                            <input type="text" class="form-control @error('performed_by') is-invalid @enderror" id="performed_by" name="performed_by" value="{{ old('performed_by', $maintenance->performed_by) }}" placeholder="e.g. ABC Auto Shop">
                            @error('performed_by')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-journal-text me-2"></i>Additional Notes</h6>
                </div>
                <div class="card-body">
                    <div class="form-floating">
                        <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="4" placeholder="Additional notes...">{{ old('notes', $maintenance->notes) }}</textarea>
                        <label for="notes">Notes (optional)</label>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-info-circle me-2"></i>Summary</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3 pb-3 border-bottom">
                        <small class="text-muted">Selected Vehicle</small>
                        <p class="mb-0 fw-medium" id="selectedVehicleInfo">{{ $maintenance->vehicle->plate_number ?? '-' }}</p>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <small class="text-muted">Assigned Driver</small>
                        <p class="mb-0 fw-medium" id="selectedDriverInfo">{{ $maintenance->driver->name ?? '-' }}</p>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <small class="text-muted">Maintenance Type</small>
                        <p class="mb-0 fw-medium" id="selectedTypeInfo">{{ ucfirst(str_replace('_', ' ', $maintenance->maintenance_type)) }}</p>
                    </div>
                    <div>
                        <small class="text-muted">Created</small>
                        <p class="mb-0 fw-medium">{{ $maintenance->created_at->format('d M Y, h:i A') }}</p>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> Update Maintenance
                </button>
                <a href="{{ route('vehicle-maintenances.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg me-1"></i> Cancel
                </a>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#vehicle_id').on('change', function() {
            var text = $(this).find('option:selected').text();
            $('#selectedVehicleInfo').text($(this).val() ? text : '-');
        });
        $('#driver_id').on('change', function() {
            var text = $(this).find('option:selected').text();
            $('#selectedDriverInfo').text($(this).val() ? text : '-');
        });
        $('#maintenance_type').on('change', function() {
            var text = $(this).find('option:selected').text();
            $('#selectedTypeInfo').text($(this).val() ? text : '-');
        });
    });
</script>
@endpush

@extends('layouts.admin')

@section('title', 'Edit Maintenance - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('vehicles.index') }}">Fleet</a></li>
    <li class="breadcrumb-item"><a href="{{ route('vehicle-maintenances.index') }}">Maintenance</a></li>
    <li class="breadcrumb-item active" aria-current="page">Edit Record</li>
@endsection

@section('page-title', 'Edit Maintenance Record')
@section('page-subtitle')
    {{ $maintenance->vehicle->plate_number ?? 'Vehicle' }}
    — {{ ucwords(str_replace('_', ' ', $maintenance->maintenance_type ?? '')) }}
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('vehicle-maintenances.update', $maintenance) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="vehicle_id" class="form-label">Vehicle <span class="text-danger">*</span></label>
                                <select id="vehicle_id" name="vehicle_id"
                                        class="form-select select2 @error('vehicle_id') is-invalid @enderror" required>
                                    <option value="">Select Vehicle</option>
                                    @foreach($vehicles as $vehicle)
                                        <option value="{{ $vehicle->id }}"
                                            {{ old('vehicle_id', $maintenance->vehicle_id) == $vehicle->id ? 'selected' : '' }}>
                                            {{ $vehicle->plate_number }} — {{ $vehicle->make }} {{ $vehicle->model }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('vehicle_id') <x-form-error name="vehicle_id" /> @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="maintenance_type" class="form-label">Maintenance Type <span class="text-danger">*</span></label>
                                <select id="maintenance_type" name="maintenance_type"
                                        class="form-select @error('maintenance_type') is-invalid @enderror" required>
                                    <option value="">Select Type</option>
                                    <option value="routine" {{ old('maintenance_type', $maintenance->maintenance_type) == 'routine' ? 'selected' : '' }}>Routine Service</option>
                                    <option value="oil_change" {{ old('maintenance_type', $maintenance->maintenance_type) == 'oil_change' ? 'selected' : '' }}>Oil Change</option>
                                    <option value="tire_replacement" {{ old('maintenance_type', $maintenance->maintenance_type) == 'tire_replacement' ? 'selected' : '' }}>Tire Replacement</option>
                                    <option value="brake_service" {{ old('maintenance_type', $maintenance->maintenance_type) == 'brake_service' ? 'selected' : '' }}>Brake Service</option>
                                    <option value="engine_repair" {{ old('maintenance_type', $maintenance->maintenance_type) == 'engine_repair' ? 'selected' : '' }}>Engine Repair</option>
                                    <option value="transmission" {{ old('maintenance_type', $maintenance->maintenance_type) == 'transmission' ? 'selected' : '' }}>Transmission</option>
                                    <option value="electrical" {{ old('maintenance_type', $maintenance->maintenance_type) == 'electrical' ? 'selected' : '' }}>Electrical</option>
                                    <option value="ac_service" {{ old('maintenance_type', $maintenance->maintenance_type) == 'ac_service' ? 'selected' : '' }}>AC Service</option>
                                    <option value="inspection" {{ old('maintenance_type', $maintenance->maintenance_type) == 'inspection' ? 'selected' : '' }}>Inspection</option>
                                    <option value="other" {{ old('maintenance_type', $maintenance->maintenance_type) == 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('maintenance_type') <x-form-error name="maintenance_type" /> @enderror
                            </div>

                            <div class="col-12">
                                <label for="description" class="form-label">Description</label>
                                <textarea id="description" name="description" rows="3"
                                          class="form-control @error('description') is-invalid @enderror">{{ old('description', $maintenance->description) }}</textarea>
                                @error('description') <x-form-error name="description" /> @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="maintenance_date" class="form-label">Maintenance Date <span class="text-danger">*</span></label>
                                <input type="date" id="maintenance_date" name="maintenance_date"
                                       class="form-control @error('maintenance_date') is-invalid @enderror"
                                       value="{{ old('maintenance_date', $maintenance->maintenance_date?->format('Y-m-d')) }}" required>
                                @error('maintenance_date') <x-form-error name="maintenance_date" /> @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="next_maintenance_date" class="form-label">Next Maintenance Date</label>
                                <input type="date" id="next_maintenance_date" name="next_maintenance_date"
                                       class="form-control @error('next_maintenance_date') is-invalid @enderror"
                                       value="{{ old('next_maintenance_date', $maintenance->next_maintenance_date?->format('Y-m-d')) }}">
                                @error('next_maintenance_date') <x-form-error name="next_maintenance_date" /> @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="cost" class="form-label">Cost ($) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" id="cost" name="cost"
                                       class="form-control @error('cost') is-invalid @enderror"
                                       value="{{ old('cost', $maintenance->cost) }}" required>
                                @error('cost') <x-form-error name="cost" /> @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="vendor" class="form-label">Vendor / Service Provider</label>
                                <input type="text" id="vendor" name="vendor"
                                       class="form-control @error('vendor') is-invalid @enderror"
                                       value="{{ old('vendor', $maintenance->vendor) }}">
                                @error('vendor') <x-form-error name="vendor" /> @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <select id="status" name="status"
                                        class="form-select @error('status') is-invalid @enderror">
                                    <option value="pending" {{ old('status', $maintenance->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="in-progress" {{ old('status', $maintenance->status) == 'in-progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="completed" {{ old('status', $maintenance->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                                    <option value="cancelled" {{ old('status', $maintenance->status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                </select>
                                @error('status') <x-form-error name="status" /> @enderror
                            </div>
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Update Record
                            </button>
                            <a href="{{ route('vehicle-maintenances.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            $('#vehicle_id').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Select Vehicle'
            });
        });
    </script>
@endpush

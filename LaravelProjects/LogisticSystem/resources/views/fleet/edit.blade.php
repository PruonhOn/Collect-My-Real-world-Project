@extends('layouts.admin')

@section('title', 'Edit Vehicle - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('vehicles.index') }}">Fleet</a></li>
    <li class="breadcrumb-item"><a href="{{ route('vehicles.show', $vehicle) }}">{{ $vehicle->code }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">Edit</li>
@endsection

@section('page-title', 'Edit Vehicle')
@section('page-subtitle', $vehicle->plate_number . ' — ' . $vehicle->make . ' ' . $vehicle->model)

@section('content')
    <div class="row">
        <div class="col-lg-9">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('vehicles.update', $vehicle) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <h6 class="fw-bold text-primary border-bottom pb-2">Basic Information</h6>

                            <div class="col-md-3">
                                <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
                                <input type="text" id="code" name="code"
                                       class="form-control @error('code') is-invalid @enderror"
                                       value="{{ old('code', $vehicle->code) }}" required>
                                @error('code') <x-form-error name="code" /> @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="plate_number" class="form-label">Plate # <span class="text-danger">*</span></label>
                                <input type="text" id="plate_number" name="plate_number"
                                       class="form-control @error('plate_number') is-invalid @enderror"
                                       value="{{ old('plate_number', $vehicle->plate_number) }}" required>
                                @error('plate_number') <x-form-error name="plate_number" /> @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="vehicle_type_id" class="form-label">Vehicle Type</label>
                                <select id="vehicle_type_id" name="vehicle_type_id"
                                        class="form-select select2 @error('vehicle_type_id') is-invalid @enderror">
                                    <option value="">Select Type</option>
                                    @foreach($vehicleTypes as $type)
                                        <option value="{{ $type->id }}" {{ old('vehicle_type_id', $vehicle->vehicle_type_id) == $type->id ? 'selected' : '' }}>
                                            {{ $type->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('vehicle_type_id') <x-form-error name="vehicle_type_id" /> @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select id="status" name="status"
                                        class="form-select @error('status') is-invalid @enderror">
                                    <option value="active" {{ old('status', $vehicle->status) == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="in-maintenance" {{ old('status', $vehicle->status) == 'in-maintenance' ? 'selected' : '' }}>In Maintenance</option>
                                    <option value="out-of-service" {{ old('status', $vehicle->status) == 'out-of-service' ? 'selected' : '' }}>Out of Service</option>
                                    <option value="retired" {{ old('status', $vehicle->status) == 'retired' ? 'selected' : '' }}>Retired</option>
                                </select>
                                @error('status') <x-form-error name="status" /> @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="make" class="form-label">Make <span class="text-danger">*</span></label>
                                <input type="text" id="make" name="make"
                                       class="form-control @error('make') is-invalid @enderror"
                                       value="{{ old('make', $vehicle->make) }}" required>
                                @error('make') <x-form-error name="make" /> @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="model" class="form-label">Model <span class="text-danger">*</span></label>
                                <input type="text" id="model" name="model"
                                       class="form-control @error('model') is-invalid @enderror"
                                       value="{{ old('model', $vehicle->model) }}" required>
                                @error('model') <x-form-error name="model" /> @enderror
                            </div>

                            <div class="col-md-2">
                                <label for="year" class="form-label">Year</label>
                                <input type="number" id="year" name="year" min="1990" max="{{ date('Y') + 5 }}"
                                       class="form-control @error('year') is-invalid @enderror"
                                       value="{{ old('year', $vehicle->year) }}">
                                @error('year') <x-form-error name="year" /> @enderror
                            </div>

                            <div class="col-md-2">
                                <label for="color" class="form-label">Color</label>
                                <input type="text" id="color" name="color"
                                       class="form-control @error('color') is-invalid @enderror"
                                       value="{{ old('color', $vehicle->color) }}">
                                @error('color') <x-form-error name="color" /> @enderror
                            </div>

                            <h6 class="fw-bold text-primary border-bottom pb-2 mt-2">Capacity & Fuel</h6>

                            <div class="col-md-3">
                                <label for="capacity_weight" class="form-label">Weight Capacity (kg)</label>
                                <input type="number" step="0.01" id="capacity_weight" name="capacity_weight"
                                       class="form-control @error('capacity_weight') is-invalid @enderror"
                                       value="{{ old('capacity_weight', $vehicle->capacity_weight) }}">
                                @error('capacity_weight') <x-form-error name="capacity_weight" /> @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="capacity_volume" class="form-label">Volume Capacity (m³)</label>
                                <input type="number" step="0.01" id="capacity_volume" name="capacity_volume"
                                       class="form-control @error('capacity_volume') is-invalid @enderror"
                                       value="{{ old('capacity_volume', $vehicle->capacity_volume) }}">
                                @error('capacity_volume') <x-form-error name="capacity_volume" /> @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="fuel_type" class="form-label">Fuel Type</label>
                                <select id="fuel_type" name="fuel_type"
                                        class="form-select @error('fuel_type') is-invalid @enderror">
                                    <option value="">Select</option>
                                    <option value="diesel" {{ old('fuel_type', $vehicle->fuel_type) == 'diesel' ? 'selected' : '' }}>Diesel</option>
                                    <option value="petrol" {{ old('fuel_type', $vehicle->fuel_type) == 'petrol' ? 'selected' : '' }}>Petrol</option>
                                    <option value="electric" {{ old('fuel_type', $vehicle->fuel_type) == 'electric' ? 'selected' : '' }}>Electric</option>
                                    <option value="hybrid" {{ old('fuel_type', $vehicle->fuel_type) == 'hybrid' ? 'selected' : '' }}>Hybrid</option>
                                    <option value="cng" {{ old('fuel_type', $vehicle->fuel_type) == 'cng' ? 'selected' : '' }}>CNG</option>
                                </select>
                                @error('fuel_type') <x-form-error name="fuel_type" /> @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="fuel_consumption" class="form-label">Consumption (L/100km)</label>
                                <input type="number" step="0.01" id="fuel_consumption" name="fuel_consumption"
                                       class="form-control @error('fuel_consumption') is-invalid @enderror"
                                       value="{{ old('fuel_consumption', $vehicle->fuel_consumption) }}">
                                @error('fuel_consumption') <x-form-error name="fuel_consumption" /> @enderror
                            </div>

                            <h6 class="fw-bold text-primary border-bottom pb-2 mt-2">Registration & Insurance</h6>

                            <div class="col-md-3">
                                <label for="insurance_number" class="form-label">Insurance #</label>
                                <input type="text" id="insurance_number" name="insurance_number"
                                       class="form-control @error('insurance_number') is-invalid @enderror"
                                       value="{{ old('insurance_number', $vehicle->insurance_number) }}">
                                @error('insurance_number') <x-form-error name="insurance_number" /> @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="insurance_expiry" class="form-label">Insurance Expiry</label>
                                <input type="date" id="insurance_expiry" name="insurance_expiry"
                                       class="form-control @error('insurance_expiry') is-invalid @enderror"
                                       value="{{ old('insurance_expiry', $vehicle->insurance_expiry?->format('Y-m-d')) }}">
                                @error('insurance_expiry') <x-form-error name="insurance_expiry" /> @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="registration_number" class="form-label">Registration #</label>
                                <input type="text" id="registration_number" name="registration_number"
                                       class="form-control @error('registration_number') is-invalid @enderror"
                                       value="{{ old('registration_number', $vehicle->registration_number) }}">
                                @error('registration_number') <x-form-error name="registration_number" /> @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="registration_expiry" class="form-label">Registration Expiry</label>
                                <input type="date" id="registration_expiry" name="registration_expiry"
                                       class="form-control @error('registration_expiry') is-invalid @enderror"
                                       value="{{ old('registration_expiry', $vehicle->registration_expiry?->format('Y-m-d')) }}">
                                @error('registration_expiry') <x-form-error name="registration_expiry" /> @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="ownership" class="form-label">Ownership</label>
                                <select id="ownership" name="ownership"
                                        class="form-select @error('ownership') is-invalid @enderror">
                                    <option value="owned" {{ old('ownership', $vehicle->ownership) == 'owned' ? 'selected' : '' }}>Owned</option>
                                    <option value="leased" {{ old('ownership', $vehicle->ownership) == 'leased' ? 'selected' : '' }}>Leased</option>
                                    <option value="rented" {{ old('ownership', $vehicle->ownership) == 'rented' ? 'selected' : '' }}>Rented</option>
                                </select>
                                @error('ownership') <x-form-error name="ownership" /> @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="purchase_price" class="form-label">Purchase Price ($)</label>
                                <input type="number" step="0.01" id="purchase_price" name="purchase_price"
                                       class="form-control @error('purchase_price') is-invalid @enderror"
                                       value="{{ old('purchase_price', $vehicle->purchase_price) }}">
                                @error('purchase_price') <x-form-error name="purchase_price" /> @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="purchase_date" class="form-label">Purchase Date</label>
                                <input type="date" id="purchase_date" name="purchase_date"
                                       class="form-control @error('purchase_date') is-invalid @enderror"
                                       value="{{ old('purchase_date', $vehicle->purchase_date?->format('Y-m-d')) }}">
                                @error('purchase_date') <x-form-error name="purchase_date" /> @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="branch_id" class="form-label">Branch</label>
                                <select id="branch_id" name="branch_id"
                                        class="form-select select2 @error('branch_id') is-invalid @enderror">
                                    <option value="">Select Branch</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ old('branch_id', $vehicle->branch_id) == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('branch_id') <x-form-error name="branch_id" /> @enderror
                            </div>
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Update Vehicle
                            </button>
                            <a href="{{ route('vehicles.show', $vehicle) }}" class="btn btn-outline-secondary">Cancel</a>
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
            $('#vehicle_type_id, #branch_id').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Select...'
            });
        });
    </script>
@endpush

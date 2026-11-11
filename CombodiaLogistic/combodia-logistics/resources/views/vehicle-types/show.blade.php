@extends('layouts.admin')

@section('title', 'Vehicle Type Details')
@section('subtitle', 'View vehicle type information')

@section('content')
<x-breadcrumb :items="[['label' => 'Vehicle Types', 'url' => route('vehicle-types.index')], ['label' => 'Vehicle Type Details']]" />

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">{{ $vehicleType->name }}</h4>
        <p class="text-muted mb-0">
            @if($vehicleType->is_active)
                <span class="badge bg-success bg-opacity-10 text-success">Active</span>
            @else
                <span class="badge bg-danger bg-opacity-10 text-danger">Inactive</span>
            @endif
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('vehicle-types.edit', $vehicleType->id) }}" class="btn btn-primary">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        <a href="{{ route('vehicle-types.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0 fw-bold"><i class="bi bi-info-circle me-2"></i>Vehicle Type Information</h6>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Type Name</label>
                        <p class="fw-medium mb-0">{{ $vehicleType->name }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Status</label>
                        <p class="mb-0">
                            @if($vehicleType->is_active)
                                <span class="badge bg-success bg-opacity-10 text-success">Active</span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger">Inactive</span>
                            @endif
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Max Weight (kg)</label>
                        <p class="fw-medium mb-0">{{ number_format($vehicleType->max_weight, 2) }} kg</p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Max Volume (cbm)</label>
                        <p class="fw-medium mb-0">{{ number_format($vehicleType->max_volume, 2) }} cbm</p>
                    </div>
                    <div class="col-12">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Description</label>
                        <p class="fw-medium mb-0">{{ $vehicleType->description ?? '-' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0 fw-bold"><i class="bi bi-speedometer me-2"></i>Capacity Limits</h6>
            </div>
            <div class="card-body text-center">
                <div class="mb-4">
                    <i class="bi bi-weight text-primary" style="font-size: 2rem;"></i>
                    <h3 class="fw-bold mt-2 mb-0">{{ number_format($vehicleType->max_weight, 2) }}</h3>
                    <small class="text-muted">Max Weight (kg)</small>
                </div>
                <hr>
                <div>
                    <i class="bi bi-box text-primary" style="font-size: 2rem;"></i>
                    <h3 class="fw-bold mt-2 mb-0">{{ number_format($vehicleType->max_volume, 2) }}</h3>
                    <small class="text-muted">Max Volume (cbm)</small>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-12">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0 fw-bold"><i class="bi bi-car-front me-2"></i>Vehicles of this Type</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Plate #</th>
                                <th>Brand</th>
                                <th>Model</th>
                                <th>Driver</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($vehicleType->vehicles ?? [] as $vehicle)
                            <tr>
                                <td><span class="badge bg-light text-dark border">{{ $vehicle->code }}</span></td>
                                <td class="fw-medium">{{ $vehicle->plate_number }}</td>
                                <td>{{ $vehicle->brand }}</td>
                                <td>{{ $vehicle->model }}</td>
                                <td>{{ $vehicle->driver->name ?? '-' }}</td>
                                <td>
                                    @php
                                        $statusColors = [
                                            'available' => 'success',
                                            'in_use' => 'primary',
                                            'maintenance' => 'warning',
                                            'inactive' => 'danger',
                                        ];
                                        $statusLabels = [
                                            'available' => 'Available',
                                            'in_use' => 'In Use',
                                            'maintenance' => 'Maintenance',
                                            'inactive' => 'Inactive',
                                        ];
                                        $color = $statusColors[$vehicle->status ?? 'available'] ?? 'secondary';
                                        $label = $statusLabels[$vehicle->status ?? 'available'] ?? ucfirst($vehicle->status ?? 'Unknown');
                                    @endphp
                                    <span class="badge bg-{{ $color }} bg-opacity-10 text-{{ $color }}">{{ $label }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="bi bi-car-front text-muted" style="font-size: 2rem;"></i>
                                    <p class="text-muted mt-2 mb-0">No vehicles assigned to this type yet</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

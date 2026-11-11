@extends('layouts.admin')

@section('title', 'Vehicle Details')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Vehicle Details</h4>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.vehicles.edit', $vehicle) }}" class="btn btn-warning">
                <i class="bi bi-pencil-square me-1"></i> Edit
            </a>
            <a href="{{ route('admin.vehicles.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <x-breadcrumb :items="[
        ['label' => 'Vehicles', 'url' => route('admin.vehicles.index')],
        ['label' => 'Vehicle Details']
    ]" />

    <div class="row">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0">Vehicle Information</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3 pb-3 border-bottom">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Code</label>
                        <p class="fw-medium mb-0">{{ $vehicle->code }}</p>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Plate Number</label>
                        <p class="fw-medium mb-0">{{ $vehicle->plate_number }}</p>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Type</label>
                        <p class="fw-medium mb-0">{{ ucfirst($vehicle->type) }}</p>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Brand</label>
                        <p class="fw-medium mb-0">{{ $vehicle->brand ?? '-' }}</p>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Model</label>
                        <p class="fw-medium mb-0">{{ $vehicle->model ?? '-' }}</p>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Year</label>
                        <p class="fw-medium mb-0">{{ $vehicle->year ?? '-' }}</p>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Color</label>
                        <p class="fw-medium mb-0">{{ $vehicle->color ?? '-' }}</p>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Fuel Type</label>
                        <p class="fw-medium mb-0">{{ ucfirst(str_replace('_', ' ', $vehicle->fuel_type ?? '-')) }}</p>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Capacity (Weight)</label>
                        <p class="fw-medium mb-0">{{ $vehicle->capacity_weight ?? '-' }} kg</p>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Capacity (Volume)</label>
                        <p class="fw-medium mb-0">{{ $vehicle->capacity_volume ?? '-' }} m³</p>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Insurance Expiry</label>
                        <p class="fw-medium mb-0">
                            @if($vehicle->insurance_expiry)
                                @if(\Carbon\Carbon::parse($vehicle->insurance_expiry)->isPast())
                                    <span class="text-danger">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                        {{ \Carbon\Carbon::parse($vehicle->insurance_expiry)->format('d M Y') }}
                                    </span>
                                @else
                                    {{ \Carbon\Carbon::parse($vehicle->insurance_expiry)->format('d M Y') }}
                                @endif
                            @else
                                -
                            @endif
                        </p>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Registration Expiry</label>
                        <p class="fw-medium mb-0">
                            @if($vehicle->registration_expiry)
                                @if(\Carbon\Carbon::parse($vehicle->registration_expiry)->isPast())
                                    <span class="text-danger">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                        {{ \Carbon\Carbon::parse($vehicle->registration_expiry)->format('d M Y') }}
                                    </span>
                                @else
                                    {{ \Carbon\Carbon::parse($vehicle->registration_expiry)->format('d M Y') }}
                                @endif
                            @else
                                -
                            @endif
                        </p>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Branch</label>
                        <p class="fw-medium mb-0">{{ $vehicle->branch->name ?? '-' }}</p>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Driver</label>
                        <p class="fw-medium mb-0">{{ $vehicle->driver->name ?? '-' }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Status</label>
                        <p class="mb-0">
                            @php
                                $statusColors = [
                                    'active' => 'success',
                                    'available' => 'success',
                                    'in_use' => 'primary',
                                    'in_transit' => 'info',
                                    'maintenance' => 'warning',
                                    'inactive' => 'secondary',
                                    'retired' => 'danger',
                                    'out_of_service' => 'danger',
                                ];
                                $statusColor = $statusColors[$vehicle->status] ?? 'secondary';
                            @endphp
                            <span class="badge bg-{{ $statusColor }}">
                                {{ ucfirst(str_replace('_', ' ', $vehicle->status)) }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0">Vehicle Summary</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3 mb-3">
                            <div class="p-3 rounded bg-light">
                                <i class="bi bi-truck fs-3 text-primary"></i>
                                <h5 class="mt-2 mb-0">{{ $vehicle->plate_number }}</h5>
                                <small class="text-muted">Plate Number</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="p-3 rounded bg-light">
                                <i class="bi bi-speedometer fs-3 text-success"></i>
                                <h5 class="mt-2 mb-0">{{ $vehicle->capacity_weight ?? 0 }} kg</h5>
                                <small class="text-muted">Weight Capacity</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="p-3 rounded bg-light">
                                <i class="bi bi-box-seam fs-3 text-info"></i>
                                <h5 class="mt-2 mb-0">{{ $vehicle->capacity_volume ?? 0 }} m³</h5>
                                <small class="text-muted">Volume Capacity</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="p-3 rounded bg-light">
                                <i class="bi bi-shield-check fs-3 text-warning"></i>
                                <h5 class="mt-2 mb-0">{{ $vehicle->insurance_expiry ? \Carbon\Carbon::parse($vehicle->insurance_expiry)->diffForHumans() : '-' }}</h5>
                                <small class="text-muted">Insurance Expires</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if($vehicle->description)
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">Description</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-0">{{ $vehicle->description }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

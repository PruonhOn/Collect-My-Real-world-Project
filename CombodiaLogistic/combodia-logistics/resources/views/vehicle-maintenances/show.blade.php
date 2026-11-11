@extends('layouts.admin')

@section('title', 'Maintenance Details')
@section('subtitle', 'Maintenance #' . $maintenance->id)

@section('content')
<x-breadcrumb :items="[['label' => 'Vehicle Maintenance', 'url' => route('vehicle-maintenances.index')], ['label' => 'Maintenance Details']]" />

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Maintenance #{{ $maintenance->id }}</h4>
        <p class="text-muted mb-0">
            <span class="badge bg-light text-dark border me-2">{{ $maintenance->vehicle->plate_number ?? '-' }}</span>
            @php
                $statusColors = [
                    'pending' => 'warning',
                    'in_progress' => 'info',
                    'completed' => 'success',
                    'cancelled' => 'danger',
                ];
                $statusLabels = [
                    'pending' => 'Pending',
                    'in_progress' => 'In Progress',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                ];
                $color = $statusColors[$maintenance->status ?? 'pending'] ?? 'secondary';
                $label = $statusLabels[$maintenance->status ?? 'pending'] ?? ucfirst($maintenance->status ?? 'Unknown');
            @endphp
            <span class="badge bg-{{ $color }} bg-opacity-10 text-{{ $color }}">{{ $label }}</span>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('vehicle-maintenances.edit', $maintenance->id) }}" class="btn btn-primary">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        <a href="{{ route('vehicle-maintenances.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0 fw-bold"><i class="bi bi-car-front me-2"></i>Maintenance Information</h6>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <small class="text-muted d-block">Maintenance Type</small>
                        <span class="badge bg-light text-dark border">{{ ucfirst(str_replace('_', ' ', $maintenance->maintenance_type)) }}</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Status</small>
                        <span class="badge bg-{{ $color }} bg-opacity-10 text-{{ $color }}">{{ $label }}</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Scheduled Date</small>
                        <span class="fw-semibold">
                            @if($maintenance->scheduled_date)
                                {{ \Carbon\Carbon::parse($maintenance->scheduled_date)->format('d M Y') }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Completed Date</small>
                        <span class="fw-semibold">
                            @if($maintenance->completed_date)
                                {{ \Carbon\Carbon::parse($maintenance->completed_date)->format('d M Y') }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Cost</small>
                        <span class="fw-semibold">${{ number_format($maintenance->cost ?? 0, 2) }}</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Performed By</small>
                        <span class="fw-semibold">{{ $maintenance->performed_by ?? '-' }}</span>
                    </div>
                    <div class="col-12">
                        <small class="text-muted d-block">Description</small>
                        <p class="mb-0">{{ $maintenance->description ?? '-' }}</p>
                    </div>
                    @if($maintenance->notes)
                    <div class="col-12">
                        <small class="text-muted d-block">Notes</small>
                        <p class="mb-0">{{ $maintenance->notes }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0 fw-bold"><i class="bi bi-car-front-fill me-2"></i>Vehicle Information</h6>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-4">
                        <small class="text-muted d-block">Plate Number</small>
                        <span class="fw-semibold">{{ $maintenance->vehicle->plate_number ?? '-' }}</span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Brand</small>
                        <span class="fw-semibold">{{ $maintenance->vehicle->brand ?? '-' }}</span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Model</small>
                        <span class="fw-semibold">{{ $maintenance->vehicle->model ?? '-' }}</span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Type</small>
                        <span class="fw-semibold">{{ ucfirst($maintenance->vehicle->type ?? '-') }}</span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Year</small>
                        <span class="fw-semibold">{{ $maintenance->vehicle->year ?? '-' }}</span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Fuel Type</small>
                        <span class="fw-semibold">{{ ucfirst($maintenance->vehicle->fuel_type ?? '-') }}</span>
                    </div>
                </div>
            </div>
        </div>

        @if($maintenance->driver)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0 fw-bold"><i class="bi bi-person-fill me-2"></i>Driver Information</h6>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                        <i class="bi bi-person-fill text-primary"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">{{ $maintenance->driver->name }}</h6>
                        <small class="text-muted">{{ $maintenance->driver->phone ?? '' }}</small>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2"></i>Record Details</h6>
            </div>
            <div class="card-body">
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Created</label>
                    <p class="fw-medium mb-0">{{ $maintenance->created_at->format('d M Y, h:i A') }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Last Updated</label>
                    <p class="fw-medium mb-0">{{ $maintenance->updated_at->format('d M Y, h:i A') }}</p>
                </div>
                <div>
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Record ID</label>
                    <p class="fw-medium mb-0">#{{ $maintenance->id }}</p>
                </div>
            </div>
        </div>

        <div class="d-grid gap-2">
            <a href="{{ route('vehicle-maintenances.edit', $maintenance->id) }}" class="btn btn-primary">
                <i class="bi bi-pencil me-1"></i> Edit Maintenance
            </a>
            <a href="{{ route('vehicle-maintenances.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </a>
        </div>
    </div>
</div>
@endsection

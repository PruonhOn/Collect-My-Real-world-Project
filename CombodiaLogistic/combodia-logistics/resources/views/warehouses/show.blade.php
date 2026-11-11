@extends('layouts.admin')

@section('title', 'Warehouse Details')
@section('subtitle', 'View warehouse information')

@section('content')
<x-breadcrumb :items="[['label' => 'Warehouses', 'url' => route('admin.warehouses.index')], ['label' => 'Warehouse Details']]" />

@php
    $capacityPercent = $warehouse->capacity > 0 ? round(($warehouse->used_capacity ?? 0) / $warehouse->capacity * 100) : 0;
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">{{ $warehouse->name }}</h4>
        <p class="text-muted mb-0">
            <span class="badge bg-light text-dark border me-2">{{ $warehouse->code }}</span>
            @if($warehouse->is_active ?? true)
                <span class="badge bg-success bg-opacity-10 text-success">Active</span>
            @else
                <span class="badge bg-danger bg-opacity-10 text-danger">Inactive</span>
            @endif
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.warehouses.edit', $warehouse->id) }}" class="btn btn-primary">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        <a href="{{ route('admin.warehouses.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0 fw-bold"><i class="bi bi-info-circle me-2"></i>Warehouse Info</h6>
            </div>
            <div class="card-body">
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Branch</label>
                    <p class="fw-medium mb-0">{{ $warehouse->branch->name ?? '-' }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Address</label>
                    <p class="fw-medium mb-0">{{ $warehouse->address ?? '-' }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">City</label>
                    <p class="fw-medium mb-0">{{ $warehouse->city ?? '-' }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Capacity</label>
                    <p class="fw-medium mb-0">{{ number_format($warehouse->capacity) }} sqm</p>
                </div>
                <div class="mb-3">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Manager</label>
                    <p class="fw-medium mb-0">{{ $warehouse->manager_name ?? '-' }}</p>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0 fw-bold"><i class="bi bi-speedometer me-2"></i>Capacity Usage</h6>
            </div>
            <div class="card-body text-center">
                <div class="position-relative d-inline-block mb-3" style="width: 120px; height: 120px;">
                    <svg viewBox="0 0 36 36" class="circular-progress" style="width: 120px; height: 120px; transform: rotate(-90deg);">
                        <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#eee" stroke-width="3"></path>
                        <path class="circle" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="{{ $capacityPercent > 90 ? '#dc3545' : ($capacityPercent > 70 ? '#ffc107' : '#198754') }}" stroke-width="3" stroke-dasharray="{{ $capacityPercent }}, 100"></path>
                    </svg>
                    <div class="position-absolute top-50 start-50 translate-middle">
                        <h4 class="fw-bold mb-0">{{ $capacityPercent }}%</h4>
                    </div>
                </div>
                <p class="text-muted mb-0">Storage Capacity Used</p>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="bi bi-grid me-2"></i>Zones</h6>
                <a href="#" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Add Zone
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Shelves</th>
                                <th>Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($warehouse->zones ?? [] as $zone)
                            <tr>
                                <td><span class="badge bg-light text-dark border">{{ $zone->code }}</span></td>
                                <td class="fw-medium">{{ $zone->name }}</td>
                                <td>{{ $zone->type ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-info bg-opacity-10 text-info">{{ $zone->shelves->count() ?? 0 }} shelves</span>
                                </td>
                                <td>
                                    @if($zone->is_active ?? true)
                                        <span class="badge bg-success bg-opacity-10 text-success">Active</span>
                                    @else
                                        <span class="badge bg-danger bg-opacity-10 text-danger">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="#" class="btn btn-sm btn-outline-primary" title="Manage Shelves">
                                        <i class="bi bi-grid-3x3-gap"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="bi bi-grid text-muted" style="font-size: 2rem;"></i>
                                    <p class="text-muted mt-2 mb-0">No zones configured yet</p>
                                    <a href="#" class="btn btn-sm btn-primary mt-2">
                                        <i class="bi bi-plus-lg me-1"></i> Add First Zone
                                    </a>
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

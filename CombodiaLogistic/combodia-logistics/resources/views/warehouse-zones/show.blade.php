@extends('layouts.admin')

@section('title', 'Zone Details')
@section('subtitle', 'View warehouse zone information')

@section('content')
<x-breadcrumb :items="[['label' => 'Warehouse Zones', 'url' => route('warehouse-zones.index')], ['label' => 'Zone Details']]" />

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">{{ $zone->name }}</h4>
        <p class="text-muted mb-0">
            <span class="badge bg-light text-dark border me-2">{{ $zone->code }}</span>
            @if($zone->is_active)
                <span class="badge bg-success bg-opacity-10 text-success">Active</span>
            @else
                <span class="badge bg-danger bg-opacity-10 text-danger">Inactive</span>
            @endif
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('warehouse-zones.edit', $zone->id) }}" class="btn btn-primary">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        <a href="{{ route('warehouse-zones.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0 fw-bold"><i class="bi bi-info-circle me-2"></i>Zone Info</h6>
            </div>
            <div class="card-body">
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Warehouse</label>
                    <p class="fw-medium mb-0">{{ $zone->warehouse->name ?? '-' }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Zone Code</label>
                    <p class="fw-medium mb-0">{{ $zone->code }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Zone Name</label>
                    <p class="fw-medium mb-0">{{ $zone->name }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Capacity</label>
                    <p class="fw-medium mb-0">{{ $zone->capacity ? number_format($zone->capacity) . ' sqm' : '-' }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Description</label>
                    <p class="fw-medium mb-0">{{ $zone->description ?? '-' }}</p>
                </div>
                <div>
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Status</label>
                    <p class="mb-0">
                        @if($zone->is_active)
                            <span class="badge bg-success bg-opacity-10 text-success">Active</span>
                        @else
                            <span class="badge bg-danger bg-opacity-10 text-danger">Inactive</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="bi bi-grid-3x3-gap me-2"></i>Shelves</h6>
                <a href="{{ route('warehouse-shelves.create', ['zone_id' => $zone->id]) }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Add Shelf
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Capacity</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($zone->warehouseShelves as $shelf)
                            <tr>
                                <td><span class="badge bg-light text-dark border">{{ $shelf->code }}</span></td>
                                <td class="fw-medium">{{ $shelf->name }}</td>
                                <td>{{ $shelf->capacity ? number_format($shelf->capacity) . ' sqm' : '-' }}</td>
                                <td>
                                    @if($shelf->is_active ?? true)
                                        <span class="badge bg-success bg-opacity-10 text-success">Active</span>
                                    @else
                                        <span class="badge bg-danger bg-opacity-10 text-danger">Inactive</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-4">
                                    <i class="bi bi-grid-3x3-gap text-muted" style="font-size: 2rem;"></i>
                                    <p class="text-muted mt-2 mb-0">No shelves configured yet</p>
                                    <a href="{{ route('warehouse-shelves.create', ['zone_id' => $zone->id]) }}" class="btn btn-sm btn-primary mt-2">
                                        <i class="bi bi-plus-lg me-1"></i> Add First Shelf
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

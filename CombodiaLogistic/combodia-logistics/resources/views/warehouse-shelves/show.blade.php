@extends('layouts.admin')

@section('title', 'Shelf Details')
@section('subtitle', 'View shelf information')

@section('content')
<x-breadcrumb :items="[['label' => 'Warehouse Shelves', 'url' => route('warehouse-shelves.index')], ['label' => 'Shelf Details']]" />

@php
    $usagePercent = $shelf->max_weight > 0 ? round(($shelf->current_weight / $shelf->max_weight) * 100) : 0;
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">{{ $shelf->name }}</h4>
        <p class="text-muted mb-0">
            <span class="badge bg-light text-dark border me-2">{{ $shelf->code }}</span>
            @if($shelf->is_active)
                <span class="badge bg-success bg-opacity-10 text-success">Active</span>
            @else
                <span class="badge bg-danger bg-opacity-10 text-danger">Inactive</span>
            @endif
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('warehouse-shelves.edit', $shelf->id) }}" class="btn btn-primary">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        <a href="{{ route('warehouse-shelves.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0 fw-bold"><i class="bi bi-info-circle me-2"></i>Shelf Info</h6>
            </div>
            <div class="card-body">
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Zone</label>
                    <p class="fw-medium mb-0">{{ $shelf->warehouseZone->name ?? '-' }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Code</label>
                    <p class="fw-medium mb-0"><span class="badge bg-light text-dark border">{{ $shelf->code }}</span></p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Max Weight</label>
                    <p class="fw-medium mb-0">{{ number_format($shelf->max_weight, 2) }} kg</p>
                </div>
                <div class="mb-3">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Current Weight</label>
                    <p class="fw-medium mb-0">{{ number_format($shelf->current_weight, 2) }} kg</p>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0 fw-bold"><i class="bi bi-speedometer me-2"></i>Weight Usage</h6>
            </div>
            <div class="card-body text-center">
                <div class="position-relative d-inline-block mb-3" style="width: 120px; height: 120px;">
                    <svg viewBox="0 0 36 36" class="circular-progress" style="width: 120px; height: 120px; transform: rotate(-90deg);">
                        <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#eee" stroke-width="3"></path>
                        <path class="circle" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="{{ $usagePercent > 90 ? '#dc3545' : ($usagePercent > 70 ? '#ffc107' : '#198754') }}" stroke-width="3" stroke-dasharray="{{ $usagePercent }}, 100"></path>
                    </svg>
                    <div class="position-absolute top-50 start-50 translate-middle">
                        <h4 class="fw-bold mb-0">{{ $usagePercent }}%</h4>
                    </div>
                </div>
                <p class="text-muted mb-0">Weight Capacity Used</p>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0 fw-bold"><i class="bi bi-box-seam me-2"></i>Packages</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Package #</th>
                                <th>Barcode</th>
                                <th>Type</th>
                                <th>Weight (kg)</th>
                                <th>Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($shelf->packages ?? [] as $package)
                            <tr>
                                <td class="fw-medium">{{ $package->package_number }}</td>
                                <td><code class="bg-light px-2 py-1">{{ $package->barcode }}</code></td>
                                <td><span class="badge bg-light text-dark">{{ ucfirst($package->type) }}</span></td>
                                <td>{{ number_format($package->weight, 2) }}</td>
                                <td>
                                    @switch($package->status)
                                        @case('pending')
                                            <span class="badge bg-warning text-dark">Pending</span>
                                            @break
                                        @case('in_warehouse')
                                            <span class="badge bg-info">In Warehouse</span>
                                            @break
                                        @case('in_transit')
                                            <span class="badge bg-primary">In Transit</span>
                                            @break
                                        @case('delivered')
                                            <span class="badge bg-success">Delivered</span>
                                            @break
                                        @default
                                            <span class="badge bg-secondary">{{ ucfirst($package->status) }}</span>
                                    @endswitch
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('packages.show', $package->id) }}" class="btn btn-sm btn-outline-info" title="View Package">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="bi bi-box-seam text-muted" style="font-size: 2rem;"></i>
                                    <p class="text-muted mt-2 mb-0">No packages on this shelf</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0 fw-bold"><i class="bi bi-list-check me-2"></i>Inventories</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped mb-0">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Unit Cost</th>
                                <th>Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($shelf->inventories ?? [] as $item)
                            <tr>
                                <td><code class="bg-light px-2 py-1">{{ $item->sku }}</code></td>
                                <td class="fw-medium">{{ $item->product_name }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>${{ number_format($item->unit_cost, 2) }}</td>
                                <td>
                                    @if($item->quantity <= 0)
                                        <span class="badge bg-danger">Out of Stock</span>
                                    @elseif($item->quantity <= ($item->reorder_point ?? 0))
                                        <span class="badge bg-warning text-dark">Below Reorder</span>
                                    @else
                                        <span class="badge bg-success">In Stock</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('inventory.edit', $item->id) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="bi bi-inbox text-muted" style="font-size: 2rem;"></i>
                                    <p class="text-muted mt-2 mb-0">No inventory items on this shelf</p>
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

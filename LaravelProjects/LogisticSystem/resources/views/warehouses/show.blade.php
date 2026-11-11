@extends('layouts.admin')

@section('title', $warehouse->name . ' - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('warehouses.index') }}">Warehouses</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $warehouse->code }}</li>
@endsection

@section('page-title', $warehouse->name)
@section('page-subtitle', 'Code: ' . $warehouse->code . ' | ' . $warehouse->city)

@section('page-actions')
    <a href="{{ route('warehouses.edit', $warehouse) }}" class="btn btn-primary btn-sm">
        <i class="bi bi-pencil"></i> Edit
    </a>
    <a href="{{ route('warehouse-locations.index', ['warehouse_id' => $warehouse->id]) }}" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-geo-alt"></i> Locations
    </a>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0 fw-bold">Warehouse Details</h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5 text-muted small">Code</dt>
                        <dd class="col-sm-7"><span class="badge bg-secondary">{{ $warehouse->code }}</span></dd>

                        <dt class="col-sm-5 text-muted small">Name</dt>
                        <dd class="col-sm-7">{{ $warehouse->name }}</dd>

                        <dt class="col-sm-5 text-muted small">Address</dt>
                        <dd class="col-sm-7">{{ $warehouse->address ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted small">City</dt>
                        <dd class="col-sm-7">{{ $warehouse->city }}</dd>

                        <dt class="col-sm-5 text-muted small">Province</dt>
                        <dd class="col-sm-7">{{ $warehouse->province ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted small">Phone</dt>
                        <dd class="col-sm-7">{{ $warehouse->phone ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted small">Email</dt>
                        <dd class="col-sm-7">{{ $warehouse->email ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted small">Manager</dt>
                        <dd class="col-sm-7">{{ $warehouse->manager_name ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted small">Branch</dt>
                        <dd class="col-sm-7">{{ $warehouse->branch?->name ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted small">Status</dt>
                        <dd class="col-sm-7">
                            @if($warehouse->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-danger">Inactive</span>
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0 fw-bold">Capacity Usage</h6>
                </div>
                <div class="card-body">
                    @php
                        $used = $warehouse->used_capacity ?? 0;
                        $total = $warehouse->total_capacity ?? 0;
                        $pct = $total > 0 ? min(round(($used / $total) * 100), 100) : 0;
                    @endphp
                    <div class="text-center mb-3">
                        <h2 class="fw-bold mb-0 {{ $pct >= 90 ? 'text-danger' : ($pct >= 70 ? 'text-warning' : 'text-success') }}">
                            {{ $pct }}%
                        </h2>
                        <small class="text-muted">Capacity used</small>
                    </div>
                    <div class="progress" style="height: 20px;">
                        <div class="progress-bar progress-bar-striped {{ $pct >= 90 ? 'bg-danger' : ($pct >= 70 ? 'bg-warning' : 'bg-success') }}"
                             role="progressbar" style="width: {{ $pct }}%">
                            {{ number_format($used, 1) }} / {{ number_format($total, 1) }} m³
                        </div>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted small">Total Area:</span>
                        <span class="fw-medium">{{ number_format($warehouse->total_area ?? 0, 1) }} m²</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0 fw-bold">Locations</h6>
                    <a href="{{ route('warehouse-locations.create', ['warehouse_id' => $warehouse->id]) }}" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-lg"></i> Add Location
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Zone</th>
                                    <th>Aisle/Rack</th>
                                    <th>Occupied</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($warehouse->locations as $location)
                                    <tr>
                                        <td><span class="badge bg-secondary">{{ $location->code }}</span></td>
                                        <td>{{ $location->name }}</td>
                                        <td>{{ $location->zone ?? '—' }}</td>
                                        <td>{{ $location->aisle ?? '—' }} / {{ $location->rack ?? '—' }}</td>
                                        <td>
                                            @if($location->is_occupied)
                                                <span class="badge bg-warning">Occupied</span>
                                            @else
                                                <span class="badge bg-success">Available</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('warehouse-locations.edit', $location) }}"
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            <i class="bi bi-geo-alt fs-2 d-block mb-2"></i>
                                            <p class="mb-0">No locations defined</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0 fw-bold">Current Inventory</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>SKU</th>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($warehouse->inventoryItems ?? [] as $item)
                                    <tr>
                                        <td><span class="badge bg-secondary">{{ $item->sku ?? '—' }}</span></td>
                                        <td>{{ $item->product_name ?? $item->product?->name ?? '—' }}</td>
                                        <td>{{ number_format($item->quantity ?? 0) }}</td>
                                        <td>{{ $item->location?->code ?? '—' }}</td>
                                        <td>
                                            @php $qty = $item->quantity ?? 0; @endphp
                                            @if($qty <= 0)
                                                <span class="badge bg-secondary">Out of Stock</span>
                                            @elseif($qty <= ($item->min_stock ?? 10))
                                                <span class="badge bg-danger">Low Stock</span>
                                            @else
                                                <span class="badge bg-success">In Stock</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            <i class="bi bi-box fs-2 d-block mb-2"></i>
                                            <p class="mb-0">No inventory items</p>
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

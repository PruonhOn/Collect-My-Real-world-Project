@extends('layouts.admin')

@section('title', $location->code . ' - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('warehouses.index') }}">Warehouses</a></li>
    <li class="breadcrumb-item"><a href="{{ route('warehouse-locations.index') }}">Locations</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $location->code }}</li>
@endsection

@section('page-title', $location->name)
@section('page-subtitle', 'Code: ' . $location->code . ' | Zone: ' . ($location->zone ?? 'N/A'))

@section('page-actions')
    <a href="{{ route('warehouse-locations.edit', $location) }}" class="btn btn-primary btn-sm">
        <i class="bi bi-pencil"></i> Edit
    </a>
    <a href="{{ route('warehouse-locations.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Back
    </a>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0 fw-bold">Location Details</h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5 text-muted small">Code</dt>
                        <dd class="col-sm-7"><span class="badge bg-secondary">{{ $location->code }}</span></dd>

                        <dt class="col-sm-5 text-muted small">Name</dt>
                        <dd class="col-sm-7 fw-medium">{{ $location->name }}</dd>

                        <dt class="col-sm-5 text-muted small">Zone</dt>
                        <dd class="col-sm-7">{{ $location->zone ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted small">Aisle</dt>
                        <dd class="col-sm-7">{{ $location->aisle ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted small">Rack</dt>
                        <dd class="col-sm-7">{{ $location->rack ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted small">Shelf</dt>
                        <dd class="col-sm-7">{{ $location->shelf ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted small">Bin</dt>
                        <dd class="col-sm-7">{{ $location->bin ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted small">Max Weight</dt>
                        <dd class="col-sm-7">{{ $location->max_weight ? number_format($location->max_weight, 2) . ' kg' : '—' }}</dd>

                        <dt class="col-sm-5 text-muted small">Max Volume</dt>
                        <dd class="col-sm-7">{{ $location->max_volume ? number_format($location->max_volume, 2) . ' m³' : '—' }}</dd>

                        <dt class="col-sm-5 text-muted small">Status</dt>
                        <dd class="col-sm-7">
                            @if($location->is_occupied)
                                <span class="badge bg-warning">Occupied</span>
                            @else
                                <span class="badge bg-success">Available</span>
                            @endif
                            @if(!$location->is_active)
                                <span class="badge bg-danger">Inactive</span>
                            @endif
                        </dd>

                        <dt class="col-sm-5 text-muted small">Warehouse</dt>
                        <dd class="col-sm-7">
                            <a href="{{ route('warehouses.show', $location->warehouse) }}" class="text-decoration-none">
                                {{ $location->warehouse->name ?? '—' }}
                            </a>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0 fw-bold">Inventory at this Location</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>SKU</th>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($location->inventoryItems ?? [] as $item)
                                    <tr>
                                        <td><span class="badge bg-secondary">{{ $item->sku ?? '—' }}</span></td>
                                        <td>{{ $item->product_name ?? $item->product?->name ?? '—' }}</td>
                                        <td>{{ number_format($item->quantity ?? 0) }}</td>
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
                                        <td colspan="4" class="text-center text-muted py-4">
                                            <i class="bi bi-box fs-2 d-block mb-2"></i>
                                            <p class="mb-0">No inventory at this location</p>
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

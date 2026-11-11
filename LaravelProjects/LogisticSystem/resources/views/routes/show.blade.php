@extends('layouts.admin')

@section('title', $route->name . ' - Route Details - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('routes.index') }}">Routes</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $route->code }}</li>
@endsection

@section('page-title', $route->name)
@section('page-subtitle', 'Route: ' . $route->code . ' | ' . $route->origin_city . ' → ' . $route->destination_city)

@section('page-actions')
    <a href="{{ route('routes.edit', $route) }}" class="btn btn-primary">
        <i class="bi bi-pencil"></i> Edit Route
    </a>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Route Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Code</small>
                            <span class="badge bg-secondary fs-6">{{ $route->code }}</span>
                        </div>
                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Status</small>
                            @if($route->is_active)
                                <span class="badge bg-success fs-6">Active</span>
                            @else
                                <span class="badge bg-secondary fs-6">Inactive</span>
                            @endif
                        </div>
                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Distance</small>
                            <strong>{{ number_format($route->distance_km, 1) }} km</strong>
                        </div>
                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Origin</small>
                            <strong><i class="bi bi-geo-alt text-primary"></i> {{ $route->origin_city }}</strong>
                        </div>
                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Destination</small>
                            <strong><i class="bi bi-geo-alt text-danger"></i> {{ $route->destination_city }}</strong>
                        </div>
                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Estimated Hours</small>
                            <strong><i class="bi bi-clock"></i> {{ number_format($route->estimated_hours, 1) }} hours</strong>
                        </div>
                        @if($route->description)
                            <div class="col-12 mb-3">
                                <small class="text-muted d-block">Description</small>
                                <p class="mb-0">{{ $route->description }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Waypoints</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addWaypointModal">
                        <i class="bi bi-plus-lg"></i> Add Waypoint
                    </button>
                </div>
                <div class="card-body p-0">
                    @if($route->waypoints && $route->waypoints->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($route->waypoints->sortBy('order') as $waypoint)
                                <div class="list-group-item d-flex align-items-center">
                                    <div class="me-3 text-muted fw-bold">{{ $waypoint->order }}</div>
                                    <i class="bi bi-geo-alt me-2 text-primary"></i>
                                    <div class="flex-grow-1">
                                        <strong>{{ $waypoint->name }}</strong>
                                        <small class="text-muted d-block">{{ $waypoint->address }}{{ $waypoint->city ? ', ' . $waypoint->city : '' }}</small>
                                    </div>
                                    <span class="badge bg-info me-2">{{ number_format($waypoint->distance_from_origin, 1) }} km</span>
                                    @if($waypoint->estimated_arrival_minutes)
                                        <small class="text-muted">~{{ $waypoint->estimated_arrival_minutes }} min</small>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-signpost-2 fs-2 d-block mb-2"></i>
                            <p class="mb-0">No waypoints defined for this route</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Route Map</h5>
                </div>
                <div class="card-body p-0">
                    <div class="bg-light d-flex align-items-center justify-content-center" style="height: 250px;">
                        <div class="text-center text-muted">
                            <i class="bi bi-map fs-1 d-block mb-2"></i>
                            <small>Map integration placeholder</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Deliveries on This Route</h5>
                </div>
                <div class="card-body p-0">
                    @if($route->deliveries && $route->deliveries->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($route->deliveries->take(10) as $delivery)
                                <a href="{{ route('deliveries.show', $delivery) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>#{{ $delivery->delivery_number }}</strong>
                                        <small class="text-muted d-block">{{ $delivery->scheduled_date->format('d M Y') }}</small>
                                    </div>
                                    @php
                                        $statusClass = match($delivery->status) {
                                            'delivered' => 'success',
                                            'in_transit' => 'primary',
                                            'scheduled' => 'warning',
                                            'cancelled' => 'danger',
                                            default => 'secondary'
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $statusClass }}">{{ str_replace('_', ' ', ucfirst($delivery->status)) }}</span>
                                </a>
                            @endforeach
                        </div>
                        @if($route->deliveries->count() > 10)
                            <div class="text-center p-2">
                                <a href="{{ route('deliveries.index', ['route_id' => $route->id]) }}" class="btn btn-sm btn-link">View all {{ $route->deliveries->count() }} deliveries</a>
                            </div>
                        @endif
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-truck fs-2 d-block mb-2"></i>
                            <p class="mb-0">No deliveries on this route</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($route->waypoints && $route->waypoints->count() > 0)
        <div class="modal fade" id="addWaypointModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('routes.waypoints.store', $route) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Add Waypoint</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Waypoint Name</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Address</label>
                                    <input type="text" name="address" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">City</label>
                                    <input type="text" name="city" class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Order</label>
                                    <input type="number" name="order" class="form-control" value="{{ ($route->waypoints->max('order') ?? 0) + 1 }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Distance (km)</label>
                                    <input type="number" step="0.1" name="distance_from_origin" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Est. Arrival (min)</label>
                                    <input type="number" name="estimated_arrival_minutes" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Add Waypoint</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

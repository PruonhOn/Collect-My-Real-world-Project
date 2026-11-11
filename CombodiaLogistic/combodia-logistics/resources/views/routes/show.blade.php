@extends('layouts.admin')

@section('title', 'Route Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Route Details</h4>
    <div>
        <a href="{{ route('routes.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <a href="{{ route('routes.edit', $route) }}" class="btn btn-primary btn-sm">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
    </div>
</div>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('routes.index') }}">Routes</a></li>
        <li class="breadcrumb-item active">{{ $route->name }}</li>
    </ol>
</nav>

<div class="row">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0">Route Information</h6>
            </div>
            <div class="card-body">
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Name</label>
                    <p class="fw-medium mb-0">{{ $route->name }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Code</label>
                    <p class="fw-medium mb-0">{{ $route->code }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Description</label>
                    <p class="fw-medium mb-0">{{ $route->description ?? '-' }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Origin</label>
                    <p class="fw-medium mb-0">{{ $route->origin ?? '-' }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Destination</label>
                    <p class="fw-medium mb-0">{{ $route->destination ?? '-' }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Estimated Distance</label>
                    <p class="fw-medium mb-0">{{ $route->estimated_distance ? $route->estimated_distance . ' km' : '-' }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Estimated Duration</label>
                    <p class="fw-medium mb-0">{{ $route->estimated_duration ?? '-' }}</p>
                </div>
                <div class="mb-0">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Status</label>
                    <p class="fw-medium mb-0">
                        @if($route->status === 'active')
                            <span class="badge bg-success">Active</span>
                        @elseif($route->status === 'inactive')
                            <span class="badge bg-danger">Inactive</span>
                        @else
                            <span class="badge bg-secondary">{{ ucfirst($route->status) }}</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Route Stops</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Stop Name</th>
                                <th>Address</th>
                                <th>Stop Order</th>
                                <th>Distance (km)</th>
                                <th>Estimated Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($route->stops as $stop)
                                <tr>
                                    <td>{{ $stop->name }}</td>
                                    <td>{{ $stop->address ?? '-' }}</td>
                                    <td>{{ $stop->stop_order ?? '-' }}</td>
                                    <td>{{ $stop->distance ? $stop->distance . ' km' : '-' }}</td>
                                    <td>{{ $stop->estimated_time ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No route stops found.</td>
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
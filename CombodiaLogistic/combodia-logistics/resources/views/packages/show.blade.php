@extends('layouts.admin')

@section('title', 'Package Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Package Details</h4>
    <div>
        <a href="{{ route('packages.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <a href="{{ route('packages.edit', $package) }}" class="btn btn-primary btn-sm">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
    </div>
</div>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('packages.index') }}">Packages</a></li>
        <li class="breadcrumb-item active">{{ $package->tracking_number }}</li>
    </ol>
</nav>

<div class="row">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0">Package Information</h6>
            </div>
            <div class="card-body">
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Tracking Number</label>
                    <p class="fw-medium mb-0">{{ $package->tracking_number }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Shipment Number</label>
                    <p class="fw-medium mb-0">{{ $package->shipment_number ?? '-' }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Description</label>
                    <p class="fw-medium mb-0">{{ $package->description ?? '-' }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Weight</label>
                    <p class="fw-medium mb-0">{{ $package->weight ? $package->weight . ' kg' : '-' }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Dimensions</label>
                    <p class="fw-medium mb-0">{{ $package->dimensions ?? '-' }}</p>
                </div>
                <div class="mb-0">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Status</label>
                    <p class="fw-medium mb-0">
                        @if($package->status === 'pending')
                            <span class="badge bg-warning text-dark">Pending</span>
                        @elseif($package->status === 'in_transit')
                            <span class="badge bg-primary">In Transit</span>
                        @elseif($package->status === 'delivered')
                            <span class="badge bg-success">Delivered</span>
                        @elseif($package->status === 'returned')
                            <span class="badge bg-danger">Returned</span>
                        @else
                            <span class="badge bg-secondary">{{ ucfirst($package->status) }}</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Tracking History</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date & Time</th>
                                <th>Status</th>
                                <th>Location</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($package->trackingHistory ?? [] as $tracking)
                                <tr>
                                    <td>{{ $tracking->created_at ? \Carbon\Carbon::parse($tracking->created_at)->format('d M Y, h:i A') : '-' }}</td>
                                    <td>
                                        @if($tracking->status === 'pending')
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        @elseif($tracking->status === 'in_transit')
                                            <span class="badge bg-primary">In Transit</span>
                                        @elseif($tracking->status === 'delivered')
                                            <span class="badge bg-success">Delivered</span>
                                        @elseif($tracking->status === 'returned')
                                            <span class="badge bg-danger">Returned</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst($tracking->status) }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $tracking->location ?? '-' }}</td>
                                    <td>{{ $tracking->remarks ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No tracking history found.</td>
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
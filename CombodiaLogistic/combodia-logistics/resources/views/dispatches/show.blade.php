@extends('layouts.admin')

@section('title', 'Dispatch Details')
@section('subtitle', 'Dispatch #' . $dispatch->dispatch_number)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Dispatch #{{ $dispatch->dispatch_number }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('dispatches.index') }}">Dispatches</a></li>
                <li class="breadcrumb-item active">Details</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('dispatches.edit', $dispatch) }}" class="btn btn-outline-primary">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        <a href="{{ route('dispatches.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-semibold">Dispatch Information</h6>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <small class="text-muted d-block">Dispatch Number</small>
                        <span class="fw-semibold">{{ $dispatch->dispatch_number }}</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Status</small>
                        @switch($dispatch->status)
                            @case('pending')
                                <span class="badge bg-warning text-dark">Pending</span>
                                @break
                            @case('assigned')
                                <span class="badge bg-info">Assigned</span>
                                @break
                            @case('in_transit')
                                <span class="badge bg-primary">In Transit</span>
                                @break
                            @case('completed')
                                <span class="badge bg-success">Completed</span>
                                @break
                            @case('cancelled')
                                <span class="badge bg-danger">Cancelled</span>
                                @break
                        @endswitch
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Shipment</small>
                        <a href="{{ route('shipments.show', $dispatch->shipment) }}" class="fw-semibold text-decoration-none">
                            {{ $dispatch->shipment->shipment_number ?? 'N/A' }}
                        </a>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Route</small>
                        <span class="fw-semibold">{{ $dispatch->route->name ?? 'N/A' }}</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Scheduled Date</small>
                        <span>{{ $dispatch->scheduled_date ? \Carbon\Carbon::parse($dispatch->scheduled_date)->format('d M Y, h:i A') : '-' }}</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Created</small>
                        <span>{{ $dispatch->created_at->format('d M Y, h:i A') }}</span>
                    </div>
                    @if($dispatch->notes)
                    <div class="col-12">
                        <small class="text-muted d-block">Notes</small>
                        <span>{{ $dispatch->notes }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-semibold">Assigned Drivers</h6>
            </div>
            <div class="card-body">
                @forelse($dispatch->drivers as $driver)
                <div class="d-flex align-items-center {{ !$loop->last ? 'pb-3 mb-3 border-bottom' : '' }}">
                    <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                        <i class="bi bi-person-fill text-primary"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">{{ $driver->name }}</h6>
                        <small class="text-muted">{{ $driver->phone ?? '' }}</small>
                    </div>
                </div>
                @empty
                <p class="text-muted mb-0">No drivers assigned</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-semibold">Status Timeline</h6>
            </div>
            <div class="card-body">
                <div class="tracking-timeline">
                    @forelse($dispatch->statusHistory as $history)
                    <div class="timeline-item">
                        <div class="timeline-dot bg-{{ $loop->first ? 'primary' : 'secondary' }}"></div>
                        <div class="timeline-content">
                            <h6 class="mb-0">{{ ucfirst(str_replace('_', ' ', $history->status)) }}</h6>
                            <small class="text-muted">{{ $history->created_at->format('d M Y, h:i A') }}</small>
                            @if($history->notes)
                            <p class="mb-0 small">{{ $history->notes }}</p>
                            @endif
                        </div>
                    </div>
                    @empty
                    <p class="text-muted text-center mb-0">No status history</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .tracking-timeline { position: relative; padding-left: 2rem; }
    .tracking-timeline::before { content: ''; position: absolute; left: 8px; top: 0; bottom: 0; width: 2px; background: #e9ecef; }
    .timeline-item { position: relative; padding-bottom: 1.2rem; }
    .timeline-item:last-child { padding-bottom: 0; }
    .timeline-dot { position: absolute; left: -2rem; top: 4px; width: 16px; height: 16px; border-radius: 50%; border: 3px solid #fff; box-shadow: 0 0 0 2px #dee2e6; }
</style>
@endpush

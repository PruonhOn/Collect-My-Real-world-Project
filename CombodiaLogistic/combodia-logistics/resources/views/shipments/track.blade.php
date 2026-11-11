@extends('layouts.guest')

@section('title', 'Track Shipment')

@section('content')
<div class="min-vh-100 d-flex align-items-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-5">
                    <h1 class="text-white fw-bold mb-2">Track Your Shipment</h1>
                    <p class="text-white-50">Enter your tracking number to see the latest status</p>
                </div>

                <div class="card border-0 shadow-lg mb-4">
                    <div class="card-body p-5">
                        <form action="{{ route('shipments.track.search') }}" method="GET">
                            <div class="input-group input-group-lg">
                                <input type="text" class="form-control" name="tracking_number" placeholder="Enter tracking number (e.g., SH-2024-0001)" value="{{ request('tracking_number') }}" required>
                                <button class="btn btn-primary px-4" type="submit">
                                    <i class="bi bi-search me-1"></i> Track
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                @if(isset($shipment))
                <div class="card border-0 shadow-lg">
                    <div class="card-body p-4">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5 class="fw-bold">{{ $shipment->shipment_number }}</h5>
                                <p class="text-muted mb-0">
                                    <i class="bi bi-calendar me-1"></i> {{ $shipment->created_at->format('d M Y') }}
                                </p>
                            </div>
                            <div class="col-md-6 text-md-end">
                                @switch($shipment->status)
                                    @case('pending')
                                        <span class="badge bg-warning text-dark fs-6">Pending</span>
                                        @break
                                    @case('approved')
                                        <span class="badge bg-info fs-6">Approved</span>
                                        @break
                                    @case('in_transit')
                                        <span class="badge bg-primary fs-6">In Transit</span>
                                        @break
                                    @case('delivered')
                                        <span class="badge bg-success fs-6">Delivered</span>
                                        @break
                                    @case('cancelled')
                                        <span class="badge bg-danger fs-6">Cancelled</span>
                                        @break
                                @endswitch
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="text-muted">From</h6>
                                <p class="mb-0 fw-semibold">{{ $shipment->sender_city }}</p>
                                <small class="text-muted">{{ $shipment->sender_address }}</small>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted">To</h6>
                                <p class="mb-0 fw-semibold">{{ $shipment->recipient_city }}</p>
                                <small class="text-muted">{{ $shipment->recipient_address }}</small>
                            </div>
                        </div>

                        <div class="bg-light rounded p-3 mb-4 text-center">
                            <small class="text-muted d-block">Current Location</small>
                            <span class="fw-semibold" id="currentLocation">
                                {{ $shipment->trackingHistory->first()->location ?? 'Awaiting pickup' }}
                            </span>
                        </div>

                        <div class="mb-4" style="height: 200px; background: #e9ecef; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                            <div class="text-center text-muted">
                                <i class="bi bi-map" style="font-size: 2rem;"></i>
                                <p class="mt-2 mb-0">Map View</p>
                            </div>
                        </div>

                        <h6 class="fw-semibold mb-3">Tracking History</h6>
                        <div class="tracking-timeline">
                            @foreach($shipment->trackingHistory as $track)
                            <div class="timeline-item">
                                <div class="timeline-dot bg-{{ $loop->first ? 'primary' : 'secondary' }}"></div>
                                <div class="timeline-content">
                                    <div class="d-flex justify-content-between">
                                        <h6 class="mb-0">{{ ucfirst(str_replace('_', ' ', $track->status)) }}</h6>
                                        <small class="text-muted">{{ $track->created_at->format('d M Y, h:i A') }}</small>
                                    </div>
                                    <p class="mb-0 text-muted small">{{ $track->location ?? '' }}</p>
                                    @if($track->description)
                                    <p class="mb-0 small">{{ $track->description }}</p>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @elseif(request('tracking_number'))
                <div class="card border-0 shadow-lg">
                    <div class="card-body p-5 text-center">
                        <i class="bi bi-exclamation-circle text-warning" style="font-size: 3rem;"></i>
                        <h5 class="mt-3">Shipment Not Found</h5>
                        <p class="text-muted">No shipment found with tracking number "{{ request('tracking_number') }}"</p>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .tracking-timeline { position: relative; padding-left: 2rem; }
    .tracking-timeline::before { content: ''; position: absolute; left: 8px; top: 0; bottom: 0; width: 2px; background: #dee2e6; }
    .timeline-item { position: relative; padding-bottom: 1.2rem; }
    .timeline-item:last-child { padding-bottom: 0; }
    .timeline-dot { position: absolute; left: -2rem; top: 4px; width: 16px; height: 16px; border-radius: 50%; border: 3px solid #fff; box-shadow: 0 0 0 2px #dee2e6; }
    body { background: #f8f9fa; }
</style>
@endpush

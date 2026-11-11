@extends('layouts.guest')

@section('title', 'Tracking: ' . $shipment->tracking_number . ' - ' . config('app.name'))

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
    .tracking-nav {
        background: #1a1a2e;
    }
    .tracking-brand {
        font-weight: 800;
        letter-spacing: -0.5px;
    }
    .tracking-brand span {
        color: #ffc107;
    }
    .tracking-nav .nav-link {
        color: rgba(255,255,255,0.85);
        font-weight: 500;
    }
    .tracking-nav .nav-link:hover {
        color: #ffc107;
    }
    .status-badge {
        font-size: 0.85rem;
        padding: 6px 16px;
        border-radius: 50px;
        font-weight: 600;
    }
    .info-label {
        font-size: 0.8rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
    }
    .info-value {
        font-size: 1rem;
        font-weight: 600;
        color: #212529;
    }
    .tracking-header {
        background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 50%, #6610f2 100%);
    }
    .timeline {
        position: relative;
        padding-left: 32px;
    }
    .timeline::before {
        content: '';
        position: absolute;
        left: 11px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #dee2e6;
    }
    .timeline-item {
        position: relative;
        padding-bottom: 24px;
    }
    .timeline-item:last-child {
        padding-bottom: 0;
    }
    .timeline-item .timeline-dot {
        position: absolute;
        left: -26px;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: #fff;
        border: 2px solid #dee2e6;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
    }
    .timeline-item.completed .timeline-dot {
        background: #198754;
        border-color: #198754;
        color: #fff;
    }
    .timeline-item.active .timeline-dot {
        background: #0d6efd;
        border-color: #0d6efd;
        color: #fff;
        box-shadow: 0 0 0 4px rgba(13,110,253,0.2);
    }
    .timeline-item.completed .timeline-dot i,
    .timeline-item.active .timeline-dot i {
        font-size: 10px;
    }
    .timeline-item.completed::before {
        background: #198754;
    }
</style>
@endpush

@section('content')
<nav class="navbar navbar-expand-lg tracking-nav py-2">
    <div class="container">
        <a class="navbar-brand tracking-brand text-white" href="{{ url('/') }}">
            <i class="bi bi-truck"></i> Logi<span>Pro</span>
        </a>
        <div class="ms-auto">
            <a href="{{ route('tracking.index') }}" class="btn btn-outline-light btn-sm">
                <i class="bi bi-search"></i> Track Another
            </a>
        </div>
    </div>
</nav>

<div class="tracking-header py-4">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center text-white">
            <div>
                <small class="text-white-50">Tracking Number</small>
                <h3 class="fw-bold mb-0">{{ $shipment->tracking_number }}</h3>
            </div>
            @php
                $statusClasses = [
                    'pending' => 'bg-warning text-dark',
                    'picked_up' => 'bg-info text-white',
                    'in_transit' => 'bg-primary text-white',
                    'in_transit_to_hub' => 'bg-primary text-white',
                    'at_hub' => 'bg-secondary text-white',
                    'out_for_delivery' => 'bg-info text-white',
                    'delivered' => 'bg-success text-white',
                    'failed' => 'bg-danger text-white',
                    'cancelled' => 'bg-dark text-white',
                ];
                $statusLabels = [
                    'pending' => 'Pending',
                    'picked_up' => 'Picked Up',
                    'in_transit' => 'In Transit',
                    'in_transit_to_hub' => 'In Transit to Hub',
                    'at_hub' => 'At Hub',
                    'out_for_delivery' => 'Out for Delivery',
                    'delivered' => 'Delivered',
                    'failed' => 'Failed',
                    'cancelled' => 'Cancelled',
                ];
                $statusClass = $statusClasses[$shipment->status] ?? 'bg-secondary';
                $statusLabel = $statusLabels[$shipment->status] ?? ucfirst($shipment->status);
            @endphp
            <span class="status-badge {{ $statusClass }}">
                <i class="bi bi-circle-fill me-1 small"></i> {{ $statusLabel }}
            </span>
        </div>
    </div>
</div>

<div class="container py-4">
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-box-arrow-up text-primary me-2"></i>Shipper</h5>
                    <h6 class="fw-bold mb-1">{{ $shipment->shipper_name ?? 'N/A' }}</h6>
                    <p class="text-muted small mb-1"><i class="bi bi-telephone me-1"></i>{{ $shipment->shipper_phone ?? 'N/A' }}</p>
                    <p class="text-muted small mb-0"><i class="bi bi-geo-alt me-1"></i>{{ $shipment->shipper_address ?? 'N/A' }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-box-arrow-in-down text-success me-2"></i>Consignee</h5>
                    <h6 class="fw-bold mb-1">{{ $shipment->consignee_name ?? 'N/A' }}</h6>
                    <p class="text-muted small mb-1"><i class="bi bi-telephone me-1"></i>{{ $shipment->consignee_phone ?? 'N/A' }}</p>
                    <p class="text-muted small mb-0"><i class="bi bi-geo-alt me-1"></i>{{ $shipment->consignee_address ?? 'N/A' }}</p>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4"><i class="bi bi-clock-history text-info me-2"></i>Tracking Timeline</h5>
                    @php $trackingEvents = $details['status_history'] ?? null; @endphp
                    @if($trackingEvents && count($trackingEvents) > 0)
                        <div class="timeline">
                            @foreach($trackingEvents as $index => $event)
                                @php
                                    $isActive = $index === 0;
                                    $itemClass = $isActive ? 'active' : 'completed';
                                    $eventStatus = $event['status'] ?? '';
                                    $eventLabel = $statusLabels[$eventStatus] ?? ucfirst(str_replace('_', ' ', $eventStatus));
                                    $eventDate = isset($event['updated_at']) ? \Carbon\Carbon::parse($event['updated_at']) : null;
                                @endphp
                                <div class="timeline-item {{ $itemClass }}">
                                    <div class="timeline-dot">
                                        @if($isActive)
                                            <i class="bi bi-circle-fill"></i>
                                        @else
                                            <i class="bi bi-check"></i>
                                        @endif
                                    </div>
                                    <div class="ps-3">
                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                            <h6 class="mb-0 fw-bold">{{ $eventLabel }}</h6>
                                            @if($eventDate)
                                                <small class="text-muted">
                                                    <i class="bi bi-clock me-1"></i>{{ $eventDate->format('d M Y, h:i A') }}
                                                </small>
                                            @endif
                                        </div>
                                        @if(!empty($event['location']))
                                            <small class="text-muted d-block mt-1"><i class="bi bi-geo-alt me-1"></i>{{ $event['location'] }}</small>
                                        @endif
                                        @if(!empty($event['description']))
                                            <p class="text-muted small mb-0 mt-1">{{ $event['description'] }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted text-center py-3 mb-0">No tracking events available yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@endpush
@endsection

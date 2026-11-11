@extends('layouts.guest')

@section('title', 'Track Your Shipment - ' . config('app.name'))

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
    .tracking-hero {
        background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 50%, #6610f2 100%);
        min-height: 60vh;
        display: flex;
        align-items: center;
        position: relative;
        overflow: hidden;
    }
    .tracking-hero::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, transparent 70%);
        animation: trackingPulse 8s ease-in-out infinite;
    }
    @keyframes trackingPulse {
        0%, 100% { transform: scale(1); opacity: 0.5; }
        50% { transform: scale(1.1); opacity: 1; }
    }
    .tracking-hero-content {
        position: relative;
        z-index: 1;
    }
    .tracking-brand {
        font-weight: 800;
        letter-spacing: -0.5px;
    }
    .tracking-brand span {
        color: #ffc107;
    }
    .search-box {
        max-width: 650px;
        margin: 0 auto;
    }
    .search-box .form-control {
        height: 60px;
        font-size: 1.1rem;
        border-radius: 50px 0 0 50px;
        border: 2px solid #fff;
        border-right: none;
        padding-left: 24px;
    }
    .search-box .form-control:focus {
        box-shadow: none;
        border-color: #ffc107;
    }
    .search-box .btn-search {
        height: 60px;
        border-radius: 0 50px 50px 0;
        padding: 0 36px;
        font-size: 1.1rem;
        font-weight: 600;
        background: #ffc107;
        border: 2px solid #ffc107;
        color: #1a1a2e;
    }
    .search-box .btn-search:hover {
        background: #e6ae06;
        border-color: #e6ae06;
    }
    .search-box .input-group {
        box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        border-radius: 50px;
    }
    .tracking-nav {
        background: rgba(0,0,0,0.15);
        backdrop-filter: blur(10px);
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        z-index: 10;
    }
    .tracking-nav .navbar-brand {
        font-weight: 700;
        color: #fff;
    }
    .tracking-nav .nav-link {
        color: rgba(255,255,255,0.85);
        font-weight: 500;
    }
    .tracking-nav .nav-link:hover,
    .tracking-nav .nav-link.active {
        color: #ffc107;
    }
    .result-section {
        margin-top: -80px;
        position: relative;
        z-index: 2;
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
    .feature-footer {
        background: #1a1a2e;
        color: rgba(255,255,255,0.7);
    }
    .feature-footer a {
        color: rgba(255,255,255,0.7);
        text-decoration: none;
    }
    .feature-footer a:hover {
        color: #ffc107;
    }
    .tracking-stats {
        background: rgba(255,255,255,0.1);
        border-radius: 12px;
        padding: 20px;
        backdrop-filter: blur(5px);
    }
    @media (max-width: 768px) {
        .tracking-hero { min-height: 50vh; }
        .search-box .form-control,
        .search-box .btn-search { height: 50px; font-size: 1rem; }
        .result-section { margin-top: -40px; }
    }
</style>
@endpush

@section('content')
<nav class="navbar navbar-expand-lg tracking-nav py-2">
    <div class="container">
        <a class="navbar-brand tracking-brand" href="{{ url('/') }}">
            <i class="bi bi-truck"></i> Logi<span>Pro</span>
        </a>
        <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#trackingNav">
            <i class="bi bi-list fs-4"></i>
        </button>
        <div class="collapse navbar-collapse" id="trackingNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="{{ route('tracking.public') }}">
                        <i class="bi bi-search"></i> Track Shipment
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/') }}">
                        <i class="bi bi-house"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('login') }}">
                        <i class="bi bi-box-arrow-in-right"></i> Login
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<section class="tracking-hero">
    <div class="container tracking-hero-content text-center text-white">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="mb-4">
                    <h1 class="display-4 fw-bold mb-2">Track Your Shipment</h1>
                    <p class="lead text-white-50">Enter your tracking number to get real-time updates on your cargo</p>
                </div>

                <form action="{{ route('tracking.public') }}" method="GET" class="search-box">
                    <div class="input-group">
                        <input type="text" name="tracking_number" class="form-control"
                               placeholder="e.g. KHR-2024-000001" value="{{ request('tracking_number') }}"
                               autocomplete="off">
                        <button class="btn btn-search" type="submit">
                            <i class="bi bi-search me-1"></i> Track
                        </button>
                    </div>
                </form>

                <div class="tracking-stats mt-4 d-flex justify-content-center gap-4 flex-wrap">
                    <div class="text-center">
                        <div class="fs-3 fw-bold text-warning">10K+</div>
                        <small class="text-white-50">Shipments Delivered</small>
                    </div>
                    <div class="text-center">
                        <div class="fs-3 fw-bold text-warning">500+</div>
                        <small class="text-white-50">Happy Clients</small>
                    </div>
                    <div class="text-center">
                        <div class="fs-3 fw-bold text-warning">25</div>
                        <small class="text-white-50">Provinces Covered</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@isset($shipment)
<div class="result-section">
    <div class="container">
        {{-- Shipment Details Card --}}
        <div class="row g-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                            <div>
                                <span class="info-label">Tracking Number</span>
                                <h4 class="mb-0 fw-bold">{{ $shipment->tracking_number }}</h4>
                            </div>
                            <div class="d-flex align-items-center gap-3">
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
                                <small class="text-muted">
                                    <i class="bi bi-calendar3 me-1"></i>
                                    {{ $shipment->created_at ? $shipment->created_at->format('d M Y, h:i A') : 'N/A' }}
                                </small>
                            </div>
                        </div>
                        <hr>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="info-label">Shipment Type</div>
                                <div class="info-value">{{ ucfirst($shipment->type ?? 'Standard') }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="info-label">Service</div>
                                <div class="info-value">{{ $shipment->service_level ?? 'Standard Delivery' }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="info-label">Estimated Delivery</div>
                                <div class="info-value">{{ $shipment->estimated_delivery_date ? $shipment->estimated_delivery_date->format('d M Y') : 'TBD' }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="info-label">Last Updated</div>
                                <div class="info-value">{{ $shipment->updated_at ? $shipment->updated_at->format('d M Y, h:i A') : 'N/A' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Shipper & Consignee --}}
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="bi bi-box-arrow-up text-primary"></i>
                            </div>
                            <h5 class="mb-0 fw-bold">Shipper</h5>
                        </div>
                        <div class="ps-2">
                            <h6 class="fw-bold mb-1">{{ $shipment->shipper_name ?? 'N/A' }}</h6>
                            <p class="text-muted small mb-1">
                                <i class="bi bi-telephone me-1"></i>{{ $shipment->shipper_phone ?? 'N/A' }}
                            </p>
                            <p class="text-muted small mb-0">
                                <i class="bi bi-geo-alt me-1"></i>{{ $shipment->shipper_address ?? 'N/A' }}
                            </p>
                            @if($shipment->shipper_email)
                                <p class="text-muted small mb-0">
                                    <i class="bi bi-envelope me-1"></i>{{ $shipment->shipper_email }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="bi bi-box-arrow-in-down text-success"></i>
                            </div>
                            <h5 class="mb-0 fw-bold">Consignee</h5>
                        </div>
                        <div class="ps-2">
                            <h6 class="fw-bold mb-1">{{ $shipment->consignee_name ?? 'N/A' }}</h6>
                            <p class="text-muted small mb-1">
                                <i class="bi bi-telephone me-1"></i>{{ $shipment->consignee_phone ?? 'N/A' }}
                            </p>
                            <p class="text-muted small mb-0">
                                <i class="bi bi-geo-alt me-1"></i>{{ $shipment->consignee_address ?? 'N/A' }}
                            </p>
                            @if($shipment->consignee_email)
                                <p class="text-muted small mb-0">
                                    <i class="bi bi-envelope me-1"></i>{{ $shipment->consignee_email }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Cargo Details --}}
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="bi bi-box text-warning"></i>
                            </div>
                            <h5 class="mb-0 fw-bold">Cargo Details</h5>
                        </div>
                        <div class="row g-3 ps-2">
                            <div class="col-4">
                                <div class="info-label">Weight</div>
                                <div class="info-value">{{ $shipment->weight ?? '0' }} kg</div>
                            </div>
                            <div class="col-4">
                                <div class="info-label">Volume</div>
                                <div class="info-value">{{ $shipment->volume ?? '0' }} m³</div>
                            </div>
                            <div class="col-4">
                                <div class="info-label">Pieces</div>
                                <div class="info-value">{{ $shipment->pieces ?? '0' }}</div>
                            </div>
                            @if($shipment->description)
                                <div class="col-12">
                                    <div class="info-label">Description</div>
                                    <div class="info-value small fw-normal">{{ $shipment->description }}</div>
                                </div>
                            @endif
                            @if($shipment->goods_type)
                                <div class="col-6">
                                    <div class="info-label">Goods Type</div>
                                    <div class="info-value">{{ $shipment->goods_type }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Current Location / Status --}}
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="bi bi-geo-alt-fill text-danger"></i>
                            </div>
                            <h5 class="mb-0 fw-bold">Current Status</h5>
                        </div>
                        <div class="ps-2">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="status-badge {{ $statusClass }}">
                                    <i class="bi bi-circle-fill me-1 small"></i> {{ $statusLabel }}
                                </span>
                            </div>
                            @if($shipment->current_location)
                                <div class="mb-2">
                                    <div class="info-label">Current Location</div>
                                    <div class="info-value">
                                        <i class="bi bi-geo-alt me-1 text-danger"></i>
                                        {{ $shipment->current_location }}
                                    </div>
                                </div>
                            @endif
                            @if($shipment->delivery_date && $shipment->status === 'delivered')
                                <div>
                                    <div class="info-label">Delivered On</div>
                                    <div class="info-value text-success">
                                        <i class="bi bi-check-circle me-1"></i>
                                        {{ $shipment->delivery_date->format('d M Y, h:i A') }}
                                    </div>
                                </div>
                            @endif
                            @if($shipment->pod_signed_by)
                                <div class="mt-2">
                                    <div class="info-label">Signed By</div>
                                    <div class="info-value">{{ $shipment->pod_signed_by }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Status Timeline --}}
            <div class="col-12 mb-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-2 mb-4">
                            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="bi bi-clock-history text-info"></i>
                            </div>
                            <h5 class="mb-0 fw-bold">Tracking Timeline</h5>
                        </div>

                        @if(isset($trackingEvents) && count($trackingEvents) > 0)
                            <div class="timeline">
                                @foreach($trackingEvents as $index => $event)
                                    @php
                                        $isLast = $loop->last;
                                        $isActive = $index === 0;
                                        $itemClass = $isActive ? 'active' : 'completed';
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
                                                <h6 class="mb-0 fw-bold">{{ $event->status_label ?? ucfirst(str_replace('_', ' ', $event->status)) }}</h6>
                                                <small class="text-muted">
                                                    <i class="bi bi-clock me-1"></i>
                                                    {{ $event->created_at ? $event->created_at->format('d M Y, h:i A') : '' }}
                                                </small>
                                            </div>
                                            @if($event->location)
                                                <small class="text-muted d-block mt-1">
                                                    <i class="bi bi-geo-alt me-1"></i>{{ $event->location }}
                                                </small>
                                            @endif
                                            @if($event->remarks)
                                                <p class="text-muted small mb-0 mt-1">{{ $event->remarks }}</p>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                <p class="mb-0">No tracking events available yet. The shipment has been created and is awaiting pickup.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@elseif(request('tracking_number'))
<div class="result-section">
    <div class="container">
        <div class="row justify-content-center mb-5">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm text-center p-5">
                    <div class="py-4">
                        <i class="bi bi-search-heart fs-1 text-muted d-block mb-3"></i>
                        <h5 class="fw-bold">Shipment Not Found</h5>
                        <p class="text-muted mb-0">We couldn't find any shipment with tracking number <strong>{{ request('tracking_number') }}</strong>. Please check the number and try again.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<footer class="feature-footer py-5 mt-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <h5 class="text-white fw-bold mb-3">
                    <i class="bi bi-truck"></i> Logi<span class="text-warning">Pro</span>
                </h5>
                <p class="small">Cambodia's trusted logistics and supply chain management platform. Real-time tracking, reliable delivery.</p>
            </div>
            <div class="col-md-2">
                <h6 class="text-white mb-3">Quick Links</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="{{ url('/') }}">Home</a></li>
                    <li class="mb-2"><a href="{{ route('tracking.public') }}">Track Shipment</a></li>
                    <li class="mb-2"><a href="#">Services</a></li>
                    <li class="mb-2"><a href="#">Contact Us</a></li>
                </ul>
            </div>
            <div class="col-md-3">
                <h6 class="text-white mb-3">Contact</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><i class="bi bi-geo-alt me-1"></i> Phnom Penh, Cambodia</li>
                    <li class="mb-2"><i class="bi bi-telephone me-1"></i> +855 23 888 999</li>
                    <li class="mb-2"><i class="bi bi-envelope me-1"></i> info@logipro.com.kh</li>
                </ul>
            </div>
            <div class="col-md-3">
                <h6 class="text-white mb-3">Operating Hours</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2">Mon - Fri: 7:00 AM - 6:00 PM</li>
                    <li class="mb-2">Saturday: 8:00 AM - 5:00 PM</li>
                    <li class="mb-2">Sunday: Closed</li>
                </ul>
            </div>
        </div>
        <hr class="border-secondary my-4">
        <div class="text-center small">
            &copy; {{ date('Y') }} LogiPro Logistics. All rights reserved. | {{ config('app.name') }}
        </div>
    </div>
</footer>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@endpush
@endsection

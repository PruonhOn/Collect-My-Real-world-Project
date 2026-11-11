@extends('layouts.admin')

@section('title', $vehicle->plate_number . ' - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('vehicles.index') }}">Fleet</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $vehicle->code }}</li>
@endsection

@section('page-title', $vehicle->plate_number)
@section('page-subtitle', $vehicle->make . ' ' . $vehicle->model . ' (' . $vehicle->year . ')')

@section('page-actions')
    <a href="{{ route('vehicles.edit', $vehicle) }}" class="btn btn-primary btn-sm">
        <i class="bi bi-pencil"></i> Edit
    </a>
    <a href="{{ route('vehicle-maintenances.create', ['vehicle_id' => $vehicle->id]) }}"
       class="btn btn-outline-primary btn-sm">
        <i class="bi bi-tools"></i> Add Maintenance
    </a>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0 fw-bold">Vehicle Details</h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5 text-muted small">Code</dt>
                        <dd class="col-sm-7"><span class="badge bg-secondary">{{ $vehicle->code }}</span></dd>

                        <dt class="col-sm-5 text-muted small">Plate #</dt>
                        <dd class="col-sm-7 fw-medium">{{ $vehicle->plate_number }}</dd>

                        <dt class="col-sm-5 text-muted small">Type</dt>
                        <dd class="col-sm-7">{{ $vehicle->vehicleType?->name ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted small">Make</dt>
                        <dd class="col-sm-7">{{ $vehicle->make }}</dd>

                        <dt class="col-sm-5 text-muted small">Model</dt>
                        <dd class="col-sm-7">{{ $vehicle->model }}</dd>

                        <dt class="col-sm-5 text-muted small">Year</dt>
                        <dd class="col-sm-7">{{ $vehicle->year ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted small">Color</dt>
                        <dd class="col-sm-7">{{ $vehicle->color ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted small">Status</dt>
                        <dd class="col-sm-7">
                            @php
                                $sc = ['active' => 'bg-success', 'in-maintenance' => 'bg-warning text-dark', 'out-of-service' => 'bg-danger', 'retired' => 'bg-secondary'];
                            @endphp
                            <span class="badge {{ $sc[$vehicle->status] ?? 'bg-secondary' }}">
                                {{ ucwords(str_replace('-', ' ', $vehicle->status ?? 'active')) }}
                            </span>
                        </dd>

                        <dt class="col-sm-5 text-muted small">Branch</dt>
                        <dd class="col-sm-7">{{ $vehicle->branch?->name ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted small">Ownership</dt>
                        <dd class="col-sm-7">{{ ucfirst($vehicle->ownership ?? 'owned') }}</dd>

                        <dt class="col-sm-5 text-muted small">Purchase Price</dt>
                        <dd class="col-sm-7">{{ $vehicle->purchase_price ? '$' . number_format($vehicle->purchase_price, 2) : '—' }}</dd>

                        <dt class="col-sm-5 text-muted small">Purchase Date</dt>
                        <dd class="col-sm-7">{{ $vehicle->purchase_date?->format('d M Y') ?? '—' }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0 fw-bold">Capacity</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Weight Capacity:</span>
                        <span class="fw-medium">{{ number_format($vehicle->capacity_weight ?? 0, 1) }} kg</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted small">Volume Capacity:</span>
                        <span class="fw-medium">{{ number_format($vehicle->capacity_volume ?? 0, 1) }} m³</span>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0 fw-bold">Alerts</h6>
                </div>
                <div class="card-body">
                    @php
                        $insuranceExpired = $vehicle->insurance_expiry && $vehicle->insurance_expiry->isPast();
                        $insuranceSoon = $vehicle->insurance_expiry && $vehicle->insurance_expiry->isFuture() && $vehicle->insurance_expiry->diffInDays(now()) <= 30;
                        $registrationExpired = $vehicle->registration_expiry && $vehicle->registration_expiry->isPast();
                        $registrationSoon = $vehicle->registration_expiry && $vehicle->registration_expiry->isFuture() && $vehicle->registration_expiry->diffInDays(now()) <= 30;
                    @endphp

                    @if($insuranceExpired)
                        <div class="alert alert-danger mb-2 py-2 small">
                            <i class="bi bi-exclamation-triangle me-1"></i> Insurance expired {{ $vehicle->insurance_expiry->format('d M Y') }}
                        </div>
                    @elseif($insuranceSoon)
                        <div class="alert alert-warning mb-2 py-2 small">
                            <i class="bi bi-exclamation-circle me-1"></i> Insurance expires {{ $vehicle->insurance_expiry->diffForHumans() }}
                        </div>
                    @endif

                    @if($registrationExpired)
                        <div class="alert alert-danger mb-2 py-2 small">
                            <i class="bi bi-exclamation-triangle me-1"></i> Registration expired {{ $vehicle->registration_expiry->format('d M Y') }}
                        </div>
                    @elseif($registrationSoon)
                        <div class="alert alert-warning mb-2 py-2 small">
                            <i class="bi bi-exclamation-circle me-1"></i> Registration expires {{ $vehicle->registration_expiry->diffForHumans() }}
                        </div>
                    @endif

                    @if(!$insuranceExpired && !$insuranceSoon && !$registrationExpired && !$registrationSoon)
                        <div class="text-center text-muted py-2">
                            <i class="bi bi-check-circle text-success d-block mb-1"></i>
                            <small>All documents are up to date</small>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0 fw-bold">Maintenance History</h6>
                    <a href="{{ route('vehicle-maintenances.index', ['vehicle_id' => $vehicle->id]) }}"
                       class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Reference</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th>Cost</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($vehicle->vehicleMaintenances->take(5) as $maint)
                                    <tr>
                                        <td><span class="badge bg-secondary">{{ $maint->reference_number ?? $maint->id }}</span></td>
                                        <td>{{ ucwords(str_replace('_', ' ', $maint->maintenance_type ?? '')) }}</td>
                                        <td>{{ $maint->maintenance_date?->format('d M Y') ?? '—' }}</td>
                                        <td>${{ number_format($maint->cost ?? 0, 2) }}</td>
                                        <td>
                                            @php
                                                $msc = ['pending' => 'bg-warning text-dark', 'in-progress' => 'bg-info', 'completed' => 'bg-success', 'cancelled' => 'bg-secondary'];
                                            @endphp
                                            <span class="badge {{ $msc[$maint->status] ?? 'bg-secondary' }}">
                                                {{ ucfirst($maint->status ?? 'pending') }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            <i class="bi bi-tools fs-2 d-block mb-2"></i>
                                            <p class="mb-0">No maintenance records</p>
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
                    <h6 class="card-title mb-0 fw-bold">Current Deliveries</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Reference</th>
                                    <th>Route</th>
                                    <th>Status</th>
                                    <th>Driver</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($vehicle->deliveries ?? [] as $delivery)
                                    <tr>
                                        <td><span class="badge bg-secondary">{{ $delivery->reference_number ?? $delivery->id }}</span></td>
                                        <td>{{ $delivery->route?->name ?? '—' }}</td>
                                        <td>
                                            <span class="badge bg-info">{{ ucfirst($delivery->status ?? 'pending') }}</span>
                                        </td>
                                        <td>{{ $delivery->driver?->name ?? '—' }}</td>
                                        <td>{{ $delivery->delivery_date?->format('d M Y') ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            <i class="bi bi-box-seam fs-2 d-block mb-2"></i>
                                            <p class="mb-0">No current deliveries</p>
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

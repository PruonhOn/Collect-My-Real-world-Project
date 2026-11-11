@extends('layouts.admin')

@section('title', 'Maintenance #' . $maintenance->id . ' - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('vehicles.index') }}">Fleet</a></li>
    <li class="breadcrumb-item"><a href="{{ route('vehicle-maintenances.index') }}">Maintenance</a></li>
    <li class="breadcrumb-item active" aria-current="page">#{{ $maintenance->id }}</li>
@endsection

@section('page-title', 'Maintenance Record')
@section('page-subtitle', $maintenance->reference_no ?? 'Reference #' . $maintenance->id)

@section('page-actions')
    <a href="{{ route('vehicle-maintenances.edit', $maintenance) }}" class="btn btn-primary btn-sm">
        <i class="bi bi-pencil"></i> Edit
    </a>
    <a href="{{ route('vehicle-maintenances.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Back
    </a>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0 fw-bold">Maintenance Details</h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5 text-muted small">Reference #</dt>
                        <dd class="col-sm-7"><span class="badge bg-secondary">{{ $maintenance->reference_no ?? $maintenance->id }}</span></dd>

                        <dt class="col-sm-5 text-muted small">Type</dt>
                        <dd class="col-sm-7 fw-medium">{{ ucwords(str_replace('_', ' ', $maintenance->maintenance_type ?? '')) }}</dd>

                        <dt class="col-sm-5 text-muted small">Description</dt>
                        <dd class="col-sm-7">{{ $maintenance->description ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted small">Maintenance Date</dt>
                        <dd class="col-sm-7">{{ $maintenance->maintenance_date?->format('d M Y') ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted small">Next Maintenance</dt>
                        <dd class="col-sm-7">
                            @if($maintenance->next_maintenance_date)
                                @if($maintenance->next_maintenance_date->isPast())
                                    <span class="text-danger">{{ $maintenance->next_maintenance_date->format('d M Y') }}</span>
                                @else
                                    {{ $maintenance->next_maintenance_date->format('d M Y') }}
                                @endif
                            @else
                                —
                            @endif
                        </dd>

                        <dt class="col-sm-5 text-muted small">Cost</dt>
                        <dd class="col-sm-7 fw-bold">${{ number_format($maintenance->cost ?? 0, 2) }}</dd>

                        <dt class="col-sm-5 text-muted small">Vendor</dt>
                        <dd class="col-sm-7">{{ $maintenance->vendor ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted small">Status</dt>
                        <dd class="col-sm-7">
                            @php
                                $msc = ['pending' => 'bg-warning text-dark', 'in-progress' => 'bg-info', 'completed' => 'bg-success', 'cancelled' => 'bg-secondary'];
                            @endphp
                            <span class="badge {{ $msc[$maintenance->status] ?? 'bg-secondary' }}">
                                {{ ucfirst($maintenance->status ?? 'pending') }}
                            </span>
                        </dd>

                        <dt class="col-sm-5 text-muted small">Created By</dt>
                        <dd class="col-sm-7">{{ $maintenance->createdBy?->name ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted small">Notes</dt>
                        <dd class="col-sm-7">{{ $maintenance->notes ?? '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0 fw-bold">Vehicle Information</h6>
                </div>
                <div class="card-body">
                    @if($maintenance->vehicle)
                        <div class="row">
                            <div class="col-md-6">
                                <dl class="row mb-0">
                                    <dt class="col-sm-5 text-muted small">Plate #</dt>
                                    <dd class="col-sm-7">
                                        <a href="{{ route('vehicles.show', $maintenance->vehicle) }}" class="text-decoration-none fw-medium">
                                            {{ $maintenance->vehicle->plate_number }}
                                        </a>
                                    </dd>

                                    <dt class="col-sm-5 text-muted small">Make / Model</dt>
                                    <dd class="col-sm-7">{{ $maintenance->vehicle->make }} {{ $maintenance->vehicle->model }}</dd>

                                    <dt class="col-sm-5 text-muted small">Year</dt>
                                    <dd class="col-sm-7">{{ $maintenance->vehicle->year ?? '—' }}</dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <dl class="row mb-0">
                                    <dt class="col-sm-5 text-muted small">Code</dt>
                                    <dd class="col-sm-7"><span class="badge bg-secondary">{{ $maintenance->vehicle->code }}</span></dd>

                                    <dt class="col-sm-5 text-muted small">Status</dt>
                                    <dd class="col-sm-7">
                                        @php
                                            $sc = ['active' => 'bg-success', 'in-maintenance' => 'bg-warning text-dark', 'out-of-service' => 'bg-danger', 'retired' => 'bg-secondary'];
                                        @endphp
                                        <span class="badge {{ $sc[$maintenance->vehicle->status] ?? 'bg-secondary' }}">
                                            {{ ucwords(str_replace('-', ' ', $maintenance->vehicle->status ?? 'active')) }}
                                        </span>
                                    </dd>

                                    <dt class="col-sm-5 text-muted small">Branch</dt>
                                    <dd class="col-sm-7">{{ $maintenance->vehicle->branch?->name ?? '—' }}</dd>
                                </dl>
                            </div>
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-truck fs-2 d-block mb-2"></i>
                            <p class="mb-0">Vehicle information not available</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

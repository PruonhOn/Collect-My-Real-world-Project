@extends('layouts.admin')

@section('title', $driver->name . ' - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('drivers.index') }}">Drivers</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $driver->code }}</li>
@endsection

@section('page-title', $driver->name)
@section('page-subtitle', 'Code: ' . $driver->code . ' | ' . $driver->phone)

@section('page-actions')
    <a href="{{ route('drivers.edit', $driver) }}" class="btn btn-primary btn-sm">
        <i class="bi bi-pencil"></i> Edit
    </a>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="mb-3">
                        @if($driver->photo)
                            <img src="{{ asset('storage/' . $driver->photo) }}" alt="{{ $driver->name }}"
                                 class="rounded-circle border" style="width: 120px; height: 120px; object-fit: cover;">
                        @else
                            <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center border"
                                 style="width: 120px; height: 120px;">
                                <i class="bi bi-person-badge text-primary fs-1"></i>
                            </div>
                        @endif
                    </div>
                    <h5 class="fw-bold mb-1">{{ $driver->name }}</h5>
                    <p class="text-muted small mb-2">{{ $driver->code }}</p>
                    @php
                        $sc = ['available' => 'bg-success', 'on-delivery' => 'bg-info', 'off-duty' => 'bg-warning text-dark', 'on-leave' => 'bg-secondary', 'terminated' => 'bg-danger'];
                    @endphp
                    <span class="badge {{ $sc[$driver->status] ?? 'bg-secondary' }} fs-6">
                        {{ ucwords(str_replace('-', ' ', $driver->status ?? 'available')) }}
                    </span>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0 fw-bold">Contact Information</h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5 text-muted small">Phone</dt>
                        <dd class="col-sm-7">{{ $driver->phone }}</dd>

                        <dt class="col-sm-5 text-muted small">Email</dt>
                        <dd class="col-sm-7">{{ $driver->email ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted small">Address</dt>
                        <dd class="col-sm-7">{{ $driver->address ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted small">Emergency Contact</dt>
                        <dd class="col-sm-7">{{ $driver->emergency_contact ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted small">Emergency Phone</dt>
                        <dd class="col-sm-7">{{ $driver->emergency_phone ?? '—' }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0 fw-bold">License Information</h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5 text-muted small">License #</dt>
                        <dd class="col-sm-7">{{ $driver->license_number ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted small">Type</dt>
                        <dd class="col-sm-7">{{ $driver->license_type ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted small">Expiry</dt>
                        <dd class="col-sm-7">
                            @if($driver->license_expiry)
                                @php
                                    $expired = $driver->license_expiry->isPast();
                                    $soon = $driver->license_expiry->isFuture() && $driver->license_expiry->diffInDays(now()) <= 30;
                                @endphp
                                <span class="badge {{ $expired ? 'bg-danger' : ($soon ? 'bg-warning text-dark' : 'bg-success') }}">
                                    {{ $driver->license_expiry->format('d M Y') }}
                                    @if($expired) (Expired) @elseif($soon) (Expiring Soon) @endif
                                </span>
                            @else
                                —
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0 fw-bold">Employment Details</h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5 text-muted small">Hire Date</dt>
                        <dd class="col-sm-7">{{ $driver->hire_date?->format('d M Y') ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted small">Salary</dt>
                        <dd class="col-sm-7">{{ $driver->salary ? '$' . number_format($driver->salary, 2) : '—' }}</dd>

                        <dt class="col-sm-5 text-muted small">Branch</dt>
                        <dd class="col-sm-7">{{ $driver->branch?->name ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted small">DOB</dt>
                        <dd class="col-sm-7">{{ $driver->date_of_birth?->format('d M Y') ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted small">National ID</dt>
                        <dd class="col-sm-7">{{ $driver->national_id ?? '—' }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0 fw-bold">Performance Stats</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center g-2">
                        <div class="col-6">
                            <h4 class="fw-bold mb-0 text-primary">{{ $stats['total_deliveries'] ?? 0 }}</h4>
                            <small class="text-muted">Deliveries</small>
                        </div>
                        <div class="col-6">
                            <h4 class="fw-bold mb-0 text-success">{{ $stats['completed_deliveries'] ?? 0 }}</h4>
                            <small class="text-muted">Completed</small>
                        </div>
                        <div class="col-6 mt-2">
                            <h4 class="fw-bold mb-0 text-info">{{ $stats['total_distance'] ?? 0 }}</h4>
                            <small class="text-muted">Total km</small>
                        </div>
                        <div class="col-6 mt-2">
                            <h4 class="fw-bold mb-0 text-warning">{{ $stats['rating'] ?? 'N/A' }}</h4>
                            <small class="text-muted">Rating</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0 fw-bold">Current Assignments</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Reference</th>
                                    <th>Vehicle</th>
                                    <th>Route</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($driver->activeDeliveries ?? $driver->deliveries->whereIn('status', ['pending', 'in-transit']) as $delivery)
                                    <tr>
                                        <td><span class="badge bg-secondary">{{ $delivery->reference_number ?? $delivery->id }}</span></td>
                                        <td>{{ $delivery->vehicle?->plate_number ?? '—' }}</td>
                                        <td>{{ $delivery->route?->name ?? '—' }}</td>
                                        <td>
                                            <span class="badge bg-info">{{ ucfirst($delivery->status ?? 'pending') }}</span>
                                        </td>
                                        <td>{{ $delivery->delivery_date?->format('d M Y') ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            <i class="bi bi-truck fs-2 d-block mb-2"></i>
                                            <p class="mb-0">No active assignments</p>
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
                    <h6 class="card-title mb-0 fw-bold">Delivery History</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Reference</th>
                                    <th>Customer</th>
                                    <th>Vehicle</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($driver->deliveries ?? [] as $delivery)
                                    <tr>
                                        <td><span class="badge bg-secondary">{{ $delivery->reference_number ?? $delivery->id }}</span></td>
                                        <td>{{ $delivery->customer?->name ?? '—' }}</td>
                                        <td>{{ $delivery->vehicle?->plate_number ?? '—' }}</td>
                                        <td>
                                            @php
                                                $dsc = ['pending' => 'bg-warning text-dark', 'in-transit' => 'bg-info', 'delivered' => 'bg-success', 'cancelled' => 'bg-danger', 'returned' => 'bg-secondary'];
                                            @endphp
                                            <span class="badge {{ $dsc[$delivery->status] ?? 'bg-secondary' }}">
                                                {{ ucfirst(str_replace('-', ' ', $delivery->status ?? 'pending')) }}
                                            </span>
                                        </td>
                                        <td>{{ $delivery->delivery_date?->format('d M Y') ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            <i class="bi bi-box-seam fs-2 d-block mb-2"></i>
                                            <p class="mb-0">No delivery history</p>
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

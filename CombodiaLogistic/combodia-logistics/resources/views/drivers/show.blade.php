@extends('layouts.admin')

@section('title', 'Driver Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Driver Details</h4>
    <div>
        <a href="{{ route('admin.drivers.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <a href="{{ route('admin.drivers.edit', $driver) }}" class="btn btn-primary btn-sm">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
    </div>
</div>

<x-breadcrumb :items="[
    ['label' => 'Drivers', 'url' => route('admin.drivers.index')],
    ['label' => $driver->name, 'active' => true],
]" />

<div class="row">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0">Driver Information</h6>
            </div>
            <div class="card-body">
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Code</label>
                    <p class="fw-medium mb-0">{{ $driver->code }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Name</label>
                    <p class="fw-medium mb-0">{{ $driver->name }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Email</label>
                    <p class="fw-medium mb-0">{{ $driver->email }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Phone</label>
                    <p class="fw-medium mb-0">{{ $driver->phone }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">License Number</label>
                    <p class="fw-medium mb-0">{{ $driver->license_number }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">License Expiry</label>
                    <p class="fw-medium mb-0">{{ $driver->license_expiry ? \Carbon\Carbon::parse($driver->license_expiry)->format('d M Y') : '-' }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Status</label>
                    <p class="fw-medium mb-0">
                        @if($driver->status === 'active')
                            <span class="badge bg-success">Active</span>
                        @elseif($driver->status === 'inactive')
                            <span class="badge bg-danger">Inactive</span>
                        @elseif($driver->status === 'on_leave')
                            <span class="badge bg-warning text-dark">On Leave</span>
                        @else
                            <span class="badge bg-secondary">{{ ucfirst($driver->status) }}</span>
                        @endif
                    </p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Branch</label>
                    <p class="fw-medium mb-0">{{ $driver->branch->name ?? '-' }}</p>
                </div>
                <div class="mb-0">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Vehicle Plate Number</label>
                    <p class="fw-medium mb-0">{{ $driver->vehicle->plate_number ?? '-' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Documents</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Document Name</th>
                                <th>Document Type</th>
                                <th>Expiry Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($driver->documents as $document)
                                <tr>
                                    <td>{{ $document->name }}</td>
                                    <td>{{ $document->type ?? '-' }}</td>
                                    <td>{{ $document->expiry_date ? \Carbon\Carbon::parse($document->expiry_date)->format('d M Y') : '-' }}</td>
                                    <td>
                                        @if($document->status === 'valid')
                                            <span class="badge bg-success">Valid</span>
                                        @elseif($document->status === 'expired')
                                            <span class="badge bg-danger">Expired</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst($document->status) }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No documents found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Schedules</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Route</th>
                                <th>Date</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($driver->schedules as $schedule)
                                <tr>
                                    <td>{{ $schedule->route->name ?? '-' }}</td>
                                    <td>{{ $schedule->date ? \Carbon\Carbon::parse($schedule->date)->format('d M Y') : '-' }}</td>
                                    <td>{{ $schedule->start_time ?? '-' }}</td>
                                    <td>{{ $schedule->end_time ?? '-' }}</td>
                                    <td>
                                        @if($schedule->status === 'completed')
                                            <span class="badge bg-success">Completed</span>
                                        @elseif($schedule->status === 'in_progress')
                                            <span class="badge bg-primary">In Progress</span>
                                        @elseif($schedule->status === 'pending')
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        @elseif($schedule->status === 'cancelled')
                                            <span class="badge bg-danger">Cancelled</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst($schedule->status) }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No schedules found.</td>
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
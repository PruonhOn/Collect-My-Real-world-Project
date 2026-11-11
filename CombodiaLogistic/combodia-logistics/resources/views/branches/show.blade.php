@extends('layouts.admin')

@section('title', 'Branch Details')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Branch Details</h4>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.branches.edit', $branch) }}" class="btn btn-warning">
                <i class="bi bi-pencil-square me-1"></i> Edit
            </a>
            <a href="{{ route('admin.branches.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <x-breadcrumb :items="[
        ['label' => 'Branches', 'url' => route('admin.branches.index')],
        ['label' => 'Branch Details']
    ]" />

    <div class="row">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0">Branch Information</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3 pb-3 border-bottom">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Code</label>
                        <p class="fw-medium mb-0">{{ $branch->code }}</p>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Name</label>
                        <p class="fw-medium mb-0">{{ $branch->name }}</p>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Address</label>
                        <p class="fw-medium mb-0">{{ $branch->address ?? '-' }}</p>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">City</label>
                        <p class="fw-medium mb-0">{{ $branch->city ?? '-' }}</p>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Province</label>
                        <p class="fw-medium mb-0">{{ $branch->province ?? '-' }}</p>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Country</label>
                        <p class="fw-medium mb-0">{{ $branch->country ?? '-' }}</p>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Phone</label>
                        <p class="fw-medium mb-0">{{ $branch->phone ?? '-' }}</p>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Email</label>
                        <p class="fw-medium mb-0">{{ $branch->email ?? '-' }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Status</label>
                        <p class="mb-0">
                            @if($branch->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-danger">Inactive</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            {{-- Warehouses Table --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Warehouses</h6>
                    <span class="badge bg-primary">{{ $warehouses->count() }}</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover DataTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Location</th>
                                    <th>Capacity</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($warehouses as $warehouse)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $warehouse->code }}</td>
                                        <td>{{ $warehouse->name }}</td>
                                        <td>{{ $warehouse->location ?? '-' }}</td>
                                        <td>{{ $warehouse->capacity ?? '-' }}</td>
                                        <td>
                                            @if($warehouse->is_active ?? false)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-secondary">Inactive</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No warehouses found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Vehicles Table --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Vehicles</h6>
                    <span class="badge bg-primary">{{ $vehicles->count() }}</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover DataTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Code</th>
                                    <th>Plate Number</th>
                                    <th>Type</th>
                                    <th>Brand</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($vehicles as $vehicle)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $vehicle->code }}</td>
                                        <td>{{ $vehicle->plate_number }}</td>
                                        <td>{{ ucfirst($vehicle->type) }}</td>
                                        <td>{{ $vehicle->brand ?? '-' }}</td>
                                        <td>
                                            <span class="badge bg-{{ $vehicle->status === 'active' ? 'success' : ($vehicle->status === 'maintenance' ? 'warning' : 'secondary') }}">
                                                {{ ucfirst($vehicle->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No vehicles found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Drivers Table --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Drivers</h6>
                    <span class="badge bg-primary">{{ $drivers->count() }}</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover DataTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Phone</th>
                                    <th>License No.</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($drivers as $driver)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $driver->code }}</td>
                                        <td>{{ $driver->name }}</td>
                                        <td>{{ $driver->phone ?? '-' }}</td>
                                        <td>{{ $driver->license_number ?? '-' }}</td>
                                        <td>
                                            @if($driver->is_active ?? false)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-secondary">Inactive</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No drivers found.</td>
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

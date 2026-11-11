<x-admin-layout>
    @section('title', $branch->name . ' - Branches - ' . config('app.name'))

    @section('breadcrumbs')
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('branches.index') }}">Branches</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $branch->name }}</li>
    @endsection

    @section('page-title', $branch->name)
    @section('page-subtitle', $branch->code . ' · ' . $branch->city . ', ' . ($branch->province ?? 'N/A'))

    @section('page-actions')
        <a href="{{ route('branches.edit', $branch) }}" class="btn btn-primary">
            <i class="bi bi-pencil me-1"></i> Edit Branch
        </a>
    @endsection

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px;">
                        <i class="bi bi-building text-primary fs-1"></i>
                    </div>
                    <h5 class="fw-bold mb-1">{{ $branch->name }}</h5>
                    <span class="badge bg-secondary mb-3">{{ $branch->code }}</span>
                    <div class="mt-2">
                        @if($branch->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-danger">Inactive</span>
                        @endif
                    </div>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Phone</span>
                        <span class="fw-medium">{{ $branch->phone ?? '—' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Email</span>
                        <span class="fw-medium">{{ $branch->email ?? '—' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Manager</span>
                        <span class="fw-medium">{{ $branch->manager_name ?? '—' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Address</span>
                        <span class="fw-medium text-end" style="max-width: 200px;">{{ $branch->address ?? '—' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">City</span>
                        <span class="fw-medium">{{ $branch->city }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Province</span>
                        <span class="fw-medium">{{ $branch->province ?? '—' }}</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="row g-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold">Assigned Staff</h5>
                            <span class="badge bg-primary">{{ $branch->users->count() }}</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($branch->users as $user)
                                            <tr>
                                                <td class="fw-medium">{{ $user->name }}</td>
                                                <td>{{ $user->email }}</td>
                                                <td>{{ $user->roles->pluck('name')->implode(', ') ?: '—' }}</td>
                                                <td>
                                                    @if($user->is_active ?? true)
                                                        <span class="badge bg-success">Active</span>
                                                    @else
                                                        <span class="badge bg-danger">Inactive</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-3">No staff assigned</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold">Warehouses</h5>
                            <span class="badge bg-primary">{{ $branch->warehouses->count() }}</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Name</th>
                                            <th>Code</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($branch->warehouses as $warehouse)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('warehouses.show', $warehouse) }}" class="text-decoration-none">{{ $warehouse->name }}</a>
                                                </td>
                                                <td><span class="badge bg-secondary">{{ $warehouse->code }}</span></td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="2" class="text-center text-muted py-3">No warehouses</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold">Vehicles</h5>
                            <span class="badge bg-primary">{{ $branch->vehicles->count() }}</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Plate #</th>
                                            <th>Type</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($branch->vehicles as $vehicle)
                                            <tr>
                                                <td class="fw-medium">{{ $vehicle->plate_number }}</td>
                                                <td>{{ $vehicle->type ?? '—' }}</td>
                                                <td>
                                                    @if(($vehicle->status ?? 'active') === 'active')
                                                        <span class="badge bg-success">Active</span>
                                                    @else
                                                        <span class="badge bg-warning">{{ ucfirst($vehicle->status ?? 'unknown') }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center text-muted py-3">No vehicles</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold">Recent Shipments</h5>
                            <span class="badge bg-primary">{{ $branch->shipments->count() }}</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Tracking #</th>
                                            <th>Type</th>
                                            <th>Origin</th>
                                            <th>Destination</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($branch->shipments->take(10) as $shipment)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('shipments.show', $shipment) }}" class="text-decoration-none fw-medium">{{ $shipment->tracking_number }}</a>
                                                </td>
                                                <td>{{ ucfirst($shipment->type) }}</td>
                                                <td>{{ $shipment->origin_city ?? '—' }}</td>
                                                <td>{{ $shipment->destination_city ?? '—' }}</td>
                                                <td>
                                                    @php
                                                        $statusColors = ['pending' => 'warning', 'in_transit' => 'info', 'delivered' => 'success', 'cancelled' => 'danger'];
                                                        $color = $statusColors[$shipment->status] ?? 'secondary';
                                                    @endphp
                                                    <span class="badge bg-{{ $color }}">{{ str_replace('_', ' ', ucfirst($shipment->status)) }}</span>
                                                </td>
                                                <td>{{ $shipment->created_at->format('d M Y') }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center text-muted py-3">No shipments from this branch</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>

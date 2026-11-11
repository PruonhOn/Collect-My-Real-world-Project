@extends('layouts.admin')

@section('title', 'Deliveries - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Deliveries</li>
@endsection

@section('page-title', 'Deliveries')
@section('page-subtitle', 'Manage delivery operations')

@section('page-actions')
    <a href="{{ route('deliveries.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> New Delivery
    </a>
@endsection

@section('content')
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('deliveries.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                        <option value="in_transit" {{ request('status') == 'in_transit' ? 'selected' : '' }}>In Transit</option>
                        <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Delivered</option>
                        <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>Partial</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="from_date" class="form-label">From Date</label>
                    <input type="date" id="from_date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                </div>
                <div class="col-md-3">
                    <label for="to_date" class="form-label">To Date</label>
                    <input type="date" id="to_date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                    <a href="{{ route('deliveries.index') }}" class="btn btn-secondary">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="table-container">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="deliveriesTable">
                <thead class="table-light">
                    <tr>
                        <th>Delivery #</th>
                        <th>Shipment #</th>
                        <th>Driver</th>
                        <th>Vehicle</th>
                        <th>Scheduled Date</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deliveries as $delivery)
                        <tr>
                            <td>
                                <a href="{{ route('deliveries.show', $delivery) }}" class="text-decoration-none fw-medium">
                                    #{{ $delivery->delivery_number }}
                                </a>
                            </td>
                            <td>
                                @if($delivery->shipment)
                                    <a href="{{ route('shipments.show', $delivery->shipment) }}" class="text-decoration-none">
                                        #{{ $delivery->shipment->shipment_number }}
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($delivery->driver)
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-info bg-opacity-10 d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                            <i class="bi bi-person text-info"></i>
                                        </div>
                                        {{ $delivery->driver->name }}
                                    </div>
                                @else
                                    <span class="text-muted">Not assigned</span>
                                @endif
                            </td>
                            <td>
                                @if($delivery->vehicle)
                                    <span class="badge bg-secondary">{{ $delivery->vehicle->plate_number }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <i class="bi bi-calendar3 me-1 text-muted"></i>
                                {{ $delivery->scheduled_date->format('d M Y') }}
                            </td>
                            <td>
                                @php
                                    $statusClass = match($delivery->status) {
                                        'delivered' => 'success',
                                        'in_transit' => 'primary',
                                        'scheduled' => 'warning',
                                        'partial' => 'info',
                                        'cancelled' => 'danger',
                                        default => 'secondary'
                                    };
                                @endphp
                                <span class="badge bg-{{ $statusClass }}">
                                    @if($delivery->status == 'in_transit') In Transit
                                    @else {{ ucfirst($delivery->status) }}
                                    @endif
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('deliveries.show', $delivery) }}" class="btn btn-outline-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('deliveries.edit', $delivery) }}" class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger" title="Delete"
                                            onclick="confirmDelete({{ $delivery->id }})">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                                <form id="delete-form-{{ $delivery->id }}" action="{{ route('deliveries.destroy', $delivery) }}" method="POST" class="d-none">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-truck fs-1 d-block mb-2"></i>
                                <p class="mb-0">No deliveries found</p>
                                <a href="{{ route('deliveries.create') }}" class="btn btn-sm btn-primary mt-2">Create Delivery</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($deliveries, 'links'))
            <div class="d-flex justify-content-end mt-2">
                {{ $deliveries->links() }}
            </div>
        @endif
    </div>
@endsection

@section('scripts')
    <script>
        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this delivery?')) {
                document.getElementById('delete-form-' + id).submit();
            }
        }
    </script>
@endsection

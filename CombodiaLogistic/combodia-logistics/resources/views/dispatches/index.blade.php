@extends('layouts.admin')

@section('title', 'Dispatches')
@section('subtitle', 'Manage all dispatches')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Dispatches</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Dispatches</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('dispatches.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> New Dispatch
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="dataTable">
                <thead class="table-light">
                    <tr>
                        <th>Dispatch #</th>
                        <th>Shipment #</th>
                        <th>Route</th>
                        <th>Status</th>
                        <th>Scheduled Date</th>
                        <th>Drivers</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($dispatches as $dispatch)
                    <tr>
                        <td><a href="{{ route('dispatches.show', $dispatch) }}" class="fw-semibold text-decoration-none">{{ $dispatch->dispatch_number }}</a></td>
                        <td><a href="{{ route('shipments.show', $dispatch->shipment) }}" class="text-decoration-none">{{ $dispatch->shipment->shipment_number ?? 'N/A' }}</a></td>
                        <td>{{ $dispatch->route->name ?? 'N/A' }}</td>
                        <td>
                            @switch($dispatch->status)
                                @case('pending')
                                    <span class="badge bg-warning text-dark">Pending</span>
                                    @break
                                @case('assigned')
                                    <span class="badge bg-info">Assigned</span>
                                    @break
                                @case('in_transit')
                                    <span class="badge bg-primary">In Transit</span>
                                    @break
                                @case('completed')
                                    <span class="badge bg-success">Completed</span>
                                    @break
                                @case('cancelled')
                                    <span class="badge bg-danger">Cancelled</span>
                                    @break
                                @default
                                    <span class="badge bg-secondary">{{ ucfirst($dispatch->status) }}</span>
                            @endswitch
                        </td>
                        <td>{{ $dispatch->scheduled_date ? \Carbon\Carbon::parse($dispatch->scheduled_date)->format('d M Y, h:i A') : '-' }}</td>
                        <td>
                            @foreach($dispatch->drivers as $driver)
                                <span class="badge bg-light text-dark">{{ $driver->name }}</span>
                            @endforeach
                            @if($dispatch->drivers->isEmpty())
                                <span class="text-muted">Unassigned</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('dispatches.show', $dispatch) }}"><i class="bi bi-eye me-2"></i>View</a></li>
                                    <li><a class="dropdown-item" href="{{ route('dispatches.edit', $dispatch) }}"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                            <p class="mt-2">No dispatches found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<style>
    .table th { font-weight: 600; font-size: 0.813rem; text-transform: uppercase; letter-spacing: 0.5px; color: #6c757d; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        $('#dataTable').DataTable({
            order: [[0, 'desc']],
            pageLength: 25,
            responsive: true
        });
    });
</script>
@endpush

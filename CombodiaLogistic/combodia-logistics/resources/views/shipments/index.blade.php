@extends('layouts.admin')

@section('title', 'Shipments')
@section('subtitle', 'Manage all shipments')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Shipments</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Shipments</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('shipments.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> New Shipment
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select class="form-select" id="filterStatus">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="in_transit">In Transit</option>
                    <option value="delivered">Delivered</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Type</label>
                <select class="form-select" id="filterType">
                    <option value="">All Types</option>
                    <option value="express">Express</option>
                    <option value="standard">Standard</option>
                    <option value="economy">Economy</option>
                    <option value="freight">Freight</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">From Date</label>
                <input type="date" class="form-control" id="filterDateFrom">
            </div>
            <div class="col-md-3">
                <label class="form-label">To Date</label>
                <input type="date" class="form-control" id="filterDateTo">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle" id="dataTable">
                <thead class="table-light">
                    <tr>
                        <th>Shipment #</th>
                        <th>Customer</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Weight (kg)</th>
                        <th>Total</th>
                        <th>Created</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($shipments as $shipment)
                    <tr>
                        <td><a href="{{ route('shipments.show', $shipment) }}" class="fw-semibold text-decoration-none">{{ $shipment->shipment_number }}</a></td>
                        <td>{{ $shipment->customer->name ?? 'N/A' }}</td>
                        <td><span class="badge bg-light text-dark">{{ ucfirst($shipment->type) }}</span></td>
                        <td>
                            @switch($shipment->status)
                                @case('pending')
                                    <span class="badge bg-warning text-dark">Pending</span>
                                    @break
                                @case('approved')
                                    <span class="badge bg-info">Approved</span>
                                    @break
                                @case('in_transit')
                                    <span class="badge bg-primary">In Transit</span>
                                    @break
                                @case('delivered')
                                    <span class="badge bg-success">Delivered</span>
                                    @break
                                @case('cancelled')
                                    <span class="badge bg-danger">Cancelled</span>
                                    @break
                                @default
                                    <span class="badge bg-secondary">{{ ucfirst($shipment->status) }}</span>
                            @endswitch
                        </td>
                        <td>
                            @switch($shipment->priority)
                                @case('high')
                                    <span class="badge bg-danger">High</span>
                                    @break
                                @case('medium')
                                    <span class="badge bg-warning text-dark">Medium</span>
                                    @break
                                @case('low')
                                    <span class="badge bg-success">Low</span>
                                    @break
                                @default
                                    <span class="badge bg-secondary">Normal</span>
                            @endswitch
                        </td>
                        <td>{{ number_format($shipment->total_weight ?? 0, 2) }}</td>
                        <td>${{ number_format($shipment->total_amount ?? 0, 2) }}</td>
                        <td>{{ $shipment->created_at->format('d M Y') }}</td>
                        <td class="text-center">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('shipments.show', $shipment) }}"><i class="bi bi-eye me-2"></i>View</a></li>
                                    <li><a class="dropdown-item" href="{{ route('shipments.edit', $shipment) }}"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                    @if($shipment->status === 'pending')
                                    <li><a class="dropdown-item text-success" href="#" onclick="approveShipment({{ $shipment->id }})"><i class="bi bi-check-circle me-2"></i>Approve</a></li>
                                    @endif
                                    @if(!in_array($shipment->status, ['cancelled', 'delivered']))
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="#" onclick="cancelShipment({{ $shipment->id }})"><i class="bi bi-x-circle me-2"></i>Cancel</a></li>
                                    @endif
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                            <p class="mt-2">No shipments found</p>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        $('#dataTable').DataTable({
            order: [[0, 'desc']],
            pageLength: 25,
            responsive: true
        });
    });

    function approveShipment(id) {
        Swal.fire({
            title: 'Approve Shipment?',
            text: 'This will approve the shipment for processing.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Approve'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `/admin/shipments/${id}/approve`;
            }
        });
    }

    function cancelShipment(id) {
        Swal.fire({
            title: 'Cancel Shipment?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `/admin/shipments/${id}/cancel`;
            }
        });
    }
</script>
@endpush

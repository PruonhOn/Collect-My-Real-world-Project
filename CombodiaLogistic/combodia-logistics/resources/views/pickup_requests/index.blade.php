@extends('layouts.admin')

@section('title', 'Pickup Requests')
@section('subtitle', 'Manage pickup requests')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Pickup Requests</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Pickup Requests</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('pickup-requests.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> New Request
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="dataTable">
                <thead class="table-light">
                    <tr>
                        <th>Request #</th>
                        <th>Customer</th>
                        <th>Address</th>
                        <th>Scheduled Date</th>
                        <th>Scheduled Time</th>
                        <th>Status</th>
                        <th>Assigned Driver</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pickupRequests as $request)
                    <tr>
                        <td><a href="{{ route('pickup-requests.show', $request) }}" class="fw-semibold text-decoration-none">{{ $request->request_number }}</a></td>
                        <td>{{ $request->customer->name ?? 'N/A' }}</td>
                        <td>{{ Str::limit($request->pickup_address, 40) }}</td>
                        <td>{{ $request->scheduled_date ? \Carbon\Carbon::parse($request->scheduled_date)->format('d M Y') : '-' }}</td>
                        <td>{{ $request->scheduled_time ?? '-' }}</td>
                        <td>
                            @switch($request->status)
                                @case('pending')
                                    <span class="badge bg-warning text-dark">Pending</span>
                                    @break
                                @case('confirmed')
                                    <span class="badge bg-info">Confirmed</span>
                                    @break
                                @case('in_progress')
                                    <span class="badge bg-primary">In Progress</span>
                                    @break
                                @case('completed')
                                    <span class="badge bg-success">Completed</span>
                                    @break
                                @case('cancelled')
                                    <span class="badge bg-danger">Cancelled</span>
                                    @break
                                @default
                                    <span class="badge bg-secondary">{{ ucfirst($request->status) }}</span>
                            @endswitch
                        </td>
                        <td>{{ $request->driver->name ?? 'Unassigned' }}</td>
                        <td class="text-center">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('pickup-requests.show', $request) }}"><i class="bi bi-eye me-2"></i>View</a></li>
                                    <li><a class="dropdown-item" href="{{ route('pickup-requests.edit', $request) }}"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                    @if($request->status === 'pending')
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="#" onclick="cancelRequest({{ $request->id }})"><i class="bi bi-x-circle me-2"></i>Cancel</a></li>
                                    @endif
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                            <p class="mt-2">No pickup requests found</p>
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

    function cancelRequest(id) {
        Swal.fire({
            title: 'Cancel Request?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `/admin/pickup-requests/${id}/cancel`;
            }
        });
    }
</script>
@endpush

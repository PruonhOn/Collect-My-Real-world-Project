@extends('layouts.admin')

@section('title', 'Activity Log')
@section('subtitle', 'System activity history')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Activity Log</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Activity Log</li>
            </ol>
        </nav>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-3">
                <label class="form-label">User</label>
                <select class="form-select" id="filterUser">
                    <option value="">All Users</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Module</label>
                <select class="form-select" id="filterModule">
                    <option value="">All Modules</option>
                    <option value="shipment">Shipment</option>
                    <option value="package">Package</option>
                    <option value="invoice">Invoice</option>
                    <option value="payment">Payment</option>
                    <option value="inventory">Inventory</option>
                    <option value="user">User</option>
                    <option value="settings">Settings</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Action</label>
                <select class="form-select" id="filterAction">
                    <option value="">All Actions</option>
                    <option value="create">Create</option>
                    <option value="update">Update</option>
                    <option value="delete">Delete</option>
                    <option value="login">Login</option>
                    <option value="logout">Logout</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Date Range</label>
                <input type="date" class="form-control" id="filterDate">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle" id="dataTable">
                <thead class="table-light">
                    <tr>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Module</th>
                        <th>Description</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td class="text-nowrap">{{ $log->created_at->format('d M Y, h:i:s A') }}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                    <i class="bi bi-person text-primary" style="font-size: 0.875rem;"></i>
                                </div>
                                {{ $log->user->name ?? 'System' }}
                            </div>
                        </td>
                        <td>
                            @switch($log->action)
                                @case('create')
                                    <span class="badge bg-success">Create</span>
                                    @break
                                @case('update')
                                    <span class="badge bg-info">Update</span>
                                    @break
                                @case('delete')
                                    <span class="badge bg-danger">Delete</span>
                                    @break
                                @case('login')
                                    <span class="badge bg-primary">Login</span>
                                    @break
                                @case('logout')
                                    <span class="badge bg-secondary">Logout</span>
                                    @break
                                @default
                                    <span class="badge bg-light text-dark">{{ ucfirst($log->action) }}</span>
                            @endswitch
                        </td>
                        <td><span class="badge bg-light text-dark">{{ ucfirst($log->module) }}</span></td>
                        <td>{{ $log->description }}</td>
                        <td><code class="bg-light px-2 py-1">{{ $log->ip_address ?? 'N/A' }}</code></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                            <p class="mt-2">No activity logs found</p>
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

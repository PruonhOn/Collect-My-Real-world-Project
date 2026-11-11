@extends('layouts.admin')

@section('title', 'Audit Logs')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Audit Logs</li>
@endsection

@section('page-title', 'Audit Logs')
@section('page-subtitle', 'Track all system activities and changes')

@section('page-actions')
    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.location.reload()">
        <i class="bi bi-arrow-clockwise me-1"></i> Refresh
    </button>
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <form method="GET" action="{{ route('audit-logs.index') }}" id="filterForm">
            <div class="row g-3 align-items-end">
                <div class="col-lg-2 col-md-4">
                    <label class="form-label small fw-semibold">From Date</label>
                    <input type="date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date', date('Y-m-01')) }}">
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label small fw-semibold">To Date</label>
                    <input type="date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date', date('Y-m-d')) }}">
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label small fw-semibold">Action</label>
                    <select name="action" class="form-select form-select-sm">
                        <option value="">All Actions</option>
                        <option value="created" @selected(request('action') === 'created')>Created</option>
                        <option value="updated" @selected(request('action') === 'updated')>Updated</option>
                        <option value="deleted" @selected(request('action') === 'deleted')>Deleted</option>
                        <option value="viewed" @selected(request('action') === 'viewed')>Viewed</option>
                        <option value="exported" @selected(request('action') === 'exported')>Exported</option>
                        <option value="imported" @selected(request('action') === 'imported')>Imported</option>
                        <option value="login" @selected(request('action') === 'login')>Login</option>
                        <option value="logout" @selected(request('action') === 'logout')>Logout</option>
                        <option value="restored" @selected(request('action') === 'restored')>Restored</option>
                        <option value="other" @selected(request('action') === 'other')>Other</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label small fw-semibold">Module</label>
                    <select name="module" class="form-select form-select-sm">
                        <option value="">All Modules</option>
                        <option value="shipment" @selected(request('module') === 'shipment')>Shipment</option>
                        <option value="delivery" @selected(request('module') === 'delivery')>Delivery</option>
                        <option value="customer" @selected(request('module') === 'customer')>Customer</option>
                        <option value="invoice" @selected(request('module') === 'invoice')>Invoice</option>
                        <option value="payment" @selected(request('module') === 'payment')>Payment</option>
                        <option value="transaction" @selected(request('module') === 'transaction')>Transaction</option>
                        <option value="user" @selected(request('module') === 'user')>User</option>
                        <option value="role" @selected(request('module') === 'role')>Role</option>
                        <option value="vehicle" @selected(request('module') === 'vehicle')>Vehicle</option>
                        <option value="driver" @selected(request('module') === 'driver')>Driver</option>
                        <option value="warehouse" @selected(request('module') === 'warehouse')>Warehouse</option>
                        <option value="inventory" @selected(request('module') === 'inventory')>Inventory</option>
                        <option value="settings" @selected(request('module') === 'settings')>Settings</option>
                        <option value="auth" @selected(request('module') === 'auth')>Authentication</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label small fw-semibold">User</label>
                    <select name="user_id" class="form-select form-select-sm">
                        <option value="">All Users</option>
                        @isset($users)
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>{{ $user->name }}</option>
                            @endforeach
                        @endisset
                    </select>
                </div>
                <div class="col-lg-2 col-md-4">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-lg-4">
                    <label class="form-label small fw-semibold">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search descriptions, IP addresses..." value="{{ request('search') }}">
                </div>
                <div class="col-lg-2 d-flex align-items-end">
                    <a href="{{ route('audit-logs.index') }}" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="bi bi-x-circle me-1"></i> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0 fw-bold">
            <i class="bi bi-journal-text me-1"></i>
            Audit Log Entries
            @isset($auditLogs)
                <span class="badge bg-secondary ms-1">{{ $auditLogs->total() ?? count($auditLogs) }}</span>
            @endisset
        </h6>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="event.preventDefault(); document.getElementById('filterForm').action += '?export=pdf'; document.getElementById('filterForm').submit();">
                <i class="bi bi-filetype-pdf me-1"></i> Export PDF
            </button>
            <button type="button" class="btn btn-outline-success btn-sm" onclick="event.preventDefault(); document.getElementById('filterForm').action += '?export=excel'; document.getElementById('filterForm').submit();">
                <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
            </button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="auditLogsTable">
            <thead class="table-light">
                <tr>
                    <th class="small fw-semibold">Date / Time</th>
                    <th class="small fw-semibold">User</th>
                    <th class="small fw-semibold">Action</th>
                    <th class="small fw-semibold">Module</th>
                    <th class="small fw-semibold">Description</th>
                    <th class="small fw-semibold">IP Address</th>
                    <th class="small fw-semibold text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($auditLogs ?? [] as $log)
                    <tr>
                        <td class="small text-nowrap">
                            <span title="{{ $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : '' }}">
                                <i class="bi bi-clock me-1 text-muted"></i>
                                {{ $log->created_at ? $log->created_at->format('d/m/Y h:i A') : 'N/A' }}
                            </span>
                        </td>
                        <td class="small">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center text-primary fw-bold me-2" style="width: 28px; height: 28px; font-size: 11px;">
                                    {{ strtoupper(substr($log->user->name ?? $log->causer->name ?? 'S', 0, 1)) }}
                                </div>
                                <span>{{ $log->user->name ?? $log->causer->name ?? 'System' }}</span>
                            </div>
                            @if($log->user->email ?? $log->causer->email ?? false)
                                <small class="text-muted d-block">{{ $log->user->email ?? $log->causer->email ?? '' }}</small>
                            @endif
                        </td>
                        <td>
                            @php
                                $actionBadge = match($log->action ?? $log->event) {
                                    'created' => ['bg-success', 'bi-plus-circle'],
                                    'updated' => ['bg-primary', 'bi-pencil-square'],
                                    'deleted' => ['bg-danger', 'bi-trash'],
                                    'viewed' => ['bg-info', 'bi-eye'],
                                    'exported' => ['bg-secondary', 'bi-download'],
                                    'imported' => ['bg-warning text-dark', 'bi-upload'],
                                    'login' => ['bg-success', 'bi-box-arrow-in-right'],
                                    'logout' => ['bg-secondary', 'bi-box-arrow-right'],
                                    'restored' => ['bg-info', 'bi-arrow-counterclockwise'],
                                    default => ['bg-dark', 'bi-question-circle'],
                                };
                            @endphp
                            <span class="badge {{ $actionBadge[0] }} rounded-pill">
                                <i class="bi {{ $actionBadge[1] }} me-1"></i>
                                {{ ucfirst($log->action ?? $log->event ?? 'N/A') }}
                            </span>
                        </td>
                        <td class="small">
                            <span class="badge bg-light text-dark border">
                                <i class="bi bi-grid me-1"></i>
                                {{ ucfirst($log->module ?? $log->auditable_type ? class_basename($log->auditable_type) : 'N/A') }}
                            </span>
                        </td>
                        <td class="small text-muted" style="max-width: 250px;">
                            <span class="d-inline-block text-truncate" style="max-width: 240px;">
                                {{ $log->description ?? $log->event ?? 'N/A' }}
                            </span>
                        </td>
                        <td class="small text-muted font-monospace">{{ $log->ip_address ?? $log->ip ?? 'N/A' }}</td>
                        <td class="text-center">
                            <a href="{{ route('audit-logs.show', $log) }}" class="btn btn-sm btn-outline-primary" title="View Details">
                                <i class="bi bi-eye"></i>
                            </a>
                            @if($log->old_values || $log->new_values)
                                <button type="button" class="btn btn-sm btn-outline-info" title="View Changes" data-bs-toggle="modal" data-bs-target="#changesModal{{ $log->id }}">
                                    <i class="bi bi-arrow-left-right"></i>
                                </button>
                                @include('audit-logs._changes_modal', ['log' => $log])
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-journal-text fs-2 d-block mb-2"></i>
                            <p class="mb-0">No audit log entries found for the selected criteria.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @isset($auditLogs)
        @if(method_exists($auditLogs, 'links'))
            <div class="card-footer bg-white">
                {{ $auditLogs->withQueryString()->links() }}
            </div>
        @endif
    @endisset
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const filterForm = document.getElementById('filterForm');
    if (filterForm) {
        filterForm.addEventListener('submit', function (e) {
            const exportVal = new URLSearchParams(window.location.search).get('export');
            if (exportVal) {
                e.preventDefault();
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'export';
                input.value = exportVal;
                this.appendChild(input);
                this.submit();
            }
        });
    }
});
</script>
@endpush

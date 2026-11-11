@extends('layouts.admin')

@section('title', 'Audit Log Details')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('audit-logs.index') }}">Audit Logs</a></li>
    <li class="breadcrumb-item active" aria-current="page">Log Details</li>
@endsection

@section('page-title', 'Audit Log Details')
@section('page-subtitle', 'Detailed view of system activity entry')

@section('page-actions')
    <a href="{{ route('audit-logs.index', request()->query()) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Logs
    </a>
@endsection

@section('content')
<div class="row g-4">
    {{-- Main Details --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold">
                    <i class="bi bi-info-circle me-1"></i>
                    Log Entry Details
                </h6>
            </div>
            <div class="card-body p-4">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <small class="text-muted text-uppercase fw-semibold d-block mb-1">Log ID</small>
                            <span class="fw-bold">#{{ $auditLog->id }}</span>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted text-uppercase fw-semibold d-block mb-1">Date & Time</small>
                            <span class="fw-bold">
                                <i class="bi bi-calendar3 me-1"></i>
                                {{ $auditLog->created_at ? $auditLog->created_at->format('F d, Y') : 'N/A' }}
                            </span>
                            <br>
                            <span class="text-muted small">
                                <i class="bi bi-clock me-1"></i>
                                {{ $auditLog->created_at ? $auditLog->created_at->format('h:i:s A') : '' }}
                            </span>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted text-uppercase fw-semibold d-block mb-1">Action</small>
                            @php
                                $actionBadge = match($auditLog->action ?? $auditLog->event) {
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
                            <span class="badge {{ $actionBadge[0] }} rounded-pill fs-6 px-3 py-2">
                                <i class="bi {{ $actionBadge[1] }} me-1"></i>
                                {{ ucfirst($auditLog->action ?? $auditLog->event ?? 'N/A') }}
                            </span>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted text-uppercase fw-semibold d-block mb-1">Module / Record Type</small>
                            <span class="badge bg-light text-dark border fs-6 px-3 py-2">
                                <i class="bi bi-grid me-1"></i>
                                {{ ucfirst($auditLog->module ?? ($auditLog->auditable_type ? class_basename($auditLog->auditable_type) : 'N/A')) }}
                            </span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <small class="text-muted text-uppercase fw-semibold d-block mb-1">Record ID</small>
                            <span class="fw-bold">{{ $auditLog->auditable_id ?? $auditLog->record_id ?? 'N/A' }}</span>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted text-uppercase fw-semibold d-block mb-1">User</small>
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white fw-bold me-2" style="width: 36px; height: 36px; font-size: 14px;">
                                    {{ strtoupper(substr($auditLog->user->name ?? $auditLog->causer->name ?? 'S', 0, 1)) }}
                                </div>
                                <div>
                                    <span class="fw-bold">{{ $auditLog->user->name ?? $auditLog->causer->name ?? 'System' }}</span>
                                    @if($auditLog->user->email ?? $auditLog->causer->email ?? false)
                                        <br>
                                        <small class="text-muted">{{ $auditLog->user->email ?? $auditLog->causer->email ?? '' }}</small>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted text-uppercase fw-semibold d-block mb-1">IP Address</small>
                            <span class="font-monospace bg-light px-2 py-1 rounded small">
                                <i class="bi bi-globe me-1"></i>
                                {{ $auditLog->ip_address ?? $auditLog->ip ?? 'N/A' }}
                            </span>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted text-uppercase fw-semibold d-block mb-1">User Agent</small>
                            <span class="small text-muted" style="word-break: break-all;">
                                {{ $auditLog->user_agent ?? $auditLog->useragent ?? 'N/A' }}
                            </span>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <div>
                    <small class="text-muted text-uppercase fw-semibold d-block mb-2">Description</small>
                    <p class="mb-0">{{ $auditLog->description ?? $auditLog->event ?? 'No description available.' }}</p>
                </div>

                @if($auditLog->url ?? $auditLog->request_url ?? false)
                    <div class="mt-3">
                        <small class="text-muted text-uppercase fw-semibold d-block mb-1">Request URL</small>
                        <code class="small">{{ $auditLog->url ?? $auditLog->request_url ?? '' }}</code>
                    </div>
                @endif

                @if($auditLog->method ?? $auditLog->request_method ?? false)
                    <div class="mt-2">
                        <small class="text-muted text-uppercase fw-semibold d-block mb-1">HTTP Method</small>
                        <span class="badge bg-dark">{{ $auditLog->method ?? $auditLog->request_method ?? '' }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="col-lg-4">
        {{-- Old Values --}}
        @if($auditLog->old_values)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-danger">
                        <i class="bi bi-file-diff me-1"></i>
                        Old Values
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <tbody>
                                @php $oldValues = is_array($auditLog->old_values) ? $auditLog->old_values : json_decode(json_encode($auditLog->old_values), true); @endphp
                                @forelse($oldValues as $field => $value)
                                    <tr>
                                        <td class="small fw-semibold text-muted text-nowrap px-3 py-2" style="width: 40%;">{{ ucfirst(str_replace('_', ' ', $field)) }}</td>
                                        <td class="small px-3 py-2">
                                            @if(is_null($value))
                                                <em class="text-muted">null</em>
                                            @elseif(is_bool($value))
                                                <span class="badge bg-{{ $value ? 'success' : 'secondary' }}">{{ $value ? 'true' : 'false' }}</span>
                                            @elseif($value instanceof \Carbon\Carbon || (is_string($value) && strtotime($value)))
                                                {{ $value instanceof \Carbon\Carbon ? $value->format('d/m/Y h:i A') : date('d/m/Y h:i A', strtotime($value)) }}
                                            @elseif(is_array($value) || is_object($value))
                                                <pre class="mb-0 small" style="max-height: 80px; overflow: auto;">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                                            @else
                                                {{ $value }}
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center text-muted small py-3">No old values recorded</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        {{-- New Values --}}
        @if($auditLog->new_values)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-success">
                        <i class="bi bi-file-diff me-1"></i>
                        New Values
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <tbody>
                                @php $newValues = is_array($auditLog->new_values) ? $auditLog->new_values : json_decode(json_encode($auditLog->new_values), true); @endphp
                                @forelse($newValues as $field => $value)
                                    <tr>
                                        <td class="small fw-semibold text-muted text-nowrap px-3 py-2" style="width: 40%;">{{ ucfirst(str_replace('_', ' ', $field)) }}</td>
                                        <td class="small px-3 py-2">
                                            @if(is_null($value))
                                                <em class="text-muted">null</em>
                                            @elseif(is_bool($value))
                                                <span class="badge bg-{{ $value ? 'success' : 'secondary' }}">{{ $value ? 'true' : 'false' }}</span>
                                            @elseif($value instanceof \Carbon\Carbon || (is_string($value) && strtotime($value)))
                                                {{ $value instanceof \Carbon\Carbon ? $value->format('d/m/Y h:i A') : date('d/m/Y h:i A', strtotime($value)) }}
                                            @elseif(is_array($value) || is_object($value))
                                                <pre class="mb-0 small" style="max-height: 80px; overflow: auto;">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                                            @else
                                                {{ $value }}
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center text-muted small py-3">No new values recorded</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        {{-- Changes Comparison --}}
        @if($auditLog->old_values && $auditLog->new_values)
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-primary">
                        <i class="bi bi-arrow-left-right me-1"></i>
                        Changes Comparison
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="small fw-semibold px-3 py-2">Field</th>
                                    <th class="small fw-semibold px-3 py-2 text-danger">Old</th>
                                    <th class="small fw-semibold px-3 py-2 text-success">New</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $oldVals = is_array($auditLog->old_values) ? $auditLog->old_values : json_decode(json_encode($auditLog->old_values), true);
                                    $newVals = is_array($auditLog->new_values) ? $auditLog->new_values : json_decode(json_encode($auditLog->new_values), true);
                                    $allFields = array_unique(array_merge(array_keys($oldVals ?? []), array_keys($newVals ?? [])));
                                @endphp
                                @forelse($allFields as $field)
                                    @php
                                        $oldVal = $oldVals[$field] ?? null;
                                        $newVal = $newVals[$field] ?? null;
                                        $hasChanged = json_encode($oldVal) !== json_encode($newVal);
                                    @endphp
                                    <tr class="{{ $hasChanged ? 'table-warning' : '' }}">
                                        <td class="small fw-semibold text-muted px-3 py-2">{{ ucfirst(str_replace('_', ' ', $field)) }}</td>
                                        <td class="small px-3 py-2 text-danger">
                                            @if(is_null($oldVal))
                                                <em class="text-muted">null</em>
                                            @elseif(is_bool($oldVal))
                                                {{ $oldVal ? 'true' : 'false' }}
                                            @elseif(is_array($oldVal) || is_object($oldVal))
                                                <pre class="mb-0 small" style="max-height: 60px; overflow: auto;">{{ json_encode($oldVal, JSON_PRETTY_PRINT) }}</pre>
                                            @else
                                                {{ $oldVal }}
                                            @endif
                                        </td>
                                        <td class="small px-3 py-2 text-success">
                                            @if(is_null($newVal))
                                                <em class="text-muted">null</em>
                                            @elseif(is_bool($newVal))
                                                {{ $newVal ? 'true' : 'false' }}
                                            @elseif(is_array($newVal) || is_object($newVal))
                                                <pre class="mb-0 small" style="max-height: 60px; overflow: auto;">{{ json_encode($newVal, JSON_PRETTY_PRINT) }}</pre>
                                            @else
                                                {{ $newVal }}
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted small py-3">No field-level comparison available</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

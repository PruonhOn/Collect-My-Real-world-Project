<div class="modal fade" id="changesModal{{ $log->id }}" tabindex="-1" aria-labelledby="changesModalLabel{{ $log->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="changesModalLabel{{ $log->id }}">
                    <i class="bi bi-arrow-left-right me-1"></i>
                    Changes Comparison
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @php
                    $oldValues = is_array($log->old_values) ? $log->old_values : json_decode(json_encode($log->old_values), true);
                    $newValues = is_array($log->new_values) ? $log->new_values : json_decode(json_encode($log->new_values), true);
                    $allFields = array_unique(array_merge(array_keys($oldValues ?? []), array_keys($newValues ?? [])));
                @endphp

                <div class="mb-3">
                    <div class="row g-2">
                        <div class="col-6">
                            <small class="text-muted text-uppercase fw-semibold">Log ID</small>
                            <div class="fw-bold">#{{ $log->id }}</div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted text-uppercase fw-semibold">Module</small>
                            <div class="fw-bold">{{ ucfirst($log->module ?? ($log->auditable_type ? class_basename($log->auditable_type) : 'N/A')) }}</div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted text-uppercase fw-semibold">Action</small>
                            <div>{{ ucfirst($log->action ?? $log->event ?? 'N/A') }}</div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted text-uppercase fw-semibold">User</small>
                            <div>{{ $log->user->name ?? $log->causer->name ?? 'System' }}</div>
                        </div>
                    </div>
                </div>

                @if(count($allFields) > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="small fw-semibold" style="width: 25%;">Field</th>
                                    <th class="small fw-semibold text-danger" style="width: 37.5%;">Old Value</th>
                                    <th class="small fw-semibold text-success" style="width: 37.5%;">New Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($allFields as $field)
                                    @php
                                        $oldVal = $oldValues[$field] ?? null;
                                        $newVal = $newValues[$field] ?? null;
                                        $hasChanged = json_encode($oldVal) !== json_encode($newVal);
                                        $displayVal = function($val) {
                                            if (is_null($val)) return '<em class="text-muted">null</em>';
                                            if (is_bool($val)) return $val ? 'true' : 'false';
                                            if (is_array($val) || is_object($val)) return '<pre class="mb-0 small" style="max-height: 100px; overflow: auto;">' . e(json_encode($val, JSON_PRETTY_PRINT)) . '</pre>';
                                            if ($val instanceof \Carbon\Carbon || (is_string($val) && strtotime($val))) {
                                                try { return e((new \Carbon\Carbon($val))->format('d/m/Y h:i A')); } catch (\Exception $e) { return e($val); }
                                            }
                                            return e((string) $val);
                                        };
                                    @endphp
                                    <tr class="{{ $hasChanged ? 'table-warning' : '' }}">
                                        <td class="small fw-semibold text-muted">{{ ucfirst(str_replace('_', ' ', $field)) }}</td>
                                        <td class="small text-danger">{!! $displayVal($oldVal) !!}</td>
                                        <td class="small text-success">{!! $displayVal($newVal) !!}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-file-diff fs-2 d-block mb-2"></i>
                        <p class="mb-0">No field-level changes to display.</p>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

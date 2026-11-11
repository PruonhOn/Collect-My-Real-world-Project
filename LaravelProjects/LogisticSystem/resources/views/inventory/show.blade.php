@extends('layouts.admin')

@section('title', $item->name . ' - Inventory Details - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}">Inventory</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $item->sku }}</li>
@endsection

@section('page-title', $item->name)
@section('page-subtitle', 'SKU: ' . $item->sku)

@section('page-actions')
    <div class="btn-group">
        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#adjustStockModal">
            <i class="bi bi-arrow-up-down"></i> Adjust Stock
        </button>
        <a href="{{ route('inventory.edit', $item) }}" class="btn btn-primary">
            <i class="bi bi-pencil"></i> Edit
        </a>
    </div>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Item Information</h5>
                    @if($item->quantity <= 0)
                        <span class="badge bg-danger fs-6">Out of Stock</span>
                    @elseif($item->quantity <= $item->min_quantity)
                        <span class="badge bg-warning text-dark fs-6">Low Stock</span>
                    @else
                        <span class="badge bg-success fs-6">In Stock</span>
                    @endif
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">SKU</small>
                            <span class="badge bg-secondary fs-6">{{ $item->sku }}</span>
                        </div>
                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Category</small>
                            <strong>{{ $item->category ?? '—' }}</strong>
                        </div>
                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Unit</small>
                            <strong>{{ $item->unit ?? '—' }}</strong>
                        </div>
                        <div class="col-md-3 mb-3">
                            <small class="text-muted d-block">Current Quantity</small>
                            <h3 class="mb-0 fw-bold {{ $item->quantity <= $item->min_quantity ? 'text-danger' : 'text-success' }}">
                                {{ number_format($item->quantity) }}
                            </h3>
                        </div>
                        <div class="col-md-3 mb-3">
                            <small class="text-muted d-block">Min Quantity</small>
                            <strong>{{ number_format($item->min_quantity) }}</strong>
                        </div>
                        <div class="col-md-3 mb-3">
                            <small class="text-muted d-block">Max Quantity</small>
                            <strong>{{ number_format($item->max_quantity) ?? '—' }}</strong>
                        </div>
                        <div class="col-md-3 mb-3">
                            <small class="text-muted d-block">Unit Price</small>
                            <strong>${{ number_format($item->unit_price, 2) }}</strong>
                        </div>
                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Weight per Unit</small>
                            <strong>{{ $item->weight_per_unit ? number_format($item->weight_per_unit, 2) . ' kg' : '—' }}</strong>
                        </div>
                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Volume per Unit</small>
                            <strong>{{ $item->volume_per_unit ? number_format($item->volume_per_unit, 3) . ' m³' : '—' }}</strong>
                        </div>
                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Total Value</small>
                            <strong>${{ number_format($item->quantity * $item->unit_price, 2) }}</strong>
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Warehouse</small>
                            <strong>{{ $item->warehouse?->name ?? '—' }}</strong>
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Location</small>
                            <strong>{{ $item->location?->code ?? $item->location?->name ?? '—' }}</strong>
                        </div>
                        @if($item->description)
                            <div class="col-12 mb-3">
                                <small class="text-muted d-block">Description</small>
                                <p class="mb-0">{{ $item->description }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Stock Movement History</h5>
                </div>
                <div class="card-body p-0">
                    @if($item->stockMovements && $item->stockMovements->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm table-striped mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th class="text-center">Qty Change</th>
                                        <th class="text-center">Balance</th>
                                        <th>Reference</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($item->stockMovements->sortByDesc('created_at') as $movement)
                                        <tr>
                                            <td>{{ $movement->created_at->format('d M Y H:i') }}</td>
                                            <td>
                                                @if($movement->type == 'in')
                                                    <span class="badge bg-success">In</span>
                                                @elseif($movement->type == 'out')
                                                    <span class="badge bg-danger">Out</span>
                                                @else
                                                    <span class="badge bg-warning text-dark">Adjustment</span>
                                                @endif
                                            </td>
                                            <td class="text-center fw-medium {{ $movement->quantity_change > 0 ? 'text-success' : 'text-danger' }}">
                                                {{ $movement->quantity_change > 0 ? '+' : '' }}{{ number_format($movement->quantity_change) }}
                                            </td>
                                            <td class="text-center">{{ number_format($movement->balance_after) }}</td>
                                            <td><small>{{ $movement->reference ?? '—' }}</small></td>
                                            <td><small class="text-muted">{{ Str::limit($movement->notes, 30) ?? '—' }}</small></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-arrow-left-right fs-2 d-block mb-2"></i>
                            <p class="mb-0">No stock movements recorded</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Stock Summary</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-muted">Stock Level</small>
                            <small>{{ number_format($item->quantity) }} / {{ number_format($item->max_quantity) ?: '∞' }}</small>
                        </div>
                        @if($item->max_quantity > 0)
                            <div class="progress" style="height: 8px;">
                                @php $pct = min(100, ($item->quantity / $item->max_quantity) * 100); @endphp
                                <div class="progress-bar {{ $pct <= 25 ? 'bg-danger' : ($pct <= 50 ? 'bg-warning' : 'bg-success') }}"
                                     role="progressbar" style="width: {{ $pct }}%"></div>
                            </div>
                        @endif
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Current Qty:</span>
                        <strong>{{ number_format($item->quantity) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Min Qty:</span>
                        <strong>{{ number_format($item->min_quantity) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Max Qty:</span>
                        <strong>{{ number_format($item->max_quantity) ?: '∞' }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total Value:</span>
                        <strong>${{ number_format($item->quantity * $item->unit_price, 2) }}</strong>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Timeline</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item">
                            <small class="text-muted">Created</small>
                            <strong class="d-block">{{ $item->created_at->format('d M Y H:i') }}</strong>
                        </div>
                        <div class="list-group-item">
                            <small class="text-muted">Last Updated</small>
                            <strong class="d-block">{{ $item->updated_at->format('d M Y H:i') }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="adjustStockModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('inventory.adjust-stock', $item) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header">
                        <h5 class="modal-title">Adjust Stock: {{ $item->name }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Current Quantity</label>
                            <input type="text" class="form-control" value="{{ number_format($item->quantity) }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="adjustment_type" class="form-label">Adjustment Type <span class="text-danger">*</span></label>
                            <select id="adjustment_type" name="adjustment_type" class="form-select" required>
                                <option value="in">Stock In (Add)</option>
                                <option value="out">Stock Out (Remove)</option>
                                <option value="set">Set to Exact Quantity</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                            <input type="number" id="quantity" name="quantity" class="form-control" min="0" step="1" required>
                        </div>
                        <div class="mb-3">
                            <label for="reference" class="form-label">Reference</label>
                            <input type="text" id="reference" name="reference" class="form-control" placeholder="e.g. PO-001, Manual count">
                        </div>
                        <div class="mb-3">
                            <label for="movement_notes" class="form-label">Notes</label>
                            <textarea id="movement_notes" name="notes" class="form-control" rows="2" placeholder="Reason for adjustment..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-check-lg"></i> Confirm Adjustment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

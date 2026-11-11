@extends('layouts.app')

@section('title', 'Inventory')
@section('heading', 'Inventory')
@section('subheading', 'Track stock levels, receive stock, and audit every movement')

@section('content')
    <div class="card mb-3">
        <div class="card-header bg-transparent d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <div>
                <h6 class="mb-0 fw-semibold"><i class="bi bi-boxes me-2"></i>Current Stock Levels</h6>
            </div>
            <div class="d-flex gap-2">
                <form class="d-flex gap-2" method="GET" action="{{ route('inventory.index') }}">
                    <input type="text" name="stock_q" value="{{ request('stock_q') }}" class="form-control form-control-sm" placeholder="Search stock...">
                    <button class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button>
                </form>
                <button class="btn btn-sm btn-success rounded-pill" data-bs-toggle="modal" data-bs-target="#stockInModal"><i class="bi bi-box-arrow-in-down me-1"></i>Receive Stock</button>
                <button class="btn btn-sm btn-outline-warning rounded-pill" data-bs-toggle="modal" data-bs-target="#adjustModal"><i class="bi bi-sliders me-1"></i>Adjust / Damage</button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Product</th>
                        <th>Variant</th>
                        <th>Variant SKU</th>
                        <th class="text-end">In Stock</th>
                        <th class="text-end">Price</th>
                        <th>Level</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($stock as $variant)
                        <tr>
                            <td><strong>{{ $variant->product->name }}</strong></td>
                            <td>{{ $variant->label ?: 'Default' }}</td>
                            <td class="small text-muted">{{ $variant->sku_variant }}</td>
                            <td class="text-end fw-semibold">{{ $variant->stock_quantity }}</td>
                            <td class="text-end">{{ money($variant->price) }}</td>
                            <td>
                                @if ($variant->stock_quantity <= 0)
                                    <span class="badge bg-danger-subtle text-danger">Out of stock</span>
                                @elseif ($variant->stock_quantity <= 5)
                                    <span class="badge bg-warning-subtle text-warning">Low stock</span>
                                @else
                                    <span class="badge bg-success-subtle text-success">Healthy</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state"><i class="bi bi-boxes"></i><p>No stock found.</p></div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-transparent">{{ $stock->appends(request()->query())->links('pagination::bootstrap-5') }}</div>
    </div>

    <div class="card">
        <div class="card-header bg-transparent d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <div>
                <h6 class="mb-0 fw-semibold"><i class="bi bi-clock-history me-2"></i>Stock Movement Log</h6>
            </div>
            <form class="d-flex flex-wrap gap-2" method="GET" action="{{ route('inventory.index') }}">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search product...">
                <select name="type" class="form-select form-select-sm">
                    <option value="">All types</option>
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}" @selected(request('type') == $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
                <button class="btn btn-sm btn-primary"><i class="bi bi-funnel"></i></button>
                @if (request()->hasAny(['q', 'type', 'date_from', 'date_to']))
                    <a href="{{ route('inventory.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                @endif
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Product / Variant</th>
                        <th>Type</th>
                        <th>Direction</th>
                        <th class="text-end">Qty</th>
                        <th class="text-center">Stock After</th>
                        <th>By</th>
                        <th>Reason</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        <tr>
                            <td class="small">{{ $transaction->created_at->format('d M Y H:i') }}</td>
                            <td>
                                <strong>{{ $transaction->variant?->product?->name ?? '—' }}</strong>
                                <div class="small text-muted">{{ $transaction->variant?->label }}</div>
                            </td>
                            <td>
                                @if ($transaction->type->direction() === 'in')
                                    <span class="badge bg-success-subtle text-success">{{ $transaction->type->label() }}</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger">{{ $transaction->type->label() }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $transaction->direction === 'in' ? 'text-bg-success' : 'text-bg-danger' }}">
                                    {{ strtoupper($transaction->direction) }}
                                </span>
                            </td>
                            <td class="text-end fw-semibold">{{ $transaction->quantity }}</td>
                            <td class="text-center">{{ $transaction->stock_before }} → <strong>{{ $transaction->stock_after }}</strong></td>
                            <td class="small">{{ $transaction->user?->name ?? 'System' }}</td>
                            <td class="small text-muted">{{ $transaction->reason ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="empty-state"><i class="bi bi-clock-history"></i><p>No stock movements recorded.</p></div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-transparent">{{ $transactions->appends(request()->query())->links('pagination::bootstrap-5') }}</div>
    </div>
@endsection

@section('modals')
    <div class="modal fade" id="stockInModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('inventory.stock-in') }}" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-box-arrow-in-down me-2 text-success"></i>Receive Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Variant <span class="text-danger">*</span></label>
                        <select name="variant_id" class="form-select" required>
                            <option value="">Select product / variant...</option>
                            @foreach ($variants as $variant)
                                <option value="{{ $variant->id }}">{{ $variant->product->name }} — {{ $variant->label ?: 'Default' }} ({{ $variant->stock_quantity }} in stock)</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantity <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" min="1" class="form-control" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" rows="2" class="form-control" placeholder="e.g. New batch from supplier"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success rounded-pill"><i class="bi bi-check-lg me-1"></i>Add to Stock</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="adjustModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('inventory.adjust') }}" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-sliders me-2 text-warning"></i>Adjust Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Variant <span class="text-danger">*</span></label>
                        <select name="variant_id" class="form-select" required>
                            <option value="">Select product / variant...</option>
                            @foreach ($variants as $variant)
                                <option value="{{ $variant->id }}">{{ $variant->product->name }} — {{ $variant->label ?: 'Default' }} ({{ $variant->stock_quantity }} in stock)</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label">Type <span class="text-danger">*</span></label>
                            <select name="type" class="form-select" id="adjustType" required>
                                <option value="adjustment">Adjustment (count correction)</option>
                                <option value="damage">Damage / Loss</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" id="adjustQty" class="form-control" placeholder="e.g. +5 or -3" required>
                            <div class="form-text">Adjustment: use +/− to correct stock. Damage: enter a positive number.</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason <span class="text-danger">*</span></label>
                        <input type="text" name="reason" class="form-control" placeholder="e.g. Stock count correction" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" rows="2" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning rounded-pill"><i class="bi bi-check-lg me-1"></i>Apply Adjustment</button>
                </div>
            </form>
        </div>
    </div>
@endsection

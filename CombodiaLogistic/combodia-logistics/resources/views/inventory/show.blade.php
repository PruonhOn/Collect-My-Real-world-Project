@extends('layouts.admin')

@section('title', 'Inventory Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Inventory Details</h4>
    <div>
        <a href="{{ route('inventories.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <a href="{{ route('inventories.edit', $inventory) }}" class="btn btn-primary btn-sm">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
    </div>
</div>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('inventories.index') }}">Inventories</a></li>
        <li class="breadcrumb-item active">{{ $inventory->SKU }}</li>
    </ol>
</nav>

<div class="row">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0">Inventory Information</h6>
            </div>
            <div class="card-body">
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Product Name</label>
                    <p class="fw-medium mb-0">{{ $inventory->product_name }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">SKU</label>
                    <p class="fw-medium mb-0">{{ $inventory->SKU }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Warehouse</label>
                    <p class="fw-medium mb-0">{{ $inventory->warehouse->name ?? '-' }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Shelf</label>
                    <p class="fw-medium mb-0">{{ $inventory->shelf->name ?? '-' }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Quantity</label>
                    <p class="fw-medium mb-0">{{ $inventory->quantity }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Reorder Point</label>
                    <p class="fw-medium mb-0">{{ $inventory->reorder_point }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Unit Cost</label>
                    <p class="fw-medium mb-0">${{ number_format($inventory->unit_cost, 2) }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Total Value</label>
                    <p class="fw-medium mb-0">${{ number_format($inventory->total_value, 2) }}</p>
                </div>
                <div class="mb-0">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Stock Status</label>
                    <p class="fw-medium mb-0">
                        @if($inventory->quantity <= 0)
                            <span class="badge bg-danger">Out of Stock</span>
                        @elseif($inventory->quantity <= $inventory->reorder_point)
                            <span class="badge bg-warning text-dark">Low Stock</span>
                        @else
                            <span class="badge bg-success">In Stock</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Stock Movements</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Quantity</th>
                                <th>Reference</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($inventory->movements ?? [] as $movement)
                                <tr>
                                    <td>{{ $movement->created_at ? \Carbon\Carbon::parse($movement->created_at)->format('d M Y, h:i A') : '-' }}</td>
                                    <td>
                                        @if($movement->type === 'in')
                                            <span class="badge bg-success">In</span>
                                        @elseif($movement->type === 'out')
                                            <span class="badge bg-danger">Out</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst($movement->type) }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $movement->quantity }}</td>
                                    <td>{{ $movement->reference ?? '-' }}</td>
                                    <td>{{ $movement->notes ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No stock movements found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
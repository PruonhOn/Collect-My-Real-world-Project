@extends('layouts.app')

@section('title', 'Stock Movements')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0"><i class="bi bi-arrow-left-right me-2 text-primary"></i>All Stock Movements</h4>
            <p class="text-muted small mb-0">Complete history of stock changes</p>
        </div>
        <a href="{{ route('inventory.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Inventory
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Product</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>Before</th>
                            <th>After</th>
                            <th>Reference</th>
                            <th>Notes</th>
                            <th>By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movements as $m)
                        <tr>
                            <td>{{ $m->created_at ? $m->created_at->format('d M, Y H:i') : '-' }}</td>
                            <td>{{ $m->product?->name ?? 'Deleted' }} <code class="ms-1 small">{{ $m->product?->sku ?? '' }}</code></td>
                            <td><span class="badge bg-{{ $m->type === 'in' ? 'success' : ($m->type === 'out' ? 'danger' : 'info') }}">{{ strtoupper($m->type) }}</span></td>
                            <td>{{ $m->quantity }}</td>
                            <td>{{ $m->before_quantity ?? '-' }}</td>
                            <td>{{ $m->after_quantity ?? '-' }}</td>
                            <td>{{ $m->reference_type ? ucfirst($m->reference_type) . ' #' . $m->reference_id : '-' }}</td>
                            <td>{{ $m->notes ?? '-' }}</td>
                            <td>{{ $m->creator?->name ?? 'System' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No movements recorded.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-top">
            <div class="d-flex justify-content-center">
                {{ $movements->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

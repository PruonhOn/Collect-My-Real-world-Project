@extends('layouts.admin')

@section('title', 'Stock Movements')
@section('subtitle', 'Stock movement history')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Stock Movements</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Stock Movements</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('stock-movements.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> New Movement
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="dataTable">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Reference</th>
                        <th>Product</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Unit Cost</th>
                        <th>Total</th>
                        <th>Warehouse</th>
                        <th>Moved By</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $movement)
                    <tr>
                        <td>{{ $movement->created_at->format('d M Y, h:i A') }}</td>
                        <td><code class="bg-light px-2 py-1">{{ $movement->reference_number ?? '-' }}</code></td>
                        <td>{{ $movement->inventory->product_name ?? 'N/A' }}</td>
                        <td>
                            @switch($movement->type)
                                @case('in')
                                    <span class="badge bg-success">In</span>
                                    @break
                                @case('out')
                                    <span class="badge bg-danger">Out</span>
                                    @break
                                @case('transfer')
                                    <span class="badge bg-info">Transfer</span>
                                    @break
                                @case('adjustment')
                                    <span class="badge bg-warning text-dark">Adjustment</span>
                                    @break
                                @default
                                    <span class="badge bg-secondary">{{ ucfirst($movement->type) }}</span>
                            @endswitch
                        </td>
                        <td>{{ $movement->quantity }}</td>
                        <td>${{ number_format($movement->unit_cost ?? 0, 2) }}</td>
                        <td>${{ number_format(($movement->unit_cost ?? 0) * $movement->quantity, 2) }}</td>
                        <td>{{ $movement->warehouse->name ?? 'N/A' }}</td>
                        <td>{{ $movement->user->name ?? 'N/A' }}</td>
                        <td>{{ Str::limit($movement->notes ?? '-', 30) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                            <p class="mt-2">No stock movements found</p>
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

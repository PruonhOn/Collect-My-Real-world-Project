@extends('layouts.admin')

@section('title', 'Stock Movements - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}">Inventory</a></li>
    <li class="breadcrumb-item active" aria-current="page">Stock Movements</li>
@endsection

@section('page-title', 'Stock Movements')
@section('page-subtitle', 'Track inventory stock changes')

@section('content')
    <div class="table-container">
        <table class="table table-hover datatable" id="movementsTable">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Product</th>
                    <th>Type</th>
                    <th>Quantity</th>
                    <th>Reference</th>
                    <th>User</th>
                </tr>
            </thead>
            <tbody>
                @forelse($inventoryItem->stockMovements ?? [] as $movement)
                    <tr>
                        <td>{{ $movement->created_at?->format('d M Y H:i') ?? '—' }}</td>
                        <td>{{ $inventoryItem->product_name ?? $inventoryItem->sku ?? '—' }}</td>
                        <td>
                            <span class="badge bg-{{ $movement->type == 'in' ? 'success' : 'danger' }}">
                                {{ ucfirst($movement->type ?? '') }}
                            </span>
                        </td>
                        <td class="fw-medium">{{ $movement->quantity > 0 ? '+' : '' }}{{ number_format($movement->quantity ?? 0) }}</td>
                        <td>{{ $movement->reference ?? '—' }}</td>
                        <td>{{ $movement->user?->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="bi bi-arrow-left-right fs-2 d-block mb-2"></i>
                            <p class="mb-0">No stock movements recorded</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

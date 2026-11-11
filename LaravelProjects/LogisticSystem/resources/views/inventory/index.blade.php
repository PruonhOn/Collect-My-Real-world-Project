@extends('layouts.admin')

@section('title', 'Inventory - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('warehouses.index') }}">Warehouses</a></li>
    <li class="breadcrumb-item active" aria-current="page">Inventory</li>
@endsection

@section('page-title', 'Inventory')
@section('page-subtitle', 'Manage warehouse stock')

@section('page-actions')
    <a href="{{ route('inventory.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> New Item
    </a>
@endsection

@section('content')
    <div class="table-container">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="inventoryTable">
                <thead class="table-light">
                    <tr>
                        <th>SKU</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Warehouse</th>
                        <th>Location</th>
                        <th>Qty</th>
                        <th>Min Qty</th>
                        <th>Unit</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($inventoryItems as $item)
                        <tr>
                            <td><span class="badge bg-secondary">{{ $item->sku }}</span></td>
                            <td>
                                <a href="{{ route('inventory.show', $item) }}" class="text-decoration-none fw-medium">{{ $item->name }}</a>
                            </td>
                            <td>{{ $item->category ?? '—' }}</td>
                            <td>
                                @if($item->warehouse)
                                    <small>{{ $item->warehouse->name }}</small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($item->location)
                                    <small>{{ $item->location->code ?? $item->location->name }}</small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="fw-medium">{{ number_format($item->quantity) }}</td>
                            <td>{{ number_format($item->min_quantity) }}</td>
                            <td>{{ $item->unit ?? '—' }}</td>
                            <td>
                                @if($item->quantity <= 0)
                                    <span class="badge bg-danger">Out of Stock</span>
                                @elseif($item->quantity <= $item->min_quantity)
                                    <span class="badge bg-warning text-dark">Low Stock</span>
                                @else
                                    <span class="badge bg-success">In Stock</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('inventory.show', $item) }}" class="btn btn-outline-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('inventory.edit', $item) }}" class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger" title="Delete"
                                            onclick="confirmDelete({{ $item->id }})">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                                <form id="delete-form-{{ $item->id }}" action="{{ route('inventory.destroy', $item) }}" method="POST" class="d-none">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-box fs-1 d-block mb-2"></i>
                                <p class="mb-0">No inventory items found</p>
                                <a href="{{ route('inventory.create') }}" class="btn btn-sm btn-primary mt-2">Add Item</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($inventoryItems, 'links'))
            <div class="d-flex justify-content-end mt-2">
                {{ $inventoryItems->links() }}
            </div>
        @endif
    </div>
@endsection

@section('scripts')
    <script>
        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this inventory item?')) {
                document.getElementById('delete-form-' + id).submit();
            }
        }
    </script>
@endsection

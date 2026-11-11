@extends('layouts.app')

@section('title', 'Inventory')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="fas fa-warehouse me-2"></i>Inventory Management</h4>
    <a href="{{ route('products.index') }}" class="btn btn-primary">
        <i class="fas fa-box me-1"></i>Manage Products
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card bg-info bg-opacity-10 text-center">
            <small class="text-muted text-uppercase">Total Stock</small>
            <h3 class="fw-bold">{{ \App\Models\Product::sum('quantity') }}</h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card bg-success bg-opacity-10 text-center">
            <small class="text-muted text-uppercase">Total Products</small>
            <h3 class="fw-bold">{{ \App\Models\Product::count() }}</h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card bg-warning bg-opacity-10 text-center">
            <small class="text-muted text-uppercase">Categories</small>
            <h3 class="fw-bold">{{ \App\Models\Category::count() }}</h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card bg-danger bg-opacity-10 text-center">
            <small class="text-muted text-uppercase">Low Stock Items</small>
            <h3 class="fw-bold text-danger">{{ \App\Models\Product::where('quantity', '<', 10)->count() }}</h3>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="fas fa-exclamation-triangle text-danger me-2"></i>Low Stock Alert (Qty < 10)</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Barcode</th>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Current Stock</th>
                        <th>Selling Price</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                    <tr class="low-stock">
                        <td><span class="badge bg-secondary">{{ $product->barcode }}</span></td>
                        <td class="fw-bold">{{ $product->name }}</td>
                        <td>{{ $product->category?->name ?? 'N/A' }}</td>
                        <td>
                            <span class="badge bg-danger fs-6">{{ $product->quantity }}</span>
                        </td>
                        <td>${{ number_format($product->selling_price, 2) }}</td>
                        <td>
                            <a href="{{ route('products.edit', $product->id) }}" class="btn btn-sm btn-warning">
                                <i class="fas fa-plus me-1"></i>Restock
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="fas fa-check-circle fa-3x text-success mb-3 d-block"></i>
                            All products are well-stocked! No low stock alerts.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

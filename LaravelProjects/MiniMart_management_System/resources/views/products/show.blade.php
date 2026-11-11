@extends('layouts.app')

@section('title', $product->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="fas fa-box me-2"></i>Product Details</h4>
    <div>
        <a href="{{ route('products.edit', $product->id) }}" class="btn btn-warning">
            <i class="fas fa-edit me-1"></i>Edit
        </a>
        <a href="{{ route('products.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i>Back
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                @if($product->image)
                    <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="img-fluid rounded" style="max-height: 250px;">
                @else
                    <div style="height: 200px;" class="d-flex align-items-center justify-content-center bg-light rounded">
                        <i class="fas fa-box fa-5x text-muted"></i>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <h4 class="fw-bold mb-3">{{ $product->name }}</h4>
                <div class="row g-3">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td class="text-muted">Product ID</td>
                                <td class="fw-bold">#{{ $product->id }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Barcode</td>
                                <td>
                                    <span class="badge bg-secondary fs-6 p-2">{{ $product->barcode }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Category</td>
                                <td class="fw-bold">{{ $product->category?->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Stock Status</td>
                                <td>
                                    @if($product->quantity < 10)
                                        <span class="badge bg-danger">Low Stock ({{ $product->quantity }})</span>
                                    @else
                                        <span class="badge bg-success">{{ $product->quantity }} in stock</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td class="text-muted">Cost Price</td>
                                <td class="fw-bold">${{ number_format($product->cost_price, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Selling Price</td>
                                <td class="fw-bold text-success">${{ number_format($product->selling_price, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Profit Margin</td>
                                <td class="fw-bold text-primary">
                                    ${{ number_format($product->selling_price - $product->cost_price, 2) }}
                                    ({{ $product->cost_price > 0 ? round((($product->selling_price - $product->cost_price) / $product->cost_price) * 100, 1) : 0 }}%)
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Created</td>
                                <td>{{ $product->created_at->format('M d, Y h:i A') }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-12">
                        <label class="text-muted">Description</label>
                        <p>{{ $product->description ?? 'No description provided.' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

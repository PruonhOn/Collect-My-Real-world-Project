@extends('layouts.app')

@section('title', 'Products')
@section('heading', 'Products')
@section('subheading', 'Manage your catalogue with size and color variants')

@section('content')
    <div class="card">
        <div class="card-header bg-transparent">
            <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
                <form class="d-flex flex-wrap gap-2 flex-grow-1" method="GET" action="{{ route('products.index') }}">
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" style="max-width:260px" placeholder="Search name, SKU, brand...">
                    <select name="category" class="form-select form-select-sm" style="max-width:220px">
                        <option value="">All categories</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->full_name }}</option>
                        @endforeach
                    </select>
                    <select name="stock" class="form-select form-select-sm" style="max-width:180px">
                        <option value="">All stock</option>
                        <option value="in" @selected(request('stock') == 'in')>In stock</option>
                        <option value="low" @selected(request('stock') == 'low')>Low stock (≤5)</option>
                        <option value="out" @selected(request('stock') == 'out')>Out of stock</option>
                    </select>
                    <button class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
                    @if (request()->has('q') || request()->has('category') || request()->has('stock'))
                        <a href="{{ route('products.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                    @endif
                </form>
                <a href="{{ route('products.create') }}" class="btn btn-primary btn-sm rounded-pill">
                    <i class="bi bi-plus-lg me-1"></i> Add Product
                </a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Variants</th>
                        <th class="text-end">Base Price</th>
                        <th class="text-end">Total Stock</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if ($product->primaryImage())
                                        <img src="{{ $product->primaryImage()->url }}" class="table-thumb" alt="">
                                    @else
                                        <div class="table-thumb table-thumb-empty"><i class="bi bi-image"></i></div>
                                    @endif
                                    <div>
                                        <strong>{{ $product->name }}</strong>
                                        <div class="small text-muted">{{ $product->sku }}{{ $product->brand ? ' · '.$product->brand : '' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $product->category?->name }}</td>
                            <td><span class="badge text-bg-light border">{{ $product->variant_count }} variants</span></td>
                            <td class="text-end">{{ money($product->base_price) }}</td>
                            <td class="text-end">
                                @if ($product->total_stock == 0)
                                    <span class="badge bg-danger-subtle text-danger">0</span>
                                @elseif ($product->total_stock <= 5)
                                    <span class="badge bg-warning-subtle text-warning">{{ $product->total_stock }}</span>
                                @else
                                    <span class="badge bg-success-subtle text-success">{{ $product->total_stock }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($product->is_active)
                                    <span class="badge bg-success-subtle text-success">Active</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('products.show', $product) }}" class="btn btn-sm btn-soft" title="View"><i class="bi bi-eye"></i></a>
                                <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-soft" title="Edit"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('products.destroy', $product) }}" class="d-inline" onsubmit="return confirm('Delete product &quot;{{ $product->name }}&quot;? This cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-soft text-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="bi bi-box-seam"></i>
                                    <p>No products found.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-transparent">
            {{ $products->links() }}
        </div>
    </div>
@endsection

@extends('layouts.app')

@section('title', 'Products')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="fas fa-box me-2"></i>Products</h4>
    <a href="{{ route('products.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Add Product
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div class="row g-2 mb-3">
            <div class="col-md-4">
                <input type="text" class="form-control" id="searchInput" placeholder="Search by name, barcode or ID...">
            </div>
            <div class="col-md-3">
                <select class="form-select" id="categoryFilter">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover" id="productsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Barcode</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Cost Price</th>
                        <th>Selling Price</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                    <tr class="{{ $product->quantity < 10 ? 'low-stock' : '' }}">
                        <td>{{ $product->id }}</td>
                        <td>
                            <span class="badge bg-secondary">{{ $product->barcode }}</span>
                        </td>
                        <td>
                            @if($product->image)
                                <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" style="width: 40px; height: 40px; object-fit: cover; border-radius: 5px;">
                            @else
                                <div class="avatar bg-secondary text-white" style="width: 40px; height: 40px;">
                                    <i class="fas fa-box"></i>
                                </div>
                            @endif
                        </td>
                        <td>{{ $product->name }}</td>
                        <td>{{ $product->category?->name ?? 'N/A' }}</td>
                        <td>${{ number_format($product->cost_price, 2) }}</td>
                        <td>${{ number_format($product->selling_price, 2) }}</td>
                        <td>
                            <span class="badge {{ $product->quantity < 10 ? 'bg-danger' : 'bg-success' }}">
                                {{ $product->quantity }}
                            </span>
                            @if($product->quantity < 10)
                                <i class="fas fa-exclamation-triangle text-danger ms-1" title="Low Stock"></i>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('products.show', $product->id) }}" class="btn btn-sm btn-info" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('products.edit', $product->id) }}" class="btn btn-sm btn-warning" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form id="delete-form-{{ $product->id }}" action="{{ route('products.destroy', $product->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-sm btn-danger" title="Delete"
                                    onclick="confirmDelete('delete-form-{{ $product->id }}', '{{ $product->name }}')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="fas fa-box-open fa-3x mb-3 d-block"></i>
                            No products found. <a href="{{ route('products.create') }}">Add your first product</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-end">
            {{ $products->links() }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $('#searchInput').on('keyup', function() {
        const val = $(this).val().toLowerCase();
        $('#productsTable tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(val) > -1);
        });
    });

    $('#categoryFilter').on('change', function() {
        const val = $(this).val();
        if (val) {
            window.location.href = '{{ route("products.index") }}?category_id=' + val;
        } else {
            window.location.href = '{{ route("products.index") }}';
        }
    });
</script>
@endpush

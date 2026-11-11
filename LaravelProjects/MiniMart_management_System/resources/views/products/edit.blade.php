@extends('layouts.app')

@section('title', 'Edit Product')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="fas fa-edit me-2"></i>Edit Product</h4>
    <a href="{{ route('products.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i>Back
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('products.update', $product->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Product Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name', $product->name) }}" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-select @error('category_id') is-invalid @enderror">
                        <option value="">Select Category</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ (old('category_id', $product->category_id) == $cat->id) ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $product->description) }}</textarea>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Cost Price <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" step="0.01" min="0" name="cost_price" class="form-control @error('cost_price') is-invalid @enderror"
                            value="{{ old('cost_price', $product->cost_price) }}" required>
                        @error('cost_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Selling Price <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" step="0.01" min="0" name="selling_price" class="form-control @error('selling_price') is-invalid @enderror"
                            value="{{ old('selling_price', $product->selling_price) }}" required>
                        @error('selling_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Quantity <span class="text-danger">*</span></label>
                    <input type="number" min="0" name="quantity" class="form-control @error('quantity') is-invalid @enderror"
                        value="{{ old('quantity', $product->quantity) }}" required>
                    @error('quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Product Image</label>
                    <input type="file" name="image" class="form-control @error('image') is-invalid @enderror" accept="image/*">
                    @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    @if($product->image)
                        <small class="text-muted">Current: <a href="{{ asset('storage/' . $product->image) }}" target="_blank">View Image</a></small>
                    @endif
                </div>
                <div class="col-md-3">
                    <label class="form-label">Barcode</label>
                    <input type="text" class="form-control" value="{{ $product->barcode }}" readonly disabled>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    @if($product->image)
                        <img src="{{ asset('storage/' . $product->image) }}" alt="" style="width: 60px; height: 60px; object-fit: cover; border-radius: 5px;">
                    @endif
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Update Product
                    </button>
                    <a href="{{ route('products.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

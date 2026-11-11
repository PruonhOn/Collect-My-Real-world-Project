@php
    $editing = isset($product) && $product->exists;
    $variantRows = $editing
        ? $product->variants
        : collect([null, null, null]);
@endphp

<form method="POST" action="{{ $editing ? route('products.update', $product) : route('products.store') }}" enctype="multipart/form-data" id="productForm">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <div class="card mb-3">
        <div class="card-header bg-transparent"><h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2"></i>Product Details</h6></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Product name <span class="text-danger">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $editing ? $product->name : '') }}" class="form-control @error('name') is-invalid @enderror" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">SKU <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="text" name="sku" value="{{ old('sku', $editing ? $product->sku : '') }}" class="form-control @error('sku') is-invalid @enderror" required>
                        <button type="button" class="btn btn-outline-secondary" id="generateSku" title="Generate SKU"><i class="bi bi-dice-5"></i></button>
                    </div>
                    @error('sku')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Category <span class="text-danger">*</span></label>
                    <select name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                        <option value="">Select category...</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $editing ? $product->category_id : '') == $category->id)>{{ $category->full_name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Brand</label>
                    <input type="text" name="brand" value="{{ old('brand', $editing ? $product->brand : '') }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Base price <span class="text-danger">*</span></label>
                    <input type="number" name="base_price" step="0.01" min="0" value="{{ old('base_price', $editing ? $product->base_price : '') }}" class="form-control @error('base_price') is-invalid @enderror" required>
                    @error('base_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">Cost price</label>
                    <input type="number" name="cost_price" step="0.01" min="0" value="{{ old('cost_price', $editing ? $product->cost_price : '') }}" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="3" class="form-control">{{ old('description', $editing ? $product->description : '') }}</textarea>
                </div>
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" @checked(old('is_active', $editing ? $product->is_active : true))>
                        <label class="form-check-label" for="is_active">Product is active (visible in POS)</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-transparent d-flex align-items-center justify-content-between">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-grid-3x3-gap me-2"></i>Variants <span class="text-muted fw-normal small">(size / color with own stock)</span></h6>
            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill" id="addVariantRow"><i class="bi bi-plus-lg me-1"></i>Add Variant</button>
        </div>
        <div class="card-body">
            @error('variants')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror
            <div class="table-responsive">
                <table class="table table-sm align-middle variant-table">
                    <thead class="table-light">
                        <tr>
                            <th style="width:130px">Size</th>
                            <th style="width:160px">Color</th>
                            <th>Variant SKU <span class="text-danger">*</span></th>
                            <th style="width:150px">Barcode</th>
                            <th style="width:110px">Stock</th>
                            <th style="width:140px">Price override</th>
                            <th style="width:45px"></th>
                        </tr>
                    </thead>
                    <tbody id="variantRows">
                        @foreach ($variantRows as $index => $variant)
                            @include('products.partials.variant-row', [
                                'variant' => $variant,
                                'index' => $index,
                                'old' => old("variants.$index"),
                            ])
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="small text-muted mb-0"><i class="bi bi-lightbulb me-1"></i>Leave size/color blank for a single-variant product. The barcode is auto-generated if empty.</p>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-transparent"><h6 class="mb-0 fw-semibold"><i class="bi bi-images me-2"></i>Product Images</h6></div>
        <div class="card-body">
            @if ($editing && $product->images->count())
                <div class="mb-3">
                    <label class="form-label small text-muted">Current images (uncheck to remove)</label>
                    <div class="d-flex flex-wrap gap-3" id="existingImages">
                        @foreach ($product->images as $image)
                            <div class="existing-image">
                                <img src="{{ $image->url }}" alt="">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="existing_images[]" value="{{ $image->image_path }}" id="img{{ $image->id }}" checked>
                                    <label class="form-check-label small" for="img{{ $image->id }}">Keep</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
            <label class="form-label small text-muted">Upload new images (max 5)</label>
            <input type="file" name="images[]" class="form-control" multiple accept="image/*">
            @error('images.*')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-lg px-4 rounded-pill">
            <i class="bi bi-check-lg me-1"></i>{{ $editing ? 'Update Product' : 'Create Product' }}
        </button>
        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-lg px-4 rounded-pill">Cancel</a>
    </div>
</form>

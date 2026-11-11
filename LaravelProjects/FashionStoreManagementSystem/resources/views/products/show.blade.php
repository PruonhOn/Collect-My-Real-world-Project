@extends('layouts.app')

@section('title', $product->name)
@section('heading', $product->name)
@section('subheading', $product->sku.' · '.$product->category?->full_name)

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    @if ($product->primaryImage())
                        <img src="{{ $product->primaryImage()->url }}" class="img-fluid rounded-3 product-detail-image" alt="">
                    @else
                        <div class="product-detail-placeholder"><i class="bi bi-image"></i></div>
                    @endif
                </div>
                @if ($product->images->count() > 1)
                    <div class="card-body pt-0 d-flex flex-wrap gap-2 justify-content-center">
                        @foreach ($product->images as $image)
                            <img src="{{ $image->url }}" class="product-thumb-sm rounded" alt="">
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
                        <div>
                            <span class="badge text-bg-light border me-1">{{ $product->category?->full_name }}</span>
                            @if ($product->brand)
                                <span class="badge text-bg-light border">{{ $product->brand }}</span>
                            @endif
                            @if ($product->is_active)
                                <span class="badge bg-success-subtle text-success">Active</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                            @endif
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-primary rounded-pill"><i class="bi bi-pencil me-1"></i>Edit</a>
                        </div>
                    </div>

                    <p class="text-muted">{{ $product->description ?: 'No description provided.' }}</p>

                    <div class="row g-3">
                        <div class="col-6 col-md-3">
                            <div class="detail-stat"><span class="detail-label">Base Price</span><strong>{{ money($product->base_price) }}</strong></div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="detail-stat"><span class="detail-label">Cost Price</span><strong>{{ money($product->cost_price) }}</strong></div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="detail-stat"><span class="detail-label">Total Stock</span>
                                <strong class="{{ $product->total_stock <= 5 ? 'text-warning' : ($product->total_stock == 0 ? 'text-danger' : '') }}">{{ $product->total_stock }} units</strong>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="detail-stat"><span class="detail-label">Variants</span><strong>{{ $product->variant_count }}</strong></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-transparent d-flex align-items-center justify-content-between">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-grid-3x3-gap me-2"></i>Variants & Barcodes</h6>
            <span class="small text-muted">Print barcodes on label sheets or scan from the POS screen.</span>
        </div>
        <div class="card-body">
            <div class="row g-3" id="variantBarcodes">
                @foreach ($product->variants as $variant)
                    <div class="col-6 col-md-4 col-xl-3">
                        <div class="barcode-card text-center">
                            <div class="fw-semibold">{{ $variant->label ?: 'Default' }}</div>
                            <small class="text-muted">{{ $variant->sku_variant }}</small>
                            <svg class="barcode-svg" data-barcode="{{ $variant->barcode ?: $variant->sku_variant }}" data-label="{{ $variant->sku_variant }}"></svg>
                            <div class="mt-2">
                                <span class="badge {{ $variant->stock_quantity <= 5 ? 'bg-warning-subtle text-warning' : 'bg-success-subtle text-success' }}">
                                    {{ $variant->stock_quantity }} in stock
                                </span>
                                <span class="badge text-bg-light border">{{ money($variant->price) }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <script>
        document.querySelectorAll('.barcode-svg').forEach(svg => {
            JsBarcode(svg, svg.dataset.barcode, { format: 'CODE128', width: 2, height: 50, displayValue: true, fontSize: 12 });
        });
    </script>
@endpush

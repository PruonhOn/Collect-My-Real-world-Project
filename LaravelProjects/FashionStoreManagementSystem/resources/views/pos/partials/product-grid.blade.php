@if ($products->isEmpty())
    <div class="empty-state w-100">
        <i class="bi bi-search"></i>
        <p>No products found. Try a different search.</p>
    </div>
@endif

@foreach ($products as $product)
    @php
        $inStock = $product->variants->filter(fn ($v) => $v->stock_quantity > 0);
        $priceFrom = $inStock->min('price');
        $priceTo = $inStock->max('price');
        $image = $product->primaryImage();
    @endphp
    <div class="product-card" data-product-id="{{ $product->id }}">
        <div class="product-thumb">
            @if ($image)
                <img src="{{ $image->url }}" alt="{{ $product->name }}" loading="lazy">
            @else
                <div class="product-thumb-placeholder"><i class="bi bi-image"></i></div>
            @endif
            @if ($product->brand)
                <span class="product-brand">{{ $product->brand }}</span>
            @endif
        </div>
        <div class="product-info">
            <h3 class="product-name" title="{{ $product->name }}">{{ $product->name }}</h3>
            <div class="product-price">
                @if ($priceFrom != $priceTo && $priceFrom !== null)
                    {{ money($priceFrom) }} – {{ money($priceTo) }}
                @elseif ($priceFrom !== null)
                    {{ money($priceFrom) }}
                @else
                    {{ money($product->base_price) }}
                @endif
            </div>
            <div class="variant-chips" data-product-name="{{ $product->name }}">
                @foreach ($inStock->take(6) as $variant)
                    <button type="button"
                            class="variant-chip {{ $variant->stock_quantity <= 5 ? 'chip-warn' : '' }}"
                            data-variant-id="{{ $variant->id }}"
                            data-name="{{ $variant->product->name }}"
                            data-label="{{ $variant->label ?: 'Default' }}"
                            data-price="{{ (float) $variant->price }}"
                            data-stock="{{ $variant->stock_quantity }}"
                            title="{{ $variant->label }} · {{ money($variant->price) }} · {{ $variant->stock_quantity }} in stock">
                        {{ $variant->label ?: 'Default' }}
                    </button>
                @endforeach
                @if ($inStock->count() > 6)
                    <span class="variant-more">+{{ $inStock->count() - 6 }} more</span>
                @endif
            </div>
        </div>
    </div>
@endforeach

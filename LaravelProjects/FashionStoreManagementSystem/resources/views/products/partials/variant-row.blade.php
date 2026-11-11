@php
    $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
    $colors = ['Black', 'White', 'Red', 'Navy', 'Beige', 'Pink', 'Blue', 'Green'];
@endphp
<tr class="variant-row">
    <td>
        <input type="text" name="variants[{{ $index }}][id]" value="{{ $variant?->id }}" class="variant-id" hidden>
        <input type="text" name="variants[{{ $index }}][size]" value="{{ old("variants.$index.size", $variant?->size) }}" class="form-control form-control-sm" list="sizesList" placeholder="e.g. M">
    </td>
    <td>
        <input type="text" name="variants[{{ $index }}][color]" value="{{ old("variants.$index.color", $variant?->color) }}" class="form-control form-control-sm" list="colorsList" placeholder="e.g. Black">
    </td>
    <td>
        <input type="text" name="variants[{{ $index }}][sku_variant]" value="{{ old("variants.$index.sku_variant", $variant?->sku_variant) }}" class="form-control form-control-sm @error('variants.'.$index.'.sku_variant') is-invalid @enderror" required>
        @error('variants.'.$index.'.sku_variant')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </td>
    <td>
        <div class="input-group input-group-sm">
            <input type="text" name="variants[{{ $index }}][barcode]" value="{{ old("variants.$index.barcode", $variant?->barcode) }}" class="form-control" placeholder="Auto">
            <button type="button" class="btn btn-outline-secondary btn-gen-barcode" title="Generate barcode"><i class="bi bi-upc-scan"></i></button>
        </div>
    </td>
    <td>
        <input type="number" name="variants[{{ $index }}][stock_quantity]" value="{{ old("variants.$index.stock_quantity", $variant?->stock_quantity ?? 0) }}" class="form-control form-control-sm" min="0" required>
    </td>
    <td>
        <input type="number" name="variants[{{ $index }}][price_override]" step="0.01" min="0" value="{{ old("variants.$index.price_override", $variant?->price_override) }}" class="form-control form-control-sm" placeholder="Use base">
    </td>
    <td class="text-center">
        <button type="button" class="btn btn-sm btn-soft text-danger btn-remove-variant" title="Remove"><i class="bi bi-x-lg"></i></button>
    </td>
</tr>

<datalist id="sizesList">
    @foreach ($sizes as $size)<option value="{{ $size }}">@endforeach
</datalist>
<datalist id="colorsList">
    @foreach ($colors as $color)<option value="{{ $color }}">@endforeach
</datalist>

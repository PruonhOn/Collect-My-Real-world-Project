<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductService
{
    public function createWithVariants(array $data, array $images = []): Product
    {
        return DB::transaction(function () use ($data, $images) {
            $product = Product::create([
                'category_id' => $data['category_id'],
                'name' => $data['name'],
                'sku' => $data['sku'],
                'description' => $data['description'] ?? null,
                'base_price' => $data['base_price'],
                'cost_price' => $data['cost_price'] ?? 0,
                'brand' => $data['brand'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            $this->syncVariants($product, $data['variants']);
            $this->syncImages($product, $images, []);

            return $product->load('variants', 'images');
        });
    }

    public function updateWithVariants(Product $product, array $data, array $images = [], array $keepPaths = []): Product
    {
        return DB::transaction(function () use ($product, $data, $images, $keepPaths) {
            $product->update([
                'category_id' => $data['category_id'],
                'name' => $data['name'],
                'sku' => $data['sku'],
                'description' => $data['description'] ?? null,
                'base_price' => $data['base_price'],
                'cost_price' => $data['cost_price'] ?? 0,
                'brand' => $data['brand'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            $this->syncVariants($product, $data['variants']);
            $this->syncImages($product, $images, $keepPaths);

            return $product->load('variants', 'images');
        });
    }

    public function syncVariants(Product $product, array $variants): void
    {
        $submittedIds = collect($variants)->pluck('id')->filter()->all();
        $product->variants()->whereNotIn('id', $submittedIds)->delete();

        foreach ($variants as $variantData) {
            $variantId = $variantData['id'] ?? null;
            $payload = [
                'size' => $variantData['size'] ?? null,
                'color' => $variantData['color'] ?? null,
                'sku_variant' => $variantData['sku_variant'],
                'barcode' => $variantData['barcode'] ?? null,
                'stock_quantity' => $variantData['stock_quantity'] ?? 0,
                'price_override' => ! empty($variantData['price_override']) ? $variantData['price_override'] : null,
            ];

            if ($variantId && $variant = ProductVariant::find($variantId)) {
                $variant->update($payload);
            } else {
                ProductVariant::create($payload + ['product_id' => $product->id]);
            }
        }
    }

    public function syncImages(Product $product, array $images, array $keepPaths = []): void
    {
        foreach ($images as $image) {
            $path = $image->store('products', 'public');
            $product->images()->create([
                'image_path' => $path,
                'is_primary' => $product->images()->count() === 0,
            ]);
        }

        $preserve = collect($keepPaths)
            ->filter(fn ($path) => is_string($path) && $path !== '')
            ->unique()
            ->all();

        $remove = $product->images()->whereNotIn('image_path', $preserve)->get();

        foreach ($remove as $image) {
            Storage::disk('public')->delete($image->image_path);
            $image->delete();
        }

        if ($product->images()->count() > 0 && ! $product->images()->where('is_primary', true)->exists()) {
            $product->images()->first()->update(['is_primary' => true]);
        }
    }

    public static function generateSku(string $prefix = 'FST'): string
    {
        return strtoupper($prefix.'-'.Str::upper(Str::random(6)));
    }
}

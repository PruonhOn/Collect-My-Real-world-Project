<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    private array $sizes = ['XS', 'S', 'M', 'L', 'XL'];
    private array $colors = ['Black', 'White', 'Navy', 'Beige', 'Rose'];

    public function run(): void
    {
        Storage::disk('public')->makeDirectory('products');

        $products = $this->catalogue();

        foreach ($products as $data) {
            [$parent, $child] = array_map('trim', explode('>', $data['category']));
            $category = Category::where('slug', Str::slug($child.'-'.$parent))->first()
                ?? Category::where('slug', Str::slug($parent))->first();

            $product = Product::create([
                'category_id' => $category?->id ?? Category::first()->id,
                'name' => $data['name'],
                'sku' => $data['sku'],
                'description' => $data['description'],
                'base_price' => $data['price'],
                'cost_price' => round($data['price'] * 0.55, 2),
                'brand' => $data['brand'],
                'is_active' => true,
            ]);

            $imagePath = $this->generatePlaceholder($product);
            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $imagePath,
                'is_primary' => true,
            ]);

            $variantIndex = 0;
            foreach ($data['variants'] as [$size, $color]) {
                $variantIndex++;
                $skuVariant = $data['sku'].'-V'.$variantIndex;
                $barcode = $this->uniqueBarcode();

                ProductVariant::create([
                    'product_id' => $product->id,
                    'size' => $size,
                    'color' => $color,
                    'sku_variant' => $skuVariant,
                    'barcode' => $barcode,
                    'stock_quantity' => random_int(25, 120),
                    'price_override' => null,
                ]);
            }
        }
    }

    private function catalogue(): array
    {
        return [
            [
                'name' => 'Silk Wrap Dress', 'category' => 'Women > Dresses', 'brand' => 'Velvet Vogue',
                'price' => 89.99, 'sku' => 'WV-DRS-001',
                'description' => 'Elegant silk wrap dress with a flattering V-neckline and tie waist.',
                'variants' => [['S', 'Navy'], ['M', 'Rose'], ['L', 'Black'], ['XL', 'Beige']],
            ],
            [
                'name' => 'Chiffon Maxi Dress', 'category' => 'Women > Dresses', 'brand' => 'Aura',
                'price' => 74.50, 'sku' => 'WV-DRS-002',
                'description' => 'Flowing chiffon maxi dress, perfect for summer evenings.',
                'variants' => [['S', 'White'], ['M', 'Rose'], ['L', 'Navy']],
            ],
            [
                'name' => 'Classic Button-Down Shirt', 'category' => 'Men > Shirts', 'brand' => 'Crown & Co',
                'price' => 42.00, 'sku' => 'MN-SHT-001',
                'description' => 'Crisp cotton button-down shirt for smart and casual looks.',
                'variants' => [['S', 'White'], ['M', 'White'], ['L', 'White'], ['XL', 'White'], ['M', 'Navy'], ['L', 'Navy']],
            ],
            [
                'name' => 'Linen Casual Shirt', 'category' => 'Men > Shirts', 'brand' => 'Crown & Co',
                'price' => 39.99, 'sku' => 'MN-SHT-002',
                'description' => 'Breathable linen shirt with a relaxed fit.',
                'variants' => [['S', 'Beige'], ['M', 'Beige'], ['L', 'Beige'], ['M', 'White'], ['L', 'Green']],
            ],
            [
                'name' => 'High-Waist Pleated Skirt', 'category' => 'Women > Bottoms', 'brand' => 'Velvet Vogue',
                'price' => 55.00, 'sku' => 'WV-BOT-001',
                'description' => 'Retro pleated skirt with a modern high waist.',
                'variants' => [['S', 'Black'], ['M', 'Black'], ['L', 'Beige'], ['M', 'Rose']],
            ],
            [
                'name' => 'Slim-Fit Chinos', 'category' => 'Men > Trousers', 'brand' => 'Urban Thread',
                'price' => 48.50, 'sku' => 'MN-TRS-001',
                'description' => 'Versatile slim-fit chinos in soft cotton twill.',
                'variants' => [['S', 'Navy'], ['M', 'Navy'], ['L', 'Navy'], ['XL', 'Navy'], ['M', 'Beige'], ['L', 'Beige']],
            ],
            [
                'name' => 'Cashmere Knit Sweater', 'category' => 'Women > Tops', 'brand' => 'Aura',
                'price' => 129.00, 'sku' => 'WV-TOP-001',
                'description' => 'Luxuriously soft cashmere crew-neck sweater.',
                'variants' => [['S', 'Rose'], ['M', 'Rose'], ['L', 'Beige'], ['M', 'Black']],
            ],
            [
                'name' => 'Oversized Hoodie', 'category' => 'Women > Tops', 'brand' => 'Urban Thread',
                'price' => 59.99, 'sku' => 'WV-TOP-002',
                'description' => 'Comfy oversized hoodie with kangaroo pocket.',
                'variants' => [['S', 'Black'], ['M', 'Black'], ['L', 'Grey'], ['XL', 'Grey'], ['M', 'White']],
            ],
            [
                'name' => 'Tailored Wool Blazer', 'category' => 'Men > Outerwear', 'brand' => 'Crown & Co',
                'price' => 149.00, 'sku' => 'MN-OTW-001',
                'description' => 'Sharp tailored blazer in pure wool.',
                'variants' => [['M', 'Navy'], ['L', 'Navy'], ['XL', 'Navy'], ['M', 'Black'], ['L', 'Black']],
            ],
            [
                'name' => 'Trench Coat', 'category' => 'Women > Outerwear', 'brand' => 'Aura',
                'price' => 179.00, 'sku' => 'WV-OTW-001',
                'description' => 'Timeless double-breasted trench coat.',
                'variants' => [['S', 'Beige'], ['M', 'Beige'], ['L', 'Beige'], ['M', 'Black']],
            ],
            [
                'name' => 'Leather Ankle Boots', 'category' => 'Footwear > Boots', 'brand' => 'Stride',
                'price' => 119.00, 'sku' => 'FW-BOT-001',
                'description' => 'Sleek leather ankle boots with a stacked heel.',
                'variants' => [['S', 'Black'], ['M', 'Black'], ['L', 'Brown'], ['M', 'Brown']],
            ],
            [
                'name' => 'Chunky Sneakers', 'category' => 'Footwear > Sneakers', 'brand' => 'Urban Thread',
                'price' => 89.00, 'sku' => 'FW-SNK-001',
                'description' => 'Trendy chunky sneakers with cushioned sole.',
                'variants' => [['S', 'White'], ['M', 'White'], ['L', 'White'], ['M', 'Black']],
            ],
            [
                'name' => 'Stiletto Heels', 'category' => 'Footwear > Heels', 'brand' => 'Stride',
                'price' => 99.99, 'sku' => 'FW-HL-001',
                'description' => 'Classic pointed stiletto heels.',
                'variants' => [['S', 'Black'], ['M', 'Black'], ['S', 'Rose'], ['M', 'Rose']],
            ],
            [
                'name' => 'Quilted Leather Handbag', 'category' => 'Bags & Wallets > Handbags', 'brand' => 'Stride',
                'price' => 139.00, 'sku' => 'BG-HB-001',
                'description' => 'Compact quilted leather handbag with gold hardware.',
                'variants' => [['One', 'Black'], ['One', 'Navy'], ['One', 'Rose']],
            ],
            [
                'name' => 'Canvas Backpack', 'category' => 'Bags & Wallets > Backpacks', 'brand' => 'Urban Thread',
                'price' => 64.00, 'sku' => 'BG-BP-001',
                'description' => 'Durable canvas backpack with padded laptop sleeve.',
                'variants' => [['One', 'Black'], ['One', 'Beige'], ['One', 'Navy']],
            ],
            [
                'name' => 'Minimalist Leather Wallet', 'category' => 'Bags & Wallets > Wallets', 'brand' => 'Crown & Co',
                'price' => 34.50, 'sku' => 'BG-WL-001',
                'description' => 'Slim leather wallet with card slots.',
                'variants' => [['One', 'Brown'], ['One', 'Black'], ['One', 'Navy']],
            ],
            [
                'name' => 'Gold Hoop Earrings', 'category' => 'Accessories > Jewellery', 'brand' => 'Aura',
                'price' => 24.99, 'sku' => 'AC-JWL-001',
                'description' => 'Lightweight gold-plated hoop earrings.',
                'variants' => [['One', 'Gold'], ['One', 'Silver']],
            ],
            [
                'name' => 'Polarized Sunglasses', 'category' => 'Accessories > Sunglasses', 'brand' => 'Stride',
                'price' => 49.00, 'sku' => 'AC-SNG-001',
                'description' => 'Polarized UV-protection sunglasses.',
                'variants' => [['One', 'Black'], ['One', 'Tortoise'], ['One', 'Rose']],
            ],
            [
                'name' => 'Silk Scarf', 'category' => 'Accessories > Scarves', 'brand' => 'Velvet Vogue',
                'price' => 29.00, 'sku' => 'AC-SCV-001',
                'description' => 'Square silk scarf with printed pattern.',
                'variants' => [['One', 'Rose'], ['One', 'Navy'], ['One', 'Beige']],
            ],
            [
                'name' => 'Leather Belt', 'category' => 'Accessories > Belts', 'brand' => 'Crown & Co',
                'price' => 39.00, 'sku' => 'AC-BLT-001',
                'description' => 'Classic leather belt with buckle.',
                'variants' => [['S', 'Brown'], ['M', 'Brown'], ['L', 'Black'], ['XL', 'Black']],
            ],
        ];
    }

    private function generatePlaceholder(Product $product): string
    {
        $hue = random_int(300, 360);
        $name = e($product->name);
        $brand = e($product->brand ?? 'Fashion');
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="600" height="400" viewBox="0 0 600 400">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="hsl({$hue}, 55%, 88%)"/>
      <stop offset="100%" stop-color="hsl({$hue}, 65%, 75%)"/>
    </linearGradient>
  </defs>
  <rect width="600" height="400" fill="url(#g)"/>
  <g fill="hsl({$hue}, 45%, 30%)" text-anchor="middle" font-family="Segoe UI, Arial, sans-serif">
    <rect x="230" y="130" width="140" height="140" rx="20" fill="hsl({$hue}, 50%, 55%)"/>
    <text x="300" y="270" font-size="28" font-weight="bold">{$name}</text>
    <text x="300" y="300" font-size="18">{$brand}</text>
  </g>
</svg>
SVG;

        $filename = 'products/'.Str::lower($product->sku).'.svg';
        Storage::disk('public')->put($filename, $svg);

        return $filename;
    }

    private function uniqueBarcode(): string
    {
        do {
            $barcode = '2'.random_int(100000000000, 999999999999);
        } while (ProductVariant::where('barcode', $barcode)->exists());

        return $barcode;
    }
}

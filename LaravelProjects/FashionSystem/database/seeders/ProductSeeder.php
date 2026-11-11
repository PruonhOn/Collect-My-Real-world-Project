<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    private array $sizes = ['S', 'M', 'L', 'XL', 'XXL'];

    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        ProductVariant::truncate();
        Product::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $brands = Brand::pluck('id', 'slug');
        $suppliers = Supplier::pluck('id');
        $categories = Category::whereNotNull('parent_id')->pluck('id', 'name');

        $products = [
            // Men's T-Shirts
            [
                'name' => 'Classic White T-Shirt',
                'category' => 'T-Shirts', 'brand' => 'uniqlo', 'cost' => 250, 'price' => 450,
                'colors' => ['White', 'Black', 'Gray'],
            ],
            [
                'name' => 'Black Graphic Tee',
                'category' => 'T-Shirts', 'brand' => 'nike', 'cost' => 350, 'price' => 650,
                'colors' => ['Black', 'White'],
            ],
            [
                'name' => 'Navy Blue Crew Neck',
                'category' => 'T-Shirts', 'brand' => 'hm', 'cost' => 200, 'price' => 399,
                'colors' => ['Navy Blue', 'Dark Green', 'Maroon'],
            ],

            // Men's Polo Shirts
            [
                'name' => 'Classic Red Polo Shirt',
                'category' => 'Polo Shirts', 'brand' => 'puma', 'cost' => 400, 'price' => 750,
                'colors' => ['Red', 'White', 'Blue'],
            ],
            [
                'name' => 'Striped Polo Shirt',
                'category' => 'Polo Shirts', 'brand' => 'adidas', 'cost' => 450, 'price' => 850,
                'colors' => ['Navy/White', 'Black/White'],
            ],

            // Men's Pants
            [
                'name' => 'Khaki Chino Pants',
                'category' => 'Pants', 'brand' => 'zara', 'cost' => 500, 'price' => 950,
                'colors' => ['Khaki', 'Navy', 'Black'],
            ],
            [
                'name' => 'Black Formal Trousers',
                'category' => 'Pants', 'brand' => 'gucci', 'cost' => 1200, 'price' => 2500,
                'colors' => ['Black', 'Charcoal'],
            ],
            [
                'name' => 'Dark Wash Denim Jeans',
                'category' => 'Pants', 'brand' => 'levis', 'cost' => 600, 'price' => 1200,
                'colors' => ['Dark Blue', 'Black', 'Light Blue'],
            ],

            // Men's Shorts
            [
                'name' => 'Grey Athletic Shorts',
                'category' => 'Shorts', 'brand' => 'nike', 'cost' => 300, 'price' => 599,
                'colors' => ['Grey', 'Black', 'Navy'],
            ],
            [
                'name' => 'Cargo Shorts',
                'category' => 'Shorts', 'brand' => 'hm', 'cost' => 350, 'price' => 650,
                'colors' => ['Beige', 'Olive', 'Black'],
            ],

            // Men's Jackets
            [
                'name' => 'Denim Jacket',
                'category' => 'Jackets', 'brand' => 'levis', 'cost' => 800, 'price' => 1500,
                'colors' => ['Blue', 'Black'],
            ],
            [
                'name' => 'Leather Bomber Jacket',
                'category' => 'Jackets', 'brand' => 'zara', 'cost' => 1500, 'price' => 2800,
                'colors' => ['Black', 'Brown'],
            ],

            // Men's Suits
            [
                'name' => 'Navy Blue Suit',
                'category' => 'Suits', 'brand' => 'gucci', 'cost' => 2000, 'price' => 3500,
                'colors' => ['Navy Blue', 'Charcoal Gray'],
            ],

            // Men's Shoes
            [
                'name' => 'Brown Leather Oxfords',
                'category' => 'Shoes', 'brand' => 'zara', 'cost' => 900, 'price' => 1800,
                'sizes' => ['40', '41', '42', '43', '44'], 'colors' => ['Brown', 'Black'],
            ],
            [
                'name' => 'Black Dress Shoes',
                'category' => 'Shoes', 'brand' => 'gucci', 'cost' => 1500, 'price' => 3200,
                'sizes' => ['40', '41', '42', '43', '44'], 'colors' => ['Black'],
            ],

            // Men's Accessories
            [
                'name' => 'Genuine Leather Belt',
                'category' => 'Accessories', 'brand' => 'levis', 'cost' => 400, 'price' => 850,
                'sizes' => ['28', '30', '32', '34', '36'], 'colors' => ['Black', 'Brown'],
            ],
            [
                'name' => 'Silk Tie',
                'category' => 'Accessories', 'brand' => 'gucci', 'cost' => 600, 'price' => 1200,
                'colors' => ['Navy', 'Burgundy', 'Black'],
            ],

            // Women's Dresses
            [
                'name' => 'Floral Summer Dress',
                'category' => 'Dresses', 'brand' => 'zara', 'cost' => 500, 'price' => 999,
                'colors' => ['Floral Blue', 'Floral Pink'],
            ],
            [
                'name' => 'Little Black Dress',
                'category' => 'Dresses', 'brand' => 'hm', 'cost' => 450, 'price' => 899,
                'colors' => ['Black'],
            ],
            [
                'name' => 'Maxi Dress',
                'category' => 'Dresses', 'brand' => 'uniqlo', 'cost' => 550, 'price' => 1099,
                'colors' => ['Mint Green', 'Dusty Rose', 'Navy'],
            ],

            // Women's Tops
            [
                'name' => 'White Button-Up Blouse',
                'category' => 'Tops', 'brand' => 'zara', 'cost' => 350, 'price' => 699,
                'colors' => ['White', 'Cream'],
            ],
            [
                'name' => 'Knit Crop Top',
                'category' => 'Tops', 'brand' => 'hm', 'cost' => 250, 'price' => 499,
                'colors' => ['Beige', 'Black', 'White'],
            ],

            // Women's Skirts
            [
                'name' => 'Pleated Mini Skirt',
                'category' => 'Skirts', 'brand' => 'zara', 'cost' => 350, 'price' => 699,
                'colors' => ['Black', 'Plaid', 'Navy'],
            ],
            [
                'name' => 'Pencil Skirt',
                'category' => 'Skirts', 'brand' => 'hm', 'cost' => 400, 'price' => 799,
                'colors' => ['Black', 'Gray', 'Navy'],
            ],

            // Women's Pants
            [
                'name' => 'High-Waist Trousers',
                'category' => 'Pants', 'brand' => 'zara', 'cost' => 500, 'price' => 999,
                'colors' => ['Black', 'Beige', 'Brown'],
            ],
            [
                'name' => 'Skinny Fit Jeans',
                'category' => 'Pants', 'brand' => 'levis', 'cost' => 550, 'price' => 1100,
                'colors' => ['Dark Blue', 'Black', 'Light Wash'],
            ],

            // Women's Blouses
            [
                'name' => 'Silk Blouse',
                'category' => 'Blouses', 'brand' => 'gucci', 'cost' => 1200, 'price' => 2500,
                'colors' => ['White', 'Blush Pink', 'Black'],
            ],
            [
                'name' => 'Printed Blouse',
                'category' => 'Blouses', 'brand' => 'zara', 'cost' => 350, 'price' => 750,
                'colors' => ['Floral', 'Polka Dot'],
            ],

            // Women's Jackets
            [
                'name' => 'Tailored Blazer',
                'category' => 'Jackets', 'brand' => 'zara', 'cost' => 700, 'price' => 1400,
                'colors' => ['Black', 'Navy', 'Beige'],
            ],

            // Women's Shoes
            [
                'name' => 'Stiletto Heels',
                'category' => 'Shoes', 'brand' => 'zara', 'cost' => 600, 'price' => 1299,
                'sizes' => ['36', '37', '38', '39', '40'], 'colors' => ['Black', 'Nude', 'Red'],
            ],
            [
                'name' => 'Ballet Flats',
                'category' => 'Shoes', 'brand' => 'hm', 'cost' => 300, 'price' => 599,
                'sizes' => ['36', '37', '38', '39', '40'], 'colors' => ['Black', 'Beige', 'Silver'],
            ],

            // Women's Handbags
            [
                'name' => 'Leather Tote Bag',
                'category' => 'Handbags', 'brand' => 'zara', 'cost' => 800, 'price' => 1600,
                'colors' => ['Black', 'Brown', 'Beige'],
            ],
            [
                'name' => 'Crossbody Bag',
                'category' => 'Handbags', 'brand' => 'gucci', 'cost' => 1800, 'price' => 3500,
                'colors' => ['Black', 'White', 'Red'],
            ],

            // Kids - Boys T-Shirts
            [
                'name' => 'Cartoon Print Tee',
                'category' => 'T-Shirts', 'brand' => 'hm', 'cost' => 200, 'price' => 399,
                'child' => 'Boys', 'sizes' => ['2T', '3T', '4T', '5', '6'],
                'colors' => ['Blue', 'Red'],
            ],
            [
                'name' => 'Solid Color Tee for Boys',
                'category' => 'T-Shirts', 'brand' => 'uniqlo', 'cost' => 180, 'price' => 350,
                'child' => 'Boys', 'sizes' => ['2T', '3T', '4T', '5', '6'],
                'colors' => ['White', 'Gray', 'Navy'],
            ],

            // Kids - Girls Dresses
            [
                'name' => 'Princess Dress',
                'category' => 'Dresses', 'brand' => 'hm', 'cost' => 350, 'price' => 699,
                'child' => 'Girls', 'sizes' => ['2T', '3T', '4T', '5', '6'],
                'colors' => ['Pink', 'Lavender', 'White'],
            ],

            // Baby
            [
                'name' => 'Printed Baby Onesie',
                'category' => 'Onesies', 'brand' => 'uniqlo', 'cost' => 150, 'price' => 299,
                'child' => 'Baby', 'sizes' => ['0-3M', '3-6M', '6-9M', '9-12M'],
                'colors' => ['White', 'Yellow', 'Mint'],
            ],
            [
                'name' => 'Footed Pajamas',
                'category' => 'Sleepwear', 'brand' => 'hm', 'cost' => 200, 'price' => 399,
                'child' => 'Baby', 'sizes' => ['0-3M', '3-6M', '6-9M', '9-12M'],
                'colors' => ['Gray', 'Blue', 'Pink'],
            ],

            // Accessories - Bags
            [
                'name' => 'Canvas Backpack',
                'category' => 'Bags', 'brand' => 'nike', 'cost' => 500, 'price' => 999,
                'colors' => ['Black', 'Gray', 'Blue'],
            ],
            [
                'name' => 'Duffel Bag',
                'category' => 'Bags', 'brand' => 'adidas', 'cost' => 600, 'price' => 1200,
                'colors' => ['Black', 'Navy'],
            ],

            // Accessories - Hats
            [
                'name' => 'Classic Baseball Cap',
                'category' => 'Hats', 'brand' => 'nike', 'cost' => 200, 'price' => 450,
                'colors' => ['Black', 'Red', 'Navy'],
            ],
            [
                'name' => 'Fedora Hat',
                'category' => 'Hats', 'brand' => 'zara', 'cost' => 350, 'price' => 699,
                'colors' => ['Black', 'Brown'],
            ],

            // Accessories - Watches
            [
                'name' => 'Classic Analog Watch',
                'category' => 'Watches', 'brand' => 'gucci', 'cost' => 1500, 'price' => 3000,
                'colors' => ['Silver/Black', 'Gold/Brown'],
            ],
            [
                'name' => 'Sport Smart Watch',
                'category' => 'Watches', 'brand' => 'nike', 'cost' => 800, 'price' => 1600,
                'colors' => ['Black', 'White'],
            ],

            // Accessories - Sunglasses
            [
                'name' => 'Aviator Sunglasses',
                'category' => 'Sunglasses', 'brand' => 'gucci', 'cost' => 1000, 'price' => 2200,
                'colors' => ['Gold/Green', 'Silver/Black'],
            ],

            // Footwear - Sneakers
            [
                'name' => 'Running Sneakers',
                'category' => 'Sneakers', 'brand' => 'nike', 'cost' => 800, 'price' => 1600,
                'sizes' => ['40', '41', '42', '43', '44'], 'colors' => ['Black/White', 'Blue/White', 'Red/White'],
            ],
            [
                'name' => 'Casual Sneakers',
                'category' => 'Sneakers', 'brand' => 'adidas', 'cost' => 700, 'price' => 1400,
                'sizes' => ['40', '41', '42', '43', '44'], 'colors' => ['White', 'Black', 'Gray'],
            ],

            // Footwear - Sandals
            [
                'name' => 'Flip Flops',
                'category' => 'Sandals', 'brand' => 'hm', 'cost' => 150, 'price' => 299,
                'sizes' => ['38', '39', '40', '41', '42'], 'colors' => ['Black', 'Brown', 'Blue'],
            ],
            [
                'name' => 'Slide Sandals',
                'category' => 'Sandals', 'brand' => 'adidas', 'cost' => 250, 'price' => 550,
                'sizes' => ['38', '39', '40', '41', '42'], 'colors' => ['Black', 'White', 'Green'],
            ],

            // Footwear - Boots
            [
                'name' => 'Ankle Boots',
                'category' => 'Boots', 'brand' => 'zara', 'cost' => 700, 'price' => 1500,
                'sizes' => ['37', '38', '39', '40', '41'], 'colors' => ['Black', 'Brown'],
            ],
            [
                'name' => 'Winter Boots',
                'category' => 'Boots', 'brand' => 'puma', 'cost' => 900, 'price' => 1800,
                'sizes' => ['39', '40', '41', '42', '43'], 'colors' => ['Black', 'Gray'],
            ],
        ];

        $skuIndex = 1;

        foreach ($products as $data) {
            $categoryName = $data['category'];
            $childName = $data['child'] ?? null;

            if ($childName) {
                $childCat = Category::where('name', $childName)->first();
                $catId = $childCat
                    ? Category::where('parent_id', $childCat->id)->where('name', $categoryName)->first()?->id
                    : $categories[$categoryName] ?? null;
            } else {
                $catId = $categories[$categoryName] ?? null;
            }

            if (!$catId) {
                continue;
            }

            $brandId = $brands[$data['brand']] ?? null;
            $supplierId = $suppliers->random();

            $sku = 'SKU-' . str_pad($skuIndex++, 4, '0', STR_PAD_LEFT);
            $slug = Str::slug($data['name']);

            $product = Product::create([
                'category_id' => $catId,
                'brand_id' => $brandId,
                'supplier_id' => $supplierId,
                'name' => $data['name'],
                'slug' => $slug,
                'sku' => $sku,
                'barcode' => 'BAR' . str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT),
                'description' => $data['name'] . ' - high quality fashion item from K&N Fashion Store.',
                'cost_price' => $data['cost'],
                'selling_price' => $data['price'],
                'stock_quantity' => 0,
                'min_stock' => 10,
                'status' => true,
            ]);

            $productSizes = $data['sizes'] ?? $this->sizes;
            $productColors = $data['colors'] ?? ['Default'];
            $variantIdx = 1;

            foreach ($productColors as $color) {
                foreach ($productSizes as $size) {
                    $stock = random_int(10, 50);
                    ProductVariant::create([
                        'product_id' => $product->id,
                        'size' => $size,
                        'color' => $color,
                        'stock_quantity' => $stock,
                        'additional_price' => 0,
                    ]);
                    $product->stock_quantity += $stock;
                    $variantIdx++;
                }
            }

            $product->save();
        }
    }
}

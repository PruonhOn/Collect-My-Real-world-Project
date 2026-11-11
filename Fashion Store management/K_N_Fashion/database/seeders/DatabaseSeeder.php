<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@kandn.com',
            'password' => bcrypt('password'),
            'phone' => '1234567890',
            'role' => 'admin',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Manager',
            'email' => 'manager@kandn.com',
            'password' => bcrypt('password'),
            'phone' => '1234567891',
            'role' => 'manager',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Staff',
            'email' => 'staff@kandn.com',
            'password' => bcrypt('password'),
            'phone' => '1234567892',
            'role' => 'staff',
            'is_active' => true,
        ]);

        $categories = [
            ['name' => 'Men', 'description' => 'Men\'s fashion collection'],
            ['name' => 'Women', 'description' => 'Women\'s fashion collection'],
            ['name' => 'Kids', 'description' => 'Kids\' fashion collection'],
            ['name' => 'Accessories', 'description' => 'Fashion accessories'],
            ['name' => 'Footwear', 'description' => 'Shoes and footwear'],
        ];

        foreach ($categories as $cat) {
            Category::create($cat);
        }

        $subCategories = [
            ['name' => 'T-Shirts', 'parent_id' => 1],
            ['name' => 'Shirts', 'parent_id' => 1],
            ['name' => 'Jeans', 'parent_id' => 1],
            ['name' => 'Dresses', 'parent_id' => 2],
            ['name' => 'Tops', 'parent_id' => 2],
            ['name' => 'Skirts', 'parent_id' => 2],
            ['name' => 'Toys', 'parent_id' => 3],
            ['name' => 'Bags', 'parent_id' => 4],
            ['name' => 'Watches', 'parent_id' => 4],
            ['name' => 'Sneakers', 'parent_id' => 5],
        ];

        foreach ($subCategories as $cat) {
            Category::create($cat);
        }

        $suppliers = [
            ['name' => 'Fashion World Ltd.', 'contact_person' => 'John Doe', 'email' => 'john@fashionworld.com', 'phone' => '1111111111', 'city' => 'New York'],
            ['name' => 'Style Importers', 'contact_person' => 'Jane Smith', 'email' => 'jane@styleimporters.com', 'phone' => '2222222222', 'city' => 'Los Angeles'],
            ['name' => 'Trendy Wholesale', 'contact_person' => 'Mike Johnson', 'email' => 'mike@trendywholesale.com', 'phone' => '3333333333', 'city' => 'Chicago'],
        ];

        foreach ($suppliers as $sup) {
            Supplier::create($sup);
        }

        Customer::create(['name' => 'Walk-in Customer', 'email' => 'walkin@example.com', 'phone' => '0000000000', 'city' => 'Default', 'loyalty_points' => 0]);

        for ($i = 1; $i <= 10; $i++) {
            Customer::create([
                'name' => "Customer $i",
                'email' => "customer$i@example.com",
                'phone' => "12345678" . str_pad($i, 2, '0', STR_PAD_LEFT),
                'city' => 'City ' . $i,
                'loyalty_points' => rand(0, 500),
            ]);
        }

        $productData = [
            ['name' => 'Classic White T-Shirt', 'sku' => 'PRD-00001', 'category_id' => 6, 'supplier_id' => 1, 'cost_price' => 8.00, 'selling_price' => 19.99],
            ['name' => 'Denim Blue Jeans', 'sku' => 'PRD-00002', 'category_id' => 8, 'supplier_id' => 2, 'cost_price' => 25.00, 'selling_price' => 49.99],
            ['name' => 'Floral Summer Dress', 'sku' => 'PRD-00003', 'category_id' => 9, 'supplier_id' => 1, 'cost_price' => 18.00, 'selling_price' => 39.99],
            ['name' => 'Leather Messenger Bag', 'sku' => 'PRD-00004', 'category_id' => 13, 'supplier_id' => 3, 'cost_price' => 30.00, 'selling_price' => 59.99],
            ['name' => 'Casual Sneakers', 'sku' => 'PRD-00005', 'category_id' => 15, 'supplier_id' => 2, 'cost_price' => 20.00, 'selling_price' => 45.00],
            ['name' => 'Silk Tie', 'sku' => 'PRD-00006', 'category_id' => 4, 'supplier_id' => 1, 'cost_price' => 5.00, 'selling_price' => 14.99],
            ['name' => 'Wool Scarf', 'sku' => 'PRD-00007', 'category_id' => 4, 'supplier_id' => 3, 'cost_price' => 7.00, 'selling_price' => 18.99],
            ['name' => 'Kids Cartoon T-Shirt', 'sku' => 'PRD-00008', 'category_id' => 12, 'supplier_id' => 1, 'cost_price' => 6.00, 'selling_price' => 14.99],
            ['name' => 'Leather Belt', 'sku' => 'PRD-00009', 'category_id' => 4, 'supplier_id' => 2, 'cost_price' => 10.00, 'selling_price' => 24.99],
            ['name' => 'Running Shoes', 'sku' => 'PRD-00010', 'category_id' => 15, 'supplier_id' => 3, 'cost_price' => 35.00, 'selling_price' => 79.99],
        ];

        $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
        $colors = ['Black', 'White', 'Blue', 'Red', 'Green', 'Gray'];

        foreach ($productData as $data) {
            $product = Product::create($data);
            for ($v = 0; $v < 3; $v++) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'size' => $sizes[array_rand($sizes)],
                    'color' => $colors[array_rand($colors)],
                    'quantity' => rand(10, 50),
                    'price_adjustment' => 0,
                ]);
            }
        }

        Setting::create(['key' => 'store_name', 'value' => 'K & N Fashion']);
        Setting::create(['key' => 'store_address', 'value' => '123 Fashion Street, Style City']);
        Setting::create(['key' => 'store_phone', 'value' => '+1 234 567 890']);
        Setting::create(['key' => 'store_email', 'value' => 'info@kandn.com']);
        Setting::create(['key' => 'currency', 'value' => 'USD']);
        Setting::create(['key' => 'tax_rate', 'value' => '10']);
    }
}

<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Services\BarcodeService;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $barcodeService = new BarcodeService();

        $products = [
            ['name' => 'Coca Cola 1.5L', 'category_id' => 1, 'cost_price' => 1.20, 'selling_price' => 2.00, 'quantity' => 100],
            ['name' => 'Pepsi 1.5L', 'category_id' => 1, 'cost_price' => 1.15, 'selling_price' => 1.95, 'quantity' => 80],
            ['name' => 'Mineral Water 500ml', 'category_id' => 1, 'cost_price' => 0.30, 'selling_price' => 0.75, 'quantity' => 200],
            ['name' => 'Orange Juice 1L', 'category_id' => 1, 'cost_price' => 1.50, 'selling_price' => 2.50, 'quantity' => 60],
            ['name' => 'Lays Classic Chips', 'category_id' => 2, 'cost_price' => 0.80, 'selling_price' => 1.50, 'quantity' => 120],
            ['name' => 'Oreo Cookies', 'category_id' => 2, 'cost_price' => 1.00, 'selling_price' => 1.80, 'quantity' => 90],
            ['name' => 'Pringles Sour Cream', 'category_id' => 2, 'cost_price' => 1.50, 'selling_price' => 2.75, 'quantity' => 5],
            ['name' => 'Mixed Nuts', 'category_id' => 2, 'cost_price' => 2.00, 'selling_price' => 3.50, 'quantity' => 45],
            ['name' => 'Canned Tuna', 'category_id' => 3, 'cost_price' => 1.00, 'selling_price' => 1.80, 'quantity' => 70],
            ['name' => 'Canned Corn', 'category_id' => 3, 'cost_price' => 0.90, 'selling_price' => 1.60, 'quantity' => 55],
            ['name' => 'Tomato Sauce', 'category_id' => 3, 'cost_price' => 0.70, 'selling_price' => 1.30, 'quantity' => 85],
            ['name' => 'Whole Milk 1L', 'category_id' => 4, 'cost_price' => 1.10, 'selling_price' => 1.90, 'quantity' => 40],
            ['name' => 'Cheddar Cheese', 'category_id' => 4, 'cost_price' => 3.00, 'selling_price' => 5.00, 'quantity' => 30],
            ['name' => 'Yogurt Pack', 'category_id' => 4, 'cost_price' => 2.00, 'selling_price' => 3.20, 'quantity' => 25],
            ['name' => 'White Bread', 'category_id' => 5, 'cost_price' => 0.80, 'selling_price' => 1.50, 'quantity' => 50],
            ['name' => 'Chocolate Cake Slice', 'category_id' => 5, 'cost_price' => 2.00, 'selling_price' => 3.50, 'quantity' => 3],
            ['name' => 'Croissant', 'category_id' => 5, 'cost_price' => 0.75, 'selling_price' => 1.50, 'quantity' => 35],
            ['name' => 'Toothpaste', 'category_id' => 6, 'cost_price' => 1.50, 'selling_price' => 2.80, 'quantity' => 65],
            ['name' => 'Shampoo 200ml', 'category_id' => 6, 'cost_price' => 2.50, 'selling_price' => 4.50, 'quantity' => 40],
            ['name' => 'Body Soap', 'category_id' => 6, 'cost_price' => 0.60, 'selling_price' => 1.20, 'quantity' => 100],
        ];

        foreach ($products as $productData) {
            $productData['barcode'] = $barcodeService->generateUniqueBarcode();
            $productData['quantity'] = $productData['quantity'] ?? rand(5, 50);
            Product::create($productData);
        }
    }
}

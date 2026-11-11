<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Food & Beverages', 'description' => 'Food and drink products'],
            ['name' => 'Dairy Products', 'description' => 'Milk, cheese, yogurt etc.'],
            ['name' => 'Bakery', 'description' => 'Bread, cakes, pastries'],
            ['name' => 'Meat & Poultry', 'description' => 'Fresh and frozen meat'],
            ['name' => 'Fruits & Vegetables', 'description' => 'Fresh produce'],
            ['name' => 'Beverages', 'description' => 'Soft drinks, juices, water'],
            ['name' => 'Snacks', 'description' => 'Chips, cookies, candies'],
            ['name' => 'Personal Care', 'description' => 'Soap, shampoo, cosmetics'],
            ['name' => 'Household', 'description' => 'Cleaning supplies, kitchen items'],
            ['name' => 'Electronics', 'description' => 'Small electronics and accessories'],
            ['name' => 'Stationery', 'description' => 'Office and school supplies'],
            ['name' => 'Baby Products', 'description' => 'Diapers, baby food, etc.'],
        ];

        foreach ($categories as $cat) {
            Category::create([
                'name' => $cat['name'],
                'slug' => Str::slug($cat['name']),
                'description' => $cat['description'],
                'status' => 'active',
                'created_by' => 1,
            ]);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Beverages', 'description' => 'Drinks and beverages'],
            ['name' => 'Snacks', 'description' => 'Chips, crackers, and snack foods'],
            ['name' => 'Canned Goods', 'description' => 'Canned food and preserves'],
            ['name' => 'Dairy', 'description' => 'Milk, cheese, and dairy products'],
            ['name' => 'Bakery', 'description' => 'Bread, cakes, and pastries'],
            ['name' => 'Personal Care', 'description' => 'Toiletries and personal care items'],
            ['name' => 'Household', 'description' => 'Cleaning and household supplies'],
            ['name' => 'Frozen Foods', 'description' => 'Frozen meals and ice cream'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}

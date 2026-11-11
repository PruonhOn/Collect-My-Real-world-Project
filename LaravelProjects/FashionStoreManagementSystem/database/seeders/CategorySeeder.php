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
            'Women' => ['Dresses', 'Tops', 'Bottoms', 'Outerwear', 'Accessories'],
            'Men' => ['Shirts', 'Trousers', 'Outerwear', 'Accessories'],
            'Footwear' => ['Heels', 'Sneakers', 'Sandals', 'Boots'],
            'Bags & Wallets' => ['Handbags', 'Backpacks', 'Wallets'],
            'Accessories' => ['Jewellery', 'Sunglasses', 'Belts', 'Scarves'],
        ];

        foreach ($categories as $parentName => $children) {
            $parent = Category::create([
                'name' => $parentName,
                'slug' => Str::slug($parentName),
                'description' => "Main {$parentName} category.",
                'is_active' => true,
            ]);

            foreach ($children as $childName) {
                Category::create([
                    'name' => $childName,
                    'slug' => Str::slug($childName.'-'.$parentName),
                    'parent_id' => $parent->id,
                    'description' => "{$childName} for {$parentName}.",
                    'is_active' => true,
                ]);
            }
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([SystemSettingSeeder::class]);

        User::create([
            'name' => 'Pruonh',
            'email' => 'Pruonh@kandn',
            'password' => Hash::make('Pruonh123'),
            'is_admin' => true,
        ]);

        User::create([
            'name' => 'Kanha',
            'email' => 'Kanha@kandn',
            'password' => Hash::make('Kanha123'),
            'is_admin' => true,
        ]);

        $categories = [
            ['name' => 'T-Shirts', 'slug' => 't-shirts', 'description' => 'Casual and stylish t-shirts'],
            ['name' => 'Pants', 'slug' => 'pants', 'description' => 'Comfortable pants and jeans'],
            ['name' => 'Dresses', 'slug' => 'dresses', 'description' => 'Elegant dresses for all occasions'],
            ['name' => 'Accessories', 'slug' => 'accessories', 'description' => 'Bags, jewelry and more'],
            ['name' => 'Shoes', 'slug' => 'shoes', 'description' => 'Footwear collection'],
        ];

        foreach ($categories as $cat) {
            Category::create($cat);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = ['Coca-Cola', 'Pepsi', 'Nestle', 'Unilever', 'P&G', 'Kraft Heinz', 'Danone', 'Mars', 'Mondelez', 'Johnson & Johnson'];

        foreach ($brands as $brand) {
            Brand::create([
                'name' => $brand,
                'slug' => Str::slug($brand),
                'status' => 'active',
                'created_by' => 1,
            ]);
        }
    }
}

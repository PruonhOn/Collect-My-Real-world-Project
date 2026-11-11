<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Brand::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $brands = [
            [
                'name' => 'Nike',
                'slug' => 'nike',
                'description' => 'American multinational corporation known for athletic footwear, apparel, and equipment.',
            ],
            [
                'name' => 'Adidas',
                'slug' => 'adidas',
                'description' => 'German multinational corporation specializing in sportswear and footwear.',
            ],
            [
                'name' => 'Zara',
                'slug' => 'zara',
                'description' => 'Spanish fast-fashion retailer known for trendy clothing and accessories.',
            ],
            [
                'name' => 'H&M',
                'slug' => 'hm',
                'description' => 'Swedish multinational fast-fashion brand offering affordable clothing for all ages.',
            ],
            [
                'name' => 'Uniqlo',
                'slug' => 'uniqlo',
                'description' => 'Japanese casual wear brand known for high-quality basics and innovative fabrics.',
            ],
            [
                'name' => "Levi's",
                'slug' => 'levis',
                'description' => 'American brand globally known for denim jeans and casual apparel.',
            ],
            [
                'name' => 'Puma',
                'slug' => 'puma',
                'description' => 'German multinational company producing athletic and casual footwear, apparel, and accessories.',
            ],
            [
                'name' => 'Gucci',
                'slug' => 'gucci',
                'description' => 'Italian luxury fashion house known for high-end clothing, handbags, and accessories.',
            ],
        ];

        foreach ($brands as $brand) {
            Brand::create($brand);
        }
    }
}

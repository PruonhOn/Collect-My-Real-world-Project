<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'name' => fake()->words(3, true),
            'sku' => Str::upper(Str::random(8)),
            'description' => fake()->sentence(),
            'price' => random_int(1000, 100000),
            'cost_price' => random_int(500, 50000),
            'status' => 'active',
            'track_inventory' => false,
            'stock_quantity' => random_int(0, 200),
            'low_stock_threshold' => 10,
        ];
    }
}

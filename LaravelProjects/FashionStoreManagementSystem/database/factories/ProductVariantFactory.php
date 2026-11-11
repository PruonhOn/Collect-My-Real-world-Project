<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductVariantFactory extends Factory
{
    public function definition(): array
    {
        $product = Product::factory();

        return [
            'product_id' => $product,
            'size' => $this->faker->randomElement(['S', 'M', 'L', 'XL']),
            'color' => $this->faker->randomElement(['Black', 'White', 'Navy', 'Beige']),
            'sku_variant' => fn (array $attrs) => 'VAR-'.Str::upper(Str::random(8)),
            'barcode' => fn () => $this->faker->numerify('#############'),
            'stock_quantity' => $this->faker->numberBetween(0, 80),
            'price_override' => null,
        ];
    }
}

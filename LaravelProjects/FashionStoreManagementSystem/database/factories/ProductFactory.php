<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name' => $this->faker->unique()->words(3, true),
            'sku' => 'FST-'.Str::upper(Str::random(6)),
            'description' => $this->faker->sentence(),
            'base_price' => $this->faker->randomFloat(2, 15, 120),
            'cost_price' => function (array $attrs) {
                return round($attrs['base_price'] * 0.55, 2);
            },
            'brand' => $this->faker->randomElement(['Zara', 'H&M', 'Uniqlo', 'Mango', 'Fendi', 'Gucci', 'Nike', 'Adidas']),
            'is_active' => true,
        ];
    }
}

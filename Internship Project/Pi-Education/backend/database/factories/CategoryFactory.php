<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(),
            'status' => Category::STATUS_ACTIVE,
        ];
    }

    public function withTranslation(string $locale = 'en'): static
    {
        return $this->afterCreating(function (Category $category) use ($locale) {
            $category->translations()->create([
                'locale' => $locale,
                'name' => fake()->word(),
            ]);
        });
    }
}

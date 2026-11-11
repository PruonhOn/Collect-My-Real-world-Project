<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Category::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $men = $this->createCategory('Men');
        $women = $this->createCategory('Women');
        $kids = $this->createCategory('Kids');
        $accessories = $this->createCategory('Accessories');
        $footwear = $this->createCategory('Footwear');

        // Men
        $this->createCategory('T-Shirts', $men->id);
        $this->createCategory('Polo Shirts', $men->id);
        $this->createCategory('Pants', $men->id, 'men-pants');
        $this->createCategory('Shorts', $men->id);
        $this->createCategory('Jackets', $men->id, 'men-jackets');
        $this->createCategory('Suits', $men->id, 'men-suits');
        $this->createCategory('Shoes', $men->id, 'men-shoes');
        $this->createCategory('Accessories', $men->id, 'men-accessories');

        // Women
        $this->createCategory('Dresses', $women->id);
        $this->createCategory('Tops', $women->id, 'women-tops');
        $this->createCategory('Skirts', $women->id);
        $this->createCategory('Pants', $women->id, 'women-pants');
        $this->createCategory('Blouses', $women->id);
        $this->createCategory('Jackets', $women->id, 'women-jackets');
        $this->createCategory('Shoes', $women->id, 'women-shoes');
        $this->createCategory('Accessories', $women->id, 'women-accessories');
        $this->createCategory('Handbags', $women->id);

        // Kids -> Boys
        $boys = $this->createCategory('Boys', $kids->id);
        $this->createCategory('T-Shirts', $boys->id, 'boys-t-shirts');
        $this->createCategory('Pants', $boys->id, 'boys-pants');
        $this->createCategory('Shorts', $boys->id, 'boys-shorts');

        // Kids -> Girls
        $girls = $this->createCategory('Girls', $kids->id);
        $this->createCategory('Dresses', $girls->id, 'girls-dresses');
        $this->createCategory('Tops', $girls->id, 'girls-tops');
        $this->createCategory('Skirts', $girls->id, 'girls-skirts');

        // Kids -> Baby
        $baby = $this->createCategory('Baby', $kids->id);
        $this->createCategory('Onesies', $baby->id);
        $this->createCategory('Sleepwear', $baby->id);

        // Accessories
        $this->createCategory('Bags', $accessories->id, 'acc-bags');
        $this->createCategory('Belts', $accessories->id, 'acc-belts');
        $this->createCategory('Hats', $accessories->id, 'acc-hats');
        $this->createCategory('Scarves', $accessories->id, 'acc-scarves');
        $this->createCategory('Watches', $accessories->id, 'acc-watches');
        $this->createCategory('Jewelry', $accessories->id, 'acc-jewelry');
        $this->createCategory('Sunglasses', $accessories->id, 'acc-sunglasses');

        // Footwear
        $this->createCategory('Sneakers', $footwear->id, 'fw-sneakers');
        $this->createCategory('Sandals', $footwear->id, 'fw-sandals');
        $this->createCategory('Boots', $footwear->id, 'fw-boots');
        $this->createCategory('Formal Shoes', $footwear->id, 'fw-formal-shoes');
        $this->createCategory('Slippers', $footwear->id, 'fw-slippers');
    }

    private function createCategory(string $name, ?int $parentId = null, ?string $slug = null): Category
    {
        return Category::create([
            'name' => $name,
            'slug' => $slug ?? str($name)->slug(),
            'parent_id' => $parentId,
            'status' => true,
        ]);
    }
}

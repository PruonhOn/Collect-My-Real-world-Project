<?php

namespace Database\Seeders;

use App\Models\Author;
use Illuminate\Database\Seeder;

class AuthorSeeder extends Seeder
{
    private array $authors = [
        [
            'slug' => 'sok-visal',
            'names' => ['km' => 'សុខ វិសាល', 'en' => 'Sok Visal', 'zh' => '索克·维萨', 'fr' => 'Sok Visal'],
        ],
        [
            'slug' => 'chan-dara',
            'names' => ['km' => 'ចាន់ ដារ៉ា', 'en' => 'Chan Dara', 'zh' => '占·达拉', 'fr' => 'Chan Dara'],
        ],
        [
            'slug' => 'kim-sophea',
            'names' => ['km' => 'គីម សុភា', 'en' => 'Kim Sophea', 'zh' => '金·索菲亚', 'fr' => 'Kim Sophea'],
        ],
        [
            'slug' => 'john-smith',
            'names' => ['km' => 'ចន ស្មីត', 'en' => 'John Smith', 'zh' => '约翰·史密斯', 'fr' => 'John Smith'],
        ],
        [
            'slug' => 'mei-lin',
            'names' => ['km' => 'ម៉ៃ លីន', 'en' => 'Mei Lin', 'zh' => '梅琳', 'fr' => 'Mei Lin'],
        ],
    ];

    public function run(): void
    {
        foreach ($this->authors as $data) {
            $author = Author::updateOrCreate(
                ['slug' => $data['slug']],
                ['slug' => $data['slug']]
            );

            foreach ($data['names'] as $locale => $name) {
                $author->translations()->updateOrCreate(
                    ['locale' => $locale],
                    ['name' => $name]
                );
            }
        }
    }
}

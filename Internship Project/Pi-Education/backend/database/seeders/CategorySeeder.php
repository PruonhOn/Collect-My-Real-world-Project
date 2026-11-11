<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    private array $categories = [
        ['slug' => 'mathematics', 'names' => ['en' => 'Mathematics', 'km' => 'គណិតវិទ្យា', 'zh' => '数学', 'fr' => 'Mathématiques']],
        ['slug' => 'science', 'names' => ['en' => 'Science', 'km' => 'វិទ្យាសាស្ត្រ', 'zh' => '科学', 'fr' => 'Sciences']],
        ['slug' => 'programming', 'names' => ['en' => 'Programming', 'km' => 'កម្មវិធីកុំព្យូទ័រ', 'zh' => '编程', 'fr' => 'Programmation']],
        ['slug' => 'technology', 'names' => ['en' => 'Technology', 'km' => 'បច្ចេកវិទ្យា', 'zh' => '技术', 'fr' => 'Technologie']],
        ['slug' => 'business', 'names' => ['en' => 'Business', 'km' => 'អាជីវកម្ម', 'zh' => '商业', 'fr' => 'Commerce']],
        ['slug' => 'languages', 'names' => ['en' => 'Languages', 'km' => 'ភាសា', 'zh' => '语言', 'fr' => 'Langues']],
        ['slug' => 'history', 'names' => ['en' => 'History', 'km' => 'ប្រវត្តិសាស្ត្រ', 'zh' => '历史', 'fr' => 'Histoire']],
        ['slug' => 'literature', 'names' => ['en' => 'Literature', 'km' => 'អក្សរសិល្ប៍', 'zh' => '文学', 'fr' => 'Littérature']],
    ];

    public function run(): void
    {
        foreach ($this->categories as $data) {
            $category = Category::updateOrCreate(
                ['slug' => $data['slug']],
                ['slug' => $data['slug'], 'status' => Category::STATUS_ACTIVE]
            );

            foreach ($data['names'] as $locale => $name) {
                $category->translations()->updateOrCreate(
                    ['locale' => $locale],
                    ['name' => $name]
                );
            }
        }
    }
}

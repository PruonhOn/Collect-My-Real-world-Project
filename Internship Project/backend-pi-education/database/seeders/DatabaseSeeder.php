<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $reader = User::factory()->create([
            'name' => 'Reader',
            'email' => 'reader@example.com',
            'password' => 'password',
        ]);

        $this->seedCategoriesAndBooks();
        $this->seedCourses();
    }

    private function seedCategoriesAndBooks(): void
    {
        $categories = collect([
            ['name' => 'Literature', 'description' => 'អក្សរសិល្ប៍ ➜ Novels, poetry and literary works.'],
            ['name' => 'Education', 'description' => 'ការអប់រំ ➜ Textbooks and learning materials.'],
            ['name' => 'History', 'description' => 'ប្រវត្តិសាស្ត្រ ➜ Cambodian and world history.'],
            ['name' => 'Technology', 'description' => 'បច្ចេកវិទ្យា ➜ Programming and digital skills.'],
            ['name' => 'Business', 'description' => 'អាជីវកម្ម ➜ Economics and entrepreneurship.'],
            ['name' => 'Health', 'description' => 'សុខភាព ➜ Wellness and medicine.'],
        ])->map(fn ($c) => Category::create([
            'name' => $c['name'],
            'slug' => strtolower($c['name']).'-'.substr(md5($c['name']), 0, 6),
            'description' => $c['description'],
        ]));

        $categories->each(function (Category $category) {
            Book::factory()
                ->count(4)
                ->state(['category_id' => $category->id])
                ->create([
                    'file_path' => 'books/km/sample-book.pdf',
                ]);

            Book::factory()
                ->free()
                ->count(1)
                ->state(['category_id' => $category->id])
                ->create([
                    'file_path' => 'books/km/sample-book-pages12.pdf',
                    'pages' => 12,
                ]);
        });
    }

    private function seedCourses(): void
    {
        $course = Course::create([
            'title' => 'Introduction to Khmer Literature',
            'slug' => 'intro-khmer-literature-'.substr(md5('khmer'), 0, 6),
            'description' => 'A comprehensive course on Khmer classical and modern literature.',
            'price' => 19.99,
            'is_published' => true,
        ]);

        foreach ([
            'Origins of Khmer Writing',
            'Classical Poetry',
            'Modern Khmer Novels',
            'Literary Analysis',
        ] as $i => $title) {
            CourseLesson::create([
                'course_id' => $course->id,
                'title' => $title,
                'description' => 'Lesson covering: '.$title,
                'duration' => 300 + ($i + 1) * 60,
                'position' => $i + 1,
            ]);
        }

        Course::create([
            'title' => 'Digital Literacy for Students',
            'slug' => 'digital-literacy-'.substr(md5('digital'), 0, 6),
            'description' => 'Essential digital skills for modern students.',
            'price' => 0,
            'is_published' => true,
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            AdminUserSeeder::class,
            CategorySeeder::class,
            AuthorSeeder::class,
            SampleBookSeeder::class,
            MySpecialBooksSeeder::class,
        ]);
    }
}

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'reader']);
    }
}

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@pi-education.com'],
            [
                'name' => 'Administrator',
                'phone' => '012345678',
                'password' => bcrypt('password123'),
                'is_active' => true,
            ]
        );
        $admin->assignRole('admin');

        $reader = User::firstOrCreate(
            ['email' => 'reader@pi-education.com'],
            [
                'name' => 'Demo Reader',
                'phone' => '098765432',
                'password' => bcrypt('password123'),
                'is_active' => true,
            ]
        );
        $reader->assignRole('reader');
    }
}

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'slug' => 'math',
                'icon' => 'Σ',
                'names' => ['km' => 'គណិតវិទ្យា', 'en' => 'Mathematics'],
                'descriptions' => [
                    'km' => 'សៀវភៅគណិតវិទ្យាគ្រប់កម្រិត',
                    'en' => 'Mathematics books for all levels',
                ],
            ],
            [
                'slug' => 'khmer-literature',
                'icon' => 'អ',
                'names' => ['km' => 'អក្សរសិល្ប៍ខ្មែរ', 'en' => 'Khmer Literature'],
                'descriptions' => [
                    'km' => 'សៀវភៅអក្សរសិល្ប៍ខ្មែរបុរាណ និងទំនើប',
                    'en' => 'Classic and modern Khmer literature',
                ],
            ],
            [
                'slug' => 'science',
                'icon' => '🔬',
                'names' => ['km' => 'វិទ្យាសាស្ត្រ', 'en' => 'Science'],
                'descriptions' => [
                    'km' => 'សៀវភៅវិទ្យាសាស្ត្រ ជីវវិទ្យា គីមីវិទ្យា រូបវិទ្យា',
                    'en' => 'Science books: biology, chemistry, physics',
                ],
            ],
            [
                'slug' => 'history',
                'icon' => '🏛️',
                'names' => ['km' => 'ប្រវត្តិវិទ្យា', 'en' => 'History'],
                'descriptions' => [
                    'km' => 'ប្រវត្តិសាស្ត្រខ្មែរ និងពិភពលោក',
                    'en' => 'Khmer and world history',
                ],
            ],
            [
                'slug' => 'language-learning',
                'icon' => '🗣️',
                'names' => ['km' => 'ការរៀនភាសា', 'en' => 'Language Learning'],
                'descriptions' => [
                    'km' => 'សៀវភៅរៀនភាសាអង់គ្លេស បារាំង ចិន និងភាសាដទៃទៀត',
                    'en' => 'Learn English, French, Chinese and other languages',
                ],
            ],
            [
                'slug' => 'computer-science',
                'icon' => '💻',
                'names' => ['km' => 'វិទ្យាសាស្ត្រកុំព្យូទ័រ', 'en' => 'Computer Science'],
                'descriptions' => [
                    'km' => 'សៀវភៅកម្មវិធីកុំព្យូទ័រ បច្ចេកវិទ្យា IT',
                    'en' => 'Programming, IT and technology books',
                ],
            ],
            [
                'slug' => 'business',
                'icon' => '💼',
                'names' => ['km' => 'ជំនួញ', 'en' => 'Business'],
                'descriptions' => [
                    'km' => 'សៀវភៅជំនួញ សេដ្ឋកិច្ច និងគ្រប់គ្រង',
                    'en' => 'Business, economics and management books',
                ],
            ],
            [
                'slug' => 'kids',
                'icon' => '🧒',
                'names' => ['km' => 'សៀវភៅកុមារ', 'en' => 'Children Books'],
                'descriptions' => [
                    'km' => 'សៀវភៅរឿងនិទាន និងអប់រំសម្រាប់កុមារ',
                    'en' => 'Story books and education for children',
                ],
            ],
        ];

        foreach ($categories as $data) {
            $category = Category::firstOrCreate(
                ['slug' => $data['slug']],
                ['icon' => $data['icon'], 'is_active' => true]
            );

            foreach ($data['names'] as $locale => $name) {
                \App\Models\CategoryTranslation::firstOrCreate(
                    ['category_id' => $category->id, 'locale' => $locale],
                    [
                        'name' => $name,
                        'description' => $data['descriptions'][$locale] ?? null,
                    ]
                );
            }
        }
    }
}

class AuthorSeeder extends Seeder
{
    public function run(): void
    {
        $authors = [
            [
                'slug' => 'sok-sovann',
                'names' => ['km' => 'សុខ សុវណ្ណ', 'en' => 'Sok Sovann'],
                'bios' => [
                    'km' => 'គ្រូបង្រៀនគណិតវិទ្យាដែលមានបទពិសោធន៍ ១៥ឆ្នាំ',
                    'en' => 'Mathematics teacher with 15 years of experience',
                ],
            ],
            [
                'slug' => 'kim-sreypov',
                'names' => ['km' => 'គឹម ស្រីពៅ', 'en' => 'Kim Sreypov'],
                'bios' => [
                    'km' => 'អ្នកនិពន្ធរឿងខ្លី និងប្រលោមលោកខ្មែរ',
                    'en' => 'Khmer short story and novel writer',
                ],
            ],
            [
                'slug' => 'david-brown',
                'names' => ['km' => 'ដេវីដ ប្រោន', 'en' => 'David Brown'],
                'bios' => [
                    'km' => 'អ្នកជំនាញវិទ្យាសាស្ត្រកុំព្យូទ័រអន្តរជាតិ',
                    'en' => 'International computer science expert',
                ],
            ],
        ];

        foreach ($authors as $data) {
            $author = Author::firstOrCreate(
                ['slug' => $data['slug']],
                []
            );

            foreach ($data['names'] as $locale => $name) {
                \App\Models\AuthorTranslation::firstOrCreate(
                    ['author_id' => $author->id, 'locale' => $locale],
                    ['name' => $name, 'bio' => $data['bios'][$locale] ?? null]
                );
            }
        }
    }
}

class SampleBookSeeder extends Seeder
{
    public function run(): void
    {
        $mathCat = Category::where('slug', 'math')->first();
        $litCat = Category::where('slug', 'khmer-literature')->first();
        $csCat = Category::where('slug', 'computer-science')->first();
        $scienceCat = Category::where('slug', 'science')->first();

        $mathAuthor = Author::where('slug', 'sok-sovann')->first();
        $litAuthor = Author::where('slug', 'kim-sreypov')->first();
        $csAuthor = Author::where('slug', 'david-brown')->first();

        $books = [
            [
                'slug' => 'khmer-mathematics-grade-9',
                'category_id' => $mathCat?->id,
                'author_id' => $mathAuthor?->id,
                'price' => 0,
                'is_free' => true,
                'is_published' => true,
                'pages' => 220,
                'titles' => [
                    'km' => 'គណិតវិទ្យាថ្នាក់ទី៩',
                    'en' => 'Khmer Mathematics Grade 9',
                ],
                'descriptions' => [
                    'km' => 'សៀវភៅគណិតវិទ្យាសម្រាប់សិស្សថ្នាក់ទី៩ គ្របដណ្តប់ពិជណិតធរណីមាត្រ និងស្ថិតិ',
                    'en' => 'Mathematics textbook for Grade 9 students covering algebra, geometry and statistics',
                ],
            ],
            [
                'slug' => 'calculus-basics',
                'category_id' => $mathCat?->id,
                'author_id' => $mathAuthor?->id,
                'price' => 9.99,
                'is_free' => false,
                'is_published' => true,
                'pages' => 350,
                'titles' => [
                    'km' => 'មូលដ្ឋានគ្រឹះកាលគូលុស',
                    'en' => 'Calculus Basics',
                ],
                'descriptions' => [
                    'km' => 'សៀវភៅរៀនកាលគូលុស ពីដេរីវេ រហូតដល់អាំងតេក្រាល',
                    'en' => 'Calculus book from derivatives to integrals',
                ],
            ],
            [
                'slug' => 'khmer-classic-novels',
                'category_id' => $litCat?->id,
                'author_id' => $litAuthor?->id,
                'price' => 0,
                'is_free' => true,
                'is_published' => true,
                'pages' => 180,
                'titles' => [
                    'km' => 'ប្រលោមលោកខ្មែរបុរាណ',
                    'en' => 'Classic Khmer Novels',
                ],
                'descriptions' => [
                    'km' => 'បណ្តុំប្រលោមលោកខ្មែរបុរាណល្បីៗ',
                    'en' => 'Collection of famous classic Khmer novels',
                ],
            ],
            [
                'slug' => 'intro-to-programming',
                'category_id' => $csCat?->id,
                'author_id' => $csAuthor?->id,
                'price' => 14.99,
                'is_free' => false,
                'is_published' => true,
                'pages' => 420,
                'titles' => [
                    'km' => 'សេចក្តីផ្តើមកម្មវិធីកុំព្យូទ័រ',
                    'en' => 'Introduction to Programming',
                ],
                'descriptions' => [
                    'km' => 'រៀនសរសេរកូដពីដំបូង គ្របដណ្តប់ Python JavaScript និង SQL',
                    'en' => 'Learn coding from scratch covering Python, JavaScript and SQL',
                ],
            ],
            [
                'slug' => 'biology-fundamentals',
                'category_id' => $scienceCat?->id,
                'author_id' => null,
                'price' => 0,
                'is_free' => true,
                'is_published' => true,
                'pages' => 300,
                'titles' => [
                    'km' => 'មូលដ្ឋានគ្រឹះជីវវិទ្យា',
                    'en' => 'Biology Fundamentals',
                ],
                'descriptions' => [
                    'km' => 'សៀវភៅជីវវិទ្យាមូលដ្ឋានសម្រាប់សិស្សវិទ្យាល័យ',
                    'en' => 'Basic biology textbook for high school students',
                ],
            ],
            [
                'slug' => 'physics-in-daily-life',
                'category_id' => $scienceCat?->id,
                'author_id' => null,
                'price' => 7.99,
                'is_free' => false,
                'is_published' => false,
                'pages' => 260,
                'titles' => [
                    'km' => 'រូបវិទ្យាក្នុងជីវិតប្រចាំថ្ងៃ',
                    'en' => 'Physics in Daily Life',
                ],
                'descriptions' => [
                    'km' => 'រូបវិទ្យាគួរឱ្យចាប់អារម្មណ៍ ភ្ជាប់ជាមួយជីវិតប្រចាំថ្ងៃ',
                    'en' => 'Interesting physics connected to everyday life',
                ],
            ],
        ];

        foreach ($books as $data) {
            $book = Book::firstOrCreate(
                ['slug' => $data['slug']],
                [
                    'category_id' => $data['category_id'],
                    'author_id' => $data['author_id'],
                    'price' => $data['price'],
                    'is_free' => $data['is_free'],
                    'is_published' => $data['is_published'],
                    'pages' => $data['pages'],
                    'file_type' => 'pdf',
                    'file_size' => 0,
                    'cover_image' => 'books/covers/'.$data['slug'].'.jpg',
                    'views_count' => rand(10, 500),
                    'downloads_count' => rand(1, 100),
                ]
            );

            foreach ($data['titles'] as $locale => $title) {
                \App\Models\BookTranslation::firstOrCreate(
                    ['book_id' => $book->id, 'locale' => $locale],
                    [
                        'title' => $title,
                        'description' => $data['descriptions'][$locale] ?? null,
                        'content' => null,
                    ]
                );
            }
        }
    }
}
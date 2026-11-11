<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    private array $books = [
        [
            'slug' => 'algebra-basics',
            'type' => 'FREE',
            'price' => 0,
            'category' => 'mathematics',
            'authors' => ['sok-visal'],
            'language' => 'km',
            'translations' => [
                'en' => ['title' => 'Algebra Basics', 'description' => 'Learn the fundamentals of algebra step by step.', 'meta_title' => 'Algebra Basics - Free Book', 'meta_description' => 'A free introduction to algebra for beginners.'],
                'km' => ['title' => 'មូលដ្ឋានគ្រឹះពិជគណិត', 'description' => 'រៀនពីមូលដ្ឋានគ្រឹះនៃពិជគណិតជំហានទៅជំហាន។', 'meta_title' => 'មូលដ្ឋានគ្រឹះពិជគណិត', 'meta_description' => 'ការណែនាំដោយឥតគិតថ្លៃអំពីពិជគណិតសម្រាប់អ្នកចាប់ផ្តើមដំបូង។'],
                'zh' => ['title' => '代数基础', 'description' => '逐步学习代数的基本原理。', 'meta_title' => '代数基础', 'meta_description' => '面向初学者的免费代数入门。'],
                'fr' => ['title' => 'Bases de l\'algèbre', 'description' => 'Apprenez les fondamentaux de l\'algèbre pas à pas.', 'meta_title' => 'Bases de l\'algèbre', 'meta_description' => 'Une introduction gratuite à l\'algèbre pour débutants.'],
            ],
        ],
        [
            'slug' => 'introduction-to-programming',
            'type' => 'FREE',
            'price' => 0,
            'category' => 'programming',
            'authors' => ['john-smith'],
            'language' => 'en',
            'translations' => [
                'en' => ['title' => 'Introduction to Programming', 'description' => 'Start your coding journey with clear, practical examples.', 'meta_title' => 'Introduction to Programming - Free', 'meta_description' => 'A free hands-on introduction to programming.'],
                'km' => ['title' => 'សេចក្តីផ្តើមកម្មវិធីកុំព្យូទ័រ', 'description' => 'ចាប់ផ្តើមដំណើរការសរសេរកូដរបស់អ្នកជាមួយឧទាហរណ៍ជាក់ស្តែង។', 'meta_title' => 'សេចក្តីផ្តើមកម្មវិធីកុំព្យូទ័រ', 'meta_description' => 'ការណែនាំដោយឥតគិតថ្លៃ និងអនុវត្តជាក់ស្តែងអំពីកម្មវិធីកុំព្យូទ័រ។'],
                'zh' => ['title' => '编程入门', 'description' => '通过清晰实用的示例开始你的编程之旅。', 'meta_title' => '编程入门', 'meta_description' => '免费的编程实践入门。'],
                'fr' => ['title' => 'Introduction à la programmation', 'description' => 'Commencez votre parcours de codage avec des exemples concrets.', 'meta_title' => 'Introduction à la programmation', 'meta_description' => 'Une introduction pratique gratuite à la programmation.'],
            ],
        ],
        [
            'slug' => 'khmer-history',
            'type' => 'FREE',
            'price' => 0,
            'category' => 'history',
            'authors' => ['chan-dara'],
            'language' => 'km',
            'translations' => [
                'en' => ['title' => 'Khmer History', 'description' => 'Explore the rich history of the Khmer civilization.', 'meta_title' => 'Khmer History - Free Book', 'meta_description' => 'A free book on the history of the Khmer civilization.'],
                'km' => ['title' => 'ប្រវត្តិសាស្ត្រខ្មែរ', 'description' => 'ស្វែងយល់ពីប្រវត្តិសាស្ត្រដ៏សម្បូរបែបនៃអរិយធម៌ខ្មែរ។', 'meta_title' => 'ប្រវត្តិសាស្ត្រខ្មែរ', 'meta_description' => 'សៀវភៅឥតគិតថ្លៃស្តីពីប្រវត្តិសាស្ត្រអរិយធម៌ខ្មែរ។'],
                'zh' => ['title' => '高棉历史', 'description' => '探索高棉文明的丰富历史。', 'meta_title' => '高棉历史', 'meta_description' => '关于高棉文明历史的免费图书。'],
                'fr' => ['title' => 'Histoire khmère', 'description' => 'Explorez la riche histoire de la civilisation khmère.', 'meta_title' => 'Histoire khmère', 'meta_description' => 'Un livre gratuit sur l\'histoire de la civilisation khmère.'],
            ],
        ],
        [
            'slug' => 'web-development-masterclass',
            'type' => 'PAID',
            'price' => 9.99,
            'category' => 'technology',
            'authors' => ['mei-lin'],
            'language' => 'en',
            'translations' => [
                'en' => ['title' => 'Web Development Masterclass', 'description' => 'Build modern, responsive websites from scratch.', 'meta_title' => 'Web Development Masterclass', 'meta_description' => 'A complete paid course on building modern websites.'],
                'km' => ['title' => 'ថ្នាក់អនុបណ្ឌិតការអភិវឌ្ឍវេបសាយ', 'description' => 'បង្កើតវេបសាយទំនើបដែលឆ្លើយតបបានពីដំបូង។', 'meta_title' => 'ថ្នាក់អនុបណ្ឌិតវេប', 'meta_description' => 'វគ្គសិក្សាបង់ប្រាក់ពេញលេញស្តីពីការបង្កើតវេបសាយទំនើប។'],
                'zh' => ['title' => '网页开发大师课', 'description' => '从零开始构建现代响应式网站。', 'meta_title' => '网页开发大师课', 'meta_description' => '关于构建现代网站的完整付费课程。'],
                'fr' => ['title' => 'Cours complet de développement web', 'description' => 'Créez des sites web modernes et responsives de zéro.', 'meta_title' => 'Cours complet de développement web', 'meta_description' => 'Un cours payant complet pour créer des sites modernes.'],
            ],
        ],
        [
            'slug' => 'english-for-beginners',
            'type' => 'PAID',
            'price' => 5.50,
            'category' => 'languages',
            'authors' => ['kim-sophea'],
            'language' => 'en',
            'translations' => [
                'en' => ['title' => 'English for Beginners', 'description' => 'A friendly course to start speaking English confidently.', 'meta_title' => 'English for Beginners', 'meta_description' => 'A paid course for beginners learning English.'],
                'km' => ['title' => 'ភាសាអង់គ្លេសសម្រាប់អ្នកចាប់ផ្តើម', 'description' => 'វគ្គសិក្សាមិត្តភាពដើម្បីចាប់ផ្តើមនិយាយភាសាអង់គ្លេសប្រកបដោយទំនុកចិត្ត។', 'meta_title' => 'ភាសាអង់គ្លេសសម្រាប់អ្នកចាប់ផ្តើម', 'meta_description' => 'វគ្គសិក្សាបង់ប្រាក់សម្រាប់អ្នកចាប់ផ្តើមរៀនភាសាអង់គ្លេស។'],
                'zh' => ['title' => '英语入门', 'description' => '一门友好的课程，帮助你自信地开始说英语。', 'meta_title' => '英语入门', 'meta_description' => '面向初学者的英语付费课程。'],
                'fr' => ['title' => 'Anglais pour débutants', 'description' => 'Un cours convivial pour commencer à parler anglais avec confiance.', 'meta_title' => 'Anglais pour débutants', 'meta_description' => 'Un cours payant pour les débutants en anglais.'],
            ],
        ],
        [
            'slug' => 'business-math',
            'type' => 'PAID',
            'price' => 7.25,
            'category' => 'business',
            'authors' => ['sok-visal', 'chan-dara'],
            'language' => 'km',
            'translations' => [
                'en' => ['title' => 'Business Math', 'description' => 'Practical mathematics for everyday business decisions.', 'meta_title' => 'Business Math', 'meta_description' => 'A paid guide to business mathematics.'],
                'km' => ['title' => 'គណិតវិទ្យាអាជីវកម្ម', 'description' => 'គណិតវិទ្យាអនុវត្តសម្រាប់ការសម្រេចចិត្តអាជីវកម្មប្រចាំថ្ងៃ។', 'meta_title' => 'គណិតវិទ្យាអាជីវកម្ម', 'meta_description' => 'ការណែនាំបង់ប្រាក់អំពីគណិតវិទ្យាអាជីវកម្ម។'],
                'zh' => ['title' => '商业数学', 'description' => '日常商业决策的实用数学。', 'meta_title' => '商业数学', 'meta_description' => '商业数学付费指南。'],
                'fr' => ['title' => 'Maths commerciales', 'description' => 'Les mathématiques pratiques pour les décisions commerciales quotidiennes.', 'meta_title' => 'Maths commerciales', 'meta_description' => 'Un guide payant de mathématiques commerciales.'],
            ],
        ],
    ];

    public function run(): void
    {
        foreach ($this->books as $data) {
            $book = Book::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'slug' => $data['slug'],
                    'type' => $data['type'],
                    'price' => $data['price'],
                    'status' => 'PUBLISHED',
                    'language' => $data['language'],
                    'published_at' => now()->subDays(random_int(1, 120)),
                    'reader_count' => random_int(10, 5000),
                ]
            );

            foreach ($data['translations'] as $locale => $translation) {
                $content = $this->generateContent($translation['title'], $locale);
                $book->translations()->updateOrCreate(
                    ['locale' => $locale],
                    [
                        'title' => $translation['title'],
                        'description' => $translation['description'],
                        'content' => $content,
                        'meta_title' => $translation['meta_title'],
                        'meta_description' => $translation['meta_description'],
                    ]
                );
            }

            $category = Category::where('slug', $data['category'])->first();
            if ($category) {
                $book->categories()->syncWithoutDetaching([$category->id]);
            }

            $authorIds = Author::whereIn('slug', $data['authors'])->pluck('id');
            $book->authors()->sync($authorIds);
        }
    }

    private function generateContent(string $title, string $locale): string
    {
        $chapterLabels = [
            'en' => ['Chapter 1', 'Chapter 2', 'Chapter 3'],
            'km' => ['ជំពូកទី ១', 'ជំពូកទី ២', 'ជំពូកទី ៣'],
            'zh' => ['第一章', '第二章', '第三章'],
            'fr' => ['Chapitre 1', 'Chapitre 2', 'Chapitre 3'],
        ];

        $labels = $chapterLabels[$locale] ?? $chapterLabels['en'];
        $summary = $this->summarySentence($locale);

        $content = '';
        foreach ($labels as $i => $label) {
            $content .= "## {$label}\n\n";
            $content .= "{$summary}\n\n";
            for ($p = 1; $p <= 3; $p++) {
                $content .= "{$this->paragraphSentence($locale)} {$this->paragraphSentence($locale)} {$this->paragraphSentence($locale)}\n\n";
            }
        }

        return $content;
    }

    private function summarySentence(string $locale): string
    {
        return match ($locale) {
            'km' => 'ជំពូកនេះណែនាំអំពីគោលគំនិតសំខាន់ៗដែលអ្នកនឹងត្រូវការ។',
            'zh' => '本章介绍你将需要的关键概念。',
            'fr' => 'Ce chapitre introduit les concepts clés dont vous aurez besoin.',
            default => 'This chapter introduces the key concepts you will need.',
        };
    }

    private function paragraphSentence(string $locale): string
    {
        return match ($locale) {
            'km' => 'ឧទាហរណ៍ និងលំហាត់ជាក់ស្តែងត្រូវបានរួមបញ្ចូលដើម្បីជួយអ្នកយល់ច្បាស់ ការអនុវត្តជាបន្តបន្ទាប់នឹងធ្វើឱ្យការរៀនរបស់អ្នកកាន់តែមានប្រសិទ្ធភាព។',
            'zh' => '其中包括示例和实践练习，帮助你更好地理解。持续的练习将使你的学习更有效。',
            'fr' => 'Des exemples et des exercices pratiques sont inclus pour vous aider à bien comprendre. Une pratique régulière rendra votre apprentissage plus efficace.',
            default => 'Examples and practical exercises are included to help you understand clearly. Consistent practice will make your learning more effective.',
        };
    }
}

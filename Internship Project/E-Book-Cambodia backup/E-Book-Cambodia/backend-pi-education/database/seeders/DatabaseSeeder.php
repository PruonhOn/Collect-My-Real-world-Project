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
        $this->call(VipPlanSeeder::class);
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
            ['name' => 'Math', 'description' => 'គណិតវិទ្យា ➜ Mathematics books for all levels.'],
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

        $historyCategory = $categories->firstWhere('name', 'History');

        Book::updateOrCreate(
            ['slug' => 'khmer-history-free'],
            [
                'category_id' => $historyCategory->id,
                'title' => 'ប្រវត្តិសាស្រ្តខ្មែរ',
                'description' => 'សៀវភៅឥតគិតថ្លៃស្តីពីប្រវត្តិសាស្រ្តខ្មែរ។',
                'author' => 'មិនស្គាល់',
                'file_path' => 'books/km/khmer-history.pdf',
                'file_type' => 'application/pdf',
                'file_size' => filesize(storage_path('app/private/books/km/khmer-history.pdf')) ?: 0,
                'language' => 'km',
                'pages' => 9,
                'price' => 0,
                'is_free' => true,
                'is_published' => true,
            ]
        );

        $this->seedMathBooks($categories);
    }

    private function seedMathBooks($categories): void
    {
        $mathCategory = $categories->firstWhere('name', 'Math');

        $books = [
            [
                'title' => 'គណិតវិទ្យាមូលដ្ឋាន',
                'slug' => 'basic-arithmetic-km',
                'description' => 'សៀវភៅស្តីពីមូលដ្ឋានគ្រឹះគណិតវិទ្យា។ រួមមានការបូក ការដក ការគុណ និងការចែក។',
                'author' => 'សុវណ្ណ រតនៈ',
                'content' => $this->buildMathContent(
                    ['មេរៀនទី ១ - ការបូកលេខ', 'មេរៀនទី ២ - ការដកលេខ', 'មេរៀនទី ៣ - ការគុណលេខ', 'មេរៀនទី ៤ - ការចែកលេខ', 'មេរៀនទី ៥ - លំដាប់ប្រមាណវិធី', 'មេរៀនទី ៦ - លេខពិរប្រាកដ', 'មេរៀនទី ៧ - ភាគលេខ', 'មេរៀនទី ៨ - ស្មើភាព', 'មេរៀនទី ៩ - បញ្ហាអនុវត្តន៍', 'មេរៀនទី ១០ - សង្ខេបនិងពិនិត្យឡើងវិញ'],
                    ['Addition is the process of combining two or more numbers to find their total. When we add 5 and 3, we get 8. This fundamental operation forms the basis of all mathematics.', 'Subtraction means taking one number away from another. If you have 10 apples and give away 3, you have 7 left. Understanding subtraction helps us solve many real-world problems.', 'Multiplication is repeated addition. Instead of adding 4 three times, we simply write 4 times 3 equals 12. Mastering multiplication tables is essential for quick calculations.', 'Division splits a number into equal groups. Sharing 12 cookies among 4 friends means each friend gets 3. Division is the inverse operation of multiplication.', 'The order of operations tells us which calculation to do first. Remember PEMDAS: Parentheses, Exponents, Multiplication, Division, Addition, and Subtraction.', 'Rounding numbers makes them easier to work with. We round to the nearest ten, hundred, or thousand based on the digit to the right of our target place value.', 'Fractions represent parts of a whole. The numerator shows how many parts we have, while the denominator shows how many equal parts the whole is divided into.', 'An equation is a mathematical statement that two expressions are equal. The equals sign means the value on the left side is the same as the value on the right side.', 'Word problems require us to translate real situations into mathematical expressions. Read carefully, identify what is given, and determine what needs to be found.', 'Reviewing all concepts strengthens our understanding. Practice regularly, check your work, and do not hesitate to ask questions when something is unclear.'],
                    10
                ),
                'pages' => 10,
            ],
            [
                'title' => 'ធរណីមាត្រសម្រាប់អ្នករៀន',
                'slug' => 'geometry-for-beginners',
                'description' => 'សៀវភៅស្តីពីធរណីមាត្រមូលដ្ឋាន។ ស្វែងយល់ពីរូបភាព មុំ និងផ្ទៃក្រឡា។',
                'author' => 'ចាន់ សុភ័ក្ត្រ',
                'content' => $this->buildMathContent(
                    ['មេរៀនទី ១ - រូបភាពធរណីមាត្រ', 'មេរៀនទី ២ - មុំនិងប្រភេទមុំ', 'មេរៀនទី ៣ - ប្រវែងនិងសមាមាត្រ', 'មេរៀនទី ៤ - ផ្ទៃក្រឡារូបភាព', 'មេរៀនទី ៥ - ទំហំរូបភាពស៊ុម', 'មេរៀនទី ៦ - តែងប្រវែងស្មើគ្នា', 'មេរៀនទី ៧ - សមាមាត្រនិងស្រដៀងគ្នា', 'មេរៀនទី ៨ - អត្រាសមាមាត្រ', 'មេរៀនទី ៩ - បញ្ហាអនុវត្តន៍ធរណីមាត្រ', 'មេរៀនទី ១០ - សង្ខេបនិងពិនិត្យឡើងវិញ'],
                    ['Geometry is the branch of mathematics that deals with shapes, sizes, and positions of figures. We encounter geometric shapes everywhere in our daily lives.', 'Angles are formed when two lines meet at a point. There are acute angles less than 90 degrees, right angles of exactly 90 degrees, and obtuse angles greater than 90 degrees.', 'Perimeter is the total distance around a shape. To find the perimeter of a rectangle, we add all four sides or use the formula two times the length plus two times the width.', 'Area measures the surface covered by a shape. A rectangle area equals length times width. A triangle area equals one-half times base times height.', 'The area of a frame is found by subtracting the inner area from the outer area. This technique helps solve practical problems involving borders and margins.', 'Equal sides define regular shapes. A square has four equal sides and four right angles. An equilateral triangle has three equal sides and three equal angles.', 'Similar shapes have the same shape but different sizes. Their corresponding angles are equal and their sides are proportional to each other.', 'A ratio compares two quantities. When two ratios are equal, they form a proportion. Cross multiplication helps us solve for unknown values in proportions.', 'Real-world geometry problems often involve finding areas, perimeters, or unknown measurements in buildings, gardens, and everyday objects.', 'Reviewing geometric concepts helps us apply them confidently. Practice identifying shapes, calculating areas, and solving real-life geometry problems regularly.'],
                    10
                ),
                'pages' => 10,
            ],
            [
                'title' => 'អាល់gebra សម្រាប់កម្រិតទី៧',
                'slug' => 'algebra-grade-7',
                'description' => 'សៀវភៅអាល់gebraសម្រាប់សិស្សថ្នាក់ទី៧។ ស្វែងយល់ពីអថេរ សមីកា និងសមីការអាល់gebra។',
                'author' => 'លីម វិសាល',
                'content' => $this->buildMathContent(
                    ['មេរៀនទី ១ - អថេរនិងកន្សោមអថេរ', 'មេរៀនទី ២ - សមីការមូលដ្ឋាន', 'មេរៀនទី ៣ - សមីការដែលមានច្រើនជំពូក', 'មេរៀនទី ៤ - សមីការអឌ្ឈចំនួន', 'មេរៀនទី ៥ - កន្សោមអាល់gebra', 'មេរៀនទី ៦ - ការដោះស្រាយសមីការ', 'មេរៀនទី ៧ - បញ្ហាអនុវត្តន៍អាល់gebra', 'មេរៀនទី ៨ - លំនាំនិងសមីការ', 'មេរៀនទី ៩ - ប្រព័ន្ធសមីការ', 'មេរៀនទី ១០ - សង្ខេបនិងពិនិត្យឡើងវិញ'],
                    ['A variable is a letter that represents an unknown number. In algebra, we use letters like x and y to stand for values we want to find or work with.', 'A basic equation has an unknown on one side and a known value on the other. To solve x plus 5 equals 12, we subtract 5 from both sides to get x equals 7.', 'Equations with multiple terms require combining like terms first. Simplify both sides before isolating the variable to make solving easier and more accurate.', 'Linear equations have variables raised to the power of one. They graph as straight lines and represent constant rates of change in real-world situations.', 'An algebraic expression combines numbers, variables, and operations. The expression 3x plus 2 means three times a number plus two.', 'Solving equations means finding the value of the variable that makes the equation true. Always perform the same operation on both sides to maintain balance.', 'Algebra helps us solve practical problems like calculating costs, finding distances, and determining unknown measurements in science and daily life.', 'Patterns in mathematics can be described using algebraic expressions. Recognizing patterns helps us predict future values and understand relationships between quantities.', 'A system of equations involves two or more equations with the same variables. We can solve them using substitution or elimination methods.', 'Reviewing algebra fundamentals builds a strong foundation for higher mathematics. Practice solving different types of equations and applying algebra to real problems.'],
                    10
                ),
                'pages' => 10,
            ],
        ];

        foreach ($books as $bookData) {
            Book::updateOrCreate(
                ['slug' => $bookData['slug']],
                [
                    'category_id' => $mathCategory->id,
                    'title' => $bookData['title'],
                    'description' => $bookData['description'],
                    'author' => $bookData['author'],
                    'content' => $bookData['content'],
                    'file_path' => null,
                    'file_type' => 'text',
                    'file_size' => 0,
                    'language' => 'km',
                    'pages' => $bookData['pages'],
                    'price' => 0,
                    'is_free' => true,
                    'is_published' => true,
                ]
            );
        }

        $this->command?->info('3 Math books with 10 pages each created.');
    }

    private function buildMathContent(array $headings, array $bodyParagraphs, int $pages): string
    {
        $blocks = [];

        for ($i = 0; $i < $pages; $i++) {
            $heading = $headings[$i] ?? 'Page '.($i + 1);
            $body = $bodyParagraphs[$i] ?? str_repeat('Mathematics is a fascinating subject that helps us understand the world around us. ', 10);
            $blocks[] = $heading."\n\n".$body;
        }

        return implode("\n\n", $blocks);
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

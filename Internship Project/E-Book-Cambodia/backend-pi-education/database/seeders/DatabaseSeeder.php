<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\Event;
use App\Models\User;
use App\Services\VipService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        User::factory()->create([
            'name' => 'Reader',
            'email' => 'reader@example.com',
            'password' => 'password',
        ]);

        $this->seedCategories();

        Author::create([
            'name' => 'Demo Author',
            'email' => 'author@example.com',
            'password' => 'password',
            'phone' => '012345678',
            'status' => Author::STATUS_ACTIVE,
            'bio' => 'Demo author account for the Pi Education author dashboard.',
            'specialization' => 'Literature',
            'organization' => 'Pi Education Cambodia',
            'email_verified_at' => now(),
        ]);

        $this->seedCourses();
        $this->call(VipPlanSeeder::class);
        if (VipService::testModeEnabled()) {
            $this->call(VipTestCardSeeder::class);
        }
        $this->seedEvents();
    }

    private function seedCategories(): void
    {
        $categories = [
            ['name' => 'Literature', 'description' => 'អក្សរសិល្ប៍ ➜ Novels, poetry and literary works.'],
            ['name' => 'Education', 'description' => 'ការអប់រំ ➜ Textbooks and learning materials.'],
            ['name' => 'History', 'description' => 'ប្រវត្តិសាស្ត្រ ➜ Cambodian and world history.'],
            ['name' => 'Technology', 'description' => 'បច្ចេកវិទ្យា ➜ Programming and digital skills.'],
            ['name' => 'Business', 'description' => 'អាជីវកម្ម ➜ Economics and entrepreneurship.'],
            ['name' => 'Health', 'description' => 'សុខភាព ➜ Wellness and medicine.'],
            ['name' => 'Math', 'description' => 'គណិតវិទ្យា ➜ Mathematics books for all levels.'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => strtolower($category['name']).'-'.substr(md5($category['name']), 0, 6)],
                [
                    'name' => $category['name'],
                    'description' => $category['description'],
                ]
            );
        }
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

    private function seedEvents(): void
    {
        $events = [
            [
                'title' => 'Monthly Book Reading Challenge',
                'description' => 'Join our community and read together this month. Complete the challenge and earn rewards.',
                'category' => 'Reading Challenge',
                'start_date' => now()->addDays(7)->setTime(14, 0),
                'end_date' => now()->addDays(30)->setTime(23, 59),
                'location' => 'Online',
                'organizer' => 'Pi Education Cambodia',
                'contact_email' => 'events@pi-education-cambodia.org',
                'fee_type' => Event::FEE_FREE,
                'speaker' => 'Sovann Ratanak',
                'status' => Event::STATUS_PUBLISHED,
                'is_featured' => true,
            ],
            [
                'title' => 'Teachers Training Workshop',
                'description' => 'A hands-on workshop for teachers on integrating digital books into classroom lessons.',
                'category' => 'Workshop',
                'start_date' => now()->addDays(19)->setTime(8, 30),
                'location' => 'Phnom Penh',
                'organizer' => 'Pi Education Cambodia',
                'contact_phone' => '012345678',
                'contact_email' => 'schools@pi-education-cambodia.org',
                'max_participants' => 60,
                'fee_type' => Event::FEE_PAID,
                'fee_amount' => 10,
                'speaker' => 'Chan Sophea',
                'status' => Event::STATUS_PUBLISHED,
            ],
            [
                'title' => 'National Book Fair',
                'description' => 'Visit our booth at the national book fair and discover new releases for all grades.',
                'category' => 'Exhibition',
                'start_date' => now()->addMonths(2)->setTime(9, 0),
                'location' => 'Koh Pich Convention Center',
                'organizer' => 'Ministry of Education',
                'registration_link' => 'https://bookfair.example.org',
                'contact_phone' => '98765432',
                'contact_email' => 'info@bookfair.example.org',
                'fee_type' => Event::FEE_FREE,
                'status' => Event::STATUS_PUBLISHED,
            ],
            [
                'title' => 'Khmer Essay Writing Contest',
                'description' => 'Write a short essay in Khmer on the topic of the year for a chance to win exciting prizes and publication.',
                'category' => 'Competition',
                'start_date' => now()->subDays(3)->setTime(9, 0),
                'end_date' => now()->subDay()->setTime(17, 0),
                'location' => 'Online',
                'fee_type' => Event::FEE_FREE,
                'status' => Event::STATUS_COMPLETED,
            ],
        ];

        foreach ($events as $data) {
            Event::updateOrCreate(['title' => $data['title']], $data);
        }
    }
}

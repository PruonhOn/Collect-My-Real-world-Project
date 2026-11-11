<?php

namespace Database\Seeders\Catalog\Datasets;

use App\Models\Book;
use Database\Seeders\Catalog\DemoBook;

/**
 * The teacher resources shelf of the demo catalogue.
 *
 * These titles carry `audience = teacher` so the catalogue can offer a
 * dedicated view for people who teach rather than study.
 */
final class TeacherCatalog
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function books(): array
    {
        return [
            DemoBook::make(
                slug: 'demo-teacher-lesson-planning',
                title: 'ការណែនាំការសរសេរបង្ហាញ',
                category: DemoBook::CATEGORY_EDUCATION,
                subject: DemoBook::SUBJECT_TEACHER,
                description: 'ជំនានៃការណែនាំបង្ហាញ ការកំណត់គោលក័ារ និងការរៀបចំសិក្សាសម្រាប់គ្រូបង្ហាញ។',
                chapters: [
                    'គោលក័ារបង្ហាញ',
                    'ការរៀបចំសិក្សា',
                    'ការវាយតម្លៃក្នុងថ្នាក់',
                    'កំណត់ចំណាំង',
                ],
                audience: 'teacher',
                level: DemoBook::LEVEL_TEACHER,
                daysAgo: 120,
                views: 320,
            ),
            DemoBook::make(
                slug: 'demo-teacher-classroom-management',
                title: 'ការគ្រប់គ្រងថ្នាក់រៀន',
                category: DemoBook::CATEGORY_EDUCATION,
                subject: DemoBook::SUBJECT_TEACHER,
                description: 'វិធីគ្រប់គ្រងថ្នាក់ ការដោះស្រាយបញ្ហា និងការបង្កើតបរិយាកាសរៀនសម្រាប់គ្រូបង្ហាញ។',
                chapters: [
                    'បរិយាកាសរៀន',
                    'ក្រមរដ្ឋានថ្នាក់',
                    'ដោះស្រាយបញ្ហា',
                    'កំណត់ចំណាំង',
                ],
                audience: 'teacher',
                level: DemoBook::LEVEL_TEACHER,
                daysAgo: 112,
                views: 290,
            ),
            DemoBook::make(
                slug: 'demo-teacher-student-assessment',
                title: 'ការវាយតម្លៃសិស្ស',
                category: DemoBook::CATEGORY_EDUCATION,
                subject: DemoBook::SUBJECT_TEACHER,
                description: 'ការវាយតម្លៃសិស្សជាការវែងជាការបង្ហាញ និងការផ្តល់ការចំណាំងវិញសម្រាប់គ្រូបង្ហាញ។',
                chapters: [
                    'ការវាយតម្លៃជាការបង្ហាញ',
                    'ឧបកប្រយោគវាយតម្លៃ',
                    'ការផ្តល់ការចំណាំងវិញ',
                    'កំណត់ចំណាំង',
                ],
                audience: 'teacher',
                level: DemoBook::LEVEL_TEACHER,
                price: 4.99,
                free: false,
                previewMode: Book::PREVIEW_MODE_READTEST,
                previewPages: 3,
                daysAgo: 104,
                views: 270,
                purchases: 58,
            ),
            DemoBook::make(
                slug: 'demo-teacher-active-learning',
                title: 'ការរៀនសូត្រដោយមានភ្លើងខ្លាំង',
                category: DemoBook::CATEGORY_EDUCATION,
                subject: DemoBook::SUBJECT_TEACHER,
                description: 'ការរៀនសូត្រដោយមានភ្លើងខ្លាំង ការស្វែងរក និងការសន្ទនា សម្រាប់គ្រូបង្ហាញ។',
                chapters: [
                    'ភាពសកម្មក្នុងថ្នាក់',
                    'ការស្វែងរក',
                    'ការសន្ទនា',
                    'កំណត់ចំណាំង',
                ],
                audience: 'teacher',
                level: DemoBook::LEVEL_TEACHER,
                price: 5.99,
                free: false,
                previewMode: Book::PREVIEW_MODE_READTEST,
                previewPages: 3,
                daysAgo: 98,
                views: 250,
                purchases: 52,
            ),
            DemoBook::make(
                slug: 'demo-teacher-curriculum-design',
                title: 'ការរៀបចំគម្រប័ន្ម',
                category: DemoBook::CATEGORY_EDUCATION,
                subject: DemoBook::SUBJECT_TEACHER,
                description: 'គោលក័ារគម្រប័ន្ម ការប្រកប្រក្សត្រ និងការសម្របជាមាត្រសាស្ត្រសម្រាប់គ្រូបង្ហាញ។',
                chapters: [
                    'គោលក័ារគម្រប័ន្ម',
                    'ប្រកប្រក្សត្រ',
                    'ការសម្របជាមាត្រសាស្ត្រ',
                    'កំណត់ចំណាំង',
                ],
                audience: 'teacher',
                level: DemoBook::LEVEL_TEACHER,
                daysAgo: 90,
                views: 230,
            ),
            DemoBook::make(
                slug: 'demo-teacher-formative-assessment',
                title: 'ការវាយតម្លៃបែងចែក',
                category: DemoBook::CATEGORY_EDUCATION,
                subject: DemoBook::SUBJECT_TEACHER,
                description: 'ការវាយតម្លៃបែងចែក ការសួរស្រាប់ និងការប្រើការទាក់ទងសម្រាប់គ្រូបង្ហាញ។',
                chapters: [
                    'ការវាយតម្លៃបែងចែក',
                    'ការសួរស្រាប់',
                    'ការប្រើការទាក់ទង',
                    'កំណត់ចំណាំង',
                ],
                audience: 'teacher',
                level: DemoBook::LEVEL_TEACHER,
                price: 4.99,
                free: false,
                previewMode: Book::PREVIEW_MODE_PREVIEW,
                previewPages: 2,
                daysAgo: 84,
                views: 210,
                purchases: 45,
            ),
            DemoBook::make(
                slug: 'demo-teacher-child-protection',
                title: 'ការការពារកូនកម្ម',
                category: DemoBook::CATEGORY_EDUCATION,
                subject: DemoBook::SUBJECT_TEACHER,
                description: 'ការការពារកូនកម្ម ការសម្គាល់ករណីជ័យ និងការការពារកុមារសម្រាប់គ្រូបង្ហាញ។',
                chapters: [
                    'ការការពារកូនកម្ម',
                    'ការសម្គាល់ករណីជ័យ',
                    'ការការពារកុមារ',
                    'កំណត់ចំណាំង',
                ],
                audience: 'teacher',
                level: DemoBook::LEVEL_TEACHER,
                daysAgo: 78,
                views: 190,
            ),
            DemoBook::make(
                slug: 'demo-teacher-ict-for-teaching',
                title: 'ព័ត៌មានបច្ចេកវិទ្យាសម្រាប់គ្រូ',
                category: DemoBook::CATEGORY_EDUCATION,
                subject: DemoBook::SUBJECT_TEACHER,
                description: 'ឧបកប្រញ្ញាផ្លូវគ្រូ ការរៀបចំសិក្សាឌីជីតា និងការវាយតម្លៃធន្លាក់សម្រាប់គ្រូបង្ហាញ។',
                chapters: [
                    'ឧបកប្រញ្ញាផ្លូវគ្រូ',
                    'សិក្សាឌីជីតា',
                    'វាយតម្លៃធន្លាក់',
                    'កំណត់ចំណាំង',
                ],
                audience: 'teacher',
                level: DemoBook::LEVEL_TEACHER,
                price: 4.99,
                free: false,
                previewMode: Book::PREVIEW_MODE_READTEST,
                previewPages: 3,
                daysAgo: 72,
                views: 180,
                purchases: 39,
            ),
            DemoBook::make(
                slug: 'demo-teacher-inclusive-education',
                title: 'ការគាំទ្រសិស្សដោយអាជីវ',
                category: DemoBook::CATEGORY_EDUCATION,
                subject: DemoBook::SUBJECT_TEACHER,
                description: 'ការគាំទ្រសិស្សដោយអាជីវ ការប្រើជំនាន់ និងការសម្របជាមាត្រសាស្ត្រសម្រាប់គ្រូបង្ហាញ។',
                chapters: [
                    'គាំទ្រដោយអាជីវ',
                    'ការប្រើជំនាន់',
                    'ការសម្របជាមាត្រសាស្ត្រ',
                    'កំណត់ចំណាំង',
                ],
                audience: 'teacher',
                level: DemoBook::LEVEL_TEACHER,
                price: 5.99,
                free: false,
                vip: true,
                previewMode: Book::PREVIEW_MODE_READTEST,
                previewPages: 3,
                daysAgo: 66,
                views: 200,
                purchases: 47,
            ),
            DemoBook::make(
                slug: 'demo-teacher-school-safety',
                title: 'សុវត្ថិភាពនៅសាលារៀន',
                category: DemoBook::CATEGORY_EDUCATION,
                subject: DemoBook::SUBJECT_TEACHER,
                description: 'សុវត្ថិភាពនៅសាលារៀន ការរៀបចំបញ្ហា និងការឆ្លើយតបក្នុងឧបទ្ទវហេតុសម្រាប់គ្រូបង្ហាញ។',
                chapters: [
                    'សុវត្ថិភាពសាលារៀន',
                    'ការរៀបចំបញ្ហា',
                    'ការឆ្លើយតបក្នុងឧបទ្ទវហេតុ',
                    'កំណត់ចំណាំង',
                ],
                audience: 'teacher',
                level: DemoBook::LEVEL_TEACHER,
                daysAgo: 60,
                views: 170,
            ),
        ];
    }
}

<?php

namespace Database\Seeders;

use App\Models\ClassSection;
use App\Models\Course;
use App\Models\AcademicTerm;
use App\Models\Teacher;
use Illuminate\Database\Seeder;

class ClassSectionSeeder extends Seeder
{
    public function run(): void
    {
        $term = AcademicTerm::where('name', 'Fall 2026')->first();

        $drAhmed = Teacher::where('employee_code', 'TCH001')->first();
        $drSarah = Teacher::where('employee_code', 'TCH002')->first();
        $drMohammad = Teacher::where('employee_code', 'TCH003')->first();
        $drFatima = Teacher::where('employee_code', 'TCH004')->first();
        $drJohn = Teacher::where('employee_code', 'TCH005')->first();
        $drMaria = Teacher::where('employee_code', 'TCH006')->first();

        $cs101 = Course::where('code', 'CS101')->first();
        $cs201 = Course::where('code', 'CS201')->first();
        $cs301 = Course::where('code', 'CS301')->first();
        $se101 = Course::where('code', 'SE101')->first();
        $math101 = Course::where('code', 'MATH101')->first();
        $math201 = Course::where('code', 'MATH201')->first();
        $ee101 = Course::where('code', 'EE101')->first();
        $ba101 = Course::where('code', 'BA101')->first();
        $acc101 = Course::where('code', 'ACC101')->first();
        $ce101 = Course::where('code', 'CE101')->first();

        $sections = [
            [
                'course_id' => $cs101->id,
                'teacher_id' => $drAhmed->id,
                'term_id' => $term->id,
                'name' => 'Section A',
                'room' => '101',
                'schedule_json' => ['days' => 'Mon/Wed', 'start_time' => '09:00', 'end_time' => '10:30'],
            ],
            [
                'course_id' => $cs101->id,
                'teacher_id' => $drSarah->id,
                'term_id' => $term->id,
                'name' => 'Section B',
                'room' => '102',
                'schedule_json' => ['days' => 'Tue/Thu', 'start_time' => '09:00', 'end_time' => '10:30'],
            ],
            [
                'course_id' => $cs201->id,
                'teacher_id' => $drAhmed->id,
                'term_id' => $term->id,
                'name' => 'Section A',
                'room' => '103',
                'schedule_json' => ['days' => 'Mon/Wed', 'start_time' => '11:00', 'end_time' => '12:30'],
            ],
            [
                'course_id' => $cs301->id,
                'teacher_id' => $drSarah->id,
                'term_id' => $term->id,
                'name' => 'Section A',
                'room' => '104',
                'schedule_json' => ['days' => 'Tue/Thu', 'start_time' => '14:00', 'end_time' => '15:30'],
            ],
            [
                'course_id' => $se101->id,
                'teacher_id' => $drSarah->id,
                'term_id' => $term->id,
                'name' => 'Section A',
                'room' => '105',
                'schedule_json' => ['days' => 'Mon/Fri', 'start_time' => '10:00', 'end_time' => '11:30'],
            ],
            [
                'course_id' => $math101->id,
                'teacher_id' => $drMohammad->id,
                'term_id' => $term->id,
                'name' => 'Section A',
                'room' => '201',
                'schedule_json' => ['days' => 'Mon/Wed', 'start_time' => '09:00', 'end_time' => '10:30'],
            ],
            [
                'course_id' => $math201->id,
                'teacher_id' => $drMohammad->id,
                'term_id' => $term->id,
                'name' => 'Section A',
                'room' => '202',
                'schedule_json' => ['days' => 'Tue/Thu', 'start_time' => '11:00', 'end_time' => '12:30'],
            ],
            [
                'course_id' => $ee101->id,
                'teacher_id' => $drFatima->id,
                'term_id' => $term->id,
                'name' => 'Section A',
                'room' => '301',
                'schedule_json' => ['days' => 'Mon/Wed', 'start_time' => '14:00', 'end_time' => '15:30'],
            ],
            [
                'course_id' => $ba101->id,
                'teacher_id' => $drJohn->id,
                'term_id' => $term->id,
                'name' => 'Section A',
                'room' => '401',
                'schedule_json' => ['days' => 'Tue/Thu', 'start_time' => '10:00', 'end_time' => '11:30'],
            ],
            [
                'course_id' => $acc101->id,
                'teacher_id' => $drJohn->id,
                'term_id' => $term->id,
                'name' => 'Section A',
                'room' => '402',
                'schedule_json' => ['days' => 'Mon/Wed', 'start_time' => '10:00', 'end_time' => '11:30'],
            ],
            [
                'course_id' => $ce101->id,
                'teacher_id' => $drMaria->id,
                'term_id' => $term->id,
                'name' => 'Section A',
                'room' => '501',
                'schedule_json' => ['days' => 'Fri', 'start_time' => '09:00', 'end_time' => '12:00'],
            ],
        ];

        foreach ($sections as $data) {
            ClassSection::create($data);
        }
    }
}

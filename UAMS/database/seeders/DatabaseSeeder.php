<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            FacultySeeder::class,
            DepartmentSeeder::class,
            ProgramSeeder::class,
            AcademicTermSeeder::class,
            CourseSeeder::class,
            AdminSeeder::class,
            TeacherSeeder::class,
            StudentSeeder::class,
            ClassSectionSeeder::class,
            EnrollmentSeeder::class,
            AttendanceSessionSeeder::class,
            AttendanceRecordSeeder::class,
        ]);
    }
}

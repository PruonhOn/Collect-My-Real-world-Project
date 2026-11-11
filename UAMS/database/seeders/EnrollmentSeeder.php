<?php

namespace Database\Seeders;

use App\Models\ClassSection;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Database\Seeder;

class EnrollmentSeeder extends Seeder
{
    public function run(): void
    {
        $cs101A = ClassSection::whereHas('course', fn ($q) => $q->where('code', 'CS101'))
            ->where('name', 'Section A')->first();
        $cs201A = ClassSection::whereHas('course', fn ($q) => $q->where('code', 'CS201'))
            ->where('name', 'Section A')->first();
        $se101A = ClassSection::whereHas('course', fn ($q) => $q->where('code', 'SE101'))
            ->where('name', 'Section A')->first();
        $math101A = ClassSection::whereHas('course', fn ($q) => $q->where('code', 'MATH101'))
            ->where('name', 'Section A')->first();
        $math201A = ClassSection::whereHas('course', fn ($q) => $q->where('code', 'MATH201'))
            ->where('name', 'Section A')->first();
        $ee101A = ClassSection::whereHas('course', fn ($q) => $q->where('code', 'EE101'))
            ->where('name', 'Section A')->first();
        $ba101A = ClassSection::whereHas('course', fn ($q) => $q->where('code', 'BA101'))
            ->where('name', 'Section A')->first();
        $acc101A = ClassSection::whereHas('course', fn ($q) => $q->where('code', 'ACC101'))
            ->where('name', 'Section A')->first();

        // CS students (STU001-STU010) -> CS101-A, CS201-A, MATH101-A
        $csStudentCodes = ['STU001', 'STU002', 'STU003', 'STU004', 'STU005', 'STU006', 'STU007', 'STU008', 'STU009', 'STU010'];
        $this->enrollStudents($csStudentCodes, [$cs101A->id, $cs201A->id, $math101A->id]);

        // SE students (STU011-STU015) -> SE101-A, CS101-A
        $seStudentCodes = ['STU011', 'STU012', 'STU013', 'STU014', 'STU015'];
        $this->enrollStudents($seStudentCodes, [$se101A->id, $cs101A->id]);

        // MATH students (STU016-STU018) -> MATH101-A, MATH201-A
        $mathStudentCodes = ['STU016', 'STU017', 'STU018'];
        $this->enrollStudents($mathStudentCodes, [$math101A->id, $math201A->id]);

        // EE students (STU019-STU022) -> EE101-A, MATH101-A
        $eeStudentCodes = ['STU019', 'STU020', 'STU021', 'STU022'];
        $this->enrollStudents($eeStudentCodes, [$ee101A->id, $math101A->id]);

        // BA students (STU023-STU026) -> BA101-A
        $baStudentCodes = ['STU023', 'STU024', 'STU025', 'STU026'];
        $this->enrollStudents($baStudentCodes, [$ba101A->id]);

        // ACC students (STU027-STU030) -> ACC101-A, BA101-A
        $accStudentCodes = ['STU027', 'STU028', 'STU029', 'STU030'];
        $this->enrollStudents($accStudentCodes, [$acc101A->id, $ba101A->id]);
    }

    private function enrollStudents(array $studentCodes, array $sectionIds): void
    {
        foreach ($studentCodes as $code) {
            $student = Student::where('student_code', $code)->first();
            if (! $student) {
                continue;
            }

            foreach ($sectionIds as $sectionId) {
                Enrollment::create([
                    'student_id' => $student->id,
                    'class_section_id' => $sectionId,
                    'status' => 'active',
                ]);
            }
        }
    }
}

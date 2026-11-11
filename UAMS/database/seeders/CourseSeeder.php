<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Program;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $csProgram = Program::where('code', 'CS')->first();
        $seProgram = Program::where('code', 'SE')->first();
        $mathProgram = Program::where('code', 'MATH')->first();
        $physProgram = Program::where('code', 'PHYS')->first();
        $eeProgram = Program::where('code', 'EE')->first();
        $meProgram = Program::where('code', 'ME')->first();
        $ceProgram = Program::where('code', 'CE')->first();
        $baProgram = Program::where('code', 'BA')->first();
        $accProgram = Program::where('code', 'ACC')->first();
        $finProgram = Program::where('code', 'FIN')->first();

        $courses = [
            ['program_id' => $csProgram->id, 'code' => 'CS101', 'name' => 'Introduction to Computer Science', 'credit_hours' => 3],
            ['program_id' => $csProgram->id, 'code' => 'CS201', 'name' => 'Data Structures and Algorithms', 'credit_hours' => 3],
            ['program_id' => $csProgram->id, 'code' => 'CS301', 'name' => 'Database Systems', 'credit_hours' => 3],
            ['program_id' => $seProgram->id, 'code' => 'SE101', 'name' => 'Software Engineering Fundamentals', 'credit_hours' => 3],
            ['program_id' => $mathProgram->id, 'code' => 'MATH101', 'name' => 'Calculus I', 'credit_hours' => 3],
            ['program_id' => $mathProgram->id, 'code' => 'MATH201', 'name' => 'Linear Algebra', 'credit_hours' => 3],
            ['program_id' => $physProgram->id, 'code' => 'PHYS101', 'name' => 'Physics I', 'credit_hours' => 3],
            ['program_id' => $eeProgram->id, 'code' => 'EE101', 'name' => 'Circuit Analysis', 'credit_hours' => 3],
            ['program_id' => $meProgram->id, 'code' => 'ME101', 'name' => 'Mechanics', 'credit_hours' => 3],
            ['program_id' => $ceProgram->id, 'code' => 'CE101', 'name' => 'Structural Analysis', 'credit_hours' => 3],
            ['program_id' => $baProgram->id, 'code' => 'BA101', 'name' => 'Principles of Management', 'credit_hours' => 3],
            ['program_id' => $accProgram->id, 'code' => 'ACC101', 'name' => 'Financial Accounting', 'credit_hours' => 3],
            ['program_id' => $finProgram->id, 'code' => 'FIN101', 'name' => 'Introduction to Finance', 'credit_hours' => 3],
        ];

        foreach ($courses as $course) {
            Course::create($course);
        }
    }
}

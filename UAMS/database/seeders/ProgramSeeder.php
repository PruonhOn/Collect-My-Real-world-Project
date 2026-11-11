<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Program;
use Illuminate\Database\Seeder;

class ProgramSeeder extends Seeder
{
    public function run(): void
    {
        $cs = Department::where('name', 'Computer Science')->first();
        $math = Department::where('name', 'Mathematics')->first();
        $physics = Department::where('name', 'Physics')->first();
        $ee = Department::where('name', 'Electrical Engineering')->first();
        $me = Department::where('name', 'Mechanical Engineering')->first();
        $ce = Department::where('name', 'Civil Engineering')->first();
        $ba = Department::where('name', 'Business Administration')->first();
        $acc = Department::where('name', 'Accounting')->first();
        $fin = Department::where('name', 'Finance')->first();

        // Computer Science
        Program::create([
            'department_id' => $cs->id,
            'name' => 'BSc Computer Science',
            'code' => 'CS',
            'description' => 'Bachelor of Science in Computer Science focusing on software development, algorithms, and systems.',
        ]);

        Program::create([
            'department_id' => $cs->id,
            'name' => 'BSc Software Engineering',
            'code' => 'SE',
            'description' => 'Bachelor of Science in Software Engineering focusing on software lifecycle, testing, and project management.',
        ]);

        // Mathematics
        Program::create([
            'department_id' => $math->id,
            'name' => 'BSc Mathematics',
            'code' => 'MATH',
            'description' => 'Bachelor of Science in Mathematics covering algebra, analysis, and applied mathematics.',
        ]);

        // Physics
        Program::create([
            'department_id' => $physics->id,
            'name' => 'BSc Physics',
            'code' => 'PHYS',
            'description' => 'Bachelor of Science in Physics covering mechanics, electromagnetism, and quantum physics.',
        ]);

        // Electrical Engineering
        Program::create([
            'department_id' => $ee->id,
            'name' => 'BSc Electrical Engineering',
            'code' => 'EE',
            'description' => 'Bachelor of Science in Electrical Engineering covering circuit design, signals, and power systems.',
        ]);

        // Mechanical Engineering
        Program::create([
            'department_id' => $me->id,
            'name' => 'BSc Mechanical Engineering',
            'code' => 'ME',
            'description' => 'Bachelor of Science in Mechanical Engineering covering dynamics, thermodynamics, and materials.',
        ]);

        // Civil Engineering
        Program::create([
            'department_id' => $ce->id,
            'name' => 'BSc Civil Engineering',
            'code' => 'CE',
            'description' => 'Bachelor of Science in Civil Engineering covering structural analysis, hydraulics, and construction.',
        ]);

        // Business Administration
        Program::create([
            'department_id' => $ba->id,
            'name' => 'BBA Business Administration',
            'code' => 'BA',
            'description' => 'Bachelor of Business Administration covering management principles, strategy, and operations.',
        ]);

        // Accounting
        Program::create([
            'department_id' => $acc->id,
            'name' => 'BSc Accounting',
            'code' => 'ACC',
            'description' => 'Bachelor of Science in Accounting covering financial accounting, auditing, and taxation.',
        ]);

        // Finance
        Program::create([
            'department_id' => $fin->id,
            'name' => 'BSc Finance',
            'code' => 'FIN',
            'description' => 'Bachelor of Science in Finance covering investments, corporate finance, and risk management.',
        ]);
    }
}

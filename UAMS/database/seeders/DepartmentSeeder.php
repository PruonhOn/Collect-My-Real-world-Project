<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Faculty;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $fsci = Faculty::where('name', 'Faculty of Science')->first();
        $feng = Faculty::where('name', 'Faculty of Engineering')->first();
        $fbiz = Faculty::where('name', 'Faculty of Business')->first();

        // Faculty of Science
        Department::create([
            'faculty_id' => $fsci->id,
            'name' => 'Computer Science',
            'description' => 'Department of Computer Science covering software development, algorithms, and computing theory.',
        ]);

        Department::create([
            'faculty_id' => $fsci->id,
            'name' => 'Mathematics',
            'description' => 'Department of Mathematics covering pure and applied mathematics.',
        ]);

        Department::create([
            'faculty_id' => $fsci->id,
            'name' => 'Physics',
            'description' => 'Department of Physics covering classical and modern physics.',
        ]);

        // Faculty of Engineering
        Department::create([
            'faculty_id' => $feng->id,
            'name' => 'Electrical Engineering',
            'description' => 'Department of Electrical Engineering covering circuits, electronics, and power systems.',
        ]);

        Department::create([
            'faculty_id' => $feng->id,
            'name' => 'Mechanical Engineering',
            'description' => 'Department of Mechanical Engineering covering thermodynamics, fluid mechanics, and manufacturing.',
        ]);

        Department::create([
            'faculty_id' => $feng->id,
            'name' => 'Civil Engineering',
            'description' => 'Department of Civil Engineering covering structural analysis, construction, and infrastructure.',
        ]);

        // Faculty of Business
        Department::create([
            'faculty_id' => $fbiz->id,
            'name' => 'Business Administration',
            'description' => 'Department of Business Administration covering management, marketing, and organizational behavior.',
        ]);

        Department::create([
            'faculty_id' => $fbiz->id,
            'name' => 'Accounting',
            'description' => 'Department of Accounting covering financial reporting, auditing, and taxation.',
        ]);

        Department::create([
            'faculty_id' => $fbiz->id,
            'name' => 'Finance',
            'description' => 'Department of Finance covering investments, corporate finance, and financial markets.',
        ]);
    }
}

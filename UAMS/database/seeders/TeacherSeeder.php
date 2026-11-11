<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TeacherSeeder extends Seeder
{
    public function run(): void
    {
        $csDept = Department::where('name', 'Computer Science')->first();
        $mathDept = Department::where('name', 'Mathematics')->first();
        $eeDept = Department::where('name', 'Electrical Engineering')->first();
        $baDept = Department::where('name', 'Business Administration')->first();
        $ceDept = Department::where('name', 'Civil Engineering')->first();

        $teachers = [
            [
                'name' => 'Dr. Ahmed Khan',
                'email' => 'ahmed.khan@uams.edu',
                'department_id' => $csDept->id,
                'employee_code' => 'TCH001',
            ],
            [
                'name' => 'Dr. Sarah Williams',
                'email' => 'sarah.williams@uams.edu',
                'department_id' => $csDept->id,
                'employee_code' => 'TCH002',
            ],
            [
                'name' => 'Dr. Mohammad Ali',
                'email' => 'mohammad.ali@uams.edu',
                'department_id' => $mathDept->id,
                'employee_code' => 'TCH003',
            ],
            [
                'name' => 'Dr. Fatima Hassan',
                'email' => 'fatima.hassan@uams.edu',
                'department_id' => $eeDept->id,
                'employee_code' => 'TCH004',
            ],
            [
                'name' => 'Dr. John Davis',
                'email' => 'john.davis@uams.edu',
                'department_id' => $baDept->id,
                'employee_code' => 'TCH005',
            ],
            [
                'name' => 'Dr. Maria Garcia',
                'email' => 'maria.garcia@uams.edu',
                'department_id' => $ceDept->id,
                'employee_code' => 'TCH006',
            ],
        ];

        foreach ($teachers as $data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make('password'),
                'role' => 'teacher',
                'is_active' => true,
            ]);
            $user->assignRole('teacher');

            Teacher::create([
                'user_id' => $user->id,
                'department_id' => $data['department_id'],
                'employee_code' => $data['employee_code'],
            ]);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $csDept = Department::where('name', 'Computer Science')->first();
        $mathDept = Department::where('name', 'Mathematics')->first();
        $eeDept = Department::where('name', 'Electrical Engineering')->first();
        $baDept = Department::where('name', 'Business Administration')->first();
        $accDept = Department::where('name', 'Accounting')->first();

        $csProgram = Program::where('code', 'CS')->first();
        $seProgram = Program::where('code', 'SE')->first();
        $mathProgram = Program::where('code', 'MATH')->first();
        $eeProgram = Program::where('code', 'EE')->first();
        $baProgram = Program::where('code', 'BA')->first();
        $accProgram = Program::where('code', 'ACC')->first();

        $students = [
            // CS students (STU001-STU010)
            ['name' => 'Omar Al-Rashid', 'email' => 'omar.alrashid@student.uams.edu', 'student_code' => 'STU001', 'department_id' => $csDept->id, 'program_id' => $csProgram->id, 'year' => 2],
            ['name' => 'Emily Chen', 'email' => 'emily.chen@student.uams.edu', 'student_code' => 'STU002', 'department_id' => $csDept->id, 'program_id' => $csProgram->id, 'year' => 1],
            ['name' => 'Yusuf Ibrahim', 'email' => 'yusuf.ibrahim@student.uams.edu', 'student_code' => 'STU003', 'department_id' => $csDept->id, 'program_id' => $csProgram->id, 'year' => 3],
            ['name' => 'Priya Sharma', 'email' => 'priya.sharma@student.uams.edu', 'student_code' => 'STU004', 'department_id' => $csDept->id, 'program_id' => $csProgram->id, 'year' => 2],
            ['name' => 'David Kim', 'email' => 'david.kim@student.uams.edu', 'student_code' => 'STU005', 'department_id' => $csDept->id, 'program_id' => $csProgram->id, 'year' => 4],
            ['name' => 'Aisha Patel', 'email' => 'aisha.patel@student.uams.edu', 'student_code' => 'STU006', 'department_id' => $csDept->id, 'program_id' => $csProgram->id, 'year' => 1],
            ['name' => 'Lucas Fernandez', 'email' => 'lucas.fernandez@student.uams.edu', 'student_code' => 'STU007', 'department_id' => $csDept->id, 'program_id' => $csProgram->id, 'year' => 3],
            ['name' => 'Fatima Al-Zahra', 'email' => 'fatima.alzahra@student.uams.edu', 'student_code' => 'STU008', 'department_id' => $csDept->id, 'program_id' => $csProgram->id, 'year' => 2],
            ['name' => 'Ryan Mitchell', 'email' => 'ryan.mitchell@student.uams.edu', 'student_code' => 'STU009', 'department_id' => $csDept->id, 'program_id' => $csProgram->id, 'year' => 1],
            ['name' => 'Sara Nazari', 'email' => 'sara.nazari@student.uams.edu', 'student_code' => 'STU010', 'department_id' => $csDept->id, 'program_id' => $csProgram->id, 'year' => 4],

            // SE students (STU011-STU015)
            ['name' => 'James O\'Connor', 'email' => 'james.oconnor@student.uams.edu', 'student_code' => 'STU011', 'department_id' => $csDept->id, 'program_id' => $seProgram->id, 'year' => 2],
            ['name' => 'Hana Tanaka', 'email' => 'hana.tanaka@student.uams.edu', 'student_code' => 'STU012', 'department_id' => $csDept->id, 'program_id' => $seProgram->id, 'year' => 1],
            ['name' => 'Carlos Rivera', 'email' => 'carlos.rivera@student.uams.edu', 'student_code' => 'STU013', 'department_id' => $csDept->id, 'program_id' => $seProgram->id, 'year' => 3],
            ['name' => 'Nadia Kovalenko', 'email' => 'nadia.kovalenko@student.uams.edu', 'student_code' => 'STU014', 'department_id' => $csDept->id, 'program_id' => $seProgram->id, 'year' => 2],
            ['name' => 'Ahmed Mansour', 'email' => 'ahmed.mansour@student.uams.edu', 'student_code' => 'STU015', 'department_id' => $csDept->id, 'program_id' => $seProgram->id, 'year' => 1],

            // MATH students (STU016-STU018)
            ['name' => 'Elena Volkov', 'email' => 'elena.volkov@student.uams.edu', 'student_code' => 'STU016', 'department_id' => $mathDept->id, 'program_id' => $mathProgram->id, 'year' => 2],
            ['name' => 'Tariq Hassan', 'email' => 'tariq.hassan@student.uams.edu', 'student_code' => 'STU017', 'department_id' => $mathDept->id, 'program_id' => $mathProgram->id, 'year' => 3],
            ['name' => 'Julia Santos', 'email' => 'julia.santos@student.uams.edu', 'student_code' => 'STU018', 'department_id' => $mathDept->id, 'program_id' => $mathProgram->id, 'year' => 1],

            // EE students (STU019-STU022)
            ['name' => 'Mohammed Al-Farsi', 'email' => 'mohammed.alfarsi@student.uams.edu', 'student_code' => 'STU019', 'department_id' => $eeDept->id, 'program_id' => $eeProgram->id, 'year' => 2],
            ['name' => 'Sophie Laurent', 'email' => 'sophie.laurent@student.uams.edu', 'student_code' => 'STU020', 'department_id' => $eeDept->id, 'program_id' => $eeProgram->id, 'year' => 3],
            ['name' => 'Raj Gupta', 'email' => 'raj.gupta@student.uams.edu', 'student_code' => 'STU021', 'department_id' => $eeDept->id, 'program_id' => $eeProgram->id, 'year' => 1],
            ['name' => 'Anna Schmidt', 'email' => 'anna.schmidt@student.uams.edu', 'student_code' => 'STU022', 'department_id' => $eeDept->id, 'program_id' => $eeProgram->id, 'year' => 4],

            // BA students (STU023-STU026)
            ['name' => 'Michael Brooks', 'email' => 'michael.brooks@student.uams.edu', 'student_code' => 'STU023', 'department_id' => $baDept->id, 'program_id' => $baProgram->id, 'year' => 2],
            ['name' => 'Leila Ahmadi', 'email' => 'leila.ahmadi@student.uams.edu', 'student_code' => 'STU024', 'department_id' => $baDept->id, 'program_id' => $baProgram->id, 'year' => 1],
            ['name' => 'Daniel Okafor', 'email' => 'daniel.okafor@student.uams.edu', 'student_code' => 'STU025', 'department_id' => $baDept->id, 'program_id' => $baProgram->id, 'year' => 3],
            ['name' => 'Mei Lin Wong', 'email' => 'mei.wong@student.uams.edu', 'student_code' => 'STU026', 'department_id' => $baDept->id, 'program_id' => $baProgram->id, 'year' => 2],

            // ACC students (STU027-STU030)
            ['name' => 'Oscar Herrera', 'email' => 'oscar.herrera@student.uams.edu', 'student_code' => 'STU027', 'department_id' => $accDept->id, 'program_id' => $accProgram->id, 'year' => 2],
            ['name' => 'Zara Ahmed', 'email' => 'zara.ahmed@student.uams.edu', 'student_code' => 'STU028', 'department_id' => $accDept->id, 'program_id' => $accProgram->id, 'year' => 1],
            ['name' => 'Thomas Nielsen', 'email' => 'thomas.nielsen@student.uams.edu', 'student_code' => 'STU029', 'department_id' => $accDept->id, 'program_id' => $accProgram->id, 'year' => 3],
            ['name' => 'Chloe Dubois', 'email' => 'chloe.dubois@student.uams.edu', 'student_code' => 'STU030', 'department_id' => $accDept->id, 'program_id' => $accProgram->id, 'year' => 4],
        ];

        foreach ($students as $data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make('password'),
                'role' => 'student',
                'is_active' => true,
            ]);
            $user->assignRole('student');

            Student::create([
                'user_id' => $user->id,
                'student_code' => $data['student_code'],
                'department_id' => $data['department_id'],
                'program_id' => $data['program_id'],
                'year' => $data['year'],
            ]);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Enums\Gender;
use App\Enums\StudentStatus;
use App\Models\Guardian;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Database\Seeder;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $demoPassword = 'password';
        $users = app(UserService::class);

        $this->user($users, 'Super Administrator', 'superadmin@sms.local', '+855 10 000 001', $demoPassword, 'super_admin');

        $this->user($users, 'Operations Admin', 'admin@sms.local', '+855 10 000 002', $demoPassword, 'admin');

        // Office staff + profile
        $staffUser = $this->user($users, 'Office Staff', 'staff@sms.local', '+855 10 000 003', $demoPassword, 'staff');
        Staff::firstOrCreate(
            ['user_id' => $staffUser->id],
            [
                'employee_code' => 'STF-0001',
                'position' => 'Registrar Officer',
                'department' => 'Academic Office',
                'hire_date' => now()->subMonths(6)->toDateString(),
            ],
        );

        // Teacher + profile
        $teacherUser = $this->user($users, 'Dara Kim', 'teacher@sms.local', '+855 10 000 004', $demoPassword, 'teacher');
        Teacher::firstOrCreate(
            ['user_id' => $teacherUser->id],
            [
                'employee_code' => 'TCH-0001',
                'hire_date' => now()->subYear()->toDateString(),
                'qualification' => 'B.Ed English',
                'specialization' => 'English Language',
            ],
        );

        // Student + profile
        $studentUser = $this->user($users, 'Dara Kim (Student)', 'student@sms.local', '+855 10 000 005', $demoPassword, 'student');
        $student = Student::firstOrCreate(
            ['user_id' => $studentUser->id],
            [
                'student_code' => 'STU-2026-0001',
                'khmer_name' => 'គីម ដារ៉ា',
                'gender' => Gender::Male->value,
                'birth_date' => now()->subYears(14)->toDateString(),
                'contact_phone' => '+855 10 000 005',
                'guardian_name' => 'Kim Sok',
                'guardian_phone' => '+855 10 000 006',
                'address' => 'Phnom Penh',
                'status' => StudentStatus::Active->value,
                'enrolled_on' => now()->toDateString(),
            ],
        );

        // Parent linked to the student
        $parentUser = $this->user($users, 'Kim Sok', 'parent@sms.local', '+855 10 000 006', $demoPassword, 'parent');
        $parent = Guardian::firstOrCreate(
            ['user_id' => $parentUser->id],
            ['occupation' => 'Business Owner'],
        );

        if ($parent->students()->where('students.id', $student->id)->doesntExist()) {
            $parent->students()->attach($student->id);
        }
    }

    private function user(UserService $users, string $name, string $email, string $phone, string $password, string $role): User
    {
        $existing = User::withTrashed()->where('email', $email)->first();

        if ($existing !== null) {
            return $existing;
        }

        return $users->create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => $password,
        ], [$role]);
    }
}

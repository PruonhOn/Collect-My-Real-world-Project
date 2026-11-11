<?php

namespace App\Services;

use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StudentService
{
    public function create(array $data): Student
    {
        $data['age'] = calculate_age($data['date_of_birth'] ?? null);

        $this->validateAge($data['age'] ?? null);

        if (! isset($data['student_id'])) {
            $data['student_id'] = $this->generateStudentId();
        }

        $user = null;

        if (! empty($data['email']) && ! empty($data['password'])) {
            $user = User::create([
                'name' => $data['full_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'role' => 'student',
                'password' => Hash::make($data['password']),
            ]);
            $user->assignRole('student');
        }

        $student = Student::create([
            'user_id' => $user?->id,
            'student_id' => $data['student_id'],
            'full_name' => $data['full_name'],
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'date_of_birth' => $data['date_of_birth'],
            'age' => $studentAge = calculate_age($data['date_of_birth'] ?? null),
            'gender' => $data['gender'] ?? 'male',
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'photo' => $data['photo'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]);

        return $student;
    }

    public function validateAge(?int $age): void
    {
        if ($age !== null && ($age < 5 || $age > 18)) {
            throw ValidationException::withMessages([
                'date_of_birth' => 'សិស្សត្រូវមានអាយុពី 5 ទៅ 18 ឆ្នាំ។',
            ]);
        }
    }

    public function generateStudentId(): string
    {
        return 'STU-' . now()->format('Y') . strtoupper(Str::random(6));
    }
}

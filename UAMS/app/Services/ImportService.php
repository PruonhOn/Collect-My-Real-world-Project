<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportService
{
    public function importStudents(UploadedFile $file): array
    {
        return $this->importRows($file, 'student');
    }

    public function importTeachers(UploadedFile $file): array
    {
        return $this->importRows($file, 'teacher');
    }

    protected function importRows(UploadedFile $file, string $type): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $rows = $spreadsheet->getActiveSheet()->toArray();

            if (empty($rows)) {
                return ['imported' => 0, 'skipped' => 0, 'errors' => ['File is empty.']];
            }

            $headers = array_map('strtolower', array_map('trim', $rows[0]));
            $dataRows = array_slice($rows, 1);

            foreach ($dataRows as $index => $row) {
                $rowNumber = $index + 2;

                try {
                    if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                        continue;
                    }

                    $record = array_combine($headers, array_pad(array_slice($row, 0, count($headers)), count($headers), null));

                    $requiredFields = $type === 'student'
                        ? ['name', 'email', 'student_code']
                        : ['name', 'email', 'employee_code'];

                    foreach ($requiredFields as $field) {
                        if (empty(trim((string) ($record[$field] ?? '')))) {
                            throw new \InvalidArgumentException("Missing required field: {$field}");
                        }
                    }

                    $email = trim((string) $record['email']);
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        throw new \InvalidArgumentException("Invalid email: {$email}");
                    }

                    DB::transaction(function () use ($record, $email, $type, &$imported, &$skipped) {
                        $existingUser = User::where('email', $email)->first();

                        if ($existingUser) {
                            if ($type === 'student') {
                                $exists = Student::where('user_id', $existingUser->id)->exists();
                            } else {
                                $exists = Teacher::where('user_id', $existingUser->id)->exists();
                            }

                            if ($exists) {
                                $skipped++;
                                return;
                            }
                        }

                        $user = $existingUser ?? User::create([
                            'name' => trim((string) $record['name']),
                            'email' => $email,
                            'password' => Hash::make('password'),
                            'role' => $type,
                            'email_verified_at' => now(),
                            'is_active' => true,
                        ]);

                        if ($type === 'student') {
                            Student::create([
                                'user_id' => $user->id,
                                'student_code' => trim((string) $record['student_code']),
                                'department_id' => !empty($record['department_id']) ? (int) $record['department_id'] : null,
                                'program_id' => !empty($record['program_id']) ? (int) $record['program_id'] : null,
                                'year' => !empty($record['year']) ? (int) $record['year'] : 1,
                            ]);
                        } else {
                            Teacher::create([
                                'user_id' => $user->id,
                                'employee_code' => trim((string) $record['employee_code']),
                                'department_id' => !empty($record['department_id']) ? (int) $record['department_id'] : null,
                            ]);
                        }

                        $imported++;
                    });
                } catch (\Exception $e) {
                    $errors[] = "Row {$rowNumber}: {$e->getMessage()}";
                }
            }
        } catch (\Exception $e) {
            $errors[] = "Failed to parse file: {$e->getMessage()}";
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }
}

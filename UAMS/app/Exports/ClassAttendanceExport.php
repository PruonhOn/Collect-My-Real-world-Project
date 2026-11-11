<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ClassAttendanceExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected array $students;
    protected int $totalSessions;

    public function __construct(array $students, int $totalSessions)
    {
        $this->students = $students;
        $this->totalSessions = $totalSessions;
    }

    public function collection()
    {
        return collect($this->students);
    }

    public function headings(): array
    {
        return [
            'Student Name',
            'Student Code',
            'Present',
            'Absent',
            'Late',
            'Excused',
            'Attendance Rate (%)',
        ];
    }

    public function map($student): array
    {
        $rate = $this->totalSessions > 0
            ? round(($student['present'] / $this->totalSessions) * 100, 1)
            : 0;

        return [
            $student['name'],
            $student['student_code'],
            $student['present'],
            $student['absent'],
            $student['late'],
            $student['excused'],
            $rate,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}

<?php

namespace App\Exports;

use App\Models\Student;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StudentsExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(private readonly ?string $search = null, private readonly ?string $status = null) {}

    public function query(): Builder
    {
        $query = Student::query()->with(['user']);

        if ($this->status !== null && $this->status !== '') {
            $query->where('status', $this->status);
        }

        if ($this->search !== null && $this->search !== '') {
            $query->where(function ($q) {
                $q->where('student_code', 'like', "%{$this->search}%")
                    ->orWhere('khmer_name', 'like', "%{$this->search}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%"));
            });
        }

        return $query->orderBy('id');
    }

    public function headings(): array
    {
        return ['ID', 'Student Code', 'Name', 'Khmer Name', 'Gender', 'Phone', 'Status'];
    }

    public function map($student): array
    {
        return [
            $student->id,
            $student->student_code,
            $student->user?->name,
            $student->khmer_name,
            $student->gender?->value,
            $student->contact_phone,
            $student->status?->value,
        ];
    }
}

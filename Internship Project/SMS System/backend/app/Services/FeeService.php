<?php

namespace App\Services;

use App\Enums\FeeStatus;
use App\Models\Student;
use App\Models\StudentFee;

final class FeeService
{
    /**
     * Create a fee invoice for a student.
     *
     * @param  array{title: string, amount: float|int|string, academic_year_id?: int|null, course_id?: int|null, due_date?: string|null}  $data
     */
    public function createInvoice(Student $student, array $data): StudentFee
    {
        return StudentFee::create([
            'student_id' => $student->getKey(),
            'academic_year_id' => $data['academic_year_id'] ?? null,
            'course_id' => $data['course_id'] ?? null,
            'invoice_no' => $this->nextInvoiceNumber(),
            'title' => $data['title'],
            'amount' => $data['amount'],
            'due_date' => $data['due_date'] ?? null,
            'status' => FeeStatus::Unpaid->value,
        ]);
    }

    /**
     * Recompute paid amount and status from confirmed payments.
     */
    public function refreshStatus(StudentFee $fee): StudentFee
    {
        $paid = (float) $fee->payments()
            ->where('status', 'confirmed')
            ->sum('amount');

        $fee->paid_amount = $paid;

        if ($paid >= (float) $fee->amount) {
            $fee->status = FeeStatus::Paid->value;
        } elseif ($paid > 0) {
            $fee->status = FeeStatus::Partial->value;
        } else {
            $fee->status = FeeStatus::Unpaid->value;
        }

        $fee->save();

        return $fee;
    }

    /**
     * Statement for one student, optionally limited to an academic year.
     */
    public function statement(int $studentId, ?int $academicYearId = null): array
    {
        $query = StudentFee::query()->where('student_id', $studentId);

        if ($academicYearId !== null) {
            $query->where('academic_year_id', $academicYearId);
        }

        $fees = $query->with('payments')->orderBy('due_date')->get();

        return [
            'total_amount' => (float) $fees->sum('amount'),
            'total_paid' => (float) $fees->sum('paid_amount'),
            'outstanding_balance' => round((float) $fees->sum('amount') - (float) $fees->sum('paid_amount'), 2),
            'fees' => $fees,
        ];
    }

    public function nextInvoiceNumber(): string
    {
        $year = now()->year;

        return sprintf('INV-%d-%06d', $year, StudentFee::withTrashed()->count() + 1);
    }
}

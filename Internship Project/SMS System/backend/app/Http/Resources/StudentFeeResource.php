<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentFeeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student' => $this->whenLoaded('student', fn () => [
                'id' => $this->student->id,
                'name' => $this->student->user?->name ?? null,
                'student_code' => $this->student->student_code,
            ]),
            'invoice_no' => $this->invoice_no,
            'title' => $this->title,
            'academic_year' => $this->whenLoaded('academicYear', fn () => $this->academicYear?->name),
            'amount' => $this->amount,
            'paid_amount' => $this->paid_amount,
            'balance' => $this->balance(),
            'status' => $this->status?->value,
            'due_date' => $this->due_date?->toDateString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}

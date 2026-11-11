<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GradeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'enrollment_id' => $this->enrollment_id,
            'letter_grade' => $this->letter_grade,
            'points' => $this->points,
            'credit_hours' => $this->credit_hours,
            'remarks' => $this->remarks,
            'enrollment' => $this->whenLoaded('enrollment', fn () => [
                'id' => $this->enrollment->id,
                'student' => $this->enrollment->student ? [
                    'id' => $this->enrollment->student->id,
                    'student_code' => $this->enrollment->student->student_code,
                    'name' => $this->enrollment->student->user?->name ?? null,
                ] : null,
                'course' => $this->enrollment->course ? [
                    'id' => $this->enrollment->course->id,
                    'name' => $this->enrollment->course->name,
                    'code' => $this->enrollment->course->code,
                ] : null,
            ]),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'course' => $this->whenLoaded('course', fn () => ['id' => $this->course->id, 'name' => $this->course->name]),
            'student' => $this->whenLoaded('student', fn () => [
                'id' => $this->student->id,
                'name' => $this->student->user?->name ?? null,
                'student_code' => $this->student->student_code,
            ]),
            'date' => $this->date?->toDateString(),
            'status' => $this->status?->value,
            'remark' => $this->remark,
        ];
    }
}

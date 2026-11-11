<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseEnrollmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course' => $this->whenLoaded('course', fn () => [
                'id' => $this->course->id,
                'name' => $this->course->name,
                'code' => $this->course->code,
                'type' => $this->course->type?->value,
            ]),
            'student' => $this->whenLoaded('student', fn () => new StudentResource($this->student)),
            'enrolled_on' => $this->enrolled_on?->toDateString(),
            'grade' => $this->whenLoaded('grade', fn () => $this->grade ? new GradeResource($this->grade) : null),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}

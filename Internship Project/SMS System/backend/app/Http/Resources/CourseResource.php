<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $enrolledCount = $this->whenCounted('students');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'type' => $this->type?->value,
            'fee' => $this->fee,
            'capacity' => $this->capacity,
            'status' => $this->status?->value,
            'featured' => $this->featured,
            'is_published' => $this->is_published,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'academic_year' => $this->whenLoaded('academicYear', fn () => [
                'id' => $this->academicYear->id,
                'name' => $this->academicYear->name,
            ]),
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ]),
            'subject' => $this->whenLoaded('subject', fn () => [
                'id' => $this->subject->id,
                'name' => $this->subject->name,
            ]),
            'teacher' => $this->whenLoaded('teacher', fn () => [
                'id' => $this->teacher->id,
                'name' => $this->teacher->user?->name ?? null,
            ]),
            'enrolled_count' => $enrolledCount ?? null,
            'seats_remaining' => is_int($enrolledCount) && $this->capacity > 0 ? max(0, $this->capacity - $enrolledCount) : null,
            'schedules' => ScheduleResource::collection($this->whenLoaded('schedules')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}

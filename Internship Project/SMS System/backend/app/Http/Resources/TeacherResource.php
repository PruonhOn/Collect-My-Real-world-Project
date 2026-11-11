<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'employee_code' => $this->employee_code,
            'name' => $this->whenLoaded('user', fn () => $this->user->name, null),
            'email' => $this->whenLoaded('user', fn () => $this->user->email, null),
            'phone' => $this->whenLoaded('user', fn () => $this->user->phone, null),
            'is_active' => $this->whenLoaded('user', fn () => $this->user->is_active, null),
            'hire_date' => $this->hire_date?->toDateString(),
            'qualification' => $this->qualification,
            'specialization' => $this->specialization,
            'bio' => $this->bio,
            'courses_count' => $this->whenCounted('courses'),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}

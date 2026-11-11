<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'student_code' => $this->student_code,
            'name' => $this->whenLoaded('user', fn () => $this->user->name, null),
            'email' => $this->whenLoaded('user', fn () => $this->user->email, null),
            'phone' => $this->whenLoaded('user', fn () => $this->user->phone, null),
            'is_active' => $this->whenLoaded('user', fn () => $this->user->is_active, null),
            'khmer_name' => $this->khmer_name,
            'gender' => $this->gender?->value,
            'birth_date' => $this->birth_date?->toDateString(),
            'contact_phone' => $this->contact_phone,
            'guardian_name' => $this->guardian_name,
            'guardian_phone' => $this->guardian_phone,
            'address' => $this->address,
            'status' => $this->status?->value,
            'enrolled_on' => $this->enrolled_on?->toDateString(),
            'courses_count' => $this->whenCounted('courses'),
            'parents' => ParentResource::collection($this->whenLoaded('parents')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}

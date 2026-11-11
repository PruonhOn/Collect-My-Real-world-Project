<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffResource extends JsonResource
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
            'position' => $this->position,
            'department' => $this->department,
            'hire_date' => $this->hire_date?->toDateString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'is_active' => $this->is_active,
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),
            'primary_role' => $this->primaryRole(),
            'profile' => $this->getProfileResource($request),
            'avatar_url' => $this->whenLoaded('avatar', fn () => $this->avatar ? url('storage/'.$this->avatar->path) : null),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    private function getProfileResource(Request $request): ?array
    {
        if (! $this->relationLoaded('teacher') && ! $this->relationLoaded('student') && ! $this->relationLoaded('staff') && ! $this->relationLoaded('parentProfile')) {
            return null;
        }

        if (($teacher = $this->teacher) !== null) {
            return (new TeacherResource($teacher))->resolve($request);
        }

        if (($student = $this->student) !== null) {
            return (new StudentResource($student))->resolve($request);
        }

        if (($staff = $this->staff) !== null) {
            return (new StaffResource($staff))->resolve($request);
        }

        if (($parent = $this->parentProfile) !== null) {
            return (new ParentResource($parent))->resolve($request);
        }

        return null;
    }
}

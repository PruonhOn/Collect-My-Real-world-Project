<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->avatar,
            'locale' => $this->locale,
            'status' => $this->status,
            'is_vip' => $this->isVipActive(),
            'vip_until' => $this->vip_until?->toIso8601String(),
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

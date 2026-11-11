<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => $this->whenLoaded('user', fn () => $this->user?->name),
            'action' => $this->action,
            'resource_type' => $this->resource_type,
            'resource_id' => $this->resource_id,
            'ip_address' => $this->ip_address,
            'before' => $this->before,
            'after' => $this->after,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}

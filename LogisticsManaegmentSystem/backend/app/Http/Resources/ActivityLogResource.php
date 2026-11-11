<?php

namespace App\Http\Resources;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One activity log entry: who did what, to what, and when.
 */
class ActivityLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ActivityLog $log */
        $log = $this->resource;

        return [
            'id' => $log->id,
            'action' => $log->action,
            'causer_type' => $log->causer_type,
            'causer_id' => $log->causer_id,
            'subject_type' => $log->subject_type,
            'subject_id' => $log->subject_id,
            'properties' => $log->properties,
            'ip_address' => $log->ip_address,
            'user_agent' => $log->user_agent,
            'created_at' => $log->created_at?->toIso8601String(),
        ];
    }
}

<?php

namespace App\Http\Resources;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A notification, with its data payload and read state.
 */
class NotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Notification $notification */
        $notification = $this->resource;

        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'title' => $notification->title,
            'body' => $notification->body,
            'data' => $notification->data,
            'read_at' => $notification->read_at?->toIso8601String(),
            'is_unread' => $notification->read_at === null,
            'created_at' => $notification->created_at?->toIso8601String(),
            'updated_at' => $notification->updated_at?->toIso8601String(),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'book_id' => $this->book_id,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
        ];

        if ($this->relationLoaded('user')) {
            $data['user'] = [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
            ];
        }

        return $data;
    }
}

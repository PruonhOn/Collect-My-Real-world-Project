<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'params' => $this->params,
            'status' => $this->status,
            'file_url' => $this->whenLoaded('file', fn () => $this->file ? url('storage/'.$this->file->path) : null),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}

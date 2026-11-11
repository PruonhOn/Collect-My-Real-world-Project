<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssessmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'title' => $this->title,
            'type' => $this->type?->value,
            'max_score' => $this->max_score,
            'weight' => $this->weight,
            'date' => $this->date?->toDateString(),
            'scores_count' => $this->whenCounted('scores'),
        ];
    }
}

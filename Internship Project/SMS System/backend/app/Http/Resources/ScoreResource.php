<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScoreResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'assessment' => $this->whenLoaded('assessment', fn () => [
                'id' => $this->assessment->id,
                'title' => $this->assessment->title,
                'type' => $this->assessment->type?->value,
                'max_score' => $this->assessment->max_score,
            ]),
            'student' => $this->whenLoaded('student', fn () => [
                'id' => $this->student->id,
                'name' => $this->student->user?->name ?? null,
                'student_code' => $this->student->student_code,
            ]),
            'score' => $this->score,
            'remark' => $this->remark,
        ];
    }
}

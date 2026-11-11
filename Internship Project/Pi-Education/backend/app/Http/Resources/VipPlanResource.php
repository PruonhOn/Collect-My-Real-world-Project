<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VipPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $translation = $this->translation($request->header('X-Locale', app()->getLocale()));

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $translation?->name ?? $this->slug,
            'description' => $translation?->description,
            'price' => (float) $this->price,
            'duration_days' => $this->duration_days,
            'features' => $this->features ?? [],
            'sort_order' => $this->sort_order,
            'translations' => $this->whenLoaded('translations', fn () => $this->translations->mapWithKeys(fn ($translation) => [
                $translation->locale => [
                    'name' => $translation->name,
                    'description' => $translation->description,
                ],
            ])),
        ];
    }
}

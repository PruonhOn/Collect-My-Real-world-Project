<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminBookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'type' => $this->type,
            'price' => (float) $this->price,
            'status' => $this->status,
            'cover_image' => $this->cover_image,
            'language' => $this->language,
            'reader_count' => $this->reader_count,
            'published_at' => $this->published_at?->toIso8601String(),
            'category_ids' => $this->whenLoaded('categories', fn () => $this->categories->pluck('id')),
            'author_ids' => $this->whenLoaded('authors', fn () => $this->authors->pluck('id')),
            'translations' => $this->whenLoaded('translations', fn () => $this->translations->mapWithKeys(function ($translation) {
                return [$translation->locale => [
                    'title' => $translation->title,
                    'description' => $translation->description,
                    'content' => $translation->content,
                    'meta_title' => $translation->meta_title,
                    'meta_description' => $translation->meta_description,
                ]];
            })),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

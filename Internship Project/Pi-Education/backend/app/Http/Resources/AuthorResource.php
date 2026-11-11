<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $translation = $this->translation($request->header('X-Locale', app()->getLocale()));

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $translation?->name,
            'biography' => $translation?->biography,
            'image' => $this->image,
            'locale' => $translation?->locale,
            'available_locales' => $this->translations->pluck('locale'),
            'translations' => $this->whenLoaded('translations', fn () => $this->translations->mapWithKeys(fn ($translation) => [
                $translation->locale => [
                    'name' => $translation->name,
                    'biography' => $translation->biography,
                ],
            ])),
            'books_count' => $this->whenCounted('books'),
        ];
    }
}

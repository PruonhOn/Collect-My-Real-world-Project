<?php

namespace App\Http\Resources;

use App\Services\BookAccessService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = $request->header('X-Locale', app()->getLocale());
        $translation = $this->translation($locale);

        $data = [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $translation?->title,
            'description' => $translation?->description,
            'type' => $this->type,
            'price' => (float) $this->price,
            'status' => $this->status,
            'cover_image' => $this->cover_image,
            'language' => $this->language,
            'locale' => $translation?->locale,
            'available_locales' => $this->translations->pluck('locale'),
            'published_at' => $this->published_at?->toIso8601String(),
            'reader_count' => $this->reader_count,
            'avg_rating' => $this->when($this->avg_rating !== null, fn () => round((float) $this->avg_rating, 2)),
            'reviews_count' => $this->whenCounted('reviews_count'),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'authors' => AuthorResource::collection($this->whenLoaded('authors')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];

        $user = $request->user('sanctum');

        if ($user) {
            $data['has_access'] = app(BookAccessService::class)->canRead($user, $this->resource);
            $data['is_wishlisted'] = $this->whenLoaded('wishlistedBy', fn () => $this->wishlistedBy->isNotEmpty());
        }

        return $data;
    }
}

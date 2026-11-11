<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Tag;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Keeps a book's tag labels in sync with the shared tag vocabulary.
 *
 * Tags are accepted loosely from the client (a comma separated string, a JSON
 * array, or a repeated `tags[]` field) because the form posts multipart data,
 * then normalised into unique, length-capped labels before being persisted.
 */
class BookTagService
{
    /** Upper bound on tags per book, to keep the pivot and UI sane. */
    public const MAX_TAGS = 12;

    /** Upper bound on a single tag label. */
    public const MAX_TAG_LENGTH = 40;

    /**
     * Replace a book's tags with the given labels, creating any that are new.
     *
     * @param  mixed  $input  Comma separated string, JSON array or array of labels.
     */
    public function sync(Book $book, mixed $input): void
    {
        $ids = $this->normalize($input)
            ->map(fn (string $name) => Tag::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name],
            )->id)
            ->all();

        $book->tags()->sync($ids);
    }

    /**
     * Reduce arbitrary client input to a de-duplicated list of tag labels.
     *
     * @return Collection<int, string>
     */
    public function normalize(mixed $input): Collection
    {
        return collect($this->flatten($input))
            ->filter(fn ($tag) => is_string($tag) || is_numeric($tag))
            ->map(fn ($tag) => trim((string) $tag))
            ->filter()
            ->map(fn (string $tag) => Str::limit($tag, self::MAX_TAG_LENGTH, ''))
            ->unique(fn (string $tag) => Str::slug($tag))
            ->values()
            ->take(self::MAX_TAGS);
    }

    /**
     * @return array<int, mixed>
     */
    private function flatten(mixed $input): array
    {
        if (is_string($input)) {
            $decoded = json_decode($input, true);
            $input = is_array($decoded) ? $decoded : explode(',', $input);
        }

        return is_array($input) ? $input : [];
    }
}

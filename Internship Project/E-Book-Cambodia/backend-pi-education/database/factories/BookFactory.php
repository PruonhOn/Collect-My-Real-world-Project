<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'category_id' => Category::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(4)),
            'description' => fake()->paragraphs(2, true),
            'author' => fake()->name(),
            'language' => 'km',
            'pages' => fake()->numberBetween(50, 600),
            'price' => fake()->randomElement([0, 2.99, 4.99, 7.99, 9.99]),
            'is_free' => false,
            'is_coming_soon' => false,
            'file_size' => fake()->numberBetween(200000, 5000000),
            'file_type' => 'application/pdf',
            // Live by default so the existing catalog and reader tests keep
            // their meaning; the workflow states below opt out of that.
            'status' => Book::STATUS_PUBLISHED,
            'published_at' => now(),
        ];
    }

    /**
     * A book sitting in the author's workspace, not yet sent for review.
     */
    public function draft(): static
    {
        return $this->state(fn () => ['status' => Book::STATUS_DRAFT]);
    }

    /**
     * A book an author has submitted and is waiting on an admin decision for.
     */
    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => Book::STATUS_PENDING,
            'submitted_at' => now(),
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => Book::STATUS_APPROVED,
            'reviewed_at' => now(),
        ]);
    }

    /**
     * A book sent back to the author, with the reason they have to act on.
     */
    public function rejected(string $note = 'Please fix the metadata and resubmit.'): static
    {
        return $this->state(fn () => [
            'status' => Book::STATUS_REJECTED,
            'submitted_at' => now(),
            'reviewed_at' => now(),
            'review_note' => $note,
        ]);
    }

    /**
     * A live book. This is the factory default so the existing catalog tests
     * keep working; is_published is derived from the status on save.
     */
    public function published(): static
    {
        return $this->state(fn () => [
            'status' => Book::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
    }

    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => 0,
            'is_free' => true,
        ]);
    }

    /**
     * Readable only with an active VIP membership - not by purchase, shared
     * access, or the free flag.
     */
    public function vipOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_vip_only' => true,
        ]);
    }

    /**
     * A book that offers its leading pages as a free preview.
     */
    public function preview(int $pages = 3): static
    {
        return $this->state(fn (array $attributes) => [
            'preview_mode' => Book::PREVIEW_MODE_PREVIEW,
            'preview_pages' => $pages,
        ]);
    }

    /**
     * A book that offers its leading pages as a trial read.
     */
    public function trialRead(int $pages = 3): static
    {
        return $this->state(fn (array $attributes) => [
            'preview_mode' => Book::PREVIEW_MODE_READTEST,
            'preview_pages' => $pages,
        ]);
    }

    /**
     * A book with plain-text content split into reader pages by `===`.
     */
    public function withContent(int $sections = 5): static
    {
        return $this->state(fn (array $attributes) => [
            'content' => collect(range(1, $sections))
                ->map(fn (int $number): string => "Section {$number}\n\nSample reading content for section {$number}.")
                ->implode("\n\n===\n\n"),
            'pages' => $sections,
        ]);
    }

    /**
     * A book that is announced but not readable yet.
     */
    public function comingSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_coming_soon' => true,
        ]);
    }
}

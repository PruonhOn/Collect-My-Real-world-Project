<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\Purchase;
use Database\Seeders\DemoBookCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoBookCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_the_demo_catalogue_publishes_a_full_shelf(): void
    {
        $this->seed(DemoBookCatalogSeeder::class);

        $books = Book::query()->where('slug', 'like', 'demo-%')->get();

        $this->assertGreaterThanOrEqual(100, $books->count());

        foreach ($books as $book) {
            $this->assertTrue($book->is_published, $book->slug.' should be published.');
            $this->assertSame(Book::STATUS_PUBLISHED, $book->status, $book->slug.' should be published.');
            $this->assertNotNull($book->category_id, $book->slug.' should belong to a category.');
            $this->assertNotNull($book->published_at, $book->slug.' should have a published date.');
            $this->assertNull($book->author_id, $book->slug.' should not belong to an author.');
            $this->assertNull($book->file_path, $book->slug.' should not point at a document.');
            $this->assertNotEmpty($book->content, $book->slug.' should have reader content.');
            $this->assertSame($book->pages, $this->pageCount($book->content), $book->slug.' should have one page per section.');
        }
    }

    public function test_the_demo_catalogue_covers_every_shelf(): void
    {
        $this->seed(DemoBookCatalogSeeder::class);

        $categories = Book::query()
            ->where('slug', 'like', 'demo-%')
            ->pluck('category_id')
            ->map(fn (int $categoryId): string => (string) Category::find($categoryId)?->name)
            ->unique()
            ->sort()
            ->values();

        foreach (['Math - Grade 7', 'Khmer - Grade 3', 'Primary', 'Lower Secondary', 'Upper Secondary School', 'Competitive Exams'] as $expected) {
            $this->assertContains($expected, $categories->all());
        }

        $this->assertGreaterThanOrEqual(25, $categories->count());
    }

    public function test_the_demo_catalogue_uses_unique_slugs(): void
    {
        $this->seed(DemoBookCatalogSeeder::class);

        $slugs = Book::query()->where('slug', 'like', 'demo-%')->pluck('slug');

        $this->assertSame($slugs->count(), $slugs->unique()->count());
        $this->assertSame($slugs->count(), $slugs->filter(fn (string $slug): bool => str_starts_with($slug, 'demo-'))->count());
    }

    public function test_the_demo_catalogue_is_idempotent(): void
    {
        $this->seed(DemoBookCatalogSeeder::class);

        $before = Book::query()->where('slug', 'like', 'demo-%')->get()
            ->mapWithKeys(fn (Book $book): array => [$book->slug => $book->published_at?->timestamp]);

        $this->seed(DemoBookCatalogSeeder::class);

        $after = Book::query()->where('slug', 'like', 'demo-%')->get()
            ->mapWithKeys(fn (Book $book): array => [$book->slug => $book->published_at?->timestamp]);

        $this->assertSame($before->count(), $after->count());
        $this->assertSame($before->all(), $after->all());
    }

    public function test_the_demo_catalogue_keeps_the_access_flags_consistent(): void
    {
        $this->seed(DemoBookCatalogSeeder::class);

        $books = Book::query()->where('slug', 'like', 'demo-%')->get();

        foreach ($books as $book) {
            if ($book->is_vip_only) {
                $this->assertFalse($book->is_free, $book->slug.' is VIP, so it cannot also be free.');
                $this->assertSame(0.0, (float) $book->price, $book->slug.' is VIP, so it cannot cost anything.');

                continue;
            }

            if ($book->is_free) {
                $this->assertSame(0.0, (float) $book->price, $book->slug.' is free, so it cannot cost anything.');
                $this->assertSame(Book::PREVIEW_MODE_NONE, $book->preview_mode, $book->slug.' is free, so it needs no gate.');

                continue;
            }

            $this->assertTrue((float) $book->price > 0, $book->slug.' is paid, so it needs a price.');
            $this->assertNotSame(Book::PREVIEW_MODE_NONE, $book->preview_mode, $book->slug.' is paid, so it needs a gate.');
            $this->assertGreaterThan(0, $book->preview_pages, $book->slug.' is paid, so it needs preview pages.');
            $this->assertLessThanOrEqual($book->pages, $book->preview_pages);
        }
    }

    public function test_the_demo_catalogue_never_grants_purchases_or_memberships(): void
    {
        $this->seed(DemoBookCatalogSeeder::class);

        $this->assertSame(0, Purchase::query()->count());
    }

    public function test_the_demo_catalogue_is_served_by_the_public_api(): void
    {
        $this->seed(DemoBookCatalogSeeder::class);

        $book = Book::query()->where('slug', 'like', 'demo-%')->firstOrFail();

        $this->getJson('/api/v1/books?search='.$book->title)
            ->assertOk()
            ->assertJsonFragment(['slug' => $book->slug]);

        $this->getJson("/api/v1/books/{$book->id}")
            ->assertOk()
            ->assertJsonPath('book.slug', $book->slug)
            ->assertJsonPath('book.is_published', true);
    }

    public function test_a_free_demo_book_is_readable_in_full(): void
    {
        $this->seed(DemoBookCatalogSeeder::class);

        $book = Book::query()->where('slug', 'like', 'demo-%')->where('is_free', true)->firstOrFail();

        $response = $this->getJson("/api/v1/reader/{$book->id}")->assertOk();

        $this->assertSame($book->content, $response->json('book.content'));
    }

    public function test_a_paid_demo_book_only_gives_a_preview_to_a_guest(): void
    {
        $this->seed(DemoBookCatalogSeeder::class);

        $book = Book::query()
            ->where('slug', 'like', 'demo-%')
            ->where('is_free', false)
            ->where('is_vip_only', false)
            ->firstOrFail();

        $response = $this->getJson("/api/v1/reader/{$book->id}")->assertOk();

        $this->assertSame('preview', $response->json('access.mode'));
        $this->assertSame($book->preview_pages, $response->json('access.preview_pages'));
        $this->assertSame(
            $book->preview_pages,
            $this->pageCount((string) $response->json('book.content')),
            'A preview must stop at the preview pages.'
        );
    }

    public function test_a_vip_demo_book_only_gives_a_guest_the_readtest_trial(): void
    {
        $this->seed(DemoBookCatalogSeeder::class);

        $book = Book::query()->where('slug', 'like', 'demo-%')->where('is_vip_only', true)->firstOrFail();

        $response = $this->getJson("/api/v1/reader/{$book->id}")->assertOk();

        $this->assertSame('preview', $response->json('access.mode'));
        $this->assertSame(Book::PREVIEW_MODE_READTEST, $response->json('access.preview_mode'));
        $this->assertFalse($response->json('access.can_read'));
        $this->assertSame(
            $book->preview_pages,
            $this->pageCount((string) $response->json('book.content')),
            'A VIP book must never leak its full content to a guest.',
        );
    }

    public function test_a_coming_soon_demo_book_cannot_be_read(): void
    {
        $this->seed(DemoBookCatalogSeeder::class);

        $book = Book::query()->where('slug', 'like', 'demo-%')->where('is_coming_soon', true)->firstOrFail();

        $this->getJson("/api/v1/reader/{$book->id}")->assertStatus(403);
    }

    /**
     * The number of reader pages a piece of content produces.
     */
    private function pageCount(string $content): int
    {
        $sections = preg_split('/\n{1,}\s*={3,}\s*\n{1,}/u', trim($content), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return count($sections);
    }
}

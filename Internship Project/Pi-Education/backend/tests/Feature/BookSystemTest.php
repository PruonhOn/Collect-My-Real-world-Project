<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_guest_can_list_published_books(): void
    {
        $response = $this->withHeader('X-Locale', 'en')->getJson('/api/v1/books');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'books',
                    'pagination' => ['current_page', 'last_page', 'per_page', 'total'],
                ],
            ]);
    }

    public function test_books_list_excludes_non_published(): void
    {
        Book::factory()->create(['status' => 'DRAFT', 'slug' => 'draft-book']);
        Book::factory()->create(['status' => 'ARCHIVED', 'slug' => 'archived-book']);

        $response = $this->getJson('/api/v1/books');

        $response->assertStatus(200);
        collect($response->json('data.books'))->each(function ($book) {
            $this->assertSame('PUBLISHED', $book['status']);
        });
    }

    public function test_guest_can_filter_books_by_type_and_category(): void
    {
        $paid = $this->getJson('/api/v1/books?type=PAID');

        $paid->assertStatus(200);
        collect($paid->json('data.books'))->each(fn ($book) => $this->assertSame('PAID', $book['type']));

        $category = Category::first();

        $filtered = $this->getJson('/api/v1/books?category='.$category->slug);
        $filtered->assertStatus(200);
        collect($filtered->json('data.books'))->each(function ($book) use ($category) {
            $slugs = collect($book['categories'])->pluck('slug');
            $this->assertTrue($slugs->contains($category->slug));
        });
    }

    public function test_guest_can_search_books(): void
    {
        $response = $this->withHeader('X-Locale', 'en')->getJson('/api/v1/books?search=algebra');

        $response->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1);

        $books = $response->json('data.books');
        $this->assertStringContainsString('Algebra', $books[0]['title']);
    }

    public function test_top_rated_sort_puts_highest_rated_first_and_unrated_last(): void
    {
        $books = Book::query()->where('status', Book::STATUS_PUBLISHED)->orderBy('id')->get();
        [$ratedLow, $ratedHigh, $unrated] = [$books[0], $books[1], $books[2]];

        Review::factory()->create(['book_id' => $ratedLow->id, 'status' => Review::STATUS_APPROVED, 'rating' => 2]);
        Review::factory()->create(['book_id' => $ratedHigh->id, 'status' => Review::STATUS_APPROVED, 'rating' => 5]);

        $ids = collect($this->getJson('/api/v1/books?sort=top_rated&per_page=50')->json('data.books'))
            ->pluck('id')->all();

        $posHigh = array_search($ratedHigh->id, $ids, true);
        $posLow = array_search($ratedLow->id, $ids, true);
        $posUnrated = array_search($unrated->id, $ids, true);

        $this->assertLessThan($posLow, $posHigh);
        $this->assertLessThan($posUnrated, $posHigh);
        $this->assertLessThan($posUnrated, $posLow);
    }

    public function test_guest_can_view_book_detail_by_slug(): void
    {
        $book = Book::where('slug', 'algebra-basics')->first();

        $response = $this
            ->withHeader('X-Locale', 'en')
            ->getJson('/api/v1/books/'.$book->slug);

        $response->assertStatus(200)
            ->assertJsonPath('data.slug', 'algebra-basics')
            ->assertJsonPath('data.type', 'FREE')
            ->assertJsonStructure([
                'data' => ['authors', 'categories'],
            ]);
    }

    public function test_book_detail_404_for_missing_book(): void
    {
        $this->getJson('/api/v1/books/non-existent')->assertStatus(404);
    }

    public function test_guest_can_list_categories(): void
    {
        $response = $this->getJson('/api/v1/categories');

        $response->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 8);
    }

    public function test_guest_can_view_category_with_books(): void
    {
        $slug = Category::where('slug', 'mathematics')->value('slug');

        $response = $this->withHeader('X-Locale', 'en')->getJson('/api/v1/categories/'.$slug);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Mathematics');

        $this->assertGreaterThan(0, count($response->json('data.books')));
    }

    public function test_guest_can_list_authors(): void
    {
        $response = $this->getJson('/api/v1/authors');

        $response->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 5);
    }

    public function test_book_translations_are_language_aware(): void
    {
        $book = Book::where('slug', 'algebra-basics')->first();

        $en = $this->withHeader('X-Locale', 'en')->getJson('/api/v1/books/'.$book->slug);
        $en->assertJsonPath('data.title', 'Algebra Basics');

        $km = $this->withHeader('X-Locale', 'km')->getJson('/api/v1/books/'.$book->slug);
        $this->assertNotNull($km->json('data.title'));
        $this->assertEquals('km', $km->json('data.locale'));

        $zh = $this->withHeader('X-Locale', 'zh')->getJson('/api/v1/books/'.$book->slug);
        $this->assertEquals('zh', $zh->json('data.locale'));
    }

    public function test_book_listing_does_not_expose_content(): void
    {
        $response = $this->getJson('/api/v1/books');

        collect($response->json('data.books'))->each(function ($book) {
            $this->assertArrayNotHasKey('content', $book);
        });
    }

    public function test_soft_deleted_books_are_hidden(): void
    {
        $book = Book::where('slug', 'algebra-basics')->first();
        $book->delete();

        $this->getJson('/api/v1/books/'.$book->slug)->assertStatus(404);
    }
}

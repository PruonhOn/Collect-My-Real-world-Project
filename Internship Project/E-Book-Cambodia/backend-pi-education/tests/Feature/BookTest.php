<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\Purchase;
use App\Models\ReadingProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    // --- Public listing ---

    public function test_anyone_can_list_published_books(): void
    {
        Book::factory()->count(5)->create();

        $this->getJson('/api/v1/books')
            ->assertOk()
            ->assertJsonCount(5, 'data');
    }

    public function test_unpublished_books_are_not_listed(): void
    {
        Book::factory()->create(['is_published' => false]);
        Book::factory()->create(['is_published' => true]);

        $response = $this->getJson('/api/v1/books');
        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_search_by_title(): void
    {
        Book::factory()->create(['title' => 'The Great Gatsby', 'is_published' => true]);
        Book::factory()->create(['title' => 'Another Book', 'is_published' => true]);

        $this->getJson('/api/v1/books?search=Gatsby')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_search_by_author(): void
    {
        Book::factory()->create(['author' => 'Author Smith', 'is_published' => true]);
        Book::factory()->create(['author' => 'Other Author', 'is_published' => true]);

        $this->getJson('/api/v1/books?search=Smith')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_authors_returns_distinct_author_list(): void
    {
        Book::factory()->create(['author' => 'Author Smith']);
        Book::factory()->create(['author' => 'Author Smith']);
        Book::factory()->create(['author' => 'Other Author']);
        Book::factory()->create(['author' => null]);
        Book::factory()->create(['author' => '']);

        $this->getJson('/api/v1/authors')
            ->assertOk()
            ->assertJsonPath('authors', ['Author Smith', 'Other Author']);
    }

    public function test_filter_by_category(): void
    {
        $cat = Category::create(['name' => 'Fiction', 'slug' => 'fiction-abc']);
        $otherCat = Category::create(['name' => 'Science', 'slug' => 'science-abc']);
        Book::factory()->create(['category_id' => $cat->id, 'is_published' => true]);
        Book::factory()->create(['category_id' => $otherCat->id, 'is_published' => true]);

        $this->getJson("/api/v1/books?category={$cat->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_book_detail_returns_related_books(): void
    {
        $cat = Category::create(['name' => 'History', 'slug' => 'history-1']);
        $book = Book::factory()->create(['category_id' => $cat->id, 'is_published' => true]);
        $related = Book::factory()->create(['category_id' => $cat->id, 'is_published' => true]);

        $this->getJson("/api/v1/books/{$book->id}")
            ->assertOk()
            ->assertJsonPath('book.id', $book->id)
            ->assertJsonCount(1, 'related');
    }

    public function test_related_books_are_ordered_newest_first(): void
    {
        $cat = Category::create(['name' => 'Physics', 'slug' => 'physics-related']);
        $book = Book::factory()->create(['category_id' => $cat->id, 'is_published' => true]);
        $older = Book::factory()->create(['category_id' => $cat->id, 'is_published' => true]);
        $newest = Book::factory()->create(['category_id' => $cat->id, 'is_published' => true]);

        $this->getJson("/api/v1/books/{$book->id}")
            ->assertOk()
            ->assertJsonPath('related.0.id', $newest->id)
            ->assertJsonPath('related.1.id', $older->id);
    }

    public function test_unpublished_book_detail_returns_404(): void
    {
        $book = Book::factory()->create(['is_published' => false]);

        $this->getJson("/api/v1/books/{$book->id}")->assertStatus(404);
    }

    public function test_book_list_includes_current_users_reading_progress(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['is_published' => true, 'pages' => 200]);

        ReadingProgress::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'last_page' => 42,
            'total_pages' => 200,
            'progress' => 21.0,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/books')
            ->assertOk()
            ->assertJsonPath('data.0.reading_progress.last_page', 42)
            ->assertJsonPath('data.0.reading_progress.total_pages', 200)
            ->assertJsonPath('data.0.reading_progress.percent', 21);
    }

    public function test_other_users_reading_progress_is_not_leaked(): void
    {
        $reader = User::factory()->create();
        $viewer = User::factory()->create();
        $book = Book::factory()->create(['is_published' => true, 'pages' => 200]);

        ReadingProgress::create([
            'user_id' => $reader->id,
            'book_id' => $book->id,
            'last_page' => 42,
            'total_pages' => 200,
            'progress' => 21.0,
        ]);

        $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/books')
            ->assertOk()
            ->assertJsonPath('data.0.reading_progress', null);
    }

    // --- Admin CRUD ---

    public function test_admin_can_create_book(): void
    {
        $admin = User::factory()->admin()->create();
        $cat = Category::create(['name' => 'Art', 'slug' => 'art-1']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/books', [
                'category_id' => $cat->id,
                'title' => 'Test Art Book',
                'author' => 'Art Author',
                'price' => 9.99,
                'is_free' => false,
                'is_published' => true,
            ])
            ->assertStatus(201)
            ->assertJsonPath('book.title', 'Test Art Book');
    }

    public function test_reader_cannot_create_book(): void
    {
        $reader = User::factory()->create();
        $cat = Category::create(['name' => 'Tmp', 'slug' => 'tmp-cat']);

        $this->actingAs($reader, 'sanctum')
            ->postJson('/api/v1/admin/books', [
                'category_id' => $cat->id,
                'title' => 'Unauthorized Book',
            ])
            ->assertStatus(403);
    }

    public function test_admin_can_update_book(): void
    {
        $admin = User::factory()->admin()->create();
        $book = Book::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/books/{$book->id}", [
                'title' => 'Updated Title',
            ])
            ->assertOk()
            ->assertJsonPath('book.title', 'Updated Title');
    }

    public function test_admin_can_delete_book(): void
    {
        $admin = User::factory()->admin()->create();
        $book = Book::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/books/{$book->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Book deleted.');

        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }

    // --- Category admin ---

    public function test_admin_can_create_category(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/categories', [
                'name' => 'Philosophy',
            ])
            ->assertStatus(201)
            ->assertJsonPath('category.name', 'Philosophy');
    }

    public function test_admin_cannot_delete_category_with_books(): void
    {
        $admin = User::factory()->admin()->create();
        $cat = Category::create(['name' => 'Stuck', 'slug' => 'stuck-1']);
        Book::factory()->create(['category_id' => $cat->id]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/categories/{$cat->id}")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Cannot delete a category that still contains books.');
    }

    public function test_anyone_can_list_categories(): void
    {
        Category::create(['name' => 'Cat1', 'slug' => 'cat1']);
        Category::create(['name' => 'Cat2', 'slug' => 'cat2']);

        // The add_level_categories migration seeds four education levels
        // (Primary, Lower Secondary, Upper Secondary School, Competitive Exams).
        $this->getJson('/api/v1/categories')
            ->assertOk()
            ->assertJsonCount(6, 'categories');
    }

    public function test_sort_books_by_price_ascending(): void
    {
        Book::factory()->create(['price' => 9.99, 'is_published' => true]);
        Book::factory()->create(['price' => 1.99, 'is_published' => true]);

        $response = $this->getJson('/api/v1/books?sort=price_asc');
        $response->assertOk();
        $data = $response->json('data');
        $this->assertEquals(1.99, (float) $data[0]['price']);
    }

    public function test_every_sort_breaks_ties_on_id(): void
    {
        // "latest" ranks by when a book went live, not when it was uploaded.
        // See test_new_arrivals_are_ordered_by_publication_date for the why.
        $expected = [
            'latest' => 'order by COALESCE(published_at, created_at) DESC, `id` desc',
            'price_asc' => 'order by `price` asc, `id` desc',
            'price_desc' => 'order by `price` desc, `id` desc',
            'popular' => 'order by `view_count` desc, `id` desc',
            'not-a-real-sort' => 'order by `id` desc',
        ];

        foreach ($expected as $sort => $orderClause) {
            Book::factory()->create(['is_published' => true]);

            DB::flushQueryLog();
            DB::enableQueryLog();

            $this->getJson("/api/v1/books?sort={$sort}")->assertOk();

            $booksQuery = collect(DB::getQueryLog())
                ->pluck('query')
                ->map(fn ($sql) => str_replace('"', '`', $sql))
                ->first(fn ($sql) => str_contains($sql, 'from `books`') && str_contains($sql, 'order by'));

            DB::disableQueryLog();

            $this->assertStringContainsString($orderClause, (string) $booksQuery, "sort={$sort}");
        }
    }

    public function test_free_books_filter(): void
    {
        Book::factory()->free()->create();
        Book::factory()->create(['price' => 5.00, 'is_free' => false]);

        $this->getJson('/api/v1/books?free=1')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    // --- Purchased flag on the catalogue listing ---

    public function test_book_list_flags_books_the_current_user_purchased(): void
    {
        $user = User::factory()->create();
        $bought = Book::factory()->create(['is_published' => true, 'price' => 5.00]);
        $untouched = Book::factory()->create(['is_published' => true, 'price' => 5.00]);

        Purchase::create([
            'user_id' => $user->id,
            'book_id' => $bought->id,
            'amount' => 5.00,
            'status' => Purchase::STATUS_PAID,
            'activated_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/books');
        $response->assertOk();

        $flags = collect($response->json('data'))->keyBy('id');

        $this->assertTrue($flags[$bought->id]['is_purchased']);
        $this->assertFalse($flags[$untouched->id]['is_purchased']);
    }

    public function test_book_list_does_not_leak_other_users_purchases(): void
    {
        $buyer = User::factory()->create();
        $viewer = User::factory()->create();
        $book = Book::factory()->create(['is_published' => true, 'price' => 5.00]);

        Purchase::create([
            'user_id' => $buyer->id,
            'book_id' => $book->id,
            'amount' => 5.00,
            'status' => Purchase::STATUS_PAID,
            'activated_at' => now(),
        ]);

        $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/books')
            ->assertOk()
            ->assertJsonPath('data.0.is_purchased', false);
    }

    public function test_guest_book_list_marks_every_book_as_not_purchased(): void
    {
        Book::factory()->create(['is_published' => true]);

        $this->getJson('/api/v1/books')
            ->assertOk()
            ->assertJsonPath('data.0.is_purchased', false);
    }

    public function test_pending_purchase_does_not_flag_a_book_as_purchased(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['is_published' => true, 'price' => 5.00]);

        Purchase::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'amount' => 5.00,
            'status' => Purchase::STATUS_PENDING,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/books')
            ->assertOk()
            ->assertJsonPath('data.0.is_purchased', false);
    }

    public function test_owned_filter_returns_only_purchased_books(): void
    {
        $user = User::factory()->create();
        $bought = Book::factory()->create(['is_published' => true, 'price' => 5.00]);
        Book::factory()->create(['is_published' => true, 'price' => 5.00]);

        Purchase::create([
            'user_id' => $user->id,
            'book_id' => $bought->id,
            'amount' => 5.00,
            'status' => Purchase::STATUS_PAID,
            'activated_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/books?owned=1');
        $response->assertOk()->assertJsonCount(1, 'data');

        $this->assertSame($bought->id, $response->json('data.0.id'));
    }

    public function test_owned_filter_never_returns_unpublished_books(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['is_published' => false, 'price' => 5.00]);

        Purchase::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'amount' => 5.00,
            'status' => Purchase::STATUS_PAID,
            'activated_at' => now(),
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/books?owned=1')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_owned_filter_returns_nothing_for_guests(): void
    {
        $buyer = User::factory()->create();
        $book = Book::factory()->create(['is_published' => true, 'price' => 5.00]);

        Purchase::create([
            'user_id' => $buyer->id,
            'book_id' => $book->id,
            'amount' => 5.00,
            'status' => Purchase::STATUS_PAID,
            'activated_at' => now(),
        ]);

        $this->getJson('/api/v1/books?owned=1')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}

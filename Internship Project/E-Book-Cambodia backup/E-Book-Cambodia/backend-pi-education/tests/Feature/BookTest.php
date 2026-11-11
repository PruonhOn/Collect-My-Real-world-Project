<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_unpublished_book_detail_returns_404(): void
    {
        $book = Book::factory()->create(['is_published' => false]);

        $this->getJson("/api/v1/books/{$book->id}")->assertStatus(404);
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

        $this->getJson('/api/v1/categories')
            ->assertOk()
            ->assertJsonCount(2, 'categories');
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

    public function test_free_books_filter(): void
    {
        Book::factory()->free()->create();
        Book::factory()->create(['price' => 5.00, 'is_free' => false]);

        $this->getJson('/api/v1/books?free=1')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}

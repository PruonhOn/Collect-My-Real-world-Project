<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function paidBook(): Book
    {
        $book = Book::factory()->paid()->create();
        $book->translations()->create(['locale' => 'en', 'title' => 'Paid Book']);

        return $book;
    }

    public function test_public_can_list_approved_reviews(): void
    {
        $book = Book::query()->where('status', Book::STATUS_PUBLISHED)->firstOrFail();

        Review::factory()->count(3)->create(['book_id' => $book->id, 'status' => Review::STATUS_APPROVED]);
        Review::factory()->pending()->create(['book_id' => $book->id]);

        $response = $this->getJson('/api/v1/books/'.$book->id.'/reviews');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data.reviews')
            ->assertJsonStructure([
                'data' => [
                    'book' => ['id', 'slug', 'title', 'avg_rating', 'reviews_count'],
                    'reviews' => [],
                    'pagination' => [],
                    'my_review',
                ],
            ]);

        $this->assertEquals(3, $response->json('data.book.reviews_count'));
        $this->assertNotNull($response->json('data.book.avg_rating'));
    }

    public function test_paid_book_review_requires_access(): void
    {
        $book = $this->paidBook();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/reviews', ['book_id' => $book->id, 'rating' => 5, 'comment' => 'Great!'])
            ->assertStatus(403);

        $user->ownedBooks()->attach($book->id, ['amount' => 9.99, 'payment_method' => 'mock', 'transaction_id' => 'MOCK-123']);

        $this->actingAs($user)
            ->postJson('/api/v1/reviews', ['book_id' => $book->id, 'rating' => 5, 'comment' => 'Great!'])
            ->assertStatus(201)
            ->assertJsonPath('data.status', Review::STATUS_PENDING);
    }

    public function test_guest_cannot_review_free_book(): void
    {
        $book = Book::query()->where('status', Book::STATUS_PUBLISHED)->where('type', 'FREE')->firstOrFail();

        $this->postJson('/api/v1/reviews', ['book_id' => $book->id, 'rating' => 5])->assertStatus(401);
    }

    public function test_user_cannot_review_same_book_twice(): void
    {
        $book = $this->paidBook();
        $user = User::factory()->create();
        $user->ownedBooks()->attach($book->id, ['amount' => 9.99, 'payment_method' => 'mock', 'transaction_id' => 'MOCK-2']);

        $this->actingAs($user)
            ->postJson('/api/v1/reviews', ['book_id' => $book->id, 'rating' => 5])
            ->assertStatus(201);

        $this->actingAs($user)
            ->postJson('/api/v1/reviews', ['book_id' => $book->id, 'rating' => 4])
            ->assertStatus(422);
    }

    public function test_user_can_update_own_review_and_it_goes_pending(): void
    {
        $book = $this->paidBook();
        $user = User::factory()->create();
        $user->ownedBooks()->attach($book->id, ['amount' => 9.99, 'payment_method' => 'mock', 'transaction_id' => 'MOCK-3']);

        $review = Review::factory()->create(['user_id' => $user->id, 'book_id' => $book->id, 'status' => Review::STATUS_APPROVED]);

        $this->actingAs($user)
            ->putJson('/api/v1/reviews/'.$review->id, ['rating' => 2, 'comment' => 'Revised'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', Review::STATUS_PENDING)
            ->assertJsonPath('data.rating', 2);
    }

    public function test_user_cannot_edit_others_review(): void
    {
        $book = $this->paidBook();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $owner->id, 'book_id' => $book->id]);

        $this->actingAs($other)
            ->putJson('/api/v1/reviews/'.$review->id, ['rating' => 1])
            ->assertStatus(403);

        $this->actingAs($other)
            ->deleteJson('/api/v1/reviews/'.$review->id)
            ->assertStatus(403);
    }

    public function test_user_can_delete_own_review(): void
    {
        $book = $this->paidBook();
        $user = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $user->id, 'book_id' => $book->id]);

        $this->actingAs($user)->deleteJson('/api/v1/reviews/'.$review->id)->assertStatus(200);

        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }

    public function test_my_review_endpoint(): void
    {
        $book = $this->paidBook();
        $user = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $user->id, 'book_id' => $book->id]);

        $this->actingAs($user)
            ->getJson('/api/v1/reviews/mine/'.$book->id)
            ->assertStatus(200)
            ->assertJsonPath('data.review.id', $review->id);
    }

    public function test_books_list_includes_rating_aggregates(): void
    {
        $book = Book::query()->where('status', Book::STATUS_PUBLISHED)->firstOrFail();
        Review::factory()->count(2)->create(['book_id' => $book->id, 'status' => Review::STATUS_APPROVED, 'rating' => 4]);
        Review::factory()->pending()->create(['book_id' => $book->id]);

        $books = collect($this->getJson('/api/v1/books?per_page=50')->json('data.books'));
        $item = $books->firstWhere('id', $book->id);

        $this->assertSame('4', (string) $item['avg_rating']);
        $this->assertSame(2, $item['reviews_count']);
    }

    public function test_admin_can_moderate_reviews(): void
    {
        $admin = User::where('email', 'admin@pieducation.test')->firstOrFail();
        $book = $this->paidBook();
        $user = User::factory()->create();
        $review = Review::factory()->pending()->create(['user_id' => $user->id, 'book_id' => $book->id]);

        $this->actingAs($admin)
            ->getJson('/api/v1/admin/reviews')
            ->assertStatus(200)
            ->assertJsonStructure(['data' => ['reviews' => [], 'pagination' => []]]);

        $this->actingAs($admin)
            ->patchJson('/api/v1/admin/reviews/'.$review->id, ['status' => Review::STATUS_APPROVED])
            ->assertStatus(200);

        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'status' => Review::STATUS_APPROVED]);

        $this->actingAs($admin)
            ->deleteJson('/api/v1/admin/reviews/'.$review->id)
            ->assertStatus(200);
    }

    public function test_dashboard_includes_pending_reviews(): void
    {
        $admin = User::where('email', 'admin@pieducation.test')->firstOrFail();

        $response = $this->actingAs($admin)->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['counters' => ['pending_reviews']]]);
    }
}

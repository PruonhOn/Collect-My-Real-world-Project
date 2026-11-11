<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishlistSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_guest_cannot_use_wishlist(): void
    {
        $this->getJson('/api/v1/wishlist')->assertStatus(401);
        $this->postJson('/api/v1/wishlist', ['book_id' => 1])->assertStatus(401);
    }

    public function test_user_can_add_book_to_wishlist(): void
    {
        $user = User::factory()->create();
        $book = Book::where('status', Book::STATUS_PUBLISHED)->firstOrFail();

        $this->actingAs($user)
            ->postJson('/api/v1/wishlist', ['book_id' => $book->id])
            ->assertStatus(201);

        $this->assertDatabaseHas('book_wishlist', ['user_id' => $user->id, 'book_id' => $book->id]);
    }

    public function test_adding_twice_is_idempotent(): void
    {
        $user = User::factory()->create();
        $book = Book::where('status', Book::STATUS_PUBLISHED)->firstOrFail();

        $this->actingAs($user)->postJson('/api/v1/wishlist', ['book_id' => $book->id])->assertStatus(201);
        $this->actingAs($user)->postJson('/api/v1/wishlist', ['book_id' => $book->id])->assertStatus(201);

        $this->assertDatabaseCount('book_wishlist', 1);
    }

    public function test_user_can_remove_book_from_wishlist(): void
    {
        $user = User::factory()->create();
        $book = Book::where('status', Book::STATUS_PUBLISHED)->firstOrFail();
        $user->wishlistedBooks()->attach($book->id);

        $this->actingAs($user)
            ->deleteJson('/api/v1/wishlist/'.$book->id)
            ->assertStatus(200);

        $this->assertDatabaseMissing('book_wishlist', ['user_id' => $user->id, 'book_id' => $book->id]);
    }

    public function test_wishlist_only_returns_user_own_books(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $book = Book::where('status', Book::STATUS_PUBLISHED)->firstOrFail();
        $otherBook = Book::where('status', Book::STATUS_PUBLISHED)->skip(1)->firstOrFail();

        $user->wishlistedBooks()->attach($book->id);
        $other->wishlistedBooks()->attach($otherBook->id);

        $response = $this->actingAs($user)->getJson('/api/v1/wishlist');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.books')
            ->assertJsonPath('data.books.0.id', $book->id);
    }

    public function test_book_list_flags_wishlisted_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $book = Book::where('status', Book::STATUS_PUBLISHED)->firstOrFail();
        $user->wishlistedBooks()->attach($book->id);

        $response = $this->actingAs($user)->getJson('/api/v1/books?per_page=50');

        $flagged = collect($response->json('data.books'))->firstWhere('id', $book->id);

        $this->assertTrue($flagged['is_wishlisted']);
    }

    public function test_wishlist_filter_returns_only_wishlisted_books(): void
    {
        $user = User::factory()->create();
        $book = Book::where('status', Book::STATUS_PUBLISHED)->firstOrFail();
        $user->wishlistedBooks()->attach($book->id);

        $response = $this->actingAs($user)->getJson('/api/v1/books?wishlist=1');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.books')
            ->assertJsonPath('data.books.0.id', $book->id);
    }

    public function test_min_rating_filter_returns_only_qualified_books(): void
    {
        $user = User::factory()->create();
        $good = Book::where('status', Book::STATUS_PUBLISHED)->firstOrFail();
        $review = Review::factory()->create(['book_id' => $good->id, 'status' => Review::STATUS_APPROVED, 'rating' => 5]);

        $this->actingAs($user)
            ->getJson('/api/v1/books?min_rating=5')
            ->assertStatus(200);

        $ids = collect(json_decode($this->getJson('/api/v1/books?min_rating=5')->getContent(), true)['data']['books'])
            ->pluck('id')->all();

        foreach ($ids as $id) {
            $this->assertGreaterThanOrEqual(5, (float) Book::withAvg('approvedReviews as avg_rating', 'rating')->find($id)->avg_rating);
        }
    }
}

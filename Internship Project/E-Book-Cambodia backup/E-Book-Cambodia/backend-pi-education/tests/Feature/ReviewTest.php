<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_review_a_free_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->free()->create(['is_published' => true]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/books/{$book->id}/reviews", [
                'rating' => 5,
                'comment' => 'Great book!',
            ])
            ->assertStatus(201)
            ->assertJsonPath('review.rating', 5)
            ->assertJsonPath('review.comment', 'Great book!');

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'status' => 'approved',
        ]);
    }

    public function test_user_cannot_review_paid_book_without_access(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['price' => 9.99, 'is_free' => false, 'is_published' => true]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/books/{$book->id}/reviews", ['rating' => 5])
            ->assertStatus(403);
    }

    public function test_review_updates_instead_of_duplicating(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->free()->create(['is_published' => true]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/books/{$book->id}/reviews", ['rating' => 3]);
        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/books/{$book->id}/reviews", ['rating' => 4]);

        $this->assertEquals(1, Review::where('user_id', $user->id)->where('book_id', $book->id)->count());
        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 4,
        ]);
    }

    public function test_review_requires_valid_rating(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->free()->create(['is_published' => true]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/books/{$book->id}/reviews", ['rating' => 9])
            ->assertStatus(422)
            ->assertJsonValidationErrors('rating');
    }

    public function test_public_index_only_shows_approved_reviews(): void
    {
        $book = Book::factory()->free()->create(['is_published' => true]);
        $approvedUser = User::factory()->create();
        $pendingUser = User::factory()->create();

        Review::create([
            'user_id' => $approvedUser->id,
            'book_id' => $book->id,
            'rating' => 5,
            'status' => Review::STATUS_APPROVED,
        ]);
        Review::create([
            'user_id' => $pendingUser->id,
            'book_id' => $book->id,
            'rating' => 1,
            'status' => Review::STATUS_PENDING,
        ]);

        $this->getJson("/api/v1/books/{$book->id}/reviews")
            ->assertOk()
            ->assertJsonCount(1, 'reviews.data');
    }

    public function test_user_can_delete_own_review_but_not_others(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $book = Book::factory()->free()->create(['is_published' => true]);

        $mine = Review::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 4,
            'status' => Review::STATUS_APPROVED,
        ]);
        $theirs = Review::create([
            'user_id' => $other->id,
            'book_id' => $book->id,
            'rating' => 2,
            'status' => Review::STATUS_APPROVED,
        ]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/reviews/{$mine->id}")
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/reviews/{$theirs->id}")
            ->assertStatus(403);

        $this->assertDatabaseMissing('reviews', ['id' => $mine->id]);
        $this->assertDatabaseHas('reviews', ['id' => $theirs->id]);
    }

    public function test_book_show_includes_ratings(): void
    {
        $book = Book::factory()->free()->create(['is_published' => true]);
        $user = User::factory()->create();

        Review::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'status' => Review::STATUS_APPROVED,
        ]);
        Review::create([
            'user_id' => User::factory()->create()->id,
            'book_id' => $book->id,
            'rating' => 3,
            'status' => Review::STATUS_APPROVED,
        ]);

        $this->getJson("/api/v1/books/{$book->id}")
            ->assertOk()
            ->assertJsonPath('ratings.count', 2)
            ->assertJsonPath('ratings.avg', 4);
    }

    public function test_admin_can_manage_reviews(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $book = Book::factory()->free()->create(['is_published' => true]);

        $review = Review::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 3,
            'status' => Review::STATUS_PENDING,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/reviews')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/reviews/{$review->id}", ['status' => 'approved'])
            ->assertOk()
            ->assertJsonPath('review.status', 'approved');

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/reviews/{$review->id}")
            ->assertOk();

        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }
}

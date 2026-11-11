<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReaderSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_guest_can_read_free_book_content(): void
    {
        $book = Book::where('slug', 'algebra-basics')->firstOrFail();

        $response = $this->withHeader('X-Locale', 'en')->getJson('/api/v1/read/'.$book->slug);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.book.type', 'FREE')
            ->assertJsonStructure(['data' => ['book', 'content']]);
        $this->assertNotEmpty($response->json('data.content'));
    }

    public function test_guest_cannot_read_paid_book_content(): void
    {
        $book = Book::where('slug', 'web-development-masterclass')->firstOrFail();

        $this->getJson('/api/v1/read/'.$book->slug)->assertStatus(403);
    }

    public function test_regular_user_cannot_read_unpurchased_paid_book(): void
    {
        $user = User::factory()->create();
        $book = Book::where('slug', 'web-development-masterclass')->firstOrFail();

        $this->actingAs($user)
            ->getJson('/api/v1/read/'.$book->slug)
            ->assertStatus(403);
    }

    public function test_paid_book_content_missing_for_guest(): void
    {
        $book = Book::where('slug', 'web-development-masterclass')->firstOrFail();

        $response = $this->getJson('/api/v1/read/'.$book->slug);
        $this->assertNull($response->json('data.content'));
    }

    public function test_vip_user_can_read_paid_book_content(): void
    {
        $user = User::factory()->vip()->create();
        $book = Book::where('slug', 'web-development-masterclass')->firstOrFail();

        $this->actingAs($user)
            ->getJson('/api/v1/read/'.$book->slug)
            ->assertStatus(200)
            ->assertJsonPath('data.book.type', 'PAID');
    }

    public function test_vip_user_with_bearer_token_can_read_paid_book_content(): void
    {
        $user = User::factory()->vip()->create();
        $token = $user->createToken('auth-token')->plainTextToken;
        $book = Book::where('slug', 'web-development-masterclass')->firstOrFail();

        $this->withToken($token)
            ->getJson('/api/v1/read/'.$book->slug)
            ->assertStatus(200)
            ->assertJsonPath('data.book.type', 'PAID');
    }

    public function test_expired_vip_user_cannot_read_paid_book(): void
    {
        $user = User::factory()->vipExpired()->create();
        $book = Book::where('slug', 'web-development-masterclass')->firstOrFail();

        $this->actingAs($user)
            ->getJson('/api/v1/read/'.$book->slug)
            ->assertStatus(403);
    }

    public function test_owner_can_read_purchased_paid_book(): void
    {
        $user = User::factory()->create();
        $book = Book::where('slug', 'web-development-masterclass')->firstOrFail();
        $user->ownedBooks()->attach($book->id, ['amount' => $book->price]);

        $this->actingAs($user)
            ->getJson('/api/v1/read/'.$book->slug)
            ->assertStatus(200);
    }

    public function test_purchased_book_remains_accessible_after_vip_expires(): void
    {
        $user = User::factory()->vipExpired()->create();
        $book = Book::where('slug', 'web-development-masterclass')->firstOrFail();

        $this->actingAs($user)
            ->getJson('/api/v1/read/'.$book->slug)
            ->assertStatus(403);

        $user->ownedBooks()->attach($book->id, ['amount' => $book->price]);

        $this->actingAs($user)
            ->getJson('/api/v1/read/'.$book->slug)
            ->assertStatus(200);
    }

    public function test_user_can_save_reading_progress(): void
    {
        $user = User::factory()->create();
        $book = Book::where('slug', 'algebra-basics')->firstOrFail();

        $this->actingAs($user)
            ->putJson('/api/v1/read/'.$book->slug.'/progress', ['progress' => 42])
            ->assertStatus(200)
            ->assertJsonPath('data.progress', 42);

        $this->assertDatabaseHas('reading_progress', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'progress' => 42,
        ]);
    }

    public function test_progress_validation_rejects_out_of_range(): void
    {
        $user = User::factory()->create();
        $book = Book::where('slug', 'algebra-basics')->firstOrFail();

        $this->actingAs($user)
            ->putJson('/api/v1/read/'.$book->slug.'/progress', ['progress' => 150])
            ->assertStatus(422);

        $this->actingAs($user)
            ->putJson('/api/v1/read/'.$book->slug.'/progress', ['progress' => -5])
            ->assertStatus(422);
    }

    public function test_my_books_lists_free_and_owned_books(): void
    {
        $user = User::factory()->create();
        $paid = Book::where('slug', 'web-development-masterclass')->firstOrFail();
        $user->ownedBooks()->attach($paid->id, ['amount' => $paid->price]);

        $response = $this->actingAs($user)->getJson('/api/v1/my-books');

        $response->assertStatus(200);

        $slugs = collect($response->json('data.books.data'))->pluck('slug');
        $this->assertTrue($slugs->contains('algebra-basics'));
        $this->assertTrue($slugs->contains('web-development-masterclass'));
        $this->assertFalse($slugs->contains('english-for-beginners'));
    }

    public function test_progress_saves_per_user_and_is_isolated(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $book = Book::where('slug', 'algebra-basics')->firstOrFail();

        $this->actingAs($userA)->putJson('/api/v1/read/'.$book->slug.'/progress', ['progress' => 10]);
        $this->actingAs($userB)->putJson('/api/v1/read/'.$book->slug.'/progress', ['progress' => 90]);

        $this->assertDatabaseHas('reading_progress', [
            'user_id' => $userA->id, 'book_id' => $book->id, 'progress' => 10,
        ]);
        $this->assertDatabaseHas('reading_progress', [
            'user_id' => $userB->id, 'book_id' => $book->id, 'progress' => 90,
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComingSoonTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_flag_a_book_as_coming_soon(): void
    {
        $this->actingAs(User::factory()->admin()->create(), 'sanctum');
        $category = Category::factory()->create();

        $this->postJson('/api/v1/admin/books', [
            'category_id' => $category->id,
            'title' => 'Coming Attraction',
            'is_published' => true,
            'is_coming_soon' => true,
        ])
            ->assertCreated()
            ->assertJsonPath('book.is_coming_soon', true);
    }

    public function test_coming_soon_books_stay_visible_in_public_listing_and_detail(): void
    {
        $book = Book::factory()->create(['is_published' => true, 'is_coming_soon' => true]);

        $this->getJson('/api/v1/books')
            ->assertOk()
            ->assertJsonFragment(['id' => $book->id]);

        $this->getJson("/api/v1/books/{$book->id}")
            ->assertOk()
            ->assertJsonPath('book.is_coming_soon', true);
    }

    public function test_coming_soon_books_cannot_be_read(): void
    {
        $book = Book::factory()->create([
            'is_published' => true,
            'is_coming_soon' => true,
            'is_free' => true,
            'file_path' => 'demo.pdf',
        ]);

        $this->getJson("/api/v1/reader/{$book->id}")->assertStatus(403);
        $this->getJson("/api/v1/reader/{$book->id}/stream")->assertStatus(403);
    }

    public function test_coming_soon_books_cannot_be_purchased(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create([
            'is_published' => true,
            'is_coming_soon' => true,
            'is_free' => false,
            'price' => 5.00,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/books/{$book->id}/purchase", ['method' => 'mock'])
            ->assertStatus(422);
    }
}

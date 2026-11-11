<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_user_can_create_order_for_paid_books(): void
    {
        $user = User::factory()->create();
        $books = Book::where('type', 'PAID')->get();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/orders', ['book_ids' => $books->pluck('id')->all()]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'pending');

        $expectedSubtotal = $books->sum(fn ($book) => (float) $book->price);
        $expectedTax = round($expectedSubtotal * 0.10, 2);

        $this->assertEqualsWithDelta($expectedSubtotal, (float) $response->json('data.subtotal'), 0.01);
        $this->assertEqualsWithDelta(round($expectedSubtotal + $expectedTax, 2), (float) $response->json('data.total'), 0.01);
        $this->assertCount(3, $response->json('data.items'));
    }

    public function test_free_books_and_owned_books_are_not_charged(): void
    {
        $user = User::factory()->create();
        $free = Book::where('type', 'FREE')->firstOrFail();
        $paid = Book::where('type', 'PAID')->where('slug', 'web-development-masterclass')->firstOrFail();
        $user->ownedBooks()->attach($paid->id, ['amount' => $paid->price]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/orders', ['book_ids' => [$free->id, $paid->id]]);

        $response->assertStatus(422);
    }

    public function test_order_with_no_purchasable_books_is_rejected(): void
    {
        $user = User::factory()->create();
        $free = Book::where('type', 'FREE')->firstOrFail();

        $this->actingAs($user)
            ->postJson('/api/v1/orders', ['book_ids' => [$free->id]])
            ->assertStatus(422);
    }

    public function test_guest_cannot_create_order(): void
    {
        $book = Book::where('type', 'PAID')->firstOrFail();

        $this->postJson('/api/v1/orders', ['book_ids' => [$book->id]])->assertStatus(401);
    }

    public function test_user_can_pay_order_and_gains_access(): void
    {
        $user = User::factory()->create();
        $book = Book::where('type', 'PAID')->where('slug', 'english-for-beginners')->firstOrFail();

        $order = $this->actingAs($user)
            ->postJson('/api/v1/orders', ['book_ids' => [$book->id]])
            ->json('data');

        $response = $this->actingAs($user)
            ->postJson('/api/v1/orders/'.$order['order_number'].'/pay', ['payment_method' => 'mock']);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.transaction_id', fn ($id) => str_starts_with($id, 'MOCK-'));

        $this->assertDatabaseHas('book_purchase', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $this->actingAs($user)
            ->getJson('/api/v1/read/'.$book->slug)
            ->assertStatus(200);
    }

    public function test_user_can_list_own_orders_only(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $book = Book::where('type', 'PAID')->firstOrFail();

        $this->actingAs($userA)->postJson('/api/v1/orders', ['book_ids' => [$book->id]]);

        $response = $this->actingAs($userB)->getJson('/api/v1/orders');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data.orders'));

        $listA = $this->actingAs($userA)->getJson('/api/v1/orders');
        $this->assertCount(1, $listA->json('data.orders'));
    }

    public function test_user_cannot_view_or_pay_others_orders(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $book = Book::where('type', 'PAID')->firstOrFail();

        $order = $this->actingAs($userA)
            ->postJson('/api/v1/orders', ['book_ids' => [$book->id]])
            ->json('data');

        $this->actingAs($userB)
            ->getJson('/api/v1/orders/'.$order['order_number'])
            ->assertStatus(404);

        $this->actingAs($userB)
            ->postJson('/api/v1/orders/'.$order['order_number'].'/pay')
            ->assertStatus(404);
    }

    public function test_paying_already_paid_order_is_idempotent(): void
    {
        $user = User::factory()->create();
        $book = Book::where('type', 'PAID')->firstOrFail();

        $order = $this->actingAs($user)
            ->postJson('/api/v1/orders', ['book_ids' => [$book->id]])
            ->json('data');

        $this->actingAs($user)->postJson('/api/v1/orders/'.$order['order_number'].'/pay');
        $second = $this->actingAs($user)->postJson('/api/v1/orders/'.$order['order_number'].'/pay');

        $second->assertStatus(200)->assertJsonPath('data.status', 'paid');

        $this->assertDatabaseCount('book_purchase', 1);
        $this->assertDatabaseHas('book_purchase', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_foreign_payment_methods_are_rejected(): void
    {
        $user = User::factory()->create();
        $book = Book::where('type', 'PAID')->firstOrFail();

        $order = $this->actingAs($user)
            ->postJson('/api/v1/orders', ['book_ids' => [$book->id]])
            ->json('data');

        $this->actingAs($user)
            ->postJson('/api/v1/orders/'.$order['order_number'].'/pay', ['payment_method' => 'aba'])
            ->assertStatus(422);
    }
}

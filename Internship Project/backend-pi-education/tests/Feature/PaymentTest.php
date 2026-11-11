<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Course;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\User;
use App\Services\PaymentGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_free_book_does_not_require_payment(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->free()->create(['is_published' => true]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/books/{$book->id}/purchase")
            ->assertStatus(422)
            ->assertJsonPath('message', 'This book is free. No payment required.');
    }

    public function test_user_can_purchase_paid_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['price' => 4.99, 'is_free' => false, 'is_published' => true]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/books/{$book->id}/purchase", ['mock_approve' => true])
            ->assertOk()
            ->assertJsonPath('activated', true);

        $this->assertDatabaseHas('purchases', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => 'paid',
        ]);

        $this->assertDatabaseHas('payments', [
            'user_id' => $user->id,
            'status' => 'completed',
        ]);
    }

    public function test_user_cannot_purchase_twice(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['price' => 4.99, 'is_free' => false, 'is_published' => true]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/books/{$book->id}/purchase")
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/books/{$book->id}/purchase")
            ->assertStatus(422)
            ->assertJsonPath('message', 'You already own this book.');
    }

    public function test_payment_records_track_status(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['price' => 9.99, 'is_free' => false, 'is_published' => true]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/books/{$book->id}/purchase");

        $this->assertDatabaseHas('payments', [
            'user_id' => $user->id,
            'amount' => 9.99,
            'method' => 'mock',
            'status' => 'completed',
        ]);
    }

    public function test_user_can_list_their_payments(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create(['price' => 5.00, 'is_free' => false, 'is_published' => true]);
        $otherBook = Book::factory()->create(['price' => 5.00, 'is_free' => false, 'is_published' => true]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/books/{$book->id}/purchase");

        $this->actingAs($otherUser, 'sanctum')
            ->postJson("/api/v1/books/{$otherBook->id}/purchase");

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/payments')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_user_cannot_see_others_payment(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $book = Book::factory()->create(['price' => 5.00, 'is_free' => false, 'is_published' => true]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/books/{$book->id}/purchase");

        $payment = Payment::where('user_id', $user->id)->first();

        $this->actingAs($other, 'sanctum')
            ->getJson("/api/v1/payments/{$payment->id}")
            ->assertStatus(403);
    }

    public function test_purchase_sets_activated_at(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['price' => 3.00, 'is_free' => false, 'is_published' => true]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/books/{$book->id}/purchase");

        $purchase = Purchase::where('user_id', $user->id)->where('book_id', $book->id)->first();
        $this->assertNotNull($purchase->activated_at);
    }

    public function test_unauthenticated_cannot_purchase(): void
    {
        $book = Book::factory()->create(['price' => 5.00, 'is_free' => false, 'is_published' => true]);

        $this->postJson("/api/v1/books/{$book->id}/purchase")
            ->assertStatus(401);
    }

    public function test_can_purchase_course(): void
    {
        $user = User::factory()->create();
        $course = Course::create([
            'title' => 'Test Course',
            'slug' => 'test-course-1',
            'price' => 19.99,
            'is_published' => true,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/courses/{$course->id}/purchase", ['mock_approve' => true])
            ->assertOk()
            ->assertJsonPath('activated', true);

        $this->assertDatabaseHas('purchases', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => 'paid',
        ]);
    }

    public function test_purchase_returns_checkout_url_when_stripe_driver_configured(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['price' => 10.00, 'is_free' => false, 'is_published' => true]);

        $gateway = Mockery::mock(PaymentGatewayService::class);
        $gateway->shouldReceive('isStripe')->andReturn(true);
        $gateway->shouldReceive('checkout')->andReturn([
            'status' => Payment::STATUS_PENDING,
            'provider_ref' => 'cs_test_123',
            'checkout_url' => 'https://checkout.stripe.com/c/pay/cs_test_123',
            'payment' => Payment::create([
                'user_id' => $user->id,
                'reference' => 'PAY-STRIPE-001',
                'method' => 'stripe',
                'amount' => 10.00,
                'status' => Payment::STATUS_PENDING,
                'provider_ref' => 'cs_test_123',
            ]),
        ]);
        $this->app->instance(PaymentGatewayService::class, $gateway);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/books/{$book->id}/purchase", ['method' => 'stripe'])
            ->assertStatus(202)
            ->assertJsonPath('checkout_url', 'https://checkout.stripe.com/c/pay/cs_test_123')
            ->assertJsonPath('activated', false);
    }

    public function test_stripe_webhook_completes_pending_payment(): void
    {
        $secret = 'whsec_test_secret';
        config(['payments.stripe.webhook_secret' => $secret]);

        $user = User::factory()->create();
        $book = Book::factory()->create(['price' => 8.00, 'is_free' => false, 'is_published' => true]);

        $purchase = Purchase::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'amount' => 8.00,
            'status' => Purchase::STATUS_PENDING,
        ]);

        Payment::create([
            'user_id' => $user->id,
            'purchase_id' => $purchase->id,
            'reference' => 'PAY-WEBHOOK-001',
            'method' => 'stripe',
            'amount' => 8.00,
            'status' => Payment::STATUS_PENDING,
            'provider_ref' => 'cs_webhook_001',
        ]);

        $body = json_encode([
            'id' => 'evt_test_1',
            'type' => 'checkout.session.completed',
            'data' => ['object' => ['id' => 'cs_webhook_001']],
        ]);

        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$body}", $secret);
        $stripeSignature = "t={$timestamp},v1={$signature}";

        $response = $this->call('POST', '/api/v1/webhooks/stripe', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $stripeSignature,
        ], $body);

        $response->assertOk();

        $this->assertDatabaseHas('payments', [
            'user_id' => $user->id,
            'provider_ref' => 'cs_webhook_001',
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('purchases', [
            'id' => $purchase->id,
            'status' => 'paid',
        ]);
    }

    public function test_stripe_webhook_rejects_payload_when_secret_not_configured(): void
    {
        config(['payments.stripe.webhook_secret' => '']);

        $this->postJson('/api/v1/webhooks/stripe', ['type' => 'checkout.session.completed'])
            ->assertStatus(400);
    }

    public function test_stripe_webhook_rejects_payload_with_invalid_signature(): void
    {
        config(['payments.stripe.webhook_secret' => 'whsec_test_secret']);

        $body = json_encode([
            'id' => 'evt_test_1',
            'type' => 'checkout.session.completed',
            'data' => ['object' => ['id' => 'cs_webhook_001']],
        ]);

        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$body}", 'wrong_secret');
        $stripeSignature = "t={$timestamp},v1={$signature}";

        $response = $this->call('POST', '/api/v1/webhooks/stripe', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $stripeSignature,
        ], $body);

        $response->assertStatus(400);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BakongPaymentTest extends TestCase
{
    use RefreshDatabase;

    private const MD5 = 'd60f3db96913029a2af979a1662c1e72';

    private const BAKONG_URL = 'https://api-bakong.nbc.gov.kh/v1/check_transaction_by_md5';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'payments.bakong.token' => $this->token(),
            'payments.bakong.account_id' => '0976002148@aclb',
            'payments.bakong.merchant_name' => 'PI Education Cambodia',
            'payments.bakong.merchant_city' => 'Phnom Penh',
            'payments.bakong.currency' => 'USD',
        ]);
    }

    private function token(int $expiresIn = 3600): string
    {
        $payload = base64_encode(json_encode(['exp' => time() + $expiresIn]));

        return 'header.'.$payload.'.signature';
    }

    private function bakongPayment(User $user, float $amount = 5.00): Payment
    {
        $purchase = Purchase::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'status' => Purchase::STATUS_PENDING,
        ]);

        return Payment::create([
            'user_id' => $user->id,
            'purchase_id' => $purchase->id,
            'reference' => 'PAY-BAKONG-'.uniqid(),
            'method' => 'bakong',
            'amount' => $amount,
            'status' => Payment::STATUS_PENDING,
            'provider_ref' => self::MD5,
        ]);
    }

    private function bakongBody(int $responseCode, ?array $data): array
    {
        return [
            'responseCode' => $responseCode,
            'responseMessage' => $responseCode === 0 ? 'Getting transaction successfully.' : 'Transaction failed.',
            'errorCode' => $responseCode === 0 ? null : 1,
            'data' => $data,
        ];
    }

    public function test_create_bakong_payment_returns_qr_and_md5(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/payments/bakong', [
                'amount' => 5.00,
                'item_name' => 'Khmer Reader Grade 1',
                'item_key' => 'book-grade-1',
            ])
            ->assertStatus(202)
            ->assertJsonPath('payment.method', 'bakong')
            ->assertJsonPath('payment.status', 'pending');

        $qr = $response->json('bakong.qr');
        $this->assertIsString($qr);
        $this->assertStringStartsWith('000201', $qr);
        $this->assertSame(md5($qr), $response->json('bakong.md5'));
        $this->assertSame('5.00', (string) $response->json('bakong.amount'));

        $this->assertDatabaseHas('payments', [
            'user_id' => $user->id,
            'method' => 'bakong',
            'status' => 'pending',
            'provider_ref' => md5($qr),
        ]);
    }

    public function test_bakong_check_confirms_paid_transaction(): void
    {
        $user = User::factory()->create();
        $payment = $this->bakongPayment($user);

        Http::fake([
            self::BAKONG_URL => Http::response($this->bakongBody(0, [
                'hash' => '8465d722d7d5065f2886f0a474a4d34dc6a7855355b611836f7b6111228893e9',
                'fromAccountId' => 'buyer@aclb',
                'toAccountId' => '0976002148@aclb',
                'currency' => 'USD',
                'amount' => 5.00,
            ])),
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/payments/{$payment->id}/bakong/check")
            ->assertOk()
            ->assertJsonPath('paid', true)
            ->assertJsonPath('status', 'completed')
            ->assertJsonPath('success', true);

        Http::assertSent(function ($request) {
            return $request->url() === self::BAKONG_URL
                && str_contains($request->header('Authorization')[0] ?? '', 'Bearer '.config('payments.bakong.token'));
        });

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('purchases', [
            'id' => $payment->purchase_id,
            'status' => 'paid',
        ]);
    }

    public function test_bakong_check_keeps_payment_pending_when_transaction_not_found(): void
    {
        $user = User::factory()->create();
        $payment = $this->bakongPayment($user);

        Http::fake([
            self::BAKONG_URL => Http::response($this->bakongBody(1, null)),
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/payments/{$payment->id}/bakong/check")
            ->assertStatus(202)
            ->assertJsonPath('paid', false)
            ->assertJsonPath('status', 'pending');

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('purchases', [
            'id' => $payment->purchase_id,
            'status' => 'pending',
        ]);
    }

    public function test_bakong_check_does_not_complete_on_amount_mismatch(): void
    {
        $user = User::factory()->create();
        $payment = $this->bakongPayment($user);

        Http::fake([
            self::BAKONG_URL => Http::response($this->bakongBody(0, [
                'hash' => '8465d722d7d5065f2886f0a474a4d34dc6a7855355b611836f7b6111228893e9',
                'fromAccountId' => 'buyer@aclb',
                'toAccountId' => '0976002148@aclb',
                'currency' => 'USD',
                'amount' => 4.00,
            ])),
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/payments/{$payment->id}/bakong/check")
            ->assertStatus(202)
            ->assertJsonPath('paid', false)
            ->assertJsonPath('status', 'pending');

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
        ]);
    }

    public function test_bakong_check_returns_clean_error_when_api_unreachable(): void
    {
        $user = User::factory()->create();
        $payment = $this->bakongPayment($user);

        Http::fake([
            self::BAKONG_URL => fn () => throw new ConnectionException('Connection timed out.'),
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/payments/{$payment->id}/bakong/check")
            ->assertStatus(502)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'The Bakong payment service is unreachable. Please try again.');

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
        ]);
    }

    public function test_bakong_check_returns_clean_error_on_invalid_response(): void
    {
        $user = User::factory()->create();
        $payment = $this->bakongPayment($user);

        Http::fake([
            self::BAKONG_URL => Http::response('<html>Bad gateway</html>', 502),
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/payments/{$payment->id}/bakong/check")
            ->assertStatus(502)
            ->assertJsonPath('success', false);
    }

    public function test_bakong_check_rejects_missing_token(): void
    {
        config(['payments.bakong.token' => '']);

        $user = User::factory()->create();
        $payment = $this->bakongPayment($user);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/payments/{$payment->id}/bakong/check")
            ->assertStatus(503)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
        ]);
    }

    public function test_bakong_check_rejects_expired_token(): void
    {
        config(['payments.bakong.token' => $this->token(-3600)]);

        $user = User::factory()->create();
        $payment = $this->bakongPayment($user);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/payments/{$payment->id}/bakong/check")
            ->assertStatus(503)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
        ]);
    }

    public function test_bakong_check_does_not_expose_token_in_response(): void
    {
        $user = User::factory()->create();
        $payment = $this->bakongPayment($user);

        Http::fake([
            self::BAKONG_URL => Http::response($this->bakongBody(0, [
                'hash' => '8465d722d7d5065f2886f0a474a4d34dc6a7855355b611836f7b6111228893e9',
                'fromAccountId' => 'buyer@aclb',
                'toAccountId' => '0976002148@aclb',
                'currency' => 'USD',
                'amount' => 5.00,
            ])),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/payments/{$payment->id}/bakong/check")
            ->assertOk();

        $this->assertStringNotContainsString(config('payments.bakong.token'), $response->getContent());
    }

    public function test_bakong_check_runs_activation_side_effects_for_book_purchase(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['price' => 5.00]);
        $purchase = Purchase::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'amount' => 5.00,
            'status' => Purchase::STATUS_PENDING,
        ]);
        $payment = Payment::create([
            'user_id' => $user->id,
            'purchase_id' => $purchase->id,
            'reference' => 'PAY-BAKONG-'.uniqid(),
            'method' => 'bakong',
            'amount' => 5.00,
            'status' => Payment::STATUS_PENDING,
            'provider_ref' => self::MD5,
        ]);

        Http::fake([
            self::BAKONG_URL => Http::response($this->bakongBody(0, [
                'hash' => '8465d722d7d5065f2886f0a474a4d34dc6a7855355b611836f7b6111228893e9',
                'fromAccountId' => 'buyer@aclb',
                'toAccountId' => '0976002148@aclb',
                'currency' => 'USD',
                'amount' => 5.00,
            ])),
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/payments/{$payment->id}/bakong/check")
            ->assertOk()
            ->assertJsonPath('paid', true);

        $this->assertSame(1, $book->fresh()->purchase_count);
    }

    public function test_bakong_check_does_not_activate_the_same_payment_twice(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['price' => 5.00]);
        $purchase = Purchase::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'amount' => 5.00,
            'status' => Purchase::STATUS_PENDING,
        ]);
        $payment = Payment::create([
            'user_id' => $user->id,
            'purchase_id' => $purchase->id,
            'reference' => 'PAY-BAKONG-'.uniqid(),
            'method' => 'bakong',
            'amount' => 5.00,
            'status' => Payment::STATUS_PENDING,
            'provider_ref' => self::MD5,
        ]);

        Http::fake([
            self::BAKONG_URL => Http::response($this->bakongBody(0, [
                'hash' => '8465d722d7d5065f2886f0a474a4d34dc6a7855355b611836f7b6111228893e9',
                'fromAccountId' => 'buyer@aclb',
                'toAccountId' => '0976002148@aclb',
                'currency' => 'USD',
                'amount' => 5.00,
            ])),
        ]);

        $this->actingAs($user, 'sanctum')->postJson("/api/v1/payments/{$payment->id}/bakong/check")->assertOk();
        $this->actingAs($user, 'sanctum')->postJson("/api/v1/payments/{$payment->id}/bakong/check")->assertOk();

        $this->assertSame(1, $book->fresh()->purchase_count);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('purchases', [
            'id' => $purchase->id,
            'status' => 'paid',
        ]);
    }
}

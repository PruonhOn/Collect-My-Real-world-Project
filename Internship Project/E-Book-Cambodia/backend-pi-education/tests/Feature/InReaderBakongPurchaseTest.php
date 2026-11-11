<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The reader keeps a gated book open and offers the KHQR sheet in place of a
 * "go back to the book page and buy it" detour. These tests pin the API
 * contract that flow depends on: a readtest book can be started with the
 * Bakong method, the payment is confirmed through the existing check endpoint,
 * and the same reader session then serves the full book.
 */
class InReaderBakongPurchaseTest extends TestCase
{
    use RefreshDatabase;

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

    private function readtestBook(): Book
    {
        return Book::factory()
            ->published()
            ->trialRead(3)
            ->withContent(6)
            ->create([
                'title' => 'Khmer Reader Grade 1',
                'price' => 4.99,
                'is_free' => false,
                'is_vip_only' => false,
                'is_published' => true,
            ]);
    }

    private function fakeBakongConfirmation(float $amount): void
    {
        Http::fake([
            self::BAKONG_URL => Http::response([
                'responseCode' => 0,
                'responseMessage' => 'Getting transaction successfully.',
                'errorCode' => null,
                'data' => [
                    'hash' => '8465d722d7d5065f2886f0a474a4d34dc6a7855355b611836f7b6111228893e9',
                    'fromAccountId' => 'buyer@aclb',
                    'toAccountId' => '0976002148@aclb',
                    'currency' => 'USD',
                    'amount' => $amount,
                ],
            ]),
        ]);
    }

    public function test_readtest_book_is_opened_as_a_trial_for_a_reader_without_the_book(): void
    {
        $user = User::factory()->create();
        $book = $this->readtestBook();

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/reader/{$book->id}")
            ->assertOk()
            ->assertJsonPath('access.mode', 'preview')
            ->assertJsonPath('access.preview_mode', Book::PREVIEW_MODE_READTEST)
            ->assertJsonPath('access.can_read', false);
    }

    public function test_reader_purchase_returns_a_khqr_instead_of_leaving_the_reader(): void
    {
        $user = User::factory()->create();
        $book = $this->readtestBook();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/books/{$book->id}/purchase", ['method' => 'bakong'])
            ->assertStatus(202)
            ->assertJsonPath('payment.method', 'bakong')
            ->assertJsonPath('payment.status', Payment::STATUS_PENDING)
            ->assertJsonPath('activated', false);

        $qr = $response->json('bakong.qr');
        $this->assertIsString($qr);
        $this->assertStringStartsWith('000201', $qr);
        $this->assertSame('4.99', (string) $response->json('bakong.amount'));
        $this->assertNotNull($response->json('payment.id'));

        $this->assertDatabaseHas('purchases', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => Purchase::STATUS_PENDING,
        ]);
    }

    public function test_confirmed_khqr_unlocks_the_same_reader_session(): void
    {
        $user = User::factory()->create();
        $book = $this->readtestBook();

        $trial = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/reader/{$book->id}")
            ->assertOk();
        $this->assertStringContainsString('Section 1', $trial->json('book.content'));
        $this->assertStringNotContainsString('Section 6', $trial->json('book.content'));

        $paymentId = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/books/{$book->id}/purchase", ['method' => 'bakong'])
            ->assertStatus(202)
            ->json('payment.id');

        $this->fakeBakongConfirmation(4.99);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/payments/{$paymentId}/bakong/check")
            ->assertOk()
            ->assertJsonPath('paid', true);

        $unlocked = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/reader/{$book->id}")
            ->assertOk();
        $this->assertStringContainsString('Section 6', $unlocked->json('book.content'));

        $this->assertDatabaseHas('purchases', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => Purchase::STATUS_PAID,
        ]);
    }

    public function test_guest_cannot_start_a_khqr_purchase(): void
    {
        $book = $this->readtestBook();

        $this->postJson("/api/v1/books/{$book->id}/purchase", ['method' => 'bakong'])
            ->assertStatus(401);
    }

    public function test_vip_only_book_asks_for_a_membership_instead_of_a_khqr(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()
            ->published()
            ->vipOnly()
            ->trialRead(3)
            ->withContent(6)
            ->create([
                'title' => 'Khmer Reader Grade 1 (VIP)',
                'price' => 4.99,
                'is_free' => false,
                'is_published' => true,
            ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/books/{$book->id}/purchase", ['method' => 'bakong'])
            ->assertStatus(422)
            ->assertJsonPath('vip_required', true);
    }
}

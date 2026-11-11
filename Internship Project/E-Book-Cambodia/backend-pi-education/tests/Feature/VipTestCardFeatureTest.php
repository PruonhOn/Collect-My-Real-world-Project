<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Purchase;
use App\Models\User;
use App\Models\VipPlan;
use App\Services\VipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class VipTestCardFeatureTest extends TestCase
{
    use RefreshDatabase;

    private const MD5 = 'd60f3db96913029a2af979a1662c1e72';

    private const BAKONG_URL = 'https://api-bakong.nbc.gov.kh/v1/check_transaction_by_md5';

    protected function setUp(): void
    {
        parent::setUp();

        config(['vip.test_mode' => true]);

        config([
            'payments.bakong.token' => $this->token(),
            'payments.bakong.account_id' => '0976002148@aclb',
            'payments.bakong.merchant_name' => 'PI Education Cambodia',
            'payments.bakong.merchant_city' => 'Phnom Penh',
            'payments.bakong.currency' => 'USD',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function token(int $expiresIn = 3600): string
    {
        $payload = base64_encode(json_encode(['exp' => time() + $expiresIn]));

        return 'header.'.$payload.'.signature';
    }

    private function test_plan(): VipPlan
    {
        return VipPlan::factory()->testCard()->create();
    }

    private function paidBakongBody(float $amount): array
    {
        return [
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
        ];
    }

    public function test_test_card_is_hidden_when_test_mode_is_disabled(): void
    {
        config(['vip.test_mode' => false]);
        $this->test_plan();

        $this->getJson('/api/v1/vip-plans')
            ->assertOk()
            ->assertJsonCount(0, 'plans');
    }

    public function test_test_card_is_listed_when_test_mode_is_enabled(): void
    {
        $this->test_plan();

        $this->getJson('/api/v1/vip-plans')
            ->assertOk()
            ->assertJsonPath('plans.0.slug', VipService::TEST_PLAN_SLUG)
            ->assertJsonPath('plans.0.duration_hours', 5)
            ->assertJsonPath('plans.0.is_test', true)
            ->assertJsonPath('plans.0.price', '0.01');
    }

    public function test_test_card_mock_purchase_activates_five_hour_subscription(): void
    {
        Carbon::setTestNow(now());
        $user = User::factory()->create();
        $plan = $this->test_plan();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/vip/{$plan->id}/purchase", [
                'method' => 'mock',
                'mock_approve' => true,
            ])
            ->assertOk()
            ->assertJsonPath('activated', true);

        $user->refresh();

        $this->assertTrue($user->isVip());
        $this->assertEqualsWithDelta(
            now()->addHours(VipService::TEST_SUBSCRIPTION_HOURS)->getTimestamp(),
            $user->vip_until->getTimestamp(),
            5
        );
        $this->assertEquals(VipService::TEST_PLAN_SLUG, $user->vip_plan);

        $purchase = Purchase::where('user_id', $user->id)->firstOrFail();
        $this->assertEquals('0.01', (string) $purchase->amount);
    }

    public function test_bakong_check_activates_test_card_subscription_for_five_hours(): void
    {
        Carbon::setTestNow(now());
        $user = User::factory()->create();
        $plan = $this->test_plan();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/vip/{$plan->id}/purchase", [
                'method' => 'bakong',
            ])
            ->assertStatus(202)
            ->assertJsonPath('payment.method', 'bakong')
            ->assertJsonPath('payment.status', 'pending')
            ->assertJsonPath('bakong.amount', '0.01');

        $paymentId = $response->json('payment.id');
        $this->assertStringStartsWith('000201', (string) $response->json('bakong.qr'));

        Http::fake([
            self::BAKONG_URL => Http::response($this->paidBakongBody(0.01)),
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/payments/{$paymentId}/bakong/check")
            ->assertOk()
            ->assertJsonPath('paid', true)
            ->assertJsonPath('status', 'completed');

        $user->refresh();

        $this->assertTrue($user->isVip());
        $this->assertEquals(VipService::TEST_PLAN_SLUG, $user->vip_plan);
        $this->assertEqualsWithDelta(
            now()->addHours(VipService::TEST_SUBSCRIPTION_HOURS)->getTimestamp(),
            $user->vip_until->getTimestamp(),
            5
        );

        $this->assertDatabaseHas('payments', ['id' => $paymentId, 'status' => 'completed']);
        $this->assertDatabaseHas('purchases', ['status' => 'paid']);
    }

    public function test_bakong_check_does_not_complete_test_card_on_amount_mismatch(): void
    {
        $user = User::factory()->create();
        $plan = $this->test_plan();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/vip/{$plan->id}/purchase", [
                'method' => 'bakong',
            ])
            ->assertStatus(202);

        $paymentId = $response->json('payment.id');

        Http::fake([
            self::BAKONG_URL => Http::response($this->paidBakongBody(0.02)),
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/payments/{$paymentId}/bakong/check")
            ->assertStatus(202)
            ->assertJsonPath('paid', false)
            ->assertJsonPath('status', 'pending');

        $this->assertFalse($user->fresh()->isVip());
        $this->assertDatabaseHas('payments', ['id' => $paymentId, 'status' => 'pending']);
    }

    public function test_same_bakong_check_cannot_activate_test_subscription_twice(): void
    {
        Carbon::setTestNow(now());
        $user = User::factory()->create();
        $plan = $this->test_plan();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/vip/{$plan->id}/purchase", [
                'method' => 'bakong',
            ])
            ->assertStatus(202);

        $paymentId = $response->json('payment.id');

        Http::fake([
            self::BAKONG_URL => Http::response($this->paidBakongBody(0.01)),
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/payments/{$paymentId}/bakong/check")
            ->assertOk();

        $expiresAt = $user->fresh()->vip_until;

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/payments/{$paymentId}/bakong/check")
            ->assertOk();

        $this->assertEquals(
            $expiresAt->getTimestamp(),
            $user->fresh()->vip_until->getTimestamp()
        );
        $this->assertSame(1, Purchase::where('user_id', $user->id)->count());
    }

    public function test_test_card_expires_after_five_hours_and_paid_book_access_is_denied(): void
    {
        $paidBook = Book::factory()->create(['is_free' => false]);
        $freeBook = Book::factory()->create(['is_free' => true]);

        $testUser = User::factory()->create([
            'vip_until' => now()->addHours(VipService::TEST_SUBSCRIPTION_HOURS),
            'vip_plan' => VipService::TEST_PLAN_SLUG,
        ]);

        $this->actingAs($testUser, 'sanctum')
            ->getJson("/api/v1/reader/{$paidBook->id}")
            ->assertOk();

        Carbon::setTestNow(now()->addHours(VipService::TEST_SUBSCRIPTION_HOURS)->addMinute());

        $this->actingAs($testUser, 'sanctum')
            ->getJson("/api/v1/reader/{$paidBook->id}")
            ->assertStatus(403);

        $this->actingAs($testUser, 'sanctum')
            ->getJson("/api/v1/reader/{$freeBook->id}")
            ->assertOk();
    }

    public function test_test_card_does_not_affect_yearly_vip(): void
    {
        Carbon::setTestNow(now());
        $this->test_plan();

        $user = User::factory()->create();
        $yearly = VipPlan::factory()->create([
            'name' => 'VIP Yearly',
            'slug' => 'vip-yearly',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/vip/{$yearly->id}/purchase", [
                'method' => 'mock',
                'mock_approve' => true,
            ])
            ->assertOk();

        $user->refresh();

        $this->assertEqualsWithDelta(
            now()->addYear()->getTimestamp(),
            $user->vip_until->getTimestamp(),
            5
        );
        $this->assertEquals('vip-yearly', $user->vip_plan);
    }

    public function test_test_card_purchase_is_rejected_when_test_mode_is_disabled(): void
    {
        config(['vip.test_mode' => false]);

        $user = User::factory()->create();
        $plan = $this->test_plan();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/vip/{$plan->id}/purchase", [
                'method' => 'bakong',
            ])
            ->assertStatus(403);

        $this->assertFalse($user->fresh()->isVip());
        $this->assertDatabaseCount('purchases', 0);
    }

    public function test_activation_is_blocked_when_test_mode_is_disabled(): void
    {
        config(['vip.test_mode' => false]);

        $this->expectException(HttpException::class);

        VipService::activate(User::factory()->create(), $this->test_plan());
    }

    public function test_admin_can_list_and_revoke_test_cards(): void
    {
        $user = User::factory()->create();
        $plan = $this->test_plan();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/vip/{$plan->id}/purchase", [
                'method' => 'mock',
                'mock_approve' => true,
            ])
            ->assertOk();

        $purchase = Purchase::where('user_id', $user->id)->firstOrFail();

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/vip-test-cards')
            ->assertOk()
            ->assertJsonPath('stats.total', 1)
            ->assertJsonPath('stats.paid', 1)
            ->assertJsonPath('stats.active', 1)
            ->assertJsonCount(1, 'purchases.data');

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/vip-test-cards/{$purchase->id}/revoke")
            ->assertOk();

        $this->assertNull($user->fresh()->vip_until);
        $this->assertNull($user->fresh()->vip_plan);
        $this->assertSame(Purchase::STATUS_PAID, $purchase->fresh()->status);
    }

    public function test_test_card_revenue_is_excluded_from_admin_dashboard(): void
    {
        $this->test_plan();

        $yearlyUser = User::factory()->create();
        $yearly = VipPlan::factory()->create([
            'name' => 'VIP Yearly',
            'slug' => 'vip-yearly',
        ]);

        $this->actingAs($yearlyUser, 'sanctum')
            ->postJson("/api/v1/vip/{$yearly->id}/purchase", [
                'method' => 'mock',
                'mock_approve' => true,
            ])
            ->assertOk();

        $testUser = User::factory()->create();
        $testPlan = VipPlan::where('slug', VipService::TEST_PLAN_SLUG)->firstOrFail();

        $this->actingAs($testUser, 'sanctum')
            ->postJson("/api/v1/vip/{$testPlan->id}/purchase", [
                'method' => 'mock',
                'mock_approve' => true,
            ])
            ->assertOk();

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('stats.total_revenue', 59.99);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/vip-users')
            ->assertOk()
            ->assertJsonPath('stats.revenue', 59.99);
    }
}

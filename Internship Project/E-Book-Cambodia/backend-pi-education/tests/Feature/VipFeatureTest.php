<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Purchase;
use App\Models\User;
use App\Models\VipPlan;
use App\Services\VipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VipFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_lists_active_vip_plans(): void
    {
        VipPlan::create([
            'name' => 'VIP Yearly',
            'slug' => 'vip-yearly',
            'price' => 59.99,
            'duration_days' => VipService::SUBSCRIPTION_DAYS,
            'is_active' => true,
        ]);

        $this->getJson('/api/v1/vip-plans')
            ->assertOk()
            ->assertJsonPath('plans.0.slug', 'vip-yearly')
            ->assertJsonPath('plans.0.duration_days', 365);
    }

    public function test_mock_vip_purchase_activates_membership_for_one_year(): void
    {
        $user = User::factory()->create();
        $plan = VipPlan::factory()->create([
            'name' => 'VIP Yearly',
            'slug' => 'vip-yearly',
        ]);

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
            now()->addYear()->getTimestamp(),
            $user->vip_until->getTimestamp(),
            5
        );
        $this->assertEquals('vip-yearly', $user->vip_plan);
    }

    public function test_vip_member_can_read_paid_book_without_purchase(): void
    {
        $user = User::factory()->create([
            'vip_until' => now()->addYear(),
            'vip_plan' => 'vip-yearly',
        ]);
        $book = Book::factory()->create(['is_free' => false]);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/reader/{$book->id}")
            ->assertOk()
            ->assertJsonPath('book.id', $book->id);
    }

    public function test_vip_member_sees_all_eligible_books_in_library(): void
    {
        $user = User::factory()->create([
            'vip_until' => now()->addYear(),
            'vip_plan' => 'vip-yearly',
        ]);
        Book::factory()->count(3)->create(['is_free' => false]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/library')
            ->assertOk();

        $this->assertCount(3, $response->json('vip'));
    }

    public function test_vip_unlocked_books_expire_one_year_after_purchase(): void
    {
        $book = Book::factory()->create(['is_free' => false]);
        $expired = User::factory()->create([
            'vip_until' => now()->subMinute(),
            'vip_plan' => 'vip-yearly',
        ]);

        $this->actingAs($expired, 'sanctum')
            ->getJson("/api/v1/reader/{$book->id}")
            ->assertStatus(403);

        $this->actingAs($expired, 'sanctum')
            ->getJson('/api/v1/library')
            ->assertOk()
            ->assertJsonCount(0, 'vip');

        $renewed = User::factory()->create([
            'vip_until' => now()->addYear()->subMinute(),
            'vip_plan' => 'vip-yearly',
        ]);

        $this->actingAs($renewed, 'sanctum')
            ->getJson("/api/v1/reader/{$book->id}")
            ->assertOk();
    }

    public function test_renewal_restarts_membership_at_one_year_instead_of_stacking(): void
    {
        $user = User::factory()->create([
            'vip_until' => now()->addDays(300),
            'vip_plan' => 'vip-yearly',
        ]);
        $plan = VipPlan::factory()->create([
            'name' => 'VIP Yearly',
            'slug' => 'vip-yearly',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/vip/{$plan->id}/purchase", [
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
    }

    public function test_manual_method_requires_admin_approval(): void
    {
        $user = User::factory()->create();
        $plan = VipPlan::factory()->create([
            'name' => 'VIP Yearly',
            'slug' => 'vip-yearly',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/vip/{$plan->id}/purchase", [
                'method' => 'wing',
            ])
            ->assertStatus(202);

        $purchase = Purchase::where('user_id', $user->id)->firstOrFail();
        $this->assertFalse($user->fresh()->isVip());

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/payments/{$response->json('payment.id')}/approve")
            ->assertOk();

        $this->assertTrue($user->fresh()->isVip());
        $this->assertEquals(Purchase::STATUS_PAID, $purchase->fresh()->status);
    }

    public function test_inactive_plan_is_not_purchasable(): void
    {
        $user = User::factory()->create();
        $plan = VipPlan::factory()->create([
            'name' => 'Retired',
            'slug' => 'vip-retired',
            'is_active' => false,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/vip/{$plan->id}/purchase", [
                'method' => 'mock',
                'mock_approve' => true,
            ])
            ->assertStatus(404);
    }

    public function test_is_vip_is_serialized_on_me(): void
    {
        $user = User::factory()->create([
            'vip_until' => now()->addYear(),
            'vip_plan' => 'vip-yearly',
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('user.is_vip', true)
            ->assertJsonPath('user.vip_plan', 'vip-yearly');
    }

    public function test_admin_can_list_all_vip_plans_including_inactive(): void
    {
        VipPlan::create([
            'name' => 'VIP Yearly',
            'slug' => 'vip-yearly',
            'price' => 59.99,
            'duration_days' => 365,
            'is_active' => true,
        ]);
        VipPlan::create([
            'name' => 'Retired',
            'slug' => 'vip-retired',
            'price' => 10,
            'duration_days' => 365,
            'is_active' => false,
        ]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/vip-plans')
            ->assertOk()
            ->assertJsonCount(2, 'plans')
            ->assertJsonPath('plans.0.slug', 'vip-retired');
    }

    public function test_admin_can_update_vip_plan_price(): void
    {
        $plan = VipPlan::create([
            'name' => 'VIP Yearly',
            'slug' => 'vip-yearly',
            'price' => 59.99,
            'duration_days' => 365,
            'is_active' => true,
        ]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/vip-plans/{$plan->id}", ['price' => 79.99])
            ->assertOk()
            ->assertJsonPath('plan.price', '79.99');

        $plan->refresh();
        $this->assertEqualsWithDelta(79.99, (float) $plan->price, 0.001);
        $this->assertSame(365, $plan->duration_days);
        $this->assertFalse($plan->is_test);

        $this->getJson('/api/v1/vip-plans')
            ->assertOk()
            ->assertJsonPath('plans.0.price', '79.99');
    }

    public function test_price_only_update_preserves_test_plan_term(): void
    {
        config()->set('vip.test_mode', true);

        $plan = VipPlan::create([
            'name' => 'VIP Test Card',
            'slug' => 'vip-test',
            'price' => 0.01,
            'duration_hours' => 5,
            'duration_days' => 0,
            'is_test' => true,
            'is_active' => true,
        ]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/vip-plans/{$plan->id}", ['price' => 0.99])
            ->assertOk();

        $plan->refresh();
        $this->assertTrue($plan->is_test);
        $this->assertSame(5, $plan->duration_hours);
        $this->assertSame(0, $plan->duration_days);
        $this->assertEqualsWithDelta(0.99, (float) $plan->price, 0.001);
    }

    public function test_admin_can_update_full_vip_plan_details(): void
    {
        $plan = VipPlan::create([
            'name' => 'VIP Yearly',
            'slug' => 'vip-yearly',
            'price' => 59.99,
            'duration_days' => 365,
            'is_active' => true,
        ]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/vip-plans/{$plan->id}", [
                'name' => 'VIP Yearly Plus',
                'slug' => 'vip-yearly-plus',
                'description' => 'Full access for one year.',
                'price' => 69.99,
                'is_active' => false,
                'is_test' => false,
            ])
            ->assertOk()
            ->assertJsonPath('plan.name', 'VIP Yearly Plus')
            ->assertJsonPath('plan.slug', 'vip-yearly-plus')
            ->assertJsonPath('plan.description', 'Full access for one year.')
            ->assertJsonPath('plan.is_active', false);

        $plan->refresh();
        $this->assertSame(69.99, (float) $plan->price);
        $this->assertSame(365, $plan->duration_days);
        $this->assertFalse($plan->is_active);
    }

    public function test_admin_can_update_test_card_term(): void
    {
        config()->set('vip.test_mode', true);

        $plan = VipPlan::create([
            'name' => 'VIP Test Card',
            'slug' => 'vip-test',
            'price' => 0.01,
            'duration_hours' => 5,
            'duration_days' => 0,
            'is_test' => true,
            'is_active' => true,
        ]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/vip-plans/{$plan->id}", [
                'is_test' => true,
                'duration_hours' => 12,
            ])
            ->assertOk();

        $plan->refresh();
        $this->assertSame(12, $plan->duration_hours);
        $this->assertSame(0, $plan->duration_days);
        $this->assertTrue($plan->is_test);
    }

    public function test_invalid_price_is_rejected(): void
    {
        $plan = VipPlan::factory()->create();

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/vip-plans/{$plan->id}", ['price' => 0])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['price']);
    }

    public function test_reader_cannot_update_vip_plan_price(): void
    {
        $plan = VipPlan::factory()->create(['price' => 59.99]);
        $reader = User::factory()->create();

        $this->actingAs($reader, 'sanctum')
            ->putJson("/api/v1/admin/vip-plans/{$plan->id}", ['price' => 99.99])
            ->assertStatus(403);

        $this->assertEqualsWithDelta(59.99, (float) $plan->fresh()->price, 0.001);
    }
}

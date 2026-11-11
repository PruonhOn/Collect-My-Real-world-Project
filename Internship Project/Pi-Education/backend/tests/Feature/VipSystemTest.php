<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VipPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VipSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_guest_can_list_active_vip_plans(): void
    {
        $response = $this->withHeader('X-Locale', 'en')->getJson('/api/v1/vip/plans');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['plans']]);

        $plans = collect($response->json('data.plans'));
        $this->assertEqualsCanonicalizing(
            ['monthly', 'quarterly', 'yearly'],
            $plans->pluck('slug')->all()
        );
    }

    public function test_inactive_plans_are_hidden_from_public_list(): void
    {
        VipPlan::where('slug', 'monthly')->update(['is_active' => false]);

        $response = $this->getJson('/api/v1/vip/plans');

        $slugs = collect($response->json('data.plans'))->pluck('slug');
        $this->assertFalse($slugs->contains('monthly'));
    }

    public function test_plan_translations_follow_locale_header(): void
    {
        $en = $this->withHeader('X-Locale', 'en')->getJson('/api/v1/vip/plans');

        $monthly = collect($en->json('data.plans'))->firstWhere('slug', 'monthly');
        $this->assertEquals('Monthly', $monthly['name']);

        $km = $this->withHeader('X-Locale', 'km')->getJson('/api/v1/vip/plans');
        $monthlyKm = collect($km->json('data.plans'))->firstWhere('slug', 'monthly');
        $this->assertEquals('ប្រចាំខែ', $monthlyKm['name']);
    }

    public function test_subscribing_activates_vip(): void
    {
        $user = User::factory()->create();
        $plan = VipPlan::where('slug', 'monthly')->firstOrFail();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/vip/subscribe', ['plan_id' => $plan->id]);

        $response->assertStatus(201)
            ->assertJsonPath('data.is_vip', true);

        $this->assertDatabaseHas('vip_subscriptions', [
            'user_id' => $user->id,
            'vip_plan_id' => $plan->id,
            'status' => 'active',
        ]);

        $this->assertTrue($user->fresh()->isVipActive());
    }

    public function test_subscribing_again_stacks_days(): void
    {
        $user = User::factory()->create();
        $plan = VipPlan::where('slug', 'quarterly')->firstOrFail();

        $this->actingAs($user)->postJson('/api/v1/vip/subscribe', ['plan_id' => $plan->id]);

        $firstEnd = $user->fresh()->vip_until;

        $this->actingAs($user)->postJson('/api/v1/vip/subscribe', ['plan_id' => $plan->id]);

        $secondEnd = $user->fresh()->vip_until;

        $this->assertEqualsWithDelta(90, $firstEnd->diffInDays($secondEnd), 0.01);
    }

    public function test_cannot_subscribe_to_invalid_or_inactive_plan(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/vip/subscribe', ['plan_id' => 9999])
            ->assertStatus(422);

        VipPlan::where('slug', 'yearly')->update(['is_active' => false]);
        $inactive = VipPlan::where('slug', 'yearly')->firstOrFail();

        $this->actingAs($user)
            ->postJson('/api/v1/vip/subscribe', ['plan_id' => $inactive->id])
            ->assertStatus(422);
    }

    public function test_guest_cannot_subscribe(): void
    {
        $plan = VipPlan::firstOrFail();

        $this->postJson('/api/v1/vip/subscribe', ['plan_id' => $plan->id])->assertStatus(401);
    }

    public function test_vip_status_reflects_subscription(): void
    {
        $user = User::factory()->create();
        $plan = VipPlan::where('slug', 'monthly')->firstOrFail();

        $this->actingAs($user)->postJson('/api/v1/vip/subscribe', ['plan_id' => $plan->id]);

        $response = $this->actingAs($user)->getJson('/api/v1/vip/status');

        $response->assertStatus(200)
            ->assertJsonPath('data.is_vip', true)
            ->assertJsonPath('data.subscription.slug', 'monthly');
    }
}

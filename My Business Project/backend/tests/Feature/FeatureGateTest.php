<?php

use App\Models\BusinessSubscription;
use App\Models\User;
use App\Services\Features\FeatureGate;

/*
|--------------------------------------------------------------------------
| Subscription entitlements
|--------------------------------------------------------------------------
|
| The plan catalogue is the single source of truth for what a business may
| do. These tests prove that a free business is blocked from paid features
| and that upgrading immediately unlocks them.
|
*/

it('grants only the free entitlements to a free business', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    $gate = app(FeatureGate::class);

    expect($gate->allows($business, 'basic_dashboard'))->toBeTrue();
    expect($gate->allows($business, 'advanced_reports'))->toBeFalse();
    expect($gate->allows($business, 'multi_branch'))->toBeFalse();
    expect($gate->allows($business, 'api_access'))->toBeFalse();
    expect($gate->allows($business, 'pdf_excel_exports'))->toBeFalse();
});

it('grants the pro entitlements to a pro business', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner, proPlan());

    $gate = app(FeatureGate::class);

    expect($gate->allows($business, 'advanced_reports'))->toBeTrue();
    expect($gate->allows($business, 'multi_branch'))->toBeTrue();
    expect($gate->allows($business, 'api_access'))->toBeTrue();
    expect($gate->allows($business, 'pdf_excel_exports'))->toBeTrue();
});

it('enforces the numeric limits of the free plan', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    $gate = app(FeatureGate::class);

    expect($gate->limit($business, 'max_products'))->toBe(50);
    expect($gate->limit($business, 'max_customers'))->toBe(100);
    expect($gate->limit($business, 'max_members'))->toBe(2);
    expect($gate->limit($business, 'max_orders_monthly'))->toBe(100);
});

it('treats pro limits as unlimited', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner, proPlan());

    $gate = app(FeatureGate::class);

    expect($gate->limit($business, 'max_products'))->toBeNull();
    expect($gate->limit($business, 'max_customers'))->toBeNull();
    expect($gate->limit($business, 'max_orders_monthly'))->toBeNull();
});

it('blocks a pro only report for a free business', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    apiAs($owner, $business, 'GET', '/api/v1/business/reports/sales')
        ->assertForbidden()
        ->assertJsonPath('upgrade_required', true);
});

it('allows a pro only report for a pro business', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner, proPlan());

    apiAs($owner, $business, 'GET', '/api/v1/business/reports/sales')
        ->assertOk()
        ->assertJsonStructure(['data' => ['from', 'to', 'totals', 'daily', 'by_status']]);
});

it('blocks multi branch management for a free business', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    apiAs($owner, $business, 'GET', '/api/v1/business/branches')->assertForbidden();
});

it('allows multi branch management for a pro business', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner, proPlan());

    apiAs($owner, $business, 'GET', '/api/v1/business/branches')->assertOk();
});

it('denies every paid feature once the subscription expired', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner, proPlan());

    BusinessSubscription::where('business_id', $business->id)->update([
        'status' => 'expired',
        'ends_at' => now()->subDay(),
    ]);

    $gate = app(FeatureGate::class);
    $gate->invalidate($business->fresh());

    expect($gate->allows($business->fresh(), 'advanced_reports'))->toBeFalse();
    expect($gate->allows($business->fresh(), 'api_access'))->toBeFalse();

    apiAs($owner, $business->fresh(), 'GET', '/api/v1/business/reports/sales')->assertForbidden();
});

it('keeps access during the grace period of a past due subscription', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner, proPlan());

    BusinessSubscription::where('business_id', $business->id)->update([
        'status' => 'past_due',
        'grace_ends_at' => now()->addDay(),
    ]);

    $gate = app(FeatureGate::class);
    $gate->invalidate($business->fresh());

    expect($gate->allows($business->fresh(), 'advanced_reports'))->toBeTrue();
});

it('revokes access when the grace period of a past due subscription is over', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner, proPlan());

    BusinessSubscription::where('business_id', $business->id)->update([
        'status' => 'past_due',
        'grace_ends_at' => now()->subMinute(),
    ]);

    $gate = app(FeatureGate::class);
    $gate->invalidate($business->fresh());

    expect($gate->allows($business->fresh(), 'advanced_reports'))->toBeFalse();
});

it('keeps access for a canceled subscription until the paid period ends', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner, proPlan());

    BusinessSubscription::where('business_id', $business->id)->update([
        'status' => 'canceled',
        'ends_at' => now()->addWeek(),
        'canceled_at' => now(),
    ]);

    $gate = app(FeatureGate::class);
    $gate->invalidate($business->fresh());

    expect($gate->allows($business->fresh(), 'advanced_reports'))->toBeTrue();
});

it('enforces the product limit of the free plan', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    $gate = app(FeatureGate::class);

    for ($i = 0; $i < 50; $i++) {
        $gate->incrementUsage($business, 'max_products');
    }

    expect($gate->checkLimit($business, 'max_products'))->toBeFalse();

    apiAs($owner, $business, 'POST', '/api/v1/business/products', [
        'name' => 'One too many',
        'price' => 100,
    ])->assertStatus(422)->assertJsonPath('upgrade_required', true);
});

it('never limits products on the pro plan', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner, proPlan());

    $gate = app(FeatureGate::class);

    for ($i = 0; $i < 200; $i++) {
        $gate->incrementUsage($business, 'max_products');
    }

    expect($gate->checkLimit($business, 'max_products'))->toBeTrue();

    apiAs($owner, $business, 'POST', '/api/v1/business/products', [
        'name' => 'Still allowed',
        'price' => 100,
    ])->assertCreated();
});

it('enforces the monthly order limit of the free plan', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    $gate = app(FeatureGate::class);

    for ($i = 0; $i < 100; $i++) {
        $gate->incrementUsage($business, 'max_orders_monthly');
    }

    $product = \App\Models\Product::withoutGlobalScopes()->create([
        'business_id' => $business->id,
        'name' => 'Widget',
        'price' => 1000,
    ]);

    apiAs($owner, $business, 'POST', '/api/v1/business/orders', [
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ])->assertStatus(422)->assertJsonPath('upgrade_required', true);
});

it('tracks and releases usage when a resource is deleted', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    $created = apiAs($owner, $business, 'POST', '/api/v1/business/products', [
        'name' => 'Temporary product',
        'price' => 100,
    ])->assertCreated()->json('data');

    $gate = app(FeatureGate::class);
    expect($gate->usage($business, 'max_products'))->toBe(1);

    apiAs($owner, $business, 'DELETE', "/api/v1/business/products/{$created['id']}")->assertOk();

    expect($gate->usage($business, 'max_products'))->toBe(0);
});

it('exposes the usage summary on the dashboard', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    $response = apiAs($owner, $business, 'GET', '/api/v1/business/dashboard');

    $response->assertOk()->assertJsonStructure([
        'data' => ['today_sales', 'month_sales', 'order_count', 'customer_count', 'product_count', 'usage'],
    ]);

    $keys = collect($response->json('data.usage'))->pluck('key');

    expect($keys)->toContain('max_products', 'max_customers', 'max_members', 'max_orders_monthly');
});

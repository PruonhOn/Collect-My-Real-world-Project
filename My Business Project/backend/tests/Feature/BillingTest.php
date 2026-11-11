<?php

use App\Models\BusinessSubscription;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Services\Payments\Gateways\FakeGateway;

/*
|--------------------------------------------------------------------------
| Billing
|--------------------------------------------------------------------------
|
| The billing domain is verified through the deterministic fake gateway. A
| subscription may only be activated by an authoritative server-side
| confirmation; a client can never assert that a payment succeeded.
|
*/

it('creates a pending charge and a billing invoice on checkout', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    $response = apiAs($owner, $business, 'POST', '/api/v1/business/billing/checkout', [
        'plan_id' => proPlan()->id,
        'billing_cycle' => 'monthly',
        'gateway' => 'fake',
    ]);

    $response->assertCreated()->assertJsonStructure([
        'data' => ['checkout_url', 'reference', 'payment', 'invoice'],
    ]);

    $payment = Payment::where('business_id', $business->id)->firstOrFail();

    expect($payment->status)->toBe('pending');
    expect($payment->amount)->toBe(proPlan()->price_monthly);
    expect($payment->invoice)->not->toBeNull();
    expect($payment->invoice->status)->toBe('open');
});

it('does not activate a subscription until the provider confirms the payment', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    $checkout = apiAs($owner, $business, 'POST', '/api/v1/business/billing/checkout', [
        'plan_id' => proPlan()->id,
        'billing_cycle' => 'monthly',
        'gateway' => 'fake',
    ])->json('data');

    // The client cannot assert success.
    apiAs($owner, $business, 'POST', '/api/v1/business/billing/confirm', [
        'reference' => $checkout['reference'],
    ])->assertStatus(422);

    expect($business->subscription()->first()->status)->toBe('active');
    expect($business->subscription()->first()->plan_id)->toBe(freePlan()->id);

    // The provider confirms the transaction.
    FakeGateway::simulatePayment($checkout['reference']);

    apiAs($owner, $business, 'POST', '/api/v1/business/billing/confirm', [
        'reference' => $checkout['reference'],
    ])->assertOk();

    $subscription = $business->subscription()->first();

    expect($subscription->status)->toBe('active');
    expect($subscription->plan_id)->toBe(proPlan()->id);
    expect($subscription->billing_cycle)->toBe('monthly');

    $payment = Payment::where('business_id', $business->id)->firstOrFail();

    expect($payment->status)->toBe('succeeded');
    expect($payment->paid_at)->not->toBeNull();
    expect($payment->invoice->fresh()->status)->toBe('paid');
});

it('activates the pro entitlements after a confirmed payment', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    $gate = app(\App\Services\Features\FeatureGate::class);

    expect($gate->allows($business, 'advanced_reports'))->toBeFalse();

    $checkout = apiAs($owner, $business, 'POST', '/api/v1/business/billing/checkout', [
        'plan_id' => proPlan()->id,
        'billing_cycle' => 'monthly',
        'gateway' => 'fake',
    ])->json('data');

    FakeGateway::simulatePayment($checkout['reference']);

    apiAs($owner, $business, 'POST', '/api/v1/business/billing/confirm', [
        'reference' => $checkout['reference'],
    ])->assertOk();

    expect($gate->allows($business, 'advanced_reports'))->toBeTrue();
    expect($gate->limit($business, 'max_products'))->toBeNull();
});

it('refuses a payment whose amount does not match the plan price', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    $checkout = apiAs($owner, $business, 'POST', '/api/v1/business/billing/checkout', [
        'plan_id' => proPlan()->id,
        'billing_cycle' => 'monthly',
        'gateway' => 'fake',
    ])->json('data');

    FakeGateway::$transactions[$checkout['reference']]['amount'] = 1;

    apiAs($owner, $business, 'POST', '/api/v1/business/billing/confirm', [
        'reference' => $checkout['reference'],
    ])->assertStatus(422);

    expect($business->subscription()->first()->plan_id)->toBe(freePlan()->id);
});

it('refuses a payment whose currency does not match the plan currency', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    $checkout = apiAs($owner, $business, 'POST', '/api/v1/business/billing/checkout', [
        'plan_id' => proPlan()->id,
        'billing_cycle' => 'monthly',
        'gateway' => 'fake',
    ])->json('data');

    FakeGateway::$transactions[$checkout['reference']]['currency'] = 'KHR';

    apiAs($owner, $business, 'POST', '/api/v1/business/billing/confirm', [
        'reference' => $checkout['reference'],
    ])->assertStatus(422);

    expect($business->subscription()->first()->plan_id)->toBe(freePlan()->id);
});

it('rejects an unknown gateway', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    apiAs($owner, $business, 'POST', '/api/v1/business/billing/checkout', [
        'plan_id' => proPlan()->id,
        'billing_cycle' => 'monthly',
        'gateway' => 'not-a-gateway',
    ])->assertStatus(422);
});

it('never activates a subscription for the free plan', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    apiAs($owner, $business, 'POST', '/api/v1/business/billing/checkout', [
        'plan_id' => freePlan()->id,
        'billing_cycle' => 'monthly',
        'gateway' => 'fake',
    ])->assertStatus(422);
});

it('cancels and resumes a subscription', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner, proPlan());

    apiAs($owner, $business, 'POST', '/api/v1/business/billing/portal', [
        'action' => 'cancel',
    ])->assertOk();

    expect($business->subscription()->first()->status)->toBe('canceled');

    apiAs($owner, $business, 'POST', '/api/v1/business/billing/portal', [
        'action' => 'resume',
    ])->assertOk();

    expect($business->subscription()->first()->status)->toBe('active');
});

it('records an audit trail for every subscription transition', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner, proPlan());

    apiAs($owner, $business, 'POST', '/api/v1/business/billing/portal', [
        'action' => 'cancel',
    ])->assertOk();

    expect(\App\Models\SubscriptionAuditLog::where('business_id', $business->id)->count())->toBeGreaterThan(0);
});

it('processes a webhook exactly once', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    $checkout = apiAs($owner, $business, 'POST', '/api/v1/business/billing/checkout', [
        'plan_id' => proPlan()->id,
        'billing_cycle' => 'monthly',
        'gateway' => 'fake',
    ])->json('data');

    FakeGateway::simulatePayment($checkout['reference']);

    $payload = [
        'event_id' => 'evt_test_0001',
        'event_type' => 'succeeded',
        'reference' => $checkout['reference'],
        'amount' => proPlan()->price_monthly,
        'currency' => proPlan()->currency,
        'business_id' => $business->id,
    ];

    $this->withHeader('X-Fake-Signature', FakeGateway::$webhookSecret)
        ->postJson('/api/v1/webhooks/fake', $payload)
        ->assertOk();

    expect($business->subscription()->first()->plan_id)->toBe(proPlan()->id);

    // A duplicate delivery of the same event must not change anything.
    $this->withHeader('X-Fake-Signature', FakeGateway::$webhookSecret)
        ->postJson('/api/v1/webhooks/fake', $payload)
        ->assertOk()
        ->assertJsonPath('message', 'Event already processed.');

    expect(\App\Models\PaymentWebhookEvent::where('event_id', 'evt_test_0001')->count())->toBe(1);
    expect(BusinessSubscription::where('business_id', $business->id)->count())->toBe(1);
});

it('rejects a webhook with an invalid signature', function () {
    seedPlans();

    $this->withHeader('X-Fake-Signature', 'wrong-secret')
        ->postJson('/api/v1/webhooks/fake', ['event_id' => 'evt_test_0002'])
        ->assertStatus(400);

    expect(\App\Models\PaymentWebhookEvent::count())->toBe(0);
});

it('does not activate a subscription for a webhook with a wrong amount', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    $checkout = apiAs($owner, $business, 'POST', '/api/v1/business/billing/checkout', [
        'plan_id' => proPlan()->id,
        'billing_cycle' => 'monthly',
        'gateway' => 'fake',
    ])->json('data');

    $this->withHeader('X-Fake-Signature', FakeGateway::$webhookSecret)
        ->postJson('/api/v1/webhooks/fake', [
            'event_id' => 'evt_test_0003',
            'event_type' => 'succeeded',
            'reference' => $checkout['reference'],
            'amount' => 1,
            'currency' => proPlan()->currency,
            'business_id' => $business->id,
        ])->assertStatus(500);

    expect($business->subscription()->first()->plan_id)->toBe(freePlan()->id);
});

it('expires a subscription when the paid period ends', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner, proPlan());

    BusinessSubscription::where('business_id', $business->id)
        ->update(['status' => 'active', 'ends_at' => now()->subMinute()]);

    $this->artisan('subscriptions:expire')->assertSuccessful();

    expect($business->subscription()->first()->status)->toBe('expired');
});

it('expires a past due subscription after the grace period', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner, proPlan());

    BusinessSubscription::where('business_id', $business->id)->update([
        'status' => 'past_due',
        'grace_ends_at' => now()->subMinute(),
    ]);

    $this->artisan('subscriptions:grace-period')->assertSuccessful();

    expect($business->subscription()->first()->status)->toBe('expired');
});

it('ends a trial that was never paid for', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner, proPlan());

    BusinessSubscription::where('business_id', $business->id)->update([
        'status' => 'trialing',
        'trial_ends_at' => now()->subMinute(),
    ]);

    $this->artisan('subscriptions:expire-trials')->assertSuccessful();

    expect($business->subscription()->first()->status)->toBe('expired');
});

it('keeps business data when a subscription expires', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner, proPlan());

    apiAs($owner, $business, 'POST', '/api/v1/business/products', [
        'name' => 'Kept product',
        'price' => 500,
    ])->assertCreated();

    BusinessSubscription::where('business_id', $business->id)->update([
        'status' => 'expired',
        'ends_at' => now()->subMinute(),
    ]);

    $this->artisan('subscriptions:expire')->assertSuccessful();

    $products = apiAs($owner, $business, 'GET', '/api/v1/business/products');
    $products->assertOk();

    expect(collect($products->json('data'))->pluck('name'))->toContain('Kept product');
});

it('exposes the plan catalogue and the current subscription', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    $response = apiAs($owner, $business, 'GET', '/api/v1/business/billing');

    $response->assertOk()->assertJsonStructure([
        'data' => ['subscription', 'plans', 'gateways', 'usage'],
    ]);

    expect(collect($response->json('data.plans'))->pluck('slug'))->toContain('free', 'pro-monthly', 'pro-yearly');
});

it('lists the billing invoices of the business only', function () {
    seedPlans();

    $owner = User::factory()->create();
    $mine = makeBusiness($owner);
    $theirs = makeBusiness(User::factory()->create());

    \App\Models\Invoice::withoutGlobalScopes()->create([
        'business_id' => $mine->id,
        'invoice_number' => 'BILL-MINE-0001',
        'subtotal' => 100,
        'total' => 100,
        'currency' => 'USD',
        'status' => 'open',
        'issue_date' => now()->toDateString(),
    ]);

    \App\Models\Invoice::withoutGlobalScopes()->create([
        'business_id' => $theirs->id,
        'invoice_number' => 'BILL-THEIRS-0001',
        'subtotal' => 100,
        'total' => 100,
        'currency' => 'USD',
        'status' => 'open',
        'issue_date' => now()->toDateString(),
    ]);

    $response = apiAs($owner, $mine, 'GET', '/api/v1/business/billing/invoices');

    $response->assertOk();

    $numbers = collect($response->json('data'))->pluck('invoice_number');

    expect($numbers)->toContain('BILL-MINE-0001')->not->toContain('BILL-THEIRS-0001');
});

it('never exposes a plan that is not active', function () {
    seedPlans();

    Plan::where('slug', 'pro-yearly')->update(['is_active' => false]);

    $response = $this->getJson('/api/v1/plans');

    $response->assertOk();

    expect(collect($response->json('data'))->pluck('slug'))->not->toContain('pro-yearly');
});

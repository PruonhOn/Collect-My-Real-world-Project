<?php

use App\Models\ApiToken;
use App\Models\Product;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| External API tokens
|--------------------------------------------------------------------------
|
| API access is a paid entitlement. A token is scoped to one business, can
| only perform the abilities it was created with, and can be revoked at any
| time. The plaintext token is only ever returned once.
|
*/

it('refuses to issue an api token for a free business', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    apiAs($owner, $business, 'POST', '/api/v1/business/api-tokens', [
        'name' => 'Integration',
        'abilities' => ['orders:read'],
    ])->assertStatus(403)->assertJsonPath('upgrade_required', true);
});

it('issues an api token for a pro business and shows it once', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner, proPlan());

    $response = apiAs($owner, $business, 'POST', '/api/v1/business/api-tokens', [
        'name' => 'Integration',
        'abilities' => ['orders:read', 'orders:write'],
    ])->assertCreated();

    expect($response->json('token'))->toBeString()->not->toBeEmpty();

    // The plaintext token is never returned again.
    $list = apiAs($owner, $business, 'GET', '/api/v1/business/api-tokens')->assertOk();

    expect($list->json('data'))->toHaveCount(1);
    expect(json_encode($list->json('data')))->not->toContain($response->json('token'));
});

it('stores only a hash of the token', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner, proPlan());

    $plainText = apiAs($owner, $business, 'POST', '/api/v1/business/api-tokens', [
        'name' => 'Integration',
        'abilities' => ['orders:read'],
    ])->assertCreated()->json('token');

    $stored = ApiToken::withoutGlobalScopes()->firstOrFail();

    expect($stored->token_hash)->not->toBe($plainText);
    expect($stored->token_hash)->toBe(hash('sha256', $plainText));
});

it('rejects an unknown ability', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner, proPlan());

    apiAs($owner, $business, 'POST', '/api/v1/business/api-tokens', [
        'name' => 'Integration',
        'abilities' => ['everything:all'],
    ])->assertStatus(422)->assertJsonValidationErrors('abilities.0');
});

it('authenticates an external client with the token', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner, proPlan());

    $plainText = apiAs($owner, $business, 'POST', '/api/v1/business/api-tokens', [
        'name' => 'Integration',
        'abilities' => ['products:read', 'orders:read'],
    ])->assertCreated()->json('token');

    Product::withoutGlobalScopes()->create([
        'business_id' => $business->id,
        'name' => 'External product',
        'price' => 1000,
    ]);

    $response = $this->withHeader('Authorization', 'Bearer '.$plainText)
        ->getJson('/api/v1/external/products');

    $response->assertOk();

    expect(collect($response->json('data'))->pluck('name'))->toContain('External product');
});

it('rejects an external request without a token', function () {
    $this->getJson('/api/v1/external/products')->assertUnauthorized();
});

it('rejects an external request with an unknown token', function () {
    $this->withHeader('Authorization', 'Bearer not-a-real-token')
        ->getJson('/api/v1/external/products')
        ->assertUnauthorized();
});

it('denies an ability the token was not granted', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner, proPlan());

    $plainText = apiAs($owner, $business, 'POST', '/api/v1/business/api-tokens', [
        'name' => 'Read only',
        'abilities' => ['products:read'],
    ])->assertCreated()->json('token');

    $this->withHeader('Authorization', 'Bearer '.$plainText)
        ->postJson('/api/v1/external/orders', [
            'items' => [['product_id' => 1, 'quantity' => 1]],
        ])->assertForbidden();
});

it('scopes an external client to the business of its token', function () {
    seedPlans();

    $owner = User::factory()->create();
    $mine = makeBusiness($owner, proPlan());
    $theirs = makeBusiness(User::factory()->create(), proPlan());

    $plainText = apiAs($owner, $mine, 'POST', '/api/v1/business/api-tokens', [
        'name' => 'Integration',
        'abilities' => ['products:read'],
    ])->assertCreated()->json('token');

    Product::withoutGlobalScopes()->create([
        'business_id' => $mine->id,
        'name' => 'Mine',
        'price' => 100,
    ]);

    Product::withoutGlobalScopes()->create([
        'business_id' => $theirs->id,
        'name' => 'Theirs',
        'price' => 100,
    ]);

    $response = $this->withHeader('Authorization', 'Bearer '.$plainText)
        ->getJson('/api/v1/external/products');

    $response->assertOk();

    expect(collect($response->json('data'))->pluck('name'))->toContain('Mine')->not->toContain('Theirs');
});

it('stops working as soon as the token is revoked', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner, proPlan());

    $plainText = apiAs($owner, $business, 'POST', '/api/v1/business/api-tokens', [
        'name' => 'Integration',
        'abilities' => ['products:read'],
    ])->assertCreated()->json('token');

    $token = ApiToken::withoutGlobalScopes()->firstOrFail();

    apiAs($owner, $business, 'DELETE', "/api/v1/business/api-tokens/{$token->id}")->assertOk();

    $this->withHeader('Authorization', 'Bearer '.$plainText)
        ->getJson('/api/v1/external/products')
        ->assertUnauthorized();
});

it('never lets a business revoke a token of another business', function () {
    seedPlans();

    $owner = User::factory()->create();
    $mine = makeBusiness($owner, proPlan());
    $theirs = makeBusiness(User::factory()->create(), proPlan());

    $foreign = ApiToken::withoutGlobalScopes()->create([
        'business_id' => $theirs->id,
        'user_id' => $theirs->owner_id,
        'name' => 'Foreign token',
        'token_hash' => hash('sha256', 'secret'),
        'abilities' => ['products:read'],
    ]);

    apiAs($owner, $mine, 'DELETE', "/api/v1/business/api-tokens/{$foreign->id}")->assertNotFound();

    expect($foreign->fresh()->revoked_at)->toBeNull();
});

it('stops a token from working when the business loses the api entitlement', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner, proPlan());

    $plainText = apiAs($owner, $business, 'POST', '/api/v1/business/api-tokens', [
        'name' => 'Integration',
        'abilities' => ['products:read'],
    ])->assertCreated()->json('token');

    $this->withHeader('Authorization', 'Bearer '.$plainText)
        ->getJson('/api/v1/external/products')
        ->assertOk();

    \App\Models\BusinessSubscription::where('business_id', $business->id)
        ->update(['plan_id' => freePlan()->id, 'status' => 'active']);

    app(\App\Services\Features\FeatureGate::class)->invalidate($business->fresh());

    $this->withHeader('Authorization', 'Bearer '.$plainText)
        ->getJson('/api/v1/external/products')
        ->assertForbidden()
        ->assertJsonPath('upgrade_required', true);
});

it('records when a token was last used', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner, proPlan());

    $plainText = apiAs($owner, $business, 'POST', '/api/v1/business/api-tokens', [
        'name' => 'Integration',
        'abilities' => ['products:read'],
    ])->assertCreated()->json('token');

    $this->withHeader('Authorization', 'Bearer '.$plainText)
        ->getJson('/api/v1/external/products')
        ->assertOk();

    expect(ApiToken::withoutGlobalScopes()->firstOrFail()->last_used_at)->not->toBeNull();
});

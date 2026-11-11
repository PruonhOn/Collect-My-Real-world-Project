<?php

use App\Models\Business;
use App\Models\BusinessMembership;
use App\Models\User;

it('registers a user and provisions a business with a free subscription', function () {
    seedPlans();

    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Jane Owner',
        'email' => 'jane@example.test',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token', 'business' => ['id', 'name']]);

    $user = User::where('email', 'jane@example.test')->firstOrFail();
    $business = Business::where('owner_id', $user->id)->firstOrFail();

    expect($business->name)->not->toBeEmpty();
    expect($business->subscription)->not->toBeNull();
    expect($business->subscription->plan->slug)->toBe('free');
    expect($business->memberships()->where('user_id', $user->id)->where('status', 'active')->count())->toBe(1);
});

it('rejects a duplicate registration email', function () {
    User::factory()->create(['email' => 'dupe@example.test']);

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Dupe',
        'email' => 'dupe@example.test',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertStatus(422)->assertJsonValidationErrors('email');
});

it('logs a user in with valid credentials', function () {
    $user = User::factory()->create(['email' => 'login@example.test']);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'login@example.test',
        'password' => 'password',
    ])->assertOk()->assertJsonStructure(['user' => ['id', 'email'], 'token']);

    expect($user->fresh()->last_login_at)->not->toBeNull();
});

it('rejects a login with the wrong password', function () {
    User::factory()->create(['email' => 'wrong@example.test']);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'wrong@example.test',
        'password' => 'not-the-password',
    ])->assertStatus(422)->assertJsonValidationErrors('email');
});

it('rejects a login for a disabled account', function () {
    User::factory()->inactive()->create(['email' => 'disabled@example.test']);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'disabled@example.test',
        'password' => 'password',
    ])->assertStatus(403);
});

it('logs out and invalidates the token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('spa')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/auth/logout')
        ->assertOk();

    forgetGuards();

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/user')
        ->assertUnauthorized();
});

it('requires authentication for protected endpoints', function () {
    $this->getJson('/api/v1/businesses')->assertUnauthorized();
});

it('rate limits repeated failed logins', function () {
    User::factory()->create(['email' => 'ratelimit@example.test']);

    for ($i = 0; $i < 6; $i++) {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'ratelimit@example.test',
            'password' => 'wrong-password',
        ]);
    }

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'ratelimit@example.test',
        'password' => 'wrong-password',
    ]);

    expect($response->status())->toBe(429);
});

it('lets a user list only the businesses they belong to', function () {
    seedPlans();

    $user = User::factory()->create();
    $mine = makeBusiness($user);

    $other = makeBusiness(User::factory()->create());

    $response = $this->actingAs($user)->getJson('/api/v1/businesses');

    $response->assertOk();

    $ids = collect($response->json('data'))->pluck('id');

    expect($ids)->toContain($mine->id);
    expect($ids)->not->toContain($other->id);
});

it('enforces the one-business limit on the free plan', function () {
    seedPlans();

    $user = User::factory()->create();
    makeBusiness($user);

    $this->actingAs($user)->postJson('/api/v1/businesses', [
        'name' => 'Second Business',
    ])->assertStatus(422)->assertJsonPath('upgrade_required', true);
});

it('switches to a business the user is an active member of', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    $member = User::factory()->create();
    addMember($business, $member, 'staff', 'active');

    $this->actingAs($member)->postJson('/api/v1/businesses/switch', [
        'business_id' => $business->id,
    ])->assertOk()->assertJsonPath('data.id', $business->id);
});

it('refuses to switch to a business where the membership was revoked', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    $member = User::factory()->create();
    $membership = addMember($business, $member, 'staff', 'active');
    $membership->update(['status' => 'revoked', 'revoked_at' => now()]);

    $this->actingAs($member)->postJson('/api/v1/businesses/switch', [
        'business_id' => $business->id,
    ])->assertStatus(403);
});

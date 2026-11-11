<?php

use App\Models\Business;
use App\Models\BusinessMembership;
use App\Models\BusinessSubscription;
use App\Models\Plan;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The test suite runs against a dedicated MySQL 8 database that mirrors the
| production engine. RefreshDatabase keeps every test hermetic.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Global Hooks
|--------------------------------------------------------------------------
|
| The fake gateway keeps its transactions in a static array. Reset it between
| tests so that a transaction created in one test can never settle a payment
| in another.
|
*/

beforeEach(function () {
    \App\Services\Payments\Gateways\FakeGateway::$transactions = [];
});

/*
|--------------------------------------------------------------------------
| Global Helpers
|--------------------------------------------------------------------------
*/

/**
 * Seed the plan / feature catalogue into the test database.
 */
function seedPlans(): void
{
    test()->seed(PlanSeeder::class);
}

function freePlan(): Plan
{
    return Plan::where('slug', 'free')->firstOrFail();
}

function proPlan(): Plan
{
    return Plan::where('slug', 'pro-monthly')->firstOrFail();
}

/**
 * Create a business owned by the given user with an active subscription on
 * the given plan, plus the matching owner membership.
 */
function makeBusiness(User $owner, ?Plan $plan = null): Business
{
    if (! Plan::where('slug', 'free')->exists()) {
        // The plan catalogue is required to provision a subscription.
        seedPlans();
    }

    $business = Business::factory()->create([
        'owner_id' => $owner->id,
        'currency' => 'USD',
    ]);

    BusinessMembership::create([
        'business_id' => $business->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'status' => 'active',
        'accepted_at' => now(),
    ]);

    $plan = $plan ?? freePlan();

    BusinessSubscription::create([
        'business_id' => $business->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'starts_at' => now(),
    ]);

    return $business->fresh();
}

/**
 * Attach an additional member to a business.
 */
function addMember(Business $business, User $user, string $role = 'staff', string $status = 'active'): BusinessMembership
{
    return BusinessMembership::create([
        'business_id' => $business->id,
        'user_id' => $user->id,
        'role' => $role,
        'status' => $status,
        'accepted_at' => $status === 'active' ? now() : null,
    ]);
}

/**
 * Send an API request with the tenant context header applied.
 */
function apiAs($user, Business $business, string $method, string $uri, array $data = [])
{
    return test()
        ->withHeader('X-Business-Id', (string) $business->id)
        ->actingAs($user)
        ->json($method, $uri, $data);
}

/**
 * Forget the resolved authentication guards.
 *
 * The application instance is reused between requests inside a single test, and
 * `Illuminate\Auth\RequestGuard` memoises the authenticated user. Production
 * boots a fresh application per request, so tests must reset this explicitly
 * whenever the authentication state is expected to change between calls.
 */
function forgetGuards(): void
{
    app('auth')->forgetGuards();
}

<?php

use App\Models\BusinessMembership;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Business profile and workspace settings
|--------------------------------------------------------------------------
|
| The settings screen of the workspace lets an owner or an administrator
| maintain the business profile and the workspace preferences. These tests
| cover the authorisation boundary and the persistence of both payloads.
|
*/

it('returns the business profile to the owner', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    apiAs($owner, $business, 'GET', '/api/v1/businesses/'.$business->id)
        ->assertOk()
        ->assertJsonPath('data.id', $business->id)
        ->assertJsonPath('data.name', $business->name);
});

it('refuses to show a business to an unrelated user', function () {
    seedPlans();

    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $business = makeBusiness($owner);

    apiAs($stranger, $business, 'GET', '/api/v1/businesses/'.$business->id)
        ->assertForbidden();
});

it('lets the owner update the business profile', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    apiAs($owner, $business, 'PUT', '/api/v1/businesses/'.$business->id, [
        'name' => 'Renamed business',
        'currency' => 'EUR',
        'phone' => '+855 12 345 678',
    ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Renamed business')
        ->assertJsonPath('data.currency', 'EUR');

    expect($business->fresh()->name)->toBe('Renamed business')
        ->and($business->fresh()->currency)->toBe('EUR');
});

it('lets a business administrator update the profile but not a staff member', function () {
    seedPlans();

    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $staff = User::factory()->create();
    $business = makeBusiness($owner);

    BusinessMembership::create([
        'business_id' => $business->id,
        'user_id' => $admin->id,
        'role' => 'admin',
        'status' => 'active',
        'accepted_at' => now(),
    ]);

    BusinessMembership::create([
        'business_id' => $business->id,
        'user_id' => $staff->id,
        'role' => 'staff',
        'status' => 'active',
        'accepted_at' => now(),
    ]);

    apiAs($admin, $business, 'PUT', '/api/v1/businesses/'.$business->id, [
        'name' => 'Admin rename',
    ])->assertOk();

    apiAs($staff, $business, 'PUT', '/api/v1/businesses/'.$business->id, [
        'name' => 'Staff rename',
    ])->assertForbidden();
});

it('rejects an invalid currency on the profile', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    apiAs($owner, $business, 'PUT', '/api/v1/businesses/'.$business->id, [
        'currency' => 'EURO',
    ])->assertStatus(422)->assertJsonValidationErrors('currency');
});

it('returns the workspace settings with their defaults', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    apiAs($owner, $business, 'GET', '/api/v1/business/settings')
        ->assertOk()
        ->assertJsonPath('data.settings.invoice_template', 'platform')
        ->assertJsonPath('data.settings.tax_rate', 0)
        ->assertJsonPath('data.settings.date_format', 'Y-m-d')
        ->assertJsonPath('data.settings.low_stock_alerts', false)
        ->assertJsonPath('data.settings.auto_invoice', false)
        ->assertJsonPath('data.business.id', $business->id);
});

it('stores the workspace settings', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    apiAs($owner, $business, 'PUT', '/api/v1/business/settings', [
        'invoice_template' => 'branded',
        'tax_rate' => 1000,
        'date_format' => 'd/m/Y',
        'low_stock_alerts' => true,
        'auto_invoice' => true,
    ])
        ->assertOk()
        ->assertJsonPath('data.invoice_template', 'branded')
        ->assertJsonPath('data.tax_rate', 1000)
        ->assertJsonPath('data.low_stock_alerts', true);

    expect($business->fresh()->settings)->toMatchArray([
        'invoice_template' => 'branded',
        'tax_rate' => 1000,
        'date_format' => 'd/m/Y',
        'low_stock_alerts' => true,
        'auto_invoice' => true,
    ]);
});

it('rejects an unknown invoice template', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    apiAs($owner, $business, 'PUT', '/api/v1/business/settings', [
        'invoice_template' => 'rainbow',
    ])->assertStatus(422)->assertJsonValidationErrors('invoice_template');
});

it('prevents a staff member from changing the workspace settings', function () {
    seedPlans();

    $owner = User::factory()->create();
    $staff = User::factory()->create();
    $business = makeBusiness($owner);

    addMember($business, $staff, 'staff');

    apiAs($staff, $business, 'PUT', '/api/v1/business/settings', [
        'tax_rate' => 500,
    ])->assertForbidden();
});

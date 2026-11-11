<?php

use App\Models\BusinessMembership;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

/*
|--------------------------------------------------------------------------
| Team invitations
|--------------------------------------------------------------------------
|
| An invitation is a membership in the "invited" state. It can only be
| accepted by the person it was addressed to, and only once.
|
*/

it('invites a new member and sends the invitation email', function () {
    seedPlans();

    Notification::fake();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    apiAs($owner, $business, 'POST', "/api/v1/businesses/{$business->id}/invite", [
        'email' => 'invitee@example.test',
        'role' => 'staff',
    ])->assertCreated()->assertJsonPath('data.status', 'invited');

    Notification::assertSentTo(
        Notification::route('mail', 'invitee@example.test'),
        \App\Notifications\BusinessInvitation::class
    );
});

it('refuses to invite a member that already belongs to the business', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    $member = User::factory()->create(['email' => 'member@example.test']);
    addMember($business, $member, 'staff', 'active');

    apiAs($owner, $business, 'POST', "/api/v1/businesses/{$business->id}/invite", [
        'email' => 'member@example.test',
    ])->assertStatus(422);
});

it('refuses a duplicate invitation for the same email', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    apiAs($owner, $business, 'POST', "/api/v1/businesses/{$business->id}/invite", [
        'email' => 'invitee@example.test',
    ])->assertCreated();

    apiAs($owner, $business, 'POST', "/api/v1/businesses/{$business->id}/invite", [
        'email' => 'invitee@example.test',
    ])->assertStatus(422);
});

it('lets the invited person read the invitation without signing in', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    $membership = apiAs($owner, $business, 'POST', "/api/v1/businesses/{$business->id}/invite", [
        'email' => 'invitee@example.test',
    ])->assertCreated()->json('data');

    $this->getJson("/api/v1/invitations/{$membership['invite_token']}")
        ->assertOk()
        ->assertJsonPath('data.business.name', $business->name)
        ->assertJsonPath('data.email', 'invitee@example.test');
});

it('rejects an unknown invitation token', function () {
    $this->getJson('/api/v1/invitations/not-a-real-token')->assertNotFound();
});

it('accepts an invitation as the invited user', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    $membership = apiAs($owner, $business, 'POST', "/api/v1/businesses/{$business->id}/invite", [
        'email' => 'invitee@example.test',
    ])->assertCreated()->json('data');

    $invitee = User::factory()->create(['email' => 'invitee@example.test']);

    apiAs($invitee, $business, 'POST', "/api/v1/invitations/{$membership['invite_token']}/accept")
        ->assertOk()
        ->assertJsonPath('data.status', 'active');

    $stored = BusinessMembership::withoutGlobalScopes()->findOrFail($membership['id']);

    expect($stored->user_id)->toBe($invitee->id);
    expect($stored->invite_token)->toBeNull();
});

it('refuses to accept an invitation that was issued to another email', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    $membership = apiAs($owner, $business, 'POST', "/api/v1/businesses/{$business->id}/invite", [
        'email' => 'invitee@example.test',
    ])->assertCreated()->json('data');

    $stranger = User::factory()->create(['email' => 'stranger@example.test']);

    apiAs($stranger, $business, 'POST', "/api/v1/invitations/{$membership['invite_token']}/accept")
        ->assertForbidden();
});

it('consumes the invitation token once it is accepted', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    $membership = apiAs($owner, $business, 'POST', "/api/v1/businesses/{$business->id}/invite", [
        'email' => 'invitee@example.test',
    ])->assertCreated()->json('data');

    $invitee = User::factory()->create(['email' => 'invitee@example.test']);

    apiAs($invitee, $business, 'POST', "/api/v1/invitations/{$membership['invite_token']}/accept")->assertOk();

    // The token is destroyed when the invitation is accepted, so the link can
    // never be replayed.
    apiAs($invitee, $business, 'POST', "/api/v1/invitations/{$membership['invite_token']}/accept")
        ->assertNotFound();

    expect(BusinessMembership::withoutGlobalScopes()->findOrFail($membership['id'])->status)->toBe('active');
});

it('refuses to accept a revoked invitation', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    $membership = apiAs($owner, $business, 'POST', "/api/v1/businesses/{$business->id}/invite", [
        'email' => 'invitee@example.test',
    ])->assertCreated()->json('data');

    BusinessMembership::withoutGlobalScopes()->where('id', $membership['id'])
        ->update(['status' => 'revoked']);

    $invitee = User::factory()->create(['email' => 'invitee@example.test']);

    apiAs($invitee, $business, 'POST', "/api/v1/invitations/{$membership['invite_token']}/accept")
        ->assertStatus(422);
});

it('requires a signed in user to accept an invitation', function () {
    $this->postJson('/api/v1/invitations/some-token/accept')->assertUnauthorized();
});

it('never lets a member of another business invite people', function () {
    seedPlans();

    $owner = User::factory()->create();
    $business = makeBusiness($owner);

    $stranger = User::factory()->create();

    apiAs($stranger, $business, 'POST', "/api/v1/businesses/{$business->id}/invite", [
        'email' => 'invitee@example.test',
    ])->assertForbidden();
});

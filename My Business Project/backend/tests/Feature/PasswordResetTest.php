<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

/*
|--------------------------------------------------------------------------
| Password reset and email verification
|--------------------------------------------------------------------------
|
| Both flows must work without disclosing whether an account exists and must
| invalidate the sessions of the account that was reset.
|
*/

it('sends a reset link for a known address', function () {
    Notification::fake();

    $user = User::factory()->create(['email' => 'reset@example.test']);

    $this->postJson('/api/v1/auth/forgot-password', [
        'email' => 'reset@example.test',
    ])->assertOk();

    Notification::assertSentTo($user, \Illuminate\Auth\Notifications\ResetPassword::class);
});

it('never discloses whether an address is registered', function () {
    Notification::fake();

    $this->postJson('/api/v1/auth/forgot-password', [
        'email' => 'unknown@example.test',
    ])->assertOk();

    Notification::assertNothingSent();
});

it('validates the email address', function () {
    $this->postJson('/api/v1/auth/forgot-password', [
        'email' => 'not-an-email',
    ])->assertStatus(422)->assertJsonValidationErrors('email');
});

it('resets the password with a valid token', function () {
    $user = User::factory()->create(['email' => 'reset@example.test']);
    $token = Password::createToken($user);

    $this->postJson('/api/v1/auth/reset-password', [
        'token' => $token,
        'email' => 'reset@example.test',
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ])->assertOk();

    expect(Hash::check('NewPassword123!', $user->fresh()->password))->toBeTrue();
});

it('rejects a reset with an invalid token', function () {
    $user = User::factory()->create(['email' => 'reset@example.test']);

    $this->postJson('/api/v1/auth/reset-password', [
        'token' => 'not-a-real-token',
        'email' => 'reset@example.test',
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ])->assertStatus(422);
});

it('requires a confirmed password', function () {
    $user = User::factory()->create(['email' => 'reset@example.test']);
    $token = Password::createToken($user);

    $this->postJson('/api/v1/auth/reset-password', [
        'token' => $token,
        'email' => 'reset@example.test',
        'password' => 'NewPassword123!',
        'password_confirmation' => 'Different123!',
    ])->assertStatus(422)->assertJsonValidationErrors('password');
});

it('invalidates the api tokens of the account after a reset', function () {
    $user = User::factory()->create(['email' => 'reset@example.test']);
    $user->createToken('spa');

    $token = Password::createToken($user);

    $this->postJson('/api/v1/auth/reset-password', [
        'token' => $token,
        'email' => 'reset@example.test',
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ])->assertOk();

    expect($user->fresh()->tokens()->count())->toBe(0);
});

it('sends a verification notification for an unverified account', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    apiAs($user, makeBusiness($user), 'POST', '/api/v1/email/verification-notification')->assertOk();

    Notification::assertSentTo($user, \Illuminate\Auth\Notifications\VerifyEmail::class);
});

it('verifies an email address through a signed link', function () {
    $user = User::factory()->unverified()->create();

    $url = \Illuminate\Support\Facades\URL::temporarySignedRoute(
        'verification.verify',
        now()->addHour(),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $this->getJson($url)->assertOk()->assertJsonPath('status', 'verified');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('refuses an unsigned verification link', function () {
    $user = User::factory()->unverified()->create();

    $this->getJson("/api/v1/email/verify/{$user->id}/".sha1($user->email))
        ->assertStatus(400);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('refuses a verification link that was signed for another account', function () {
    $user = User::factory()->unverified()->create();
    $other = User::factory()->unverified()->create();

    $url = \Illuminate\Support\Facades\URL::temporarySignedRoute(
        'verification.verify',
        now()->addHour(),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    // Swap in the hash of a different account.
    $tampered = str_replace(sha1($user->email), sha1($other->email), $url);

    $this->getJson($tampered)->assertStatus(400);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('refuses to send a verification mail for an already verified address', function () {
    $user = User::factory()->create();

    apiAs($user, makeBusiness($user), 'POST', '/api/v1/email/verification-notification')
        ->assertStatus(422);
});

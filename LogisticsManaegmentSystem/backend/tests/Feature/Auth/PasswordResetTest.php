<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * POST /api/v1/auth/forgot-password and POST /api/v1/auth/reset-password
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private const NEW_PASSWORD = 'N3w-Str0ng-Pass!';

    public function test_a_reset_link_is_emailed_to_a_known_address(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'ops@acme.test']);

        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'ops@acme.test'])
            ->assertOk()
            ->assertJsonPath('success', true);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_an_unknown_address_is_answered_identically(): void
    {
        Notification::fake();

        User::factory()->create(['email' => 'ops@acme.test']);

        $known = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'ops@acme.test']);
        $unknown = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nobody@acme.test']);

        // Reporting "no such account" here would hand out the customer list.
        $known->assertOk();
        $unknown->assertOk();
        $this->assertSame($known->json(), $unknown->json());

        // Exactly one mail went out, the one the known address earned.
        Notification::assertSentTimes(ResetPassword::class, 1);
    }

    public function test_a_malformed_address_still_fails_validation(): void
    {
        // The one case that may be answered differently: the input not being an
        // email address at all reveals nothing about who is registered.
        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'not-an-address'])
            ->assertStatus(422)
            ->assertJsonStructure(['success', 'message', 'errors' => ['email']]);
    }

    public function test_a_valid_token_resets_the_password(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'ops@acme.test']);
        $token = $this->captureTokenFor($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'ops@acme.test',
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertTrue(Hash::check(self::NEW_PASSWORD, $user->fresh()->password));

        // The user is not signed in by a reset: whoever prompted it should not
        // inherit a session.
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_a_reset_kills_sessions_that_were_running_on_the_old_password(): void
    {
        $user = User::factory()->create(['email' => 'ops@acme.test']);
        $user->createToken('spa');

        $this->assertSame(1, $user->tokens()->count());

        $token = Password::broker()->createToken($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'ops@acme.test',
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ])->assertOk();

        // A reset is the user stating the old password is compromised. Any
        // session still running on it has to die with it.
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_a_token_cannot_be_used_twice(): void
    {
        $user = User::factory()->create(['email' => 'ops@acme.test']);
        $token = Password::broker()->createToken($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'ops@acme.test',
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ])->assertOk();

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'ops@acme.test',
            'password' => 'An0ther-Str0ng-Pass!',
            'password_confirmation' => 'An0ther-Str0ng-Pass!',
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_an_invalid_token_is_refused(): void
    {
        User::factory()->create(['email' => 'ops@acme.test']);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'a-token-that-was-never-issued',
            'email' => 'ops@acme.test',
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath(
                'message',
                'This password reset link is invalid or has expired. Please request a new one.',
            );
    }

    public function test_an_expired_token_is_refused(): void
    {
        $user = User::factory()->create(['email' => 'ops@acme.test']);
        $token = Password::broker()->createToken($user);

        $this->travel(config('auth.passwords.users.expire') + 1)->minutes();

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'ops@acme.test',
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ])->assertStatus(422);
    }

    public function test_a_weak_password_is_refused(): void
    {
        // A password distinct from the factory default, so the check below
        // proves the reset did not happen rather than restating the seed.
        $user = User::factory()->create([
            'email' => 'ops@acme.test',
            'password' => 'The-Old-Passw0rd!',
        ]);

        $token = Password::broker()->createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'ops@acme.test',
            // Too short, and no symbol.
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(422)->assertJsonStructure(['success', 'message', 'errors' => ['password']]);

        // The reset must not have gone through on the way to being rejected.
        $this->assertTrue(Hash::check('The-Old-Passw0rd!', $user->fresh()->password));
    }

    public function test_a_mismatched_confirmation_is_refused(): void
    {
        $user = User::factory()->create(['email' => 'ops@acme.test']);
        $token = Password::broker()->createToken($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'ops@acme.test',
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => 'Something-Else-Str0ng!',
        ])
            ->assertStatus(422)
            ->assertJsonStructure(['success', 'message', 'errors' => ['password']]);
    }

    public function test_the_new_password_actually_signs_the_user_in(): void
    {
        Notification::fake();

        User::factory()->create([
            'email' => 'ops@acme.test',
            'password' => 'The-Old-Passw0rd!',
        ]);

        $token = $this->captureTokenFor(User::query()->firstOrFail());

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'ops@acme.test',
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ])->assertOk();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'ops@acme.test',
            'password' => self::NEW_PASSWORD,
        ])->assertOk();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'ops@acme.test',
            'password' => 'The-Old-Passw0rd!',
        ])->assertUnauthorized();
    }

    /**
     * Pulls the reset token out of the notification that would have been sent.
     *
     * Going through the notification rather than calling the broker directly
     * means the token, the URL and the expiry the user receives are the same
     * ones exercised in production.
     */
    private function captureTokenFor(User $user): string
    {
        Notification::fake();

        $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])->assertOk();

        $captured = null;

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$captured): bool {
            $captured = $notification->token;

            return true;
        });

        $this->assertIsString($captured);

        return $captured;
    }
}

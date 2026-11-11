<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * POST /api/v1/auth/login
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Str0ng-Passw0rd!';

    public function test_valid_credentials_return_a_token_and_the_current_user_contract(): void
    {
        $user = User::factory()->create([
            'email' => 'ops@acme.test',
            'password' => self::PASSWORD,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'ops@acme.test',
            'password' => self::PASSWORD,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'ops@acme.test')
            ->assertJsonPath('data.user.is_active', true)
            ->assertJsonPath('data.user.customer', null)
            ->assertJsonPath('data.user.roles', [])
            ->assertJsonPath('data.user.permissions', [])
            // The exact key set frontend/src/types/api.ts declares as
            // CurrentUser. A missing key reads as `undefined` in the SPA, so the
            // assertion is on presence, not just on the values that happen to
            // be non-null here.
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'token',
                    'expires_in',
                    'user' => [
                        'id', 'name', 'email', 'phone', 'avatar_path',
                        'is_active', 'last_login_at', 'customer',
                        'roles', 'permissions',
                    ],
                ],
            ]);

        $this->assertIsString($response->json('data.token'));
        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_the_issued_token_authenticates_subsequent_requests(): void
    {
        $user = User::factory()->create([
            'email' => 'ops@acme.test',
            'password' => self::PASSWORD,
        ]);

        $token = $this->postJson('/api/v1/auth/login', [
            'email' => 'ops@acme.test',
            'password' => self::PASSWORD,
        ])->json('data.token');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);
    }

    public function test_a_successful_sign_in_stamps_last_login_at(): void
    {
        $user = User::factory()->neverLoggedIn()->create([
            'email' => 'ops@acme.test',
            'password' => self::PASSWORD,
        ]);

        $this->assertNull($user->last_login_at);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'ops@acme.test',
            'password' => self::PASSWORD,
        ])->assertOk();

        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_a_wrong_password_is_rejected(): void
    {
        User::factory()->create([
            'email' => 'ops@acme.test',
            'password' => self::PASSWORD,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'ops@acme.test',
            'password' => 'not-the-password',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'These credentials do not match our records.');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_an_unknown_address_is_indistinguishable_from_a_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'ops@acme.test',
            'password' => self::PASSWORD,
        ]);

        $unknown = $this->postJson('/api/v1/auth/login', [
            'email' => 'nobody@acme.test',
            'password' => self::PASSWORD,
        ]);

        $wrongPassword = $this->postJson('/api/v1/auth/login', [
            'email' => 'ops@acme.test',
            'password' => 'not-the-password',
        ]);

        // Any difference here - status, wording, a field error, a response time
        // difference large enough to measure - turns the endpoint into a way to
        // enumerate which addresses are registered.
        $unknown->assertStatus($wrongPassword->status());
        $this->assertSame($unknown->json(), $wrongPassword->json());
    }

    public function test_a_deactivated_account_is_refused_even_with_the_right_password(): void
    {
        User::factory()->deactivated()->create([
            'email' => 'ops@acme.test',
            'password' => self::PASSWORD,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'ops@acme.test',
            'password' => self::PASSWORD,
        ])
            ->assertForbidden()
            ->assertJsonPath('message', 'Your account has been deactivated. Please contact an administrator.');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_remember_me_issues_a_token_that_does_not_expire(): void
    {
        $user = User::factory()->create([
            'email' => 'ops@acme.test',
            'password' => self::PASSWORD,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'ops@acme.test',
            'password' => self::PASSWORD,
            'remember' => true,
        ])->assertOk();

        // Without the `remember` ability a "keep me signed in" tick box would do
        // nothing at all, because the token would still die at expiry.
        $this->assertContains('remember', $user->tokens()->sole()->abilities);
    }

    public function test_a_normal_sign_in_issues_a_token_with_no_abilities(): void
    {
        $user = User::factory()->create([
            'email' => 'ops@acme.test',
            'password' => self::PASSWORD,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'ops@acme.test',
            'password' => self::PASSWORD,
        ])->assertOk();

        $this->assertSame([], $user->tokens()->sole()->abilities);
    }

    public function test_missing_fields_are_reported_as_a_validation_failure(): void
    {
        $this->postJson('/api/v1/auth/login', [])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['success', 'message', 'errors' => ['email', 'password']]);
    }

    public function test_the_address_is_matched_regardless_of_case_or_padding(): void
    {
        User::factory()->create([
            'email' => 'ops@acme.test',
            'password' => self::PASSWORD,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => '  OPS@Acme.Test  ',
            'password' => self::PASSWORD,
        ])->assertOk();
    }

    public function test_repeated_failures_lock_the_address_and_ip_pair_out(): void
    {
        User::factory()->create([
            'email' => 'ops@acme.test',
            'password' => self::PASSWORD,
        ]);

        $attempts = (int) config('logistics.auth.login_max_attempts');

        for ($attempt = 0; $attempt < $attempts; $attempt++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'ops@acme.test',
                'password' => 'not-the-password',
            ])->assertUnauthorized();
        }

        // The correct password is now refused too, until the window passes.
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'ops@acme.test',
            'password' => self::PASSWORD,
        ]);

        $response->assertStatus(429)
            ->assertJsonPath('success', false)
            ->assertHeader('Retry-After');

        $this->assertStringContainsString('Too many sign-in attempts', $response->json('message'));
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_a_lockout_does_not_extend_to_a_different_address(): void
    {
        User::factory()->create([
            'email' => 'ops@acme.test',
            'password' => self::PASSWORD,
        ]);

        User::factory()->create([
            'email' => 'finance@acme.test',
            'password' => self::PASSWORD,
        ]);

        $attempts = (int) config('logistics.auth.login_max_attempts');

        for ($attempt = 0; $attempt <= $attempts; $attempt++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'ops@acme.test',
                'password' => 'not-the-password',
            ]);
        }

        // Keying the limiter on the address alone would let anyone lock a
        // colleague out of their own account.
        $this->postJson('/api/v1/auth/login', [
            'email' => 'finance@acme.test',
            'password' => self::PASSWORD,
        ])->assertOk();
    }

    public function test_a_successful_sign_in_clears_the_failure_count(): void
    {
        User::factory()->create([
            'email' => 'ops@acme.test',
            'password' => self::PASSWORD,
        ]);

        $attempts = (int) config('logistics.auth.login_max_attempts');

        for ($attempt = 0; $attempt < $attempts - 1; $attempt++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'ops@acme.test',
                'password' => 'not-the-password',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/v1/auth/login', [
            'email' => 'ops@acme.test',
            'password' => self::PASSWORD,
        ])->assertOk();

        // Otherwise a user who fumbles their password a few times is left
        // waiting out a lockout they have already served the time for.
        $this->postJson('/api/v1/auth/login', [
            'email' => 'ops@acme.test',
            'password' => 'not-the-password',
        ])->assertUnauthorized();

        $this->assertTrue(Hash::check(self::PASSWORD, User::query()->firstOrFail()->password));
    }
}

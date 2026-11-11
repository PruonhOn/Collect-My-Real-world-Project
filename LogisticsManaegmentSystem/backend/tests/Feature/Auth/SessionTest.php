<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GET /api/v1/auth/me and POST /api/v1/auth/logout
 */
class SessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_me_requires_a_token(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthenticated. Please sign in again.');
    }

    public function test_me_returns_the_signed_in_user_with_roles_and_permissions(): void
    {
        $this->seedPermissions();

        $user = User::factory()->withRole('operations-clerk')->create();

        $this->withToken($user->createToken('spa')->plainTextToken)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.roles', ['operations-clerk'])
            ->assertJsonPath('data.is_active', true);
    }

    public function test_me_returns_effective_permissions_including_those_from_the_role(): void
    {
        $this->seedPermissions();

        $user = User::factory()->withRole('finance-officer')->create();

        $response = $this->withToken($user->createToken('spa')->plainTextToken)
            ->getJson('/api/v1/auth/me')
            ->assertOk();

        $permissions = $response->json('data.permissions');

        $this->assertIsArray($permissions);
        $this->assertContains('invoice.create', $permissions);
        $this->assertNotContains('user.view', $permissions);

        // Sorted and de-duplicated: the SPA renders this straight into a
        // permissions list, and a stable order keeps payloads diffable.
        $sorted = $permissions;
        sort($sorted);
        $this->assertSame($sorted, $permissions);
        $this->assertSame(array_values(array_unique($permissions)), $permissions);
    }

    public function test_a_user_with_no_roles_gets_empty_lists_rather_than_nulls(): void
    {
        // The SPA calls Object.entries() and .includes() on these without
        // guarding, so null would break the shell on a freshly created account.
        $user = User::factory()->create();

        $this->withToken($user->createToken('spa')->plainTextToken)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.roles', [])
            ->assertJsonPath('data.permissions', []);
    }

    public function test_logout_requires_a_token(): void
    {
        $this->postJson('/api/v1/auth/logout')->assertUnauthorized();
    }

    public function test_logout_revokes_the_token_that_made_the_request(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('spa')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(0, $user->tokens()->count());

        $this->asNewRequest()->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    }

    public function test_logging_out_on_one_device_leaves_other_sessions_alone(): void
    {
        $user = User::factory()->create();

        $office = $user->createToken('spa')->plainTextToken;
        $phone = $user->createToken('spa')->plainTextToken;

        $this->withToken($office)->postJson('/api/v1/auth/logout')->assertOk();

        $this->assertSame(1, $user->tokens()->count());

        $this->asNewRequest()->withToken($phone)
            ->getJson('/api/v1/auth/me')
            ->assertOk();
    }

    public function test_deactivating_an_account_kills_its_live_sessions(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('spa')->plainTextToken;

        // A token stays valid until it expires, so deactivation has to be checked
        // on every request rather than only at the next sign-in.
        $user->update(['is_active' => false]);

        $this->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertForbidden()
            ->assertJsonPath(
                'message',
                'Your account has been deactivated. Please contact an administrator.',
            );

        // Revoked as a side effect, not merely refused, so a captured token
        // cannot be replayed.
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_a_revoked_token_is_rejected(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('spa')->plainTextToken;

        $user->tokens()->delete();

        $this->asNewRequest()->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    }

    public function test_an_expired_token_is_rejected(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('spa')->plainTextToken;
        $user->tokens()->sole()->forceFill(['expires_at' => now()->subMinute()])->save();

        $this->asNewRequest()->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    }
}

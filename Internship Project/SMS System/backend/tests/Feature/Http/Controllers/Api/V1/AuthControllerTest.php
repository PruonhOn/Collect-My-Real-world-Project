<?php

namespace Tests\Feature\Http\Controllers\Api\V1;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use LazilyRefreshDatabase;
    use SeedsRolesAndPermissions;

    public function test_returns_401_when_requesting_me_without_a_token(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_valid_credentials_create_token_and_return_user(): void
    {
        $this->seedRolesAndPermissions();

        $user = $this->adminUser(['password' => 'secret123']);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'secret123',
            'device_name' => 'pixel-test',
        ])
            ->assertOk()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.email', $user->email)
            ->assertJsonStructure(['data' => ['token', 'user']]);

        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'pixel-test']);
    }

    public function test_returns_422_with_invalid_credentials(): void
    {
        $this->seedRolesAndPermissions();

        $user = $this->adminUser();

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email'])
            ->assertJsonPath('errors.email.0', 'The provided credentials are incorrect.');
    }

    public function test_returns_422_when_account_is_deactivated(): void
    {
        $this->seedRolesAndPermissions();

        $user = $this->adminUser(['is_active' => false, 'password' => 'secret123']);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'This account has been deactivated. Contact the administrator.');
    }

    public function test_returns_422_when_email_is_missing(): void
    {
        $this->postJson('/api/v1/auth/login', ['password' => 'secret123'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_logout_deletes_the_current_token(): void
    {
        $this->seedRolesAndPermissions();

        $user = $this->adminUser();
        $token = $user->createToken('web')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('data.message', 'Logged out.');

        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
    }

    public function test_me_returns_the_authenticated_user(): void
    {
        $this->seedRolesAndPermissions();

        $user = $this->adminUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.primary_role', 'admin');
    }

    public function test_valid_password_change_updates_password_and_revokes_token(): void
    {
        $this->seedRolesAndPermissions();

        $user = $this->adminUser(['password' => 'old-password']);
        $token = $user->createToken('web')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/auth/change-password', [
                'current_password' => 'old-password',
                'new_password' => 'new-password-123',
                'new_password_confirmation' => 'new-password-123',
            ])
            ->assertOk()
            ->assertJsonPath('data.message', 'Password updated. Please log in again.');

        $this->assertTrue(Hash::check('new-password-123', $user->refresh()->password));
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
    }

    public function test_returns_422_when_current_password_does_not_match(): void
    {
        $this->seedRolesAndPermissions();

        $user = $this->adminUser(['password' => 'old-password']);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/change-password', [
                'current_password' => 'wrong',
                'new_password' => 'new-password-123',
                'new_password_confirmation' => 'new-password-123',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['current_password']);
    }
}

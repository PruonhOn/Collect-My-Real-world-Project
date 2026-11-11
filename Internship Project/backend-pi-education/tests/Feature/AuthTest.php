<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'New Reader',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'role']]);

        $this->assertDatabaseHas('users', ['email' => 'new@example.com', 'role' => 'reader']);
    }

    public function test_register_validates_email(): void
    {
        User::factory()->create(['email' => 'dup@example.com']);

        $this->postJson('/api/v1/register', [
            'name' => 'Dup',
            'email' => 'dup@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_register_requires_password_confirmation(): void
    {
        $this->postJson('/api/v1/register', [
            'name' => 'No Confirm',
            'email' => 'noconfirm@example.com',
            'password' => 'password123',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_user_can_login(): void
    {
        User::factory()->create([
            'email' => 'reader@test.com',
            'password' => 'secret123',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'reader@test.com',
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create(['email' => 'ok@test.com', 'password' => 'rightpass']);

        $this->postJson('/api/v1/login', [
            'email' => 'ok@test.com',
            'password' => 'wrongpass',
        ])->assertStatus(401);
    }

    public function test_authenticated_user_can_access_me(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('user.email', $user->email);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/v1/me')->assertStatus(401);
        $this->getJson('/api/v1/library')->assertStatus(401);
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $tokenId = explode('|', $token)[0];

        // Verify token exists before logout.
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $tokenId]);

        $this->postJson('/api/v1/logout', [], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()->assertJsonPath('message', 'Logged out successfully.');

        // Token was deleted from DB after logout.
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
    }

    public function test_user_can_update_profile(): void
    {
        $user = User::factory()->create(['password' => 'oldpass123']);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/me', [
                'name' => 'Updated Name',
                'phone' => '+85512345678',
            ])
            ->assertOk()
            ->assertJsonPath('user.name', 'Updated Name')
            ->assertJsonPath('user.phone', '+85512345678');
    }

    public function test_password_update_requires_current_password(): void
    {
        $user = User::factory()->create(['password' => 'currentpass']);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/me', [
                'password' => 'newpassword',
                'password_confirmation' => 'newpassword',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('current_password');
    }

    public function test_password_is_hashed(): void
    {
        User::factory()->create(['email' => 'hash-me@test.com', 'password' => 'plain123']);

        $user = User::where('email', 'hash-me@test.com')->first();
        $this->assertNotEquals('plain123', $user->password);
        $this->assertTrue(Hash::check('plain123', $user->password));
    }

    public function test_admin_role_user_flagged_as_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->role === 'admin');

        $reader = User::factory()->create();
        $this->assertFalse($reader->isAdmin());
    }
}

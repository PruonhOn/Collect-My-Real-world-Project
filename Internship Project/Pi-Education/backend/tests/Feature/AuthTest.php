<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected string $registeredEmail = 'john@example.com';

    protected string $registeredToken = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => $this->registeredEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->registeredToken = $response->json('data.token');
    }

    public function test_visitor_can_register(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'name', 'email', 'roles']]]);

        $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);
        $this->assertDatabaseHas('role_user', [
            'user_id' => User::where('email', 'jane@example.com')->value('id'),
        ]);
    }

    public function test_register_requires_confirmed_password(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different-pass',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_cannot_register_duplicate_email(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Another Name',
            'email' => $this->registeredEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['email']]);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $this->registeredEmail,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token', 'user']]);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $this->registeredEmail,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJson(['message' => 'Invalid credentials.']);
    }

    public function test_authenticated_user_can_get_current_user(): void
    {
        $response = $this->withToken($this->registeredToken)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.email', $this->registeredEmail)
            ->assertJsonPath('data.roles', ['user']);
    }

    public function test_unauthenticated_user_cannot_get_current_user(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }

    public function test_user_can_logout(): void
    {
        $response = $this->withToken($this->registeredToken)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_token_is_invalid_after_logout(): void
    {
        $this->withToken($this->registeredToken)->postJson('/api/v1/auth/logout');

        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertNull(
            PersonalAccessToken::findToken($this->registeredToken)
        );
    }

    public function test_admin_can_access_admin_area(): void
    {
        $admin = User::where('email', 'admin@pieducation.test')->first();

        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_normal_user_cannot_access_admin_area(): void
    {
        $response = $this->withToken($this->registeredToken)->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }
}

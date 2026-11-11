<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected string $token = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Profile User',
            'email' => 'profile@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->token = $response->json('data.token');
    }

    public function test_user_can_get_profile(): void
    {
        $response = $this->withToken($this->token)->getJson('/api/v1/profile');

        $response->assertStatus(200)
            ->assertJsonPath('data.email', 'profile@example.com');
    }

    public function test_user_can_update_profile(): void
    {
        $response = $this->withToken($this->token)->putJson('/api/v1/profile', [
            'name' => 'Updated Name',
            'locale' => 'en',
            'phone' => '012-345-678',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.locale', 'en');

        $this->assertDatabaseHas('users', [
            'id' => User::where('email', 'profile@example.com')->value('id'),
            'name' => 'Updated Name',
        ]);
    }

    public function test_user_cannot_update_profile_with_invalid_locale(): void
    {
        $response = $this->withToken($this->token)->putJson('/api/v1/profile', [
            'name' => 'Updated Name',
            'locale' => 'de',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['locale']]);
    }

    public function test_user_can_change_password(): void
    {
        $response = $this->withToken($this->token)->postJson('/api/v1/profile/password', [
            'current_password' => 'password123',
            'new_password' => 'newpassword456',
            'new_password_confirmation' => 'newpassword456',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'profile@example.com',
            'password' => 'newpassword456',
        ]);

        $login->assertStatus(200);
    }

    public function test_user_cannot_change_password_with_wrong_current(): void
    {
        $response = $this->withToken($this->token)->postJson('/api/v1/profile/password', [
            'current_password' => 'wrong',
            'new_password' => 'newpassword456',
            'new_password_confirmation' => 'newpassword456',
        ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Current password is incorrect.']);
    }

    public function test_unauthenticated_user_cannot_access_profile(): void
    {
        $this->getJson('/api/v1/profile')->assertStatus(401);
    }
}

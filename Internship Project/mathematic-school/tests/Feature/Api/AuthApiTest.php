<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\WithFaker;

class AuthApiTest extends ApiTestCase
{
    use WithFaker;

    public function test_user_can_register_as_student(): void
    {
        $password = 'password123';
        $email = $this->faker->unique()->safeEmail();

        $response = $this->postJson('/api/v1/register', [
            'name' => 'Test Student',
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
            'role' => 'student',
            'date_of_birth' => '2015-01-01',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['user', 'token'])
            ->assertJsonPath('user.email', $email)
            ->assertJsonPath('user.role', 'student');

        $this->assertDatabaseHas('users', ['email' => $email]);
        $this->assertDatabaseHas('students', ['full_name' => 'Test Student']);
    }

    public function test_user_can_login_and_get_token(): void
    {
        $user = $this->createUser([
            'email' => 'login@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
        ], 'student');
        $this->createStudent($user);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'login@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user', 'profile'])
            ->assertJsonPath('user.email', 'login@example.com');
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $this->createUser([
            'email' => 'wrong@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
        ], 'student');

        $this->postJson('/api/v1/login', [
            'email' => 'wrong@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(422);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $this->createUser([
            'email' => 'inactive@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'is_active' => false,
        ], 'student');

        $this->postJson('/api/v1/login', [
            'email' => 'inactive@example.com',
            'password' => 'password',
        ])->assertForbidden();
    }

    public function test_authenticated_user_can_access_their_profile(): void
    {
        $user = $this->createUser(['role' => 'student'], 'student');
        $this->createStudent($user);

        $this->withHeaders($this->bearerAuth($user))
            ->getJson('/api/v1/user')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonStructure(['user', 'profile']);
    }

    public function test_unauthenticated_user_cannot_access_protected_route(): void
    {
        $this->getJson('/api/v1/my/courses')->assertUnauthorized();
    }

    public function test_user_can_logout(): void
    {
        $user = $this->createUser(['role' => 'student'], 'student');
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/logout')
            ->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $user->tokens()->first()?->id]);
    }
}

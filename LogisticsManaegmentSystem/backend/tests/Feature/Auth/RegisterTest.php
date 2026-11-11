<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_it_registers_a_new_user_successfully(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'Str0ng-Passw0rd!',
            'password_confirmation' => 'Str0ng-Passw0rd!',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', 'jane@example.com')
            ->assertJsonPath('data.verification_required', true);

        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
            'name' => 'Jane Doe',
            'is_active' => true,
        ]);

        $user = User::where('email', 'jane@example.com')->first();
        $this->assertTrue($user->hasRole('viewer'));

        Notification::assertSentTo(
            $user,
            VerifyEmail::class
        );
    }

    public function test_it_validates_registration_inputs(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => '',
            'email' => 'not-an-email',
            'password' => 'short',
            'password_confirmation' => 'mismatch',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_it_rejects_already_verified_duplicate_emails(): void
    {
        User::factory()->create([
            'email' => 'jane@example.com',
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'Str0ng-Passw0rd!',
            'password_confirmation' => 'Str0ng-Passw0rd!',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_it_allows_re_registering_with_unverified_email(): void
    {
        Notification::fake();

        User::factory()->create([
            'name' => 'Old Name',
            'email' => 'jane@example.com',
            'email_verified_at' => null,
        ]);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'New Name',
            'email' => 'jane@example.com',
            'password' => 'Str0ng-Passw0rd!',
            'password_confirmation' => 'Str0ng-Passw0rd!',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
            'name' => 'New Name',
        ]);
    }

    public function test_it_handles_mail_failure_gracefully_during_registration(): void
    {
        Notification::shouldReceive('send')->andThrow(new \Exception('Mail server error'));

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Str0ng-Passw0rd!',
            'password_confirmation' => 'Str0ng-Passw0rd!',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);
    }
}

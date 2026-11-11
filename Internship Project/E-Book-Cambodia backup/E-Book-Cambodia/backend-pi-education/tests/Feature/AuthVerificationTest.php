<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_unverified_user_and_sends_verification_email(): void
    {
        Notification::fake();

        $this->postJson('/api/v1/register', [
            'name' => 'New Reader',
            'email' => 'verify-me@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(201);

        $user = User::where('email', 'verify-me@example.com')->first();

        $this->assertNotNull($user);
        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_email_can_be_verified_via_signed_link(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->get($url)
            ->assertRedirect(config('frontend.url').'/login?verified=1');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_verification_rejects_bad_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1('someone-else@example.com'),
        ]);

        $this->get($url)->assertStatus(403);
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_verification_rejects_link_without_valid_signature(): void
    {
        $user = User::factory()->unverified()->create();

        $url = route('verification.verify', [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->get($url)->assertStatus(403);
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_verification_rejects_expired_link(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute('verification.verify', now()->subMinutes(10), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->get($url)->assertStatus(403);
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_verification_notification_rejected_for_verified_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/email/verification-notification')
            ->assertOk()
            ->assertJsonPath('message', 'Email already verified.');
    }

    public function test_forgot_password_sends_reset_link(): void
    {
        Notification::fake();

        User::factory()->create(['email' => 'reset@example.com']);

        $this->postJson('/api/v1/forgot-password', ['email' => 'reset@example.com'])
            ->assertOk();

        $user = User::where('email', 'reset@example.com')->first();
        Notification::assertSentTo($user, ResetPasswordNotification::class);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => 'reset@example.com']);
    }

    public function test_forgot_password_does_not_reveal_unknown_email(): void
    {
        $this->postJson('/api/v1/forgot-password', ['email' => 'ghost@example.com'])
            ->assertStatus(422);
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'reset-me@example.com',
            'password' => 'oldpass123',
        ]);
        $token = Password::broker()->createToken($user);

        $this->postJson('/api/v1/reset-password', [
            'token' => $token,
            'email' => 'reset-me@example.com',
            'password' => 'newpass456',
            'password_confirmation' => 'newpass456',
        ])->assertOk();

        $this->assertTrue(Hash::check('newpass456', $user->fresh()->password));
    }
}

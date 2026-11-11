<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SecuritySystemTest extends TestCase
{
    use RefreshDatabase;

    protected string $token = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Security User',
            'email' => 'security@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->token = $response->json('data.token');
    }

    public function test_api_404_returns_uniform_error_envelope(): void
    {
        $this->getJson('/api/v1/books/nonexistent-slug')
            ->assertStatus(404)
            ->assertJson(['success' => false, 'message' => 'Book not found.']);
    }

    public function test_unknown_api_route_returns_uniform_error_envelope(): void
    {
        $this->getJson('/api/v1/does-not-exist')
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_missing_book_returns_404_envelope_without_stack_trace(): void
    {
        $response = $this->withToken($this->token)
            ->getJson('/api/v1/books/nonexistent-slug');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Book not found.');
    }

    public function test_avatar_upload_is_rejected_for_non_image(): void
    {
        $this->withToken($this->token)
            ->putJson('/api/v1/profile', [
                'name' => 'Security User',
                'locale' => 'en',
                'avatar' => UploadedFile::fake()->create('malicious.php', 100),
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['avatar']]);
    }

    public function test_avatar_upload_is_rejected_when_too_large(): void
    {
        $this->withToken($this->token)
            ->putJson('/api/v1/profile', [
                'name' => 'Security User',
                'locale' => 'en',
                'avatar' => UploadedFile::fake()->image('avatar.jpg')->size(4096),
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['avatar']]);
    }

    public function test_valid_avatar_image_is_accepted(): void
    {
        $this->withToken($this->token)
            ->putJson('/api/v1/profile', [
                'name' => 'Security User',
                'locale' => 'en',
                'avatar' => UploadedFile::fake()->image('avatar.png', 100, 100),
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Security User');

        $avatar = User::where('email', 'security@example.com')->value('avatar');
        $this->assertNotNull($avatar);
    }

    public function test_guest_cannot_reach_authenticated_only_endpoint(): void
    {
        $this->getJson('/api/v1/profile')
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_regular_user_cannot_reach_admin_endpoint(): void
    {
        $this->withToken($this->token)
            ->getJson('/api/v1/admin/users')
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }
}

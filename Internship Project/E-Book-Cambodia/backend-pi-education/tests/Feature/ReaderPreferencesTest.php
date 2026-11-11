<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Reader display preferences are stored on the account so a student can pick up
 * the same theme and type size on another device. The client falls back to
 * localStorage when signed out.
 */
class ReaderPreferencesTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'fontSize' => 2,
            'theme' => 'sepia',
            'font' => 'serif',
            'lineHeight' => 1.7,
            'mode' => 'spread',
        ], $overrides);
    }

    public function test_guests_cannot_store_reader_preferences(): void
    {
        $this->putJson('/api/v1/me/reader-preferences', $this->payload())
            ->assertStatus(401);
    }

    public function test_a_reader_saves_preferences_and_reads_them_back_from_me(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/me/reader-preferences', $this->payload())
            ->assertOk()
            ->assertJsonPath('reader_preferences.theme', 'sepia')
            ->assertJsonPath('reader_preferences.fontSize', 2)
            ->assertJsonPath('reader_preferences.mode', 'spread');

        $this->assertSame($this->payload(), $user->fresh()->reader_preferences);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('user.reader_preferences.theme', 'sepia')
            ->assertJsonPath('user.reader_preferences.lineHeight', 1.7);
    }

    public function test_preferences_are_replaced_not_merged(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/me/reader-preferences', $this->payload())
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/me/reader-preferences', $this->payload([
                'fontSize' => 4,
                'theme' => 'focus',
                'font' => 'sans',
                'lineHeight' => 2.1,
                'mode' => 'scroll',
            ]))
            ->assertOk()
            ->assertJsonPath('reader_preferences.theme', 'focus');

        $this->assertSame('scroll', $user->fresh()->reader_preferences['mode']);
    }

    public function test_invalid_preferences_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/me/reader-preferences', $this->payload([
                'fontSize' => 99,
                'theme' => 'neon',
                'mode' => 'diagonal',
                'lineHeight' => 9,
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['fontSize', 'theme', 'mode', 'lineHeight']);
    }
}

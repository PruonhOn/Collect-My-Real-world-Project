<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\V1\System\PingController;
use App\Support\ApiResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Contract tests for the public surface of API v1, plus the error handling that
 * every future endpoint inherits from bootstrap/app.php.
 */
class SystemEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_ping_returns_the_success_envelope(): void
    {
        $response = $this->getJson('/api/v1/ping');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'ok')
            ->assertJsonPath('data.api_version', 'v1')
            ->assertJsonStructure(['success', 'message', 'data']);
    }

    public function test_health_reports_a_healthy_database_and_cache(): void
    {
        $response = $this->getJson('/api/v1/ping/health');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.checks.database.ok', true)
            ->assertJsonPath('data.checks.cache.ok', true);
    }

    public function test_version_endpoint_does_not_leak_internal_details(): void
    {
        $response = $this->getJson('/api/v1/version');

        $response->assertOk()
            ->assertJsonPath('data.api_version', 'v1')
            ->assertJsonStructure(['success', 'message', 'data' => ['api_version', 'laravel', 'php']]);

        $this->assertArrayNotHasKey('debug', $response->json());
    }

    public function test_ping_is_not_behind_authentication(): void
    {
        // The liveness probe has to answer before anyone can log in, so it must
        // not carry auth:sanctum. It still gets the `api` group middleware.
        $middleware = Route::getRoutes()->getByAction(PingController::class)->middleware();

        $this->assertNotContains('auth:sanctum', $middleware);
    }

    public function test_unknown_api_route_returns_a_json_404(): void
    {
        $response = $this->getJson('/api/v1/does-not-exist');

        $response->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertHeader('Content-Type', 'application/json');
    }

    public function test_404_errors_key_is_an_empty_object(): void
    {
        $response = $this->getJson('/api/v1/does-not-exist');

        // The frontend does `Object.entries(errors)`, which throws on `null` and
        // behaves differently on `[]` vs `{}`.
        $this->assertStringContainsString('"errors":{}', $response->getContent());
    }

    public function test_unsupported_method_returns_405_without_leaking_the_route_list(): void
    {
        $response = $this->postJson('/api/v1/ping');

        $response->assertStatus(405)->assertJsonPath('success', false);

        // Symfony's default message enumerates every valid method for the URI.
        $this->assertStringNotContainsString('GET', $response->json('message'));
    }

    public function test_validation_failures_use_the_error_envelope(): void
    {
        Route::middleware('api')->post('/api/v1/__test/validation', function () {
            return ApiResponse::error('The given data was invalid.', 422, [
                'email' => ['The email field is required.'],
            ]);
        });

        $response = $this->postJson('/api/v1/__test/validation', []);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'The given data was invalid.')
            ->assertJsonPath('errors.email.0', 'The email field is required.');
    }
}

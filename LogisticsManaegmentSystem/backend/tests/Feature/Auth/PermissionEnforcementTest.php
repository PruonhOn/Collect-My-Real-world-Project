<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * The `permission` middleware contract.
 *
 * routes/api.php requires every route to carry the permission it needs, on the
 * understanding that the React router only mirrors those permissions for UI
 * purposes. These tests register a route the way a module will and check that
 * the server is the thing actually enforcing it.
 */
class PermissionEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Shaped exactly as a module route will be. `auth:sanctum` first.
        Route::middleware(['api', 'auth:sanctum', 'permission:invoice.update'])
            ->get('/api/v1/__test/invoices', fn () => response()->json(['success' => true]));
    }

    public function test_an_anonymous_caller_is_told_to_sign_in_not_that_the_route_exists(): void
    {
        $this->getJson('/api/v1/__test/invoices')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated. Please sign in again.');

        // The permission middleware answers 403 to an anonymous caller, so if it
        // ever ran before auth:sanctum a signed-out browser would learn which
        // endpoints exist.
    }

    public function test_a_user_holding_the_permission_is_allowed_through(): void
    {
        $this->seedPermissions();

        $user = User::factory()->withRole('finance-officer')->create();

        $this->withToken($user->createToken('spa')->plainTextToken)
            ->getJson('/api/v1/__test/invoices')
            ->assertOk();
    }

    public function test_a_user_without_the_permission_is_refused(): void
    {
        $this->seedPermissions();

        $user = User::factory()->withRole('viewer')->create();

        $this->withToken($user->createToken('spa')->plainTextToken)
            ->getJson('/api/v1/__test/invoices')
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_a_user_with_no_roles_at_all_is_refused(): void
    {
        $this->seedPermissions();

        $user = User::factory()->create();

        $this->withToken($user->createToken('spa')->plainTextToken)
            ->getJson('/api/v1/__test/invoices')
            ->assertForbidden();
    }

    public function test_a_permission_granted_directly_is_honoured(): void
    {
        $this->seedPermissions();

        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findByName('invoice.update'));

        $this->withToken($user->createToken('spa')->plainTextToken)
            ->getJson('/api/v1/__test/invoices')
            ->assertOk();
    }

    public function test_a_revoked_permission_stops_working_immediately(): void
    {
        $this->seedPermissions();

        $user = User::factory()->withRole('finance-officer')->create();
        $token = $user->createToken('spa')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/__test/invoices')->assertOk();

        $user->syncRoles([]);

        $this->asNewRequest()->withToken($token)
            ->getJson('/api/v1/__test/invoices')
            ->assertForbidden();
    }

    public function test_the_refusal_does_not_name_the_missing_permission(): void
    {
        $this->seedPermissions();

        $user = User::factory()->withRole('viewer')->create();

        $response = $this->withToken($user->createToken('spa')->plainTextToken)
            ->getJson('/api/v1/__test/invoices')
            ->assertForbidden();

        // config/permission.php keeps display_permission_in_exception off for
        // exactly this reason: the authorization model is not something an
        // unauthorized caller gets to read.
        $this->assertStringNotContainsString('invoice.update', $response->json('message'));
    }

    public function test_a_deactivated_user_is_refused_even_with_the_right_permission(): void
    {
        $this->seedPermissions();

        $user = User::factory()->deactivated()->withRole('finance-officer')->create();

        $this->withToken($user->createToken('spa')->plainTextToken)
            ->getJson('/api/v1/__test/invoices')
            ->assertForbidden()
            ->assertJsonPath(
                'message',
                'Your account has been deactivated. Please contact an administrator.',
            );
    }
}

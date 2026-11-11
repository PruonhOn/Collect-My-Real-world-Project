<?php

namespace Tests\Feature\MasterData;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Shared setup for every master-data module test.
 *
 * Seeds the permission graph, then provides an authenticated caller so each test
 * can state only the one thing it is actually about.
 */
abstract class MasterDataTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPermissions();
    }

    /**
     * A signed-in user holding the given role, with a usable bearer token.
     *
     * Returns the test case, not a response: `withToken` registers a default
     * header for every subsequent call, which is the shape these tests read
     * best at.
     */
    protected function actingAsRole(string $role): static
    {
        $user = User::factory()->withRole($role)->create();

        return $this->withToken($user->createToken('spa')->plainTextToken);
    }

    /**
     * The super-admin token, for tests about payload shape rather than access.
     */
    protected function actingAsAdmin(): static
    {
        return $this->actingAsRole('super-admin');
    }
}

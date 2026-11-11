<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Shared setup for the administration module tests.
 *
 * The user module is the first admin surface, and it is not master data, so it
 * gets its own base rather than borrowing MasterDataTestCase - the two are
 * about different contracts and a change to one should not quietly move the
 * other.
 */
abstract class AdminTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPermissions();
    }

    /**
     * A signed-in user holding the given role, with a usable bearer token.
     */
    protected function actingAsRole(string $role): static
    {
        $user = User::factory()->withRole($role)->create();

        return $this->withToken($user->createToken('spa')->plainTextToken);
    }

    /**
     * The break-glass token, for tests about payload shape and policy.
     */
    protected function actingAsAdmin(): static
    {
        return $this->actingAsRole('super-admin');
    }
}

<?php

namespace Tests;

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Create the permission inventory and role grants from config/permissions.php.
     *
     * A test that cares about authorization needs real permission rows - Spatie
     * resolves a role's grants through the pivot table, so a role created by
     * UserFactory::withRole() on its own grants nothing. Call this first, then
     * build the user.
     */
    protected function seedPermissions(): void
    {
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * Treat the next call as a genuinely separate HTTP request.
     *
     * Every request made from a test shares one application container, and the
     * resolved auth guard is a singleton inside it. A guard that authenticated
     * a user for the first call hands the same instance back for the second,
     * even after the underlying token has been deleted. Production gets a fresh
     * container per request, so a test asserting that something changed between
     * two calls - a revoked token, a deactivated account - has to clear that
     * state or it proves nothing.
     */
    protected function asNewRequest(): static
    {
        $this->app['auth']->forgetGuards();

        return $this;
    }
}

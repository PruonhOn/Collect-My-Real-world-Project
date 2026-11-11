<?php

namespace Tests\Feature\Auth;

use App\Support\PermissionCatalog;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The seeded permission graph, checked against config/permissions.php.
 *
 * Everything here runs the seeder through Model::withoutEvents(), which is the
 * condition `php artisan db:seed` and `migrate:fresh --seed` actually create -
 * DatabaseSeeder wraps itself in WithoutModelEvents.
 *
 * That matters because Spatie's PermissionRegistrar caches the whole
 * permission list in memory and normally refreshes itself from Permission's
 * created events. With events suppressed nothing refreshes it, so a seeder that
 * creates rows and then looks them up reads the pre-seed snapshot and reports
 * that permissions it created a moment ago do not exist. It is worth being
 * explicit about the wrapper, because a test that lets the events fire passes
 * either way and would not notice that regression coming back.
 */
class RolePermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    private function runSeeder(): void
    {
        Model::withoutEvents(fn () => (new RolePermissionSeeder)->run());
    }

    public function test_it_seeds_every_permission_in_the_catalog(): void
    {
        $this->runSeeder();

        $this->assertEqualsCanonicalizing(
            PermissionCatalog::names(),
            Permission::query()->pluck('name')->all(),
        );
    }

    public function test_every_permission_lands_on_the_configured_guard(): void
    {
        $this->runSeeder();

        $this->assertSame(
            [PermissionCatalog::guard()],
            Permission::query()->distinct()->pluck('guard_name')->all(),
        );
    }

    public function test_the_catalog_guard_does_not_follow_a_sanctum_request_guard(): void
    {
        $this->runSeeder();

        $this->app['auth']->shouldUse('sanctum');

        $this->assertSame('web', PermissionCatalog::guard());
        $this->assertTrue(
            Role::findByName('operations-clerk', PermissionCatalog::guard())->exists,
        );
    }

    public function test_it_grants_each_role_exactly_the_permissions_the_config_asks_for(): void
    {
        $this->runSeeder();

        foreach (array_keys(PermissionCatalog::roles()) as $name) {
            $expected = PermissionCatalog::role($name)['permissions'];
            $actual = Role::findByName($name, PermissionCatalog::guard())
                ->permissions()
                ->pluck('name')
                ->all();

            $this->assertEqualsCanonicalizing($expected, $actual, "role {$name}");
        }
    }

    public function test_a_role_can_be_created_only_once_across_repeated_runs(): void
    {
        $this->runSeeder();
        $this->runSeeder();

        $this->assertSame(count(PermissionCatalog::roles()), Role::query()->count());
        $this->assertSame(count(PermissionCatalog::names()), Permission::query()->count());
    }

    public function test_a_permission_dropped_from_a_role_in_the_config_is_revoked_from_the_pivot(): void
    {
        $this->runSeeder();

        $clerk = Role::findByName('operations-clerk', PermissionCatalog::guard());
        $this->assertTrue($clerk->permissions()->where('name', 'shipment.update')->exists());

        $this->app->make('config')->set('permissions.roles.operations-clerk', [
            'label' => 'Operations Clerk',
            'permissions' => ['dashboard.view'],
        ]);
        $this->runSeeder();

        $this->assertSame(
            ['dashboard.view'],
            $clerk->fresh()->permissions()->pluck('name')->all(),
        );
    }
}

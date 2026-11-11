<?php

namespace Database\Seeders;

use App\Support\PermissionCatalog;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates the permission inventory and the starting role set.
 *
 * Idempotent, so it is safe to re-run after adding a module: new permissions are
 * created, existing ones are left alone, and each role's grants are re-synced to
 * match config/permissions.php. A permission removed from the config is *not*
 * deleted here - dropping a permission that historical records reference would
 * be worse than leaving an orphan row, so removal stays a deliberate act.
 */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $this->createPermissions();
        $this->syncRoles();
    }

    private function createPermissions(): void
    {
        $guard = PermissionCatalog::guard();

        $permissions = collect(PermissionCatalog::names())
            ->mapWithKeys(fn (string $name): array => [$name => ['name' => $name, 'guard_name' => $guard]])
            ->sortKeys()
            ->all();

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission['name'], $permission['guard_name']);
        }

        // Required, and not merely tidy. Spatie's PermissionRegistrar holds the
        // whole permission list in memory and normally refreshes itself from
        // Permission's created/deleted model events. DatabaseSeeder runs under
        // WithoutModelEvents, so nothing refreshes it: without this flush the
        // first lookup in syncRoles() reads the pre-seed snapshot and reports
        // that permissions created a moment ago do not exist.
        $this->forgetCachedPermissions();
    }

    private function syncRoles(): void
    {
        $guard = PermissionCatalog::guard();

        foreach (array_keys(PermissionCatalog::roles()) as $name) {
            $definition = PermissionCatalog::role($name);

            $role = Role::findOrCreate($name, $guard);

            // syncPermissions rather than assign: a permission pulled out of the
            // role in the config has to come back off the user, which an
            // additive assign would never do.
            $role->syncPermissions($definition['permissions']);
        }

        // Same reason as in createPermissions(), one phase up: Role::findOrCreate
        // left the roles snapshot stale, and the admin seeder that runs next
        // resolves 'super-admin' through it.
        $this->forgetCachedPermissions();
    }

    private function forgetCachedPermissions(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

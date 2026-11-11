<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->seedPermissions();
        $this->seedRoles();
        $this->seedSettings();

        $this->call([
            PlanSeeder::class,
            CouponSeeder::class,
            DemoDataSeeder::class,
        ]);
    }

    /**
     * The canonical permission catalogue. Permissions are platform-wide and
     * applied to business roles inside a tenant context.
     */
    public const PERMISSIONS = [
        'products.view', 'products.create', 'products.update', 'products.delete',
        'customers.view', 'customers.manage',
        'orders.view', 'orders.create', 'orders.manage',
        'invoices.view', 'invoices.create', 'invoices.manage',
        'reports.view', 'reports.export',
        'team.view', 'team.manage',
        'settings.manage',
        'billing.view', 'billing.manage',
        'inventory.view', 'inventory.manage',
        'branches.view', 'branches.manage',
    ];

    public const ROLES = [
        'owner' => self::PERMISSIONS,
        'admin' => [
            'products.view', 'products.create', 'products.update', 'products.delete',
            'customers.view', 'customers.manage',
            'orders.view', 'orders.create', 'orders.manage',
            'invoices.view', 'invoices.create', 'invoices.manage',
            'reports.view', 'reports.export',
            'team.view', 'settings.manage',
            'inventory.view', 'inventory.manage',
            'branches.view', 'branches.manage',
        ],
        'staff' => [
            'products.view', 'products.create', 'products.update',
            'customers.view', 'customers.manage',
            'orders.view', 'orders.create', 'orders.manage',
            'invoices.view', 'invoices.create',
            'reports.view', 'inventory.view',
        ],
    ];

    protected function seedPermissions(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission);
        }
    }

    protected function seedRoles(): void
    {
        foreach (self::ROLES as $name => $permissions) {
            $role = SpatieRole::findOrCreate($name);
            $role->syncPermissions($permissions);
        }
    }

    protected function seedSettings(): void
    {
        Setting::updateOrCreate(
            ['key' => 'platform.name'],
            ['value' => config('app.name'), 'group' => 'platform']
        );

        Setting::updateOrCreate(
            ['key' => 'platform.support_email'],
            ['value' => 'support@mybusiness.test', 'group' => 'platform']
        );
    }
}

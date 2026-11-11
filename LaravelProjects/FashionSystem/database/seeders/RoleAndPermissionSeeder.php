<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('role_has_permissions')->truncate();
        DB::table('model_has_roles')->truncate();
        DB::table('model_has_permissions')->truncate();
        Permission::truncate();
        Role::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $permissions = [
            'view_products', 'create_products', 'edit_products', 'delete_products',
            'view_sales', 'create_sales', 'edit_sales', 'delete_sales',
            'view_purchases', 'create_purchases', 'edit_purchases', 'delete_purchases',
            'view_customers', 'create_customers', 'edit_customers', 'delete_customers',
            'view_reports', 'manage_users', 'manage_settings',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }

        $admin = Role::create(['name' => 'Admin', 'guard_name' => 'web']);
        $admin->givePermissionTo(Permission::all());

        $manager = Role::create(['name' => 'Manager', 'guard_name' => 'web']);
        $manager->givePermissionTo([
            'view_products', 'create_products', 'edit_products', 'delete_products',
            'view_sales', 'create_sales', 'edit_sales', 'delete_sales',
            'view_purchases', 'create_purchases', 'edit_purchases', 'delete_purchases',
            'view_customers', 'create_customers', 'edit_customers', 'delete_customers',
            'view_reports',
        ]);

        $cashier = Role::create(['name' => 'Cashier', 'guard_name' => 'web']);
        $cashier->givePermissionTo([
            'view_sales', 'create_sales',
            'view_products', 'view_customers', 'create_customers', 'edit_customers',
        ]);
    }
}

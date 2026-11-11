<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Dashboard
            'dashboard.view',

            // Products
            'products.create', 'products.view', 'products.edit', 'products.delete',
            'products.barcode',

            // Categories
            'categories.create', 'categories.view', 'categories.edit', 'categories.delete',

            // Brands
            'brands.create', 'brands.view', 'brands.edit', 'brands.delete',

            // Units
            'units.create', 'units.view', 'units.edit', 'units.delete',

            // Suppliers
            'suppliers.create', 'suppliers.view', 'suppliers.edit', 'suppliers.delete',

            // Customers
            'customers.create', 'customers.view', 'customers.edit', 'customers.delete',
            'customers.loyalty',

            // Employees
            'employees.create', 'employees.view', 'employees.edit', 'employees.delete',

            // Inventory
            'inventory.view', 'inventory.stock-in', 'inventory.stock-out', 'inventory.adjust',

            // Purchases
            'purchases.create', 'purchases.view', 'purchases.edit', 'purchases.delete',

            // POS
            'pos.operate', 'pos.receipt',

            // Sales
            'sales.create', 'sales.view', 'sales.edit', 'sales.delete',
            'sales.refund',

            // Financial
            'financial.view', 'expenses.manage', 'incomes.manage',

            // Reports
            'reports.view', 'reports.sales', 'reports.inventory', 'reports.financial',

            // Settings
            'settings.view', 'settings.edit',

            // Roles
            'roles.create', 'roles.view', 'roles.edit', 'roles.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }

        // Super Admin
        $superAdmin = Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
        $superAdmin->givePermissionTo(Permission::all());

        // Manager
        $manager = Role::create(['name' => 'Manager', 'guard_name' => 'web']);
        $manager->givePermissionTo([
            'dashboard.view',
            'products.view', 'products.edit',
            'categories.view', 'categories.edit',
            'brands.view', 'brands.edit',
            'units.view',
            'suppliers.view', 'suppliers.edit',
            'customers.view', 'customers.edit',
            'employees.view', 'employees.edit',
            'inventory.view', 'inventory.stock-in', 'inventory.stock-out', 'inventory.adjust',
            'purchases.view', 'purchases.edit',
            'sales.view',
            'financial.view',
            'reports.view', 'reports.sales', 'reports.inventory', 'reports.financial',
            'settings.view', 'settings.edit',
        ]);

        // Cashier
        $cashier = Role::create(['name' => 'Cashier', 'guard_name' => 'web']);
        $cashier->givePermissionTo([
            'pos.operate', 'pos.receipt',
            'sales.create', 'sales.view',
            'customers.view', 'customers.create',
            'products.view',
            'dashboard.view',
        ]);

        // Inventory Staff
        $inventoryStaff = Role::create(['name' => 'Inventory Staff', 'guard_name' => 'web']);
        $inventoryStaff->givePermissionTo([
            'inventory.view', 'inventory.stock-in', 'inventory.stock-out', 'inventory.adjust',
            'products.view',
            'categories.view',
            'dashboard.view',
        ]);

        // Accountant
        $accountant = Role::create(['name' => 'Accountant', 'guard_name' => 'web']);
        $accountant->givePermissionTo([
            'financial.view', 'expenses.manage', 'incomes.manage',
            'reports.view', 'reports.sales', 'reports.financial',
            'dashboard.view',
            'sales.view',
            'purchases.view',
        ]);
    }
}

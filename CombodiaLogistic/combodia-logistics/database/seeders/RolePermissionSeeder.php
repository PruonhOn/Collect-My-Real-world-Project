<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        if (User::count() > 0) {
            return;
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
            'permissions.view', 'permissions.assign',
            'branches.view', 'branches.create', 'branches.edit', 'branches.delete',
            'warehouses.view', 'warehouses.create', 'warehouses.edit', 'warehouses.delete',
            'warehouses.zones', 'warehouses.shelves',
            'customers.view', 'customers.create', 'customers.edit', 'customers.delete',
            'customers.addresses',
            'suppliers.view', 'suppliers.create', 'suppliers.edit', 'suppliers.delete',
            'vehicles.view', 'vehicles.create', 'vehicles.edit', 'vehicles.delete',
            'vehicles.maintenance', 'vehicles.types',
            'drivers.view', 'drivers.create', 'drivers.edit', 'drivers.delete',
            'drivers.documents', 'drivers.schedules',
            'shipments.view', 'shipments.create', 'shipments.edit', 'shipments.delete',
            'shipments.approve', 'shipments.cancel', 'shipments.assign',
            'shipments.status', 'shipments.tracking',
            'packages.view', 'packages.create', 'packages.edit',
            'packages.barcode', 'packages.qrcode',
            'pickups.view', 'pickups.create', 'pickups.edit',
            'pickups.assign', 'pickups.confirm',
            'dispatches.view', 'dispatches.create', 'dispatches.edit',
            'dispatches.assign_driver', 'dispatches.load', 'dispatches.complete',
            'routes.view', 'routes.create', 'routes.edit', 'routes.delete',
            'tracking.view', 'tracking.update', 'tracking.history',
            'inventory.view', 'inventory.create', 'inventory.edit',
            'inventory.stock_movement', 'inventory.adjustment',
            'invoices.view', 'invoices.create', 'invoices.edit',
            'invoices.cancel', 'invoices.refund',
            'payments.view', 'payments.create', 'payments.edit',
            'payments.refund', 'payments.methods',
            'expenses.view', 'expenses.create', 'expenses.edit',
            'expenses.approve', 'expenses.categories',
            'income.view', 'income.create', 'income.edit',
            'income.categories',
            'returns.view', 'returns.create', 'returns.edit',
            'returns.approve', 'returns.complete',
            'reports.view', 'reports.shipment', 'reports.financial',
            'reports.inventory', 'reports.driver', 'reports.warehouse',
            'reports.customer', 'reports.vehicle', 'reports.profit_loss',
            'reports.export_pdf', 'reports.export_excel',
            'dashboard.view', 'dashboard.analytics',
            'settings.view', 'settings.edit',
            'activity_logs.view',
            'backup.create', 'backup.restore', 'backup.download',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }

        $superAdmin = Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
        $superAdmin->givePermissionTo(Permission::all());

        $sysAdmin = Role::create(['name' => 'System Administrator', 'guard_name' => 'web']);
        $sysAdmin->givePermissionTo(Permission::all());
        $sysAdmin->revokePermissionTo(['backup.restore', 'permissions.assign']);

        $warehouseManager = Role::create(['name' => 'Warehouse Manager', 'guard_name' => 'web']);
        $warehouseManager->givePermissionTo([
            'dashboard.view', 'dashboard.analytics',
            'warehouses.view', 'warehouses.create', 'warehouses.edit',
            'warehouses.zones', 'warehouses.shelves',
            'packages.view', 'packages.create', 'packages.edit',
            'packages.barcode', 'packages.qrcode',
            'inventory.view', 'inventory.create', 'inventory.edit',
            'inventory.stock_movement', 'inventory.adjustment',
            'reports.view', 'reports.inventory', 'reports.warehouse', 'reports.export_pdf', 'reports.export_excel',
            'settings.view',
            'activity_logs.view',
        ]);

        $warehouseStaff = Role::create(['name' => 'Warehouse Staff', 'guard_name' => 'web']);
        $warehouseStaff->givePermissionTo([
            'dashboard.view',
            'warehouses.view',
            'packages.view', 'packages.create', 'packages.edit',
            'packages.barcode', 'packages.qrcode',
            'inventory.view', 'inventory.create', 'inventory.edit',
            'inventory.stock_movement',
            'shipments.view',
            'settings.view',
        ]);

        $logisticsManager = Role::create(['name' => 'Logistics Manager', 'guard_name' => 'web']);
        $logisticsManager->givePermissionTo([
            'dashboard.view', 'dashboard.analytics',
            'shipments.view', 'shipments.create', 'shipments.edit', 'shipments.approve', 'shipments.cancel', 'shipments.assign',
            'shipments.status', 'shipments.tracking',
            'packages.view', 'packages.create', 'packages.edit',
            'pickups.view', 'pickups.create', 'pickups.edit', 'pickups.assign', 'pickups.confirm',
            'dispatches.view', 'dispatches.create', 'dispatches.edit', 'dispatches.assign_driver', 'dispatches.load', 'dispatches.complete',
            'routes.view', 'routes.create', 'routes.edit',
            'tracking.view', 'tracking.update', 'tracking.history',
            'drivers.view', 'drivers.documents', 'drivers.schedules',
            'vehicles.view', 'vehicles.maintenance',
            'reports.view', 'reports.shipment', 'reports.driver', 'reports.vehicle',
            'reports.export_pdf', 'reports.export_excel',
            'settings.view',
            'activity_logs.view',
        ]);

        $dispatcher = Role::create(['name' => 'Dispatcher', 'guard_name' => 'web']);
        $dispatcher->givePermissionTo([
            'dashboard.view',
            'shipments.view', 'shipments.assign', 'shipments.status', 'shipments.tracking',
            'pickups.view', 'pickups.assign', 'pickups.confirm',
            'dispatches.view', 'dispatches.create', 'dispatches.edit', 'dispatches.assign_driver', 'dispatches.load', 'dispatches.complete',
            'routes.view',
            'tracking.view', 'tracking.update', 'tracking.history',
            'drivers.view', 'drivers.schedules',
            'vehicles.view',
            'settings.view',
        ]);

        $driver = Role::create(['name' => 'Driver', 'guard_name' => 'web']);
        $driver->givePermissionTo([
            'dashboard.view',
            'shipments.view', 'shipments.tracking',
            'dispatches.view',
            'tracking.view', 'tracking.update', 'tracking.history',
            'pickups.view',
            'settings.view',
        ]);

        $customer = Role::create(['name' => 'Customer', 'guard_name' => 'web']);
        $customer->givePermissionTo([
            'dashboard.view',
            'shipments.view', 'shipments.create', 'shipments.tracking',
            'invoices.view',
            'payments.view', 'payments.create',
            'tracking.view', 'tracking.history',
            'settings.view',
        ]);

        $finance = Role::create(['name' => 'Finance', 'guard_name' => 'web']);
        $finance->givePermissionTo([
            'dashboard.view', 'dashboard.analytics',
            'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.cancel', 'invoices.refund',
            'payments.view', 'payments.create', 'payments.edit', 'payments.refund', 'payments.methods',
            'expenses.view', 'expenses.create', 'expenses.edit', 'expenses.approve', 'expenses.categories',
            'income.view', 'income.create', 'income.edit', 'income.categories',
            'reports.view', 'reports.financial', 'reports.profit_loss',
            'reports.export_pdf', 'reports.export_excel',
            'settings.view',
            'activity_logs.view',
        ]);

        $accountant = Role::create(['name' => 'Accountant', 'guard_name' => 'web']);
        $accountant->givePermissionTo([
            'dashboard.view',
            'invoices.view', 'invoices.edit',
            'payments.view', 'payments.edit',
            'expenses.view', 'expenses.create', 'expenses.edit', 'expenses.categories',
            'income.view', 'income.create', 'income.edit', 'income.categories',
            'reports.view', 'reports.financial', 'reports.profit_loss',
            'reports.export_pdf', 'reports.export_excel',
            'settings.view',
        ]);

        $sales = Role::create(['name' => 'Sales', 'guard_name' => 'web']);
        $sales->givePermissionTo([
            'dashboard.view',
            'customers.view', 'customers.create', 'customers.edit',
            'shipments.view', 'shipments.create',
            'invoices.view',
            'reports.view', 'reports.customer',
            'settings.view',
        ]);

        $support = Role::create(['name' => 'Customer Support', 'guard_name' => 'web']);
        $support->givePermissionTo([
            'dashboard.view',
            'customers.view', 'customers.addresses',
            'shipments.view', 'shipments.tracking',
            'tracking.view', 'tracking.history',
            'returns.view', 'returns.create', 'returns.edit',
            'settings.view',
        ]);

        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@combodialogistics.com',
            'phone' => '+85512345678',
            'username' => 'admin',
            'password' => Hash::make('password'),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('Super Admin');

        $users = [
            ['name' => 'System Admin', 'email' => 'sysadmin@combodialogistics.com', 'username' => 'sysadmin', 'role' => 'System Administrator'],
            ['name' => 'Warehouse Manager', 'email' => 'warehouse@combodialogistics.com', 'username' => 'warehouse', 'role' => 'Warehouse Manager'],
            ['name' => 'Logistics Manager', 'email' => 'logistics@combodialogistics.com', 'username' => 'logistics', 'role' => 'Logistics Manager'],
            ['name' => 'Dispatcher', 'email' => 'dispatcher@combodialogistics.com', 'username' => 'dispatcher', 'role' => 'Dispatcher'],
            ['name' => 'Finance', 'email' => 'finance@combodialogistics.com', 'username' => 'finance', 'role' => 'Finance'],
            ['name' => 'Sales', 'email' => 'sales@combodialogistics.com', 'username' => 'sales', 'role' => 'Sales'],
        ];

        foreach ($users as $userData) {
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'phone' => '+855' . rand(10000000, 99999999),
                'username' => $userData['username'],
                'password' => Hash::make('password'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
            $user->assignRole($userData['role']);
        }
    }
}

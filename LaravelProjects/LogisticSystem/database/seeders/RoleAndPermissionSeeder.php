<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            [
                'name' => 'View Dashboard',
                'slug' => 'view-dashboard',
                'group' => 'Dashboard',
            ],
            [
                'name' => 'View Branches',
                'slug' => 'view-branches',
                'group' => 'Branches',
            ],
            [
                'name' => 'Create Branches',
                'slug' => 'create-branches',
                'group' => 'Branches',
            ],
            [
                'name' => 'Edit Branches',
                'slug' => 'edit-branches',
                'group' => 'Branches',
            ],
            [
                'name' => 'Delete Branches',
                'slug' => 'delete-branches',
                'group' => 'Branches',
            ],
            [
                'name' => 'View Customers',
                'slug' => 'view-customers',
                'group' => 'Customers',
            ],
            [
                'name' => 'Create Customers',
                'slug' => 'create-customers',
                'group' => 'Customers',
            ],
            [
                'name' => 'Edit Customers',
                'slug' => 'edit-customers',
                'group' => 'Customers',
            ],
            [
                'name' => 'Delete Customers',
                'slug' => 'delete-customers',
                'group' => 'Customers',
            ],
            [
                'name' => 'View Shipments',
                'slug' => 'view-shipments',
                'group' => 'Shipments',
            ],
            [
                'name' => 'Create Shipments',
                'slug' => 'create-shipments',
                'group' => 'Shipments',
            ],
            [
                'name' => 'Edit Shipments',
                'slug' => 'edit-shipments',
                'group' => 'Shipments',
            ],
            [
                'name' => 'Delete Shipments',
                'slug' => 'delete-shipments',
                'group' => 'Shipments',
            ],
            [
                'name' => 'Update Shipment Status',
                'slug' => 'update-shipment-status',
                'group' => 'Shipments',
            ],
            [
                'name' => 'Assign Shipment',
                'slug' => 'assign-shipment',
                'group' => 'Shipments',
            ],
            [
                'name' => 'View Warehouses',
                'slug' => 'view-warehouses',
                'group' => 'Warehouses',
            ],
            [
                'name' => 'Create Warehouses',
                'slug' => 'create-warehouses',
                'group' => 'Warehouses',
            ],
            [
                'name' => 'Edit Warehouses',
                'slug' => 'edit-warehouses',
                'group' => 'Warehouses',
            ],
            [
                'name' => 'Delete Warehouses',
                'slug' => 'delete-warehouses',
                'group' => 'Warehouses',
            ],
            [
                'name' => 'Manage Inventory',
                'slug' => 'manage-inventory',
                'group' => 'Warehouses',
            ],
            [
                'name' => 'Adjust Stock',
                'slug' => 'adjust-stock',
                'group' => 'Warehouses',
            ],
            [
                'name' => 'View Vehicles',
                'slug' => 'view-vehicles',
                'group' => 'Vehicles',
            ],
            [
                'name' => 'Create Vehicles',
                'slug' => 'create-vehicles',
                'group' => 'Vehicles',
            ],
            [
                'name' => 'Edit Vehicles',
                'slug' => 'edit-vehicles',
                'group' => 'Vehicles',
            ],
            [
                'name' => 'Delete Vehicles',
                'slug' => 'delete-vehicles',
                'group' => 'Vehicles',
            ],
            [
                'name' => 'Manage Maintenance',
                'slug' => 'manage-maintenance',
                'group' => 'Vehicles',
            ],
            [
                'name' => 'View Drivers',
                'slug' => 'view-drivers',
                'group' => 'Drivers',
            ],
            [
                'name' => 'Create Drivers',
                'slug' => 'create-drivers',
                'group' => 'Drivers',
            ],
            [
                'name' => 'Edit Drivers',
                'slug' => 'edit-drivers',
                'group' => 'Drivers',
            ],
            [
                'name' => 'Delete Drivers',
                'slug' => 'delete-drivers',
                'group' => 'Drivers',
            ],
            [
                'name' => 'View Routes',
                'slug' => 'view-routes',
                'group' => 'Routes & Deliveries',
            ],
            [
                'name' => 'Create Routes',
                'slug' => 'create-routes',
                'group' => 'Routes & Deliveries',
            ],
            [
                'name' => 'Edit Routes',
                'slug' => 'edit-routes',
                'group' => 'Routes & Deliveries',
            ],
            [
                'name' => 'Delete Routes',
                'slug' => 'delete-routes',
                'group' => 'Routes & Deliveries',
            ],
            [
                'name' => 'View Deliveries',
                'slug' => 'view-deliveries',
                'group' => 'Routes & Deliveries',
            ],
            [
                'name' => 'Create Deliveries',
                'slug' => 'create-deliveries',
                'group' => 'Routes & Deliveries',
            ],
            [
                'name' => 'Edit Deliveries',
                'slug' => 'edit-deliveries',
                'group' => 'Routes & Deliveries',
            ],
            [
                'name' => 'Delete Deliveries',
                'slug' => 'delete-deliveries',
                'group' => 'Routes & Deliveries',
            ],
            [
                'name' => 'Assign Deliveries',
                'slug' => 'assign-deliveries',
                'group' => 'Routes & Deliveries',
            ],
            [
                'name' => 'Update Delivery Status',
                'slug' => 'update-delivery-status',
                'group' => 'Routes & Deliveries',
            ],
            [
                'name' => 'View Invoices',
                'slug' => 'view-invoices',
                'group' => 'Invoices',
            ],
            [
                'name' => 'Create Invoices',
                'slug' => 'create-invoices',
                'group' => 'Invoices',
            ],
            [
                'name' => 'Edit Invoices',
                'slug' => 'edit-invoices',
                'group' => 'Invoices',
            ],
            [
                'name' => 'Delete Invoices',
                'slug' => 'delete-invoices',
                'group' => 'Invoices',
            ],
            [
                'name' => 'Record Payments',
                'slug' => 'record-payments',
                'group' => 'Invoices',
            ],
            [
                'name' => 'Download Invoices',
                'slug' => 'download-invoices',
                'group' => 'Invoices',
            ],
            [
                'name' => 'View Finance',
                'slug' => 'view-finance',
                'group' => 'Finance',
            ],
            [
                'name' => 'Create Transactions',
                'slug' => 'create-transactions',
                'group' => 'Finance',
            ],
            [
                'name' => 'View Expenses',
                'slug' => 'view-expenses',
                'group' => 'Finance',
            ],
            [
                'name' => 'Create Expenses',
                'slug' => 'create-expenses',
                'group' => 'Finance',
            ],
            [
                'name' => 'Approve Expenses',
                'slug' => 'approve-expenses',
                'group' => 'Finance',
            ],
            [
                'name' => 'View Reports',
                'slug' => 'view-reports',
                'group' => 'Reports',
            ],
            [
                'name' => 'Export Reports',
                'slug' => 'export-reports',
                'group' => 'Reports',
            ],
            [
                'name' => 'View Settings',
                'slug' => 'view-settings',
                'group' => 'Settings',
            ],
            [
                'name' => 'Edit Settings',
                'slug' => 'edit-settings',
                'group' => 'Settings',
            ],
            [
                'name' => 'View Users',
                'slug' => 'view-users',
                'group' => 'Users & Roles',
            ],
            [
                'name' => 'Create Users',
                'slug' => 'create-users',
                'group' => 'Users & Roles',
            ],
            [
                'name' => 'Edit Users',
                'slug' => 'edit-users',
                'group' => 'Users & Roles',
            ],
            [
                'name' => 'Delete Users',
                'slug' => 'delete-users',
                'group' => 'Users & Roles',
            ],
            [
                'name' => 'View Roles',
                'slug' => 'view-roles',
                'group' => 'Users & Roles',
            ],
            [
                'name' => 'Create Roles',
                'slug' => 'create-roles',
                'group' => 'Users & Roles',
            ],
            [
                'name' => 'Edit Roles',
                'slug' => 'edit-roles',
                'group' => 'Users & Roles',
            ],
            [
                'name' => 'Delete Roles',
                'slug' => 'delete-roles',
                'group' => 'Users & Roles',
            ],
        ];

        $roles = [
            'super_admin' => [
                'name' => 'Super Admin',
                'description' => 'Full system access',
            ],
            'branch_manager' => [
                'name' => 'Branch Manager',
                'description' => 'Branch management',
            ],
            'warehouse_staff' => [
                'name' => 'Warehouse Staff',
                'description' => 'Warehouse operations',
            ],
            'dispatcher' => [
                'name' => 'Dispatcher',
                'description' => 'Dispatch operations',
            ],
            'driver' => [
                'name' => 'Driver',
                'description' => 'Driver role',
            ],
        ];

        $rolePermissions = [
            'super_admin' => array_column($permissions, 'slug'),
            'branch_manager' => [
                'view-dashboard',
                'view-branches', 'create-branches', 'edit-branches', 'delete-branches',
                'view-customers', 'create-customers', 'edit-customers', 'delete-customers',
                'view-shipments', 'create-shipments', 'edit-shipments', 'delete-shipments',
                'update-shipment-status', 'assign-shipment',
                'view-warehouses', 'create-warehouses', 'edit-warehouses', 'delete-warehouses',
                'manage-inventory', 'adjust-stock',
                'view-vehicles', 'create-vehicles', 'edit-vehicles', 'delete-vehicles',
                'manage-maintenance',
                'view-drivers', 'create-drivers', 'edit-drivers', 'delete-drivers',
                'view-routes', 'create-routes', 'edit-routes', 'delete-routes',
                'view-deliveries', 'create-deliveries', 'edit-deliveries', 'delete-deliveries',
                'assign-deliveries', 'update-delivery-status',
                'view-invoices', 'create-invoices', 'edit-invoices', 'delete-invoices',
                'record-payments', 'download-invoices',
                'view-finance', 'create-transactions', 'view-expenses', 'create-expenses',
                'approve-expenses',
                'view-reports', 'export-reports',
            ],
            'warehouse_staff' => [
                'view-dashboard',
                'view-shipments', 'update-shipment-status',
                'view-warehouses', 'manage-inventory', 'adjust-stock',
            ],
            'dispatcher' => [
                'view-dashboard',
                'view-shipments', 'assign-shipment',
                'view-routes', 'create-routes', 'edit-routes',
                'view-deliveries', 'create-deliveries', 'edit-deliveries',
                'assign-deliveries', 'update-delivery-status',
            ],
            'driver' => [
                'view-dashboard',
                'view-shipments',
                'view-deliveries',
                'update-delivery-status',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }

        foreach ($roles as $slug => $data) {
            $role = Role::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                ]
            );

            $permissionSlugs = $rolePermissions[$slug];
            $permissionIds = Permission::whereIn('slug', $permissionSlugs)->pluck('id')->toArray();

            $role->permissions()->sync($permissionIds);
        }
    }
}

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Permission groups
    |--------------------------------------------------------------------------
    |
    | The complete inventory of what a user is allowed to do, grouped exactly
    | as the administration screens present them. `name => label` per group,
    | so the Roles & Permissions screen can render a checkbox matrix straight
    | from this file without a second lookup table.
    |
    | Naming is `<resource>.<action>`. A permission is only ever created by the
    | seeder from this list, so a typo here cannot silently become a permission
    | that no role holds and no route requires.
    |
    | Every CRUD resource follows the same four actions. `view` is separate from
    | the mutating actions because read access and write access are granted to
    | different people far more often than not - a finance auditor needs to see
    | an invoice but must not be able to void one.
    |
    */

    'groups' => [

        'Overview' => [
            'dashboard.view' => 'View the dashboard',
            'premium.view' => 'View premium service screens',
        ],

        'Operations' => [
            'booking.view' => 'View bookings',
            'booking.create' => 'Create bookings',
            'booking.update' => 'Edit bookings',
            'booking.delete' => 'Delete bookings',

            'shipment.view' => 'View shipments',
            'shipment.create' => 'Create shipments',
            'shipment.update' => 'Edit shipments',
            'shipment.delete' => 'Delete shipments',

            'tracking.view' => 'View shipment tracking',
            'tracking.update' => 'Update shipment milestones',

            'container.view' => 'View containers',
            'container.create' => 'Create containers',
            'container.update' => 'Edit containers',
            'container.delete' => 'Delete containers',

            'customs.view' => 'View customs clearances',
            'customs.create' => 'Create customs clearances',
            'customs.update' => 'Edit customs clearances',
            'customs.delete' => 'Delete customs clearances',

            'delivery.view' => 'View deliveries',
            'delivery.create' => 'Create deliveries',
            'delivery.update' => 'Edit deliveries',
            'delivery.delete' => 'Delete deliveries',
        ],

        'Master Data' => [
            'customer.view' => 'View customers',
            'customer.create' => 'Create customers',
            'customer.update' => 'Edit customers',
            'customer.delete' => 'Delete customers',

            'shipper.view' => 'View shippers',
            'shipper.create' => 'Create shippers',
            'shipper.update' => 'Edit shippers',
            'shipper.delete' => 'Delete shippers',

            'consignee.view' => 'View consignees',
            'consignee.create' => 'Create consignees',
            'consignee.update' => 'Edit consignees',
            'consignee.delete' => 'Delete consignees',

            'carrier.view' => 'View carriers',
            'carrier.create' => 'Create carriers',
            'carrier.update' => 'Edit carriers',
            'carrier.delete' => 'Delete carriers',

            'vessel.view' => 'View vessels',
            'vessel.create' => 'Create vessels',
            'vessel.update' => 'Edit vessels',
            'vessel.delete' => 'Delete vessels',

            'port.view' => 'View ports',
            'port.create' => 'Create ports',
            'port.update' => 'Edit ports',
            'port.delete' => 'Delete ports',

            'warehouse.view' => 'View warehouses',
            'warehouse.create' => 'Create warehouses',
            'warehouse.update' => 'Edit warehouses',
            'warehouse.delete' => 'Delete warehouses',

            'factory.view' => 'View factories',
            'factory.create' => 'Create factories',
            'factory.update' => 'Edit factories',
            'factory.delete' => 'Delete factories',

            'truck.view' => 'View trucks',
            'truck.create' => 'Create trucks',
            'truck.update' => 'Edit trucks',
            'truck.delete' => 'Delete trucks',

            'driver.view' => 'View drivers',
            'driver.create' => 'Create drivers',
            'driver.update' => 'Edit drivers',
            'driver.delete' => 'Delete drivers',
        ],

        'Finance' => [
            'charge.view' => 'View charges',
            'charge.create' => 'Create charges',
            'charge.update' => 'Edit charges',
            'charge.delete' => 'Delete charges',

            'invoice.view' => 'View invoices',
            'invoice.create' => 'Create invoices',
            'invoice.update' => 'Edit invoices',
            'invoice.delete' => 'Delete invoices',

            'payment.view' => 'View payments',
            'payment.create' => 'Record payments',
            'payment.update' => 'Edit payments',
            'payment.delete' => 'Delete payments',

            'expense.view' => 'View expenses',
            'expense.create' => 'Create expenses',
            'expense.update' => 'Edit expenses',
            'expense.delete' => 'Delete expenses',
        ],

        'Documents' => [
            'document.view' => 'View documents',
            'document.create' => 'Upload documents',
            'document.update' => 'Edit document details',
            'document.delete' => 'Delete documents',
            // Downloading is separate from viewing because a bill of lading is
            // commercially sensitive: a user can be shown that a document exists
            // without being able to open it.
            'document.download' => 'Download documents',
        ],

        'Reports' => [
            'report.view' => 'View reports',
            'report.export' => 'Export reports',
        ],

        'Administration' => [
            'user.view' => 'View users',
            'user.create' => 'Create users',
            'user.update' => 'Edit users',
            'user.delete' => 'Delete users',

            'role.view' => 'View roles and permissions',
            'role.create' => 'Create roles',
            'role.update' => 'Edit roles',
            'role.delete' => 'Delete roles',

            'notification.view' => 'View notifications',
            'notification.update' => 'Manage notifications',

            'activity_log.view' => 'View the activity log',

            'setting.view' => 'View system settings',
            'setting.update' => 'Change system settings',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Roles
    |--------------------------------------------------------------------------
    |
    | The starting role set. An administrator composes anything else from the
    | screen above; these are the ones a freight forwarder needs on day one.
    |
    | `permissions` is a list of concrete names. The token '*' is expanded to
    | the full inventory by App\Support\PermissionCatalog, so a role granted '*'
    | keeps automatically picking up permissions added by later phases instead
    | of needing a reseed to notice them.
    |
    | `super-admin` and `admin` are granted the same permissions on purpose.
    | They are separated by policy rather than by capability: super-admin is
    | the break-glass account that cannot be deactivated or have its roles
    | edited, so there is always a way back in.
    |
    | An entry may be a concrete name ('invoice.update'), a resource wildcard
    | ('invoice.*'), an action wildcard ('*.view'), or '*' for everything.
    | App\Support\PermissionCatalog resolves them against the inventory above.
    |
    */

    'roles' => [

        'super-admin' => [
            'label' => 'Super Administrator',
            'description' => 'Unrestricted access. Cannot be deactivated or demoted.',
            'permissions' => ['*'],
        ],

        'admin' => [
            'label' => 'Administrator',
            'description' => 'Full access to every module including user management.',
            'permissions' => ['*'],
        ],

        'operations-manager' => [
            'label' => 'Operations Manager',
            'description' => 'Runs the operation end to end, including rates and invoices.',
            'permissions' => [
                'dashboard.view',

                'booking.*',
                'shipment.*',
                'tracking.*',
                'container.*',
                'customs.*',
                'delivery.*',

                'customer.*',
                'shipper.*',
                'consignee.*',
                'carrier.*',
                'vessel.*',
                'port.*',
                'warehouse.*',
                'factory.*',
                'truck.*',
                'driver.*',

                'charge.*',
                'invoice.*',
                'payment.view',
                'expense.view',

                'document.*',
                'report.*',

                'notification.view',
            ],
        ],

        'operations-clerk' => [
            'label' => 'Operations Clerk',
            'description' => 'Day-to-day bookings, shipments and documentation. No deletions, no money.',
            'permissions' => [
                'dashboard.view',

                'booking.view', 'booking.create', 'booking.update',
                'shipment.view', 'shipment.create', 'shipment.update',
                'tracking.view', 'tracking.update',
                'container.view', 'container.create', 'container.update',
                'customs.view', 'customs.create', 'customs.update',
                'delivery.view', 'delivery.create', 'delivery.update',

                'customer.view', 'shipper.view', 'consignee.view',
                'carrier.view', 'vessel.view', 'port.view', 'port.view',
                'warehouse.view', 'factory.view', 'truck.view', 'driver.view',

                'document.view', 'document.create', 'document.download',

                'report.view',
            ],
        ],

        'finance-officer' => [
            'label' => 'Finance Officer',
            'description' => 'Owns invoicing, payments and expenses. Read-only elsewhere.',
            'permissions' => [
                'dashboard.view',

                'charge.*',
                'invoice.*',
                'payment.*',
                'expense.*',

                'customer.view',
                'shipper.view',
                'consignee.view',
                'carrier.view',

                'shipment.view',
                'booking.view',

                'document.view', 'document.download',
                'report.*',
            ],
        ],

        'viewer' => [
            'label' => 'Viewer',
            'description' => 'Read-only across the whole system.',
            'permissions' => [
                'dashboard.view',
                '*.view',
            ],
        ],

        'premium' => [
            'label' => 'Premium Account Manager',
            'description' => 'Handles VIP customers and priority shipments. Full operational control, no user or system management.',
            'permissions' => [
                'dashboard.view',
                'premium.view',

                'booking.*',
                'shipment.*',
                'tracking.*',
                'container.*',
                'customs.*',
                'delivery.*',

                'customer.view', 'customer.create', 'customer.update',
                'shipper.view', 'shipper.create', 'shipper.update',
                'consignee.view', 'consignee.create', 'consignee.update',
                'carrier.view', 'carrier.create', 'carrier.update',
                'vessel.view', 'vessel.create', 'vessel.update',
                'port.view', 'port.create', 'port.update',
                'warehouse.view', 'warehouse.create', 'warehouse.update',
                'factory.view', 'factory.create', 'factory.update',
                'truck.view', 'truck.create', 'truck.update',
                'driver.view', 'driver.create', 'driver.update',

                'charge.view',
                'invoice.view',
                'payment.view',
                'expense.view',

                'document.*',
                'report.*',

                'notification.view',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Wildcard matching
    |--------------------------------------------------------------------------
    |
    | Spatie's own wildcard permission syntax is disabled in config/permission
    | php. This file does the expansion at seed time instead, so a role holds a
    | concrete set of permission rows. That keeps `can()` cheap and makes the
    | effective grant set readable straight out of the database.
    |
    */

];

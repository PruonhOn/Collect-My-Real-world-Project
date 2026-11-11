<?php
// ============================================================
// database/seeders/DatabaseSeeder.php
// ============================================================
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesPermissionsSeeder::class,
            UserSeeder::class,
            AssetSeeder::class,
            WorkOrderSeeder::class,
        ]);
    }
}


// ============================================================
// database/seeders/RolesPermissionsSeeder.php
// ============================================================
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\{Role, Permission};

class RolesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view work-orders', 'create work-orders', 'edit work-orders',
            'delete work-orders', 'assign work-orders', 'complete work-orders',
            'view assets', 'create assets', 'edit assets', 'delete assets',
            'generate reports', 'export reports',
            'manage users', 'view users',
            'view alerts', 'dismiss alerts',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());

        $tech = Role::firstOrCreate(['name' => 'technician', 'guard_name' => 'web']);
        $tech->syncPermissions([
            'view work-orders', 'edit work-orders', 'complete work-orders',
            'view assets', 'view alerts', 'dismiss alerts',
        ]);

        $viewer = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);
        $viewer->syncPermissions(['view work-orders', 'view assets', 'view alerts']);
    }
}


// ============================================================
// database/seeders/UserSeeder.php
// ============================================================
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        User::firstOrCreate(['email' => 'admin@mms.local'], [
            'name'     => 'Admin User',
            'password' => Hash::make('password'),
        ])->assignRole('admin');

        // Technicians
        $techs = [
            ['name' => 'Jordan Mills',  'email' => 'jordan@mms.local'],
            ['name' => 'Sam Torres',    'email' => 'sam@mms.local'],
            ['name' => 'Priya Nair',    'email' => 'priya@mms.local'],
            ['name' => 'Chris Lee',     'email' => 'chris@mms.local'],
        ];
        foreach ($techs as $t) {
            User::firstOrCreate(['email' => $t['email']], [
                'name' => $t['name'], 'password' => Hash::make('password'),
            ])->assignRole('technician');
        }

        // Viewer
        User::firstOrCreate(['email' => 'viewer@mms.local'], [
            'name' => 'View Only', 'password' => Hash::make('password'),
        ])->assignRole('viewer');
    }
}


// ============================================================
// database/seeders/AssetSeeder.php
// ============================================================
namespace Database\Seeders;

use App\Models\Asset;
use Illuminate\Database\Seeder;

class AssetSeeder extends Seeder
{
    public function run(): void
    {
        $assets = [
            ['name' => 'Chiller Unit #1',       'code' => 'AST-001', 'category' => 'HVAC',       'location' => 'Building A, Roof'],
            ['name' => 'Chiller Unit #2',       'code' => 'AST-002', 'category' => 'HVAC',       'location' => 'Building B, Roof'],
            ['name' => 'Chiller Unit #3',       'code' => 'AST-003', 'category' => 'HVAC',       'location' => 'Building A, Floor 2'],
            ['name' => 'Generator Unit #1',     'code' => 'AST-004', 'category' => 'Electrical', 'location' => 'Basement'],
            ['name' => 'Elevator A',            'code' => 'AST-005', 'category' => 'Mechanical', 'location' => 'Building A'],
            ['name' => 'Elevator B',            'code' => 'AST-006', 'category' => 'Mechanical', 'location' => 'Building B'],
            ['name' => 'Main Electrical Panel', 'code' => 'AST-007', 'category' => 'Electrical', 'location' => 'Ground Floor'],
            ['name' => 'Plumbing System B3',    'code' => 'AST-008', 'category' => 'Plumbing',   'location' => 'Building B, Floor 3'],
            ['name' => 'Fire Suppression #1',   'code' => 'AST-009', 'category' => 'Safety',     'location' => 'Building A, All Floors'],
            ['name' => 'CCTV System',           'code' => 'AST-010', 'category' => 'Security',   'location' => 'All Buildings'],
        ];

        foreach ($assets as $a) {
            Asset::firstOrCreate(['code' => $a['code']], array_merge($a, [
                'status'       => 'active',
                'installed_at' => now()->subYears(rand(1, 8))->toDateString(),
            ]));
        }
    }
}


// ============================================================
// database/seeders/WorkOrderSeeder.php
// ============================================================
namespace Database\Seeders;

use App\Models\{WorkOrder, Asset, User};
use Illuminate\Database\Seeder;

class WorkOrderSeeder extends Seeder
{
    public function run(): void
    {
        $assets = Asset::all();
        $admin  = User::where('email', 'admin@mms.local')->first();
        $techs  = User::role('technician')->get();

        $sampleOrders = [
            ['title' => 'Replace cooling fan',          'type' => 'corrective',  'priority' => 'high',   'status' => 'in_progress', 'progress' => 65],
            ['title' => 'Generator PM service',         'type' => 'preventive',  'priority' => 'high',   'status' => 'open',        'progress' => 0],
            ['title' => 'Elevator B annual inspection', 'type' => 'inspection',  'priority' => 'medium', 'status' => 'open',        'progress' => 0],
            ['title' => 'Plumbing leak repair Floor 3', 'type' => 'corrective',  'priority' => 'high',   'status' => 'completed',   'progress' => 100],
            ['title' => 'Lighting replacement B1',      'type' => 'corrective',  'priority' => 'low',    'status' => 'in_progress', 'progress' => 80],
            ['title' => 'Fire suppression test',        'type' => 'inspection',  'priority' => 'critical','status'=> 'assigned',    'progress' => 0],
            ['title' => 'HVAC filter replacement',      'type' => 'preventive',  'priority' => 'medium', 'status' => 'completed',   'progress' => 100],
            ['title' => 'CCTV camera cleaning',         'type' => 'preventive',  'priority' => 'low',    'status' => 'open',        'progress' => 0],
        ];

        foreach ($sampleOrders as $i => $data) {
            $tech = $techs->random();
            WorkOrder::create(array_merge($data, [
                'asset_id'     => $assets->random()->id,
                'created_by'   => $admin->id,
                'assigned_to'  => $tech->id,
                'due_at'       => now()->addDays(rand(-3, 14)),
                'started_at'   => in_array($data['status'], ['in_progress','completed']) ? now()->subHours(rand(1,12)) : null,
                'completed_at' => $data['status'] === 'completed' ? now()->subHours(rand(1,6)) : null,
            ]));
        }
    }
}

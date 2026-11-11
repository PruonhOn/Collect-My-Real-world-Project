<?php
// ============================================================
// routes/web.php
// ============================================================
use App\Http\Controllers\{
    DashboardController,
    WorkOrderController,
    AssetController,
    ReportController,
    UserController,
};
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/api/kpis', [DashboardController::class, 'kpisJson'])->name('dashboard.kpis');

    // Work Orders
    Route::resource('work-orders', WorkOrderController::class);
    Route::patch('work-orders/{workOrder}/assign',   [WorkOrderController::class, 'assign'])->name('work-orders.assign');
    Route::patch('work-orders/{workOrder}/start',    [WorkOrderController::class, 'start'])->name('work-orders.start');
    Route::patch('work-orders/{workOrder}/complete', [WorkOrderController::class, 'complete'])->name('work-orders.complete');

    // Assets
    Route::resource('assets', AssetController::class);
    Route::get('assets/{asset}/history', [AssetController::class, 'history'])->name('assets.history');

    // Reports — Admins only
    Route::middleware('can:generate-reports')->group(function () {
        Route::get('/reports',             [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/work-orders', [ReportController::class, 'workOrders'])->name('reports.work-orders');
        Route::get('/reports/assets',      [ReportController::class, 'assets'])->name('reports.assets');
        Route::post('/reports/export',     [ReportController::class, 'export'])->name('reports.export');
    });

    // User management — Admins only
    Route::middleware('role:admin')->group(function () {
        Route::resource('users', UserController::class);
        Route::patch('users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
    });
});

// ============================================================
// app/Console/Kernel.php — Scheduled Jobs
// ============================================================
/*
In Laravel 13, define schedules in routes/console.php:

use Illuminate\Support\Facades\Schedule;
use App\Jobs\{NotifyOverdueWorkOrders, GenerateMaintenanceReport};

Schedule::job(new NotifyOverdueWorkOrders)->hourly();
Schedule::job(new GenerateMaintenanceReport)->monthly()->emailOutputTo('manager@company.com');
*/

// ============================================================
// database/seeders/RolesAndPermissionsSeeder.php
// ============================================================
/*
php artisan make:seeder RolesAndPermissionsSeeder

use Spatie\Permission\Models\{Role, Permission};

$permissions = [
    // Work Orders
    'view work-orders', 'create work-orders', 'edit work-orders',
    'delete work-orders', 'assign work-orders', 'complete work-orders',
    // Assets
    'view assets', 'create assets', 'edit assets', 'delete assets',
    // Reports
    'generate reports', 'export reports',
    // Users
    'manage users', 'view users',
    // Alerts
    'view alerts', 'dismiss alerts',
];

foreach ($permissions as $perm) {
    Permission::firstOrCreate(['name' => $perm]);
}

// Admin — full access
$admin = Role::firstOrCreate(['name' => 'admin']);
$admin->syncPermissions(Permission::all());

// Technician — own work orders + assets view
$tech = Role::firstOrCreate(['name' => 'technician']);
$tech->syncPermissions([
    'view work-orders', 'edit work-orders', 'complete work-orders',
    'view assets', 'view alerts', 'dismiss alerts',
]);

// Viewer — read-only
$viewer = Role::firstOrCreate(['name' => 'viewer']);
$viewer->syncPermissions([
    'view work-orders', 'view assets', 'view alerts',
]);

// Create default admin user
\App\Models\User::factory()->create([
    'name'  => 'Admin User',
    'email' => 'admin@mms.local',
])->assignRole('admin');
*/

// ============================================================
// app/Policies/WorkOrderPolicy.php
// ============================================================
/*
class WorkOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view work-orders');
    }

    public function view(User $user, WorkOrder $workOrder): bool
    {
        return $user->can('view work-orders') ||
               $workOrder->assigned_to === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('create work-orders');
    }

    public function update(User $user, WorkOrder $workOrder): bool
    {
        // Admins can edit all; technicians can only update assigned orders
        if ($user->hasRole('admin')) return true;
        return $workOrder->assigned_to === $user->id && $user->can('edit work-orders');
    }

    public function delete(User $user, WorkOrder $workOrder): bool
    {
        return $user->hasRole('admin') && $user->can('delete work-orders');
    }

    public function assign(User $user): bool
    {
        return $user->can('assign work-orders');
    }
}
*/

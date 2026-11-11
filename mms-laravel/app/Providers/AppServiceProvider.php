<?php
// ============================================================
// app/Providers/AppServiceProvider.php
// ============================================================
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Use our custom dark-theme pagination
        Paginator::defaultView('components.pagination');
        Paginator::defaultSimpleView('components.pagination');
    }
}


// ============================================================
// app/Providers/AuthServiceProvider.php
// ============================================================
namespace App\Providers;

use App\Models\WorkOrder;
use App\Policies\WorkOrderPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        WorkOrder::class => WorkOrderPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}

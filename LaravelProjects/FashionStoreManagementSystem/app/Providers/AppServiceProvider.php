<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Policies\CategoryPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\InventoryPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use App\Policies\ReportPolicy;
use App\Policies\SettingsPolicy;
use App\Policies\StaffPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\ReportingService::class);
        $this->app->singleton(\App\Services\SaleService::class);
        $this->app->singleton(\App\Services\InventoryService::class);
        $this->app->singleton(\App\Services\ProductService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(User::class, StaffPolicy::class);
        Gate::policy(InventoryTransaction::class, InventoryPolicy::class);
        Gate::policy(Setting::class, SettingsPolicy::class);
        Gate::policy(ReportPolicy::class, ReportPolicy::class);

        View::composer('*', function ($view) {
            $view->with('appSettings', $this->resolveSettings());
        });
    }

    private function resolveSettings(): array
    {
        try {
            return Setting::allSettings();
        } catch (\Throwable) {
            return [
                'store_name' => 'Fashion Store',
                'store_tagline' => null,
                'store_address' => null,
                'store_phone' => null,
                'store_email' => null,
                'currency' => '$',
                'tax_rate' => '10',
                'store_logo' => null,
                'receipt_footer' => 'Thank you for shopping with us!',
            ];
        }
    }
}

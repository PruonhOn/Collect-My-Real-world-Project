<?php

namespace App\Providers;

use App\Http\ViewComposers\SidebarComposer;
use App\Repositories\AuditLogRepository;
use App\Repositories\BaseRepository;
use App\Repositories\BranchRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\DeliveryRepository;
use App\Repositories\DriverRepository;
use App\Repositories\ExpenseRepository;
use App\Repositories\InventoryItemRepository;
use App\Repositories\InvoiceRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\PermissionRepository;
use App\Repositories\RoleRepository;
use App\Repositories\RouteRepository;
use App\Repositories\ShipmentRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;
use App\Repositories\VehicleRepository;
use App\Repositories\WarehouseRepository;
use App\Services\AuditLogService;
use App\Services\BranchService;
use App\Services\CustomerService;
use App\Services\DeliveryService;
use App\Services\DriverService;
use App\Services\ExpenseService;
use App\Services\InventoryService;
use App\Services\InvoiceService;
use App\Services\NotificationService;
use App\Services\PaymentService;
use App\Services\PermissionService;
use App\Services\RoleService;
use App\Services\RouteService;
use App\Services\ShipmentService;
use App\Services\TransactionService;
use App\Services\UserService;
use App\Services\VehicleService;
use App\Services\WarehouseService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->when(AuditLogService::class)->needs(BaseRepository::class)->give(AuditLogRepository::class);
        $this->app->when(BranchService::class)->needs(BaseRepository::class)->give(BranchRepository::class);
        $this->app->when(CustomerService::class)->needs(BaseRepository::class)->give(CustomerRepository::class);
        $this->app->when(DeliveryService::class)->needs(BaseRepository::class)->give(DeliveryRepository::class);
        $this->app->when(DriverService::class)->needs(BaseRepository::class)->give(DriverRepository::class);
        $this->app->when(ExpenseService::class)->needs(BaseRepository::class)->give(ExpenseRepository::class);
        $this->app->when(InventoryService::class)->needs(BaseRepository::class)->give(InventoryItemRepository::class);
        $this->app->when(InvoiceService::class)->needs(BaseRepository::class)->give(InvoiceRepository::class);
        $this->app->when(NotificationService::class)->needs(BaseRepository::class)->give(NotificationRepository::class);
        $this->app->when(PaymentService::class)->needs(BaseRepository::class)->give(PaymentRepository::class);
        $this->app->when(PermissionService::class)->needs(BaseRepository::class)->give(PermissionRepository::class);
        $this->app->when(RoleService::class)->needs(BaseRepository::class)->give(RoleRepository::class);
        $this->app->when(RouteService::class)->needs(BaseRepository::class)->give(RouteRepository::class);
        $this->app->when(ShipmentService::class)->needs(BaseRepository::class)->give(ShipmentRepository::class);
        $this->app->when(TransactionService::class)->needs(BaseRepository::class)->give(TransactionRepository::class);
        $this->app->when(UserService::class)->needs(BaseRepository::class)->give(UserRepository::class);
        $this->app->when(VehicleService::class)->needs(BaseRepository::class)->give(VehicleRepository::class);
        $this->app->when(WarehouseService::class)->needs(BaseRepository::class)->give(WarehouseRepository::class);
    }

    public function boot(): void
    {
        View::composer('*', SidebarComposer::class);
    }
}

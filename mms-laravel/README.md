# MaintainPro — Maintenance Management System
**Laravel 13 | Livewire 3 | Spatie Permissions | Laravel Reverb**

---

## Stack
- **Laravel 13** — core framework
- **Livewire 3** — reactive UI components (no page refresh)
- **Alpine.js** — lightweight interactions & animations
- **Spatie Laravel Permission** — roles & permissions (Admin, Technician, Viewer)
- **Laravel Reverb** — native WebSocket server for real-time notifications
- **Laravel Echo** — frontend WebSocket client
- **Maatwebsite/Excel** — Excel exports
- **Barryvdh/Laravel-DomPDF** — PDF reports
- **Redis** — cache + queue backend

---

## Quick Start

```bash
# 1. Create Laravel 13 project
composer create-project laravel/laravel mms-laravel "^13.0"
cd mms-laravel

# 2. Install packages
composer require \
  livewire/livewire \
  spatie/laravel-permission \
  maatwebsite/excel \
  barryvdh/laravel-dompdf \
  laravel/reverb

# 3. Frontend
npm install @alpinejs/morph chart.js

# 4. Publish configs
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan reverb:install

# 5. Setup .env
DB_CONNECTION=mysql
DB_DATABASE=mms_db
REDIS_HOST=127.0.0.1
BROADCAST_DRIVER=reverb
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis

# 6. Run migrations + seed
php artisan migrate --seed

# 7. Start all services
php artisan serve &
php artisan reverb:start &
php artisan queue:work &
npm run dev
```

---

## Directory Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── WorkOrderController.php
│   │   ├── AssetController.php
│   │   ├── DashboardController.php
│   │   ├── ReportController.php
│   │   └── UserController.php
│   ├── Middleware/
│   │   └── CheckPermission.php
│   └── Requests/
│       ├── StoreWorkOrderRequest.php
│       └── StoreAssetRequest.php
├── Models/
│   ├── User.php
│   ├── WorkOrder.php
│   ├── Asset.php
│   ├── MaintenanceLog.php
│   └── Alert.php
├── Services/
│   ├── WorkOrderService.php
│   ├── AssetService.php
│   └── ReportService.php
├── Notifications/
│   ├── WorkOrderAssigned.php
│   ├── WorkOrderOverdue.php
│   └── AlertTriggered.php
└── Livewire/
    ├── Dashboard/
    │   └── KpiCards.php
    ├── WorkOrders/
    │   ├── WorkOrderTable.php
    │   └── WorkOrderForm.php
    └── Notifications/
        └── NotificationBell.php
```

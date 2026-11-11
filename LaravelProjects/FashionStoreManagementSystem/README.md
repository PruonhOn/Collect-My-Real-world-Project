# Fashion Store Management System

A complete, production-ready retail management system for fashion stores, built with **Laravel 11**, **MySQL**, and **Bootstrap 5**. It covers the full point-of-sale and inventory workflow: products with size/color variants and barcodes, an AJAX cashier POS with printable receipts, orders, refunds, inventory tracking, reporting with CSV exports, staff management, and store settings.

## Features

- **Roles & access control** — `admin`, `manager`, and `cashier` roles with route-level and policy-level authorization.
- **Products & variants** — multi-image products, size/color variants, auto-generated variant SKUs, barcode display, cost/retail pricing, soft deletes.
- **AJAX Point of Sale (POS)** — product grid with search/filter, barcode lookup, cart, discounts (fixed/percentage), tax, cash/QR payments, change calculation, and a printable receipt (JsBarcode barcodes).
- **Orders & refunds** — order history with filters, order detail, partial/full refunds with automatic stock restoration.
- **Inventory** — stock-in (purchase), stock adjustments, damage/loss, and a full transaction ledger with before/after stock levels.
- **Reports** — dashboard KPIs with revenue chart, sales report with date/cashier/customer filters, top products, low/out-of-stock alerts, inventory valuation, and CSV exports.
- **Customers** — customer directory with order history and loyalty labels.
- **Staff & settings** — staff CRUD with activation toggle, store settings (name, currency, tax rate, receipt footer, logo).
- **Seeded demo data** — 30 days of realistic sales history, sample refunds, customers, and products.

## Requirements

- PHP >= 8.2 (tested on 8.4)
- Composer
- MySQL 8.x
- Node.js >= 18 and npm

## Installation

```bash
# 1. Install PHP dependencies
composer install

# 2. Configure environment
cp .env.example .env
php artisan key:generate
#   - Edit .env: set DB_DATABASE, DB_USERNAME, DB_PASSWORD
#   - Create the database, e.g.: CREATE DATABASE fashion_store;

# 3. Migrate and seed demo data
php artisan migrate --seed

# 4. Link storage so product images are served
php artisan storage:link

# 5. Install and build front-end assets
npm install
npm run build

# 6. Start the app
php artisan serve
```

Open http://localhost:8000 and log in with a demo account.

## Demo accounts

| Role    | Email                      | Password |
|---------|----------------------------|----------|
| Admin   | `admin@fashionstore.test`  | `password` |
| Manager | `manager@fashionstore.test`| `password` |
| Cashier | `cashier@fashionstore.test`| `password` |

**Access matrix**
- **Admin** — everything, including staff management and store settings.
- **Manager** — dashboard, POS, orders, products, categories, customers, inventory, reports.
- **Cashier** — dashboard, POS, and orders only (redirected to POS after login).

## Tech stack

- **Backend:** Laravel 11, MySQL, Eloquent ORM, Form Requests, Policies, Services
- **Frontend:** Blade, Bootstrap 5, Bootstrap Icons, Chart.js, JsBarcode, Vite
- **Payment/inventory logic:** transactional `SaleService` with row-locking stock checks, `InventoryService` ledger

## Project structure

```
app/
  Enums/            Role, OrderStatus, PaymentMethod, DiscountType, InventoryTransactionType
  Exceptions/       InsufficientStockException
  Http/Controllers/ Dashboard, Pos, Product, Category, Customer, Inventory, Order, Report, Staff, Setting
  Http/Middleware/  EnsureUserRole, EnsureUserActive
  Http/Requests/    Form request validation for every write endpoint
  Models/           Product, ProductVariant, ProductImage, Category, Customer, Order, OrderItem, Refund, RefundItem, InventoryTransaction, Setting, User
  Policies/         Per-resource authorization policies
  Services/         SaleService, InventoryService, ProductService, ReportingService
  Support/          helpers.php (app_setting, money, store_name, ...)
database/
  migrations/       12 domain migrations
  seeders/          Demo settings, users, categories, products, customers, orders
resources/
  views/            Blade templates (layouts, POS, CRUD, reports, auth)
  js/               app.js, pos.js, dashboard.js, reports.js, product-form.js
  css/              Custom theme on top of Bootstrap
```

## Troubleshooting

- **Barcodes don't render** — barcodes are rendered client-side by JsBarcode from the variant barcode/SKU.
- **Product images missing** — re-run `php artisan storage:link`.
- **Reset demo data** — run `php artisan migrate:fresh --seed`.

## License

This project is for demonstration and internal use.

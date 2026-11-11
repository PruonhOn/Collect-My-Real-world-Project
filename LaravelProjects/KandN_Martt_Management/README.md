# K&N Mart Management System (MMS)

A complete Mart Management System built with Laravel 12, PHP 8+, MySQL/SQLite, Bootstrap 5, and REST API Architecture.

## System Overview

The Mart Management System (MMS) is a comprehensive retail management solution designed for supermarkets and minimarts. It covers the complete business lifecycle from supplier management and purchasing to point-of-sale operations and financial reporting.

## Features

### 1. Supplier Management
- Supplier registration with full contact details
- Supplier product catalog management
- Purchase order management
- Supplier payment tracking

### 2. Product Management
- Categories (hierarchical), Brands, Units
- Barcode generation (auto & manual)
- Product pricing with discount & tax support
- Product images
- SKU and Barcode tracking
- Low stock alerts

### 3. Inventory Management
- Stock In / Stock Out
- Stock Adjustments (addition, subtraction, damage, expired)
- Stock Movement History
- Low Stock Warnings
- Reorder level management

### 4. Purchasing Process
- Purchase Orders with reference tracking
- Goods Receiving with automatic stock update
- Supplier Payments
- Purchase History with payment status

### 5. Point of Sale (POS)
- Fast product search (name, SKU, barcode)
- Barcode scanner integration
- Shopping cart with quantity controls
- Discount system (fixed & percentage)
- Tax calculation
- Multiple payment methods (cash, card, bank transfer, mobile banking)
- Receipt printing
- Customer selection/lookup

### 6. Customer Management
- Customer registration
- Membership cards
- Loyalty points system
- Purchase history tracking

### 7. Employee Management
- Employee profiles linked to users
- Attendance tracking
- Position & department management

### 8. Financial Management
- Income & Expense tracking
- Profit & Loss reports
- Cash flow monitoring

### 9. Reporting System
- Daily Sales Report
- Monthly Sales Report
- Product Performance Report
- Inventory Report
- Profit Report
- Customer Report

### 10. Role-Based Access Control (RBAC)
- **Super Admin**: Full system access
- **Manager**: Reports, inventory, employee management
- **Cashier**: POS operations, sales, receipts
- **Inventory Staff**: Stock management
- **Accountant**: Financial reports, expense management

### 11. Advanced Features
- Barcode generation & printing
- Loyalty points system
- Low stock notifications
- User activity audit trail
- System settings management

## Technology Stack

| Component | Technology |
|-----------|------------|
| Backend | Laravel 12, PHP 8.4+ |
| Database | MySQL 8.0+ / SQLite |
| Frontend | Bootstrap 5.3, jQuery, Chart.js |
| API | RESTful with Sanctum Auth |
| RBAC | Spatie Laravel Permission |
| Barcode | milon/barcode |
| PDF/Receipt | barryvdh/laravel-dompdf |
| Tables | DataTables |
| Icons | Bootstrap Icons |

## Installation

### Prerequisites
- PHP 8.1+
- Composer 2.x
- MySQL 8.0+ or SQLite
- Node.js & NPM (for Vite)

### Setup Steps

```bash
# 1. Clone the repository
git clone <repository-url> kn-mart
cd kn-mart

# 2. Install PHP dependencies
composer install

# 3. Environment setup
cp .env.example .env
php artisan key:generate

# 4. Configure database in .env
# For MySQL:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=kn_mart
# DB_USERNAME=root
# DB_PASSWORD=

# For SQLite (default):
# DB_CONNECTION=sqlite

# 5. Run migrations and seeders
php artisan migrate --seed

# 6. Create storage link
php artisan storage:link

# 7. Install & build frontend
npm install
npm run build

# 8. Start development server
php artisan serve
```

### Default Login Credentials

| Role | Email | Password |
|------|-------|----------|
| Super Admin | admin@mart.com | password |
| Manager | manager@mart.com | password |
| Cashier | cashier@mart.com | password |
| Inventory Staff | inventory@mart.com | password |
| Accountant | accountant@mart.com | password |

## Database Schema

### Master Tables
- **users** - System users with authentication
- **roles** / **permissions** - RBAC via Spatie
- **categories** - Product categories (self-referencing parent)
- **brands** - Product brands
- **units** - Units of measurement (with conversion)
- **suppliers** - Supplier information
- **customers** - Customer information with loyalty points
- **employees** - Employee records linked to users
- **products** - Product catalog with pricing, stock, barcode

### Inventory Tables
- **stocks** - Current stock levels per product
- **stock_movements** - Audit trail of all stock changes
- **stock_adjustments** - Manual stock adjustments

### Purchase Tables
- **purchases** - Purchase orders
- **purchase_details** - Purchased items
- **supplier_payments** - Payments to suppliers

### Sales Tables
- **sales** - Sales transactions
- **sale_details** - Sold items
- **sale_payments** - Payment records

### Financial Tables
- **expenses** - Business expenses
- **incomes** - Business incomes
- **transactions** - Unified transaction log

### System Tables
- **activity_logs** - User activity audit trail
- **notifications** - System notifications
- **settings** - Application settings

## API Endpoints

Base URL: `/api`

### Authentication
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/login` | Login |
| POST | `/register` | Register |
| POST | `/logout` | Logout |
| GET | `/user` | Get authenticated user |

### Products
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/products` | List products |
| POST | `/products` | Create product |
| GET | `/products/{id}` | Show product |
| PUT | `/products/{id}` | Update product |
| DELETE | `/products/{id}` | Delete product |
| GET | `/products/search` | Search products |
| GET | `/products/barcode/{id}` | Generate barcode |

### Categories / Brands / Units / Suppliers / Customers / Employees
Standard CRUD endpoints via `apiResource`.

### Inventory
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/inventory` | List stock |
| POST | `/inventory/stock-in` | Add stock |
| POST | `/inventory/stock-out` | Remove stock |
| POST | `/inventory/adjust` | Adjust stock |
| GET | `/inventory/movements` | Stock movements |
| GET | `/inventory/low-stock` | Low stock alerts |

### POS
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/pos/search` | Search products |
| POST | `/pos/scan` | Scan barcode |
| POST | `/pos/checkout` | Complete sale |
| GET | `/pos/receipt/{id}` | Get receipt |
| GET | `/pos/low-stock` | Low stock alerts |

### Reports
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/reports/daily-sales` | Daily sales report |
| GET | `/reports/monthly-sales` | Monthly sales report |
| GET | `/reports/product-performance` | Product performance |
| GET | `/reports/profit-loss` | Profit & loss |
| GET | `/reports/inventory` | Inventory report |
| GET | `/reports/customers` | Customer report |

### Dashboard
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/dashboard` | Dashboard stats |
| GET | `/dashboard/sales-chart` | Sales chart data |

## Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/          # REST API Controllers (18 controllers)
│   │   └── Web/          # Blade Web Controllers (16 controllers)
│   ├── Middleware/        # CheckRole, CheckPermission, ActivityLogger
│   ├── Requests/         # Form requests
│   └── Resources/        # API resources
├── Models/               # 22 Eloquent Models
├── Repositories/         # BaseRepository, ProductRepository, SaleRepository
├── Services/             # InventoryService, PosService, ReportService, BarcodeService, NotificationService
└── Traits/               # ApiResponse Trait

database/
├── migrations/           # 27 migration files
└── seeders/              # 7 seeders (roles, users, categories, brands, units, settings)

resources/views/
├── layouts/              # Main app layout
├── auth/                 # Login page
├── dashboard/            # Dashboard with charts
├── pos/                  # Point of Sale interface
├── products/             # Product management
├── inventory/            # Stock management
├── suppliers/            # Supplier management
├── purchases/            # Purchase orders
├── sales/                # Sales history
├── customers/            # Customer management
├── employees/            # Employee management
├── financial/            # Financial management
├── reports/              # Reports
└── settings/             # System settings

routes/
├── web.php               # Web routes (Blade)
└── api.php               # API routes (Sanctum)
```

## Architecture & Design Patterns

- **SOLID Principles** - Single responsibility, Open-closed, Liskov substitution, Interface segregation, Dependency inversion
- **Repository Pattern** - Data access abstraction via BaseRepository
- **Service Layer** - Business logic encapsulated in Services
- **RBAC** - Role-based access control via Spatie
- **RESTful API** - Well-defined API endpoints with JSON responses
- **MVC Architecture** - Clean separation of concerns

## Development Roadmap

### Phase 1: Foundation (Week 1)
- [x] Project setup & configuration
- [x] Database design & migrations
- [x] Authentication (Sanctum)
- [x] RBAC (Spatie)

### Phase 2: Core Features (Week 2)
- [x] Product management (CRUD)
- [x] Category, Brand, Unit management
- [x] Supplier management
- [x] Customer management

### Phase 3: Operations (Week 3)
- [x] Inventory management
- [x] Purchase order management
- [x] Sales management
- [x] POS interface

### Phase 4: Business Intelligence (Week 4)
- [x] Dashboard & reports
- [x] Financial management
- [x] Barcode system
- [x] Loyalty system

### Phase 5: Enhancement (Week 5)
- [ ] Email notifications
- [ ] SMS integration
- [ ] Multi-currency support
- [ ] Advanced analytics
- [ ] Mobile app (React Native)

## Deployment Guide

### Production Server Requirements
- PHP 8.1+ with extensions: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML, GD
- MySQL 8.0+ or MariaDB 10.3+
- Composer 2.x
- Web server: Nginx or Apache
- SSL certificate

### Deployment Steps

```bash
# 1. Prepare the application
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 2. Set production environment
# Update .env:
# APP_ENV=production
# APP_DEBUG=false
# APP_URL=https://your-domain.com

# 3. Set up database
php artisan migrate --force

# 4. Set up cron job for scheduled tasks
# * * * * * php /path-to-project/artisan schedule:run >> /dev/null 2>&1

# 5. Set up queue worker (if using queues)
# php artisan queue:work --daemon

# 6. Set proper permissions
chmod -R 775 storage bootstrap/cache
chmod -R 775 public/uploads
```

### Backup Strategy
- Daily database backup via `php artisan backup:run`
- Weekly full backup of storage directory
- Store backups in separate secure location

## Security

- All passwords hashed with Bcrypt
- API authentication via Sanctum tokens
- Role-based middleware for authorization
- Permission-based access control
- CSRF protection for web routes
- SQL injection prevention via Eloquent ORM
- Input validation on all requests

## License

This project is proprietary software.

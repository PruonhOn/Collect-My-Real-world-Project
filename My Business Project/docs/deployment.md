# Deployment

## 1. Requirements

| Component | Version | Notes |
| --- | --- | --- |
| PHP | 8.4 | `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `curl`, `zip`, `gd`, `intl` |
| Composer | 2.x | |
| MySQL | 8.0+ | InnoDB, `utf8mb4` |
| Node.js | 20+ (22/24 recommended) | Only needed to build the SPA |
| Web server | Nginx or Apache | TLS termination |
| Process supervisor | systemd / Supervisor | Queue worker + scheduler |

## 2. Backend installation

```bash
cd backend
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

Edit `.env`:

```dotenv
APP_NAME="My Business"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.example.com
APP_FRONTEND_URL=https://app.example.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=my_business
DB_USERNAME=my_business
DB_PASSWORD=••••••••

CACHE_STORE=redis          # or database
QUEUE_CONNECTION=redis     # or database
SESSION_DRIVER=database

MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=••••••••
MAIL_PASSWORD=••••••••
MAIL_FROM_ADDRESS="billing@example.com"

BILLING_DEFAULT_GATEWAY=stripe
STRIPE_SECRET_KEY=sk_live_•••
STRIPE_WEBHOOK_SECRET=whsec_•••
```

Then:

```bash
php artisan migrate --force
php artisan db:seed --class=PlanSeeder      # plans + feature matrix
php artisan storage:link
php artisan optimize                          # config + route + view cache
```

## 3. Frontend build

```bash
cd frontend
npm ci
npm run build            # emits frontend/dist
```

`VITE_*` values are baked into the bundle at build time:

| Variable | Purpose |
| --- | --- |
| `VITE_API_URL` | API base URL. `/api/v1` behind a reverse proxy, or the absolute API origin |
| `VITE_APP_NAME` | Product name shown in the shell |

Publish `frontend/dist` as the document root of the SPA vhost.

## 4. Web server

### Nginx (SPA + API on the same origin)

```nginx
server {
    listen 443 ssl http2;
    server_name example.com;

    root /var/www/my-business/frontend/dist;
    index index.html;

    location /api/ {
        proxy_pass http://127.0.0.1:8100;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    location / {
        try_files $uri $uri/ /index.html;   # SPA history fallback
    }
}
```

The SPA fallback is required: every client-side route (`/dashboard`,
`/orders`, …) must serve `index.html`.

### Apache

```apache
<VirtualHost *:443>
    ServerName example.com
    DocumentRoot /var/www/my-business/frontend/dist

    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ /index.html [L]

    ProxyPass        /api/ http://127.0.0.1:8100/
    ProxyPassReverse /api/ http://127.0.0.1:8100/
</VirtualHost>
```

### Laravel behind Nginx

```nginx
server {
    listen 80;
    server_name api.example.com;
    root /var/www/my-business/backend/public;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

Point `DocumentRoot` at `backend/public` — never at the project root.

## 5. Background work

```bash
php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
php artisan schedule:work
```

systemd units:

```ini
# /etc/systemd/system/my-business-worker.service
[Unit]
Description=My Business queue worker
After=network.target

[Service]
User=www-data
WorkingDirectory=/var/www/my-business/backend
ExecStart=/usr/bin/php artisan queue:work redis --sleep=3 --tries=3
Restart=always

[Install]
WantedBy=multi-user.target
```

```ini
# /etc/systemd/system/my-business-scheduler.service
[Service]
User=www-data
WorkingDirectory=/var/www/my-business/backend
ExecStart=/usr/bin/php artisan schedule:work
Restart=always
```

```ini
# /etc/systemd/system/my-business-scheduler.timer
[Timer]
OnCalendar=*:*:00
Persistent=true

[Install]
WantedBy=timers.target
```

```bash
sudo systemctl enable --now my-business-worker my-business-scheduler.timer
```

Alternatively a single cron entry:

```
* * * * * cd /var/www/my-business/backend && php artisan schedule:run >> /dev/null 2>&1
```

## 6. Release procedure

```bash
cd /var/www/my-business/backend
git pull --ff-only
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan queue:restart

cd ../frontend
npm ci
npm run build
# reload nginx / restart php-fpm
```

Migrations run **before** the new code is served. Migrations must be backward
compatible with the previous release (add columns before dropping them, in two
releases) so that a rollback of the code never breaks the schema.

## 7. Health checks

| Check | Command / URL |
| --- | --- |
| Liveness | `GET /up` (Laravel health route) |
| Database | `php artisan migrate:status` |
| Queue | `php artisan queue:monitor` or the worker log |
| Scheduler | `php artisan schedule:list` |
| API | `curl -i https://api.example.com/api/v1/plans` |
| SPA | `curl -I https://app.example.com/` |

## 8. Operational settings

| Setting | Value | Why |
| --- | --- | --- |
| `APP_DEBUG` | `false` | Never leak stack traces |
| `SESSION_SECURE_COOKIE` | `true` | HTTPS only |
| `LOG_LEVEL` | `info` (production) | Keep the disk small |
| `BCRYPT_ROUNDS` | `12` | Default trade-off |
| `CACHE_STORE` | `redis` or `database` | Entitlement cache performance |
| `QUEUE_CONNECTION` | `redis` or `database` | Mail and PDF generation |

Rotate the credentials regularly: `php artisan auth:clear-resets`, rotate
gateway secrets, rotate `APP_KEY` only with a full session flush (it
invalidates every encrypted value).

## 9. Monitoring

Minimum viable monitoring:

- **Uptime**: HTTP check on `/up` and `/api/v1/plans`, 1-minute interval.
- **Errors**: 5xx rate on the API; alert above 1%.
- **Latency**: p95 of `/api/v1/business/dashboard`.
- **Billing**: number of `past_due` subscriptions, number of failed webhooks in
  `payment_webhook_events`.
- **Disk**: `storage/` usage, database size, backup freshness.

## 10. Local development

```bash
# terminal 1 — API on :8100
cd backend
php artisan serve --port=8100

# terminal 2 — SPA on :5173 with the API proxy
cd frontend
npm run dev
```

The Vite proxy forwards `/api` to `VITE_API_PROXY_TARGET`
(`http://127.0.0.1:8100` by default), so the browser never makes a
cross-origin request during development.

Run the test suites:

```bash
cd backend
php artisan test                       # MySQL
$env:DB_CONNECTION='sqlite'; $env:DB_DATABASE='database/testing.sqlite'; php artisan test

cd ../frontend
npm run lint
npm run build
```

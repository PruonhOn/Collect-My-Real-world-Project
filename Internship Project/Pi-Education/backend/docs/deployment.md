# Pi-Education — Production Preparation & Deployment Guide

This document describes how to run Pi-Education in production. It covers environment
configuration, storage, queues, cache, deployment, backups, logging, and error monitoring.

> **Important security rule:** Never commit `.env`, API keys, payment secrets, or private
> credentials. Only `.env.example` (with placeholders) is committed.

---

## 1. Environment Configuration

Copy the template and fill in real values:

```bash
cp .env.example .env
```

Key production settings:

| Variable | Recommended value | Why |
|---|---|---|
| `APP_ENV` | `production` | Enables production optimizations |
| `APP_DEBUG` | `false` | Never expose stack traces |
| `APP_URL` | `https://api.pi-education.com` | Real API domain |
| `APP_KEY` | generated via `php artisan key:generate` | Encrypts cookies/session |
| `CORS_ALLOWED_ORIGINS` | `https://pi-education.com` | Only allow your real frontend |
| `SANCTUM_STATEFUL_DOMAINS` | `pi-education.com` | Frontend cookie domain |
| `FRONTEND_URL` | `https://pi-education.com` | Frontend origin |
| `API_RATE_LIMIT` | `60` | Requests per minute per user/IP |
| `LOG_LEVEL` | `warning` | Reduce noise in production |

Secure the database with a dedicated, least-privilege user. Set `DB_HOST`, `DB_DATABASE`,
`DB_USERNAME`, `DB_PASSWORD` to your production values.

---

## 2. Database Migrations & Seeders

```bash
php artisan migrate --force          # --force skips confirmation in production
php artisan db:seed --force          # only if seeding production (dev credentials only)
```

Change the seeded admin password (`admin@pieducation.test` / `admin1234`) immediately or
create the admin through your onboarding flow. Do **not** reuse development credentials.

---

## 3. Storage Configuration

- Default local filesystem (`storage/app/private`) holds avatars/covers when
  `FILESYSTEM_DISK=local` and they are stored on the `public` disk.
- Create the public storage symlink so covers/avatars load:

```bash
php artisan storage:link
```

- For scale, switch book covers/avatars to S3-compatible storage:

```bash
# .env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=...
AWS_BUCKET=...
```

- **Protected book content is stored in the database (`book_translations.content`) and is
  served only through the authorized `/read/{slug}` endpoint.** It is never placed on the
  public disk, so no public file URLs leak paid content.

---

## 4. Queue Configuration

`QUEUE_CONNECTION=database` works but is not ideal for scale. In production:

1. Switch to Redis:

```bash
# .env
QUEUE_CONNECTION=redis
CACHE_STORE=redis
REDIS_HOST=...
```

2. Run the queue worker as a supervised service:

```bash
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

On Linux, use `supervisord` or systemd to keep the worker running. When notifications or
payroll/email jobs are added they will flow through this worker.

---

## 5. Cache & Optimization

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

Always re-cache config after changing `.env`. Set `CACHE_STORE=redis` in production. The
`database` cache driver is fine for small deployments but Redis scales better.

---

## 6. Serving the Application

Point your web server (Nginx / Apache) at `backend/public`. The React SPA is a separate
static build served from your frontend host/CDN.

Example Nginx vhost (API):

```nginx
server {
    listen 443 ssl;
    server_name api.pi-education.com;

    root /var/www/pi-education/backend/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
    }

    location ~ \.htaccess {
        deny all;
    }
}
```

Set the frontend `VITE_API_URL`/proxy to the API domain. Serve `frontend/dist` from your
static host or CDN (e.g. Nginx, Netlify, Vercel, Cloudflare Pages).

---

## 7. Backup Strategy

Back up three things:

1. **MySQL database** — the source of truth for users, books, orders, VIP, payments.
2. **Storage files** — `backend/storage/app` (avatars, covers).
3. **`.env`** — secrets (do not commit; keep a secure copy).

Recommended:

```bash
# Daily DB dump (optionally with mysqldump --single-transaction for InnoDB)
mysqldump -uUSER -p pi_education > backups/pi_education_$(date +%F).sql

# Keep N days of rotated dumps
find backups -name "pi_education_*.sql" -mtime +14 -delete
```

- Store backups **off-site** (object storage / separate server) and test restores regularly.
- For keys on S3-compatible storage: enable versioning and cross-region replication.
- Consider scheduled backup via cron or a managed service (e.g. AWS RDS automated backups).

---

## 8. Logging

- `LOG_CHANNEL=stack`, `LOG_STACK=single` writes local logs to `storage/logs/laravel.log`.
- In production set `LOG_LEVEL=warning` or `error`.
- For multiple servers use a central aggregator (e.g. `daily`, `papertrail`, or send to an
  external service). Configure a syslog/channel driver as needed.

---

## 9. Error Monitoring

Recommended: **Sentry** (PHP + JS).

```bash
composer require sentry/sentry-laravel
npm install --save @sentry/react
```

Then in `.env`:

```bash
SENTRY_LARAVEL_DSN=...
```

- Initialize Sentry in the Laravel exception handler and the React app entry.
- Sentry captures exceptions with stack traces and source context without exposing them to
  end users. **Frontend/API responses still return the generic envelope** (`{"success":false}`).

Alternative/lightweight: a crash-reporting endpoint or a Slack/webhook notification fed from
the log. Also add uptime monitoring (e.g. hit `GET /api/v1/health` every minute).

---

## 10. Deployment Checklist

- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] `APP_KEY` set
- [ ] Migrations run with `--force`
- [ ] `storage:link` created (local disk) or `FILESYSTEM_DISK=s3` configured
- [ ] Queue worker running (`queue:work`) with Redis
- [ ] Config/route/view/event cache built
- [ ] CORS restricted to real origins
- [ ] DB dedicated user, strong password, off-site backups
- [ ] Logging level set; error monitoring (Sentry) configured
- [ ] HTTPS enforced; security headers added
- [ ] Admin default password changed

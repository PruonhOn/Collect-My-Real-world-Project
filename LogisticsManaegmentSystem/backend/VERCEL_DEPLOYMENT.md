# Vercel Deployment Guide (Laravel API)

This document covers the Vercel container deployment for the Laravel backend. It does not replace the existing Render instructions in the repository root `DEPLOYMENT.md`.

## Deployment topology

- Backend: Vercel Functions using the Container runtime.
- Container entrypoint: `Dockerfile.vercel` at the root of this backend repository.
- HTTP server: FrankenPHP/Caddy listening on `$PORT` (Vercel defaults to port 80).
- Frontend: React + Vite on Cloudflare Pages, calling the Vercel API over HTTPS.
- Database: external MySQL.
- Documents: S3-compatible object storage. Vercel local storage is ephemeral.

## Project root

The Vercel project must use this backend repository as its project root so that `Dockerfile.vercel` and `vercel.json` are at the Vercel project root.

If the Vercel project is connected to a monorepo instead, set the project Root Directory to `backend`.

Do not move `Dockerfile.vercel` into a subdirectory. Vercel only auto-detects the blessed names `Dockerfile`, `Containerfile`, `Dockerfile.vercel`, and `Containerfile.vercel` at the project root.

## Required environment variables

Set these in Vercel Project Settings for Preview and Production. Never commit values to Git.

| Variable | Required | Notes |
| --- | --- | --- |
| `APP_NAME` | Yes | Display name, for example `Logistics ERP`. |
| `APP_ENV` | Yes | `production`. |
| `APP_DEBUG` | Yes | `false`. Never enable in Production or Preview. |
| `APP_KEY` | Yes | Generate with `php artisan key:generate --show` and store only in Vercel. |
| `APP_URL` | Yes | Public HTTPS API origin, for example `https://your-api.vercel.app`. Used by signed email links and URL generation. |
| `FRONTEND_URL` | Yes | Exact Cloudflare Pages origin, for example `https://your-app.pages.dev`. No trailing slash. Drives CORS and auth redirects. |
| `DB_CONNECTION` | Yes | `mysql`. |
| `DB_HOST` | Yes | Publicly reachable MySQL host. |
| `DB_PORT` | Yes | Usually `3306`. |
| `DB_DATABASE` | Yes | Production database name. |
| `DB_USERNAME` | Yes | Production database user. |
| `DB_PASSWORD` | Yes | Production database password. |
| `MYSQL_ATTR_SSL_CA` | If required | Path to a CA bundle when the MySQL provider requires TLS verification. |
| `SESSION_DRIVER` | Yes | `database`. The `sessions` table is created by migrations. |
| `CACHE_STORE` | Yes | `database`. The `cache` and `cache_locks` tables are created by migrations. |
| `QUEUE_CONNECTION` | Yes | `sync`. Vercel has no persistent queue worker in this setup. |
| `LOG_CHANNEL` | Yes | `stderr` so logs reach Vercel. |
| `LOG_LEVEL` | Recommended | `info` or `warning` in production. |
| `FILESYSTEM_DISK` | Recommended | `local` is acceptable for non-document files, but it is ephemeral on Vercel. |
| `DOCUMENTS_DISK` | Yes | `s3`. Required for durable document uploads. |
| `AWS_ACCESS_KEY_ID` | Yes when `DOCUMENTS_DISK=s3` | S3-compatible access key. |
| `AWS_SECRET_ACCESS_KEY` | Yes when `DOCUMENTS_DISK=s3` | S3-compatible secret key. |
| `AWS_DEFAULT_REGION` | Yes when `DOCUMENTS_DISK=s3` | Region, or `auto` for Cloudflare R2. |
| `AWS_BUCKET` | Yes when `DOCUMENTS_DISK=s3` | Private document bucket. |
| `AWS_ENDPOINT` | For non-AWS providers | S3-compatible endpoint. |
| `AWS_USE_PATH_STYLE_ENDPOINT` | For non-AWS providers | `true` for Cloudflare R2 and many MinIO deployments. |
| `AWS_URL` | Optional | Only needed if a public bucket URL is used. Document downloads do not require it. |
| `TRUSTED_PROXIES` | Yes | `*` for Vercel. See the security note below. |
| `GOOGLE_CLIENT_ID` | If Google login is enabled | Google OAuth client ID. |
| `GOOGLE_CLIENT_SECRET` | If Google login is enabled | Google OAuth client secret. |
| `GOOGLE_REDIRECT_URI` | If Google login is enabled | `https://your-api.vercel.app/api/v1/auth/google/callback`. Must match Google Cloud Console exactly. |
| `RESEND_API_KEY` | If using Resend | Required for verification and password-reset mail. |
| `MAIL_MAILER` | Yes | `resend` or `smtp`. Do not leave as `log` in production. |
| `MAIL_FROM_ADDRESS` | Yes | Verified sender address. |
| `MAIL_FROM_NAME` | Recommended | Sender name. |
| `SANCTUM_TOKEN_EXPIRATION` | Recommended | Token lifetime in minutes, for example `720`. |
| `API_RATE_LIMIT` | Recommended | Default `120`. |
| `AUTH_LOGIN_MAX_ATTEMPTS` | Recommended | Default `5`. |
| `AUTH_LOGIN_DECAY_SECONDS` | Recommended | Default `60`. |
| `PERMISSION_CACHE_TTL` | Recommended | Default `5`. |
| `APP_SEED_DEMO_DATA` | Yes | `false` in production. |
| `ADMIN_NAME` | Optional | Used only by seeders. Do not seed a known default password into production. |
| `ADMIN_EMAIL` | Optional | Used only by seeders. |
| `ADMIN_PASSWORD` | Optional | Used only by seeders. Prefer creating the first admin through a controlled process. |
| `BASE_CURRENCY` | Optional | Default `USD`. |

`VIEW_COMPILED_PATH=/tmp/views`, `XDG_CONFIG_HOME=/tmp/caddy/config`, and `XDG_DATA_HOME=/tmp/caddy/data` are already set in `Dockerfile.vercel`. Do not override them with paths inside `/app`; Vercel Functions have a read-only filesystem except `/tmp`.

## Security notes

- `APP_DEBUG=false` is set in the image and must also be set in Vercel.
- `APP_KEY`, database credentials, S3 credentials, Google secrets, and the Resend key are secrets. Store them only in Vercel Environment Variables.
- `.env`, `.env.*`, and `.vercel` are excluded from Git and the Docker build context. `.env.example` is intentionally included and must contain no real secrets.
- `TRUSTED_PROXIES=*` is acceptable only because the container is reached through Vercel's proxy and is not directly exposed. It is required so rate limiting and audit logs see the client IP from `X-Forwarded-For`. Do not use `*` if the container can be reached directly.
- Keep `SESSION_SECURE_COOKIE=true` if cookies are used over HTTPS. The SPA currently uses bearer tokens, not cookie authentication.
- Do not enable demo seeding in production.
- Do not run `php artisan migrate` from the container on every cold start. Run migrations deliberately, after a backup.

## Local container verification

The image was built and exercised locally with a read-only root filesystem and a writable `/tmp`, which mirrors the Vercel constraint:

- `docker build -f Dockerfile.vercel -t logistics-vercel-audit .` completes.
- Required PHP extensions are present: `bcmath`, `curl`, `dom`, `fileinfo`, `gd`, `intl`, `mbstring`, `opcache`, `pcntl`, `pdo_mysql`, `simplexml`, `xml`, `zip`.
- `GET /up` returns `200` under `--read-only --tmpfs /tmp`.
- `config('view.compiled')` resolves to `/tmp/views`.
- Caddy autosave and state resolve to `/tmp/caddy/...` with no read-only filesystem errors.
- The production permission cache TTL defaults to 5 minutes and honors `PERMISSION_CACHE_TTL`.
- The full backend test suite passes: 536 tests, 2,268 assertions.

## Deployment-specific code changes

- `AppServiceProvider` now sets `permission.cache.expiration_time` before Spatie's registrar resolves. This replaces a call to a method removed in `spatie/laravel-permission` 8.3.0 and fixes a production-only `package:discover` failure during the Docker build.
- `PermissionCatalog` resolves the role guard through Spatie's `Guard::getDefaultName(User::class)` instead of reading `auth.defaults.guard` directly. Laravel 13 writes Sanctum's request guard into that config key, which made role validation reject the seeded `web` roles after authentication.
- Dashboard previews pass their injected clock into `Shipment::isLate()`, so `is_late` agrees with the late query instead of using the wall clock.
- `Dockerfile.vercel` redirects Laravel compiled views and Caddy state to `/tmp`, and sets PHP upload limits that fit Vercel's request cap.

## Preview deployment

1. Confirm `Dockerfile.vercel`, `Caddyfile`, `vercel.json`, `.dockerignore`, and the S3 dependency changes are committed in the backend repository.
2. Create or link the Vercel project to the backend repository.
3. Set the Vercel project Root Directory to the backend repository root.
4. Add Preview environment variables. Use a separate preview database or a read-only/staging database. Do not point Preview at production data.
5. Deploy a preview from the Vercel dashboard or with:
   ```bash
   vercel
   ```
6. Open the preview URL and verify `GET /up` and `GET /api/v1/ping`.
7. Run the preview testing checklist below.

## Production deployment

Do not execute these steps until the preview is approved.

1. Back up the production database and the current document storage.
2. Confirm all Production environment variables are set in Vercel.
3. Confirm `APP_DEBUG=false`, `APP_ENV=production`, `APP_URL`, `FRONTEND_URL`, and `DOCUMENTS_DISK=s3`.
4. Run migrations from a controlled environment with network access to the production database, after the backup:
   ```bash
   php artisan migrate --force
   ```
5. Seed roles and permissions only if required by the deployment process. Do not seed demo data.
6. Deploy the backend to Vercel.
7. Verify `GET /up`, `GET /api/v1/ping`, login, a protected read, a document upload, and a document download.
8. Update the Cloudflare Pages build environment variable and redeploy the frontend.

## Connecting the Cloudflare Pages frontend

In the Cloudflare Pages project settings, set:

```text
VITE_API_BASE_URL=https://your-api.vercel.app/api/v1
```

Then redeploy the frontend. Vite embeds this value at build time, so changing it without a rebuild has no effect.

Confirm the backend has:

```text
FRONTEND_URL=https://your-app.pages.dev
```

No trailing slash. The value must match the browser `Origin` header exactly.

If Google sign-in is enabled, add this authorized redirect URI in Google Cloud Console:

```text
https://your-api.vercel.app/api/v1/auth/google/callback
```

The frontend already uses `frontend/public/_redirects` with `/* /index.html 200` for SPA routing.

## Database backup and migration

Take a backup before any schema change:

```bash
mysqldump --single-transaction --routines --triggers \
  -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p \
  "$DB_DATABASE" > logistics-backup-$(date +%Y%m%d-%H%M%S).sql
```

Restore only after an approved rollback decision:

```bash
mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p "$DB_DATABASE" < logistics-backup.sql
```

Migrations are not run by `Dockerfile.vercel`. Run them from a trusted machine or CI job that can reach the database. Do not run them automatically on every container start.

## File storage and the 4.5 MB limit

Vercel Functions limit request and response bodies to 4.5 MB. This is a platform limit that applies before Laravel sees the request.

Current document behavior:

- Uploads go through `POST /api/v1/documents`.
- Downloads go through `GET /api/v1/documents/{id}/download`.
- Downloads are authorized by the `document.download` permission and streamed by Laravel.
- Files are stored on the disk named in `DOCUMENTS_DISK`.
- The application's configured upload limit is 20 MB, which is larger than Vercel's 4.5 MB limit.
- The Vercel image sets PHP `upload_max_filesize=4M` and `post_max_size=5M`, so PHP does not reject files well below the platform limit at the old 2 MB default.

Consequences on Vercel:

- Uploads larger than 4 MB fail at PHP with an upload-size error.
- Uploads larger than 4.5 MB fail with `413 FUNCTION_PAYLOAD_TOO_LARGE` before PHP sees them.
- Large downloads may fail with `FUNCTION_RESPONSE_PAYLOAD_TOO_LARGE` if the response is not streamed past the limit.
- Local document storage is ephemeral and is lost on scale-in or redeploy.

Safe path without changing upload behavior:

- Set `DOCUMENTS_DISK=s3` and configure the S3-compatible bucket.
- Keep uploads under 4 MB until a direct-to-storage flow is approved.
- Test a download larger than 4.5 MB on the preview deployment.

Changes that require explicit approval because they alter existing upload/download behavior:

- Direct-to-S3 pre-signed uploads from the browser.
- Pre-signed S3 download URLs instead of the authorized API stream.
- Lowering the application upload limit below 4.5 MB.
- Moving document traffic to a non-Vercel host.

Existing documents stored on a local disk must be copied to the S3 bucket and their `document_versions.disk` and `path` values reconciled before switching `DOCUMENTS_DISK` in production. Do not run that data migration without a backup and approval.

## Preview testing checklist

- [ ] `GET /up` returns a healthy response.
- [ ] `GET /api/v1/ping` returns the API envelope.
- [ ] `POST /api/v1/auth/login` works with a preview test account.
- [ ] A protected endpoint returns `401` without a token and `200` with one.
- [ ] Role and permission checks still return `403` where expected.
- [ ] CORS preflight from the Cloudflare Pages origin succeeds.
- [ ] Password reset and email verification links point at the Vercel API URL.
- [ ] Google sign-in callback returns to the Cloudflare frontend (if enabled).
- [ ] Document upload under 4 MB succeeds.
- [ ] Document download succeeds and preserves the original filename.
- [ ] A document larger than 4 MB is tested to confirm the platform limit and the chosen workaround.
- [ ] Database sessions and database cache work after a cold start.
- [ ] Vercel logs show no filesystem write errors.
- [ ] Vercel logs show no missing extension or database connection errors.

## Production deployment checklist

- [ ] Production database backup completed and verified.
- [ ] Document storage backup or S3 replication completed.
- [ ] All Production environment variables set.
- [ ] `APP_DEBUG=false` and `APP_ENV=production`.
- [ ] `APP_KEY` is set and backed up securely.
- [ ] `APP_URL` and `FRONTEND_URL` are the final HTTPS origins.
- [ ] `DOCUMENTS_DISK=s3` and the bucket is private.
- [ ] `TRUSTED_PROXIES=*` is set with the Vercel-only exposure assumption documented.
- [ ] Migrations reviewed and ready; not yet executed.
- [ ] Preview deployment approved.
- [ ] Cloudflare Pages `VITE_API_BASE_URL` updated and redeployed.
- [ ] Post-deployment smoke tests completed.
- [ ] Rollback plan and previous deployment URL recorded.

## Known deployment blockers

1. The Vercel container filesystem is read-only except `/tmp`. This guide sets `VIEW_COMPILED_PATH=/tmp/views`; local document storage is still unsupported.
2. Vercel's 4.5 MB request/response limit conflicts with the current 20 MB document upload limit. The image caps PHP uploads at 4 MB to fail predictably, but this still reduces the usable upload size on Vercel.
3. The backend repository currently has uncommitted deployment files and dependency changes. Vercel builds from Git, so those must be committed before deployment.
4. The backend repository has no committed Vercel configuration yet (`Dockerfile.vercel`, `Caddyfile`, `vercel.json`, `.dockerignore` are untracked).
5. Existing local document files cannot be served from Vercel unless they are copied to durable object storage.
6. This setup has no queue worker or scheduler on Vercel. `QUEUE_CONNECTION=sync` is intentional; any future queued work needs an external worker or a different host.

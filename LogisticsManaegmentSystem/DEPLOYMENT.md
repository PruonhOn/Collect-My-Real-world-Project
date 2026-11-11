# Production Deployment Guide

This guide outlines how to deploy the **Logistics Management System** with separate hosting:
- **Backend (Laravel API):** Render
- **Frontend (React + Vite SPA):** Cloudflare Pages
- **Database:** MySQL (Production Database)

> For the Vercel container deployment, see [`backend/VERCEL_DEPLOYMENT.md`](backend/VERCEL_DEPLOYMENT.md). The Render instructions below remain valid for Render.

---

## 1. Local Development Configuration

### Backend (`/backend`)
1. Copy `.env.example` to `.env`:
   ```bash
   cp .env.example .env
   ```
2. Generate application key:
   ```bash
   php artisan key:generate
   ```
3. Run migrations and seeders:
   ```bash
   php artisan migrate --seed
   ```
4. Start the Laravel development server:
   ```bash
   php artisan serve
   ```

### Frontend (`/frontend`)
1. Install dependencies:
   ```bash
   npm install
   ```
2. Start the Vite dev server (proxies `/api` to `http://127.0.0.1:8000`):
   ```bash
   npm run dev
   ```

---

## 2. Backend Deployment (Render)

Deploy the Laravel backend as a **Web Service** on Render using Docker (`docker/php/Dockerfile`).

### Render Service Settings:
- **Environment:** `Docker`
- **Build Command:** (Handled by Dockerfile)
- **Start Command:** (Or default Docker command via entrypoint)

### Required Environment Variables in Render Dashboard:
| Variable | Value / Description |
| :--- | :--- |
| `APP_NAME` | `Logistics ERP` |
| `APP_ENV` | `production` |
| `APP_KEY` | `base64:...` (Generate using `php artisan key:generate --show`) |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://your-backend-service.onrender.com` |
| `FRONTEND_URL` | `https://your-frontend-app.pages.dev` |
| `DB_CONNECTION` | `mysql` |
| `DB_HOST` | Production MySQL host |
| `DB_PORT` | `3306` |
| `DB_DATABASE` | Production database name |
| `DB_USERNAME` | Production database username |
| `DB_PASSWORD` | Production database password |
| `SESSION_DRIVER` | `database` (or `cookie`) |
| `QUEUE_CONNECTION` | `database` (or `redis`) |
| `CACHE_STORE` | `database` (or `redis`) |
| `MAIL_MAILER` | `resend` (or `smtp`) |
| `RESEND_API_KEY` | Your Resend API key (if using Resend) |
| `FILESYSTEM_DISK` | `local` (or `s3` if using AWS S3 for persistent uploads) |

---

## 3. Frontend Deployment (Cloudflare Pages)

Deploy the React + Vite SPA to **Cloudflare Pages**.

### Cloudflare Pages Settings:
- **Framework Preset:** `Vite`
- **Build Command:** `npm run build`
- **Build Output Directory:** `dist`
- **Root Directory:** `frontend`

### Required Environment Variables in Cloudflare Pages Dashboard:
| Variable | Value / Description |
| :--- | :--- |
| `VITE_API_BASE_URL` | `https://your-backend-service.onrender.com/api/v1` |

*(Note: Never put private secrets in `VITE_` environment variables as they are exposed publicly in frontend client bundles).*

---

## 4. Production Database Setup
1. Provision a production MySQL database (e.g., Render Managed MySQL, AWS RDS, PlanetScale, or DigitalOcean MySQL).
2. Run database migrations on deployment:
   ```bash
   php artisan migrate --force
   ```
3. Seed roles and permissions (if not seeded automatically):
   ```bash
   php artisan db:seed --class=RolePermissionSeeder --force
   ```

---

## 5. Build and Deployment Commands

### Backend Build (Docker)
Docker builds automatically via Render using `docker/php/Dockerfile`. During container startup, `entrypoint.sh` executes:
- `php artisan config:cache`
- `php artisan route:cache`
- `php artisan view:cache`
- `php artisan migrate --force`

### Frontend Build (Cloudflare Pages)
Cloudflare Pages executes:
```bash
npm install && npm run build
```

---

## 6. Post-Deployment Testing Checklist
- [ ] Visit `https://your-frontend-app.pages.dev`.
- [ ] Test user registration (`POST /api/v1/auth/register`) and login (`POST /api/v1/auth/login`).
- [ ] Verify API requests successfully reach Render without CORS errors.
- [ ] Test protected routes and role/permission guards.
- [ ] Test document upload and download functionality.
- [ ] Check Render logs (`https://dashboard.render.com`) for any unhandled exceptions or 500 errors.

---

## 7. Troubleshooting

- **CORS Errors:**
  - Verify `FRONTEND_URL` in Render matches your exact Cloudflare Pages domain (e.g. `https://your-app.pages.dev` with no trailing slash).
  - Verify `supports_credentials => true` in `config/cors.php`.
- **404 Errors on Frontend Refresh:**
  - Ensure `frontend/public/_redirects` contains `/* /index.html 200` so Cloudflare Pages correctly routes SPA paths.
- **401 / 419 Authentication Errors:**
  - Ensure Authorization Bearer token is correctly sent in Axios headers (`api.ts`).
- **500 Internal Server Error:**
  - Check Render logs (`docker logs` or Render dashboard logs) for exception traces.
  - Verify `APP_KEY` is set in Render.
- **Database Connection Errors:**
  - Verify MySQL host, port, username, password, and database name environment variables in Render. Ensure database allows external connections from Render's IP range.

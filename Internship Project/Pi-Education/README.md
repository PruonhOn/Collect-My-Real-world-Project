# Pi-Education

Full-stack multilingual online education & digital library platform (Khmer / English / Chinese / French).

- **Backend:** Laravel REST API (PHP 8.4, MySQL, Sanctum auth)
- **Frontend:** React 18 + Vite SPA (React Router, TanStack Query, Tailwind)

## Features

- **Auth & Profiles** — register, login, logout, profile & avatar upload & password management (Sanctum bearer tokens, throttled login/register)
- **Catalog & Discovery** — multilingual books, categories, authors; search, type/category/author/language/price/rating filters; sorts (newest, popular, top-rated, most-read, price); reviews & average ratings
- **Reader** — free & purchased reading with saved reading progress (N+1-free)
- **VIP subscriptions** — plan catalog with bulk `vip_until` extension, `vip/status`
- **Orders & payments** — cart-style orders (subtotal + 10% tax), mock payment checkout, idempotent pay (no duplicate access grants)
- **Reviews & ratings** — moderated by admins; pending reviews hidden until approved; one review per user/book
- **Notifications** — created automatically on paid orders, VIP subscriptions, and review moderation; unread badge, bell dropdown, "mark all read"
- **Wishlist** — add/remove books; wishlist-only browsing filter
- **Admin panel** — dashboard stats, user management (role/status/VIP), book CRUD with per-locale translations, categories, authors, VIP plans, review moderation
- **i18n** — km / en / zh / fr via `X-Locale` header (backend) and React i18n context (frontend)
- **Security** — Laravel Policies + Gates on resources, API rate limiting, uniform error envelope, CORS, validated file uploads

## Project structure

```
backend/    Laravel REST API
frontend/   React + Vite SPA
```

## Requirements

- PHP >= 8.3, Composer
- Node.js >= 20, npm
- MySQL 8.x
- Laragon recommended on Windows

## Getting started

### 1. Backend

```bash
cd backend
composer install
copy .env.example .env        # edit DB credentials
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Backend runs at `http://127.0.0.1:8000`. API base path is `/api/v1`.

### 2. Frontend

```bash
cd frontend
npm install
npm run dev
```

Frontend runs at `http://localhost:5173` (Vite proxies `/api` to the backend).

## Seeded data

| Role       | Email                  | Password  |
|------------|------------------------|-----------|
| Admin      | admin@pieducation.test | admin1234 |

Seeds also include 6 books, categories, authors, and VIP plans (all with km/en/zh/fr translations).

## Testing

```bash
cd backend
php artisan test              # full suite (106 tests)
vendor/bin/pint               # code style

cd frontend
npm run build
npm run lint
```

## Deployment

See [`backend/docs/deployment.md`](backend/docs/deployment.md) for production environment
configuration, storage, queues, cache, deployment, backup, logging, and error-monitoring
strategy.

## API overview

- `GET /books` — list + filters (`search`, `type`, `category`, `author`, `language`, `min_price`, `max_price`, `min_rating`, `sort`, `paginate`)
- `GET /books/{slug}` — detail (includes `avg_rating`, `reviews_count`, `has_access`/`is_wishlisted` when authenticated)
- `POST /auth/register`, `POST /auth/login` (both throttled)
- Authenticated: `vip/plans|status|subscribe`, `orders*`, `reviews*`, `notifications*`, `wishlist*`, `profile*`, `my-books`, `read/{slug}/progress`
- Admin (`role:admin`): `admin/dashboard`, `admin/users`, `admin/books`, `admin/categories`, `admin/authors`, `admin/vip/plans`, `admin/reviews`

## Notes

- Payments use the built-in **mock** provider. Real providers (ABA, Bakong, ACLEDA, Wing) require official gateway documentation before integration.
- Responses follow `{ success, message, data }` (and `{ success, message, errors }` on errors) for all API responses, including framework-level 404/403/401/429/500 cases.
- Pass `X-Locale` (`km|en|zh|fr`) to receive localized content.
- API requests are rate-limited (`API_RATE_LIMIT`, default 60/min per user/IP); login/register are throttled separately.
- Protected book content is stored in the database and served only via the authorized `/read/{slug}` endpoint; it is never exposed as a public file URL.

## License

Proprietary — Pi-Education.
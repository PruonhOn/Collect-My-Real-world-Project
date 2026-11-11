# My Business

A multi-tenant SaaS platform for small and medium businesses: products,
customers, orders, invoices, branches, reports, team management, plan
entitlements and an external API — with a Laravel 13 JSON API and a React 19
single-page application.

<!-- Logo: frontend/src/assets/logo.svg -->

## Contents

- [What is inside](#what-is-inside)
- [Requirements](#requirements)
- [Quick start](#quick-start)
- [Tests and checks](#tests-and-checks)
- [Project layout](#project-layout)
- [The domain in one page](#the-domain-in-one-page)
- [API](#api)
- [Configuration](#configuration)
- [Documentation](#documentation)
- [Roadmap](#roadmap)

## What is inside

**Backend** — Laravel 13 / PHP 8.4, JSON API only.

- Sanctum authentication: register, login, logout, password reset, email
  verification, business invitations.
- Multi-tenancy with a global `business_id` scope, a tenant middleware and
  per-membership roles (owner, admin, staff).
- Business modules: products, customers, orders, invoices, branches, activity
  log.
- Data-driven entitlements: plans, features and the plan-feature matrix live in
  the database and are resolved by a single `FeatureGate` service that fails
  closed.
- Billing: checkout, gateway confirmation, webhooks with idempotency, trial and
  grace-period handling, cancellation and resumption, a full subscription audit
  log.
- Payment gateways: `fake` (local development and tests), Stripe, Bakong, ABA.
- Platform administration: users, businesses, plans, subscriptions, payments,
  impersonation — all audited.
- External API with revocable, hashed, ability-scoped tokens.

**Frontend** — React 19, Vite 8, Tailwind CSS 4, React Router 7.

- Auth screens, a full workspace (dashboard, products, customers, orders,
  invoices, team, branches, reports, billing, API tokens, settings) and the
  platform administration console.
- Feature gating that mirrors the server, plan usage counters, workspace
  switching, and a shared UI kit.

## Requirements

| Tool | Version |
| --- | --- |
| PHP | 8.4 (`pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `curl`, `zip`, `gd`, `intl`) |
| Composer | 2.x |
| MySQL | 8.0+ |
| Node.js | 20+ (22 or 24 recommended) |

## Quick start

### 1. Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

Create the database, then edit `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=my_business
DB_USERNAME=root
DB_PASSWORD=secret

APP_URL=http://localhost:8100
APP_FRONTEND_URL=http://localhost:5173
```

Create the schema and the catalogue:

```bash
php artisan migrate
php artisan db:seed          # plans, features, coupons
php artisan storage:link
```

Start the API:

```bash
php artisan serve --port=8100
```

The API answers on `http://127.0.0.1:8100/api/v1`. The health endpoint is
`GET /up`.

### 2. Frontend

```bash
cd frontend
npm install
cp .env.example .env
npm run dev
```

The SPA runs on `http://localhost:5173` and proxies `/api` to
`VITE_API_PROXY_TARGET` (`http://127.0.0.1:8100` by default), so there is no
cross-origin call during development.

### 3. Sign in

1. Open `http://localhost:5173/register` and create an account.
2. Create a business (the account becomes its owner and receives the free
   plan).
3. Products, customers and orders are available immediately; branches, reports
   and API tokens require a Pro plan — the interface shows what is locked.

To exercise billing without a gateway account, keep
`BILLING_DEFAULT_GATEWAY=fake` and use the local fake gateway callback
`POST /api/v1/webhooks/fake` with the header `X-Fake-Signature:
fake-webhook-secret`.

## Tests and checks

```bash
# Backend — MySQL
cd backend
php artisan test

# Backend — SQLite
$env:DB_CONNECTION='sqlite'
$env:DB_DATABASE='database/testing.sqlite'
php artisan test

# Frontend
cd frontend
npm run lint
npm run build
```

Current status: **122 backend tests / 379 assertions** green on both SQLite and
MySQL 8, `npm run lint` with 0 errors, `npm run build` successful.

## Project layout

```
.
├── backend/                 Laravel 13 API
│   ├── app/
│   │   ├── Http/Controllers/Api/      Auth, business, billing, admin, external
│   │   ├── Http/Middleware/           Tenant, feature, super-admin, API token
│   │   ├── Models/                    Tenant models with the BusinessScope trait
│   │   ├── Scopes/                    BusinessScope
│   │   ├── Services/
│   │   │   ├── Billing/               Subscription, checkout, payment
│   │   │   ├── Features/              FeatureGate
│   │   │   └── Payments/Gateways/     Fake, Stripe, Bakong, ABA
│   │   └── Jobs/                      Queued mail, invoices, alerts
│   ├── database/migrations/           One migration per table
│   ├── database/seeders/              PlanSeeder, CouponSeeder
│   ├── routes/                        api.php, console.php
│   └── tests/Feature/                 Authentication, tenancy, billing, tokens
├── frontend/                React 19 SPA (Vite)
│   └── src/
│       ├── api/            Axios client, interceptors, error helpers
│       ├── assets/         Brand assets
│       ├── components/     FeatureGate, PageHeader, ui/ kit
│       ├── context/        AuthContext, BusinessContext
│       ├── hooks/          useApi
│       ├── layouts/        Auth, business, admin shells
│       ├── pages/          auth/ business/ admin/
│       ├── routes/         Guards
│       ├── services/       One module per API group
│       └── utils/          Formatting, validation, constants
└── docs/                   Architecture, database, security, billing, API, ops
```

## The domain in one page

- A **user** owns one or more **businesses** and can be a **member** of others
  with a role: `owner`, `admin` or `staff`.
- A **business** has **branches** (Pro), **members**, **products**, **customers**,
  **orders** and **invoices**.
- An **order** references products and a customer; the totals are computed on
  the server and the stock is moved with a written **stock movement**.
- An **invoice** is a customer invoice with a PDF document.
- A **subscription** binds a business to a **plan**. The plan carries a set of
  **features** (capabilities and limits). `FeatureGate` resolves the effective
  features from the plan, the subscription status and the billing period.
- A **payment** is created by a checkout, confirmed by the gateway, and then
  activates the subscription. Webhook deliveries are idempotent.
- An **API token** belongs to a user and to a business, carries abilities and
  can be revoked or given an expiry date.

Entitlement rules in one sentence: **the active plan and the subscription state
decide everything, and an unresolvable subscription grants nothing.**

## API

Base URL `/api/v1`. Browser clients authenticate with a Sanctum bearer token and
send `X-Business-Id` to select the tenant. External clients send an API token
with per-endpoint abilities.

```bash
# List the public plan catalogue
curl http://127.0.0.1:8100/api/v1/plans

# Authenticate
curl -X POST http://127.0.0.1:8100/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"you@example.com","password":"secret","device_name":"cli"}'
```

Full reference, including request and response payloads for every endpoint:
[`docs/api.md`](docs/api.md).

## Configuration

Backend — `backend/.env`:

| Variable | Purpose |
| --- | --- |
| `APP_URL` | Public URL of the API |
| `APP_FRONTEND_URL` | Public URL of the SPA; builds reset, invitation and payment return links |
| `DB_*` | Database connection |
| `MAIL_*` | Mail delivery |
| `BILLING_DEFAULT_GATEWAY` | `fake`, `stripe`, `bakong` or `aba` |
| `BILLING_TRIAL_DAYS` | Length of the trial offered by the Pro plans |
| `BILLING_GRACE_PERIOD_DAYS` | Days a `past_due` subscription keeps its access |
| `STRIPE_*`, `BAKONG_*`, `ABA_*` | Gateway credentials |

Frontend — `frontend/.env`:

| Variable | Purpose |
| --- | --- |
| `VITE_API_URL` | API base URL (`/api/v1` behind the proxy) |
| `VITE_API_PROXY_TARGET` | Backend origin used by the Vite dev proxy |
| `VITE_APP_NAME` | Product name shown in the shell |

`VITE_*` values are baked into the bundle at build time.

## Documentation

| Document | Content |
| --- | --- |
| [`docs/architecture.md`](docs/architecture.md) | Layers, tenancy model, request lifecycle, money and date rules |
| [`docs/database.md`](docs/database.md) | Every table, its columns, the conventions, the migrations |
| [`docs/security.md`](docs/security.md) | Threat model, authentication, authorisation, hardening checklist |
| [`docs/billing.md`](docs/billing.md) | Plans, the feature matrix, the subscription state machine, the gateways |
| [`docs/api.md`](docs/api.md) | Every endpoint with its payloads and error codes |
| [`docs/deployment.md`](docs/deployment.md) | Installation, web server, queue, scheduler, release procedure |
| [`docs/backup-recovery.md`](docs/backup-recovery.md) | Backup strategy, restore procedure, disaster scenarios |
| [`docs/implementation-plan.md`](docs/implementation-plan.md) | Delivery status per phase |
| [`docs/acceptance-criteria.md`](docs/acceptance-criteria.md) | Every requirement with its verification evidence |

## Roadmap

- Coupon redemption applied at checkout.
- CI pipeline running the backend suite and the frontend checks on every push.
- End-to-end browser tests for the critical paths.
- Ownership transfer for a business.
- Sanctum SPA cookie mode so the session token is not kept in `localStorage`.

# Architecture

## 1. Overview

My Business is a two-tier application:

- **backend/** — Laravel 13 JSON API. It owns authentication, tenancy,
  authorisation, money arithmetic, entitlement resolution and every persistence
  decision.
- **frontend/** — React 19 single page application built with Vite 8. It owns
  presentation, form validation ergonomics and client-side navigation only. It
  never computes money, never decides entitlements and never trusts its own
  state for a security decision.

```
Browser
  │  Bearer token + X-Business-Id
  ▼
Vite dev proxy / Nginx  ──►  Laravel API (/api/v1)
                                 │
                                 ├── EnsureBusinessContext   (tenant resolution)
                                 ├── EnsureFeatureEnabled    (entitlements)
                                 ├── SuperAdminMiddleware   (platform admin)
                                 ├── Controllers
                                 ├── Services (tenancy, billing, features, payments, pdf, api)
                                 └── Eloquent models + global scopes → MySQL 8
```

## 2. Backend layers

| Layer | Location | Responsibility |
| --- | --- | --- |
| HTTP | `app/Http/Controllers` | Request validation, response shaping, HTTP status codes |
| HTTP | `app/Http/Middleware` | Tenant context, entitlements, admin gate, API tokens |
| HTTP | `app/Http/Requests` | Reusable form requests where validation is shared |
| Domain services | `app/Services` | Business rules that must not live in a controller |
| Persistence | `app/Models`, `app/Scopes` | Data mapping and automatic tenant scoping |
| Jobs / commands | `app/Jobs`, `app/Console/Commands` | Deferred and scheduled work |

### Key services

| Service | Responsibility |
| --- | --- |
| `Tenancy\BusinessContextService` | Resolves the active business, asserts membership, binds `current.business` |
| `Features\FeatureGate` | Single authoritative source of entitlements, limits and usage counters |
| `Billing\BillingService` | Pending charges, invoices, checkout confirmation, plan activation |
| `Billing\SubscriptionService` | Subscription state machine (trialing → active → past_due → canceled → expired) |
| `Payments\PaymentGatewayManager` | Gateway registry, configuration checks, gateway resolution |
| `Payments\Gateways\*` | `StripeGateway`, `BakongKhqrGateway`, `AbaPaywayGateway`, `FakeGateway` |
| `Api\ApiTokenService` | Issues/revokes revocable business-scoped tokens with abilities |
| `Pdf\InvoicePdfService` | Invoice PDF rendering |
| `OrderService` | Order creation, totals, stock movements |

## 3. Request lifecycle (business workspace)

1. `auth:sanctum` verifies the bearer token and resolves the user.
2. `business.context` (`EnsureBusinessContext`) resolves the tenant:
   - the header `X-Business-Id` (or the `business_id` query/body parameter) is
     read and validated against an **active membership**; ownership always
     grants access;
   - the resolved business is bound as `app('current.business')`.
3. `feature.enabled:<key>` (`EnsureFeatureEnabled`) rejects the request with
   `403` and `upgrade_required: true` when the plan does not include the
   feature.
4. The controller validates input, delegates to a service and returns JSON.
5. Eloquent global scopes add `business_id = <current business>` to every
   business-scoped query, so a forgotten `where` clause cannot leak data.

Controllers never query a business table without the scope, and services use
`withoutGlobalScopes()` **only** in explicitly cross-tenant administrative
code paths.

## 4. Multi-tenancy model

- A `users` row may own several `businesses` and may be a member of several
  others.
- `business_memberships` carries the role (`owner`, `admin`, `staff`) and the
  lifecycle status (`invited`, `active`, `revoked`).
- Every tenant-owned table has a `business_id` foreign key with an index.
- `App\Scopes\BusinessScope` is applied to the tenant-owned models. The scope
  resolves the tenant from `current.business` and adds the constraint.
- Queued jobs, console commands and scheduled tasks use
  `BusinessContextService::runIn($business, fn () => ...)` to bind a tenant
  explicitly, so background work is scoped exactly like HTTP work.

Roles:

| Role | May do |
| --- | --- |
| `owner` | Everything, including ownership, subscription and member management |
| `admin` | Manage catalogue, customers, orders, invoices, team members and settings |
| `staff` | Day-to-day operations; no member, billing or settings administration |

## 5. Money, tax and totals

- All monetary columns are `unsignedBigInteger` minor units.
- `OrderService` is the only place that computes an order total: it derives
  line totals from the stored unit prices, applies the discount and tax, and
  writes the resulting `subtotal`, `tax`, `discount` and `total`.
- The API returns the stored values; the SPA formats them with `Intl.NumberFormat`.
- A product may carry `cost_price`. When at least one product has cost data,
  the profit report is available; otherwise the API returns
  `profit_available: false` and a `null` profit instead of a misleading `0`.

## 6. Frontend architecture

```
frontend/src/
├── api/client.js          axios instance, interceptors, error helpers, file download
├── components/ui/         Button, Card, Form, Badge, Table, Modal, Feedback
├── components/            FeatureGate, PageHeader
├── context/               AuthContext, BusinessContext
├── hooks/                 useApi, useDebouncedValue
├── layouts/               AuthLayout, BusinessLayout, AdminLayout
├── pages/auth/            Login, Register, ForgotPassword, ResetPassword,
│                          VerifyEmail, AcceptInvitation
├── pages/business/        Dashboard, Products, Customers, Orders, Invoices,
│                          Branches, Team, Reports, ApiTokens, Billing, Settings
├── pages/admin/           Dashboard, Users, Businesses, Plans, Subscriptions, Payments
├── routes/guards.jsx      ProtectedRoute, BusinessRoute, AdminRoute
├── services/              authService, businessService, adminService
├── utils/                 constants, format helpers
├── App.jsx                route table
└── main.jsx               providers + router
```

- **State**: `AuthContext` owns the session, `BusinessContext` owns the list of
  businesses, the active business and the entitlement snapshot. Both are
  derived from the API and never persist entitlement decisions.
- **Data fetching**: `useApi(loader, deps)` returns `{ data, loading, error,
  reload }` and re-runs whenever the serialised dependency list changes.
- **Entitlements**: `BusinessContext.allows('multi_branch')` mirrors the
  backend answer for presentation only. The backend remains authoritative; a
  locked button is a courtesy, not a control.
- **Forms**: `react-hook-form` + `zod` with `@hookform/resolvers`. The same
  rules as the API are re-used where the API exposes them.
- **Styling**: Tailwind CSS 4 with a small set of primitives in
  `components/ui`, so the pages stay free of duplicated markup.

### API client conventions

| Concern | Implementation |
| --- | --- |
| Base URL | `VITE_API_URL`, default `/api/v1` |
| Auth header | `Authorization: Bearer <token>` from `localStorage["my_business.token"]` |
| Tenant header | `X-Business-Id: <id>` from `localStorage["my_business.business_id"]` |
| 401 handling | The interceptor clears the session and triggers the auth provider |
| Errors | `apiErrorMessage(error)` (first validation message or `message`) and `apiFieldErrors(error)` (per-field map) |
| Upgrades | `isUpgradeRequired(error)` is true for `402` or `upgrade_required` payloads |
| Downloads | `downloadFile(url, filename, params)` streams CSV/XLSX/PDF through the authenticated client |

## 7. Cross-cutting concerns

- **Activity log**: business mutations write to `business_activity_logs`.
- **Audit log**: platform admin mutations write to `admin_audit_logs` with the
  actor, the action, the reason and the before/after values.
- **Idempotency**: checkout and webhook handling are idempotent; a repeated
  webhook event is ignored through `payment_webhook_events`.
- **Scheduled work**: trial expiry, subscription expiry, grace period processing
  and gateway reconciliation run from the scheduler.

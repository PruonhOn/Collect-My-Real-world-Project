# Implementation Plan

Status legend: **Done** = implemented and covered by an automated test or a
build/lint check · **Partial** = implemented, gaps listed · **Todo** = not
started.

## Phase 0 — Foundation

| Item | Status | Evidence |
| --- | --- | --- |
| Laravel 13 project skeleton, PHP 8.4 | Done | `backend/` |
| MySQL 8 configuration, `.env.example` | Done | `backend/.env.example` |
| Sanctum, Spatie permissions, queue/cache/database drivers | Done | `config/sanctum.php`, `config/permission.php` |
| Structured exception handling for the API (no stack traces in responses) | Done | `backend/bootstrap/app.php` |
| Middleware aliases (`business.context`, `feature.enabled`, `super.admin`, `api.token`) | Done | `backend/bootstrap/app.php` |
| Vite 8 + React 19 + Tailwind 4 SPA skeleton | Done | `frontend/` |
| ESLint 10 flat config with the React Hooks compiler rules | Done | `frontend/eslint.config.js` |

## Phase 1 — Identity

| Item | Status | Evidence |
| --- | --- | --- |
| Register / login / logout, token issuing | Done | `tests/Feature/AuthenticationTest.php` |
| `GET /user` with businesses and memberships | Done | `AuthenticationTest` |
| Password reset request and reset | Done | `tests/Feature/PasswordResetTest.php` |
| Email verification (signed URL + resend) | Done | `VerificationController` |
| Business invitations (issue, preview, accept once) | Done | `tests/Feature/InvitationTest.php` |
| Rate limiting on the auth endpoints | Done | `routes/api.php`, `throttle:auth` |

## Phase 2 — Multi-tenancy

| Item | Status | Evidence |
| --- | --- | --- |
| Tenant resolution middleware, ownership + membership | Done | `EnsureBusinessContext`, `BusinessContextService` |
| Global `business_id` scope on every tenant model | Done | `app/Scopes/BusinessScope.php` |
| Cross-tenant access is impossible | Done | `tests/Feature/TenantIsolationTest.php` |
| Business CRUD, switching, profile and workspace settings | Done | `BusinessController`, `tests/Feature/BusinessSettingsTest.php` |
| Team management (invite, role change, revoke) | Done | `TeamController` |

## Phase 3 — Business modules

| Item | Status | Evidence |
| --- | --- | --- |
| Products (CRUD, SKU uniqueness per business, categories) | Done | `BusinessModulesTest` |
| Customers (CRUD, search, pagination) | Done | `BusinessModulesTest` |
| Orders (server-side totals, stock movements, status flow) | Done | `OrderService`, `BusinessModulesTest` |
| Invoices (customer invoices, numbering, PDF) | Done | `InvoiceController`, `InvoicePdfService` |
| Branches (feature gated by `multi_branch`) | Done | `BranchController` |
| Dashboard metrics and plan usage | Done | `BusinessDashboardController` |
| Sales and profit reports with date ranges | Done | `ReportController` |
| CSV / XLSX exports | Done | `ExportController` |
| Activity log | Done | `BusinessActivityLog` |

## Phase 4 — Billing and entitlements

| Item | Status | Evidence |
| --- | --- | --- |
| Plan and feature catalogue seeded from the database | Done | `PlanSeeder`, `config/billing.php` |
| `FeatureGate` with fail-closed behaviour, limits, usage counters, caching | Done | `tests/Feature/FeatureGateTest.php` |
| Checkout → pending payment → provider confirmation → activation | Done | `tests/Feature/BillingTest.php` |
| Webhook idempotency | Done | `payment_webhook_events`, `WebhookController` |
| Subscription state machine with audit log | Done | `SubscriptionService` |
| Cancel / resume | Done | `BillingController::portal` |
| Fake, Stripe, Bakong and ABA gateways | Done | `app/Services/Payments/Gateways` |
| Scheduled expiry, grace period and reconciliation | Done | `routes/console.php` |
| Coupons | Partial | `coupons` + `coupon_redemptions` exist; redemption at checkout is not wired yet |

## Phase 5 — Platform administration

| Item | Status | Evidence |
| --- | --- | --- |
| Admin dashboard (users, MRR, churn, revenue trend) | Done | `AdminDashboardController` |
| User administration with audit log | Done | `Admin\UserController` |
| Business administration, suspension with reason | Done | `Admin\BusinessAdminController` |
| Plan and feature matrix administration | Done | `Admin\PlanController` |
| Subscription overrides with reason | Done | `Admin\SubscriptionController` |
| Payment browser | Done | `Admin\PaymentController` |
| Impersonation with audit trail | Done | `ImpersonationController`, `impersonation_logs` |

## Phase 6 — External API

| Item | Status | Evidence |
| --- | --- | --- |
| Revocable, hashed, business-scoped API tokens with abilities | Done | `ApiTokenService`, `tests/Feature/ApiTokenTest.php` |
| External read/write endpoints with per-ability middleware | Done | `routes/api.php`, `ExternalApiController` |
| Token management screen | Done | `frontend/src/pages/business/ApiTokens.jsx` |

## Phase 7 — Web application

| Item | Status | Evidence |
| --- | --- | --- |
| API client with token/tenant interceptors and error helpers | Done | `frontend/src/api/client.js` |
| Auth context, business context, entitlement snapshot | Done | `frontend/src/context` |
| Shared UI kit (buttons, cards, forms, tables, modals, feedback) | Done | `frontend/src/components/ui` |
| Layouts and route guards | Done | `frontend/src/layouts`, `frontend/src/routes/guards.jsx` |
| Auth screens (login, register, reset, verify, invitation) | Done | `frontend/src/pages/auth` |
| Workspace screens (dashboard, products, customers, orders, invoices, team, branches, reports, billing, API tokens, settings) | Done | `frontend/src/pages/business` |
| Admin screens (overview, users, businesses, plans, subscriptions, payments) | Done | `frontend/src/pages/admin` |
| Lint clean and production build | Done | `npm run lint` (0 errors), `npm run build` |

## Phase 8 — Documentation and release

| Item | Status | Evidence |
| --- | --- | --- |
| Architecture, database, security, billing, API, deployment, backup documentation | Done | `docs/` |
| Root README with the quick start | Done | `README.md` |
| Acceptance criteria with verification evidence | Done | `docs/acceptance-criteria.md` |
| Automated test suite on SQLite and MySQL | Done | 122 tests, 379 assertions |
| CI pipeline | Todo | Not yet configured |
| Translation / localisation | Todo | English only |
| Coupon redemption at checkout | Todo | See phase 4 |

## Verification summary

| Check | Command | Result |
| --- | --- | --- |
| Backend tests (SQLite) | `php artisan test` with `DB_CONNECTION=sqlite` | 122 passed, 379 assertions |
| Backend tests (MySQL 8) | `php artisan test` | 122 passed, 379 assertions |
| Frontend lint | `npm run lint` | 0 errors, 2 advisory fast-refresh warnings |
| Frontend build | `npm run build` | built in ~1.4 s, chunks split (react / charts / forms / vendor) |

## Suggested next steps

1. Wire coupon redemption into the checkout flow and add the coupon screen.
2. Add a CI workflow (backend tests on SQLite and MySQL, frontend lint and build).
3. Add Playwright end-to-end tests for the critical paths (sign up → create
   business → create product → create order → checkout).
4. Add the ownership transfer workflow (mentioned in the team API error
   message).
5. Move the Sanctum token to an HttpOnly cookie when the SPA is served from a
   dedicated domain.

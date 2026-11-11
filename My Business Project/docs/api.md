# API Reference

Base URL: `/api/v1` (the SPA talks to it through `VITE_API_URL`).

## Conventions

**Authentication** — every protected endpoint expects
`Authorization: Bearer <sanctum-token>`.

**Tenant context** — every endpoint under `/business` expects
`X-Business-Id: <business-id>`. The header selects the tenant; it never grants
access. A user without an active membership receives `403`.

**Pagination** — list endpoints return the Laravel paginator shape:

```json
{
  "data": [ ... ],
  "current_page": 1,
  "last_page": 3,
  "per_page": 20,
  "total": 42,
  "from": 1,
  "to": 20
}
```

**Money** — every monetary value is an integer in the minor unit (cents).

**Errors**

```json
{ "message": "The given data was invalid.", "errors": { "email": ["The email has already been taken."] } }
```

| Status | Meaning |
| --- | --- |
| `401` | Missing or invalid token |
| `403` | Authenticated but not allowed (missing membership, suspended business, missing entitlement) |
| `404` | Not found **or** belongs to another tenant |
| `422` | Validation error, plan limit reached, illegal state transition |
| `429` | Rate limited |

A missing entitlement answers `403` with `{"upgrade_required": true}`; a plan
limit answers `422` with `{"upgrade_required": true, "limit": n}`.

---

## 1. Public endpoints

| Method | Path | Purpose |
| --- | --- | --- |
| `POST` | `/auth/register` | Create an account, returns a token |
| `POST` | `/auth/login` | Exchange credentials for a token |
| `GET` | `/plans` | Public plan catalogue |
| `POST` | `/auth/forgot-password` | Send a reset link |
| `POST` | `/auth/reset-password` | Reset the password with a token |
| `GET` | `/email/verify/{id}/{hash}` | Verify an email address |
| `GET` | `/invitations/{token}` | Preview an invitation |
| `POST` | `/webhooks/{stripe,bakong,aba}` | Provider callbacks |
| `POST` | `/webhooks/fake` | Fake gateway callback (local/testing only) |

### `POST /auth/register`

```json
{ "name": "Sokha", "email": "sokha@example.com", "password": "secret-password",
  "password_confirmation": "secret-password", "device_name": "Chrome on Windows" }
```

`201` → `{ "token": "...", "user": { ... } }`

### `POST /auth/login`

```json
{ "email": "sokha@example.com", "password": "secret-password",
  "device_name": "Chrome on Windows" }
```

`200` → `{ "token": "...", "user": { ... } }`

---

## 2. Session endpoints

| Method | Path | Purpose |
| --- | --- | --- |
| `GET` | `/user` | The signed-in user with `ownedBusinesses` and `memberships` |
| `POST` | `/auth/logout` | Revoke the current token |
| `POST` | `/email/verification-notification` | Resend the verification email |
| `POST` | `/invitations/{token}/accept` | Accept an invitation (single use) |

`GET /user` →

```json
{
  "id": 1, "name": "Sokha", "email": "sokha@example.com",
  "email_verified_at": "2026-10-22T09:00:00.000000Z",
  "is_super_admin": false,
  "owned_businesses": [ { "id": 1, "name": "Sokha Store", "slug": "sokha-store" } ],
  "memberships": [ { "id": 1, "business_id": 2, "role": "admin", "status": "active" } ]
}
```

---

## 3. Businesses

| Method | Path | Purpose |
| --- | --- | --- |
| `GET` | `/businesses` | Businesses the user owns or belongs to |
| `POST` | `/businesses` | Create a business (owner + free subscription) |
| `GET` | `/businesses/{business}` | One business (owner/admin) |
| `PUT` | `/businesses/{business}` | Update the profile (owner/admin) |
| `POST` | `/businesses/switch` | Change the active workspace |
| `POST` | `/businesses/{business}/invite` | Invite a member by email |

`POST /businesses`

```json
{ "name": "Sokha Store", "business_type": "retail", "country": "KH",
  "currency": "USD", "timezone": "Asia/Phnom_Penh" }
```

`201` → `{ "data": { "id": 1, "name": "Sokha Store", "slug": "sokha-store-a1b2c3", ... } }`

`POST /businesses/switch` → `{ "business_id": 2 }` → `{ "data": { "id": 2, ... } }`

`PUT /businesses/{business}` accepts `name`, `business_type`, `country`,
`currency` (ISO 4217, 3 characters), `timezone`, `address`, `phone`, `email`,
`website`.

---

## 4. Workspace (`/business`, requires `X-Business-Id`)

| Method | Path | Purpose |
| --- | --- | --- |
| `GET` | `/business/dashboard` | Sales, counts, recent orders, plan usage |
| `GET/POST` | `/business/products` | List / create products |
| `GET/PUT/DELETE` | `/business/products/{product}` | Product maintenance |
| `GET/POST` | `/business/customers` | List / create customers |
| `GET/PUT/DELETE` | `/business/customers/{customer}` | Customer maintenance |
| `GET/POST` | `/business/orders` | List / create orders |
| `GET/PUT/DELETE` | `/business/orders/{order}` | Order maintenance |
| `GET/POST` | `/business/invoices` | List / create customer invoices |
| `GET/PUT/DELETE` | `/business/invoices/{invoice}` | Invoice maintenance |
| `GET` | `/business/invoices/{invoice}/pdf` | Invoice PDF |
| `GET` | `/business/team` | Members, roles and invitation state |
| `POST` | `/business/team` | Invite a member (owner/admin) |
| `PUT/DELETE` | `/business/team/{team}` | Change role / revoke access |
| `GET/POST/PUT/DELETE` | `/business/branches` | Branches — `multi_branch` feature |
| `GET` | `/business/exports` | Export capabilities of the plan |
| `GET` | `/business/exports/{resource}` | CSV/XLSX export (`products`, `customers`, `orders`, `invoices`) |
| `GET` | `/business/reports/sales` | Sales report — `advanced_reports` |
| `GET` | `/business/reports/profit` | Profit report — `profit_reports` |
| `GET` | `/business/billing` | Subscription, plans, gateways, usage |
| `POST` | `/business/billing/checkout` | Start a checkout |
| `POST` | `/business/billing/confirm` | Ask the server to re-verify a payment |
| `POST` | `/business/billing/portal` | Cancel / resume the subscription |
| `GET` | `/business/billing/invoices` | Billing invoices |
| `GET/PUT` | `/business/settings` | Workspace settings (owner/admin to write) |
| `GET/POST/DELETE` | `/business/api-tokens` | API tokens — `api_access` feature |

### `GET /business/dashboard`

```json
{
  "data": {
    "today_sales": 12500, "month_sales": 184000,
    "order_count": 12, "customer_count": 34, "product_count": 21,
    "currency": "USD",
    "recent_orders": [ { "id": 9, "order_number": "ORD-0009", "customer": "Dara",
                         "total": 25000, "status": "completed", "created_at": "..." } ],
    "usage": [ { "key": "max_products", "used": 4, "limit": 50,
                 "remaining": 46, "percentage": 8 } ]
  }
}
```

### `GET /business/billing`

```json
{
  "data": {
    "subscription": {
      "status": "active", "plan": "Pro Monthly", "plan_slug": "pro-monthly",
      "billing_cycle": "monthly", "trial_ends_at": null,
      "starts_at": "2026-10-01T00:00:00.000000Z",
      "ends_at": "2026-11-01T00:00:00.000000Z", "cancel_at_period_end": false
    },
    "plans": [ { "id": 2, "name": "Pro Monthly", "slug": "pro-monthly", "type": "pro",
                  "price_monthly": 2900, "price_yearly": 29000, "currency": "USD",
                  "trial_enabled": true, "trial_days": 14,
                  "features": [ { "key": "api_access", "enabled": true, "limit": null } ] } ],
    "gateways": ["fake", "stripe"],
    "usage": [ { "key": "max_products", "used": 4, "limit": 50, "remaining": 46 } ]
  }
}
```

### `POST /business/billing/checkout`

```json
{ "plan_id": 2, "billing_cycle": "monthly", "gateway": "fake", "coupon_code": "WELCOME" }
```

`201` →

```json
{
  "data": {
    "checkout_url": "https://gateway.test/checkout/ref_123",
    "reference": "ref_123",
    "payment": { "id": 5, "payment_number": "PAY-0005", "amount": 2900,
                 "currency": "USD", "status": "pending", "provider": "fake" },
    "invoice": { "id": 5, "invoice_number": "INV-0005", "total": 2900, "status": "open" }
  }
}
```

### `POST /business/billing/confirm`

```json
{ "reference": "ref_123" }
```

`200` → the updated subscription. The server re-checks the payment with the
gateway; a client can request the confirmation but can never assert it.

### `POST /business/billing/portal`

```json
{ "action": "cancel", "at_period_end": true }
{ "action": "resume" }
```

### `GET/PUT /business/settings`

`GET` →

```json
{
  "data": {
    "business": { "id": 1, "name": "Sokha Store", "currency": "USD",
                  "timezone": "Asia/Phnom_Penh", "email": null, "phone": null,
                  "address": null, "website": null },
    "settings": { "invoice_template": "platform", "tax_rate": 0, "date_format": "Y-m-d",
                  "low_stock_alerts": false, "auto_invoice": false }
  }
}
```

`PUT` accepts `invoice_template` (`platform|branded`), `tax_rate` (basis
points), `date_format` (`Y-m-d|d/m/Y|m/d/Y`), `low_stock_alerts`, `auto_invoice`.
Only the owner and business administrators may write.

### `GET /business/api-tokens`

```json
{ "data": [ { "id": 3, "name": "Accounting", "abilities": ["orders:read"],
              "is_revoked": false, "expires_at": null, "last_used_at": null,
              "created_at": "...", "user": { "id": 1, "name": "Sokha", "email": "..." } } ],
  "available_abilities": ["products:read", "products:write", "customers:read",
                           "customers:write", "orders:read", "orders:write"] }
```

`POST /business/api-tokens` → `201` →

```json
{ "message": "The token is shown once. Store it somewhere safe.",
  "data": { "token": { "id": 3, "name": "Accounting", ... } },
  "token": "the-plain-text-secret" }
```

### `GET /business/exports`

```json
{ "data": { "resources": ["products", "customers", "orders", "invoices"],
            "formats": ["csv", "xlsx"], "can_export_excel": true, "max_rows": 5000 } }
```

`GET /business/exports/{resource}?format=csv|xlsx&limit=1000` streams the file.

### `GET /business/reports/sales?from=&to=`

```json
{ "data": { "from": "2026-10-01", "to": "2026-10-31", "currency": "USD",
            "totals": { "orders": 12, "sales": 184000, "discount": 5000, "tax": 9200 },
            "daily": [ { "date": "2026-10-02", "orders": 2, "sales": 25000 } ],
            "by_status": [ { "status": "completed", "orders": 9, "sales": 150000 } ] } }
```

### `GET /business/reports/profit?from=&to=`

```json
{ "data": { "from": "2026-10-01", "to": "2026-10-31", "currency": "USD",
            "profit_available": true, "profit": 41200, "revenue": 184000,
            "by_product": [ { "name": "Coffee", "quantity": 120, "revenue": 60000, "profit": 18000 } ],
            "best_sellers": [ { "name": "Coffee", "quantity": 120, "revenue": 60000 } ] } }
```

`profit_available` is `false` when no product carries cost data; `profit` is
then `null` instead of a misleading `0`.

---

## 5. Platform admin (`/admin`, requires `is_super_admin`)

| Method | Path | Purpose |
| --- | --- | --- |
| `GET` | `/admin/dashboard` | Users, businesses, MRR, churn, revenue trend |
| `GET` | `/admin/users` | Paginated users (`search`, `status`) |
| `GET/PUT/DELETE` | `/admin/users/{user}` | User maintenance, super-admin rights |
| `GET` | `/admin/businesses` | Paginated businesses (`search`, `status`) |
| `GET/PUT/DELETE` | `/admin/businesses/{business}` | Business maintenance |
| `POST` | `/admin/businesses/{business}/suspend` | Suspend with a reason |
| `POST` | `/admin/businesses/{business}/unsuspend` | Restore with a reason |
| `GET/POST/PUT/DELETE` | `/admin/plans` | Plan and feature matrix maintenance |
| `GET` | `/admin/subscriptions` | Paginated subscriptions (`status`, `plan_id`) |
| `GET/PUT` | `/admin/subscriptions/{subscription}` | Change a subscription status |
| `GET` | `/admin/payments` | Paginated payments (`status`, `provider`, `business_id`) |
| `POST` | `/admin/impersonate/{user}` | Start an impersonation session |
| `POST` | `/admin/impersonate/stop` | Stop the impersonation session |

`GET /admin/dashboard` →

```json
{ "data": { "total_users": 128, "total_businesses": 96, "new_registrations": 12,
            "active_subscriptions": 41, "past_due_subscriptions": 2,
            "canceled_subscriptions": 5, "expired_subscriptions": 3,
            "churn_rate": 10.87, "mrr": 118900,
            "revenue_trend": [ { "month": "2026-10", "total": 118900, "payments": 41 } ],
            "payments": { "succeeded": 120, "failed": 4, "success_rate": 96.77 },
            "suspended_businesses": 1 } }
```

MRR counts only `active` subscriptions on recurring plans; yearly plans are
amortised over twelve months. One-time payments are not counted.

`PUT /admin/subscriptions/{subscription}` → `{ "status": "active", "reason": "…" }`
with `status` ∈ `trialing|active|past_due|canceled|expired|suspended`.

---

## 6. External API (`/v1/external`, API token)

| Method | Path | Ability |
| --- | --- | --- |
| `GET` | `/v1/external/products` | `products:read` |
| `GET` | `/v1/external/customers` | `customers:read` |
| `GET` | `/v1/external/orders` | `orders:read` |
| `POST` | `/v1/external/orders` | `orders:write` |

The token is sent as `Authorization: Bearer <api-token>`. Every route is
scoped to the business that issued the token.

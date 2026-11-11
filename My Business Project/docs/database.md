# Database

MySQL 8 (InnoDB, `utf8mb4`) is the production engine. The schema is created by
Laravel migrations and is always applied with `php artisan migrate --force`.

## 1. Conventions

| Concern | Rule |
| --- | --- |
| Primary keys | Auto-incrementing `bigint` |
| Money | `bigInteger` in the minor unit (cents). Never `float`/`double` |
| Percentages | `bigInteger` basis points (1000 = 10.00%) |
| Dates | `timestamp` for instants, `date` for calendar dates |
| Time zone | Stored in UTC; the business time zone is applied at query/report time |
| Soft deletes | `businesses`, `products`, `customers`, `plans` use `deleted_at` |
| JSON | `json` columns for structured but non-queried data (`settings`, `metadata`) |
| Naming | `snake_case` tables and columns, `camelCase` in JSON payloads |

Every tenant-owned table has `business_id` with a foreign key and at least one
index that starts with `business_id`, because every query filters by it.

## 2. Entity relationship overview

```
users ─┬─< businesses (owner_id)
       ├─< business_memberships >─ businesses
       ├─< api_tokens
       └─< admin_audit_logs / impersonation_logs

businesses ─┬─< business_subscriptions >─ plans
            ├─< products >─ categories
            ├─< customers
            ├─< orders >─ order_items
            ├─< business_invoices
            ├─< branches
            ├─< stock_movements
            ├─< payments >─ invoices (billing) >─ invoice_items
            ├─< api_tokens
            ├─< usage_counters / usage_events
            ├─< business_activity_logs
            └─< subscription_audit_logs

plans ──< plan_feature >─ features
coupons ──< coupon_redemptions >─ payments
payment_webhook_events (idempotency ledger)
settings (platform key/value)
```

## 3. Table reference

### `businesses`
The tenant root. `slug` is unique. `settings` holds workspace preferences
(invoice template, tax rate in basis points, date format, low-stock alerts,
automatic invoicing). `is_suspended` is set by platform administrators and
blocks sign-in; `suspension_reason` and `suspended_by` record who and why.

### `business_memberships`
One row per (business, user). `role` ∈ `owner|admin|staff`, `status` ∈
`invited|active|revoked`. `invite_token` is a 48 character random string, unique
across the table, and is nulled once the invitation is accepted. Unique
constraints on `(business_id, user_id)` and `(business_id, email)` prevent
duplicate memberships.

### `features`, `plans`, `plan_feature`
The entitlement catalogue.

- `features.key` — stable identifier (`multi_branch`, `advanced_reports`,
  `api_access`, `max_products`, …). `type` ∈ `boolean|limit|quota`.
  `default_enabled` / `default_limit` are the platform fallback used when a
  plan does not mention the feature.
- `plans` — `free`, `pro-monthly`, `pro-yearly` are seeded by `PlanSeeder`.
  `price_monthly` / `price_yearly` are minor units.
- `plan_feature` — the join table with `enabled` and `limit_value`
  (`NULL` = unlimited).

### `business_subscriptions`
One active subscription per business (`business_id` is unique). `status` ∈
`trialing|active|past_due|canceled|expired|suspended`. `ends_at` and
`grace_ends_at` drive the access decisions in `FeatureGate`.

### `payments`
Billing-side payments. `payment_number` is a unique human reference;
`provider` + `provider_payment_id` identify the transaction at the gateway;
`amount` is minor units; `status` ∈
`pending|processing|succeeded|failed|refunded|canceled`.

### `invoices` / `invoice_items`
Billing invoices issued when a payment is created. `line_items` and
`billing_details` are JSON snapshots; `invoice_items` stores the rendered
lines. Business-facing customer invoices live in `business_invoices` and
reference `customer_id` and `order_id`.

### `orders` / `order_items`
`subtotal`, `tax`, `discount` and `total` are computed on the server by
`OrderService` and stored. `order_items.name` and `unit_price` are snapshots so
that historical orders survive product edits.

### `products`
`price` is minor units. `cost_price` (added by migration
`2026_10_22_014210_add_cost_price_to_products_table.php`) is optional and
enables profit reporting. Unique `(business_id, sku)` allows the same SKU in
different businesses but not twice in the same one.

### `usage_counters` / `usage_events`
Counters are keyed by `(business_id, key, period)` where `period` is `Y-m` for
monthly quotas. `usage_events` is the append-only audit trail of every
increment and decrement.

### `api_tokens`
Only the SHA-256 hash of a token is stored. `abilities` is a JSON array of
scopes (`products:read`, `orders:write`, …). `revoked_at` and `expires_at`
disable a token without deleting the audit trail.

### Audit and log tables
`business_activity_logs` (who did what inside a workspace),
`admin_audit_logs` (platform admin actions with reason and before/after
values), `subscription_audit_logs` (subscription state transitions),
`impersonation_logs` (admin impersonation sessions).

### `payment_webhook_events`
Idempotency ledger for provider callbacks. `event_id` is unique, so a repeated
delivery is recorded but never processed twice.

## 4. Tenant scoping

`App\Scopes\BusinessScope` is applied to every tenant-owned model. It adds
`where business_id = <current business>` to queries and to relation loads. The
scope is skipped explicitly (`withoutGlobalScopes()`) only in:

- administrative cross-tenant queries (`app/Http/Controllers/Api/Admin/*`),
- exports, where the tenant filter is applied manually and explicitly,
- background jobs that bind a business through
  `BusinessContextService::runIn()`.

`TenantIsolationTest` asserts the boundary from the outside: a member of
business A can never read or mutate a record of business B, whatever the id.

## 5. Migrations

```
php artisan migrate            # apply pending migrations
php artisan migrate --force    # production (no confirmation)
php artisan migrate:status     # inspect
php artisan migrate:rollback --step=1
```

Migrations are append-only in production: a released migration is never
edited, corrections are new migrations. Seeders are idempotent
(`updateOrCreate`) and can be re-run safely:

```
php artisan db:seed --class=PlanSeeder       # plans + features
php artisan db:seed --class=DemoDataSeeder   # demo tenant data
php artisan db:seed --class=CouponSeeder     # sample coupons
```

## 6. Database maintenance

| Task | Command / approach |
| --- | --- |
| Reap expired sessions and cache | `php artisan optimize:clear` (scheduled daily) |
| Optimise tables | Nightly `OPTIMIZE TABLE` on the large InnoDB tables |
| Reclaim space | `OPTIMIZE TABLE` on `order_items`, `usage_events`, `stock_movements` |
| Reset entitlement cache | `php artisan cache:clear` or `FeatureGate::invalidate()` |
| Verify integrity | `php artisan test --filter=TenantIsolation` |

Use `php artisan db:monitor` (Laravel 12+) or the MySQL `performance_schema`
for slow query review in production.

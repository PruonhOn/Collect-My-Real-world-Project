# Billing

## 1. Model

| Concept | Table | Notes |
| --- | --- | --- |
| Plan | `plans` | `free`, `pro-monthly`, `pro-yearly` (seeded) |
| Feature | `features` | 24 catalogue entries: capabilities and limits |
| Plan ↔ feature | `plan_feature` | `enabled` + `limit_value` (`NULL` = unlimited) |
| Subscription | `business_subscriptions` | One per business, holds the current status and period |
| Payment | `payments` | Every charge attempt, with gateway references |
| Billing invoice | `invoices` + `invoice_items` | Issued with a charge |
| Webhook ledger | `payment_webhook_events` | Idempotency of provider callbacks |
| Usage | `usage_counters`, `usage_events` | Quota consumption per period |

Entitlements are **data driven**. Nothing in the application code hard-codes
"pro users can do X"; the answer is resolved from the database by
`App\Services\Features\FeatureGate`.

## 2. Plans and the feature matrix

| Feature key | Type | Free | Pro |
| --- | --- | --- | --- |
| `basic_dashboard` | boolean | on | on |
| `limited_exports` | boolean | on | on |
| `core_settings` | boolean | on | on |
| `multi_branch` | boolean | – | on |
| `advanced_reports` | boolean | – | on |
| `profit_reports` | boolean | – | on |
| `best_selling_reports` | boolean | – | on |
| `custom_roles` | boolean | – | on |
| `branded_invoices` | boolean | – | on |
| `api_access` | boolean | – | on |
| `inventory_management` | boolean | – | on |
| `low_stock_alerts` | boolean | – | on |
| `stock_history` | boolean | – | on |
| `pdf_excel_exports` | boolean | – | on |
| `discounts_coupons` | boolean | – | on |
| `tax_settings` | boolean | – | on |
| `business_activity_history` | boolean | – | on |
| `priority_support` | boolean | – | on |
| `max_businesses` | limit | 1 | 1 |
| `max_products` | limit | 50 | unlimited |
| `max_customers` | limit | 100 | unlimited |
| `max_members` | limit | 2 | 20 |
| `max_orders_monthly` | limit | 100 | unlimited |
| `max_branches` | limit | 0 | unlimited |

Prices (minor units): Free `0/0`, Pro Monthly `2900/29000`, Pro Yearly
`2900/27840`. Pro plans enable a 14 day trial.

## 3. Subscription state machine

```
            checkout + confirmed payment
trialing ──────────────────────────────► active
   │                                       │  │  │
   │ trial expired                        │  │  └─► canceled ──(resume)──► active
   ▼                                       │  └────► past_due ──► expired
expired                                   └────────► expired
```

| Status | Meaning | Access |
| --- | --- | --- |
| `trialing` | Trial running | Full plan entitlements until `trial_ends_at` |
| `active` | Paid and current | Full plan entitlements until `ends_at` |
| `past_due` | Collection failed | Entitlements kept during `grace_ends_at` (default 3 days) |
| `canceled` | User cancelled | Kept until `ends_at` when cancelled at period end |
| `expired` | Paid period over | Free-tier defaults only |
| `suspended` | Administratively suspended | Denied |

`SubscriptionService::transition()` is the single entry point for every change.
It runs in a transaction, writes a `subscription_audit_logs` row and
invalidates the entitlement cache. Webhooks, the admin console and the
scheduled commands all go through it, so the audit trail can never diverge
from the actual state.

## 4. Checkout flow

```
SPA                         API                              Gateway
 │  POST /business/billing/checkout
 │  {plan_id, billing_cycle, gateway}
 │ ─────────────────────────► createPendingCharge()
 │                            ├─ pending payment row
 │                            └─ billing invoice (status: open)
 │                            createCheckout() ─────────────►
 │ ◄───────── {checkout_url, reference, payment, invoice}
 │  redirect to checkout_url
 │                            ◄───────── webhook / confirm ──
 │                            verify signature
 │                            idempotency check (payment_webhook_events)
 │                            payment → succeeded, invoice → paid
 │                            SubscriptionService::activateFromPayment()
 │                            FeatureGate::invalidate()
```

Rules that the implementation guarantees:

- **The browser never activates a plan.** `POST /business/billing/confirm` only
  asks the server to re-check the payment with the gateway; the authoritative
  state comes from the provider.
- A checkout for the `free` plan is rejected (`422`).
- An inactive plan cannot be purchased (`422`).
- An unknown or unconfigured gateway is rejected with the list of available
  gateways.
- Duplicate webhook deliveries are ignored through the unique `event_id`.

## 5. Gateways

| Gateway | Class | Configuration |
| --- | --- | --- |
| Stripe | `StripeGateway` | `STRIPE_SECRET_KEY`, `STRIPE_PUBLISHABLE_KEY`, `STRIPE_WEBHOOK_SECRET`, price ids |
| Bakong KHQR | `BakongKhqrGateway` | `BAKONG_MERCHANT_ID`, `BAKONG_SECRET_KEY`, `BAKONG_APP_ID` |
| ABA Payway | `AbaPaywayGateway` | `ABA_MERCHANT_ID`, `ABA_PRIVATE_KEY`, `ABA_PUBLIC_KEY`, `ABA_IPN_HASH` |
| Fake | `FakeGateway` | Deterministic, offline. Never enabled in production |

The active gateway is `BILLING_DEFAULT_GATEWAY` (`fake` by default — change it
before going live). `PaymentGatewayManager::availableGateways()` only returns
gateways whose configuration is complete, so an unfinished integration is never
offered to a customer.

The fake gateway is used by the automated tests and by the "Simulate payment
(dev)" button on the billing screen. It settles through
`POST /api/v1/webhooks/fake` with the header `X-Fake-Signature:
fake-webhook-secret` and the event type `payment_succeeded`. That route exists
only in the `local` and `testing` environments.

## 6. Entitlements in practice

```php
$gate->allows($business, 'multi_branch');       // bool
$gate->limit($business, 'max_products');         // int|null (null = unlimited)
$gate->remaining($business, 'max_products');     // int|null
$gate->checkLimit($business, 'max_products');    // bool, before creating
$gate->incrementUsage($business, 'max_products');// after creating
$gate->syncUsage($business, 'max_products', $n); // recompute from the source of truth
```

The gate **fails closed**: if the subscription cannot be resolved, restricted
features are denied rather than granted.

Resolved entitlements are cached per business for 300 seconds and invalidated
whenever a plan, a plan-feature row or a subscription changes.

## 7. Limits and usage metering

| Feature key | Counter | Period |
| --- | --- | --- |
| `max_products` | `max_products` | monthly |
| `max_customers` | `max_customers` | monthly |
| `max_members` | `max_members` | monthly |
| `max_orders_monthly` | `orders_monthly` | monthly |

Counters live in `usage_counters` with the unique key
`(business_id, key, period)`; `period` is `Y-m` in the business time zone.
Every increment is also appended to `usage_events` for auditing.

When a limit is reached, the API answers `422` with
`{"message": "...", "limit": n, "upgrade_required": true}`. The dashboard shows
the current consumption with a progress bar and links to the billing screen.

## 8. Cancellation and resume

`POST /business/billing/portal` accepts:

```json
{ "action": "cancel", "at_period_end": true }
{ "action": "resume" }
```

- `cancel` with `at_period_end: true` keeps the entitlements until `ends_at`;
  with `false` the access stops immediately.
- `resume` reactivates the subscription, keeping the current period when it is
  still in the future.

## 9. Scheduled maintenance

| Command | Purpose |
| --- | --- |
| `subscriptions:expire-trials` | Turns finished trials into `expired` |
| `subscriptions:expire` | Expires subscriptions past `ends_at` |
| `subscriptions:grace-period` | Moves `past_due` subscriptions past the grace period to `expired` |
| `subscriptions:reconcile` | Compares local subscriptions with the gateway state |

They are already scheduled in `routes/console.php` (hourly, and every four
hours for the reconciliation) and run through `php artisan schedule:work` or
the cron entry from [deployment.md](deployment.md).

## 10. Admin operations

Platform administrators can change a subscription from the admin console
(`/admin/subscriptions`):

```json
{ "status": "active", "reason": "Payment confirmed offline" }
```

The change is applied through `SubscriptionService::transition()`, so the audit
log, the entitlement cache and the business view stay consistent.

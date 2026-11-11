# Acceptance Criteria

Every criterion below is verifiable. The **Evidence** column names the test,
command or file that proves it.

Last full verification run: **122 tests / 379 assertions** on both SQLite and
MySQL 8, `npm run lint` with 0 errors, `npm run build` successful.

## 1. Authentication

| # | Criterion | Evidence |
| --- | --- | --- |
| 1.1 | A visitor can register with name, email and password and receives a token | `AuthenticationTest` — "registers a new user" |
| 1.2 | The password is hashed and never returned by the API | `AuthenticationTest` — "never exposes the password" |
| 1.3 | Invalid credentials answer `422` with a field error | `AuthenticationTest` — "rejects invalid credentials" |
| 1.4 | The login endpoint is rate limited | `routes/api.php` (`throttle:auth`) |
| 1.5 | A user can request a password reset link and set a new password | `PasswordResetTest` (2 tests) |
| 1.6 | A reset token can only be used once | `PasswordResetTest` — "cannot reuse the reset token" |
| 1.7 | `GET /user` returns the user with owned businesses and memberships | `AuthenticationTest` — "returns the authenticated user" |
| 1.8 | Logging out invalidates the token | `AuthenticationTest` — "logs the user out" |

## 2. Multi-tenancy and authorisation

| # | Criterion | Evidence |
| --- | --- | --- |
| 2.1 | A request without a tenant header is rejected | `TenantIsolationTest` |
| 2.2 | A user who is not a member of the business receives `403` | `TenantIsolationTest` — "rejects a user without membership" |
| 2.3 | Records of another business are invisible, even with a valid id | `TenantIsolationTest` — "cannot read a product of another business" |
| 2.4 | Writing to another business's records is impossible | `TenantIsolationTest` — "cannot update a product of another business" |
| 2.5 | The same SKU may exist in two different businesses | `BusinessModulesTest` — "allows the same sku in two different businesses" |
| 2.6 | A suspended business loses access | `BusinessAdminController` + `Admin` tests |
| 2.7 | A staff member cannot manage members, settings or the profile | `BusinessSettingsTest` — "lets a business administrator update the profile but not a staff member" |
| 2.8 | An invitation token can only be accepted once | `InvitationTest` — "consumes the invitation token once it is accepted" |

## 3. Business modules

| # | Criterion | Evidence |
| --- | --- | --- |
| 3.1 | Products can be created, updated, searched, paginated and deleted | `BusinessModulesTest` (3 tests) |
| 3.2 | A duplicate SKU inside one business is rejected | `BusinessModulesTest` — "rejects a duplicate sku in the same business" |
| 3.3 | Customers can be created, updated, searched and deleted | `BusinessModulesTest` |
| 3.4 | An order total is computed on the server from the stored prices | `BusinessModulesTest` — "calculates the order total on the server" |
| 3.5 | Creating an order moves the stock and writes a stock movement | `BusinessModulesTest` — "records a stock movement when an order is created" |
| 3.6 | Invoices are numbered, listed and downloadable as PDF | `InvoiceController`, `InvoicePdfService` |
| 3.7 | Branches are only reachable with the `multi_branch` entitlement | `EnsureFeatureEnabled`, `FeatureGateTest` |
| 3.8 | The dashboard returns today's and this month's sales plus the plan usage | `BusinessDashboardController`, `BusinessModulesTest` — "returns the dashboard summary" |
| 3.9 | Sales reports aggregate by day and by status for a date range | `ReportController` |
| 3.10 | The profit report reports `profit_available: false` when no cost data exists | `ReportController::profit` |
| 3.11 | CSV and XLSX exports are tenant scoped and limited to 5000 rows | `ExportController` |

## 4. Billing and entitlements

| # | Criterion | Evidence |
| --- | --- | --- |
| 4.1 | The plan and feature matrix is seeded in the database, not in code | `PlanSeeder`, `plans` / `features` / `plan_feature` tables |
| 4.2 | A free plan denies a pro-only feature | `FeatureGateTest` — "denies a feature that is not in the plan" |
| 4.3 | A pro plan grants the feature | `FeatureGateTest` — "grants a feature that is in the plan" |
| 4.4 | An expired subscription loses the paid entitlements | `FeatureGateTest` — "denies everything when the subscription expired" |
| 4.5 | A past-due subscription keeps access during the grace period | `FeatureGateTest` — "keeps access during the grace period" |
| 4.6 | A canceled subscription keeps access until the end of the paid period | `FeatureGateTest` |
| 4.7 | The gate fails closed when the subscription cannot be resolved | `FeatureGateTest` — "fails closed when the subscription cannot be resolved" |
| 4.8 | Limits are enforced when creating records | `FeatureGateTest`, `TeamController` |
| 4.9 | Checkout creates a pending payment and an invoice | `BillingTest` |
| 4.10 | The plan is only activated after the provider confirms the payment | `BillingTest` — "activates the subscription after a confirmed payment" |
| 4.11 | A repeated webhook does not activate the plan twice | `BillingTest` — "ignores a duplicated webhook event" |
| 4.12 | A checkout for the free plan is rejected | `BillingController::checkout` |
| 4.13 | A subscription can be cancelled and resumed | `BillingController::portal`, `SubscriptionService` |
| 4.14 | Every subscription change is written to the audit log | `subscription_audit_logs`, `SubscriptionService::transition` |
| 4.15 | The admin console can override a subscription status with a reason | `Admin\SubscriptionController` |

## 5. API tokens

| # | Criterion | Evidence |
| --- | --- | --- |
| 5.1 | A token is issued with a name, abilities and an optional expiry | `ApiTokenTest` — "issues an api token" |
| 5.2 | Only the hash is stored, the plain text is returned once | `ApiTokenTest` — "stores only the hash of the token" |
| 5.3 | A token can be revoked and stops working immediately | `ApiTokenTest` — "revokes an api token" |
| 5.4 | A token only reaches its own business | `ApiTokenTest` — "never reaches another business" |
| 5.5 | An expired token is rejected | `ApiTokenTest` — "rejects an expired token" |
| 5.6 | An ability that the token does not carry is rejected | `ApiTokenTest` — "rejects a request without the required ability" |
| 5.7 | The external API is only reachable with the `api_access` entitlement | `EnsureFeatureEnabled` on `/business/api-tokens` |

## 6. Web application

| # | Criterion | Evidence |
| --- | --- | --- |
| 6.1 | Unauthenticated visitors are redirected to the login screen | `frontend/src/routes/guards.jsx` |
| 6.2 | A non-super-admin cannot open the admin area | `guards.jsx` → `AdminRoute` |
| 6.3 | The API client sends the bearer token and the tenant header | `frontend/src/api/client.js` |
| 6.4 | A `401` clears the session and returns to the login screen | `api/client.js` interceptor + `AuthContext` |
| 6.5 | Locked features are hidden or explained in the interface | `FeatureGate` component, `BusinessContext.allows` |
| 6.6 | The plan usage is visible with the current consumption | `pages/business/Dashboard.jsx` |
| 6.7 | Money is displayed with the business currency, stored as cents | `utils/format.js`, `formatMoney` |
| 6.8 | Forms validate in the browser and the API validates again | `react-hook-form` + `zod` on both sides |
| 6.9 | The workspace can be switched from the header | `BusinessLayout`, `BusinessContext.switchBusiness` |
| 6.10 | The production build succeeds and the bundle is split | `npm run build` |
| 6.11 | The linter reports no error | `npm run lint` |

## 7. Documentation and operations

| # | Criterion | Evidence |
| --- | --- | --- |
| 7.1 | Architecture, tenancy and money rules are documented | `docs/architecture.md` |
| 7.2 | The schema and the conventions are documented | `docs/database.md` |
| 7.3 | The security model and the hardening checklist are documented | `docs/security.md` |
| 7.4 | The billing lifecycle is documented | `docs/billing.md` |
| 7.5 | Every endpoint is documented with its payload | `docs/api.md` |
| 7.6 | The installation and release procedure is documented | `docs/deployment.md` |
| 7.7 | The backup and restore procedure is documented | `docs/backup-recovery.md` |
| 7.8 | The delivery status is documented | `docs/implementation-plan.md` |

## 8. Known gaps

| Item | Impact | Plan |
| --- | --- | --- |
| Coupon redemption is not applied at checkout | Discount codes cannot be used yet | Wire `CouponSeeder` data into `BillingService::createPendingCharge` |
| No CI pipeline | Regressions are only caught manually | Add a workflow running the backend suite (SQLite + MySQL) and `npm run lint` / `npm run build` |
| No end-to-end tests | Browser flows are only verified manually | Add Playwright for sign-up → business → product → order → checkout |
| Ownership transfer | The team API refuses to change the owner | Implement the dedicated transfer workflow |
| Token storage in `localStorage` | XSS could expose the session | Move to an HttpOnly cookie with Sanctum SPA mode |

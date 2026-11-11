# My Business — Documentation

My Business is a multi-tenant SaaS for small companies: inventory, customers,
orders, invoices, reporting, team management, branch management and a
subscription/billing layer with data-driven entitlements.

| Document | Contents |
| --- | --- |
| [architecture.md](architecture.md) | Layers, request lifecycle, tenancy model, key services, frontend structure |
| [database.md](database.md) | Schema, tables, relationships, money and time conventions, migrations |
| [security.md](security.md) | Authentication, authorisation, tenant isolation, tokens, hardening checklist |
| [billing.md](billing.md) | Plans, entitlements, checkout, webhooks, subscriptions, usage metering |
| [api.md](api.md) | Complete endpoint reference with request/response shapes |
| [deployment.md](deployment.md) | Requirements, installation, configuration, release process, scheduled tasks |
| [backup-recovery.md](backup-recovery.md) | Backup strategy, restore procedure, RPO/RTO, disaster drills |
| [implementation-plan.md](implementation-plan.md) | Delivery phases, status of every module, remaining work |
| [acceptance-criteria.md](acceptance-criteria.md) | Functional and non-functional acceptance checklist with verification evidence |

## Repository layout

```
my-business/
├── backend/    Laravel 13 API (Sanctum, MySQL 8, Pest)
├── frontend/   React 19 + Vite 8 SPA (Tailwind 4, React Router 7)
├── docs/       This documentation set
└── README.md   Quick start
```

## Conventions used everywhere in this documentation

- **Money** is always an integer in the minor unit of the currency (cents). The
  API never returns floats for monetary values; the browser formats them.
- **Dates** are stored in UTC and rendered in the business time zone. A business
  owns exactly one IANA time zone, used for day/month boundaries in reports.
- **Tenancy** is request scoped. Every business table carries a `business_id`
  column, and a global scope enforces it automatically.
- **Entitlements** are never hard-coded. They live in `features`,
  `plan_feature` and `business_subscriptions` and are resolved by
  `App\Services\Features\FeatureGate`.

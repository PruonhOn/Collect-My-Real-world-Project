# SMS — System Architecture

> Properties: **status:** approved · **owner:** backend · **updated:** 2026

## 1. Overview

Monolithic centralized API + multiple thin frontends. All business rules live in the Laravel API. Frontends are views only; authorization is enforced in the API.

## 2. Clients

| Client | Repo | Auth | Purpose |
|---|---|---|---|
| Admin Web | `apps/admin-web` | Sanctum (cookie/Bearer) | School operations, RBAC, finance, reports |
| Teacher Web | `apps/teacher-web` | Bearer token | Roster, attendance, scores for own courses |
| Student Web | `apps/student-web` | Bearer token | Read-only academic view |
| Parent Web | `apps/parent-web` | Bearer token | Children monitoring, fees, messaging |
| Public Website | `apps/public-website` | none | Marketing, courses, contact, partnership |
| Mobile App | `apps/mobile-app` | Bearer token (secure store) | Student & Parent |

## 3. Technology Stack

| Layer | Choice |
|---|---|
| Backend | Laravel 12 · PHP 8.4 |
| Auth | Laravel Sanctum (SPA cookie for web + tokens for mobile) |
| RBAC | `spatie/laravel-permission` |
| DB | MySQL 8.4 |
| Cache/Queue | Redis (database driver allowed in dev) |
| Queue worker | Laravel Horizon (prod) / `queue:work` (dev) |
| Storage | `filesystems.default` switchable local / S3-compatible (Cloud Object Storage) |
| API docs | `dedoc/scramble` (OpenAPI 3, auto-generated) |
| PDF export | `dompdf/dompdf` (queued) |
| Excel export | `maatwebsite/excel` (queued) |
| Search | Eloquent + MySQL `LIKE` (indexed) |

## 4. Roles

`Super Admin`, `Admin`, `Teacher`, `Staff`, `Student`, `Parent`, `Public Visitor` (unauthenticated).

## 5. Domain Boundaries (backend `app/` layout)

```
app/
├── Http/Controllers/Api/V1/     # thin HTTP layer (validation + policy + response)
├── Http/Requests/               # FormRequest per action
├── Http/Resources/              # response transformers
├── Models/
├── Policies/
├── Services/                    # business logic (single source of truth)
│   ├── AttendanceService.php
│   ├── ScoreService.php
│   ├── GradeService.php
│   ├── EnrollmentService.php
│   ├── FeeService.php
│   ├── PaymentService.php       # gateway abstraction
│   ├── PaymentGateways/         # ManualGateway (default), future: BakongGateway, AcledaGateway...
│   ├── NotificationService.php
│   ├── FileService.php
│   ├── AuditService.php
│   ├── ReportService.php        # dispatch export jobs
│   └── DashboardService.php
├── Enums/                       # PHP 8.1+ enums (CourseType, AttendanceStatus, ...)
└── Events/                      # domain events (attendance.recorded, payment.confirmed, ...)
```

## 6. API Conventions

- Base path: `/api/v1`
- JSON envelope: `{ "data": {...}, "meta": {...}, "errors": null }`
- Validation errors: HTTP 422, `errors` object keyed by field
- Pagination: `?page=&per_page=`
- Filtering: `?filter[status]=active`
- Searching: `?search=term`
- Eager loading: `?include=courses,teacher`
- Auth errors: 401 (token), 403 (permission/scope)
- Rate limiting: 60/min authenticated, 30/min public, stricter on `/auth/login`

## 7. Security

Eloquent (SQLi-safe), FormRequest validation, Spatie permission middleware, per-model Policies, Sanctum tokens, bcrypt/argon2 hashing, MIME+size checked uploads, CSP headers, HttpOnly cookie option, audit trail on all sensitive mutations, env-only secrets.

## 8. Caching & Queues

- Dashboard totals + report aggregates cached in Redis, invalidated on writes.
- Exports, receipts, notification fan-out run via queue jobs.
- Local dev on Laragon: `.env` `QUEUE_CONNECTION=database` unless Redis available.

## 9. Notifications

DB channel now. `NotificationService` broadcasts domain events; mail/SMS/webpush/mobile-push channels plug in later without touching callers.
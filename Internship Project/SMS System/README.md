# School Management System (SMS)

A professional, scalable School Management System with a centralized Laravel REST API and multiple isolated frontend applications.

## Architecture

```
Admin Web  ·  Teacher Web  ·  Student Web  ·  Parent Web  ·  Public Website  ·  Mobile App
                                      |
                                      v
                           Laravel REST API  (/api/v1)
                                      |
                                      v
                   MySQL 8  ·  Redis  ·  Cloud Object Storage
```

## Repositories (monorepo)

| Path                   | Purpose                                    |
| ---------------------- | ------------------------------------------ |
| `backend/`             | Laravel 12 REST API                         |
| `apps/admin-web/`      | Admin dashboard (React + Vite + Tailwind)   |
| `apps/teacher-web/`    | Teacher console                             |
| `apps/student-web/`    | Student academic dashboard                  |
| `apps/parent-web/`     | Parent monitoring dashboard                 |
| `apps/public-website/` | Public marketing site (no login)            |
| `apps/mobile-app/`     | Student & Parent mobile app (Expo)          |
| `packages/api-client/` | Shared typed API client (React/tanstack)    |
| `packages/ui/`         | Shared design system                        |
| `docs/`                | Architecture, ERD, API docs                 |

## Documentation

- [Architecture](./docs/architecture.md)
- [Database ERD](./docs/erd.md)

## Development

See `backend/README.md` for backend setup.
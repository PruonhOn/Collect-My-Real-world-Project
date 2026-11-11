# Digital Library (បណ្ណាល័យឌីជីថល)

A full-stack Digital Library application with a Laravel API backend and React frontend. Supports online reading with PDF.js, mock payments, book sharing, and a full admin panel. Primary UI is in Khmer.

## Tech Stack

| Layer       | Technology                               |
|-------------|------------------------------------------|
| Backend     | Laravel 13, PHP 8.4, Sanctum (Bearer tokens) |
| Frontend    | React 19, Vite 8, React Router 7, Tailwind CSS 4, PDF.js |
| Database    | MySQL 8.4                                |
| PDF Engine  | pdfjs-dist (client-side rendering)       |

---

## Project Structure

```
Digital-Library/
├── backend/               ← Laravel API
│   ├── app/
│   │   ├── Http/Controllers/Api/   (Auth, Books, Reader, Payment, Sharing, Admin)
│   │   ├── Models/                 (User, Book, Category, Course, Purchase, Payment, Bookmark, ReadingProgress, SharingKey, SharedAccess)
│   │   └── Services/               (AccessService, MockPaymentService, BookUploadService)
│   ├── database/migrations/
│   ├── database/seeders/
│   └── tests/Feature/              (74 API tests)
├── frontend/              ← React SPA
│   └── src/
│       ├── api/client.js           (Axios + token interceptor)
│       ├── context/                (Auth, Toast)
│       ├── components/             (Navbar, Footer, BookCard, guards, UI helpers)
│       ├── pages/                  (Home, Books, BookDetail, Reader, Library, Payments, Sharing, Login, Register)
│       └── pages/admin/            (Dashboard, Users, Books, Categories, Courses, Payments, SharingKeys)
└── .gitignore
```

---

## Setup

### Prerequisites

- PHP 8.3+ with required extensions (pdo_mysql, mbstring, openssl, bcmath, fileinfo)
- Composer
- Node.js 18+ / npm
- MySQL 8+

### Backend Setup

```bash
cd backend

# 1. Install dependencies
composer install

# 2. Configure environment
cp .env.example .env
php artisan key:generate
# Edit .env: set DB_DATABASE=digital_library, DB_PASSWORD=root (or your MySQL password)

# 3. Create database and run migrations + seed
mysql -u root -proot -e "CREATE DATABASE IF NOT EXISTS digital_library CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate:fresh --seed

# 4. Create storage symlink for cover images
php artisan storage:link

# 5. Start the server
php artisan serve
```

API runs at `http://localhost:8000`

### Frontend Setup

```bash
cd frontend

# 1. Install dependencies
npm install

# 2. Start the dev server (proxies /api to backend:8000)
npm run dev
```

Frontend runs at `http://localhost:5173`

### Demo Accounts

| Role    | Email                | Password |
|---------|----------------------|----------|
| Admin   | admin@example.com    | password |
| Reader  | reader@example.com   | password |

---

## Database Schema (10 tables)

| Table            | Purpose                                            |
|------------------|----------------------------------------------------|
| users            | Readers + admins, role=reader\|admin                |
| categories       | Book categories (e.g. Literature, Technology)      |
| books            | E-books with cover, file, price, pages, language   |
| courses          | Online courses (admin-managed)                     |
| course_lessons   | Lessons within courses                             |
| purchases        | One per user per book/course, status=pending\|paid |
| payments         | Mock payment records linked to purchases           |
| bookmarks        | User page bookmarks per book                       |
| reading_progress | Last page + progress % per user per book           |
| sharing_keys     | 30-day expiring codes, max 3 uses                 |
| shared_access    | Active/revoked access granted via shared keys      |

---

## API Endpoints (v1 prefix)

### Public

| Method | Endpoint                  | Description                   |
|--------|---------------------------|-------------------------------|
| POST   | `/register`               | Create account                |
| POST   | `/login`                  | Get Bearer token              |
| GET    | `/categories`             | List categories               |
| GET    | `/categories/{id}`        | Category detail + books       |
| GET    | `/books`                  | List books (search/sort/filter/paginate) |
| GET    | `/books/{id}`             | Book detail + related         |
| GET    | `/courses`                | List courses                  |
| GET    | `/courses/{id}`           | Course detail + lessons       |

### Authenticated (requires `Authorization: Bearer <token>`)

| Method | Endpoint                        | Description               |
|--------|---------------------------------|---------------------------|
| POST   | `/logout`                       | Revoke token              |
| GET    | `/me`                           | Current user              |
| PUT    | `/me`                           | Update profile/password   |
| POST   | `/books/{id}/purchase`          | Buy book (mock payment)   |
| POST   | `/courses/{id}/purchase`        | Buy course                |
| GET    | `/payments`                     | Payment history           |
| GET    | `/library`                      | My owned + shared books   |
| GET    | `/library/{book_id}`            | Access details for a book |
| GET    | `/reader/{id}`                  | Reader meta + progress    |
| GET    | `/reader/{id}/stream`           | Protected PDF stream      |
| POST   | `/reader/{id}/progress`         | Save reading progress     |
| GET    | `/reader/{id}/bookmarks`        | List bookmarks            |
| POST   | `/reader/{id}/bookmarks`        | Add bookmark              |
| DELETE | `/reader/bookmarks/{id}`        | Remove bookmark           |
| POST   | `/books/{id}/share`             | Generate sharing key      |
| POST   | `/redeem`                       | Redeem sharing key        |
| GET    | `/sharing`                      | My keys + received access |
| DELETE | `/sharing/{shared_access_id}`   | Revoke shared access      |

### Admin Only (role=admin, prefix `/admin`)

| Method | Endpoint                     | Description              |
|--------|------------------------------|--------------------------|
| GET    | `/dashboard`                 | Stats + charts           |
| GET    | `/users`                     | List users (searchable)  |
| PATCH  | `/users/{id}/role`           | Change role              |
| DELETE | `/users/{id}`                | Delete user              |
| CRUD   | `/categories`                | Create/update/delete     |
| CRUD   | `/books`                     | Create/update/delete (+ file upload) |
| CRUD   | `/courses`                   | Create/update/delete (+ cover upload)|
| GET    | `/payments`                  | All payment records      |
| GET    | `/sharing-keys`              | All sharing keys         |

---

## Phases Completed

| #  | Phase                        | Status |
|----|------------------------------|--------|
| 1  | Laravel + React + MySQL + Sanctum | Done |
| 2  | Migrations, Models, Relationships, Seeders | Done |
| 3  | Auth: Register/Login/Logout/Roles/Protected | Done |
| 4  | Books: Categories, Upload, Details, Search/Filter | Done |
| 5  | Library: Purchase, Access Control, My Library | Done |
| 6  | Reader: PDF stream, Page nav, Bookmarks, Progress, Security | Done |
| 7  | Payment: Mock, Records, Status, Activation | Done |
| 8  | Sharing: Generate, Redeem, Max 3, Revoke | Done |
| 9  | Admin: Dashboard, Users, Books, Courses, Payments, Keys | Done |
| 10 | UI/UX: Khmer, Responsive, Loading/Error/Empty states | Done |
| 11 | Testing: 74 backend API tests, all passing | Done |
| 12 | Docs, Pint (code style), Production config | Done |

---

## Security Notes

- All book files are served via a protected `/reader/{id}/stream` endpoint that checks access before returning bytes — PDF content is never exposed as a public URL
- Reading progress and bookmarks are scoped per user; users cannot access other users' data
- Sharing keys are limited to 3 uses with 30-day expiry
- The mock payment system auto-approves all transactions (for demo purposes)

## Production Notes

For production deployment:
- Set `APP_DEBUG=false` and `APP_ENV=production`
- Use `php artisan config:cache && php artisan route:cache && php artisan view:cache`
- Build the frontend: `cd frontend && npm run build` → serve `/frontend/dist` via Nginx
- Set up SSL (HTTPS) for Sanctum token authentication in production
- Replace the mock payment gateway with a real payment provider

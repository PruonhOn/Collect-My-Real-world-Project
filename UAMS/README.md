# University Attendance Management System (UAMS)

A modern, production-ready University Attendance Management System built with Laravel 11+, MySQL, Tailwind CSS, and Alpine.js.

## Features

- **Role-Based Access Control**: Super Admin, Admin/Registrar, Teacher, Student
- **QR Code Attendance**: Generate time-limited QR codes for student self-check-in
- **Manual Attendance**: Teachers can mark attendance manually with full audit trail
- **Automated Alerts**: Absence threshold warnings and notifications
- **Reporting & Analytics**: Per-student, per-class, per-department, per-term reports with PDF/Excel export
- **Bulk Import**: Import students and teachers via CSV/Excel files
- **Audit Trail**: Every attendance change is logged with old/new values
- **Responsive UI**: Modern admin dashboard with sidebar navigation

## Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | Laravel 13 (PHP 8.4+) |
| Database | MySQL 8 (or SQLite for development) |
| Auth | Laravel Breeze + Spatie Permission |
| Frontend | Blade + Tailwind CSS + Alpine.js |
| QR Codes | simplesoftwareio/simple-qrcode |
| PDF Export | barryvdh/laravel-dompdf |
| Excel Export | maatwebsite/excel |

## Requirements

- PHP 8.3 or higher
- Composer
- Node.js & NPM
- MySQL 8.0+ (or SQLite for quick setup)

## Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd UAMS
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configure database**
   Edit `.env` and set your database credentials:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=uams
   DB_USERNAME=root
   DB_PASSWORD=
   ```

   Or use SQLite for quick setup:
   ```
   DB_CONNECTION=sqlite
   ```

6. **Run migrations and seeders**
   ```bash
   php artisan migrate --seed
   ```

7. **Build frontend assets**
   ```bash
   npm run build
   ```

8. **Start the development server**
   ```bash
   php artisan serve
   ```

9. **Visit** http://localhost:8000

## Default Login Credentials

| Role | Email | Password |
|------|-------|----------|
| Super Admin | super_admin@uams.edu | password |
| Admin | admin@uams.edu | password |
| Teacher (Dr. Ahmed Khan) | ahmed.khan@uams.edu | password |
| Teacher (Dr. Sarah Williams) | sarah.williams@uams.edu | password |
| Student (STU001) | student001@uams.edu | password |
| Student (STU002) | student002@uams.edu | password |

*All demo accounts use password: `password`*

## Project Structure

```
app/
├── Enums/           # PHP enums for statuses and roles
├── Http/
│   ├── Controllers/
│   │   ├── Admin/   # Admin panel controllers
│   │   ├── Teacher/ # Teacher panel controllers
│   │   └── Student/ # Student panel controllers
│   ├── Middleware/   # Custom middleware (EnsureUserIsActive)
│   └── Requests/    # Form request validation classes
├── Models/          # Eloquent models with relationships
├── Policies/        # Authorization policies
└── Services/        # Business logic services
    ├── AttendanceService.php
    ├── EnrollmentService.php
    ├── ReportService.php
    ├── NotificationService.php
    └── ImportService.php

database/
├── migrations/      # Database migrations
├── seeders/         # Realistic demo data seeders
└── factories/       # Model factories

resources/views/
├── admin/           # Admin panel views
├── teacher/         # Teacher panel views
├── student/         # Student panel views
└── layouts/         # Main layout with sidebar
```

## Key Features

### QR Code Attendance
1. Teacher creates a session for their class section
2. System generates a unique QR code for the session
3. Students scan the QR code (or visit the URL) to check in
4. QR tokens are time-limited and can be regenerated
5. Teacher can close the session, auto-marking absent students

### Attendance Taking Flow
1. Navigate to a class section → Take Attendance
2. Create a new session (date, time, grace period)
3. Mark each student: Present / Absent / Late / Excused
4. Or use bulk actions (Mark All Present, etc.)
5. Close session when done → absent students auto-marked

### Reporting
- **Student Report**: Per-course attendance breakdown with percentages
- **Class Report**: Per-student attendance in a class section
- **Department Report**: Aggregate stats across all courses
- **Term Report**: Full term summary
- Export to PDF or Excel

## Configuration

### Attendance Grace Period
Default grace period is 15 minutes. Can be adjusted per session.

### Absence Threshold
Students with >20% absence rate in any course receive automated warnings.

### QR Code Expiry
QR codes are valid for the duration of the session + grace period.

## Testing

```bash
php artisan test
```

Or with Pest:
```bash
php artisan test --pest
```

## Notes

- This application uses SQLite by default for easy local development. For production, use MySQL 8.
- Mail is set to `log` driver by default. Configure SMTP for email notifications.
- Queue driver is `database`. For production, consider Redis or SQS.
- The system uses Spatie Laravel Permission for role-based access control.

## License

MIT License

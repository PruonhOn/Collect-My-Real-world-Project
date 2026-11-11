# SMS — Database ERD & Schema

> **status:** approved · **version:** v1 · MySQL 8.4 (InnoDB, utf8mb4)

## 1. Entity Relationship Diagram

```
users ─┬─ 1:1 ─ teachers ─┬─> courses (teacher_id)
       ├─ 1:1 ─ staff      │
       ├─ 1:1 ─ students ──┴─> course_enrollments ──> courses
       └─ 1:1 ─ parents ──(m:n student_parent)──> students

academic_years 1─n courses 1─n schedules
categories      1─n courses 1─n assessments 1─n scores ──> students
subjects        1─n courses 1─n attendance ──> students
                          1─n grades     ──> course_enrollments

students 1─n student_fees 1─n payments 1─1 payment_receipts

announcements (fan-out) ──> notifications ──> users
messages      (threads)  ──> users
contact_inquiries        (public website)

audit_logs   (polymorphic: resource_type + resource_id)
files        (polymorphic: fileable)
school_settings (key/value)
```

## 2. Tables

### Identity & RBAC
| Table | Columns (key) | Constraints |
|---|---|---|
| `users` | id, name, email, phone, password, avatar_file_id, is_active, last_login_at, timestamps, deleted_at | email UNIQUE |
| `roles` | id, name, guard_name, display_name | UK(name, guard_name) — Spatie |
| `permissions` | id, name, guard_name, display_name | UK(name, guard_name) — Spatie |
| `role_has_permissions` | role_id, permission_id | PK(role_id, permission_id) — Spatie |
| `model_has_roles` | role_id, model_type, model_id | PK — Spatie |
| `model_has_permissions` | permission_id, model_type, model_id | PK — Spatie |
| `personal_access_tokens` | (Sanctum) | framework |
| `password_reset_tokens` | (framework) | framework |

### People profiles
| Table | Columns (key) | Constraints |
|---|---|---|
| `teachers` | id, user_id, employee_code, hire_date, qualification, specialization, bio | user_id UNIQUE FK, employee_code UNIQUE |
| `staff` | id, user_id, employee_code, position, department, hire_date | user_id UNIQUE FK, employee_code UNIQUE |
| `students` | id, user_id, student_code, khmer_name, gender, birth_date, contact_phone, guardian_name, guardian_phone, address, status, enrolled_on | user_id UNIQUE FK, student_code UNIQUE |
| `parents` | id, user_id, occupation | user_id UNIQUE FK |
| `student_parent` | student_id, parent_id, relationship, is_primary | PK(student_id, parent_id), FK both |

### Academic catalog
| Table | Columns (key) | Constraints |
|---|---|---|
| `academic_years` | id, name, start_date, end_date, is_current, status | name UNIQUE |
| `categories` | id, name, slug, description | slug UNIQUE |
| `subjects` | id, name, code, description | code UNIQUE |
| `courses` | id, academic_year_id, category_id, subject_id, teacher_id, name, code, description, type, fee, capacity, status, featured, start_date, end_date, deleted_at | FK all, type ENUM(face_to_face, part_time, online), status ENUM(active, archived) |
| `course_enrollments` | id, course_id, student_id, enrolled_on | UK(course_id, student_id) |
| `schedules` | id, course_id, day_of_week, start_time, end_time, room | FK course_id, day_of_week ENUM(1..7) |
| `attendance` | id, course_id, schedule_id, student_id, date, status, remark, recorded_by | UK(course_id, student_id, date), status ENUM(present, late, absent, excused) |

### Assessment & results
| Table | Columns (key) | Constraints |
|---|---|---|
| `assessments` | id, course_id, title, type, max_score, weight, date | type ENUM(quiz, assignment, midterm, final, other) |
| `scores` | id, assessment_id, student_id, score, remark | UK(assessment_id, student_id) |
| `grades` | id, enrollment_id, letter_grade, points, credit_hours, remarks | UK(enrollment_id) |

### Finance
| Table | Columns (key) | Constraints |
|---|---|---|
| `student_fees` | id, student_id, academic_year_id, course_id(optional), invoice_no, title, amount, paid_amount, status, due_date, deleted_at | invoice_no UNIQUE, status ENUM(unpaid, partial, paid, waived, cancelled) |
| `payments` | id, student_fee_id, student_id, parent_id(optional), processed_by, amount, method, gateway, transaction_id, status, paid_on, notes | status ENUM(pending, confirmed, failed, refunded) |
| `payment_receipts` | id, payment_id, receipt_no, file_id | payment_id UNIQUE, receipt_no UNIQUE |

### Communication
| Table | Columns (key) | Constraints |
|---|---|---|
| `announcements` | id, title, body, audience, published_at, expires_at, created_by | audience JSON |
| `notifications` | id, user_id, type, title, body, data, read_at | Laravel-compatible |
| `messages` | id, sender_id, recipient_id, parent_id, subject, body, read_at | FK users, parent_id = self-thread |
| `contact_inquiries` | id, name, email, phone, subject, message, status | status ENUM(new, replied, closed) |

### System
| Table | Columns (key) | Constraints |
|---|---|---|
| `school_settings` | id, group, key, value, is_public | UK(group, key) |
| `audit_logs` | id, user_id, action, resource_type, resource_id, ip_address, user_agent, before, after, created_at | JSON before/after |
| `files` | id, fileable_type, fileable_id, disk, path, original_name, mime_type, size, uuid | polymorphic |
| `reports` | id, type, params, file_id, status, generated_by | status ENUM(pending, processing, completed, failed) |

### Framework
`jobs`, `failed_jobs`, `cache`, `cache_locks`, `sessions`.

## 3. Indexes

- FK columns on all relations
- `attendance: (course_id, student_id, date)` composite unique
- `scores: (assessment_id, student_id)` composite unique
- `course_enrollments: (course_id, student_id)` composite unique
- `students.student_code`, `teachers.employee_code`, `academic_years.name`, `categories.slug`, `subjects.code` unique
- `payments.paid_on`, `student_fees.status`, `courses.status`, `attendance.status`, `attendance.date` — supporting filtering/report indices

## 4. Soft Deletes

`users`, `teachers`, `staff`, `students`, `courses`, `course_enrollments`, `student_fees`, `announcements`. Historical records never hard-deleted in these domains.

## 5. Enum domains (PHP 8 enums in backend)

- `CourseType: face_to_face | part_time | online`
- `AttendanceStatus: present | late | absent | excused`
- `CourseStatus: active | archived`
- `StudentStatus: active | suspended | transferred | graduated | dropped`
- `FeeStatus: unpaid | partial | paid | waived | cancelled`
- `PaymentStatus: pending | confirmed | failed | refunded`
- `AssessmentType: quiz | assignment | midterm | final | other`
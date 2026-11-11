# K&N Store Management System

A full-featured Python/Flask store management system with a modern admin dashboard.

## Features

- **Dashboard** — Revenue stats, low stock alerts, recent sales chart
- **Products (CRUD)** — Add/Edit/Delete with image upload, search & filter
- **Categories** — Manage product categories with icons & colors
- **Sales** — Record sales, filter by period, auto-deduct stock
- **Stock Management** — Row/Catalog view, stock adjustments, movement history
- **Analytics** — 4 interactive charts (trend, category pie, top products, monthly)
- **Reports** — Generate PDF reports (daily/weekly/monthly/annual)
- **Profile** — Upload profile picture, edit info, change password
- **Dark/Light Mode** — Toggle per admin account

## Setup

```bash
pip install -r requirements.txt
python app.py
```

Then open: http://localhost:5050

**Default Login:** `admin` / `admin123`

## Tech Stack

- Python 3.10+
- Flask + Flask-SQLAlchemy + Flask-Login
- SQLite database
- Matplotlib (charts) + ReportLab (PDF reports)
- Pure HTML/CSS (no JS framework needed)

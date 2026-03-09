# CSE 135 - Analytics Backend (Checkpoint 1)

## Team Members
- Victoria Timofeev
- Christine Le
- Ryan Soe

---

## Server Access


**SSH into Apache server:**
```
ssh grader@146.190.150.30
password: CSE135rocks
```

---

## What We Built

This checkpoint covers the three required areas for the analytics backend derisk:

### 1. MVC-Style App with Authentication and Navigation

We built a PHP-based backend hosted at `reporting.cse135vrc.site` with a login system that protects all dashboard pages from forceful browsing.

**How authentication works:**
- `login.php` — presents a login form and starts a PHP session on success
- `dashboard.php` — checks `$_SESSION['user']` at the top; redirects to `/login.php` if not set
- `logout.php` — destroys the session and redirects back to login

Typing in a protected URL like `/dashboard.php` directly without logging in will redirect to the login page. Sessions are required to access any page behind the login wall.

**Login credentials (for grader):**
- Username: `grader`
- Password: `ilovecse135`

### 2. Datastore Connected to a Data Table

The dashboard fetches live event data from the PostgreSQL database via the REST API and renders it as an HTML table. The table displays the 100 most recent events with columns for ID, Session ID, Event Type, and URL.

- API endpoint: `GET /api/events`
- Rendered in: `dashboard.php` via JavaScript `fetch()`

### 3. Datastore Connected to a Chart

The dashboard also renders a bar chart (using Chart.js) showing event counts grouped by event type, pulled from the same PostgreSQL database.

- API endpoint: `GET /api/event-summary`
- Rendered in: `dashboard.php` via Chart.js

---

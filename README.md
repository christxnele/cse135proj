# CSE 135 — Web Analytics Platform

**Team:** Victoria Timofeev, Christine Le, Ryan Soe

---

## Links

| | URL |
|-|-----|
| **Repo** | https://github.com/christxnele/cse135proj|
| **Course site** | https://cse135vrc.site |
| **Analytics dashboard** | https://reporting.cse135vrc.site |
| **Collector endpoint** | https://collector.cse135vrc.site |
| **Test site** | https://test.cse135vrc.site |

**Dashboard login:** `grader` / `ilovecse135`

**SSH access:** `ssh grader@146.190.150.30` (password: `CSE135rocks`)

---

## Project Overview

This is a full-stack web analytics platform built for UCSD CSE 135. A single Digital Ocean server runs four Apache virtual hosts. The test site deliberately generates messy, real-world analytics data (broken forms, rage-click traps, JS chaos injection) that the reporting dashboard then surfaces. The collector is embedded in the test site and sends batched events to a PostgreSQL database every 5 seconds or on page exit.

---

## Architecture

```
cse135vrc.site          →  course site (HW1, HW2 pages)
test.cse135vrc.site     →  intentionally broken e-commerce site (generates analytics data)
collector.cse135vrc.site →  POST endpoint: receives batched events from collector.js
reporting.cse135vrc.site →  PHP dashboard + REST API backed by PostgreSQL
```

All four domains deploy automatically on push to `main` via GitHub Actions + rsync over SSH.

### collector.js

Embedded in the test site. Captures:
- **Pageview events** — static device/browser info, navigation timing, Web Vitals (LCP, CLS, INP)
- **Activity batches** — clicks, scrolls, mousemoves, keypresses, idle periods, JS errors, promise rejections, resource load errors

Events are buffered and flushed every 5 seconds via `navigator.sendBeacon` (with a `fetch` fallback), and also on page exit. Session identity is maintained using `sessionStorage` + a first-party cookie.

### Database

PostgreSQL database `analytics` on `127.0.0.1:5432`.

| Table | Purpose |
|-------|---------|
| `sessions` | One row per browser session (UUID, created_at, last_seen) |
| `events` | Every collected event — event_type, url, session_id, jsonb payload |
| `users` | Dashboard accounts with bcrypt passwords, roles, and allowed_sections |
| `report_comments` | Analyst annotations attached to report tabs |

### REST API (`/api/*`)

Full CRUD on `events` and `sessions`. Read-only aggregate endpoints:

| Endpoint | Returns |
|----------|---------|
| `GET /api/event-summary` | Event counts grouped by type |
| `GET /api/reports/traffic` | Pageview KPIs, daily trend, top pages, browser/OS breakdown |
| `GET /api/reports/performance` | Web Vitals score distributions and per-page averages |
| `GET /api/reports/errors` | Error counts by type, top error messages, rage click incidents |
| `GET /api/reports/behavior` | Scroll depth and mouse travel distance per URL |

---

## Reports Page — Tab by Tab

The reports page (`/reports.php`) is the main analytical interface. Access to each tab is controlled by role — `super_admin` and `analyst` can see all tabs and post comments; `viewer` sees only saved reports; analysts with a restricted `allowed_sections` JSONB field only see their assigned tabs.

### Traffic & Engagement

Answers: *how much traffic is the site getting, from where, and on what browsers?*

- **KPI cards** — total pageviews, unique sessions, average pages per session
- **Pageviews per Day** — line chart showing daily volume trends over time
- **Top 10 Pages by Views** — vertical bar chart of the most-visited URLs
- **Page Breakdown table** — per-URL view count, session count, first seen, and last seen dates
- **Browser Breakdown** — horizontal bar chart of Chrome / Firefox / Safari / Edge / Other, derived from the `userAgent` string captured at pageview time (Edge is checked before Chrome to avoid false matches since Edge's UA contains "Chrome")
- **OS Breakdown** — horizontal bar chart of Windows / macOS / iOS / Android / Linux / Other

### Performance

Answers: *how fast does the site feel to real users, and which pages are the slowest?*

- **KPI cards** — percentage of sessions scoring "Good" on each of the three Core Web Vitals (LCP, CLS, INP)
- **Web Vitals Score Distribution** — grouped bar chart showing Good / Needs Improvement / Poor counts for LCP, CLS, and INP side-by-side
- **Average LCP by Page** — horizontal bar chart, color-coded green/orange/red by threshold (Good < 2500ms, Poor ≥ 4000ms)
- **Per-Page Vitals Averages table** — avg LCP, CLS, INP with inline score labels per URL

### Errors & Reliability

Answers: *what's breaking, how often, and are users getting frustrated?*

- **KPI cards** — JS errors, promise rejections, resource errors, affected sessions, and rage click incidents
- **Error Counts by Type** — bar chart (red/orange/blue per type)
- **Rage Clicks by URL** — horizontal bar chart. A "rage click incident" is any 5-second activity batch containing 3+ click sub-events — a reliable proxy for repeated frustrated clicking, since the collector flushes on a 5-second cycle
- **Top Error Messages table** — error type, message/source, URL, and occurrence count

### User Behavior

Answers: *how deeply are users reading pages, and how engaged is their mouse?*

- **KPI cards** — number of pages with scroll data, number of pages with mouse movement data
- **Avg Scroll Depth by URL** — horizontal bar chart of the average maximum scroll position (px) reached per session, grouped by URL
- **Avg Mouse Travel Distance by URL** — horizontal bar chart of average cumulative Euclidean distance (px) the mouse traveled per session per URL; higher values indicate more engaged, exploratory users
- **Scroll Depth per Page table** — per-URL breakdown of sessions, average max scroll (px), and counts of sessions that reached the 200px, 500px, and 1000px thresholds

---

## Use of AI

Claude Code (claude-sonnet-4-6) was used extensively throughout this project for implementation. Specific contributions included:

- Designing and writing the PostgreSQL queries for the report endpoints, particularly the CTEs for scroll depth (window function to get max scroll per session before averaging) and mouse travel distance (LAG to compute Euclidean distance between consecutive mousemove events)
- Scaffolding the Chart.js rendering code for each tab and wiring it to the API responses
- Writing the rage click detection query — identifying activity batches with 3+ click sub-events as a proxy for frustrated users
- Helping structure the role-based access control pattern (`allowed_sections` JSONB + `canAccessSection()`) and applying it consistently to the new Behavior tab
- Refining UA-string parsing order (Edge must be checked before Chrome because Edge's UA string contains "Chrome/")
- Implementing the three-tier user permission system (super admin, analyst, viewer), including section-based access control for analysts where each analyst can be restricted to a defined subset of report sections (traffic, performance, errors, behavior) via checkboxes in the admin panel, with viewers restricted to read-only access to saved report snapshots

**Observed value:** AI was most valuable for the database query layer. Writing correct CTEs with window functions for aggregating nested JSONB sub-events (the collector stores click, scroll, and mousemove events inside a JSONB array within a single activity row) would have taken considerably longer by hand. The AI maintained the existing code patterns accurately and caught the UA edge case without prompting.

Where it was less reliable: initial suggestions for the scroll depth query used a subquery structure that would have been much slower at scale than the CTE approach that was ultimately used. Reviewing and questioning the first draft led to a better result.

---

## Roadmap

Things we would have liked to build given more time, in rough priority order:

### Near-term
- **Date range filter** — currently all reports are all-time; a date picker would make the traffic and error tabs far more useful for spotting regressions
- **Session replay lite** — replay the sequence of pages visited within a session using the `page-enter` sub-events already captured
- **Heatmap overlay** — the click `x`/`y` coordinates are already collected; a canvas overlay on a URL screenshot would show click density without any new data collection
- **Alerting** — email or webhook notification when error rate exceeds a threshold or a new JS error type appears

### Medium-term
- **Funnel analysis** — define a sequence of URLs (e.g. product → cart → checkout) and measure drop-off at each step using session event sequences already in the database
- **Cohort retention** — group sessions by acquisition date and measure how many return on subsequent days
- **A/B test tracking** — add a `variant` field to the collector payload and split all report views by variant

### Long-term / architectural
- **Event streaming** — replace the 5-second batch flush with a WebSocket or server-sent event stream for near-real-time dashboards
- **Partitioned events table** — partition `events` by month in PostgreSQL; the table will grow quickly and query performance on `event_type` filters will degrade without it
- **Multi-tenant isolation** — currently all sites share one database; per-site schemas or row-level security would be needed to offer this as a real SaaS product

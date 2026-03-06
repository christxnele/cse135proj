# CSE 135 HW3

## Site Link
https://cse135vrc.site

## Team Members
- Victoria Timofeev
- Christine Le
- Ryan Soe

## Server Info

**IP Address:** 146.190.150.30

**SSH (Apache server) login:**
```
ssh grader@146.190.150.30
password: CSE135rocks
```

**Site login:**
```
Username: grader
Password: ilovecse135
```


## API Testing

We tested the reporting API at `https://reporting.cse135vrc.site` using `curl` with HTTP Basic Auth (`grader:ilovecse135`). We verified full CRUD operations on two endpoints: `/api/events` and `/api/sessions`. For each, we tested GET (all records and single by ID), POST to create a new record, PUT to update an existing one, and DELETE to remove it.

## collector.js — Changes Beyond the Tutorial

In collector.js, we built a full client-side analytics collector that captures pageview data (static device/browser info, navigation timing, and Web Vitals like LCP, CLS, and INP) and user activity events (clicks, scrolls, mousemoves, keypresses, idle periods, and JS errors). Data is batched in a buffer and sent to the collector endpoint using sendBeacon with a fetch fallback, flushed periodically every 5 seconds and also on page exit. We also set up session management via sessionStorage and a first-party cookie, and exposed a window._cq command queue so host pages can configure the collector before it loads.

# [Project] Employee Time Clock

> Generic documentation for the employee time clock plugin.

## Overview

[Project] Employee Time Clock consolidates essential time-tracking features into a single lightweight, modern and secure WordPress plugin.

## Features

- Live clock-in/clock-out with pause/resume.
- Client, activity, project, task and tag per entry.
- Recent task list to resume with one click.
- Weekly timesheet for employees.
- Filterable reports for admins.
- Extended CSV export.
- CSV import of entries.
- Week locking for payroll closure.
- Payroll page per month.
- Manageable clients and activities.
- Operational rules (rounding, max shift, required notes).
- WP Cron automations (auto close, flag review, digest, reminders, exceptions).
- PTO / vacation tracking.
- Entry approval flow.
- Optional IP capture and geolocation.
- Configurable holidays and non-working days.
- Absence/PTO requests with approval.
- Admin dashboard widget.
- Manual time entry from timesheet.
- WordPress coding standards: unique function and class names, nonces, sanitization and capabilities.

## How this system was built

Unlike the other three systems, this is a standalone, self-contained WordPress plugin rather than a phase that talks to an external platform. It follows a fairly conventional WordPress plugin structure: an activator handles setup, a set of includes classes each own one responsibility (time entries, clients, activities, holidays, leave, locked weeks, cron jobs, admin screens, public-facing shortcodes), and WP Cron handles the recurring automations (auto-close, digest, reminders, flag review). All data — time entries, clients, activities, PTO — lives in WordPress from the start, with no dependency on any outside service.

## Advantages and disadvantages

**Advantages**
- Fully self-contained: no external platform to configure, authenticate against, or keep in sync — it works the moment it's activated.
- Everything related to time tracking (clock in/out, breaks, timesheets, reports, payroll, PTO, CSV import/export) lives under one roof, which simplifies both usage and maintenance.
- Follows WordPress coding conventions (unique prefixes, nonces, sanitization, capability checks), so it coexists safely with other plugins.
- No network latency or external downtime can affect clock-in/out — the most time-sensitive action in the system.

**Disadvantages**
- Being self-contained also means it can't fall back on an external system — if something goes wrong, there's no secondary source of the data.
- As more time-tracking features are added, the plugin itself has to grow, which can make it harder to manage than a system split across smaller connected pieces.
- It's purpose-built for time tracking specifically, so unlike the portal/API-frontend/native-core trio, the same pattern can't be reused directly for a different kind of app.
- Data import from other tools is manual (CSV), rather than an automatic, ongoing sync.

## Requirements

- WordPress 5.8 or higher
- Tested up to 6.5
- PHP 7.4 or higher
- License: GPL-2.0-or-later

## Installation

1. Upload the `project-employee-time-clock` folder to `/wp-content/plugins/`.
2. Activate the plugin in the **Plugins** menu.
3. Go to **Employee Time Clock > Settings** to configure roles, PTO and overtime threshold.
4. Use the shortcodes `[project_etc_clock]` and `[project_etc_timesheet]` on any page.

## Shortcodes

| Shortcode | Description |
|-----------|-------------|
| `[project_etc_clock]` | Employee clock-in panel. |
| `[project_etc_timesheet]` | Weekly employee timesheet. |

## FAQ

### Can multiple time clock plugins coexist?

[Project] Employee Time Clock uses a unique prefix (`project_etc_`) and its own textdomain so it does not interfere with other plugins in the [Project] family.

## Screenshots

1. Clock-in timer with client, activity, project, task and tags.
2. Weekly employee timesheet with manual entries and approvals.
3. Admin panel with reports, CSV export and approval flow.
4. Clients, activities, operational rules and week locking management.

## Changelog

### 1.0.0
- Initial release with clock-in, breaks, timesheet, reports, PTO, CSV export, approvals, holidays, absence requests, dashboard widget and manual time entry.
- Integration: clients, activities, pause/resume, projects, tasks, tags, recent tasks, week locking, CSV import, operational rules and WP Cron automations.

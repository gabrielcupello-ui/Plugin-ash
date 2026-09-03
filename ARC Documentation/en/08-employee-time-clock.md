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

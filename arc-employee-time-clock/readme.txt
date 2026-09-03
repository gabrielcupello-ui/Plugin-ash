=== Arc Employee Time Clock ===
Contributors: arc-automation
Donate link: https://ashrivercollective.com
Tags: time clock, employee, timesheet, attendance, pto
Requires at least: 5.8
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Employee time clock with time tracking, pause, clients, activities, reports, PTO, payroll and CSV import.

== Description ==

**Arc Employee Time Clock** consolidates the essential features of the most popular time-clock plugins into a single lightweight, modern and secure plugin:

* Live Clockify-style time tracking (clock in / clock out / pause / resume).
* Client, activity, project, task and tags per entry.
* Recent tasks list to resume with one click.
* Weekly employee timesheet covering every day.
* Filterable reports for administrators.
* Extended CSV export.
* CSV import of entries.
* Week locking for payroll close.
* Payroll page by month.
* Clients and activities management.
* Operating rules (rounding, maximum shift length, required notes).
* WP Cron automations (auto-close, flag sweep, digest, reminders and exceptions).
* PTO / vacation tracking.
* Entry approval workflow.
* IP capture and optional geolocation.
* Configurable holidays and non-working days.
* Absence/PTO requests with approval.
* Dashboard widget for administrators.
* Manual time entry from the timesheet.
* Based on WordPress Coding Standards: unique function and class names, nonces, sanitization and capabilities.

== Installation ==

1. Upload the `arc-employee-time-clock` folder to `/wp-content/plugins/`.
2. Activate the plugin from the **Plugins** menu.
3. Go to **Employee Time Clock > Settings** to configure roles, PTO and the overtime threshold.
4. Use the shortcodes `[arc_etc_clock]` and `[arc_etc_timesheet]` on any page.

== Frequently Asked Questions ==

= What shortcodes does it use? =

* `[arc_etc_clock]` — employee time clock panel.
* `[arc_etc_timesheet]` — employee weekly timesheet.

= Can several time-clock plugins coexist? =

Arc Employee Time Clock uses a unique prefix (`arc_etc_`) and its own text domain so it does not interfere with other Arc family plugins (API Frontend, Portal, Intranet).

== Screenshots ==

1. Time-clock timer with client, activity, project, task and tags.
2. Employee weekly timesheet with manual entries and approvals.
3. Administration panel with reports, CSV export and approval workflow.
4. Management of clients, activities, operating rules and week locking.

== Changelog ==

= 1.0.0 =
* Initial version with time tracking, breaks, timesheet, reports, PTO, CSV export, approvals, holidays, absence requests, dashboard widget and manual time entry.
* IPC integration: clients, activities, pause/resume, projects, tasks, tags, recent tasks, week locking, CSV import, operating rules and WP Cron automations.

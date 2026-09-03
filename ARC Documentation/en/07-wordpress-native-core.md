# [Project] WordPress Native Core

> Generic documentation for the Native Core plugin.

## Overview

The [Project] WordPress Native Core is the third step in the integration roadmap. It stores [MODULE_1], [MODULE_2] and [MODULE_3] data in custom MySQL tables, with optional synchronization to [EXTERNAL_PLATFORM] / [EXTERNAL_DATA_STORE].

## Features

- Native core: custom tables for daily reports, HR applications, tasks and sync queue.
- Module registry: `Project_Native_Modules` lets you add/remove modules without touching core.
- Native dashboard with shortcode `[project_native_dashboard]`.
- [EXTERNAL_VENDOR] connector via sync queue (`project_native_sync_queue`) and a [EXTERNAL_PLATFORM] endpoint.
- Compatibility: detects and reuses `project-employee-time-clock` for the time clock module.
- REST API: `/wp-json/project-native/v1/modules` and `/wp-json/project-native/v1/stats`.
- Responsive CSS framework loaded via CDN.

## Installation

1. Copy the `project-wordpress-native` folder to `wp-content/plugins/`.
2. Activate the plugin.
3. Create a page with the shortcode `[project_native_dashboard]`.
4. Go to **[Project] Native** in the admin menu and configure:
   - [EXTERNAL_VENDOR] Sync Bridge URL ([EXTERNAL_PLATFORM] endpoint).
   - Shared API secret.
   - Enable synchronization.

## Tailwind CSS

The plugin loads a responsive CSS framework via CDN. All templates and the admin page use utility classes. `assets/css/native.css` is empty (compatibility only) to avoid conflicts with CDN-generated classes.

## How this system was built

This plugin is the third phase of the integration roadmap. It moves the actual data — daily reports, HR applications, tasks — into custom MySQL tables created and managed by WordPress itself, instead of keeping it only in the external platform. A module registry lets each data type (reports, HR, tasks, etc.) be added or removed independently. Records still relevant to the external platform are queued and pushed out on a schedule (via WP Cron) with an HMAC signature, so the external side can stay in sync without WordPress having to wait on it for every read.

## Advantages and disadvantages

**Advantages**
- WordPress owns the data directly, so reads are fast local database queries instead of network calls to another system.
- The dashboard and modules keep working even if the external platform is temporarily unreachable, since sync is queued and retried later rather than required in real time.
- The module registry makes it straightforward to add new data types without touching the core.
- Detects and reuses the standalone time-clock plugin when present, avoiding duplicate functionality.

**Disadvantages**
- Introduces a second source of truth: WordPress and the external platform must be kept in sync, and the 5-minute queue means there is always a small window of eventual-consistency lag.
- More moving parts to maintain overall — custom database tables, an activator/migration step, and a sync bridge, on top of the module logic itself.
- Business logic that previously lived entirely in the external platform now has to be partly re-implemented on the WordPress side.
- Took the longest of the four systems to build, since it does more than route or display data — it stores and manages it.

## Architecture

```
WordPress user
        ↓
[project_native_dashboard] / module shortcodes
        ↓
   Project_Native_Core (REST + UI)
        ↓
Project_Native_Modules + Project_Native_External_Bridge
        ↓
MySQL (project_native_* tables)
        ↓
Sync queue → [EXTERNAL_PLATFORM] / [EXTERNAL_DATA_STORE] (optional)
```

### Main components

- `Project_Native_Activator`: creates tables, flushes rewrite rules, handles activation/deactivation.
- `Project_Native_Modules`: module registry and ordering.
- `Project_Native_Core`: shortcodes, REST routes, assets, admin UI.
- `Project_Native_External_Bridge`: sync queue with [EXTERNAL_PLATFORM].

## Shortcodes

| Shortcode | File | Description |
|-----------|------|-------------|
| `[project_native_dashboard]` | `templates/dashboard.php` | Dashboard with stats and modules. |
| `[project_native_time_clock]` | `templates/time-clock.php` | Time clock (or delegates to `project-employee-time-clock`). |
| `[project_native_eod]` | `templates/eod.php` | Daily report form. |
| `[project_native_hr]` | `templates/hr.php` | Job application form. |
| `[project_native_tasks]` | `templates/tasks.php` | Task list. |

## REST endpoints

Namespace: `/wp-json/project-native/v1/`

| Route | Method | Description | Permissions |
|-------|--------|-------------|-------------|
| `/modules` | GET | Lists registered modules. | Logged-in user |
| `/stats` | GET | Returns dashboard statistics. | Logged-in user |

Example `/stats` response:

```json
{
  "week_hours": 32.5,
  "eod_count": 12,
  "active_tasks": 5,
  "candidates": 3
}
```

## Tables created

| Table | Purpose |
|-------|---------|
| `wp_project_native_eod_reports` | Daily end-of-day reports. |
| `wp_project_native_hr_applications` | HR applications and candidates. |
| `wp_project_native_tasks` | Tasks and projects. |
| `wp_project_native_sync_queue` | Pending sync changes to [EXTERNAL_VENDOR]. |

## Module registration (extending)

`Project_Native_Modules` lets you add modules without touching core:

```php
add_action( 'project_native_modules_init', function ( $registry ) {
    $registry->register( 'invoices', array(
        'label'         => 'Invoicing',
        'description'   => 'Invoice management',
        'icon'          => 'money-alt',
        'shortcode'     => 'project_native_invoices',
        'order'         => 50,
        'capability'    => 'read',
        'external_sync' => true,
    ) );
} );
```

The core automatically looks for `templates/invoices.php` to render the shortcode.

## External synchronization

1. Deploy a [EXTERNAL_PLATFORM] Web App with `doPost` that receives JSON.
2. Paste the URL in **[Project] Native > External Sync Bridge URL**.
3. Every change that fires `do_action( 'project_native_record_changed', $module, $record_id, $action, $data )` is enqueued.
4. A cron every 5 minutes processes the queue and sends changes to [EXTERNAL_PLATFORM].

Body sent to [EXTERNAL_PLATFORM]:

```json
{
  "timestamp": 1700000000,
  "module": "eod",
  "record_id": 123,
  "action": "create",
  "payload": { "report_date": "2026-09-03", "hours_worked": 8.5 },
  "signature": "hmac-sha256"
}
```

[EXTERNAL_PLATFORM] must verify the HMAC with the same secret configured in WordPress (or `wp_salt('auth')`).

## Hooks and filters

| Hook / Filter | Type | Description |
|---------------|------|-------------|
| `project_native_modules_init` | action | Fired when building the registry. Receives `Project_Native_Modules`. |
| `project_native_modules` | filter | Modify the final module list. |
| `project_native_dashboard_stats` | filter | Modify dashboard statistics. |
| `project_native_force_assets` | filter | Force asset loading. |
| `project_native_record_changed` | action | Fired when a record changes. Automatically enqueues in the bridge. |

## Time clock integration

If the `project-employee-time-clock` plugin is active, the dashboard uses it to calculate `week_hours`. Otherwise the `time_clock` module renders with `templates/time-clock.php`.

To report hours to the dashboard:

```php
add_filter( 'project_native_dashboard_stats', function ( $stats, $user_id ) {
    $stats['week_hours'] = 40; // calculate from your source.
    return $stats;
}, 10, 2 );
```

## Troubleshooting

| Symptom | Likely cause | Fix |
|---------|--------------|-----|
| Tables missing | Activated without rewrite flush | Deactivate and reactivate the plugin. |
| No sync to [EXTERNAL_VENDOR] | Sync disabled or URL empty | Check **[Project] Native > Sync active** and URL. |
| Empty stats | Modules inactive or no data | Verify modules are registered and active. |
| Tailwind not styling | CDN not loaded | Check connection to the CDN. |

## Changelog

### 1.0.0
- Initial native core release.
- Activator creating EOD, HR, Tasks and Sync Queue tables.
- Module registry (`Project_Native_Modules`).
- Dashboard and shortcodes with responsive CSS.
- [EXTERNAL_VENDOR] Sync Bridge with queue and HMAC.
- REST API for modules and stats.
- Detection of `project-employee-time-clock`.

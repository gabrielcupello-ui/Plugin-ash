# Ash River Collective — API Frontend for WordPress

Plugin that implements the integration **Option 2**: **WordPress as the native frontend** and **Google Apps Script / Google Sheets as the backend** through an internal REST proxy.

## Table of contents

1. [Summary](#summary)
2. [Features](#features)
3. [Installation](#installation)
4. [Tailwind CSS](#tailwind-css)
5. [Architecture](#architecture)
6. [Shortcodes](#shortcodes)
7. [Configuration](#configuration)
8. [REST Endpoints and Proxy](#rest-endpoints-and-proxy)
9. [Endpoint Registration (Extension)](#endpoint-registration-extension)
10. [Cache and Retries](#cache-and-retries)
11. [Hooks and Filters](#hooks-and-filters)
12. [Example: Adding a New App](#example-adding-a-new-app)
13. [Security](#security)
14. [Troubleshooting](#troubleshooting)
15. [Changelog](#changelog)
16. [Next Steps](#next-steps)
17. [Structure](#structure)
18. [WordPress Standards](#wordpress-standards)

## Summary

Instead of embedding apps in iframes, this plugin consumes data from Google Apps Script over REST and displays it with native WordPress forms and dashboards. Each app is registered with its endpoint, allowed actions, and API Key.

## Features

- Native WordPress shortcodes:
  - `[arc_api_time_clock]` — clock in/out form.
  - `[arc_api_eod_form]` — EOD report form.
  - `[arc_api_dashboard]` — centralized dashboard.
  - `[arc_api_tasks]` — task list with filter.
  - `[arc_api_hr]` — job application form.
- Internal REST proxy: `/wp-json/arc-api-frontend/v1/{app}/{action}`.
- GET response cache for 60 seconds and automatic invalidation on writes.
- Automatic retries with 500 ms backoff on network failures.
- WordPress nonce authentication.
- Per-app API Key to validate in Apps Script.
- Optional signed tokens for SSO.
- Draft auto-save in the EOD form (localStorage).
- Dashboard with real stats.
- Error logs when `WP_DEBUG` is enabled.

## Installation

1. Copy the `arc-wordpress-api-frontend` folder to `wp-content/plugins/`.
2. Activate the plugin.
3. Go to **Settings > ARC API Frontend**.
4. Configure each Apps Script app endpoint and its API Keys.
5. Create pages with the shortcodes.

## Tailwind CSS

The plugin loads **Tailwind CSS v4** via CDN on the frontend and wp-admin:

```html
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
```

All templates (`dashboard.php`, `eod-form.php`, `time-clock.php`, `tasks.php`, `hr.php`) and the admin forms use Tailwind utility classes. `assets/css/api-frontend.css` only keeps the message `show`/`success`/`error` states and the spinner.

## Architecture

```
WordPress User
        ↓
Shortcode (dashboard / form / tasks / hr / time clock)
        ↓
WordPress REST API  →  Arc_API_Frontend_Proxy
        ↓
wp_remote_request() → POST to Apps Script Web App (doPost)
        ↓
Google Apps Script → Google Sheets
```

Main components:

- `Arc_API_Frontend_App`: bootstrap, settings, shortcodes, admin menu.
- `Arc_API_Frontend_Endpoint_Registry`: centralized endpoint and action registry.
- `Arc_API_Frontend_Proxy`: exposes dynamic REST routes based on the registry and forwards requests to Apps Script.
- `Arc_API_Frontend_Auth`: generates signed tokens for SSO (optional).

## Shortcodes

| Shortcode | File | Description |
|-----------|------|-------------|
| `[arc_api_dashboard]` | `templates/dashboard.php` | Dashboard with stats. |
| `[arc_api_time_clock]` | `templates/time-clock.php` | Clock in/out buttons. |
| `[arc_api_eod_form]` | `templates/eod-form.php` | EOD form. |
| `[arc_api_tasks]` | `templates/tasks.php` | Task table with filter. |
| `[arc_api_hr]` | `templates/hr.php` | Application form. |

## Configuration

| Field | Description |
|-------|-------------|
| **Logo URL** | Dashboard logo URL. |
| **Allowed Roles** | Roles that can use the shortcodes. |
| **Endpoint URL** | Deployment URL (Web App) for each Apps Script app. |
| **API Key** | Shared key that Apps Script must validate. |
| **Actions** | Whitelist of allowed actions for each app (`clock_in`, `submit`, `get_tasks`, etc.). |
| **Google API Key** | Optional. For future use with a Service Account. |

## REST Endpoints and Proxy

Namespace: `/wp-json/arc-api-frontend/v1/`

The proxy registers dynamic routes:

```
/wp-json/arc-api-frontend/v1/{app}/{action}
```

For example:

```
/wp-json/arc-api-frontend/v1/eod_report/submit
/wp-json/arc-api-frontend/v1/task_app/get_tasks
```

Each request body includes:

```json
{
  "action": "submit",
  "wp_email": "user@ashrivercollective.com",
  "wp_name": "Example User",
  "wp_user_id": 42,
  "api_key": "YOUR_API_KEY"
}
```

Apps Script must expose a `doPost` that validates `api_key` and processes `action`.

## Endpoint Registration (Extension)

`Arc_API_Frontend_Endpoint_Registry` allows adding apps without modifying the proxy:

```php
add_action( 'arc_api_frontend_registry_init', function ( $registry ) {
    $registry->register_endpoint( 'invoices', array(
        'label'    => 'Invoicing',
        'endpoint' => 'https://script.google.com/macros/s/.../exec',
        'api_key'  => 'my-api-key',
        'enabled'  => true,
        'actions'  => array( 'list', 'create', 'update' ),
        'order'    => 50,
    ) );
} );
```

## Cache and Retries

- `GET` requests are cached for 60 seconds (`transients` + `wp_cache`).
- Filter `arc_api_frontend_cache_ttl` allows modifying the TTL.
- On network errors, the proxy retries up to 2 times with 500 ms wait.
- Filter `arc_api_frontend_max_retries` allows modifying the retries.
- After a successful write (`POST`), the proxy invalidates the app's cache.

## Hooks and Filters

| Hook / Filter | Type | Description |
|---------------|------|-------------|
| `arc_api_frontend_registry_init` | action | Fires when building the registry. Receives `Arc_API_Frontend_Endpoint_Registry`. |
| `arc_api_frontend_endpoints` | filter | Allows modifying the final endpoint list. |
| `arc_api_frontend_cache_ttl` | filter | Cache TTL in seconds. Parameters: `$ttl`, `$app`, `$action`, `$body`. |
| `arc_api_frontend_max_retries` | filter | Maximum number of retries. Parameters: `$retries`, `$app`, `$action`, `$body`. |
| `arc_api_frontend_force_assets` | filter | Forces asset loading. |

## Example: Adding a New App

1. Create the shortcode in `Arc_API_Frontend_App` (or use `add_shortcode`).
2. Register the endpoint on `arc_api_frontend_registry_init`.
3. Create the template at `templates/my-app.php`.
4. In the frontend call the proxy REST: `fetch(arcApiFrontend.restUrl + 'my-app/my_action')`.
5. In Apps Script implement `doPost` for `my_action`.

## Security

- Only logged-in users with an allowed role can use the shortcodes.
- Each request carries `X-WP-Nonce` and is validated by WordPress.
- Apps Script must validate `api_key`.
- For production, signed tokens are recommended (`/wp-json/arc-api-frontend/v1/auth/token`).

## Troubleshooting

| Symptom | Likely cause | Solution |
|---------|--------------|----------|
| Proxy error 403 | User without an allowed role | Review **Allowed Roles**. |
| Proxy error 404 | App or action not registered | Review the endpoint `actions`. |
| Error 502 | Apps Script not responding | Verify the deployment URL and permissions. |
| Styles not applied | Tailwind CDN blocked | Verify connection to `cdn.jsdelivr.net`. |
| Outdated data | Cache active | Wait 60 s or invalidate with a successful write. |

## Changelog

### 1.0.0
- Initial version of the API Frontend.
- Endpoint registry (`Arc_API_Frontend_Endpoint_Registry`).
- REST proxy with cache and retries.
- Shortcodes for time clock, EOD, tasks, HR, and dashboard.
- Tailwind CSS v4 via CDN.

## Next Steps

1. Run `node plantillas/update-arc-urls.js` to generate `arc-apps-config.json` with the deployment URLs.
2. The plugin will automatically import the endpoints on activation. You can also use the **Import endpoints now** button in **Settings > ARC API Frontend**.
3. Implement `doPost` in each app following `docs/apps-script-api.md` and `docs/gas-snippets/`.
4. Use `[arc_api_dashboard]` as the team home page.
5. Extend with additional forms as needed.

## Structure

```
arc-wordpress-api-frontend/
├── arc-wordpress-api-frontend.php
├── arc-apps-config.json        (auto-generated copy)
├── includes/
│   ├── class-arc-api-frontend.php
│   ├── class-arc-api-endpoint-registry.php
│   ├── class-arc-api-proxy.php
│   └── class-arc-api-auth.php
├── assets/
│   ├── css/api-frontend.css
│   └── js/api-frontend.js
├── templates/
│   ├── dashboard.php
│   ├── eod-form.php
│   ├── time-clock.php
│   └── tasks.php
│   └── hr.php
├── docs/
│   ├── apps-script-api.md
│   └── gas-snippets/
│       ├── time-clock-api.gs
│       ├── eod-report-api.gs
│       ├── hr-api.gs
│       └── task-app-api.gs
└── readme.txt
```

## WordPress Standards

- Plugin headers ready for WordPress.org (`readme.txt`, `uninstall.php`, `Domain Path`).
- REST endpoints with `permission_callback` and input sanitization.
- Cache with transients and cleanup on uninstall.
- `arc_` prefix on all classes and functions.

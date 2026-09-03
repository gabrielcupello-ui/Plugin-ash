# [Project] WordPress API Frontend

> Generic documentation for the API Frontend plugin.

## Overview

The [Project] WordPress API Frontend is the second step in the integration roadmap. It replaces iframe embedding with native WordPress forms and dashboards while keeping the [EXTERNAL_PLATFORM] / [EXTERNAL_DATA_STORE] backend.

## Features

- Native WordPress shortcodes:
  - `[project_api_time_clock]` — clock in/out form.
  - `[project_api_eod_form]` — daily report form.
  - `[project_api_dashboard]` — centralized dashboard.
  - `[project_api_tasks]` — task list with filter.
  - `[project_api_hr]` — job application form.
- Internal REST proxy: `/wp-json/project-api-frontend/v1/{app}/{action}`.
- 60-second GET cache and automatic invalidation on writes.
- Automatic retries with 500 ms backoff on network failure.
- WordPress nonce authentication.
- Per-app API key for [EXTERNAL_PLATFORM] validation.
- Optional signed tokens for SSO.
- Autosave drafts in daily report form (localStorage).
- Dashboard with real stats.
- Error logging when `WP_DEBUG` is enabled.

## Installation

1. Copy the `project-wordpress-api-frontend` folder to `wp-content/plugins/`.
2. Activate the plugin.
3. Go to **Settings > [Project] API Frontend**.
4. Configure each [EXTERNAL_PLATFORM] app endpoint URL and API key.
5. Create pages with the shortcodes.

## Tailwind CSS

The plugin loads a responsive CSS framework via CDN in frontend and wp-admin. Utility classes are used in all templates; `assets/css/api-frontend.css` only keeps message states and the spinner.

## How this system was built

This plugin is the second phase of the integration roadmap. It keeps the same external backend as the portal phase, but replaces the iframe with native WordPress shortcodes, forms and a REST proxy. Every request from a shortcode goes through a WordPress REST route, which is forwarded server-side to the external platform's web app; responses are cached briefly and retried automatically on network failure. This removes the need for iframes while the underlying data and business logic remain exactly where they were before.

## Advantages and disadvantages

**Advantages**
- Native WordPress look and feel — forms, dashboards and tables render as part of the site instead of inside a framed window.
- No data migration required: the external backend keeps functioning exactly as before.
- Built-in caching and automatic retries make the connection to the external platform more resilient to slow responses or transient failures.
- Incremental improvement over the portal phase without a full rewrite.

**Disadvantages**
- Every read or write still has to cross the network to the external platform, so overall responsiveness is bound by that platform's latency and uptime.
- The 60-second cache can serve slightly stale data right after a change made elsewhere.
- Each connected app needs its own API key and endpoint configuration, adding an operational/maintenance step whenever an app is added or changed.

## Architecture

```
WordPress user
        ↓
Shortcode (dashboard / form / tasks / hr / time clock)
        ↓
WordPress REST API → Project_API_Frontend_Proxy
        ↓
wp_remote_request() → POST to [EXTERNAL_PLATFORM] Web App (doPost)
        ↓
[EXTERNAL_PLATFORM] → [EXTERNAL_DATA_STORE]
```

### Main components

- `Project_API_Frontend_App`: bootstrap, settings, shortcodes, admin menu.
- `Project_API_Frontend_Endpoint_Registry`: centralized endpoint and action registry.
- `Project_API_Frontend_Proxy`: exposes dynamic REST routes and forwards requests to [EXTERNAL_PLATFORM].
- `Project_API_Frontend_Auth`: generates signed tokens for SSO (optional).

## Shortcodes

| Shortcode | File | Description |
|-----------|------|-------------|
| `[project_api_dashboard]` | `templates/dashboard.php` | Dashboard with stats. |
| `[project_api_time_clock]` | `templates/time-clock.php` | Clock in/out buttons. |
| `[project_api_eod_form]` | `templates/eod-form.php` | Daily report form. |
| `[project_api_tasks]` | `templates/tasks.php` | Task table with filter. |
| `[project_api_hr]` | `templates/hr.php` | Job application form. |

## Configuration

| Field | Description |
|-------|-------------|
| **Logo URL** | Logo URL on the dashboard. |
| **Allowed roles** | Roles that can use the shortcodes. |
| **Endpoint URL** | Deployment URL of each [EXTERNAL_PLATFORM] Web App. |
| **API Key** | Shared key that [EXTERNAL_PLATFORM] must validate. |
| **Actions** | Whitelist of allowed actions per app. |
| **Service API Key** | Optional. For future service account usage. |

## REST endpoints and proxy

Namespace: `/wp-json/project-api-frontend/v1/`

The proxy registers dynamic routes:

```
/wp-json/project-api-frontend/v1/{app}/{action}
```

Example:

```
/wp-json/project-api-frontend/v1/eod_report/submit
/wp-json/project-api-frontend/v1/task_app/get_tasks
```

Each request body includes:

```json
{
  "action": "submit",
  "wp_email": "user@[DOMAIN]",
  "wp_name": "Example User",
  "wp_user_id": 42,
  "api_key": "YOUR_API_KEY"
}
```

[EXTERNAL_PLATFORM] must expose a `doPost` that validates `api_key` and processes `action`.

## Endpoint registry (extending)

`Project_API_Frontend_Endpoint_Registry` lets you add apps without modifying the proxy:

```php
add_action( 'project_api_frontend_registry_init', function ( $registry ) {
    $registry->register_endpoint( 'invoices', array(
        'label'    => 'Invoicing',
        'endpoint' => 'https://script.[EXTERNAL_VENDOR].com/.../exec',
        'api_key'  => 'my-api-key',
        'enabled'  => true,
        'actions'  => array( 'list', 'create', 'update' ),
        'order'    => 50,
    ) );
} );
```

## Cache and retries

- GET requests are cached for 60 seconds (`transients` + `wp_cache`).
- Filter `project_api_frontend_cache_ttl` to change TTL.
- On network errors the proxy retries up to 2 times with 500 ms delay.
- Filter `project_api_frontend_max_retries` to change retries.
- After a successful write (`POST`) the proxy invalidates the app cache.

## Hooks and filters

| Hook / Filter | Type | Description |
|---------------|------|-------------|
| `project_api_frontend_registry_init` | action | Fired when building the registry. Receives `Project_API_Frontend_Endpoint_Registry`. |
| `project_api_frontend_endpoints` | filter | Modify the final endpoint list. |
| `project_api_frontend_cache_ttl` | filter | Cache TTL in seconds. Parameters: `$ttl`, `$app`, `$action`, `$body`. |
| `project_api_frontend_max_retries` | filter | Max retries. Parameters: `$retries`, `$app`, `$action`, `$body`. |
| `project_api_frontend_force_assets` | filter | Force asset loading. |

## Example: adding a new app

1. Create the shortcode in `Project_API_Frontend_App` (or use `add_shortcode`).
2. Register the endpoint in `project_api_frontend_registry_init`.
3. Create the template in `templates/my-app.php`.
4. In the frontend call the REST proxy: `fetch(projectApiFrontend.restUrl + 'my-app/my_action')`.
5. In [EXTERNAL_PLATFORM] implement `doPost` for `my_action`.

## Security

- Only logged-in users with allowed roles can use shortcodes.
- Each request carries `X-WP-Nonce` and is validated by WordPress.
- [EXTERNAL_PLATFORM] must validate `api_key`.
- For production, signed tokens are recommended (`/wp-json/project-api-frontend/v1/auth/token`).

## Troubleshooting

| Symptom | Likely cause | Fix |
|---------|--------------|-----|
| 403 on proxy | User without allowed role | Review **Allowed roles**. |
| 404 on proxy | App or action not registered | Review endpoint `actions`. |
| 502 | [EXTERNAL_PLATFORM] not responding | Check deployment URL and permissions. |
| Styles not applied | CDN blocked | Check connection to the CDN. |
| Stale data | Cache active | Wait 60 s or invalidate with a successful write. |

## Changelog

### 1.0.0
- Initial API Frontend release.
- Endpoint registry (`Project_API_Frontend_Endpoint_Registry`).
- REST proxy with cache and retries.
- Shortcodes for time clock, daily report, tasks, HR and dashboard.
- Responsive CSS framework via CDN.

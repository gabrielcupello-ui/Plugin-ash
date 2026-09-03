# [Project] WordPress Portal

> Generic documentation for the Portal plugin.

## Overview

The [Project] WordPress Portal is the first step in the integration roadmap. It centralizes access to existing [EXTERNAL_PLATFORM] apps in a single WordPress portal using iframes or direct links, without rewriting the app logic.

## Features

- Centralized portal for team apps.
- Collapsible sidebar, header with user info, dashboard cards and quick access.
- Protected virtual route `/project-portal/`.
- Shortcode `[project_portal]`.
- Dynamic app registry.
- SSO bridge with [EXTERNAL_PLATFORM] via signed HMAC tokens.
- [EXTERNAL_VENDOR] Web Apps open inside iframe or new tab.
- WordPress role-based access control.
- `?wp_user=email` passthrough to embedded apps.
- Responsive CSS framework loaded via CDN.

## Installation

1. Copy the `project-wordpress-portal` folder to `wp-content/plugins/`.
2. Activate the plugin from **Plugins** in wp-admin.
3. Go to **Settings > [Project] Portal** and paste the deployment URL of each app.
4. Save permalinks (**Settings > Permalinks > Save**) to activate the `/project-portal/` route.

## Usage

### Option A: Shortcode

Add the shortcode to any page:

```
[project_portal]
```

### Option B: Virtual route

Visit directly:

```
https://[DOMAIN]/project-portal/
```

The `/project-portal/` route is protected by login and roles.

## Configuration

| Field | Description |
|-------|-------------|
| **Portal title** | Name shown in the header. |
| **Welcome title** | Title of the home screen. |
| **Welcome description** | Subtitle of the home screen. |
| **Help email** | Help button link in the header. |
| **Logo URL** | Logo image in the sidebar. If empty, title is shown. |
| **Allowed roles** | WordPress roles that can access. Administrators always can. |
| **Pass WordPress email** | Adds `?wp_user=email` to embedded app URLs. |
| **Login redirect URL** | URL to send unauthenticated users. |
| **Logout URL** | Custom logout URL. |
| **SSO integration** | Shared [EXTERNAL_PLATFORM] endpoint and API Secret for signed tokens. |

## Tailwind CSS

The frontend and admin pages load a responsive CSS framework via CDN:

```html
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
```

All templates (`portal-shortcode.php`) and helpers use utility classes. `assets/css/portal.css` keeps only state helpers that the CDN does not generate.

## How this system was built

This plugin was built as the first phase of a broader integration roadmap. Rather than rewriting the team's existing external web apps, it wraps them in a single WordPress entry point: a shortcode and a protected virtual route render a sidebar/dashboard shell, and each app opens inside an iframe (or a new tab when framing isn't possible). Access, branding and navigation are handled natively in WordPress, while all business logic and data stay untouched in the original external apps. An SSO bridge (HMAC-signed tokens) is layered on top so users don't have to log in twice.

## Advantages and disadvantages

**Advantages**
- Fastest of the four systems to build and deploy — no existing app logic had to be rewritten.
- Low risk: because the external apps were not modified, a portal failure does not affect the apps themselves.
- Centralizes access, branding and permissions in one place, using WordPress's own roles and login.
- SSO bridge removes the need to log into each app separately.

**Disadvantages**
- Still dependent on iframes for embedding, which some external tools block via `X-Frame-Options` or a strict content-security-policy, forcing a "new tab" fallback with a less integrated feel.
- No native WordPress UI — the experience inside each iframe still looks and behaves like a separate app.
- No data lives in WordPress itself; the portal has nothing to fall back on if the external platform is unreachable.

## Architecture

```
WordPress user
        ↓
[project_portal] shortcode  →  /project-portal/ virtual route
        ↓
  Project_Portal (singleton)
        ↓
Project_Portal_App_Registry + Project_Portal_Router + Project_Portal_External_Auth_Bridge
        ↓
templates/portal-shortcode.php
        ↓
iframe / new tab  →  [EXTERNAL_PLATFORM] Web Apps
```

- `Project_Portal`: bootstrap, settings, shortcode, route and admin menu.
- `Project_Portal_App_Registry`: centralized and ordered app registry.
- `Project_Portal_Router`: intercepts `/project-portal/` and renders the template.
- `Project_Portal_External_Auth_Bridge`: REST endpoints for SSO with [EXTERNAL_PLATFORM].

## Shortcodes

| Shortcode | Description |
|-----------|-------------|
| `[project_portal]` | Renders the full portal. |

## REST endpoints

Namespace: `/wp-json/project-portal/v1/`

| Route | Method | Description | Permissions |
|-------|--------|-------------|-------------|
| `/auth/user` | GET | Returns current user data for SSO. | Logged-in user |
| `/auth/token` | POST | Generates a temporary signed token. | Logged-in user |

Example `/auth/user` response:

```json
{
  "email": "user@[DOMAIN]",
  "name": "Example User",
  "display_name": "Example User",
  "roles": ["editor"],
  "timestamp": 1700000000,
  "signature": "..."
}
```

## App registration (extending)

The portal uses `Project_Portal_App_Registry` so apps are not hard-coded. You can register additional apps from another plugin or `functions.php`:

```php
add_action( 'project_portal_registry_init', function ( $registry ) {
    $registry->register_app( 'wiki', array(
        'label'       => 'Wiki',
        'url'         => 'https://wiki.[DOMAIN]',
        'icon'        => 'book',
        'target'      => 'new_tab',
        'description' => 'Internal documentation',
        'order'       => 25,
        'capability'  => 'read',
    ) );
} );
```

Supported targets: `iframe`, `new_tab`, `modal`, `ajax`.

## Hooks and filters

| Hook / Filter | Type | Description |
|---------------|------|-------------|
| `project_portal_registry_init` | action | Fired when building the registry. Receives `Project_Portal_App_Registry`. |
| `project_portal_registered_apps` | filter | Modify the final app list. |
| `project_portal_force_assets` | filter | Force asset loading. |
| `project_portal_can_access` | filter | Override portal access. |
| `project_portal_before_render` | action | Fired before including the template. |

### Example: add an external link

```php
add_filter( 'project_portal_registered_apps', function ( $apps ) {
    $apps['drive'] = array(
        'label'       => 'Shared Drive',
        'url'         => 'https://drive.[EXTERNAL_VENDOR].com',
        'icon'        => 'file-text',
        'target'      => 'new_tab',
        'description' => 'Quick access to team drive',
        'order'       => 15,
        'capability'  => 'read',
        'enabled'     => true,
    );
    return $apps;
} );
```

## Auto-configuration

- `templates/project-apps-config.json` contains the `scriptId` and paths of each app.
- `templates/update-project-urls.js` uses `clasp deployments` to read the URLs and update the JSON.
- The JSON is copied to both plugins and automatically imported to WordPress.
- In **Settings > [Project] Portal** the **Import URLs now** button is available.

## SSO integration with [EXTERNAL_PLATFORM]

1. Deploy a [EXTERNAL_PLATFORM] Web App with `doGet`/`doPost`.
2. Configure **Shared [EXTERNAL_PLATFORM] endpoint URL** and **API Secret**.
3. The portal generates HMAC-SHA256 signed tokens.
4. [EXTERNAL_PLATFORM] validates the signature using the same secret (or `wp_salt('auth')` if empty).
5. See `docs/external-auth-bridge.md` for details.

## Troubleshooting

| Symptom | Likely cause | Fix |
|---------|--------------|-----|
| iframe error | X-Frame-Options or [EXTERNAL_VENDOR] CSP | Use target `new_tab` or configure CSP. |
| Portal not visible | Not logged in or no permissions | Review roles and login redirect. |
| Tailwind not styling | CDN not loaded / no connection | Check that `https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4` is accessible. |
| App not in menu | `enabled = false` or missing URL | Configure the URL in Settings > [Project] Portal. |

## Changelog

### 1.0.0
- Initial portal release.
- App registry (`Project_Portal_App_Registry`).
- Home dashboard with cards and quick access.
- Collapsible sidebar and virtual route `/project-portal/`.
- [EXTERNAL_PLATFORM] authentication bridge with signed tokens.
- Responsive CSS framework via CDN.

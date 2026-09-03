# Ash River Collective — Integrated WordPress Portal

WordPress plugin that centralizes access to the ARC team's Google Apps Script apps in a single visual portal:

- **IPC Time Clock**
- **Arc EOD Report**
- **Arc Human Resources**
- **Arc Task App**

Inspired by the architecture of the **Intranet ARC** plugin (`Plugin ash/intranet`), it offers a collapsible sidebar, a header with the current user, a quick-access dashboard, and an authentication bridge with Google Apps Script ready for SSO.

## Table of contents

1. [Overview](#overview)
2. [Installation](#installation)
3. [Usage](#usage)
4. [Configuration](#configuration)
5. [Tailwind CSS](#tailwind-css)
6. [Architecture](#architecture)
7. [Shortcodes](#shortcodes)
8. [REST endpoints](#rest-endpoints)
9. [App registration (extending)](#app-registration-extending)
10. [Hooks and filters](#hooks-and-filters)
11. [Auto-configuration](#auto-configuration)
12. [SSO integration with Google Apps Script](#sso-integration-with-google-apps-script)
13. [Troubleshooting](#troubleshooting)
14. [Changelog](#changelog)
15. [Next steps](#next-steps)

## Overview

This plugin acts as **Option 1**: a centralized portal inside WordPress. Existing Google Apps Script apps are embedded via iframes or links, without rewriting their logic. The plugin handles authentication, roles, and a dynamic sidebar menu.

## Installation

1. Copy the `arc-wordpress-portal` folder into `wp-content/plugins/`.
2. Activate the plugin from **Plugins** in wp-admin.
3. Go to **Settings > ARC Portal** and paste the deployment URLs for each app.
4. Save permalinks (**Settings > Permalinks > Save**) to activate the `/arc-portal/` route.

## Usage

### Option A: Shortcode

Add the shortcode to any page:

```
[arc_portal]
```

### Option B: Virtual route

Visit directly:

```
https://yourdomain.com/arc-portal/
```

The `/arc-portal/` route is protected by login and roles.

## Configuration

| Field | Description |
|-------|-------------|
| **Portal title** | Name shown in the header. |
| **Welcome title** | Title of the home screen. |
| **Welcome description** | Subtitle of the home screen. |
| **Help email** | Help button link in the header. |
| **Logo URL** | Logo image in the sidebar. If empty, the title is shown. |
| **Allowed roles** | WordPress roles that can access. Administrators always can. |
| **Pass WordPress email** | Adds `?wp_user=email` to the embedded app URLs. |
| **Login redirect URL** | URL to send unauthenticated users. |
| **Logout URL** | Custom logout URL. |
| **SSO integration** | Shared GAS endpoint and API Secret for signed tokens. |

## Tailwind CSS

The frontend and admin pages load **Tailwind CSS v4** via CDN:

```html
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
```

All templates (`portal-shortcode.php`) and denied-access helpers use Tailwind utility classes. `assets/css/portal.css` only keeps state helpers (`is-active`, `hide`, `arc-sidebar-collapsed`) that the CDN does not generate.

## Architecture

```
WordPress user
        ↓
[arc_portal] shortcode  →  /arc-portal/ virtual route
        ↓
  Arc_Portal (singleton)
        ↓
Arc_Portal_App_Registry + Arc_Portal_Router + Arc_Portal_GAS_Auth_Bridge
        ↓
templates/portal-shortcode.php
        ↓
iframe / new tab  →  Google Apps Script Web Apps
```

- `Arc_Portal`: bootstrap, settings, shortcode, route and admin menu.
- `Arc_Portal_App_Registry`: centralized and ordered app registry.
- `Arc_Portal_Router`: intercepts `/arc-portal/` and renders the template.
- `Arc_Portal_GAS_Auth_Bridge`: REST endpoints for SSO with Google Apps Script.

## Shortcodes

| Shortcode | Description |
|-----------|-------------|
| `[arc_portal]` | Renders the full portal. |

## REST endpoints

Namespace: `/wp-json/arc-portal/v1/`

| Route | Method | Description | Permissions |
|-------|--------|-------------|-------------|
| `/auth/user` | GET | Returns current user data for SSO. | Logged-in user |
| `/auth/token` | POST | Generates a temporary signed token. | Logged-in user |

Example `/auth/user` response:

```json
{
  "email": "user@ashrivercollective.com",
  "name": "Example User",
  "display_name": "Example User",
  "roles": ["editor"],
  "timestamp": 1700000000,
  "signature": "..."
}
```

## App registration (extending)

The portal uses `Arc_Portal_App_Registry` so apps are not hard-coded. You can register additional apps from another plugin or `functions.php`:

```php
add_action( 'arc_portal_registry_init', function ( $registry ) {
    $registry->register_app( 'wiki', array(
        'label'       => 'Wiki ARC',
        'url'         => 'https://wiki.ashrivercollective.org',
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
| `arc_portal_registry_init` | action | Fired when building the registry. Receives `Arc_Portal_App_Registry`. |
| `arc_portal_registered_apps` | filter | Modify the final app list. |
| `arc_portal_force_assets` | filter | Force asset loading. |
| `arc_portal_can_access` | filter | Override portal access. |
| `arc_portal_before_render` | action | Fired before including the template. |

### Example: add an external link

```php
add_filter( 'arc_portal_registered_apps', function ( $apps ) {
    $apps['drive'] = array(
        'label'       => 'Shared Drive',
        'url'         => 'https://drive.google.com',
        'icon'        => 'file-text',
        'target'      => 'new_tab',
        'description' => 'Quick access to the team drive',
        'order'       => 15,
        'capability'  => 'read',
        'enabled'     => true,
    );
    return $apps;
} );
```

## Auto-configuration

- `plantillas/arc-apps-config.json` contains the `scriptId` and paths of each app.
- `plantillas/update-arc-urls.js` uses `clasp deployments` to read the URLs and update the JSON.
- The JSON is copied to both plugins and automatically imported into WordPress.
- In **Settings > ARC Portal** the **Import URLs now** button is available.

## SSO integration with Google Apps Script

1. Deploy a Web App in Apps Script with `doGet`/`doPost`.
2. Configure **Shared GAS endpoint URL** and **API Secret**.
3. The portal generates HMAC-SHA256 signed tokens.
4. Apps Script validates the signature using the same secret (or `wp_salt('auth')` if left empty).
5. See `docs/gas-auth-bridge.md` for details.

## Troubleshooting

| Symptom | Likely cause | Fix |
|---------|--------------|-----|
| The iframe shows an error | X-Frame-Options or Google CSP | Use target `new_tab` or configure CSP. |
| The portal is not visible | Not logged in or no permissions | Check roles and login redirect. |
| Tailwind styles are not applied | CDN not loaded / no connection | Verify that `https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4` is accessible. |
| App does not appear in the menu | `enabled = false` or missing URL | Configure the URL in Settings > ARC Portal. |

## Changelog

### 1.0.0
- Initial portal release.
- App registry (`Arc_Portal_App_Registry`).
- Home dashboard with cards and quick access.
- Collapsible sidebar and virtual route `/arc-portal/`.
- GAS authentication bridge with signed tokens.
- Tailwind CSS v4 via CDN.

## Next steps

1. Run `node plantillas/update-arc-urls.js` to generate `arc-apps-config.json` with the deployment URLs.
2. The plugin will automatically import the file when activated. You can also use the **Import URLs now** button in **Settings > ARC Portal**.
3. Test the shortcode and the `/arc-portal/` route.
4. `IPC Time Clock` already has the patch applied: run `clasp push` in `plantillas/IPC Time Clock` to deploy it.
5. Apply the changes from `docs/apps-integration.md` in `Arc EOD Report`, `Arc Human Resources` and `Arc Task App`.
6. Implement real SSO following `docs/gas-auth-bridge.md` if you want unified authentication.

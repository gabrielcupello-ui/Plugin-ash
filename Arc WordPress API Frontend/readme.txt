=== Ash River Collective — API Frontend ===
Contributors:      arc-automation
Donate link:       https://ashrivercollective.com
Tags:              google-apps-script, rest-api, dashboard, forms, automation
Requires at least: 5.8
Tested up to:      6.6
Requires PHP:      7.4
Stable tag:        1.1.0
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Frontend nativo de WordPress que consume y envía datos a Google Apps Script.

== Description ==

**Ash River Collective — API Frontend** implementa la Opción 2 de integración: WordPress como frontend, Google Apps Script / Sheets como backend.

* Shortcodes nativos: `[arc_api_dashboard]`, `[arc_api_time_clock]`, `[arc_api_eod_form]`, `[arc_api_tasks]`.
* Proxy REST interno hacia Apps Script.
* Autenticación por nonce y API Key por app.
* Caché de lecturas e invalidación automática en escrituras.
* Tokens firmados opcionales para SSO.

== Installation ==

1. Upload the `arc-wordpress-api-frontend` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Optionally run `node plantillas/update-arc-urls.js` to generate `arc-apps-config.json`; the plugin will auto-import it.
4. Go to **Settings > ARC API Frontend** to verify or configure the Apps Script endpoints.
5. Implement `doPost` in each app following `docs/apps-script-api.md`.

== Frequently Asked Questions ==

= Does it replace the existing Apps Script apps? =
No. It creates a WordPress frontend while keeping logic and storage in Google Workspace.

= How is data sent securely? =
All front-end requests hit WordPress REST endpoints first. WordPress forwards them to Apps Script with an API Key.

== Changelog ==

= 1.1.0 =
* Added response caching and auto-invalidation.
* Added dashboard stats, task filter and EOD draft auto-save.
* Added signed-token auth endpoint.

= 1.0.0 =
* Initial release.

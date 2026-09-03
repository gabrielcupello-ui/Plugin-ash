=== Ash River Collective — Portal Integrado ===
Contributors:      arc-automation
Donate link:       https://ashrivercollective.com
Tags:              portal, google-apps-script, intranet, iframe, sso
Requires at least: 5.8
Tested up to:      6.6
Requires PHP:      7.4
Stable tag:        1.1.0
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Portal centralizado en WordPress que agrupa las apps de Google Apps Script del equipo ARC.

== Description ==

**Ash River Collective — Portal Integrado** centraliza el acceso a las aplicaciones internas del equipo ARC:

* IPC Time Clock
* Arc EOD Report
* Arc Human Resources
* Arc Task App

Incluye sidebar colapsable, header con información del usuario, navegación por iframe, una pantalla de inicio con cards y un puente de autenticación con Google Apps Script.

== Installation ==

1. Upload the `arc-wordpress-portal` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Optionally run `node plantillas/update-arc-urls.js` to generate `arc-apps-config.json`; the plugin will auto-import it.
4. Go to **Settings > ARC Portal** to verify or paste the Web App URLs for each app.
5. Save permalinks (**Settings > Permalinks > Save**) to enable the `/arc-portal/` route.

== Frequently Asked Questions ==

= How do I configure an app? =
Go to **Settings > ARC Portal**, paste the Google Apps Script Web App URL and, optionally, set the app to open in a new tab.

= Can I pass the WordPress email to the embedded apps? =
Yes. Enable the option "Pasar email de WordPress a las apps" to append `?wp_user=email` to each iframe URL.

== Changelog ==

= 1.1.0 =
* Added home dashboard with app cards.
* Added per-app iframe/new tab target option.
* Added iframe load error fallback.
* Added IPC Time Clock wp_user integration.

= 1.0.0 =
* Initial release.

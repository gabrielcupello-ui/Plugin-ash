# Integration with the ARC apps

This plugin is designed to group the four apps that live in `CascadeProjects/Plugins/plantillas`:

- `IPC Time Clock`
- `Arc EOD Report`
- `Arc Human Resources`
- `Arc Task App`

Each one has its own authentication mechanism. This document explains how to configure them in the portal and which changes are already applied (or pending) for transparent access from WordPress.

## 1. IPC Time Clock

### Status

**Patched.** The local files already contain the changes; only `clasp push` is missing.

### Portal configuration

1. Go to **Settings > ARC Portal**.
2. Paste the deployment URL (`Deploy > Web app`) into the `time_clock` field.
3. Enable **Pass WordPress email to the apps**.

### What the patch does

- `Web.js` receives `?wp_user=email` and passes it to the template.
- `Auth.js` accepts `wpUser` as an alternative to `Session.getActiveUser()`.
- `Index.html` injects `window.WP_USER` and sends it in `bootstrap()` and `signIn()`.
- `Web.js` allows iframe with `setXFrameOptionsMode(ALLOWALL)`.

With this, an employee accessing from WordPress will see their name preselected and can enter without a PIN if their email matches the `Employees` sheet.

---

## 2. Arc EOD Report

### Status

**Patched.** The changes are in `plantillas/Arc EOD Report/`; `clasp push` is still needed.

### Portal configuration

1. Paste the deployment URL into the `eod_report` field.
2. Enable **Pass WordPress email to the apps**.

### What the patch does

- `Main.js` receives `?wp_user=email` and passes it to the template as `wpUser`.
- `index.html` injects `window.WP_USER`.
- `Scripts.html` preloads the `nombreMiembro` field with the WordPress email and leaves it read-only.
- `Main.js` already allows iframe with `setXFrameOptionsMode(ALLOWALL)`.

The EOD form is public; the admin panel still requires an administrator login.

---

## 3. Arc Human Resources

### Status

**Patched for iframe + email preload.** The changes are in `plantillas/Arc Human Resources/`; `clasp push` is still needed.

### Portal configuration

1. Paste the deployment URL into the `hr` field.
2. Enable **Pass WordPress email to the apps**.

### What the patch does

- `Main.js` receives `?wp_user=email` and passes it to the template as `wpUser`.
- `index.html` injects `window.WP_USER`.
- `Main.js` allows iframe with `setXFrameOptionsMode(ALLOWALL)`.
- `Scripts.html` preloads the applicant `correo` field with the WordPress email.

The admin panel still requires its own authentication.

---

## 4. Arc Task App

### Status

**Partially patched.** `doGet` accepts `wp_user` and the page loads in an iframe, but subsequent operations still use `Session.getActiveUser()`.

### Recommended portal configuration

By default, `task_app` is configured to open in a **new tab** because the app requires an active Google session to modify data. To use it embedded, the user must have an active Google session in the browser.

1. Paste the deployment URL into the `task_app` field.
2. Enable **Pass WordPress email to the apps**.

### What the patch does

- `Main.js` receives `?wp_user=email` and uses it in `doGet`.
- `Auth.js` adds `getEffectiveUser_(wpUser)` as a fallback for `currentEmail_()`.
- `index.html` injects `window.WP_USER` and `getInitialData()` receives it.
- `Main.js` allows iframe with `setXFrameOptionsMode(ALLOWALL)`.

Full SSO for all operations (create/edit tasks) requires a larger refactor or token-based authentication.

---

## Iframe considerations

Google Apps Script, by default, does not allow a Web App to be loaded in an iframe. All apps now use:

```javascript
.setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL)
```

## Quick summary

| App | Current login | Useful parameter | Applied change |
|-----|---------------|------------------|----------------|
| IPC Time Clock | Name + PIN / Google | `wp_user` | Automatic iframe login |
| Arc EOD Report | Public / Admin with pass | `wp_user` | Email preloaded in form |
| Arc Human Resources | Public / Admin with session | `wp_user` | Email preloaded in form |
| Arc Task App | Google Account | `wp_user` | Initial load with `wp_user`; operations require Google login |

## Deployment

After any change to `plantillas/`, run:

```bash
clasp push
```

Then in Apps Script, create a **Web app** deployment and copy the URL into **Settings > ARC Portal**.

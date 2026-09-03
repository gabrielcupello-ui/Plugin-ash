# Integración con las apps de ARC

Este plugin está pensado para agrupar las cuatro apps que viven en `CascadeProjects/Plugins/plantillas`:

- `IPC Time Clock`
- `Arc EOD Report`
- `Arc Human Resources`
- `Arc Task App`

Cada una tiene su propio mecanismo de autenticación. A continuación se indica cómo configurarlas en el portal y qué cambios están ya aplicados (o pendientes) para un acceso transparente desde WordPress.

## 1. IPC Time Clock

### Estado

**Parcheado.** Los archivos locales ya tienen los cambios; solo falta `clasp push`.

### Configuración en el portal

1. Ve a **Ajustes > ARC Portal**.
2. Pega la URL de despliegue (`Deploy > Web app`) en el campo `time_clock`.
3. Activa **Pasar email de WordPress a las apps**.

### Qué hace el parche

- `Web.js` recibe `?wp_user=email` y lo pasa a la plantilla.
- `Auth.js` acepta `wpUser` como alternativa a `Session.getActiveUser()`.
- `Index.html` inyecta `window.WP_USER` y lo envía en `bootstrap()` y `signIn()`.
- `Web.js` permite iframe con `setXFrameOptionsMode(ALLOWALL)`.

Con esto, un empleado que acceda desde WordPress verá su nombre preseleccionado y podrá entrar sin PIN si su email coincide con la hoja `Employees`.

---

## 2. Arc EOD Report

### Estado

**Parcheado.** Los cambios están en `plantillas/Arc EOD Report/`; falta `clasp push`.

### Configuración en el portal

1. Pega la URL de despliegue en el campo `eod_report`.
2. Activa **Pasar email de WordPress a las apps**.

### Qué hace el parche

- `Main.js` recibe `?wp_user=email` y lo pasa al template como `wpUser`.
- `index.html` inyecta `window.WP_USER`.
- `Scripts.html` precarga el campo `nombreMiembro` con el email de WordPress y lo deja de solo lectura.
- `Main.js` ya permite iframe con `setXFrameOptionsMode(ALLOWALL)`.

El formulario de EOD es público; el panel de admin sigue requiriendo login de administrador.

---

## 3. Arc Human Resources

### Estado

**Parcheado para iframe + precarga de email.** Los cambios están en `plantillas/Arc Human Resources/`; falta `clasp push`.

### Configuración en el portal

1. Pega la URL de despliegue en el campo `hr`.
2. Activa **Pasar email de WordPress a las apps**.

### Qué hace el parche

- `Main.js` recibe `?wp_user=email` y lo pasa al template como `wpUser`.
- `index.html` inyecta `window.WP_USER`.
- `Main.js` permite iframe con `setXFrameOptionsMode(ALLOWALL)`.
- `Scripts.html` precarga el campo `correo` del aplicante con el email de WordPress.

El panel de admin sigue requiriendo autenticación propia.

---

## 4. Arc Task App

### Estado

**Parcheado parcialmente.** `doGet` acepta `wp_user` y la página se carga en iframe, pero las operaciones posteriores siguen usando `Session.getActiveUser()`.

### Configuración recomendada en el portal

Por defecto, `task_app` está configurado para abrirse en **una nueva pestaña** porque la app requiere una sesión activa de Google para modificar datos. Para usarla embebida, el usuario debe tener sesión de Google activa en el navegador.

1. Pega la URL de despliegue en el campo `task_app`.
2. Activa **Pasar email de WordPress a las apps**.

### Qué hace el parche

- `Main.js` recibe `?wp_user=email` y lo usa en `doGet`.
- `Auth.js` añade `getEffectiveUser_(wpUser)` como fallback de `currentEmail_()`.
- `index.html` inyecta `window.WP_USER` y `getInitialData()` lo recibe.
- `Main.js` permite iframe con `setXFrameOptionsMode(ALLOWALL)`.

Para un SSO completo en todas las operaciones (crear/editar tareas) se requiere un refactor mayor o autenticación por token.

---

## Consideraciones de iframe

Google Apps Script, por defecto, no permite que una Web App se cargue en iframe. Todas las apps ahora usan:

```javascript
.setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL)
```

## Resumen rápido

| App | Login actual | Parámetro útil | Cambio aplicado |
|-----|--------------|----------------|-----------------|
| IPC Time Clock | Nombre + PIN / Google | `wp_user` | Login automático en iframe |
| Arc EOD Report | Público / Admin con pass | `wp_user` | Precarga email en formulario |
| Arc Human Resources | Público / Admin con sesión | `wp_user` | Precarga email en formulario |
| Arc Task App | Google Account | `wp_user` | Carga inicial con `wp_user`; operaciones requieren Google login |

## Despliegue

Después de cualquier cambio en `plantillas/`, ejecuta:

```bash
clasp push
```

Luego en Apps Script, crea un despliegue de tipo **Web app** y copia la URL en **Ajustes > ARC Portal**.

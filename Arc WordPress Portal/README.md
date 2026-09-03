# Ash River Collective — Portal Integrado para WordPress

Plugin de WordPress que centraliza el acceso a las apps de Google Apps Script del equipo ARC en un único portal visual:

- **IPC Time Clock**
- **Arc EOD Report**
- **Arc Human Resources**
- **Arc Task App**

Inspirado en la arquitectura del plugin **Intranet ARC** (`Plugin ash/intranet`), ofrece sidebar colapsable, header con usuario, dashboard de accesos directos y un puente de autenticación con Google Apps Script preparado para SSO.

## Tabla de contenidos

1. [Resumen](#resumen)
2. [Instalación](#instalación)
3. [Uso](#uso)
4. [Configuración](#configuración)
5. [Tailwind CSS](#tailwind-css)
6. [Arquitectura](#arquitectura)
7. [Shortcodes](#shortcodes)
8. [Endpoints REST](#endpoints-rest)
9. [Registro de apps (extensión)](#registro-de-apps-extensión)
10. [Hooks y filtros](#hooks-y-filtros)
11. [Auto-configuración](#auto-configuración)
12. [Integración SSO con Google Apps Script](#integración-sso-con-google-apps-script)
13. [Troubleshooting](#troubleshooting)
14. [Changelog](#changelog)
15. [Próximos pasos](#próximos-pasos)

## Resumen

Este plugin actúa como **Opción 1**: un portal centralizado dentro de WordPress. Las apps de Google Apps Script existentes se embeben mediante iframes o enlaces, sin reescribir su lógica. El plugin gestiona autenticación, roles y un menú lateral dinámico.

## Instalación

1. Copia la carpeta `arc-wordpress-portal` dentro de `wp-content/plugins/`.
2. Activa el plugin desde **Plugins** en wp-admin.
3. Ve a **Ajustes > ARC Portal** y pega las URLs de despliegue de cada app.
4. Guarda los permalinks (**Ajustes > Enlaces permanentes > Guardar**) para activar la ruta `/arc-portal/`.

## Uso

### Opción A: Shortcode

Añade el shortcode en cualquier página:

```
[arc_portal]
```

### Opción B: Ruta virtual

Visita directamente:

```
https://tudominio.com/arc-portal/
```

La ruta `/arc-portal/` está protegida por login y roles.

## Configuración

| Campo | Descripción |
|-------|-------------|
| **Título del portal** | Nombre que aparece en el header. |
| **Título de bienvenida** | Título de la pantalla de inicio. |
| **Descripción de bienvenida** | Subtítulo de la pantalla de inicio. |
| **Email de ayuda** | Enlace del botón de ayuda en el header. |
| **Logo URL** | Imagen del logo en el sidebar. Si está vacío se muestra el título. |
| **Roles permitidos** | Roles de WordPress que pueden acceder. Los administradores siempre pueden. |
| **Pasar email de WordPress** | Añade `?wp_user=email` a las URLs de las apps embebidas. |
| **Login redirect URL** | URL a la que enviar usuarios no logueados. |
| **Logout URL** | URL de logout personalizada. |
| **Integración SSO** | Endpoint compartido de GAS y API Secret para tokens firmados. |

## Tailwind CSS

El frontend y las páginas de administración cargan **Tailwind CSS v4** vía CDN:

```html
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
```

Todas las plantillas (`portal-shortcode.php`) y los helpers de acceso denegado usan clases utilitarias de Tailwind. `assets/css/portal.css` solo conserva helpers de estado (`is-active`, `hide`, `arc-sidebar-collapsed`) que no genera el CDN.

## Arquitectura

```
Usuario WordPress
        ↓
[arc_portal] shortcode  →  /arc-portal/ ruta virtual
        ↓
  Arc_Portal (singleton)
        ↓
Arc_Portal_App_Registry + Arc_Portal_Router + Arc_Portal_GAS_Auth_Bridge
        ↓
plantillas/portal-shortcode.php
        ↓
iframe / nueva pestaña  →  Google Apps Script Web Apps
```

- `Arc_Portal`: bootstrap, settings, shortcode, ruta y menú admin.
- `Arc_Portal_App_Registry`: registro centralizado y ordenado de apps.
- `Arc_Portal_Router`: intercepta `/arc-portal/` y redirige al template.
- `Arc_Portal_GAS_Auth_Bridge`: endpoints REST para SSO con Apps Script.

## Shortcodes

| Shortcode | Descripción |
|-----------|-------------|
| `[arc_portal]` | Renderiza el portal completo. |

## Endpoints REST

Namespace: `/wp-json/arc-portal/v1/`

| Ruta | Método | Descripción | Permisos |
|------|--------|-------------|----------|
| `/auth/user` | GET | Devuelve datos del usuario actual para SSO. | Usuario logueado |
| `/auth/token` | POST | Genera un token firmado temporal. | Usuario logueado |

Ejemplo de respuesta `/auth/user`:

```json
{
  "email": "usuario@ashrivercollective.com",
  "name": "Usuario Ejemplo",
  "display_name": "Usuario Ejemplo",
  "roles": ["editor"],
  "timestamp": 1700000000,
  "signature": "..."
}
```

## Registro de apps (extensión)

El portal usa `Arc_Portal_App_Registry` para que las apps no estén hard-codeadas. Puedes registrar apps adicionales desde otro plugin o `functions.php`:

```php
add_action( 'arc_portal_registry_init', function ( $registry ) {
    $registry->register_app( 'wiki', array(
        'label'       => 'Wiki ARC',
        'url'         => 'https://wiki.ashrivercollective.org',
        'icon'        => 'book',
        'target'      => 'new_tab',
        'description' => 'Documentación interna',
        'order'       => 25,
        'capability'  => 'read',
    ) );
} );
```

Targets soportados: `iframe`, `new_tab`, `modal`, `ajax`.

## Hooks y filtros

| Hook / Filtro | Tipo | Descripción |
|---------------|------|-------------|
| `arc_portal_registry_init` | action | Se dispara al construir el registro. Recibe `Arc_Portal_App_Registry`. |
| `arc_portal_registered_apps` | filter | Permite modificar la lista final de apps. |
| `arc_portal_force_assets` | filter | Fuerza la carga de assets. |
| `arc_portal_can_access` | filter | Permite anular el acceso al portal. |
| `arc_portal_before_render` | action | Se dispara antes de incluir el template. |

### Ejemplo: añadir un enlace externo

```php
add_filter( 'arc_portal_registered_apps', function ( $apps ) {
    $apps['drive'] = array(
        'label'       => 'Drive Compartido',
        'url'         => 'https://drive.google.com',
        'icon'        => 'file-text',
        'target'      => 'new_tab',
        'description' => 'Acceso rápido al Drive del equipo',
        'order'       => 15,
        'capability'  => 'read',
        'enabled'     => true,
    );
    return $apps;
} );
```

## Auto-configuración

- `plantillas/arc-apps-config.json` contiene los `scriptId` y rutas de cada app.
- `plantillas/update-arc-urls.js` usa `clasp deployments` para leer las URLs y actualizar el JSON.
- El JSON se copia a ambos plugins y se importa automáticamente a WordPress.
- En **Ajustes > ARC Portal** aparece el botón **Importar URLs ahora**.

## Integración SSO con Google Apps Script

1. Despliega una Web App en Apps Script con `doGet`/`doPost`.
2. Configura **URL del endpoint compartido de GAS** y **API Secret**.
3. El portal genera tokens firmados con HMAC-SHA256.
4. Apps Script valida la firma usando el mismo secreto (o `wp_salt('auth')` si se deja vacío).
5. Consulta la documentación en `docs/gas-auth-bridge.md`.

## Troubleshooting

| Síntoma | Causa probable | Solución |
|---------|---------------|----------|
| El iframe muestra un error | X-Frame-Options o CSP de Google | Usa target `new_tab` o configura CSP. |
| No se ve el portal | Sin login o sin permisos | Revisa roles y redirección de login. |
| Tailwind no aplica estilos | CDN no cargado / sin conexión | Verifica que `https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4` sea accesible. |
| App no aparece en el menú | `enabled = false` o falta URL | Configura la URL en Ajustes > ARC Portal. |

## Changelog

### 1.0.0
- Versión inicial del portal.
- Registro de apps (`Arc_Portal_App_Registry`).
- Dashboard de inicio con cards y accesos directos.
- Sidebar colapsable y ruta virtual `/arc-portal/`.
- Puente de autenticación GAS con tokens firmados.
- Tailwind CSS v4 vía CDN.

## Próximos pasos

1. Ejecuta `node plantillas/update-arc-urls.js` para generar `arc-apps-config.json` con las URLs de despliegue.
2. El plugin importará automáticamente el archivo al activarse. También puedes usar el botón **Importar URLs ahora** en **Ajustes > ARC Portal**.
3. Probar el shortcode y la ruta `/arc-portal/`.
4. `IPC Time Clock` ya tiene el parche aplicado: haz `clasp push` en `plantillas/IPC Time Clock` para desplegarlo.
5. Aplicar los cambios de `docs/apps-integration.md` en `Arc EOD Report`, `Arc Human Resources` y `Arc Task App`.
6. Implementar SSO real siguiendo `docs/gas-auth-bridge.md` si se desea unificar autenticación.

# Ash River Collective — API Frontend para WordPress

Plugin que implementa la **Opción 2** de integración: **WordPress como frontend nativo** y **Google Apps Script / Google Sheets como backend** a través de un proxy REST interno.

## Tabla de contenidos

1. [Resumen](#resumen)
2. [Características](#características)
3. [Instalación](#instalación)
4. [Tailwind CSS](#tailwind-css)
5. [Arquitectura](#arquitectura)
6. [Shortcodes](#shortcodes)
7. [Configuración](#configuración)
8. [Endpoints REST y proxy](#endpoints-rest-y-proxy)
9. [Registro de endpoints (extensión)](#registro-de-endpoints-extensión)
10. [Caché y reintentos](#caché-y-reintentos)
11. [Hooks y filtros](#hooks-y-filtros)
12. [Ejemplo: añadir una nueva app](#ejemplo-añadir-una-nueva-app)
13. [Seguridad](#seguridad)
14. [Troubleshooting](#troubleshooting)
15. [Changelog](#changelog)
16. [Próximos pasos](#próximos-pasos)

## Resumen

En lugar de embeber apps en iframes, este plugin consume datos de Google Apps Script a través de REST y los muestra con formularios y dashboards nativos de WordPress. Cada app se registra con su endpoint, acciones permitidas y API Key.

## Características

- Shortcodes nativos de WordPress:
  - `[arc_api_time_clock]` — formulario de clock in/out.
  - `[arc_api_eod_form]` — formulario de EOD report.
  - `[arc_api_dashboard]` — dashboard centralizado.
  - `[arc_api_tasks]` — listado de tareas con filtro.
  - `[arc_api_hr]` — formulario de aplicación de empleo.
- Proxy REST interno: `/wp-json/arc-api-frontend/v1/{app}/{action}`.
- Caché de respuestas GET por 60 segundos e invalidación automática en escrituras.
- Reintentos automáticos con backoff de 500 ms ante fallos de red.
- Autenticación por nonce de WordPress.
- API Key por app para validar en Apps Script.
- Tokens firmados opcionales para SSO.
- Autoguardado de borradores en EOD form (localStorage).
- Dashboard con estadísticas reales.
- Logs de errores cuando `WP_DEBUG` está activo.

## Instalación

1. Copia la carpeta `arc-wordpress-api-frontend` a `wp-content/plugins/`.
2. Activa el plugin.
3. Ve a **Ajustes > ARC API Frontend**.
4. Configura los endpoints de cada app de Apps Script y sus API Keys.
5. Crea páginas con los shortcodes.

## Tailwind CSS

El plugin carga **Tailwind CSS v4** vía CDN en frontend y wp-admin:

```html
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
```

Todas las plantillas (`dashboard.php`, `eod-form.php`, `time-clock.php`, `tasks.php`, `hr.php`) y los formularios de administración usan clases utilitarias de Tailwind. `assets/css/api-frontend.css` solo conserva los estados `show`/`success`/`error` del mensaje y el spinner.

## Arquitectura

```
Usuario WordPress
        ↓
Shortcode (dashboard / formulario / tareas / hr / time clock)
        ↓
REST API de WordPress  →  Arc_API_Frontend_Proxy
        ↓
wp_remote_request() → POST a Apps Script Web App (doPost)
        ↓
Google Apps Script → Google Sheets
```

Componentes principales:

- `Arc_API_Frontend_App`: bootstrap, settings, shortcodes, menú admin.
- `Arc_API_Frontend_Endpoint_Registry`: registro centralizado de endpoints y acciones.
- `Arc_API_Frontend_Proxy`: expone REST routes dinámicas basadas en el registro y reenvía peticiones a Apps Script.
- `Arc_API_Frontend_Auth`: genera tokens firmados para SSO (opcional).

## Shortcodes

| Shortcode | Archivo | Descripción |
|-----------|---------|-------------|
| `[arc_api_dashboard]` | `templates/dashboard.php` | Panel con estadísticas. |
| `[arc_api_time_clock]` | `templates/time-clock.php` | Botones de clock in/out. |
| `[arc_api_eod_form]` | `templates/eod-form.php` | Formulario EOD. |
| `[arc_api_tasks]` | `templates/tasks.php` | Tabla de tareas con filtro. |
| `[arc_api_hr]` | `templates/hr.php` | Formulario de aplicación. |

## Configuración

| Campo | Descripción |
|-------|-------------|
| **Logo URL** | URL del logo en el dashboard. |
| **Roles permitidos** | Roles que pueden usar los shortcodes. |
| **Endpoint URL** | URL de despliegue (Web App) de cada app de Apps Script. |
| **API Key** | Clave compartida que Apps Script debe validar. |
| **Acciones** | Lista blanca de acciones permitidas para cada app (`clock_in`, `submit`, `get_tasks`, etc.). |
| **Google API Key** | Opcional. Para uso futuro con Service Account. |

## Endpoints REST y proxy

Namespace: `/wp-json/arc-api-frontend/v1/`

El proxy registra rutas dinámicas:

```
/wp-json/arc-api-frontend/v1/{app}/{action}
```

Por ejemplo:

```
/wp-json/arc-api-frontend/v1/eod_report/submit
/wp-json/arc-api-frontend/v1/task_app/get_tasks
```

Cada petición incluye en el body:

```json
{
  "action": "submit",
  "wp_email": "usuario@ashrivercollective.com",
  "wp_name": "Usuario Ejemplo",
  "wp_user_id": 42,
  "api_key": "TU_API_KEY"
}
```

Apps Script debe exponer un `doPost` que valide `api_key` y procese `action`.

## Registro de endpoints (extensión)

`Arc_API_Frontend_Endpoint_Registry` permite añadir apps sin modificar el proxy:

```php
add_action( 'arc_api_frontend_registry_init', function ( $registry ) {
    $registry->register_endpoint( 'invoices', array(
        'label'    => 'Facturación',
        'endpoint' => 'https://script.google.com/macros/s/.../exec',
        'api_key'  => 'mi-api-key',
        'enabled'  => true,
        'actions'  => array( 'list', 'create', 'update' ),
        'order'    => 50,
    ) );
} );
```

## Caché y reintentos

- Las peticiones `GET` se cachean por 60 segundos (`transients` + `wp_cache`).
- Filtro `arc_api_frontend_cache_ttl` permite modificar el TTL.
- En errores de red, el proxy reintenta hasta 2 veces con 500 ms de espera.
- Filtro `arc_api_frontend_max_retries` permite modificar los reintentos.
- Tras una escritura exitosa (`POST`), el proxy invalida el caché de la app.

## Hooks y filtros

| Hook / Filtro | Tipo | Descripción |
|---------------|------|-------------|
| `arc_api_frontend_registry_init` | action | Se dispara al construir el registro. Recibe `Arc_API_Frontend_Endpoint_Registry`. |
| `arc_api_frontend_endpoints` | filter | Permite modificar la lista final de endpoints. |
| `arc_api_frontend_cache_ttl` | filter | TTL del caché en segundos. Parámetros: `$ttl`, `$app`, `$action`, `$body`. |
| `arc_api_frontend_max_retries` | filter | Número máximo de reintentos. Parámetros: `$retries`, `$app`, `$action`, `$body`. |
| `arc_api_frontend_force_assets` | filter | Fuerza la carga de assets. |

## Ejemplo: añadir una nueva app

1. Crea el shortcode en `Arc_API_Frontend_App` (o usa `add_shortcode`).
2. Registra el endpoint en `arc_api_frontend_registry_init`.
3. Crea la plantilla en `templates/mi-app.php`.
4. En el frontend llama al proxy REST: `fetch(arcApiFrontend.restUrl + 'mi-app/mi_accion')`.
5. En Apps Script implementa `doPost` para `mi_accion`.

## Seguridad

- Solo usuarios logueados con rol permitido pueden usar los shortcodes.
- Cada petición lleva `X-WP-Nonce` y es validada por WordPress.
- Apps Script debe validar `api_key`.
- Para producción se recomiendan tokens firmados (`/wp-json/arc-api-frontend/v1/auth/token`).

## Troubleshooting

| Síntoma | Causa probable | Solución |
|---------|---------------|----------|
| Error 403 en el proxy | Usuario sin rol permitido | Revisa **Roles permitidos**. |
| Error 404 en el proxy | App o acción no registrada | Revisa `actions` del endpoint. |
| Error 502 | Apps Script no responde | Verifica la URL de despliegue y los permisos. |
| No se aplican estilos | Tailwind CDN bloqueado | Verifica conexión a `cdn.jsdelivr.net`. |
| Datos desactualizados | Caché activo | Espera 60 s o invalida con una escritura exitosa. |

## Changelog

### 1.0.0
- Versión inicial del API Frontend.
- Registro de endpoints (`Arc_API_Frontend_Endpoint_Registry`).
- Proxy REST con caché y reintentos.
- Shortcodes para time clock, EOD, tasks, HR y dashboard.
- Tailwind CSS v4 vía CDN.

## Próximos pasos

1. Ejecuta `node plantillas/update-arc-urls.js` para generar `arc-apps-config.json` con las URLs de despliegue.
2. El plugin importará automáticamente los endpoints al activarse. También puedes usar el botón **Importar endpoints ahora** en **Ajustes > ARC API Frontend**.
3. Implementar `doPost` en cada app siguiendo `docs/apps-script-api.md` y `docs/gas-snippets/`.
4. Usar `[arc_api_dashboard]` como página de inicio del equipo.
5. Extender con formularios adicionales según necesidad.

## Estructura

```
arc-wordpress-api-frontend/
├── arc-wordpress-api-frontend.php
├── arc-apps-config.json        (copia auto-generada)
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

## Estándares WordPress

- Headers del plugin listos para WordPress.org (`readme.txt`, `uninstall.php`, `Domain Path`).
- REST endpoints con `permission_callback` y sanitización de entradas.
- Caché con transients y limpieza al desinstalar.
- Prefijo `arc_` en todas las clases y funciones.

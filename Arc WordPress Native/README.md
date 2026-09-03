# Ash River Collective — WordPress Native Core (Opción 3)

Plugin de WordPress que implementa la **Opción 3**: **almacenamiento y lógica nativa en MySQL**, con **conectores a Google Apps Script / Sheets** para sincronización progresiva. Es el destino final de la migración: datos en WordPress, sincronización opcional con Google.

## Tabla de contenidos

1. [Resumen](#resumen)
2. [Características](#características)
3. [Instalación](#instalación)
4. [Tailwind CSS](#tailwind-css)
5. [Arquitectura](#arquitectura)
6. [Shortcodes](#shortcodes)
7. [Endpoints REST](#endpoints-rest)
8. [Tablas creadas](#tablas-creadas)
9. [Registro de módulos (extensión)](#registro-de-módulos-extensión)
10. [Sincronización con Google](#sincronización-con-google)
11. [Hooks y filtros](#hooks-y-filtros)
12. [Integración con Time Clock](#integración-con-time-clock)
13. [Troubleshooting](#troubleshooting)
14. [Changelog](#changelog)
15. [Próximos pasos](#próximos-pasos)

## Resumen

Este plugin es el núcleo nativo de Ash River Collective. Guarda EOD reports, aplicaciones de RRHH y tareas en tablas propias de WordPress. Los datos se pueden sincronizar progresivamente con Google Sheets a través de una cola (`arc_native_sync_queue`) y un endpoint de Apps Script.

## Características

- **Núcleo nativo**: tablas propias para EOD, RRHH, Tasks y Sync Queue.
- **Registro de módulos**: `Arc_Native_Modules` permite añadir/quitar módulos sin tocar el núcleo.
- **Dashboard nativo** con shortcode `[arc_native_dashboard]`.
- **Conector Google** vía cola de sincronización (`arc_native_sync_queue`) y un endpoint de Apps Script.
- **Compatibilidad**: detecta y reutiliza `arc-employee-time-clock` para el módulo de Control Horario.
- **REST API**: `/wp-json/arc-native/v1/modules` y `/wp-json/arc-native/v1/stats`.
- **Tailwind CSS v4**: cargado vía CDN en frontend y wp-admin.

## Instalación

1. Copia la carpeta `arc-wordpress-native` a `wp-content/plugins/`.
2. Activa el plugin.
3. Crea una página con el shortcode `[arc_native_dashboard]`.
4. Ve a **ARC Native** en el menú lateral y configura:
   - URL del Google Sync Bridge (endpoint de Apps Script).
   - API Secret compartido.
   - Activar sincronización.

## Tailwind CSS

El plugin carga **Tailwind CSS v4** vía CDN:

```html
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
```

Todas las plantillas (`dashboard.php`, `eod.php`, `time-clock.php`, `hr.php`, `tasks.php`) y la página de administración usan clases utilitarias de Tailwind. `assets/css/native.css` está vacío (solo compatibilidad) para evitar conflictos con las clases generadas por el CDN.

## Arquitectura

```
Usuario WordPress
        ↓
[arc_native_dashboard] / shortcodes de módulo
        ↓
   Arc_Native_Core (REST + UI)
        ↓
Arc_Native_Modules + Arc_Native_Google_Bridge
        ↓
MySQL (tablas arc_native_*)
        ↓
Cola de sincronización → Google Apps Script / Sheets (opcional)
```

Componentes principales:

- `Arc_Native_Activator`: crea tablas, reescribe rewrite rules y maneja activación/desactivación.
- `Arc_Native_Modules`: registro y ordenamiento de módulos.
- `Arc_Native_Core`: shortcodes, REST routes, assets, admin UI.
- `Arc_Native_Google_Bridge`: cola de sincronización con Google Apps Script.

## Shortcodes

| Shortcode | Archivo | Descripción |
|-----------|---------|-------------|
| `[arc_native_dashboard]` | `templates/dashboard.php` | Panel con estadísticas y módulos. |
| `[arc_native_time_clock]` | `templates/time-clock.php` | Control horario (o delega a `arc-employee-time-clock`). |
| `[arc_native_eod]` | `templates/eod.php` | Formulario EOD. |
| `[arc_native_hr]` | `templates/hr.php` | Formulario de aplicación. |
| `[arc_native_tasks]` | `templates/tasks.php` | Listado de tareas. |

## Endpoints REST

Namespace: `/wp-json/arc-native/v1/`

| Ruta | Método | Descripción | Permisos |
|------|--------|-------------|----------|
| `/modules` | GET | Lista los módulos registrados. | Usuario logueado |
| `/stats` | GET | Devuelve estadísticas del dashboard. | Usuario logueado |

Ejemplo de respuesta `/stats`:

```json
{
  "week_hours": 32.5,
  "eod_count": 12,
  "active_tasks": 5,
  "candidates": 3
}
```

## Tablas creadas

| Tabla | Propósito |
|-------|-----------|
| `wp_arc_native_eod_reports` | Reportes diarios de fin de jornada. |
| `wp_arc_native_hr_applications` | Aplicaciones y candidatos de RRHH. |
| `wp_arc_native_tasks` | Tareas y proyectos. |
| `wp_arc_native_sync_queue` | Cola de cambios pendientes de sincronizar con Google. |

## Registro de módulos (extensión)

`Arc_Native_Modules` permite añadir módulos sin modificar el núcleo:

```php
add_action( 'arc_native_modules_init', function ( $registry ) {
    $registry->register( 'invoices', array(
        'label'         => 'Facturación',
        'description'   => 'Gestión de facturas',
        'icon'          => 'money-alt',
        'shortcode'     => 'arc_native_invoices',
        'order'         => 50,
        'capability'    => 'read',
        'google_sync'   => true,
    ) );
} );
```

El core buscará automáticamente `templates/invoices.php` para renderizar el shortcode.

## Sincronización con Google

1. Despliega una Web App en Apps Script con `doPost` que reciba JSON.
2. Pega la URL en **ARC Native > URL de Google Sync Bridge**.
3. Cada cambio que dispare `do_action( 'arc_native_record_changed', $module, $record_id, $action, $data )` se encola.
4. Un cron cada 5 minutos procesa la cola y envía los cambios a Google.

Cuerpo enviado a Google:

```json
{
  "timestamp": 1700000000,
  "module": "eod",
  "record_id": 123,
  "action": "create",
  "payload": { "report_date": "2026-09-03", "hours_worked": 8.5 },
  "signature": "hmac-sha256"
}
```

Apps Script debe verificar el HMAC con el mismo secreto configurado en WordPress (o `wp_salt('auth')`).

## Hooks y filtros

| Hook / Filtro | Tipo | Descripción |
|---------------|------|-------------|
| `arc_native_modules_init` | action | Se dispara al construir el registro. Recibe `Arc_Native_Modules`. |
| `arc_native_modules` | filter | Permite modificar la lista final de módulos. |
| `arc_native_dashboard_stats` | filter | Permite modificar las estadísticas del dashboard. |
| `arc_native_force_assets` | filter | Fuerza la carga de assets. |
| `arc_native_record_changed` | action | Se dispara cuando cambia un registro. Encola automáticamente en el bridge. |

## Integración con Time Clock

Si el plugin `arc-employee-time-clock` está activo, el dashboard usa sus funciones para calcular `week_hours`. De lo contrario, el módulo `time_clock` se renderiza con el template `templates/time-clock.php`.

Para reportar horas al dashboard:

```php
add_filter( 'arc_native_dashboard_stats', function ( $stats, $user_id ) {
    $stats['week_hours'] = 40; // calcular desde tu fuente.
    return $stats;
}, 10, 2 );
```

## Troubleshooting

| Síntoma | Causa probable | Solución |
|---------|---------------|----------|
| Tablas no existen | Plugin activado sin rewrite flush | Desactiva y vuelve a activar el plugin. |
| No se sincroniza con Google | Sync desactivada o URL vacía | Revisa **ARC Native > Sincronización activa** y URL. |
| Estadísticas vacías | Módulos inactivos o sin datos | Verifica que los módulos estén registrados y activos. |
| Tailwind no aplica estilos | CDN no cargado | Verifica conexión a `cdn.jsdelivr.net`. |

## Changelog

### 1.0.0
- Versión inicial del núcleo nativo.
- Activador con creación de tablas EOD, HR, Tasks y Sync Queue.
- Registro de módulos (`Arc_Native_Modules`).
- Dashboard y shortcodes con Tailwind CSS.
- Google Sync Bridge con cola y HMAC.
- REST API para módulos y estadísticas.
- Detección de `arc-employee-time-clock`.

## Próximos pasos

1. Implementar CRUD completo en `templates/` y REST handlers.
2. Añadir autenticación con tokens firmados para el Google bridge.
3. Crear módulos nativos de Tasks y HR con sus propios handlers.
4. Migrar datos históricos de Google Sheets a las tablas nativas.
5. Añadir tests unitarios para el registro de módulos y el bridge.

## Estructura de archivos

```
arc-wordpress-native/
├── arc-wordpress-native.php
├── includes/
│   ├── class-arc-native-activator.php
│   ├── class-arc-native-modules.php
│   ├── class-arc-native-core.php
│   └── class-arc-native-google-bridge.php
├── assets/
│   ├── css/native.css
│   └── js/native.js
├── templates/
│   ├── dashboard.php
│   ├── eod.php
│   ├── time-clock.php
│   ├── hr.php
│   └── tasks.php
└── README.md
```

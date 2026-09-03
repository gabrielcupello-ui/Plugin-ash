# Integración Apps Script para ARC API Frontend

Este plugin implementa la **Opción 2**: WordPress es el frontend; Google Apps Script / Sheets sigue siendo el backend.

## Cómo funciona

1. El usuario ve formularios y dashboards en WordPress (shortcodes).
2. Los formularios envían datos al REST API de WordPress:
   - `/wp-json/arc-api-frontend/v1/time_clock/clock_in`
   - `/wp-json/arc-api-frontend/v1/eod_report/submit`
   - `/wp-json/arc-api-frontend/v1/task_app/get_tasks`
   - etc.
3. WordPress (como proxy) reenvía la petición a la Web App de Apps Script configurada.
4. Apps Script recibe `action`, `wp_email`, `wp_name`, `wp_user_id` y `api_key`.

## Seguridad

- WordPress valida que el usuario esté logueado y tenga un rol permitido.
- Cada app puede tener su propia `api_key`.
- Opcional: usar el endpoint `/wp-json/arc-api-frontend/v1/auth/token` para emitir tokens firmados que Apps Script verifique.

## Ejemplos de `doPost` por app

En `docs/gas-snippets/` hay archivos `.gs` listos para copiar en cada proyecto de Apps Script:

- `time-clock-api.gs`
- `eod-report-api.gs`
- `hr-api.gs`
- `task-app-api.gs`

Cada uno implementa un `doPost` central que valida `api_key` y despacha `action`.

## Configuración paso a paso

1. En cada app de Apps Script, copia el `.gs` correspondiente de `docs/gas-snippets/`.
2. Despliega una Web App (`Deploy > New deployment > Web app`) con acceso `Anyone`.
3. Guarda la URL en **Ajustes > ARC API Frontend**.
4. Genera una `API Key` en Apps Script y guárdala en `ScriptProperties`:
   ```javascript
   PropertiesService.getScriptProperties().setProperty('API_KEY', 'tu-clave-segura');
   ```
5. Pega la misma `API Key` en el campo correspondiente de WordPress.
6. Opcional: configura tokens firmados usando el endpoint `/auth/token`.

## Acciones recomendadas por app

### IPC Time Clock

- `clock_in`: registrar entrada.
  ```javascript
  function clockIn(data) {
    const ss = SpreadsheetApp.openById('ID');
    const sh = ss.getSheetByName('TimeLog');
    sh.appendRow([
      Utilities.getUuid(),
      data.wp_email,
      new Date(),
      data.client || '',
      data.activity || '',
      new Date(), // start
      '', // end
      0, // break
      0, // hours
      '', // billable
      '', // notes
      'WordPress', // source
      'PENDING'
    ]);
    return { success: true, message: 'Entrada registrada' };
  }
  ```
- `clock_out`: registrar salida.
- `get_stats`: devolver estadísticas para el dashboard.
  ```javascript
  function getStats(data) {
    return { success: true, week_hours: 38.5, eod_count: 5, active_tasks: 12, candidates: 3 };
  }
  ```

### Arc EOD Report

- `submit`: guardar un EOD report.
- `get_my_reports`: devolver reportes del usuario.

### Arc Human Resources

- `submit_application`: guardar una solicitud de empleo.
- `get_interviews`: devolver entrevistas.

### Arc Task App

- `get_tasks`: devolver tareas del usuario.
  ```javascript
  function getTasks(data) {
    const ss = SpreadsheetApp.openById('ID');
    const sh = ss.getSheetByName('Tasks');
    const rows = sh.getDataRange().getValues().slice(1);
    const tasks = rows.filter(function(r) {
      return data.status === 'all' || String(r[4]) === data.status;
    }).map(function(r) {
      return { Title: r[2], ProjectName: r[1], Status: r[4], Priority: r[5] };
    });
    return { success: true, tasks: tasks };
  }
  ```
- `create_task`: crear una tarea.
- `update_task`: actualizar estado.

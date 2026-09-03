=== Arc Employee Time Clock ===
Contributors: arc-automation
Donate link: https://ashrivercollective.com
Tags: time clock, employee, timesheet, attendance, pto
Requires at least: 5.8
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Cronómetro de empleados con fichaje, pausa, clientes, actividades, reportes, PTO, nómina e importación CSV.

== Description ==

**Arc Employee Time Clock** consolida las funciones esenciales de los plugins de fichaje más usados en un solo plugin ligero, moderno y seguro:

* Fichaje en vivo estilo Clockify (clock in / clock out / pause / resume).
* Cliente, actividad, proyecto, tarea y etiquetas por entrada.
* Lista de tareas recientes para retomar con un clic.
* Timesheet semanal para el empleado con todos los días.
* Reportes filtrables para administradores.
* Exportación CSV ampliada.
* Importación CSV de entradas.
* Bloqueo de semanas para cierre de nómina.
* Página de nómina (payroll) por mes.
* Clientes y actividades administrables.
* Reglas operativas (redondeo, máximo de jornada, notas obligatorias).
* Automatizaciones WP Cron (auto-cierre, revisión de banderas, digest, recordatorios y excepciones).
* Seguimiento de PTO / vacaciones.
* Flujo de aprobación de entradas.
* Captura de IP y geolocalización opcional.
* Festivos y días no laborables configurables.
* Solicitudes de ausencia/PTO con aprobación.
* Widget de dashboard para administradores.
* Entrada manual de tiempo desde el timesheet.
* Basado en WordPress Coding Standards: nombres de funciones y clases únicos, nonces, sanitización y capacidades.

== Installation ==

1. Sube la carpeta `arc-employee-time-clock` a `/wp-content/plugins/`.
2. Activa el plugin en el menú **Plugins**.
3. Ve a **Employee Time Clock > Settings** para configurar roles, PTO y umbral de horas extra.
4. Usa los shortcodes `[arc_etc_clock]` y `[arc_etc_timesheet]` en cualquier página.

== Frequently Asked Questions ==

= ¿Qué shortcodes usa? =

* `[arc_etc_clock]` — panel de fichaje para el empleado.
* `[arc_etc_timesheet]` — hoja de tiempo semanal del empleado.

= ¿Pueden varios plugins de fichaje convivir? =

Arc Employee Time Clock utiliza un prefijo único (`arc_etc_`) y un textdomain propio para no interferir con otros plugins de la familia Arc (API Frontend, Portal, Intranet).

== Screenshots ==

1. Cronómetro de fichaje con cliente, actividad, proyecto, tarea y etiquetas.
2. Timesheet semanal del empleado con entradas manuales y aprobaciones.
3. Panel de administración con reportes, exportación CSV y flujo de aprobación.
4. Gestión de clientes, actividades, reglas operativas y bloqueo de semanas.

== Changelog ==

= 1.0.0 =
* Versión inicial con fichaje, breaks, timesheet, reportes, PTO, export CSV, aprobaciones, festivos, solicitudes de ausencia, widget de dashboard y entrada manual de tiempo.
* Integración IPC: clientes, actividades, pausa/reanudación, proyectos, tareas, etiquetas, tareas recientes, bloqueo de semanas, importación CSV, reglas operativas y automatizaciones WP Cron.

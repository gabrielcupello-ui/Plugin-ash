# Hoja de ruta [PROJECT] — Explicada paso a paso

> Documento sencillo para planear la migración.

## La idea

Queremos que todos los programas del equipo ([MODULE_1], [MODULE_2], [MODULE_3], [MODULE_4]) estén dentro de la página de [ORGANIZATION]. Vamos a hacerlo por partes, sin romper nada.

## Principios

1. **Unir primero.** Dar un solo portal ahora.
2. **Mejorar después.** Cambiar formularios uno por uno.
3. **Quedarnos con los datos al final.** Que WordPress sea el dueño de la información.
4. **No romper lo que funciona.** Cada paso suma, no quita.

---

## Fase 1 — Lanzar el portal (Opción 1)

**Meta:** Que los empleados entren por una sola página de WordPress y vean todas las apps.

**Qué hay que hacer**
- Instalar el plugin `project-wordpress-portal`.
- Pegar las URLs de despliegue de cada app de [EXTERNAL_PLATFORM].
- Crear una página de WordPress con el shortcode `[project_portal]`.
- Configurar quién puede entrar (roles).
- Activar que se pase el email del usuario a las apps.

**Tiempo estimado:** 1 a 3 días.

**Quién lo hace:** [WORDPRESS_ADMIN] configura WordPress; [TECHNICAL_LEAD] da las URLs de [EXTERNAL_PLATFORM].

**Cómo saber que funcionó:** Un empleado entra a la página y puede abrir todas las apps desde el menú.

---

## Fase 2 — Mejorar los formularios (Opción 2)

**Meta:** Que los formularios se vean como parte de WordPress, no como ventanitas de [EXTERNAL_VENDOR].

**Orden recomendado**
1. **[MODULE_1]** — es el formulario más simple.
2. **[MODULE_2]** — también es un formulario.
3. **[MODULE_3]** — listado de tareas.
4. **[MODULE_4]** — solo si el plugin actual no lo cubre bien.

**Qué hay que hacer**
- Instalar el plugin `project-wordpress-api-frontend`.
- Ajustar cada app de [EXTERNAL_PLATFORM] para que acepte datos por `doPost`.
- Crear páginas en WordPress para cada shortcode (`[project_api_module_1_form]`, `[project_api_module_2]`, etc.).
- Poner esas páginas en el portal.

**Tiempo estimado:** 2 a 4 semanas por app.

**Quién lo hace:** [TECHNICAL_LEAD] o el desarrollador de [EXTERNAL_PLATFORM]; [WORDPRESS_ADMIN] configura WordPress.

**Cómo saber que funcionó:** Los usuarios dejan de notar que están usando [EXTERNAL_DATA_STORE].

---

## Fase 3 — Que WordPress tenga los datos (Opción 3)

**Meta:** Que la información viva en WordPress y [EXTERNAL_VENDOR] solo reciba copias si se quiere.

**Orden recomendado**
1. **[MODULE_1]** — poca información, fácil de migrar.
2. **[MODULE_2]** — candidatos y postulaciones.
3. **[MODULE_3]** — proyectos y tareas.
4. **[MODULE_4]** — conectar o reemplazar el plugin de fichaje.

**Qué hay que hacer**
- Instalar el plugin `project-wordpress-native`.
- Pasar los datos viejos de [EXTERNAL_DATA_STORE] a las tablas de WordPress.
- Cambiar los shortcodes para que usen las tablas de WordPress.
- Configurar la sincronización con [EXTERNAL_VENDOR] si quieres mantener copias.

**Tiempo estimado:** 4 a 8 semanas por módulo.

**Quién lo hace:** Desarrollador WordPress + [TECHNICAL_LEAD] para la migración de datos.

**Cómo saber que funcionó:** Los reportes y exportes salen directamente de WordPress.

---

## Fase 4 — Agregar más cosas

**Meta:** Aprovechar el mismo sistema para más módulos.

**Ejemplos de lo que se puede agregar**
- Facturación.
- Wiki o biblioteca de documentos.
- Solicitudes de vacaciones/PTO.
- Administración de horas extra y festivos.
- Presupuestos por cliente o proyecto.
- Dashboard para jefes.

---

## Puertas de decisión

| Pregunta | Si la respuesta es sí | Si la respuesta es no |
|----------|----------------------|------------------------|
| ¿Las URLs de [EXTERNAL_PLATFORM] ya están listas? | Lanzar Fase 1. | Esperar a tenerlas. |
| ¿La gente se queja de las ventanitas? | Empezar Fase 2. | Quedarse en Fase 1. |
| ¿Los reportes de [EXTERNAL_DATA_STORE] son lentos? | Empezar Fase 3. | Quedarse en Fase 2. |
| ¿Quieren agregar más módulos? | Extender Fase 3. | Mantener el alcance actual. |

---

## Riesgos y cómo evitarlos

| Riesgo | Solución |
|--------|----------|
| Una app de [EXTERNAL_PLATFORM] deja de funcionar en el portal | Tener la opción de abrir en pestaña nueva. |
| La gente no quiere usar los nuevos formularios | Dejar los enlaces viejos y nuevos activos al mismo tiempo. |
| Se pierden datos al migrar | Hacer respaldo de [EXTERNAL_DATA_STORE] y pruebas antes. |
| La página se pone lenta | Usar caché en Fase 2 e índices de base de datos en Fase 3. |
| El proyecto crece mucho | El sistema está hecho para agregar módulos sin reescribir todo. |

---

## Próximos pasos

1. **Esta semana:** Instalar el portal y poner las URLs.
2. **La próxima semana:** Recibir comentarios de [WORDPRESS_ADMIN], [TECHNICAL_LEAD], [STAKEHOLDER_1], [STAKEHOLDER_2] y [STAKEHOLDER_3].
3. **La semana siguiente:** Decidir cuál app mejora primero en Fase 2 (recomendamos [MODULE_1]).
4. **Mes 2:** Empezar a mejorar esa app.
5. **Mes 3 en adelante:** Evaluar si ya es momento de Fase 3.

# Checklist para la llamada [PROJECT] — Explicado paso a paso

> Quienes participan: [WORDPRESS_ADMIN], [TECHNICAL_LEAD], [STAKEHOLDER_1], [STAKEHOLDER_2] y [STAKEHOLDER_3] (opcional).

## Antes de la llamada

- [ ] Decidir quién va a compartir pantalla.
- [ ] Asegurarse de que [TECHNICAL_LEAD] tenga las URLs de despliegue de las apps de [EXTERNAL_PLATFORM].
- [ ] Tener a mano esta carpeta `[PROJECT] Documentation` para mostrar las comparaciones y diagramas.
- [ ] Decidir cuál opción se va a mostrar primero (recomendamos la Opción 1 — Portal).

## Agenda sugerida (45 a 60 minutos)

### 1. Contexto y visión (5 minutos)
- [ ] Explicar el objetivo: un solo portal dentro de `[DOMAIN]`.
- [ ] Mencionar que hay 3 opciones y que son pasos, no opciones que compiten entre sí.

### 2. Demo del Portal (Opción 1) (15 minutos)
- [ ] Mostrar la página de configuración en WordPress.
- [ ] Mostrar cómo se registran las apps (nombre, icono, URL).
- [ ] Mostrar la página con el shortcode `[project_portal]`.
- [ ] Mostrar el menú lateral, las tarjetas y cómo se abre una app.
- [ ] Explicar quién puede entrar según su rol.
- [ ] Mencionar que se puede pasar el email del usuario a las apps.

### 3. Revisar las 3 opciones (15 minutos)
- [ ] Usar `es/01-comparativa-opciones.md` para explicar qué hace cada una y sus ventajas.
- [ ] Usar `es/03-diagramas-arquitectura.md` para mostrar cómo fluye la información.
- [ ] Usar `es/04-resumen-ejecutivo.md` para resumir la propuesta.

### 4. Plan de fases (15 minutos)
- [ ] Mostrar `es/02-hoja-ruta-fases.md`.
- [ ] Poner fecha para lanzar la Fase 1.
- [ ] Decidir cuál app se mejora primero en la Fase 2 (recomendamos [MODULE_1]).
- [ ] Asignar responsables.

### 5. Preguntas abiertas (10 minutos)
- [ ] ¿Ya tenemos las URLs de las apps de [EXTERNAL_PLATFORM]?
- [ ] ¿Qué app es la que más problemas da hoy?
- [ ] ¿Necesitamos guardar los datos viejos de [EXTERNAL_DATA_STORE]?
- [ ] ¿[WORDPRESS_ADMIN] será la única admin de WordPress?
- [ ] ¿Hay otros módulos que quieran agregar? (facturación, wiki, PTO, etc.)

## Después de la llamada

- [ ] Compartir la grabación o las notas.
- [ ] Actualizar `es/02-hoja-ruta-fases.md` con las fechas y responsables acordados.
- [ ] Crear una lista de tareas en el sistema que usen (Trello, Asana, Notion, etc.).
- [ ] Agendar la siguiente reunión después de lanzar la Fase 1.

## Decisiones importantes que hay que tomar

| Decisión | Quién decide | Fecha límite |
|----------|--------------|---------------|
| ¿Cuándo lanzamos la Fase 1? | Todos | |
| ¿Qué app mejoramos primero en la Fase 2? | Todos | |
| ¿Quién da las URLs de [EXTERNAL_PLATFORM]? | [TECHNICAL_LEAD] | |
| ¿Quién administra WordPress? | [WORDPRESS_ADMIN] | |
| ¿Hacemos respaldo de [EXTERNAL_DATA_STORE]? | [TECHNICAL_LEAD] + [WORDPRESS_ADMIN] | |
| ¿Cuándo nos volvemos a reunir? | Todos | |

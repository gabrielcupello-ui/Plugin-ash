# Opciones de integración [PROJECT] — Explicado de forma sencilla

> Documento para entender las 3 opciones sin técnicos.

## Resumen

El objetivo es que todo esté en un solo lugar dentro de la página de [ORGANIZATION]. Tenemos **3 plugins de WordPress** que se pueden usar. No son rivales: son pasos. Se puede empezar con el más rápido y, con el tiempo, llegar al más completo.

| Opción | Nombre | Explicación sencilla | ¿Cuándo usarlo? |
|--------|--------|----------------------|-----------------|
| **1** | **Portal [Project]** | Es una página de WordPress con un menú lateral. Cada app de [EXTERNAL_VENDOR] se abre dentro de esa página, como si fuera una ventana. | Para **empezar ya**, sin cambiar nada de [EXTERNAL_PLATFORM]. |
| **2** | **API Frontend [Project]** | Los formularios y tableros se ven como parte de WordPress, pero por detrás siguen guardando datos en [EXTERNAL_DATA_STORE]. | Para cuando quieras que **se vea más bonito y profesional**. |
| **3** | **Native Core [Project]** | Todo se guarda dentro de WordPress (base de datos propia). [EXTERNAL_VENDOR] puede recibir copias, pero WordPress es el dueño de la información. | Para el **futuro**, cuando necesites reportes rápidos y control total. |

---

## Opción 1 — Portal [Project]

### Qué hace
- Crea una página en WordPress con el menú `[project_portal]`.
- El usuario entra con su cuenta de WordPress y ve un menú lateral.
- Cada app de [EXTERNAL_VENDOR] se abre dentro de la página (como una ventanita) o en una pestaña nueva.
- Puede poner el logo, colores y el nombre del equipo.
- Puede pasar automáticamente el email del usuario a las apps de [EXTERNAL_PLATFORM].

### Ventajas
- **Es el más rápido.** Solo necesitas pegar las URLs de las apps de [EXTERNAL_PLATFORM].
- **Un solo lugar.** Los empleados no necesitan varios enlaces.
- **Más seguro.** El login lo maneja WordPress.
- **Se ve bien en el celular** (menú lateral que se esconde).
- **Se pueden agregar más apps** fácilmente (wiki, Drive, facturación, etc.).
- **Preparado para autenticación segura** con [EXTERNAL_PLATFORM] más adelante.

### Desventajas
- Las apps siguen viviendo en [EXTERNAL_PLATFORM]. Si [EXTERNAL_VENDOR] no permite abrirlas dentro de una página, puede dar problemas.
- Los datos siguen en [EXTERNAL_DATA_STORE]. WordPress no los controla.

### Úsala cuando
- Necesites **un portal esta semana**.
- Las apps de [EXTERNAL_PLATFORM] ya funcionan bien y solo quieres unificar el acceso.

---

## Opción 2 — API Frontend [Project]

### Qué hace
- Los formularios y tableros se ven como parte de WordPress.
- Cuando el usuario envía un formulario, WordPress lo manda a [EXTERNAL_PLATFORM].
- Cada app tiene su propia clave de seguridad (API Key).
- Los resultados pueden guardarse en caché para que carguen más rápido.

### Ventajas
- **Se ve nativo de WordPress.** No parece que estés dentro de [EXTERNAL_VENDOR].
- **Mejor en el celular.** Sin ventanitas que no funcionan bien.
- **Más seguro.** Las URLs de [EXTERNAL_VENDOR] no se ven directamente.
- **Carga más rápido** gracias al caché.
- **Si falla la conexión, reintenta automáticamente.**
- **Es el paso intermedio** hacia la Opción 3.

### Desventajas
- Hay que modificar un poco las apps de [EXTERNAL_PLATFORM] para que acepten los formularios de WordPress.
- Los datos todavía están en [EXTERNAL_DATA_STORE].

### Úsala cuando
- Quieras que **se vea profesional** sin reescribir todo todavía.
- Tengas a alguien que pueda ajustar las apps de [EXTERNAL_PLATFORM].

---

## Opción 3 — Native Core [Project]

### Qué hace
- Todo se guarda en tablas propias de WordPress.
- El dashboard, formularios y reportes son nativos.
- [EXTERNAL_PLATFORM] puede recibir copias de los datos si se quiere, pero ya no es el dueño.
- Puede conectarse con el plugin de fichaje (`project-employee-time-clock`).

### Ventajas
- **WordPress es el dueño de los datos.** No dependes de [EXTERNAL_VENDOR].
- **Reportes más rápidos.** Una base de datos es más rápida que una hoja de cálculo.
- **Puedes crecer mucho** sin problemas de espacio o velocidad.
- **Control total.** Puedes agregar campos, reportes y exportaciones fácilmente.
- **La cola de sincronización** permite seguir enviando copias a [EXTERNAL_VENDOR].

### Desventajas
- Hay que migrar los datos viejos de [EXTERNAL_DATA_STORE] a WordPress.
- Es más trabajo al principio.

### Úsala cuando
- El negocio haya **crecido y [EXTERNAL_DATA_STORE] ya no alcance**.
- Necesites reportes avanzados y control total de la información.

---

## Comparación rápida

| Pregunta | Opción 1 | Opción 2 | Opción 3 |
|----------|----------|----------|----------|
| ¿Qué tan rápido se lanza? | Días | Semanas | Meses |
| ¿Cómo se ve? | Bien | Muy bien | Muy bien |
| ¿Dónde están los datos? | [EXTERNAL_DATA_STORE] | [EXTERNAL_DATA_STORE] | WordPress |
| ¿Es fácil de usar en el celular? | Regular | Sí | Sí |
| ¿Se puede crecer mucho? | Poco | Medio | Mucho |
| ¿Qué tanto trabajo da? | Muy poco | Medio | Mucho al inicio |
| ¿Preparado para el futuro? | Sí | Sí | Sí |

---

## Ruta recomendada

1. **Empezar con la Opción 1** para darle al equipo un portal esta semana.
2. **Pasar formularios importantes a la Opción 2** (recomendamos empezar por [MODULE_1]).
3. **Migrar todo a la Opción 3** cuando el negocio lo necesite.

Así no se pierde nada en el camino y cada paso mejora la experiencia.

# Diagramas de arquitectura [PROJECT] — Explicados de forma sencilla

> Para entender cómo fluye la información en cada opción.

---

## Opción 1 — Portal [Project]

```
Empleado
   │
   ▼
Página de WordPress con [project_portal]
   │
   ▼
Menú lateral con iconos ([MODULE_1], [MODULE_2], [MODULE_3], [MODULE_4])
   │
   ├─────► iframe (la app se abre dentro de WordPress)
   │
   └─────► pestaña nueva (la app se abre aparte)
   │
   ▼
[EXTERNAL_PLATFORM] (la app que ya existe)
   │
   ▼
[EXTERNAL_DATA_STORE] (donde se guardan los datos)
```

**En pocas palabras:** El empleado entra a WordPress y desde allí abre las apps de [EXTERNAL_VENDOR]. WordPress es la puerta; [EXTERNAL_VENDOR] sigue siendo la casa.

---

## Opción 2 — API Frontend [Project]

```
Empleado
   │
   ▼
Formulario o tablero dentro de WordPress
   │
   ▼
WordPress recibe la información
   │
   ▼
WordPress la manda a [EXTERNAL_PLATFORM]
   │
   ▼
[EXTERNAL_DATA_STORE] guarda la información
```

**En pocas palabras:** El empleado llena un formulario en WordPress. WordPress le manda los datos a [EXTERNAL_VENDOR]. [EXTERNAL_VENDOR] sigue guardando todo, pero el usuario no lo nota.

---

## Opción 3 — Native Core [Project]

```
Empleado
   │
   ▼
Formulario o tablero dentro de WordPress
   │
   ▼
WordPress guarda la información en sus propias tablas
   │
   ├─────► muestra reportes, exportes, etc.
   │
   └─────► (opcional) manda copia a [EXTERNAL_PLATFORM]
               │
               ▼
          [EXTERNAL_DATA_STORE] (copia de respaldo)
```

**En pocas palabras:** El empleado llena un formulario en WordPress y WordPress guarda todo. [EXTERNAL_VENDOR] puede recibir una copia, pero ya no manda.

---

## Comparación visual del camino completo

### Hoy

```
[MODULE_1]         [MODULE_2]           [MODULE_3]         [MODULE_4]
   │                  │                    │                  │
   └──────────────────┴────────────────────┴──────────────────┘
                             │
                             ▼
                 [EXTERNAL_DATA_STORE]
```

Ahora cada app vive sola y los datos están en [EXTERNAL_DATA_STORE].

### Después de la Fase 1

```
Empleado
   │
   ▼
WordPress Portal
   │
   ├─────► [MODULE_1] ([EXTERNAL_VENDOR])
   ├─────► [MODULE_2] ([EXTERNAL_VENDOR])
   ├─────► [MODULE_3] ([EXTERNAL_VENDOR])
   └─────► [MODULE_4] ([EXTERNAL_VENDOR])
```

Ahora hay una sola puerta, pero las apps siguen en [EXTERNAL_VENDOR].

### Después de la Fase 2

```
Empleado
   │
   ▼
WordPress con formularios bonitos
   │
   ▼
WordPress manda datos a [EXTERNAL_VENDOR]
   │
   ▼
[EXTERNAL_DATA_STORE]
```

Los formularios se ven como parte de WordPress, pero [EXTERNAL_VENDOR] sigue guardando.

### Después de la Fase 3

```
Empleado
   │
   ▼
WordPress con formularios y reportes
   │
   ▼
Base de datos de WordPress
   │
   ├─────► reportes rápidos
   └─────► copia opcional a [EXTERNAL_VENDOR]
```

WordPress es el dueño de todo y [EXTERNAL_VENDOR] solo recibe copias si se quiere.

---

## ¿Quién hace qué?

| Persona | Trabajo |
|-----------|---------|
| **[WORDPRESS_ADMIN]** | Admin de WordPress: instalar plugins, configurar usuarios, roles, páginas. |
| **[TECHNICAL_LEAD]** | Encargado de [EXTERNAL_PLATFORM]: dar URLs, ajustar formularios, validar claves. |
| **[STAKEHOLDER_1], [STAKEHOLDER_2], [STAKEHOLDER_3]** | Probar el portal y dar retroalimentación. |
| **Desarrollador WordPress** | Fase 3: migrar datos y construir tablas nativas. |

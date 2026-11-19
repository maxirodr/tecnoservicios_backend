# Prueba de usuario Semana 1 · Ronda 5 · Qué cambió desde la ronda 4

> Para el QA Lead: es la entrada de §0 y §2 del prompt `tasks/prompt-prueba-usuario-semana-1-ronda-4.md`
> (se reusa hasta aprobar). Reportes de la ronda 4: `docs/qa/prueba-usuario/semana-1-ronda-4/` (ya en `staging`).
> Lo que pide el §7 de la ronda 4 para aprobar: PU-302, PU-305, PU-306 y PU-307 arreglados, y en esta ronda
> PU-041 ✅ y O-1.1 ✅.

## Código a probar (`staging`, 2026-11-19)

- **Backend `staging`:** el arreglo de PU-302 es `a8deacf` (merge `f98fdd1`). El resto son docs.
- **Front `staging` @ `d2a4806`:** merge de los arreglos de PU-305 a PU-310.
- `main` = `staging` en los dos repos.
- Sin migraciones nuevas. Después del pull: `config:clear` (hay una clave nueva, `app.zona_empresa`).

## Arreglado (los 7 abiertos y la nota del §8)

| PU | Sev. | Qué cambió | Cómo verificarlo |
|---|---|---|---|
| PU-302 | alta | "Cargado" sale con el **día de Argentina**: el back corta la fecha en `America/Argentina/Buenos_Aires`, no en UTC (`PresupuestoResource`). El año que se completa en el N.º usa la misma zona. | El mismo truco del Lead: `created_at` = `2026-11-20 01:30:00` UTC → "19/11/2026"; `2026-11-19 02:59:00` → "18/11/2026". Restaurar al terminar. |
| PU-305 | media | Al **ingresar**, se descarta todo lo que quedó en caché (menos la sesión). Cada pantalla vuelve a pedir sus datos y el borrador se compara contra la versión de ahora. | Pasos de REG-03 con **ingreso real**, en Editar obra y en Editar proveedor: tiene que decir "Mientras no estabas, alguien cambió esto…" y mostrar lo que guardó el otro. Reservá ≥ 4 códigos. |
| PU-306 | media | Tabla de permisos: si el guardado falla, la pantalla sube hasta el error y le pone el foco (como PU-111). | Bajar hasta "Guardar cambios" y forzar 500 y 422: sin irse, con Atrás, con la barra y con "Sectores". |
| PU-307 | baja | Usuarios en el celular: **toda la tarjeta** abre "Editar usuario", con un solo botón (el nombre), como Obras (PU-210). | Pixel 7 e iPhone SE: tocar el mail, el sector y "Activo" en las tarjetas de "Dora" y "Carla". Con teclado, el foco se marca en la tarjeta. |
| PU-308 | baja | Plazos: "No había cambios para guardar." (y "Guardamos…") se va apenas se edita un campo, como Mi perfil. | Pasos de REG-01. |
| PU-309 | baja | Buscador: la pista solo nombra lo que la persona puede encontrar. Sin proveedores: "Por N.º de presupuesto, cliente, planta o nombre del trabajo.". | Laura o Sofía. Con Diego sigue la pista completa. |
| PU-310 | baja | "Ingreso trabado hasta las HH:MM" (lista y diálogo) en **hora de Argentina**, en cualquier huso. | Pasos de VER-01 en UTC y en Madrid. |
| §8 nota | — | "Destrabar ingreso": ahora se ve "Destrabamos el ingreso de …" aunque la ficha se recargue sin el bloqueo. | Un bloqueo real, o el `page.route` de VER, sobre la ficha. |

**Sin cambio:** B-02 (el campo de fecha nativo usa el idioma del navegador) sigue siendo límite conocido.

**Verificación de desarrollo:**
- Back: Obras 149/149, `pint` y `phpstan` en verde.
- Front: typecheck, lint y formato en verde; vitest 429/429; e2e 55/55 (PC y celular); build OK.
- Cada test nuevo falla sin su arreglo.

## Cazar regresiones alrededor de

- **Cambio de caché al ingresar.** Recorrer:
  - ingreso normal (llega a Obras);
  - sesión vencida, con vuelta a la pantalla de origen;
  - borrador recuperado **y** descartado en las pantallas con borrador (Nueva obra, Editar obra, proveedor alta y edición);
  - "Salir" y entrar con otra persona.
- **Fechas del back:** el resto de las fechas que llegan sin hora (fechas previstas y fecha del documento) son de
  calendario y no deberían cambiar.
- **Tarjeta de Usuarios:** en PC sigue siendo tabla y el nombre es lo único que abre el diálogo.

## Datos de la ronda

Carpeta `docs/qa/prueba-usuario/semana-1-ronda-5/`, rama `qa/prueba-usuario-semana-1-ronda-5` (desde `staging`),
hallazgos desde **PU-501**.

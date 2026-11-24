# Hallazgos · Prueba de usuario Semana 2 · Ronda 3 (visual) · 2026-11-24

Front `0cbdc4d` (rediseño "Obrador" `2054a31`), backend `432c725`. Numeración desde PU-801. Severidad visual del §4 del prompt de la ronda: **media** = ilegible, tapado, no tocable en el celular o información solo por color; **baja** = desalineado o espaciado.

| ID | Severidad | Título | Regresión |
|---|---|---|---|
| PU-801 | media | En el Calendario, la prioridad de cada pedido solo se distingue por el color | no |
| PU-802 | media | Carga por persona: la tarjeta no se puede tocar, solo el nombre (42×19 px) | no |
| PU-803 | baja | En el celular la pestaña activa queda fuera de la vista en la tira de pestañas | rediseño 2054a31 |
| PU-804 | baja | En las tablas con nombre de fila, sobra una raya en el borde de abajo, debajo de la primera columna | rediseño 2054a31 |
| PU-805 | baja | En Editar pedido, "Cancelar / Guardar" quedan pegados a la tarjeta "Historial de cambios" | probablemente no |
| PU-806 | baja | Equipos en reparación (SE): "Pendiente de aprobación" se mete en la columna Fecha | no determinado |
| PU-807 | baja | Plazos en el celular: el encabezado "Cuando entre la fecha de pedido y la de entrega hay…" se corta sin aviso | rediseño 2054a31 |

### PU-801 · En el Calendario, la prioridad de cada pedido solo se distingue por el color
- **Severidad:** media
- **Tipo:** accesibilidad
- **F-XX / pantalla:** F-11 · W-03 Calendario · `/calendario` (mes) y `/calendario?vista=semana` · PC 1366×768
- **Persona:** Carla (Compras) y Laura (Ingeniería de proyectos)
- **Pasos:** 1. Carla entra a Calendario, vista Mes, noviembre 2026. 2. Mirar los pedidos del 23 y 24/11: "QAS2R3-A Comprar bridas…" y "QAS2R3-A U1 urgente vence hoy" (Urgente), "QAS2R2-A D5 Diego a Carla…" (Prioritario). 3. Pasar a Semana. 4. Repetir con Laura ("QAS2R2-B entrega antes 3", Urgente; "QAS2R2-A D4…", Normal).
- **Esperado:** que lo urgente se note también sin color (prompt de la ronda §2.1: "qué está vencido o urgente, y que se note también sin el color (texto o ícono)"; §4: "información que solo se distingue por el color" = media). En el resto del sistema, Urgente lleva el ícono ⚠ y la palabra.
- **Obtenido:**
  - Cada pedido es un recuadro con la tarea y nada más: rojo lleno para Urgente, amarillo para Prioritario, gris para Normal.
  - No hay ícono ni palabra a la vista: `svg=0` en los no completados. La prioridad solo está en un texto oculto para lectores de pantalla ("… · Urgente").
  - Tampoco hay leyenda de colores en la pantalla.
  - Un pedido vencido (entrega 23/11, hoy 24/11) no tiene ninguna marca. Ahí la fecha la da la columna, así que lo grave es la prioridad.
  - Los completados sí se distinguen sin color: ícono ✓ y tachado.
  - Reproducido 2 veces (06-verificar rep 1: Carla y Laura; rep 2: Laura y Carla), en Mes y en Semana.
- **Evidencia:** `capturas/A-01-1-mes-carla.png`, `A-01-1-semana-carla.png`, `A-01-2-mes-carla.png`, `A-01-2-semana-carla.png`, `calendario-semana-carla-pc-r3.png` · `logs/A-01.log` (clases `cal-pedido--urgente|--prioritario|--normal`, `svg=0`)
- **Causa probable (opcional):** `src/features/pedidos/components/calendario/PedidoEnCalendario.tsx`. La prioridad va solo en la clase de color y en un `sr-only`. Bastaría el mismo ⚠ de `Prioridad` delante de la tarea en los urgentes, o la palabra.
- **Regresión de:** no. El "antes" (`docs/ui/capturas/antes/pc-calendario-mes.png`) ya era solo color, y el "después" de referencia (`despues/pc-calendario-mes.png`) también. El rediseño no lo resolvió: lo hizo más fuerte (rojo lleno).
- **Origen:** A-01 (`borradores/a.md`, PC) + B-02 (`borradores/b.md`, celular: mismo problema en Pixel 7 y SE, también "al sol"; capturas `B-02-1…4.png`). **Nota del Lead:** RN-PED-47 pide "el **color** de su prioridad", pero no impide sumar la palabra o el ícono; la regla de esta ronda (§2.1 y §4) pide que se note sin color, y en el resto del sistema Urgente ya lleva ⚠ y la palabra. Verificado en el código por el Lead.

### PU-802 · Carga por persona: la tarjeta no se puede tocar, solo el nombre (42×19 px)
- **Severidad:** media
- **Tipo:** celular
- **F-XX / pantalla:** W-26 · Carga por persona · `/pedidos/carga-por-persona`
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. En el celular, Más → Pedidos → pestaña "Carga por persona". 2. Tocar la tarjeta de "Carla" en cualquier lugar que no sea el nombre (por ejemplo sobre "Abiertas 52" o la barra).
- **Esperado:** "Tocando una persona se ven sus tareas abiertas." (texto de la misma pantalla); en el resto de las listas la tarjeta entera es tocable; controles ≥ 44×44.
- **Obtenido:** no pasa nada (`elementFromPoint` = `TR`, la URL no cambia). Solo abre el nombre, que mide **"Carla" 42×19 px** y **"Diego" 46×19 px**. Reproducido en Pixel 7 y en SE, 2 tarjetas × 2 puntos cada una.
- **Evidencia:** `capturas/B-01-1.png` (Pixel) · `capturas/B-01-2.png` (SE) · `logs/B-01.log`
- **Causa probable (opcional):** `CargaPorPersonaPantalla.tsx:36` usa `className="tabla-inicio__tarea"`, pero el `::after` que estira el link está acotado a `.tabla-inicio .tabla-inicio__tarea` (`pedidos-inicio.css:128`) y esta tabla es `tabla tabla--tarjetas tabla--grilla tabla-carga` (sin `tabla-inicio`).
- **Regresión de:** no (en `antes\celular-pedidos-carga-por-persona.png` también se tocaba solo el nombre)
- **Origen:** B-01 (`borradores/b.md`). Causa verificada en el código por el Lead.

### PU-803 · En el celular la pestaña activa queda fuera de la vista en la tira de pestañas
- **Severidad:** baja
- **Tipo:** celular
- **F-XX / pantalla:** Carga por persona, Equipos en reparación, Plazos de la prioridad, Tareas de mi gente (Inicio), Obras · Terminadas; en SE también Tabla de permisos
- **Persona:** Diego
- **Pasos:** 1. En el celular entrar a `/pedidos/carga-por-persona` (o a cualquiera de las de arriba). 2. Mirar la tira de pestañas.
- **Esperado:** la pestaña en la que estoy se ve marcada (se lee "dónde estoy", §2 punto 1).
- **Obtenido:** la tira arranca siempre en la primera pestaña y la activa queda a la derecha, fuera de la vista: "Carga por persona" [671..836] con la tira en [16..396] (Pixel) / [16..359] (SE); "Equipos en reparación" [836..1030]; "Plazos de la prioridad" [366..553]; "Tareas de mi gente" [499..665]; "Terminadas (0)" [310..448]; en SE "Tabla de permisos" [205..367]. Se ve "Todos los pedidos · Servicios generales · Pedi…" sin ninguna subrayada. El título de la pantalla sí dice dónde estoy. Reproducido en Pixel y en SE.
- **Evidencia:** `capturas/B-03-1.png` (Carga, SE) · `capturas/B-03-2.png` (Plazos, SE) · `B-pestana-*-{celular,se}.png` · `logs/B-03.log`
- **Causa probable (opcional):** la tira es deslizable (`overflow-x`) y no se hace `scrollIntoView` de la pestaña activa al montar.
- **Regresión de:** rediseño 2054a31 (en `antes\celular-pedidos-carga-por-persona.png` los accesos eran botones en varias filas, todos a la vista, con el activo marcado; en `despues\` ya se ve así)
- **Origen:** B-03 (`borradores/b.md`).

### PU-804 · En las tablas con nombre de fila, sobra una raya en el borde de abajo, debajo de la primera columna
- **Severidad:** baja
- **Tipo:** consistencia-con-documento
- **F-XX / pantalla:** F-12 · W-04 Carga por persona `/pedidos/carga-por-persona` · obra, tabla Presupuestos `/obras/5` · Usuarios › Sectores `/usuarios/sectores` · PC 1366×768
- **Persona:** Hernán (Carga por persona) y Diego (obra y Sectores)
- **Pasos:** 1. Hernán entra a Pedidos › Carga por persona. 2. Mirar el borde inferior de la tabla, debajo de "Pablo" (última fila). 3. Diego, en la ficha de la obra 5, tabla Presupuestos (debajo de "3-931-2026"), y en Usuarios › Sectores.
- **Esperado:** el borde de abajo de la tarjeta de la tabla, redondeado y limpio, como en Mis tareas o Todos los pedidos.
- **Obtenido:**
  - En la última fila, la celda de la primera columna (un `<th>` de fila) conserva su borde inferior de 1 px.
  - El resto de la fila (los `<td>`) no lo tiene.
  - Se ve como una raya corta pegada a la esquina redondeada, que llega hasta donde termina esa columna.
  - Medido: `th 1px solid rgb(230,227,220) · td 0px none`, en las 3 tablas.
  - Reproducido 2 veces: `06-verificar` y `08-zoom`.
- **Evidencia:** `capturas/A-02-zoom-carga.png`, `A-02-zoom-obra-presupuestos.png`, `A-02-zoom-sectores.png` (×3), `pedidos-carga-por-persona-pc-r3.png`, `obra-ficha-pc-r3-completa.png` · `logs/A-02.log`
- **Causa probable (opcional):** `src/shared/ui/Tabla.css:51`. `.tabla tbody tr:last-child td { border-bottom: 0; }` no incluye `th`. Con `tr:last-child > *` (como en `pedidos-inicio.css:98`) alcanza.
- **Regresión de:** rediseño 2054a31
- **Origen:** A-02 (`borradores/a.md`).

### PU-805 · En Editar pedido, "Cancelar / Guardar" quedan pegados a la tarjeta "Historial de cambios"
- **Severidad:** baja
- **Tipo:** consistencia-con-documento
- **F-XX / pantalla:** F-33 · W-32 Editar pedido · `/pedidos/:id/editar` · PC 1366×768
- **Persona:** Laura (Ingeniería de proyectos)
- **Pasos:** 1. Laura abre `/pedidos/82/editar`. 2. Bajar hasta el pie del formulario. 3. Repetir en `/pedidos/18/editar` y `/pedidos/91/editar`.
- **Esperado:** el mismo aire entre bloques que en el resto de la pantalla (las tarjetas del detalle se separan con `margin-bottom: var(--espacio-4)`).
- **Obtenido:** el borde de abajo de "Guardar" toca el borde de arriba de la tarjeta "Historial de cambios": **0 px** de separación (medido en 82, 18, 91 y 18). La barra de botones parece parte de la tarjeta de abajo. Reproducido 2 veces (rep 1: 82 y 18; rep 2: 91 y 18).
- **Evidencia:** `capturas/A-03-1-editar-82.png`, `A-03-1-editar-18.png`, `A-03-2-editar-91.png`, `A-03-2-editar-18.png`, `pedido-editar-pc-r3-completa.png` · `logs/A-03.log`
- **Causa probable (opcional):** la barra del pie del formulario (texto "Si cambia la fecha de entrega…" + botones) no tiene margen inferior (`0px`) y la tarjeta `.bloque-pedido` del historial (`pedido-detalle.css:89`) solo tiene margen abajo.
- **Regresión de:** probablemente no. El Lead comparó el CSS: antes del rediseño, `.bloque-pedido` ya tenía solo `margin-bottom` (`pedidos.css:158` en `c816c89`). No hay par "editar" en `docs/ui/capturas/`.
- **Origen:** A-03 (`borradores/a.md`).

### PU-806 · Equipos en reparación (SE): "Pendiente de aprobación" se mete en la columna Fecha
- **Severidad:** baja
- **Tipo:** celular
- **F-XX / pantalla:** Equipos en reparación · `/pedidos/equipos`
- **Persona:** Diego
- **Pasos:** 1. En un iPhone SE (375 px), abrir Equipos en reparación con un equipo en "Pendiente de aprobación". 2. Mirar la fila "Aprobación | Fecha" de la tarjeta.
- **Esperado:** cada dato en su columna, nada encimado.
- **Obtenido:** la etiqueta "Pendiente de aprobación" termina en x=200 y la celda "Fecha" empieza en x=196 (la celda Aprobación termina en 180): el "—" de la fecha queda pegado a la etiqueta. En Pixel 7 entra (Fecha en 214). 2 vueltas iguales.
- **Evidencia:** `capturas/B-04-1.png` · `capturas/B-04-2.png` · `logs/B-04.log`
- **Regresión de:** no determinado (en `antes\`/`despues\` no hay captura con equipos)
- **Origen:** B-04 (`borradores/b.md`). Regresión: no determinada (las capturas de referencia de Equipos no tienen filas).

### PU-807 · Plazos en el celular: el encabezado "Cuando entre la fecha de pedido y la de entrega hay…" se corta sin aviso
- **Severidad:** baja
- **Tipo:** celular
- **F-XX / pantalla:** Plazos de la prioridad · `/usuarios/plazos`
- **Persona:** Diego
- **Pasos:** 1. En el celular, Más → Usuarios → Plazos de la prioridad. 2. Leer el encabezado de la segunda columna.
- **Esperado:** el encabezado se lee entero, o hay la pista "Deslizá para ver más →" como en Tabla de permisos.
- **Obtenido:** se ve "Cuando entre la fecha de pedido y la de en" (Pixel) / "…y la" (SE), cortado en el borde. La tabla se puede deslizar (`overflow-x: auto`, 460 px de contenido en 378 / 341) pero solo por el encabezado y no hay pista. Las filas ("hasta 1 día", "hasta 7 días", "más de 7 días") se leen bien. 2 vueltas en cada equipo.
- **Evidencia:** `capturas/B-05-1.png` (Pixel) · `capturas/B-05-2.png` (SE) · `usuarios-plazos-{celular,se}-r3.png`
- **Regresión de:** rediseño 2054a31. En `despues\celular-usuarios-plazos.png` ya se ve igual, y en `antes\` el encabezado se leía entero (ver Origen).
- **Origen:** B-05 (`borradores/b.md`). **Regresión confirmada por el Lead:** en `antes/celular-usuarios-plazos.png` el encabezado se partía en dos renglones y se leía entero.

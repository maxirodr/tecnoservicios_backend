# Borrador · Rol A · PC 1366×768 · Semana 2 · ronda 3 (visual) · 2026-11-24

- **Personas (sesiones guardadas `s2r3\sesiones\`):** Laura, Carla, Hernán, Diego. Ninguna tocó "Salir".
- **Ingresos reales gastados:** 0. La pantalla de Ingreso se vio sin sesión. Para la pantalla del código se **simuló**
  la respuesta de `POST /api/auth/pedir-codigo` con `page.route`: no salió ningún mail ni se gastó ningún código.
- **Front / back:** `0cbdc4d` / `432c725` (worktrees `_qa_s2r3`). Navegador: Chromium de Playwright, `es-AR`,
  `America/Argentina/Buenos_Aires`, uno solo a la vez.
- **Datos creados (prefijo `QAS2R3-A`):**
  - Obra 5 `3-931-2026 · QAS2R3-A Reemplazo integral de cañería de vapor…` (124 caracteres), creada por Diego desde la
    pantalla Nueva obra.
  - Pedido 82 `QAS2R3-A Comprar bridas de acero inoxidable 316…` (131 caracteres), creado por Laura desde Nuevo pedido:
    obra 5, Carla, entrega ayer (Urgente, vencido), aclaraciones largas.
  - Pedidos 83–91 (por API, como Laura): `T1…T4 para tomar con teclado` (Carla los pasó al sector Compras), `S1…S4
    seguido por Hernán`, `U1 urgente vence hoy`.
  - Para PU-703 quedaron T1 y T2 **En curso** (Carla los tomó) y Hernán **dejó de seguir** S1 y S2.
  - No se tocó la base a mano ni se movieron fechas.
- **Scripts:** `C:\Users\GREEN\AppData\Local\Temp\claude\4fe530b1c11d8e8d\f4efcdda-aa94-4434-aafd-2e4822d4d74b\scratchpad\s2r3\A\`
  - `lib.mjs` tiene las mediciones: scroll horizontal, desbordes, paradas de Tab y anillo de foco.
  - `01-datos` · `02-pantallas` (pasada general) · `03-pu703` · `04-ventanas` · `05-estados` · `06-verificar` ·
    `07-errores-lentos` · `08-zoom`.
  - Las salidas están en `A\out\`.
- **En todas las pantallas:**
  - Scroll horizontal de la página: **0**.
  - Desbordes de texto detectados: **0**.
  - Errores de consola o de red: **0** (salvo los 404/422 provocados a propósito).
  - Foco: con Tab, el anillo se ve en todos los controles. En las filas, el anillo es el contorno de la fila entera.

## Pantallas

| pantalla | ✅/⚠️/❌ | qué se miró | evidencia | nota |
|---|---|---|---|---|
| Inicio · Mis tareas | ✅ | Laura (8 filas) y Carla (66 filas, tabla larga), tarea de 131 y obra de 124 caracteres, Urgente/vencido, una parada de Tab por fila, "Tomar" | `inicio-mis-tareas-pc-r3*.png`, `inicio-mis-tareas-carla-pc-r3*.png` | Urgente lleva ícono ⚠ y texto. Vencido se lee como texto "· vencido" y con la franja rayada a la izquierda. Con obras de nombre largo, el desplegable "Obra" se estira a todo el ancho (ver Opiniones). |
| Inicio · Pedidos que pedí | ✅ | Laura, 50 filas por página, textos largos | `inicio-que-pedi-pc-r3*.png` | — |
| Inicio · Tareas que sigo | ✅ | Laura y Hernán: dos paradas por fila (link y "dejar de seguir"), las dos con nombre propio | `inicio-que-sigo*-pc-r3*.png` | El botón de la fila es la segunda parada, como se diseñó. |
| Inicio · Tareas de mi gente | ✅ | Hernán, una parada por fila | `inicio-mi-gente-pc-r3*.png` | — |
| Pedido · detalle | ✅ | Laura (solicitante) y Carla (propietaria). Título de 131 caracteres en 2 líneas, obra larga, aclaraciones largas, vencido en texto. 404 (`/pedidos/999999`, `/pedidos/abc`): "Pedido no encontrado" | `pedido-detalle-pc-r3*.png`, `pedido-detalle-carla-pc-r3*.png`, `pedido-inexistente-pc-r3.png`, `pedido-detalle-completado-pc-r3.png` | — |
| Pedido · recorrido | ✅ | Pedido 18, con 16 eventos | `pedido-recorrido-pc-r3-completa.png` | — |
| Pedido · ventanas (Bloquear, Completar, Pegar link dentro de Completar, Reasignar, Reabrir) | ✅ | Abren con Enter y el foco va al primer campo. Título de 131 caracteres en 3 líneas, sin cortes. Enviar vacío muestra los errores. El Tab queda dentro de la ventana. **Escape cierra y el foco vuelve al botón que la abrió** (las 5 veces). | `pedido-bloquear*-pc-r3.png`, `pedido-completar*-pc-r3.png`, `pedido-completar-pegar-link-pc-r3.png`, `pedido-reasignar*-pc-r3.png`, `pedido-reabrir*-pc-r3.png` · `out\04-ventanas.json` | Escape dentro de "Pegar link" cierra **toda** la ventana Completar, no solo el pegado (dato menor, no visual). |
| Nuevo pedido | ✅ | Vacío con errores, completo con textos largos y creado ("Pedido creado. Se le avisó a Carla.") | `pedido-nuevo-errores-pc-r3.png`, `pedido-nuevo-pc-r3.png` | — |
| Editar pedido | ⚠️ | Título largo (tarea más obra, 4 líneas), historial de cambios | `pedido-editar-pc-r3-completa.png` | **A-03:** "Guardar" queda pegado (0 px) a la tarjeta "Historial de cambios". |
| Todos los pedidos | ✅ | Diego, 50 filas por página, filtros, una parada por fila, vacío, cargando y error | `pedidos-todos-pc-r3*.png`, `estado-*-pedidos-todos-pc-r3.png` | Urgente con ícono, "vencido" en texto. |
| Servicios generales | ✅ | 43 filas, vacío, cargando y error | `pedidos-servicios-generales-pc-r3*.png`, `estado-*-pedidos-servicios-generales-pc-r3.png` | — |
| Carga por persona | ⚠️ | Hernán, barras, "(inactivo)" en texto | `pedidos-carga-por-persona-pc-r3*.png`, `A-02-zoom-carga.png` | **A-02:** sobra una raya en el borde de abajo de la tabla. |
| Calendario (mes y semana) | ⚠️ | Diego vacío ("No hay pedidos en este mes."), Carla y Laura con datos. Tarea larga en la celda (se parte en líneas, no se corta). Tab: un link por pedido, con anillo. | `calendario-*-pc-r3*.png`, `calendario-*-carla-pc-r3*.png`, `A-01-*.png` | **A-01:** la prioridad (Urgente/Prioritario/Normal) solo se distingue por el color. |
| Equipos en reparación | ✅ | Vacío con "+ Agregar equipo". La ventana abre con el foco en el primer campo, Escape la cierra y el foco vuelve. Cargando y error. | `pedidos-equipos-pc-r3.png`, `pedidos-equipos-agregar*-pc-r3.png` | No se cargaron equipos: la tabla con filas no se vio en PC. |
| Obras · listado | ✅ | En ejecución vacía, con la fila Servicios generales. Confirmadas (6) con obras de 120+ caracteres. | `obras-pc-r3.png`, `obras-confirmadas-pc-r3.png` | — |
| Obras · ficha | ⚠️ | Título de 124 caracteres en 2 líneas, pestañas Pedidos y Documentos, 404 (`/obras/abc`) | `obra-ficha-pc-r3-completa.png`, `obra-ficha-documentos-pc-r3-completa.png` | **A-02** en la tabla Presupuestos de la ficha. |
| Obras · alta | ✅ | Vacía con errores ("Escribí solo el número, por ejemplo 2-131-2026."), completa y guardada | `obra-nueva-errores-pc-r3.png`, `obra-nueva-pc-r3.png` | — |
| Obras · presupuesto por rubro | ✅ | Título largo, tabla de rubros | `obra-presupuestos-pc-r3*.png` | — |
| Proveedores (listado y ficha) | ✅ | Lista, ficha `/proveedores/1`, vacío, cargando y error | `proveedores-pc-r3.png`, `proveedor-ficha-pc-r3.png` | — |
| Usuarios | ✅ | Lista, vacío, cargando y error | `usuarios-pc-r3*.png` | — |
| Tabla de permisos | ✅ | Desplegables por celda, scroll horizontal **dentro** de la tabla (la página no se mueve), leyenda Editar/Ver/Sin acceso con texto | `usuarios-permisos-pc-r3*.png` | Varias paradas por fila: es una tabla para editar, cada celda es un control. |
| Sectores | ⚠️ | Lista con "renombrar" y "sacar" | `usuarios-sectores-pc-r3*.png`, `A-02-zoom-sectores.png` | **A-02** |
| Plazos | ✅ | Urgente/Prioritario/Normal con ícono y texto | `usuarios-plazos-pc-r3.png` | — |
| Mi perfil | ✅ | Mis datos y Mis avisos | `perfil-pc-r3*.png` | El mail bloqueado se parte en 2 líneas (ver Opiniones). |
| Campana y avisos | ✅ | La campana abre (con "Cargando avisos…") y Escape cierra devolviendo el foco a la campana. Lista de avisos: el no leído lleva punto y franja, no solo color. Vacío, cargando y error. | `campana-abierta-pc-r3.png`, `campana-tras-escape-pc-r3.png`, `avisos-pc-r3.png` | — |
| Buscador | ✅ | `?q=QAS2R3` con resultados, cargando y error | `buscador-pc-r3*.png` | — |
| Ingreso y código | ✅ | Sin sesión, `/` lleva a `/ingresar`. Vacío: "Completá el mail.". El código pasa a "Te mandamos un código a …" con el foco en el campo del código. | `ingreso-pc-r3.png`, `ingreso-errores-pc-r3.png`, `ingreso-codigo-pc-r3.png` | El pedido del código se simuló (no se gastó ninguno). |
| Estados vacío / cargando / error | ✅ | 22 pantallas. Cargando: spinner con texto ("Cargando tus tareas…", `aria-busy`). Vacío: mensaje propio de cada pantalla. Error (500 en la lista): "No pudimos cargar esta información" + "Reintentar", **con la barra visible**. | `estado-{cargando,vacio,error}-*-pc-r3.png` · `out\05-estados.json`, `out\07-errores-lentos.json` | El error llega a los 5–6,5 s, porque el front reintenta 3 veces (4 pedidos 500). Mientras tanto se ve "Cargando…". En `05` algunas capturas de error salieron todavía en "Cargando…" por eso; las de `07` muestran el error. "Server Error" en algunas capturas de `05` es el texto que puse en la simulación: el back real dice "El sistema tuvo un problema…". |
| **PU-703** (foco tras Tomar / dejar de seguir) | ✅ | Carla, en Mis tareas: Tab hasta "Tomar · QAS2R3-A T1…" y Enter. Hernán, en Que sigo: "dejar de seguir · QAS2R3-A S1…". **2 repeticiones de cada uno** (T1/T2, S1/S2). | `pu703-tomar-{1,2}-pc-r3.png`, `pu703-dejar-{1,2}-pc-r3.png` · `out\03-pu703-{1,2}.json` | Tomar: el foco queda en el link de la **misma fila** (ya En curso), con el contorno de la fila visible a los 3 s (después de recargar la lista). El Tab siguiente va a la fila de abajo. Dejar de seguir: la fila se va (7→6, 6→5) y el foco pasa al link de la **fila vecina**. El Tab siguiente va a su "dejar de seguir". No queda en `body`. No hay anuncio para lector de pantalla (`aria-live`) del resultado, pero el arreglo no lo prometía. |
| Pantalla de error del sistema (§3) | — | Se probaron direcciones raras (`/pedidos/abc`, `/obras/abc`, `/pedidos?page=abc&orden=zzz`, `/calendario?vista=zzz&obra=abc`, `/avisos?page=-1`, `/buscar?q=%25%00`) y una respuesta del detalle con forma rota (`adjuntos:'x'`, `puede:null`). | `error-sistema-intento-pc-r3.png` · `out\05-estados.json`, `out\07…json` | **No se pudo forzar.** Todo cae en 404 propios, en "No pudimos cargar esta información / El sistema respondió algo inesperado…" (la validación de la respuesta lo ataja antes de dibujar) o en un 422 con "Tiene que ser 1 o más." (`/avisos?page=-1`, dirección inventada). |
| "Pantalla que no baja" (§3) | — | — | `no-baja-intento-pc-r3.png` | **No se pudo forzar: no hay código por sección (un solo bundle, sin `lazy()`).** Probé bloquear en dev `/src/features/pedidos/pantallas/CalendarioPantalla.tsx`. Queda la **página en blanco entera**, sin barra ni mensaje, porque en dev el módulo es parte del arranque. No es el caso del §3. |

## Hallazgos

### A-01 · En el Calendario, la prioridad de cada pedido solo se distingue por el color
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

### A-02 · En las tablas con nombre de fila, sobra una raya en el borde de abajo, debajo de la primera columna
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

### A-03 · En Editar pedido, "Cancelar / Guardar" quedan pegados a la tarjeta "Historial de cambios"
- **Severidad:** baja
- **Tipo:** consistencia-con-documento
- **F-XX / pantalla:** F-33 · W-32 Editar pedido · `/pedidos/:id/editar` · PC 1366×768
- **Persona:** Laura (Ingeniería de proyectos)
- **Pasos:** 1. Laura abre `/pedidos/82/editar`. 2. Bajar hasta el pie del formulario. 3. Repetir en `/pedidos/18/editar` y `/pedidos/91/editar`.
- **Esperado:** el mismo aire entre bloques que en el resto de la pantalla (las tarjetas del detalle se separan con `margin-bottom: var(--espacio-4)`).
- **Obtenido:** el borde de abajo de "Guardar" toca el borde de arriba de la tarjeta "Historial de cambios": **0 px** de separación (medido en 82, 18, 91 y 18). La barra de botones parece parte de la tarjeta de abajo. Reproducido 2 veces (rep 1: 82 y 18; rep 2: 91 y 18).
- **Evidencia:** `capturas/A-03-1-editar-82.png`, `A-03-1-editar-18.png`, `A-03-2-editar-91.png`, `A-03-2-editar-18.png`, `pedido-editar-pc-r3-completa.png` · `logs/A-03.log`
- **Causa probable (opcional):** la barra del pie del formulario (texto "Si cambia la fecha de entrega…" + botones) no tiene margen inferior (`0px`) y la tarjeta `.bloque-pedido` del historial (`pedido-detalle.css:89`) solo tiene margen abajo.
- **Regresión de:** no sé si es del rediseño: no hay par "editar" en `docs/ui/capturas/`. Propongo "rediseño 2054a31" si el Lead lo confirma con el "antes".

## Opiniones (gusto, no hallazgos)
- **Desplegable "Obra" en Inicio, Todos los pedidos y Calendario:** con obras de 120+ caracteres, el `select` nativo toma el ancho de la opción más larga. Ocupa todo el renglón y baja "Estado" y "Buscar tarea" al renglón siguiente. Por ejemplo, Carla en Mis tareas. No se rompe nada, pero el filtro queda desproporcionado. Un ancho máximo lo ordenaría.
- **Editar pedido:** el título "Editar · tarea · obra" con textos largos ocupa 4 líneas de letra grande antes del formulario. Con la tarea sola alcanzaría (la obra está en el campo de abajo).
- **Mi perfil:** el mail bloqueado se parte en dos líneas ("diego@empresa-" / "ejemplo.com") en una caja que parece un campo.
- **Calendario:** el rojo lleno de Urgente es lo más fuerte de la pantalla, más que la fecha de hoy. Aparte de A-01, es una elección de tono.

## No probado
- **Pantalla de error del sistema:** no se pudo forzar sin tocar código (ver tabla).
- **"Pantalla que no baja":** no se pudo forzar; no hay código por sección (un solo bundle). El intento de bloquear un módulo en dev deja la página en blanco.
- **Equipos en reparación con filas:** no hay equipos cargados y no creé uno, para priorizar la cobertura. Se vio el vacío, la ventana de alta (errores y Escape), cargando y error.
- **Contraste medido y peso de la página:** son del Lead. Celular: Rol B.
- **Envíos reales de las ventanas** (Bloquear, Completar, Reasignar, Reabrir): no se confirmaron. Solo se abrieron, se envió vacío y se cerró con Escape, para no cambiar datos (prueba visual).
- **Ingreso real con código:** no se gastó ninguno.

— Rol A (PC 1366×768), subagente QA NexoSmart · 2026-11-24

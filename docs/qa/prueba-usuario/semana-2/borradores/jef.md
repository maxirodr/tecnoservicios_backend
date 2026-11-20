# Borrador JEF · Jefaturas, listas y calendario · Semana 2 · ronda 1

- **Fecha:** 2026-11-20, de 11:31 a 11:55 (hora de Argentina; base y logs en UTC = +3 h).
- **Código probado:** worktrees `_qa_s2` en `staging` (back `7e007dd`, front `d8f2433`). API :8340 y front :5316. Health 200 (0,12–0,14 s) al empezar y al terminar; no hubo lentitud ni 503. Sin `errors-2026-11-20.log` y `failed_jobs` = 0 al terminar.
- **Personas / sesiones:** Hernán (Jefatura de Proyectos), Dora (Dirección), Diego (control: su gente es solo Carla), Laura y Pablo (sin gente a cargo) y Pablo para completar (`sesiones/<persona>.json`). PC 1366×768, `es-AR`, `America/Argentina/Buenos_Aires`; además el navegador en **UTC** y con `page.clock`. Nunca se tocó "Salir".
- **Ingresos reales gastados: 0.**
- **Datos creados (prefijo `QAS2-JEF`):**
  - Usuarios (alta de Diego por API, sector Ingeniería de proyectos, depende de Hernán): `QAS2-JEF Inactivo con pendientes` (id 15, `qa-jef-inactivo@empresa-ejemplo.com`) y `QAS2-JEF Inactivo sin pendientes` (id 16, `qa-jef-inactivo0@…`). Los dos quedaron **inactivos**; el 16 se activó un momento para probar el cambio de "depende de" (Hernán → Diego → Hernán) y quedó inactivo con jefe Hernán, verificado en la respuesta.
  - Pedidos 45–54 (Laura pide; todos con `QAS2-JEF` en la tarea): 45 y 46 al inactivo (entrega 19-nov y 26-nov); 47–52 a Pablo (18-nov vencido, 20-nov hoy, domingo 22-nov, lunes 23-nov, uno con período 19-nov → 2-dic y entrega 27-nov, y el 52 que Pablo completó); 53 a Hernán con obra `QAS2-PED Reparación de losa tanque B`; 54 a Sofía con período 14 → 21-nov. "Con quién": "Corralón Ñandú año"; aclaraciones con tildes y ñ.
  - Hernán siguió y dejó de seguir los pedidos 50 y 51 (quedan sin seguir).
- **Base:** **no moví fechas a mano**; nada que restaurar. Los bordes de día se probaron con `page.clock` (front) y con `Carbon::setTestNow` dentro de un `php artisan tinker` de solo lectura (back; el reloj cambia solo en ese proceso).
- **Scripts y salidas:** `C:\Users\GREEN\AppData\Local\Temp\claude\4fe530b1c11d8e8d\f4efcdda-aa94-4434-aafd-2e4822d4d74b\scratchpad\s2\JEF\` (`lib.mjs`, `00-explora.mjs` … `14-evidencia.mjs`, `10-bordes-back.php`; salidas en `out\`). Los `.xlsx` bajados, aparte, en `…\scratchpad\s2\JEF-dl\`.

## Funcionalidades

| F-XX | Estado | Pantallas | Evidencia | Nota |
|---|---|---|---|---|
| F-06 Tareas de mi gente | ⚠️ | W-26 `/?pestana=mi-gente` | `F-06-tareas-de-mi-gente-pc.png` | Hernán ve a Laura, Sofía, **Pablo (vía Sofía)**, el inactivo y "Hernán (yo) · Jefatura de Proyectos · 1 abierta"; no ve a Carla, Diego ni a QA Lead Reg2 (Ingeniería, sin jefe). Grupo "Laura · Ingeniería de proyectos · 10 abiertas · 1 vencida", 3 primeros por entrega, "ver las 7 restantes" ↔ "ver menos". Orden de grupos por vencidas y después abiertas (Laura 1/10, Pablo 1/5, inactivo 1/2, Sofía 0/2, Hernán 0/1). **Inactivo con pendientes:** "QAS2-JEF Inactivo con pendientes (inactivo) · Ingeniería de proyectos · 2 abiertas · 1 vencida"; el inactivo **sin** pendientes no aparece (DU-P09). Sin abiertas: "Pablo · Ingeniería de proyectos · sin tareas abiertas" (antes de cargarle pedidos). Filtro Estado: Completada y todas filtran las filas. "17/11/2026 · vencido" en rojo, "· hoy", "· mañana". Dora (Dirección) ve a **todos** los activos más el inactivo con pendientes y "Dora (yo)"; Diego solo a Carla y "Diego (yo)". Laura: sin pestaña, `?pestana=mi-gente` cae en Mis tareas y la API da 403 "Esta sección es para quien tiene gente a cargo.". La fila no se abre al tocarla: **JEF-02**. |
| F-07 Tareas que sigo | ✅ | W-28 `/?pestana=que-sigo` · W-09 | `F-07-tareas-que-sigo-pc-jef.png` | Hernán "Seguir" en dos pedidos de Pablo → "Ahora seguís este pedido."; la lista con Prioridad · Tarea · Obra · **Propietario** · Entrega · Estado, por entrega; "dejar de seguir" saca de a uno hasta "No seguís ningún pedido.". |
| F-11 Calendario | ⚠️ | W-03 `/calendario` | `F-11-calendario-mes-mis-tareas-pc.png`, `F-11-calendario-mes-mi-gente-pc.png`, `F-11-calendario-semana-mi-gente-pc.png`, `F-11-calendario-semana-domingo-utc-pc.png` | Mes "Noviembre 2026" de lunes 26-oct a domingo 6-dic, hoy marcado (viernes 20-nov), días de otro mes atenuados. Semana "Semana del 16 al 22-nov", ‹ › mueve de a una semana. **Mis tareas** (Hernán: lo suyo, 25-nov y dos completados el 2-dic) y **Mi gente** (los de Laura, Sofía, Pablo y el inactivo, **sin** los de Hernán; Dora ve también a Hernán; Laura no tiene el botón). Color por prioridad (`--urgente`/`--prioritario`/`--normal`). **Períodos como barras** que cruzan semanas: "Sofía · … (14 al 21-nov) →" de sáb 14 a dom 15 y "← …" de lun 16 a sáb 21; el de Pablo 19-nov → 2-dic en 3 semanas (jue–dom, completa, lun–mié). **Completados tachados** (`text-decoration: line-through`, "· completado"). Filtro Obra (la obra QAS2-PED y "Sin obra") filtra. Tocar un pedido abre `/pedidos/{id}`. Sin "(inactivo)": **JEF-03**; al volver pierde la vista: **JEF-04**. |
| F-12 Carga por persona | ⚠️ | W-04 `/pedidos/carga-por-persona` | `F-12-carga-por-persona-pc.png` | "Carga de mi gente · semana del 16/11/2026 al 22/11/2026" (lunes a domingo). Hernán: Laura 10 · 1 · 3, Pablo 5 · 1 · 2, Sofía 2 · 0 · 1, inactivo 2 · 1 · 0, Hernán 1 · 0 · 0: **los mismos números que la cuenta en la base** (vencidas = antes de hoy; esta semana = de hoy, 20, al domingo 22, sin las vencidas: el lunes 23 de Pablo no cuenta). Orden por vencen esta semana y después vencidas. Barra 100 % / 67 % / 33 % / 0 % y la leyenda "no es un puntaje". Filtro Sector (Jefatura de Proyectos → solo Hernán; Compras → "No hay personas en este sector."). Tocar a Pablo → `/pedidos?propietario=5` con sus 5 abiertos. Dora: 14 personas (Carla 21 · 0 · 6, verificado en la base); Diego: Carla y él. Sin gente a cargo, por URL, el mensaje culpa al sector: **JEF-01**. |
| F-35 Todos los pedidos y Excel | ⚠️ | W-24 `/pedidos` | `F-35-todos-los-pedidos-filtros-pc.png`, `F-35-exportar-excel-pc.png` | **Cada filtro contra la base** (Hernán, alcance todas): abiertos por defecto 43 de 55 · Obra 3 · Sin obra 40 · Sector 19 · Propietario Pablo 5 · "En el sector, sin persona" 1 · Solicitante 3 · Completada 12 · Bloqueada 3 · todos 55 · Urgente 11 · Solo vencidos 3 (no trae los de hoy) · Entrega 20 → 22 13 (inclusive) · combinados (Ingeniería + Urgente + vencidos 3; Laura + Pablo + todos 6): **"N de M pedidos con estos filtros" igual a la base en los 15 casos**. Los filtros quedan en la URL. **Orden:** Entrega ▲ por defecto, ▼ al tocar; Pedido y Obra asc/desc; verificado fila por fila. **Paginado** de a 50: "Página 1 de 2 · 55 pedidos", "Siguiente" mantiene estado y orden. No hay buscador en W-24 (el documento no lo pide; el buscador general es del rol OBR). **Excel:** `pedidos-2026-11-20.xlsx`, hoja "Pedidos", encabezados en negrita y en español: "Obra · Tarea · Sector · Propietario · Solicitante · Pedido · Entrega · Prioridad · Estado · N.º de presupuesto · Con quién / para quién · Período · Aclaraciones · Completado" (DU-P16). Fechas como **fecha real** con formato `dd/mm/yyyy`; Período "19/11/2026 al 02/12/2026"; N.º de presupuesto "9-801-2026"; Completado con fecha solo en los completados; "Corralón Ñandú año" y "áéíóú y ñ" sin mojibake. Baja **todo lo filtrado**: 55 y 66 filas con la pantalla en 50; el filtro y el orden de la pantalla (Ingeniería + todos + entrega desc) coinciden **fila por fila** (21 y 23 filas en dos descargas). Única diferencia: falta "(inactivo)": **JEF-03**. Fila que no se abre: **JEF-02**. |
| F-36 Servicios generales | ⚠️ | W-25 `/pedidos/servicios-generales` | `F-36-servicios-generales-pc.png` | Solo abiertos sin obra (41 = la base; ninguno con obra ni completado), por entrega, columnas Prioridad · Tarea · Sector · Propietario · Solicitante · Entrega · Estado, el inactivo con "(inactivo)", y "Los pedidos para varias obras se cargan sin obra, nombrando las obras en la tarea. Los completados están en Todos los pedidos." (DU-P13). "+ Nuevo pedido sin obra" abre W-08 con "Sin obra / Servicios generales" elegido. Fila que no se abre: **JEF-02**. |
| F-61 Jerarquía | ✅ | W-26 · W-04 · W-03 | `F-06-tareas-de-mi-gente-pc.png` | Varios niveles (Hernán → Sofía → Pablo), Dirección = todos, nadie de otra rama (Hernán no ve a Carla; Diego no ve a Laura). Cambiar "depende de" del usuario 16 de Hernán a Diego: al reabrir, sale de la lista de Hernán y aparece en la de Diego ("QAS2-JEF Inactivo sin pendientes · Ingeniería de proyectos · sin tareas abiertas"); de vuelta, igual. Sin gente: sin pestaña ni acceso, API 403. |

**Fechas (todo ✅):**
- **Navegador en UTC a la misma hora real:** W-26, W-04, W-24 (todos), W-25 y el calendario (mes y semana) dan **el mismo texto, línea por línea**, que con el navegador en Argentina.
- **`page.clock` + UTC y + Argentina** (el back con la hora real):
  - viernes 20-nov **23:30** (en UTC ya es 21): "viernes 20/11/2026", "20/11/2026 · hoy", "21/11/2026 · mañana", calendario con hoy = viernes 20 y semana 16–22.
  - domingo 22-nov 23:30: hoy = domingo 22, semana del 16 al 22.
  - lunes 23-nov 00:30: semana del 23 al 29, hoy = lunes 23.
  - lunes 30-nov 23:30 (en UTC ya es 1-dic): mes Noviembre y semana del 30-nov al 6-dic.
- **Back en los bordes** (`Carbon::setTestNow`, carga de Pablo):
  | Hora UTC (Argentina) | Hoy | Semana | Vencidas | Esta semana |
  |---|---|---|---|---|
  | 21-nov 02:30 (vie 23:30) | 20-nov | 16–22 | 1 | 2 |
  | 23-nov 02:30 (dom 23:30) | 22-nov | 16–22 | 2 | 1 |
  | 23-nov 03:30 (lun 00:30) | 23-nov | 23–29 | 3 | 2 |

  En los tres casos, lo esperado por DU-P14.

Consola y red: ningún error JS ni warning de React en las pantallas. Los únicos 4xx son los provocados a propósito:
- 403 de mi gente / carga para Laura y Pablo;
- 422 al pedir 92 días de calendario por API (tope de 62).

## Hallazgos

### JEF-01 · Carga por persona sin gente a cargo dice que es un permiso del sector y manda a pedírselo a gerencia
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-12 · W-04 Carga por persona · `/pedidos/carga-por-persona`
- **Persona:** Laura y Pablo (Ingeniería de proyectos, sin gente a cargo)
- **Pasos:** 1. Laura (o Pablo) entra a `/pedidos/carga-por-persona` por la dirección (no tiene el acceso en la barra de Pedidos, que está bien). 2. Mirar el mensaje.
- **Esperado:** que diga por qué no la ve, como la API: "Esta sección es para quien tiene gente a cargo." (RN-PED-35, RN-IDE-13: no es una celda de la matriz, gerencia no la puede habilitar), sin el filtro ni la leyenda de una pantalla que no se muestra.
- **Obtenido:** "No tenés acceso a esta sección" / "Tu sector no tiene permiso para ver Carga por persona. Si lo necesitás, pedíselo a gerencia.", debajo del título "Carga de mi gente", del desplegable "Sector: todos" y de la leyenda "La barra compara lo que vence esta semana entre las personas: no es un puntaje. Tocando una persona se ven sus tareas abiertas.". Reproducido 2 veces con cada una.
- **Evidencia:** `capturas/JEF-01-1.png` (Laura) · `capturas/JEF-01-2.png` (Pablo) · `logs/JEF-01.log` · `GET /api/pedidos/carga-por-persona` → 403 "Esta sección es para quien tiene gente a cargo."
- **Causa probable (opcional):** `CargaPorPersonaPantalla.tsx:103` usa `ConsultaError` con `seccion`, y para un 403 `shared/ui/States.tsx:135` arma siempre "Tu sector no tiene permiso para ver {seccion}. {PEDIRSELO}" sin mirar el mensaje del back; el filtro y la leyenda se dibujan fuera del estado de error.
- **Regresión de:** no

### JEF-02 · Tocar la fila no abre el pedido: solo el texto de la tarea
- **Severidad:** baja
- **Tipo:** consistencia-con-documento
- **F-XX / pantalla:** F-35 · W-24 Todos los pedidos `/pedidos`; también F-36 · W-25 `/pedidos/servicios-generales` y F-06 · W-26 `/?pestana=mi-gente`
- **Persona:** Hernán (Jefatura de Proyectos)
- **Pasos:** 1. Abrir Todos los pedidos. 2. Tocar la primera fila sobre el propietario, la entrega o el sector (fuera del texto subrayado de la tarea). 3. Igual en Servicios generales y en Tareas de mi gente.
- **Esperado:** "tocando una fila se abre el pedido (W-09)" (W-24, RN-PED-42; HU-042 c3 "tocando una fila se abre el pedido").
- **Obtenido:** no pasa nada (la URL sigue en `/pedidos`, `/pedidos/servicios-generales`, `/?pestana=mi-gente`), el cursor de la fila es la flecha (`cursor: auto`); solo abre el link de la tarea (→ `/pedidos/21`). Reproducido 2 veces en las tres pantallas. En el calendario, tocar el pedido sí lo abre.
- **Evidencia:** `capturas/JEF-02-1.png` (fila con el mouse encima, sin indicación de que se puede tocar) · salida `out/11-v1.txt`, `out/11-v2.txt`
- **Causa probable (opcional):** `TablaTodos` (y las tablas de W-25/W-26) ponen el `Link` solo en la celda de la tarea; la `<tr>` no tiene manejador.
- **Regresión de:** no

### JEF-03 · El "(inactivo)" del propietario falta en el Excel y en el calendario "Mi gente"
- **Severidad:** baja
- **Tipo:** consistencia-con-documento
- **F-XX / pantalla:** F-35 · W-24 Exportar a Excel (`GET /api/pedidos/exportar`); F-11 · W-03 Calendario `/calendario` (Mi gente)
- **Persona:** Hernán (Jefatura de Proyectos); el calendario igual con Dora (Dirección)
- **Pasos:** 1. Diego desactiva a `QAS2-JEF Inactivo con pendientes`, que tiene 2 pedidos abiertos. 2. Hernán abre Todos los pedidos filtrado por Ingeniería de proyectos: la tabla dice "QAS2-JEF Inactivo con pendientes (inactivo)". 3. "Exportar a Excel" y abrir el archivo. 4. Calendario → Mi gente → Semana.
- **Esperado:** que en todos lados se lea que esa persona ya no está, como en la tabla, W-25, W-26 y W-04: "esos pedidos se ven con 'Carla (inactiva)'… en Todos los pedidos y en Tareas de mi gente" (RN-PED-60, DU-P09). El Excel lleva "las columnas de la tabla" (RN-PED-45).
- **Obtenido:**
  - **Excel**, columna Propietario: "QAS2-JEF Inactivo con pendientes" sin la marca (filas 8 y 19 de la primera descarga, 10 y 21 de la segunda); el resto de las filas coincide con la pantalla.
  - **Calendario:** "QAS2-JEF Inactivo con pendientes · QAS2-JEF Inactivo 1 vencido" el 19-nov y "… Inactivo 2 la semana que viene" el 26-nov, sin "(inactivo)".
  - Quien reparte el trabajo desde el Excel o el calendario no se entera de que esos pedidos no los está mirando nadie.
- **Evidencia:** `capturas/JEF-03-1.png` (calendario, semana del 16 al 22-nov) · `capturas/JEF-03-2.png` (la tabla con "(inactivo)") · `logs/JEF-03.log` (comparación Excel ↔ pantalla, dos descargas)
- **Causa probable (opcional):** `PedidosAExcel.php:68` escribe `$p->propietario->nombre` sin mirar `activo`; `PedidoEnCalendario.tsx:8` usa `p.propietario?.nombre` sin la marca.
- **Regresión de:** no

### JEF-04 · El calendario vuelve a "Mes · Mis tareas" al volver de un pedido
- **Severidad:** baja
- **Tipo:** funcional
- **F-XX / pantalla:** F-11 · W-03 Calendario · `/calendario`
- **Persona:** Hernán (Jefatura de Proyectos)
- **Pasos:** 1. Calendario → "Mi gente" → "Semana" → "›" (Semana del 23 al 29-nov). 2. Tocar un pedido (abre `/pedidos/50`). 3. Volver con "Atrás" del navegador.
- **Esperado:** volver a la misma vista (Mi gente, semana del 23 al 29-nov), como vuelven Inicio (`?pestana=`) y Todos los pedidos (filtros, orden y página en la URL); una jefatura revisa la semana de su gente pedido por pedido (W-03 "filtro por obra y 'Mi gente' para jefaturas").
- **Obtenido:** vuelve a "Noviembre 2026" con "Mes" y "Mis tareas" elegidos: hay que volver a tocar Mi gente, Semana y ›. Reproducido 2 veces.
- **Evidencia:** `capturas/JEF-04-1.png` (antes de abrir el pedido) · `capturas/JEF-04-2.png` (después de Atrás)
- **Causa probable (opcional):** `CalendarioPantalla.tsx:40-43` guarda vista, semana, Mis tareas / Mi gente y obra en `useState`, no en la URL.
- **Regresión de:** no

## Observaciones (no son hallazgos)
1. En W-26, con "Estado: Completada", los grupos siguen diciendo "10 abiertas · 1 vencida" (son los abiertos, RN-PED-41) y los que no tienen completados muestran solo el título, sin tabla ni "sin tareas…". Se entiende; lo dejo anotado por si se quiere un "sin completados".
2. El desplegable Sector de W-04 lista todos los sectores, también los que no tienen a nadie de mi gente (Compras → "No hay personas en este sector."). El documento no dice qué sectores ofrecer.
3. Los filtros de W-24 escritos muy rápido uno detrás del otro (sin pausa, solo con un robot) pueden pisarse: "Desde" y "Hasta" llenados en el mismo instante dejaron solo `hasta=`. Escribiéndolos como una persona (o en cualquier orden con un segundo entre medio) anda: `?desde=2026-11-20&hasta=2026-11-22` → 13. No lo reporto.
4. El `.xlsx` no trae anchos de columna, autofiltro ni la primera fila fija, y en Propiedades dice "Untitled Spreadsheet" / "OpenSpout". DU-P16 no pide más; es un detalle para cuando lo abran en Excel.

## §6.1 del backlog (lo que me toca)
- **"Mirar la planilla completa, filtrar" (HU-042):** **funciona.** Todos los filtros, el orden, "N de M" y el paginado coinciden con la base, y el Excel baja todo lo filtrado. La planilla de la obra (HU-054) es del rol OBR.
- **"Ver lo mío en una sola lista" (HU-038/039):**
  - Funciona en lo que vi: Mis tareas de Laura "10 abiertas · 1 vencida", con vencido / hoy / mañana bien en las dos zonas horarias.
  - El detalle es del rol PED.
- **No condicionan la prueba, pero se probaron:**
  - HU-040 Tareas de mi gente: **funciona.**
  - HU-041 Carga por persona: **funciona.**
  - HU-044 Calendario: **funciona.**
  - Todo con hallazgos bajos.

## No probado
- **Celular** (W-41/W-47 de estas listas, calendario en lista): es del rol CEL.
- **Alcance "propias"** en W-24, en el Excel y en el calendario: ningún sector de la prueba tiene Pedidos restringido y cambiar la matriz es del rol PER. Lo vi solo con alcance "todas".
- **Escribir las fechas Desde/Hasta con el teclado** en el control nativo: con Playwright no se cargan, es el límite B-02 conocido. Se probaron con `fill` y por URL.
- **"Vencido" del back a la medianoche real:** no se puede mover la hora del servidor que atiende. Se verificó la misma lógica con `Carbon::setTestNow` en un proceso aparte (tabla de arriba), y el front con `page.clock`.
- **Más de 2 niveles hacia abajo con gerencia → jefatura → persona reales:** el seeder no tiene a Hernán dependiendo de una gerencia y no quise tocar el "depende de" de personas del seeder. Se probó Hernán → Sofía → Pablo y Dirección = todos.

— Rol 3 JEF (Jefaturas, listas y calendario), subagente QA NexoSmart, 2026-11-20 11:55 ART.

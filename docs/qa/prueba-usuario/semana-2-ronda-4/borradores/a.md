# Borrador · Rol A · PC 1366×768 · Semana 2 · ronda 4 (corta) · 2026-11-25

- **Personas (sesiones `s2r4\sesiones\`):** Carla, Laura, Hernán, Diego. Ninguna tocó "Salir".
- **Ingresos reales gastados:** 0.
- **Front / back:** `21dfe1a` / `3c69efc` (worktrees `_qa_s2r4`). Chromium de Playwright, `es-AR`,
  `America/Argentina/Buenos_Aires`, 1366×768, un navegador a la vez.
- **Datos creados (prefijo `QAS2R4-A`, por API como Laura, para Carla / sector Compras):**
  - Pedidos 94–99 `D1…D6` con entrega el jueves 26/11 y 142–158 caracteres. D1, D2 y D6 son urgentes (D6 tiene una
    palabra larga sin espacios), D3 y D4 prioritarios, D5 normal.
  - Pedidos 100–102 con período: `P1` urgente del 27/11 al 3/12 (cruza semana y mes), `P2` prioritario del 17 al 19/11 y `P3` normal
    del 29/10 al 4/11 (viene del mes anterior).
  - 103, 104, 109 y 110 `T5…T8`: Carla los pasó al sector. Los tomó en la prueba (T5, T6 y T7); T8 queda sin tomar.
  - 105, 106, 111 y 112 `S5…S8`, seguidos por Hernán. Hernán dejó de seguir S5, S6 y S7.
  - Además se usaron T3/T4 y S3/S4 de la ronda 3 (`QAS2R3-A`): T3 y T4 quedaron tomados por Carla, y Hernán dejó de seguir S3 y S4.
  - No se tocó la base a mano ni se movieron fechas.
- **Scripts:** `C:\Users\GREEN\AppData\Local\Temp\claude\4fe530b1c11d8e8d\f4efcdda-aa94-4434-aafd-2e4822d4d74b\scratchpad\s2r4\A\`.
  - Datos: `01-datos`, `01b-mas-filas`.
  - PU-801 y el calendario: `02-calendario`.
  - PU-802 y las filas tocables: `03-filas`.
  - PU-804/805 y los bordes: `04-tablas-editar`.
  - Pestañas: `05-pestanas`.
  - A-01: `06-ficha-salto` y `07-ficha-spa`.
  - Las salidas JSON están en `A\out\`.
- **En todas las pantallas:**
  - Scroll horizontal de la página: 0.
  - Errores de consola o de red: 0.
  - Health ok y `failed_jobs` = 0. No hay `errors-2026-11-25.log`.
  - El back está lento (health tarda ~0,9 s), así que las esperas se hicieron por estado y no por tiempo fijo.

## Verificación

| PU | título | ✅/⚠️/❌ | dispositivos | evidencia | nota |
|---|---|---|---|---|---|
| PU-801 | Calendario: la prioridad se distingue sin color | ✅ | PC 1366×768, mes y semana, Carla y Laura, 2 rep. | `PU-801-r4-{1,2}-{mes,semana}-carla-pc.png` y `…-gris-pc.png` (acromatopsia por CDP), `PU-801-r4-{1,2}-mes-laura-pc.png`, `PU-801-r4-{1,2}-zoom-{mes,semana}[-gris].png` · `out\02-calendario-{1,2}.json` | **Íconos:** los urgentes llevan ⚠ (`lucide-triangle-alert`): 7 entregas y 2 barras en el mes de Carla. Los prioritarios llevan la doble flecha (`chevrons-up`): 58 entregas y 1 barra. Los normales no llevan ícono (13 entregas y 2 barras). **Escala de grises:** urgente queda como caja oscura con ⚠. Prioritario y normal quedan los dos gris claro: se distinguen por la doble flecha. **Lector de pantalla:** el `sr-only` dice " · Urgente", " · Prioritario" o " · Normal". **Completados:** mantienen el ✓ (`circle-check`), el tachado y el borde punteado, y el `sr-only` dice "· Urgente · completado" (5 en Carla; en Laura, 2 urgentes y 4 normales). Igual en la semana del 23/11, la del 30/11, la del 16/11 y diciembre. |
| PU-802 | Carga por persona: la fila entera se toca, una parada por fila | ✅ | PC, Hernán, 2 rep. (+1) | `PU-802-r4-{1,2}-foco-fila{1,2}-pc.png`, `PU-802-r4-{1,2}-hover-pc.png` · `out\03-filas-{1,2,3}.json` | 5 filas, con 1 elemento enfocable por fila. Con Tab se hacen 5 paradas, una por fila (Laura, Sofía, Inactivo, Hernán, Pablo). El anillo (`outline 3px rgb(29,94,82)`) se dibuja en la **fila**, no en el nombre. Enter abre `/pedidos?propietario=4`. **Clic fuera del nombre:** probé 2 filas × 2 puntos (columna Sector y barra "Carga de la semana"). En el punto tocado está el link estirado, y las 4 veces abre el listado de esa persona (`?propietario=3` / `=4`), en las 3 corridas. |
| PU-804 | Sin el borde de más bajo el nombre en la última fila | ✅ | PC, zoom ×3, Hernán y Diego, 2 rep. | `PU-804-r4-{1,2}-zoom-{carga,obra-presupuestos,sectores}.png`, `PU-804-r4-{1,2}-tabla-*.png` · `out\04-tablas-editar-{1,2}.json` | En la última fila: `TH 0px none` y `TD 0px none` en las 3 tablas. En la ronda 3, el `th` tenía `1px solid`. En el zoom la esquina redondeada queda limpia. |
| PU-805 | Editar pedido: aire entre el pie y "Historial de cambios" | ✅ | PC, Laura, 2 rep. | `PU-805-r4-{1,2}-editar-{82,18,91}.png` · `out\04-tablas-editar-{1,2}.json` | La separación entre "Guardar" y la tarjeta del historial es de **16 px** en los pedidos 82, 18 y 91, en las 2 corridas. En la ronda 3 era 0 px. |

## Regresión (§3, PC)

| punto | ✅/⚠️/❌ | evidencia | nota |
|---|---|---|---|
| Calendario · celdas del mes con varios pedidos y textos largos | ✅ | `PU-801-r4-*-mes-carla-pc.png`, `…-semana-carla-gris-pc.png` | El 26/11 y el 27/11 de Carla tienen más de 20 pedidos cada uno. Las tareas de 142–158 caracteres se parten en 7–9 renglones dentro de la celda, y la palabra larga de D6 se corta sin salirse. En todos los pedidos con ícono (88 en el mes, 66 en la semana), el ícono va en el primer renglón y a la altura del texto (desfase ≤ 3 px). No pisa el texto: 0 casos de solapamiento, desborde de la celda o scroll interno. |
| Calendario · barras de período | ✅ | `PU-801-r4-1-mes-carla-pc.png`, `…-semana-sig-carla-pc.png`, `…-mes-dic-carla-pc.png`, `…-semana-17-carla-pc.png` | **P3** empieza en octubre: primer tramo "… (29-oct al 4-nov) →" y segundo "← …", sin flecha al final. **P1** termina "… (27-nov al 3-dic) →" y sigue "← …" en la semana del 30/11 y en diciembre. **P2** (17 al 19/11) va en un solo tramo. Las barras urgentes y prioritarias llevan su ícono; la normal, ninguno. |
| Filas tocables · Mis tareas (Carla), Tareas que sigo y Tareas de mi gente (Hernán), Todos los pedidos (Diego) | ✅ | `reg-todos-foco-{1,2,3}-pc-r4.png` · `out\03-filas-{1,2,3}.json` | **Paradas de Tab:** 1 por fila en Mis tareas, Mi gente y Todos los pedidos. Las filas con botón llevan 2: el link y "Tomar" o "dejar de seguir", igual que en la ronda 3. **Anillo:** en la fila. **Clic fuera del link:** abre el pedido (`/pedidos/93`, `/49`, `/50`, `/28`), en las 3 corridas. |
| "Tomar" y "dejar de seguir" no abren el pedido; foco después (PU-703) | ✅ | `reg-tomar-{teclado,mouse}-{1,2,3}-pc-r4.png`, `reg-dejar-{teclado,mouse}-{1,2,3}-pc-r4.png` | **Tomar:** con teclado (T4 y T7) y con el mouse (T5 y T6). La dirección sigue en `/`. El foco queda en el link de la **misma fila**, ya "En curso", con el anillo de fila (en la acción con mouse no se pinta el anillo, como corresponde a `:focus-visible`). **Dejar de seguir:** con teclado (S4 y S7) y con el mouse (S5 y S6). La dirección sigue en `/?pestana=que-sigo`, la fila se va (7→6, 5→4, 6→5) y el foco pasa al link de la fila vecina. La acción tarda 1,5–3,9 s. En la rep. 1 medí a los 3 s fijos y el botón seguía en "Tomando…"/"Dejando…": era la lentitud del back (en la base T3 y S3 quedaron bien). Por eso se repitió esperando el fin de la acción (rep. 2 y 3). |
| Tiras de pestañas: Inicio, Pedidos, Obras, Usuarios, ficha de obra (PC, no se mueven solas) | ⚠️ | `reg-pestanas-{inicio,pedidos,obras,usuarios,obra-ficha}-pc-r4[-2].png` · `out\05-pestanas-{1,2}.json` | **La tira en sí:** en ninguna pantalla desborda. `scrollLeft` queda en 0 y las posiciones son estables (muestras a 0–3 s al entrar y a 0–0,9 s después de cada cambio, 2 corridas). En Inicio, Pedidos, Obras y Usuarios la página tampoco se mueve (`scrollY` 0). **Ficha de la obra: la página baja sola al entrar → A-01.** |
| Tablas con nombre de fila: encabezado y filas del medio | ✅ | `PU-804-r4-*-tabla-*.png` · `out\04-tablas-editar-{1,2}.json` | **Carga, Presupuestos de la obra, Sectores, Usuarios y Mi gente:** el encabezado tiene `1px solid` en todas las celdas, y las filas del medio también (`th` y `td`). Solo la última fila queda en 0. |
| Backend sano | ✅ | — | Health ok, `failed_jobs` 0 y sin log del canal `errors` del día. |

## Hallazgos

### A-01 · En la ficha de la obra, en PC, la página baja sola unos segundos después de entrar y corta el título
- **Severidad:** baja
- **Tipo:** consistencia-con-documento
- **F-XX / pantalla:** F-21 · ficha de obra `/obras/:id` · PC 1366×768 (ventana de 1366×768 y de 1366×657, que es lo que
  queda de alto en Chrome con barras en una pantalla de 1366×768)
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:**
  1. Diego entra a Obras › Confirmadas.
  2. Toca la obra 6 "9-983-2026 · QAS2R3-B Reemplazo integral…", o abre `/obras/5` directo.
  3. No toca nada y mira la parte de arriba durante unos segundos.
- **Esperado:** la ficha queda arriba, con el título a la vista, hasta que el usuario baje. Según el §3 de las
  novedades, en PC las pestañas no se mueven solas. El arreglo de PU-803 tenía que llevar la pestaña activa a la vista
  solo dentro de la tira deslizable.
- **Obtenido:**
  - Cuando termina de cargar, la página hace scroll vertical sola hasta que el borde de abajo de la tira (Pedidos /
    Documentos y contactos) toca el borde de abajo de la ventana:
    - con 1366×768: `scrollY` 0→15 en la obra 5 y 0→25 en la obra 6;
    - con 1366×657: 0→126 en la obra 5, 0→136 en la obra 6 y 0→28 en la obra 1. El título queda cortado arriba (`h1` en
      −27 px).
  - Pasa entre 2,2 y 6,6 s después de entrar, cuando llegan los datos: el usuario ya está leyendo.
  - Pasa igual con `prefers-reduced-motion` (salta sin animación).
  - Con 1366×1000, la tira entra entera y no se mueve (`scrollY` 0).
  - En Inicio, Pedidos, Obras y Usuarios no pasa: su tira está arriba, a la vista.
  - Reproducido 2 veces con carga directa (`06-ficha-salto` rep. 1 y 2) y 1 vez entrando desde el listado
    (`07-ficha-spa` rep. 2, obra 6). En la rep. 1 de `07`, la obra 5 todavía no había terminado de cargar a los 3,5 s:
    el back estaba lento.
- **Evidencia:** `capturas/A-01-2-spa-657-despues.png` (después del salto: el título de la obra cortado arriba, tira abajo) ·
  `A-01-2-ficha-obra-arriba-657.png` (la misma ficha llevada arriba a mano: así debería quedar) ·
  `A-01-1-ficha-obra-al-entrar-768.png`, `A-01-2-ficha-obra-al-entrar-{768,657}.png` · `logs/A-01.log`
- **Causa probable (opcional):** `src/shared/ui/useActivaALaVista.ts`: `activa.scrollIntoView({ block: 'nearest', inline: 'center' })`.
  El comentario dice que `'nearest'` "no mueve la página si la tira ya se ve". Pero si la tira está fuera de la
  ventana o se ve a medias, la mueve, y en la ficha de la obra la tira está debajo de la cabecera y de Presupuestos.
  Hace falta desplazar solo la tira, por ejemplo con `tira.scrollTo({ left: … })` en vez de `scrollIntoView`, o
  hacerlo solo cuando desborda.
- **Regresión de:** PU-803 (commit del arreglo de las tiras deslizables, `useActivaALaVista`, entre `0cbdc4d` y `21dfe1a`)

## Opiniones (gusto, no hallazgos)
- **Calendario en escala de grises:** la doble flecha de Prioritario es más finita que el ⚠ y, en gris, Prioritario y
  Normal son la misma caja clara. Se distinguen, pero hay que mirar el ícono. En días con 20+ prioritarios y uno normal
  (el 26/11 de Carla), el normal no salta a la vista. Un borde algo más marcado en Prioritario ayudaría.
- **Período en el Calendario:** un pedido con período aparece dos veces, como barra y como entrega el día de fin (P2 el
  19/11, P3 el 4/11). Es coherente, pero en el mes ocupa mucho lugar con textos largos.

## No probado
- **Celular** (Pixel 7, SE, al sol; PU-801 en la lista del celular con "Urgente ·"; PU-803, PU-806 y PU-807; que la página no salte al tocar una
  pestaña en el celular; calendario con fechas cambiadas a mano, PU-701): son del Rol B.
- **Lector de pantalla real (NVDA):** se verificó el texto `sr-only` en el DOM, no la locución.
- **Zoom del navegador al 125–150 % en PC:** no lo probé. Por A-01 es probable que el salto sea mayor ahí, pero no lo
  medí.

— Rol A (PC 1366×768), subagente QA NexoSmart · 2026-11-25

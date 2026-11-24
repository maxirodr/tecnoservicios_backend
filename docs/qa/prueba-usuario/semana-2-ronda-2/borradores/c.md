# Borrador · Rol C · Celular y teclado · Semana 2, ronda 2 · 2026-11-20 / 2026-11-24

## Encabezado

- **Personas / sesiones:** Carla, Laura, Hernán y Diego, con sus sesiones guardadas (`s2r2\sesiones\*.json`). El 20/11 se usaron las sesiones del 20; el 24/11 las nuevas que dejó el Lead. No se usó a nadie más.
- **Dispositivos:** Pixel 7 (`devices['Pixel 7']`, 412 px), iPhone SE forzado a 375×667 (táctil, `isMobile`) y PC 1366×768 para el teclado. `es-AR`, hora de Argentina.
- **Ingresos reales gastados:** 0.
- **Código:** backend `9ca659a`, front `c816c89`. Health 200 al retomar (24/11 09:11). Durante la prueba no hubo demoras raras.
- **Corte del 20/11:** al retomar se revisaron las salidas. `02-tarjetas` (PU-603 y PU-605 en el celular) había quedado **completo en las 4 corridas** (Pixel 7 ×2, iPhone SE ×2): se usa tal cual. `03-pu601` tenía 3 de 4 corridas: el 24/11 se corrió la que faltaba (iPhone SE, Hernán). `04-teclado-pc` y `05-regresion` no habían corrido: se corrieron enteros el 24/11.
- **Datos creados (prefijo `QAS2R2-C`):**
  - obra 4 `QAS2R2-C Montaje de cañería nave 3 · 9-970-2026` (Diego), que queda **Confirmada**;
  - pedidos 47–50 (C1–C4, datos base);
  - pedidos 51–68 (`02-tarjetas`: "T … para tomar", "A … con obra / sin obra"). 51–56 repiten el texto de 57–59 porque la primera corrida del 20/11 se cortó y se volvió a correr;
  - pedidos 69–72 (PU-601 desde la obra);
  - pedidos 73–76 (regresión: creados por Laura, completados por Carla con la foto);
  - pedidos 77, 78, 81 (teclado) y 79, 80 (C-01). Los "T"/"F" quedaron En curso, tomados por Carla.
- **Restaurado:** después de cada "dejar de seguir", Hernán vuelve a seguir C2 (`POST /api/pedidos/47/seguir` → 200). En la base Hernán sigue los pedidos 47 y 49, como al principio. En la base no se escribió nada: solo hubo lecturas.
- **Foto real:** `C\files\remito QAS2R2-C foto.jpg`, JPG de 2200×1650 y 1.818.108 bytes (1,8 MB), generada en la carpeta.
- **Scripts:** `C:\Users\GREEN\AppData\Local\Temp\claude\4fe530b1c11d8e8d\f4efcdda-aa94-4434-aafd-2e4822d4d74b\scratchpad\s2r2\C\` (`01-datos`, `01b-datos`, `02-tarjetas`, `03-pu601`, `04-teclado-pc`, `05-regresion`, `06-foco-accion`, `lib.mjs`). Las salidas están en `C\out\` (`out-02-{p7,se}-{1,2}.txt`, `out-03-{p7,se}-{1,2}.txt`, `out-04-pc-{1,2,3}.txt`, `out-05-{p7,se}-{1,2}.txt`, `out-06-pc-{1,2}.txt`).

## Tabla de verificación

| PU | Título | Estado | Pantallas | Evidencia | Nota |
|---|---|---|---|---|---|
| PU-601 (celular) | "+ Nuevo pedido en esta obra": el campo Obra quedaba en "Elegí…" | ✅ | `/obras/4` → `/pedidos/nuevo` (W-47 → W-43) | `capturas/PU-601-r2-{p7-1,p7-2,se-1,se-2}-5s-celular.png`, `PU-601-r2-{p7,se}-creado-celular.png` · `out-03-*.txt` | **4 de 4:** Pixel 7 con Diego y con Hernán, iPhone SE con Diego y con Hernán. El link "+ Nuevo pedido en esta obra" mide 220×44 y se tocó con el dedo. El campo mostró `value=4` "QAS2R2-C Montaje de cañería nave 3 · 9-970-2026" a 0,3 s (3 opciones), a 1,5 s y a 5 s (6 opciones, ya con la lista). Elegir "QAS2R2-A Obra dos tanque" y volver a la obra 4 anduvo. No hay scroll horizontal (412/412 y 375/375). "Crear pedido" muestra "✓ Pedido creado / … QAS2R2-C Montaje de cañería nave 3 · para Carla · 27/11/2026 / Se le avisó a Carla.". La API devuelve `obra.id = 4` en los pedidos 69, 70, 71 y 72. Sin errores de consola ni de red. |
| PU-603 (celular) | Tocar la tarjeta no abría el pedido | ✅ | `/` Mis tareas (Carla), `/?pestana=que-pedi` (Laura), `/?pestana=mi-gente` y `/?pestana=que-sigo` (Hernán), `/avisos` (Carla) | `capturas/PU-603-r2-{p7,se}-{mis-tareas,pedidos-que-ped-,tareas-de-mi-gente,tareas-que-sigo,tomar,dejar-de-seguir,avisos}-celular.png` · `out-02-{p7,se}-{1,2}.txt` | **4 corridas (Pixel 7 ×2, iPhone SE ×2), 2 tarjetas por lista (3 en Mis tareas).** Se tocó con el dedo (`touchscreen.tap`) la tarea, la obra, la fecha, el estado, la prioridad (Pedidos que pedí no tiene prioridad) y la esquina inferior derecha vacía. **Los 21 toques por corrida abrieron el `/pedidos/{id}` de esa tarjeta.** En el punto tocado está el link estirado `a.tabla-inicio__tarea`. Las tarjetas miden 380 px de ancho en Pixel 7 y 343 px en iPhone SE, y de 268 a 342 px de alto, sin scroll horizontal. **"Tomar"** (74×44) no abre el pedido: la URL sigue en `/` y la API dice `propietario=Carla, estado=en_curso`. **"dejar de seguir"** (129×44) tampoco: la URL sigue en `/?pestana=que-sigo`, la tarjeta se va y Hernán la vuelve a seguir por la API (200). En **Avisos**, la obra va dentro del texto (PU-605). Tocar la obra abre `/pedidos/58` (61, 64, 67) y tocar el "cuándo" (`<time>`) abre `/pedidos/59` (62, 65, 68). Los dos avisos pasan de `leido=false` a `true`. Las tarjetas de avisos miden ≥ 112 px. Sin errores de consola ni de red. |
| PU-603 (teclado PC) | Una parada de Tab por fila, foco en la fila, Enter abre | ✅ (ver **C-01** en la regresión) | `/`, `/?pestana=que-pedi`, `/?pestana=mi-gente`, `/?pestana=que-sigo` (1366×768) | `capturas/PU-603-r2-pc-teclado-{mis-tareas,pedidos-que-ped-,tareas-de-mi-gente,tareas-que-sigo,tomar,dejar-de-seguir,dialogo-reasignar}.png` · `out-04-pc-{2,3}.txt` | **2 corridas buenas (2 y 3).** Se recorrieron con Tab 51/54 filas de Mis tareas, 44/47 de Pedidos que pedí, 3 de Tareas de mi gente y 3 de Tareas que sigo: **1 parada de link en cada fila.** El foco marca la fila entera (`outline 2px rgb(31, 95, 122)`) y Enter abre el pedido (`/pedidos/49`, `/pedidos/50`). "Tomar · <tarea>" y "dejar de seguir · <tarea>" son la parada siguiente de su fila, con anillo propio (`outline 2px`). Enter en "Tomar" deja la URL en `/` y toma el pedido (`en_curso`). Enter en "dejar de seguir" deja la URL en `/?pestana=que-sigo` y saca la fila. **Diálogos del pedido** (C1, Carla, sin guardar, 0 escrituras): Bloquear "Motivo * → Cancelar → Bloquear"; Completar "Comentario * → Subir archivo → Pegar link → Cancelar → Completar y avisar"; Reasignar "**Una persona** (grupo de opciones: 1 parada) → Persona * → Comentario (opcional) → Cancelar → Reasignar y avisar". En la corrida 1, Mis tareas y Pedidos que pedí dieron "distinto de 1", pero era **error del script**: agrupaba por el texto de la tarea, y hay pedidos con el mismo texto (51/54/57, 2/6, 39/43). Desde la corrida 2 se agrupa por `href`. |
| PU-605 (celular) | La lista de avisos repetía la obra | ✅ | `/avisos` (Carla) | `capturas/PU-605-r2-{p7,se}-avisos-celular.png`, `F-13-avisos-{p7,se}-celular-c.png` · `out-02-*.txt`, `out-05-*.txt` | **8 de 8** (4 corridas × 2 avisos) más 4 en la regresión. Ejemplos: "Laura te asignó QAS2R2-C A p7-1 con obra · QAS2R2-C Montaje de cañería nave 3 / hace 2 min" y "Laura te asignó QAS2R2-C A p7-1 sin obra · Servicios generales / hace 2 min". La obra aparece **1 vez**, y abajo queda solo el "cuándo". |

## Regresión en el celular (Pixel 7 ×2, iPhone SE ×2)

| Qué | Resultado | Evidencia / nota |
|---|---|---|
| Barra de abajo | ✅ 4 de 4 | "Tareas \| Obras \| Avisos \| 9+ \| Más". Avisos tiene `aria-label` "Avisos, 48 sin leer". Tocar Avisos abre `/avisos`. |
| Crear un pedido corto (W-43) y "Se le avisó a…" | ✅ 4 de 4 (pedidos 73–76) | `F-13-nuevo-pedido-{p7,se}-celular-c.png`, `F-13-pedido-creado-{p7,se}-celular-c.png`. Formulario sin "Fecha de pedido", 9 controles, todos ≥ 44 px, sin scroll horizontal. Muestra "Prioridad: Urgente (sola, 1 día) / Al crear, Carla recibe el aviso.". `POST /api/pedidos` → 201. Confirmación en 3,3–4,1 s desde "+ Nuevo pedido", con el llenado automático incluido: "✓ Pedido creado / … · para Carla · 25/11/2026 / Se le avisó a Carla. Lo vas a ver en “Pedidos que pedí” y te avisamos cuando lo tome y cuando lo complete. / Agregar un link / Ver el pedido / Cargar otro pedido". |
| Aviso a Carla en el celular (W-41) | ✅ 4 de 4 | "No leído / Laura te asignó QAS2R2-C R … cemento para losa · QAS2R2-C Montaje de cañería nave 3 / recién". Al tocarlo abre `/pedidos/{id}`, hace `POST /api/avisos/{id}/leido` → 200 y "No leídos" baja de 48 a 47. |
| Comentar, Tomar, Bloquear, Retomar | ✅ 4 de 4 | `F-13-{detalle,comentado,bloquear}-{p7,se}-celular-c.png`. Al comentar se vacía el campo y el texto queda en el hilo. Aparecen "Lo tomaste: el pedido pasó a En curso.", "El pedido pasó a Bloqueada." (con el motivo) y "Lo retomaste: el pedido volvió a En curso.". El diálogo Bloquear mide 380×425 (Pixel 7) y todos sus controles son ≥ 44 px. |
| **Completar con una foto JPG real (1,8 MB)** | ✅ 4 de 4 | `F-13-completar-{subiendo,foto}-{p7,se}-celular-c.png`, `F-13-completado-{p7,se}-celular-c.png`. "Subir archivo" abre el selector (`accept` incluye `image/*`, `.jpg`, `.heic`; múltiple). La subida (`POST /api/adjuntos` → 201) tarda 0,5–1,7 s y muestra "remito QAS2R2-C foto.jpg · 1,8 MB · quitar". "Completar y avisar" → 200 y "Pedido completado. Le avisamos a Laura.". La API guarda el comprobante `image/jpeg` de 1.818.108 bytes. En el detalle, el comprobante es un botón de 44 px de alto: al tocarlo se descarga "remito QAS2R2-C foto.jpg", sin salir del pedido, y `GET /api/adjuntos/{id}` → 200 `image/jpeg` 1818108 (corridas 2–4; la 1 lo buscaba como link y no lo midió). |
| Sin scroll horizontal y controles ≥ 44 px | ✅ | En todas las pantallas de arriba, 412/412 y 375/375; 0 controles chicos. |
| Consola y red | ✅ | Limpias en todas las pantallas, con las 4 personas, en todas las corridas: sin errores JS, sin warnings de React y sin respuestas ≥ 400. |
| Teclado: foco después de "Tomar" / "dejar de seguir" en la lista | ⚠️ **C-01** | Ver el hallazgo. |

## Hallazgos

### C-01 · Con teclado, después de "Tomar" o "dejar de seguir" en la lista, el foco se pierde (queda en la página)
- **Severidad:** baja
- **Tipo:** accesibilidad
- **F-XX / pantalla:** Inicio · Mis tareas `/` ("Tomar") y Tareas que sigo `/?pestana=que-sigo` ("dejar de seguir"), PC 1366×768
- **Persona:** Carla (Compras) y Hernán (Jefatura de Proyectos), solo teclado
- **Pasos:** 1. Carla, en Mis tareas, con un pedido del sector sin persona (QAS2R2-C F foco-1): Tab hasta "Tomar · QAS2R2-C F foco-1 tomar" y Enter. 2. Mirar dónde queda el foco y apretar Tab. 3. Hernán, en Tareas que sigo: Tab hasta "dejar de seguir · QAS2R2-C C2 Laura a Carla sin obra" y Enter. 4. Mirar dónde queda el foco y apretar Tab.
- **Esperado:** después de la acción, el foco queda en un lugar con sentido: la misma fila, que ahora dice "En curso", o la fila siguiente cuando la fila se va. El lector de pantalla anuncia qué pasó, y el foco sigue visible. Es el mismo criterio que se pidió en PU-136 y PU-212 de la Semana 1: el foco no se pierde cuando el botón desaparece.
- **Obtenido:** la acción anda (el pedido pasa a `en_curso` / la fila se va, sin abrir el pedido), pero el foco queda en `body` ("foco en body"): no hay anillo en ningún lado y no se anuncia nada. Al apretar Tab después de "Tomar", el foco salta a otra fila más abajo ("QAS2R2-C 601 se-2 hernan desde la obra", con la página desplazada a `scrollY=1039`). Después de "dejar de seguir", el Tab siguiente vuelve a dar `body`: sale de la página, porque la fila quitada era la última con foco. Reproducido: "Tomar" 4 veces (`04` corridas 2 y 3, `06` corridas 1 y 2); "dejar de seguir" 5 veces (`04` corridas 1, 2 y 3, `06` corridas 1 y 2).
- **Evidencia:** `capturas/C-01-1.png`, `C-01-3.png` (Mis tareas después de "Tomar", sin foco visible) · `capturas/C-01-2.png`, `C-01-4.png` (Tareas que sigo después de "dejar de seguir", sin foco visible) · `logs/C-01.log` · `C\out\out-06-pc-{1,2}.txt`, `out-04-pc-{2,3}.txt`
- **Causa probable (opcional):** `src/features/pedidos/components/inicio/AccionFila.tsx:17-31,34-73`: `BotonFila` usa `Button` con `cargando`, y al terminar el botón se desmonta. "Tomar" desaparece porque el pedido ya no es "del sector"; con "dejar de seguir" se va la fila entera. Nadie mueve el foco ni hay un `aria-live` con el resultado.
- **Regresión de:** no ("Tomar" y "dejar de seguir" ya eran botones aparte en la ronda 1; es el mismo patrón que PU-136 / PU-212 de la Semana 1, en una pantalla nueva)

## §6.1 del backlog (lo que me toca)

- Celular: barra Tareas · Obras · Avisos · Más: **funciona**.
- Mis tareas en tarjetas, tocables enteras, ≥ 44 px, sin scroll horizontal: **funciona** (PU-603).
- Crear un pedido corto desde el celular, con "Se le avisó a…": **funciona**.
- Completar con la foto del remito desde el celular (subida real): **funciona**.
- Avisos en el celular (W-41): tocar abre el pedido y lo marca leído, y la obra no se repite: **funciona**.
- Teclado y foco visible en PC: una parada por fila y un grupo de opciones = una parada: **funciona**. El foco después de "Tomar" / "dejar de seguir" no anda (C-01, baja).

## No probado

- "Tomar" / "dejar de seguir" con lector de pantalla real (NVDA/VoiceOver): solo se midió `document.activeElement` y el anillo.
- Avisos con obra **y** "cuándo" de otros tipos (vence mañana, completado): en el celular se usaron avisos de asignación. B cubrió el panel de PC con los otros tipos.
- Foto HEIC de iPhone y cámara (`capture`): el `input` no tiene `capture` y en Chromium headless no hay cámara. Se probó un JPG real de 1,8 MB.
- PU-601 en el celular con la red lenta: A lo cubrió en PC (2,5 s).
- Teclado en Todos los pedidos y Servicios generales: A lo cubrió en PC.

Firma: Rol C · Celular y teclado · NexoSmart QA · 2026-11-24 (iniciado el 2026-11-20, retomado tras el corte).

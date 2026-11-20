# Borrador CEL · Celular y accesibilidad (rol 6) · Semana 2 · ronda 1

- **Fecha:** 2026-11-20, 12:05–12:35 (Argentina). Worktrees `staging`: back `7e007dd`, front `d8f2433`.
- **Dispositivos:**
  - Pixel 7: `devices['Pixel 7']`, 412×915;
  - iPhone SE forzado a 375×667 (táctil, `deviceScaleFactor` 2, UA de iPhone SE);
  - PC 1366×768 con teclado;
  - Chromium `--lang=es-AR`, zona `America/Argentina/Buenos_Aires`.
- **Personas / sesiones:** Laura (crea y reabre), Carla (avisos, comenta, toma, bloquea, retoma, completa con la foto, reasigna), Hernán (Tareas de mi gente, Más), Diego (ficha de obra, Más), Dora (Más).
- **Ingresos reales gastados:** 0.
- **Datos creados (prefijo `QAS2-CEL`):**
  - 6 pedidos de Laura a Carla en la obra "QAS2-PED Reparación de losa tanque B":
    - `QAS2-CEL p7-1 …` (100 y 102);
    - `QAS2-CEL se-1 …` (103 y 104);
    - `QAS2-CEL p7-2 …` (105 y 106).
  - Los "cemento para losa" (100, 103, 105) quedaron **Completada**, con la foto del remito.
  - Los "arena gruesa (reasignar)" (102, 104, 106) quedaron **En curso** de Carla, después de pasar por el sector Compras.
  - 3 comprobantes `remito QAS2-CEL foto.jpg` (adjuntos 23, 25 y 26). Es un JPG real de 3000×4000 y 2 678 620 B, generado con System.Drawing en `CEL\files\`.
  - No moví fechas en la base.
- **Scripts:** en `C:\Users\GREEN\AppData\Local\Temp\claude\4fe530b1c11d8e8d\f4efcdda-aa94-4434-aafd-2e4822d4d74b\scratchpad\s2\CEL\`. `lib.mjs` es el de la Semana 1 (r5), adaptado.
  - `00-explora`: recorrido inicial;
  - `01-tarjetas`: toques en las tarjetas, controles de menos de 44 px y scroll horizontal;
  - `02-flujo`: W-43 → W-41 → W-42 completo;
  - `03-aviso-leido`: aviso leído al volver;
  - `04-foco-pc`: teclado en los diálogos;
  - `05-extra`: Más, W-47 y descarga del comprobante.
  - Las salidas están en `CEL\out\out-*.txt`.
- **Consola y red:** limpias en todas las corridas: sin errores JS, sin warnings de React y sin 4xx/5xx.
  - Todas las escrituras dieron 201/200 (`POST /api/pedidos`, `/comentarios`, `/tomar`, `/bloquear`, `/retomar`, `/adjuntos`, `/completar`, `/reasignar`, `/api/avisos/{id}/leido`).
  - Health 200 en 0,14 s. `errors-2026-11-20.log` no existe: no hubo errores.

## Tabla F-XX

| F-XX | estado | pantallas (Pixel 7 / iPhone SE) | evidencia | nota |
|---|---|---|---|---|
| F-13 · barra de abajo y "Más" (W-41) | ✅ | Todas las pantallas probadas, con las 5 personas | `F-13-mas-hernan-p7-celular.png`, `F-13-mas-hernan-se-celular.png` | **Barra:** "Tareas · Obras · Avisos (9+) · Más". `aria-label` de Avisos: "Avisos, 86 sin leer".<br>**Más:**<br>• Carla: Pedidos que pedí · Tareas que sigo · Pedidos · Calendario · Proveedores · Mi perfil · Salir;<br>• Laura: lo mismo, sin Proveedores;<br>• Hernán: agrega "Tareas de mi gente";<br>• Diego y Dora: agregan "Tareas de mi gente" y "Usuarios".<br>**Gastos y Rendiciones no aparecen** con nadie (es lo esperado, Semana 3). "Tareas de mi gente" solo aparece con gente a cargo. No hay scroll horizontal (412/412 y 375/375). |
| F-13 · Mis tareas en tarjetas (W-41) | ⚠️ | Mis tareas (Carla, Laura) · Pedidos que pedí (Carla, Laura) · Tareas de mi gente (Hernán) · Tareas que sigo (Hernán, vacía) | `CEL-01-1.png`, `CEL-01-2.png`, `out-01-{p7,se}-*.txt` | **Lo que anda:**<br>• Tarjetas de 380×268–309 (Pixel 7) y 343×268–309 (SE), con prioridad, tarea, obra, solicitante, entrega y estado.<br>• El vencido va arriba, en rojo ("16/11/2026 · vencido"), y arriba dice "38 abiertas · 1 vencida" y "+ Nuevo pedido".<br>• "Tomar" de un pedido en el sector mide 74×44 y lo toma (el estado pasa a "En curso" en la tarjeta).<br>• Sin scroll horizontal: solo deslizan las pestañas de Inicio, como se espera.<br>**⚠️ Pero:** la tarjeta **no se toca entera**. Solo abre el texto de la tarea, de **21 px de alto** (CEL-01). |
| F-13 · W-43 pedido corto desde el celular | ✅ | `/pedidos/nuevo` con Laura, 3 veces (2 pedidos cada vez) | `F-13-nuevo-pedido-{p7,se}-celular.png`, `F-13-pedido-creado-{p7,se}-celular.png` | **Formulario:** Tarea · Obra · Sector · Propietario · Entrega · Con quién · Aclaraciones. **No pide fecha de pedido.** Muestra "Prioridad: Prioritario (sola, 5 días)" y "Al crear, Carla recibe el aviso.". 9 controles, ninguno de menos de 44 px.<br>**Confirmación:** "✓ Pedido creado / … · para Carla · 25/11/2026 / **Se le avisó a Carla.** Lo vas a ver en "Pedidos que pedí" y te avisamos cuando lo tome y cuando lo complete." con "Agregar un link · Ver el pedido · Cargar otro pedido".<br>**"Cargar otro pedido"** deja el formulario vacío (Tarea, Obra y Propietario en blanco), y el segundo POST es otro pedido (otro id).<br>**Tiempo:** 3,4–4,7 s desde "+ Nuevo pedido" hasta la confirmación, con el llenado automático (la API respondió enseguida). |
| F-13 · comentar desde el celular (W-42) | ✅ | Detalle 100/103/105 (Carla) | `F-13-detalle-*.png`, `F-13-comentado-*.png` | "Escribir un comentario…" → "Comentar" → `201`. El hilo muestra "Carla · 20/11/2026 12:26 / Pedí cotización al corralón…", el campo queda vacío y el recorrido dice "Sistema avisó a Laura (comentado)". |
| F-13 · completar con la foto del remito (W-42) | ✅ | Diálogo Completar (Carla), 3 veces | `F-13-completar-subiendo-*.png`, `F-13-completar-foto-*.png`, `F-13-completado-*.png` | **Subida:** "Subir archivo" abre el selector del sistema (`filechooser`, múltiple) y sube el JPG de 2,6 MB: `POST /api/adjuntos` 201 en 0,5–0,7 s. Se ve "remito QAS2-CEL foto.jpg · 2,6 MB · quitar". "Completar y avisar" queda deshabilitado mientras sube.<br>**Al confirmar:** "Pedido completado. Le avisamos a Laura.".<br>**Resultado:**<br>• API: `estado=completada`, comprobante `image/jpeg` de 2 678 620 B (el mismo tamaño que el original);<br>• en el detalle: "Completado por Carla el 20/11/2026 12:26: Entregado en obra…", "Comprobantes" y en el recorrido "Agregó comprobante: remito QAS2-CEL foto.jpg";<br>• Laura, en el celular, toca el comprobante (botón 320×44) y **baja "remito QAS2-CEL foto.jpg"**.<br>**Cámara:** el input es `accept=".pdf,.jpg,…,.heic,.heif,…,image/*,application/pdf"`, **sin `capture`**. Así el celular ofrece "cámara o galería" (RN-PED-50, pedidos.md §10). No se puede abrir una cámara real en Chromium: ver "No probado". |
| F-13 · avisos en el celular (W-41) | ✅ (ver CEL-01) | `/avisos` (Carla) | `F-13-avisos-{p7,se}-celular.png`, `F-13-aviso-leido-al-volver-p7-celular.png`, `out-03-*.txt` | **Lista en tarjetas:** con ● y "No leído", "Laura te asignó QAS2-CEL p7-1 cemento para losa · QAS2-PED Reparación…" y "recién". 32 controles, ninguno de menos de 44 px.<br>**Tocar el aviso:** abre `/pedidos/100` y manda `POST /api/avisos/349/leido` → 200. La API da `leido=true`.<br>**Al volver** (Atrás o la barra): la tarjeta queda sin ●, "No leídos" baja de 85 a 84 y la barra dice "Avisos, 84 sin leer". Pasó en 4 de 5 vueltas.<br>En la primera vuelta de `02-flujo` (Pixel 7) se leyó la fila al instante de volver y todavía decía "No leído" con 86. No se repitió en las otras 4 (2 de `02` y 2 de `03`, mirando a 1, 5 y 17 s), así que no lo reporto.<br>**Detalles:** tocar sobre la obra o el "cuándo" de la tarjeta no abre nada (dentro de CEL-01). El texto repite la obra, igual que en la campana: es AVI-02 y pasa también en esta lista. |
| F-13 · tomar, bloquear y reasignar en el celular (W-42) | ✅ | Detalle (Carla), 3 veces | `F-13-bloquear-*.png`, `F-13-reasignar-*.png`, `F-13-tomar-desde-tarjeta-*.png` | **Tomar:** "Lo tomaste: el pedido pasó a En curso.".<br>**Bloquear:** el diálogo mide 380×425 / 343×445. Con el motivo dice "El pedido pasó a Bloqueada." y "Bloqueado: Esperando que el corralón confirme stock. QAS2-CEL".<br>**Retomar:** "Lo retomaste: el pedido volvió a En curso.".<br>**Reasignar:** entra en el ancho y el diálogo no tiene controles de menos de 44 px. "El sector (lo toma el primero que pueda)" → Compras → "Reasignamos el pedido y le avisamos.", y queda No iniciada (DU-P04). En Mis tareas aparece "Sin propietario · lo toma el primero que pueda · Tomar". Con "Tomar" desde la tarjeta queda En curso y Carla propietaria (DU-P02). |
| F-13 · obras en el celular (W-47) | ✅ (con OBR-02) | Obras y ficha de obra 1 y 2 (Laura, Diego) | `F-13-obras-*.png`, `F-13-obra-pedidos-*.png`, `F-13-obra-tarjeta-entera-p7-celular.png` | **Listado:** "8 pedidos abiertos" y "Servicios generales · 70 pedidos abiertos".<br>**Ficha:** se abre en "Pedidos (6 abiertos)", con los presupuestos arriba.<br>**Tarjetas de la pestaña Pedidos:** se tocan enteras (todo punto abre `/pedidos/59`, con `::after`). Es el comportamiento que falta en Mis tareas.<br>**"+ Nuevo pedido en esta obra":** el campo Obra queda en "Elegí…". Es **OBR-02**, que pasa igual en el celular (2 de 2). No lo duplico. |
| Teclado y foco en PC (diálogos del pedido) | ✅ | Bloquear, Completar, Reasignar, Reabrir y Comentar (Carla 102/104, Laura 100/103), 2 corridas | `F-13-foco-*-pc.png`, `out-04-pc-{1,2}.txt` | **Foco:** el primer foco va al primer campo. Tab y Shift+Tab giran dentro del diálogo y nunca salen. Todos los focos tienen el contorno visible (`outline 2px rgb(31,95,122)`).<br>**Orden:**<br>• Completar: Comentario → Subir archivo → Pegar link → Cancelar → Completar y avisar;<br>• Reasignar: opción → Persona/Sector → Comentario → Cancelar → Reasignar y avisar.<br>**Grupo de opciones (Reasignar, "Pasárselo a"):** es **una sola parada**, en la opción marcada. ArrowDown cambia a "El sector…" y muestra el campo Sector. **El patrón PU-502 no se repite.**<br>**Escape:** vacío cierra, y el foco vuelve al botón que abrió el diálogo. Con texto escrito pregunta "Tenés cambios sin guardar. ¿Salir igual?" (Seguir editando / Salir), con el foco en "Seguir editando".<br>**Comentar** (en la página): textarea → "Comentar", los dos con contorno.<br>No se guardó nada ("escrituras: ninguna"). |

**Conteo:** ✅ 8 · ⚠️ 1 · ❌ 0

## Hallazgos

### CEL-01 · En el celular la tarjeta de Mis tareas no se toca entera: solo abre el texto de la tarea, de 21 px de alto
- **Severidad:** baja (la propongo baja por coherencia con JEF-02, que es la misma causa en PC. Si QC la ve como "celular roto en el camino principal", sería media).
- **Tipo:** celular
- **F-XX / pantalla:**
  - F-13 / F-04 · W-41 Mis tareas `/`;
  - también Pedidos que pedí `/?pestana=que-pedi` y Tareas de mi gente `/?pestana=mi-gente`;
  - en parte la lista de Avisos `/avisos` (ahí no abren la obra ni el "cuándo").
- **Persona:** Carla (Compras), Laura (Ingeniería de proyectos) y Hernán (Jefatura de Proyectos).
- **Pasos:**
  1. En el celular, abrir Inicio → Mis tareas.
  2. Tocar una tarjeta sobre la prioridad, la obra, el solicitante, la entrega, el estado o una esquina.
  3. Lo mismo en Pedidos que pedí y en Tareas de mi gente.
- **Esperado:**
  - W-41 / HU-051: Mis tareas "en **tarjetas**", tocables (criterio del rol: tarjetas tocables enteras, ≥ 44 px).
  - Es como ya funcionan la pestaña Pedidos de la obra en el celular (W-47) y Usuarios (PU-307 de la Semana 1).
- **Obtenido:**
  - En Mis tareas, Pedidos que pedí y Tareas de mi gente, los 5 puntos de cada tarjeta dan "→ nada" (la URL sigue en `/`), en 2 tarjetas por pantalla:
    - Pixel 7: 2 corridas (`out-01-p7-1.txt`, `out-01-p7-2.txt`);
    - iPhone SE: `out-01-se-1.txt`.
  - El único lugar que abre el pedido es el link de la tarea: `a "QAS2-PER 1 entrega anterior al pedido" 280×21`, `a "QAS2-AVI R 5 vence hoy" 177×21`, etc. Mide 21 px de alto en una tarjeta de 380×268, y el `cursor` de la fila es `auto`.
  - En la ficha de obra, la misma tarjeta abre `/pedidos/59` desde cualquier punto.
  - En Avisos, tocar sobre "Servicios generales" o "hace 1 min" no abre nada. El resto de la tarjeta sí abre.
- **Evidencia:**
  - `capturas/CEL-01-1.png` (Mis tareas, Pixel 7) y `capturas/CEL-01-2.png` (iPhone SE);
  - para comparar, `capturas/F-13-obra-tarjeta-entera-p7-celular.png`;
  - `CEL\out\out-01-{p7-1,p7-2,se-1}.txt` (scratchpad del rol).
- **Causa probable (opcional):**
  - El `::after` con `inset:0` que agranda el link a toda la tarjeta solo existe para `.pedidos-de-la-obra .tabla-inicio__tarea` (`src/features/pedidos/components/obra/obra-pedidos.css:59-64`). Falta para `.tabla-inicio` en `components/inicio/celdas.tsx`.
  - En `avisos.css:212` el `::after` de `.tabla-avisos__obra` es solo el separador " · ", y las celdas de obra y fecha quedan fuera del link.
  - Es la misma raíz que JEF-02 (la fila no abre en PC): el Lead puede unirlos.
- **Regresión de:** no

## §6.1 del backlog: "Desde el celular (HU-051, HU-052)"

| punto | resultado |
|---|---|
| Ver lo mío en tarjetas y los avisos con el contador (HU-051) | **funciona** (con CEL-01, baja: hay que tocar el título). |
| Cargar un pedido corto y ver "Se le avisó a…" (HU-052 c3/c4) | **funciona** (3 de 3). |
| Comentar desde el celular (HU-052 c1) | **funciona** (3 de 3). |
| **Completar con la foto del remito** (HU-052 c2, señal de peligro de §6.1) | **funciona**: imagen real de 2,6 MB, 3 de 3 en los dos celulares. Queda Completada, avisa a Laura y Laura descarga la foto. |
| Tomar, bloquear con motivo, retomar y reasignar desde el celular | **funciona** (3 de 3). |
| Sin scroll horizontal | **funciona**: 412/412 y 375/375 en todas las pantallas. |

## No probado

- **Cámara real y HEIC del iPhone:** Chromium headless no abre la cámara ni hay iOS Safari.
  - Verifiqué que el input acepta `image/*` y `.heic/.heif` y no fuerza `capture`, así que el sistema ofrece cámara o galería.
  - La subida se probó con un JPG real de 2,6 MB por el selector de archivos (`filechooser.setFiles`). Una foto HEIC no se subió.
- **Lector de pantalla real (TalkBack / VoiceOver / NVDA):** no hay en el entorno. Revisé roles y nombres por DOM: el `aria-label` de Avisos es "Avisos, N sin leer" y el sr-only del botón de la fila es "Tomar · tarea".
- **"Menos de un minuto" con una persona escribiendo** (HU-052 c5): solo lo medí con llenado automático (3,4–4,7 s). No es un tiempo humano.
- **"Pegar link" como comprobante desde el celular:** el botón se ve (44 px), pero no completé con un link. Lo cubre el rol 1 en PC.
- **Reasignar a una persona desde el celular:** probé reasignar al sector. "Una persona" solo lo probé con teclado en PC, sin guardar.

---
CEL (rol 6): F-13 en Pixel 7 e iPhone SE 375×667 (barra, Más con 5 personas, tarjetas, W-43, W-41, W-42 completo con la foto real de 2,6 MB, W-47) y teclado en PC en 5 diálogos del pedido. 1 hallazgo (CEL-01, baja, celular). 0 ingresos reales, 6 pedidos `QAS2-CEL`.

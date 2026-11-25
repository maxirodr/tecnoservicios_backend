# Borrador B · Celular (Pixel 7 e iPhone SE 375×667) · Semana 2 · ronda 4 (corta) · 2026-11-25

- **Personas / sesiones:** Carla, Laura, Hernán y Diego (storageState de `scratchpad\s2r4\sesiones\`). Pixel 7 = `devices['Pixel 7']` (412 px); iPhone SE = viewport 375×667, `isMobile`, `hasTouch`, DPR 2. `es-AR`, `America/Argentina/Buenos_Aires`. Toques con `touchscreen.tap`. Front `staging` @ `21dfe1a`.
- **Ingresos reales gastados:** 0.
- **Datos creados (prefijo `QAS2R4-B`):** Laura → Carla, sin obra, con período (para las barras del calendario). El formulario compacto del celular no trae "Período", así que se cargaron desde la PC:
  - pedido **107** "QAS2R4-B Alquiler de grupo electrógeno de 200 kVA con tablero de transferencia automática para la parada de planta, incluye traslado y operador" (urgente, entrega 26/11, período 24/11–02/12);
  - pedido **108** "QAS2R4-B Alojamiento para la cuadrilla de soldadores que viene de Neuquén a la parada de planta de urea (cuatro personas, habitaciones dobles)" (prioritario, entrega 01/12, período 30/11–04/12).
  - No moví nada en la base ni cambié nada compartido.
- **"Tomar" y "dejar de seguir":** el `POST …/tomar` y el `DELETE …/seguir` se cortaron con `page.route(...).abort()`, así que no se tocaron pedidos de otro prefijo.
- **Scripts:** `C:\Users\GREEN\AppData\Local\Temp\claude\4fe530b1c11d8e8d\f4efcdda-aa94-4434-aafd-2e4822d4d74b\scratchpad\s2r4\B\`. Son `lib.mjs` (medición, el de la ronda 3), `cal.mjs`, `cal-dias.mjs`, `carga.mjs`, `carga-destino*.mjs`, `pestanas-entrada.mjs`, `pestanas.mjs`, `inicio-salto.mjs`, `equipos-plazos.mjs`, `reg-cal.mjs`, `reg-tarjetas.mjs` y `datos.mjs`. Las salidas están en los `*.txt` del mismo nombre.
- **Cómo se midió:**
  - Scroll horizontal de la página: `scrollWidth > clientWidth`.
  - Elementos fuera de `[0, innerWidth]`.
  - Pestaña activa "a la vista": su rectángulo dentro del de la tira `.deslizable`.
  - "Al sol": `html{filter:contrast(0.6) brightness(1.3)}`.
  - Escala de grises: CDP `Emulation.setEmulatedVisionDeficiency {type:'achromatopsia'}`.
  - Movimiento reducido: `page.emulateMedia({reducedMotion:'reduce'})`, más un envoltorio de `scrollIntoView` y `scrollTo` que registra cada llamada con su `behavior`.
  - Consola y red: limpias. Los únicos `ERR_FAILED` son los cortes a propósito.

## 1. Verificación PU-801…807 (lo que toca al celular)

| PU | título | | dispositivos | evidencia | nota |
|---|---|---|---|---|---|
| PU-801 | Calendario: prioridad solo por color | ✅ | Pixel 7, SE · mes (nov) y semana (23/11) · normal, al sol, grises, grises + al sol · 2 vueltas | `PU-801-r4-{celular,se}-{mes,semana}[-sol\|-gris\|-gris-sol\|-full].png`, `PU-801-r4-{celular,se}-dia{1,2,3}-gris-sol.png`, `cal.txt` | En el celular, mes y semana muestran la lista (83 y 68 ítems). En las 2 vueltas y los 2 equipos, 0 ítems mal: Urgente lleva el ícono `triangle-alert` + "Urgente ·" (9); Prioritario, `chevrons-up` + "Prioritario ·" (60/53); Normal, sin ícono ni palabra; los completados (5), `circle-check` y tachado, sin palabra. Ícono de 16×16 dentro del ítem; ningún texto cortado; alto mínimo 44 px. En grises y al sol se distinguen por ícono y palabra (urgente = franja oscura; prioritario = franja clara). El lector sigue con "· Urgente" / "· Urgente · completado". Las barras de período también llevan ícono y palabra. |
| PU-802 | Carga por persona: solo se toca el nombre | ✅ | Pixel 7, SE · 2 tarjetas (Carla, Diego) × 2 puntos (abajo-derecha, izquierda-medio) · 2 vueltas | `PU-802-r4-{celular,se}.png`, `PU-802-r4-{celular,se}-abre.png`, `-destino.png`, `carga.txt` | 16/16 toques abren las tareas de esa persona: `/pedidos?propietario=6` (Carla) y `/pedidos?propietario=1` (Diego). `elementFromPoint` = el link (el `::after` cubre la tarjeta). Área tocable = la tarjeta: 380×178 / 380×201 (Pixel) y 343×178 / 343×201 (SE). El link medido solo ("Carla" 41×17) sigue chico, pero no es el blanco real. Un solo link por fila. Tab: una parada por fila (`A(fila:Carla)`, `A(fila:Diego)`). Ver la nota de Opiniones sobre el filtro "Propietario". |
| PU-803 | Pestaña activa fuera de la vista en la tira | ⚠️ | Pixel 7, SE · 6 pantallas de B-03 · normal y `reducedMotion: 'reduce'` · 2 vueltas | `PU-803-r4-*-{celular,se}.png`, `pestanas-entrada.txt`, `pestanas.txt` | **Bien (4 de 6):** Carga por persona [123..288] en [16..396]; Equipos en reparación [202..396]; Plazos [209..396]; Tabla de permisos [125..287] (SE [107..269]). Al cambiar, la nueva queda a la vista en todas las tiras. Con `reduce`, todas las llamadas son `behavior:"instant"` (48/48): sin animación. Sin `reduce`, la primera es `instant` y los cambios `smooth`. **Mal (2 de 6):** al entrar a **Tareas de mi gente** y a **Obras · Terminadas**, la activa queda cortada a la derecha cuando llegan los contadores "(N)": Pixel "Tareas de mi gente" [302..468] en [16..396]; SE [265..431] en [16..359]; "Terminadas (0)" Pixel [310..448], SE [293..431]. Pasa en las 2 vueltas, los 2 equipos y con y sin `reduce` → **B-01**. |
| PU-806 | Equipos (SE): "Pendiente de aprobación" se mete en Fecha | ✅ | SE y Pixel 7 · 2 vueltas | `PU-806-r4-{se,celular}.png`, `equipos-plazos.txt` | SE: la etiqueta ocupa [33..180] en 2 renglones (45 px) y la celda Aprobación [33..180]; Fecha empieza en 196. Pixel: [33..198], Fecha en 214. Nada encimado; sin scroll horizontal. |
| PU-807 | Plazos en el celular: encabezado cortado | ✅ | Pixel 7 y SE · 2 vueltas | `PU-807-r4-{celular,se}.png`, `-pantalla.png` | Se lee entero "Cuando entre la fecha de pedido y la de entrega hay…" en 2 renglones (64 px). th [128..395] en un marco de 396 (Pixel) y [128..358] en 359 (SE). Marco `scrollWidth = clientWidth` (378/378, 341/341). Página 412/412 y 375/375. |

PU-804 y PU-805 no me tocan (son de PC, Rol A).

## 2. Regresión (§3 de las novedades) en el celular

| qué | | dispositivos | evidencia | nota |
|---|---|---|---|---|
| Calendario · fechas a mano (PU-701) | ✅ | Pixel 7, SE · 2 vueltas | `reg-calendario-{99,0000,valida,mes-dic,mes-13-45}-{celular,se}-r4.png`, `reg-cal.txt` | `desde=2026-11-99` y `desde=0000-00-00` → "Semana del 23 al 29-nov" (hoy), sin error. `desde=2026-13-45` (mes) → "Noviembre 2026". La válida `2026-12-01` → "Semana del 30-nov al 6-dic" y "Diciembre 2026". Sin scroll horizontal. |
| Calendario · días con varios pedidos y textos largos | ✅ | Pixel 7, SE | `PU-801-r4-*-full.png`, `PU-801-r4-*-dia*-gris-sol.png` | El 25/11 tiene 1 prioritario y 5 completados; el 26/11, varios urgentes y prioritarios de 130+ caracteres. El ícono queda a la izquierda, centrado en alto, y no rompe el renglón ni tapa la tarea. Ningún texto cortado. |
| Calendario · barras de período (inicio/fin) | ✅ | Pixel 7, SE | `reg-cal.txt`, `PU-801-r4-se-semana-gris.png` | "Lo que dura varios días" muestra "(24-nov al 2-dic)", "(27-nov al 3-dic)" y "(30-nov al 4-dic)" en la semana y el mes que tocan. En el mes de noviembre también "(29-oct al 4-nov)" (normal, sin ícono) y "(17 al 19-nov)". Urgente y prioritario llevan ícono y palabra. |
| Mis tareas (Carla) · tarjeta entera + "Tomar" | ✅ | Pixel 7, SE · 2 vueltas | `reg-mis-tareas-{celular,se}-r4.png`, `reg-mis-tareas-accion-*-r4.png`, `reg-tarjetas.txt` | 2 tarjetas → `/pedidos/102` y `/pedidos/101`. "Tomar" (107×44) **no navega**: se cortó `POST /api/pedidos/54/tomar`, sale "No pudimos conectarnos con el sistema…" y el foco queda en el botón "Tomar". |
| Tareas que sigo (Laura) · "dejar de seguir" | ✅ | Pixel 7, SE · 2 vueltas | `reg-que-sigo-*-r4.png`, `reg-que-sigo-accion-*-r4.png` | Tarjetas → `/pedidos/12` y `/pedidos/6`. "dejar de seguir" (167×44) **no navega**: se cortó `DELETE /api/pedidos/12/seguir`, sale el mensaje de conexión y el foco queda en el botón. |
| Todos los pedidos (Diego) | ✅ | Pixel 7, SE · 2 vueltas | `reg-todos-{celular,se}-r4.png` | Tarjetas → `/pedidos/102` y `/pedidos/28`. |
| Tareas de mi gente (Hernán) | ✅ | Pixel 7, SE · 2 vueltas | `reg-mi-gente-*-r4.png` | Tarjetas → `/pedidos/28` y `/pedidos/50`. Esta lista no tiene "Tomar" para Hernán. |
| Tiras de pestañas · la página no salta al tocar | ⚠️ | Pixel 7, SE · Inicio, Pedidos, Obras, Usuarios, ficha de obra 1, Avisos · normal y `reduce` · 2 vueltas | `reg-pestanas-*-{celular,se}-r4.png`, `pestanas.txt`, `inicio-salto.txt` | Pedidos, Usuarios, ficha de obra y Avisos: `scrollY` igual antes y después en todos los toques. **Inicio**, y Obras una vez, salta: con la página bajada 45 px (Pixel) o 71 px (SE), tocar una pestaña que todavía no cargó la deja en `scrollY 0`. Mientras dice "Cargando lo que seguís…", el alto del documento baja al de la pantalla (839/667). Con la pestaña ya cargada, no salta. Las únicas llamadas al tocar son `scrollIntoView({block:'nearest', inline:'center'})`; el salto no lo produce el arreglo de PU-803 → **B-02**. |
| Sin scroll horizontal en las pantallas de los arreglos | ✅ | Pixel 7, SE | todas las salidas `*.txt` | Página 412/412 y 375/375 en Calendario (mes, semana y fechas a mano), Carga por persona, Equipos, Plazos, Tabla de permisos, Inicio (4 pestañas), Todos los pedidos, Obras, ficha de obra y Avisos. 0 elementos que se salgan. |

## 3. Hallazgos

### B-01 · Tareas de mi gente y Obras · Terminadas: la pestaña activa vuelve a quedar cortada cuando llegan los contadores
- **Severidad:** baja
- **Tipo:** celular
- **F-XX / pantalla:** Inicio · Tareas de mi gente (`/?pestana=mi-gente`) y Obras · Terminadas (`/obras?estado=terminada`)
- **Persona:** Hernán (Jefatura de Proyectos)
- **Pasos:**
  1. En el celular (Pixel 7 o SE), entrar a `/?pestana=mi-gente`, o tocar "Tareas de mi gente" desde la tira de Pedidos.
  2. Esperar a que carguen los números de las pestañas.
  3. Mirar la tira de pestañas.
  4. Repetir con `/obras?estado=terminada`.
- **Esperado:** PU-803: "la activa queda a la vista al entrar y al cambiar".
- **Obtenido:** en el primer momento la activa sí queda a la vista: a los 1–2 s, "Tareas de mi gente" [230..396] en [16..396] y "Terminadas" [262..376]. Cuando llegan "(6)", "(4)" y "(0)", las pestañas se ensanchan, la tira no se vuelve a acomodar y la activa queda cortada a la derecha:
  - "Tareas de mi gente" [302..468] en [16..396] (Pixel) y [265..431] en [16..359] (SE); se lee "Tareas de r…" / "Tareas de mi g…";
  - "Terminadas (0)" [310..448] (Pixel) y [293..431] (SE); se lee "Termina…".
  - `scrollLeft` queda igual (188 de 260 posibles; 0 de 52).
  - Igual con y sin `reducedMotion`, en las 2 vueltas y los 2 equipos. Las otras 4 pantallas de B-03 (sin contadores) andan bien.
- **Evidencia:** `capturas/B-01-1.png` (mi gente, Pixel) · `B-01-2.png` (mi gente, SE) · `B-01-3.png` (Terminadas, Pixel) · `B-01-4.png` (Terminadas, SE) · `PU-803-r4-mi-gente-{celular,se}.png` · `logs/B-01.log`
- **Causa probable (opcional):** `shared/ui/useActivaALaVista.ts` solo vuelve a llevar la activa a la vista si cambia el elemento activo o la cantidad de pestañas (`ultima.current.activa === activa && pestanas === ...` → `return`). Los contadores cambian el ancho sin cambiar ninguna de las dos cosas. Podría escuchar el tamaño (`ResizeObserver`) o incluir el `scrollWidth` en la comparación.
- **Regresión de:** PU-803 (arreglo incompleto: es el mismo síntoma de B-03 en 2 de sus 6 pantallas)

### B-02 · Inicio en el celular: al tocar una pestaña que no cargó todavía, la página sube arriba de todo
- **Severidad:** baja
- **Tipo:** celular
- **F-XX / pantalla:** Inicio · tira "Mis tareas / Pedidos que pedí / Tareas que sigo / Tareas de mi gente" (`/`); una vez también Obras (`/obras?estado=…`) con `reduce`
- **Persona:** Hernán
- **Pasos:**
  1. En el celular, entrar a `/?pestana=mi-gente`.
  2. Bajar la página un poco, dejando la tira a la vista bajo la barra (45 px en Pixel, 71 px en SE).
  3. Tocar "Tareas que sigo" (primera vez en la sesión).
- **Esperado:** §3 de las novedades: "en el celular, al tocar una pestaña la página no salta".
- **Obtenido:**
  - A los 20 ms `scrollY` pasa de 45 a 0 (Pixel) o de 71 a 0 (SE). El contenido pasa a "Cargando lo que seguís…" y el documento se achica al alto de la pantalla (h2495 → h839; SE h2591 → h667). Cuando carga (h1309 / h1380), la página queda arriba y la tira bajó de y=100 a y=145 / 171.
  - Volviendo a una pestaña ya cargada no salta: y45 → y45, y71 → y71.
  - En 2 vueltas × 2 equipos, igual con y sin `reduce`. En `pestanas.txt` también con "Pedidos que pedí".
  - En Obras pasó una vez (v2, `reduce`: y36 → 0 con h839, mientras cargaba la lista).
  - El arreglo de PU-803 no es la causa: la única llamada al tocar es `scrollIntoView({block:'nearest', inline:'center'})`.
- **Evidencia:** `capturas/B-02-1.png` (Pixel) · `capturas/B-02-2.png` (SE; la página ya arriba después del toque) · `logs/B-02.log` (línea de tiempo `scrollY` / alto)
- **Causa probable (opcional):** al cambiar de pestaña, la lista se reemplaza por el estado "Cargando…" en lugar de mantener el contenido anterior mientras llega el nuevo, y el navegador recorta el `scrollY` al nuevo alto.
- **Regresión de:** no determinado. No es del commit de PU-803 (ver las llamadas), y sin git no puedo comparar con la ronda 3.

## 4. §6.1 del backlog (lo que toca a esta ronda)
- Calendario en el celular como lista por día, con prioridad visible sin color: **funciona**.
- Mis tareas / Tareas que sigo / Todos / Mi gente en tarjetas tocables enteras; "Tomar" y "dejar de seguir" sin abrir el pedido: **funciona**.
- 390 px sin desplazamiento horizontal (412 y 375 en las pantallas de los arreglos): **funciona**.

## 5. Opiniones (gusto, no hallazgos)
- **Carga por persona → tareas de la persona:** la lista filtra bien (`propietario_id=6` en la API). Pero con el servidor lento, el filtro "Propietario" muestra "todos" hasta que llega la lista de personas: vi 2 opciones durante 2–3 s. Después pasa a "Carla". Es transitorio: no lo reporto.
- **Tarjeta de Diego en Carga por persona:** tiene 0 abiertas y lleva a "No hay pedidos con estos filtros. 0 de 112". Es correcto, pero una tarjeta en 0 podría no ser tocable o decirlo.
- **Ficha de obra con `reduce`:** "Documentos y contactos" queda 2 px pasada del borde: [184..398] en [16..396] y [147..361] en [16..359]. Sin `reduce` queda [182..396]. No se nota a la vista.
- **Calendario en el SE:** el título "Semana del 23 al 29-nov" parte en "29-" / "nov".
- **Calendario:** los vencidos (23/11) se ven igual que los urgentes de mañana. No es parte de PU-801.

## 6. No probado
- PU-804 y PU-805: son de PC (Rol A).
- Tab y foco después de la acción (PU-703) en PC: es del Rol A. En el celular, la única medida que tomé es que el foco queda en el botón después del "Tomar" o "dejar de seguir" cortado.
- "Tomar" y "dejar de seguir" de verdad: se cortaron (pedidos de otro prefijo). Solo se vio que no navegan y el mensaje.
- Lector de pantalla real (TalkBack/VoiceOver): solo se leyó el texto `sr-only` del DOM.
- Movimiento reducido en el sistema operativo real: se emuló con `emulateMedia`.
- Regresión de B-02: sin git no la puedo comparar con la ronda 3.
- Backend sano (health, `failed_jobs`, canal `errors`): es del Lead. Health dio 200 cada vez que lo miré.
- La API respondió lenta (varios segundos por pedido, otros roles en paralelo). Las mediciones se tomaron después de `networkidle` y con líneas de tiempo, y no saqué conclusiones de los "Cargando…" largos.

— Rol B (celular), subagente QA NexoSmart, 2026-11-25 · 0 ingresos reales · datos `QAS2R4-B` (pedidos 107 y 108).

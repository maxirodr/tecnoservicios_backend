# Borrador B · Celular (Pixel 7 e iPhone SE 375×667) · Semana 2 · ronda 3 (visual) · 2026-11-24

- **Personas / sesiones:** Carla, Laura, Hernán, Diego (storageState de `scratchpad\s2r3\sesiones\`); contexto sin sesión para Ingreso. Pixel 7 = `devices['Pixel 7']` (412×839 útil); iPhone SE = viewport 375×667, `isMobile`, `hasTouch`, DPR 2. `es-AR`, `America/Argentina/Buenos_Aires`. Toques con `touchscreen.tap`.
- **Ingresos reales gastados:** 0. La pantalla de código se vio respondiendo `POST /api/auth/pedir-codigo` con un 200 falso desde `page.route` (no se mandó ningún mail).
- **Datos creados (prefijo `QAS2R3-B`, todo desde el celular):** obra 6 "QAS2R3-B Reemplazo integral de cañería de vapor y condensado en planta de urea, sector compresores, con andamiaje y aislación térmica nueva" (9-983-2026 REV01, Hernán, oferta = JPG ficticio); pedido 92 "QAS2R3-B Compra de bridas ciegas ANSI 150 de 6 pulgadas, juntas espirometálicas y bulones grado B7 para la parada de planta del jueves" (Laura → Carla, obra 6, **completado** por Carla con `remito-QAS2R3-B.jpg`); pedido 93 "QAS2R3-B Coordinar con el proveedor el retiro de los matafuegos vencidos de la oficina central y traer los de reemplazo antes del viernes" (Laura → Carla, sin obra, entrega 23/11, **abierto**); proveedor 2 "QAS2R3-B Metalúrgica y Tornería Industrial del Sur Bahiense…" (Diego); equipo en reparación 1 "QAS2R3-B Hidrolavadora trifásica…" (Diego). Nada movido en la base, nada compartido cambiado.
- **"Tomar" sin tocar datos ajenos:** el `POST …/tomar` se cortó con `page.route(...).abort()` (solo se miró que no navega y el mensaje).
- **Scripts:** `C:\Users\GREEN\AppData\Local\Temp\claude\4fe530b1c11d8e8d\f4efcdda-aa94-4434-aafd-2e4822d4d74b\scratchpad\s2r3\B\` (`lib.mjs` medición, `barrido.mjs`, `tarjetas.mjs`, `extras.mjs`, `e2e.mjs`, `datos2.mjs`, `dialogo-alto.mjs`, `equipo-pill.mjs`, `plazos.mjs`; salidas `*.txt` / `barrido-*.json`).
- **Cómo se midió:** en cada pantalla `documentElement.scrollWidth > clientWidth`; elementos con `getBoundingClientRect` fuera de `[0, innerWidth]` que no estén dentro de un contenedor con `overflow-x` propio; todo control (`a[href]`, `button`, `input`, `select`, `textarea`, `[role=tab]`…) visible con ancho o alto < 44 px (casilla/radio: se mide su `label`). "Al sol": `html{filter:contrast(0.6) brightness(1.3)}`. Consola y red (4xx/5xx de `/api`).

## 1. Pantallas → ✅ / ⚠️ / ❌

Capturas `<pantalla>-celular-r3.png` (Pixel 7) y `<pantalla>-se-r3.png` (SE) en `capturas\`; "al sol": `*-sol-r3.png`. Ninguna pantalla con scroll horizontal (Pixel 412/412, SE 375/375 en las 37 rutas) ni elementos que se salgan. Consola y red limpias salvo los 500 forzados a propósito.

| pantalla | | qué se miró | evidencia | nota |
|---|---|---|---|---|
| Inicio · Mis tareas (Carla) | ✅ | h-scroll, 44 px, tarjeta entera (2 tarjetas × 2 puntos × 2 equipos → abre el pedido), "Tomar" 107×44 no abre (queda en `/` con "No pudimos conectarnos con el sistema…" por el corte), al sol | `inicio-mis-tareas-{celular,se}-r3.png`, `-sol-r3.png`, `B-tomar-despues-{celular,se}.png` | Los títulos `a.tabla-inicio__tarea` miden ~200×17 pero el `::after` cubre la tarjeta: el blanco real es la tarjeta (193–362 px de alto). Al sol se leen "Urgente" (ícono + texto), "No iniciada", "23/11/2026 · vencido". |
| Inicio · Pedidos que pedí / Tareas que sigo / Tareas de mi gente | ✅ | ídem (Laura, Laura, Hernán) | `inicio-que-pedi-*`, `inicio-que-sigo-*`, `inicio-mi-gente-*` | Tarjeta entera abre en las 3. En la tira, "Tareas de mi gente" activa queda fuera de vista → **B-03**. |
| Estados de Inicio (cargando / vacío / error) | ✅ | API demorada 3 s; filtro sin resultados; 500 en `/api/pedidos/inicio` y `/mis-tareas` | `estado-cargando-pedidos-*-r3.png`, `estado-vacio-…`, `estado-error-…` | "Cargando tus tareas…"; "No hay tareas con estos filtros."; tras los reintentos (~10 s) "No pudimos cargar esta información" + "Reintentar". Sin desborde en SE. |
| Pedido · detalle y recorrido | ✅ | pedidos 81, 76, 92 y 93 (títulos de 130+ caracteres) | `pedido-detalle*-{celular,se}-r3.png`, `pedido-detalle-largo-*`, `pedido-largo-sg-*`, `B-e2e-6-completado-full-celular.png` | Títulos largos parten bien; "Historial del recorrido" legible; botones de acción ≥ 44. |
| Pedido · 5 ventanas (Tomar → En curso, Bloquear, Completar, Reasignar, Comentar) | ✅ | apertura, controles medidos, alto en SE, Escape | `pedido-{bloquear,completar,reasignar,tomar}-{celular,se}-r3.png`, `B-dialogo-*-deslizado-se.png` | Todos los controles ≥ 44 (radios de Reasignar: label 303×44 / 303×50). En SE Reasignar mide 802 px y Completar 730 px (pantalla 667): deslizando dentro de la ventana los botones quedan a la vista (y=571) y no los tapa la barra. "Tomar → En curso" actúa directo (sin ventana), como en PC. |
| Nuevo pedido / Editar pedido | ✅ | formulario completo con textos de 130+ y aclaración larga | `pedido-nuevo-*`, `pedido-editar-*`, `B-e2e-2-nuevo-*`, `B-e2e-3-creado-*` | Único "chico": `input[type=file]` 1×1 (oculto a propósito; el control visible es "Subir archivo" 128×44). |
| Todos los pedidos | ✅ | 50 tarjetas, tarjeta entera, al sol | `pedidos-todos-*-r3.png`, `-sol-r3.png` | El botón de orden "Obra ↕" (41×44) está en un `thead` recortado (1 px, `clip`): no se ve en celular, no cuenta. Ver Opiniones (filtros). |
| Servicios generales | ✅ | 43 tarjetas, tarjeta entera | `pedidos-servicios-generales-*` | Link en el texto "Todos los pedidos" 120×16 (enlace dentro de un párrafo; no lo cuento). |
| Carga por persona | ⚠️ | tarjeta entera, 44 px | `B-01-1.png`, `B-01-2.png` | **B-01** (solo se toca el nombre: "Carla" 42×19, "Diego" 46×19). **B-03** (pestaña activa fuera de vista). |
| Calendario (mes y semana) | ⚠️ | Carla, Pixel y SE, al sol | `calendario-{mes,semana}-{celular,se}-r3.png`, `B-02-*.png` | **B-02** prioridad solo por color. Completados con ✓ y tachado: bien. |
| Equipos en reparación | ⚠️ | vacío y con 1 equipo de texto largo | `pedidos-equipos-*-r3.png`, `B-04-*.png` | Vacío: "Todavía no hay equipos en reparación." + "+ Agregar equipo". **B-04** en SE. **B-03**. editar/quitar ≥ 44. |
| Obras · listado | ✅ | En ejecución (0, vacío), Confirmadas (6), tarjeta entera, al sol | `obras-*-r3.png`, `obras-confirmadas-*` | El link "9-983-2026 · …" mide 87×24 pero el `::after` cubre la tarjeta (tap abajo/izquierda → `/obras/6` y `/obras/5`). |
| Obras · ficha / alta / editar / presupuesto por rubro | ✅ | obra 1 y obra 6 (nombre de 133 caracteres), alta completa desde el celular | `obra-ficha-pedidos-*`, `obra-nueva-*`, `obra-editar-*`, `obra-presupuesto-*`, `B-e2e-1-obra-creada-celular.png` | Pedidos de la obra: tarjeta entera abre (`/pedidos/23`). |
| Proveedores (lista y ficha) | ✅ | con el genérico y con uno de 130 caracteres | `proveedores-*-r3.png`, `proveedor-ficha-*`, `B-proveedores-tap-*.png`, `B-proveedor-nuevo-celular.png` | Tarjeta entera → `/proveedores/2`. |
| Usuarios / Sectores / Tabla de permisos / Plazos | ⚠️ | Diego | `usuarios*-r3.png`, `B-05-*.png` | Usuarios: tarjeta entera abre la ficha (botón estirado). Permisos: tabla deslizable con "Deslizá para ver más →". **B-05** Plazos. **B-03** (Plazos y, en SE, Tabla de permisos). |
| Mi perfil, campana y avisos | ✅ | Laura / Carla | `perfil-*`, `avisos-*` | Tarjeta de aviso entera → abre el pedido (`/pedidos/93`, `/pedidos/92`). |
| Buscador | ✅ | `?q=QAS2R2` | `buscador-*` | — |
| Barra inferior y "Más" | ✅ | Carla y Diego, cada ítem tocado | `mas-*-r3.png`, `extras-*.txt` | Barra: 4 ítems 103×60 (Pixel) / 94×60 (SE), marca el activo. "Más" Carla: Pedidos que pedí · Tareas que sigo · Pedidos · Calendario · Proveedores · Mi perfil · Salir; Diego suma Tareas de mi gente y Usuarios. Cada ítem 378×48 / 341×48 y lleva a su pantalla. "Salir" no se tocó (regla de sesiones). |
| Ingreso y código | ✅ | sin sesión; código con 200 falso | `ingreso-{celular,se}-r3.png`, `ingreso-codigo-{celular,se}-r3.png` | Campo de código `inputmode=numeric`, `autocomplete=one-time-code`; "Reenviar código (29 s)". Sin chicos ni desborde. |
| Recorrido de punta a punta en el celular | ✅ | Hernán crea la obra → Laura crea 2 pedidos → Carla toca la tarjeta (abre el 92) → Completar con comentario + `remito-QAS2R3-B.jpg` (26 KB) | `B-e2e-*.png`, `pedido-completar-celular-r3.png` | "Pedido completado. Le avisamos a Laura."; estado Completada; "Comprobantes: remito-QAS2R3-B.jpg · 26 KB"; recorrido "Carla lo completó sin tomarlo…" y "Carla · Agregó comprobante: remito-QAS2R3-B.jpg". |

## 2. Hallazgos

### B-01 · Carga por persona: la tarjeta no se puede tocar, solo el nombre (42×19 px)
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

### B-02 · Calendario en el celular: la prioridad se distingue solo por el color
- **Severidad:** media
- **Tipo:** accesibilidad
- **F-XX / pantalla:** F-11 · W-03 Calendario, vista mes y semana · `/calendario`, `/calendario?vista=semana`
- **Persona:** Carla (Compras)
- **Pasos:** 1. En el celular, Más → Calendario (mes o semana). 2. Mirar los pedidos del lunes 23 y del martes 24.
- **Esperado:** §4 de la ronda 3: la información no se distingue solo por el color; §2 Rol A/B: "qué está vencido o urgente, y que se note también sin el color (texto o ícono)".
- **Obtenido:** cada pedido es una franja con el texto de la tarea; Urgente = fondo rojo, Prioritario = amarillo, Normal = gris, sin texto ni ícono visible (la prioridad solo está en un `sr-only`: "· Urgente"). Tampoco hay leyenda en la pantalla. "QAS2R3-A Comprar bridas de acero…" y "QAS2R3-B Coordinar con el proveedor…" (23/11, vencidos) se ven igual que un urgente de mañana. Al sol (contraste 0,6) se siguen distinguiendo los colores, pero sigue sin haber texto. Visto en Pixel y SE, mes y semana.
- **Evidencia:** `capturas/B-02-1.png` (SE semana) · `capturas/B-02-2.png` (SE semana, al sol) · `capturas/B-02-3.png` (SE mes) · `capturas/B-02-4.png` (Pixel semana, al sol)
- **Causa probable (opcional):** `components/calendario/PedidoEnCalendario.tsx:13` (clase `--${prioridad}`) y `:28` (texto solo para lector de pantalla). Nota: `pedidos.md` RN-PED-47 dice "con el **color de su prioridad**"; si el Lead lo da por esperado, pasa a Opiniones.
- **Regresión de:** no (igual en `antes\celular-calendario-semana.png`)

### B-03 · En el celular la pestaña activa queda fuera de la vista en la tira de pestañas
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

### B-04 · Equipos en reparación (SE): "Pendiente de aprobación" se mete en la columna Fecha
- **Severidad:** baja
- **Tipo:** celular
- **F-XX / pantalla:** Equipos en reparación · `/pedidos/equipos`
- **Persona:** Diego
- **Pasos:** 1. En un iPhone SE (375 px), abrir Equipos en reparación con un equipo en "Pendiente de aprobación". 2. Mirar la fila "Aprobación | Fecha" de la tarjeta.
- **Esperado:** cada dato en su columna, nada encimado.
- **Obtenido:** la etiqueta "Pendiente de aprobación" termina en x=200 y la celda "Fecha" empieza en x=196 (la celda Aprobación termina en 180): el "—" de la fecha queda pegado a la etiqueta. En Pixel 7 entra (Fecha en 214). 2 vueltas iguales.
- **Evidencia:** `capturas/B-04-1.png` · `capturas/B-04-2.png` · `logs/B-04.log`
- **Regresión de:** no determinado (en `antes\`/`despues\` no hay captura con equipos)

### B-05 · Plazos en el celular: el encabezado "Cuando entre la fecha de pedido y la de entrega hay…" se corta sin aviso
- **Severidad:** baja
- **Tipo:** celular
- **F-XX / pantalla:** Plazos de la prioridad · `/usuarios/plazos`
- **Persona:** Diego
- **Pasos:** 1. En el celular, Más → Usuarios → Plazos de la prioridad. 2. Leer el encabezado de la segunda columna.
- **Esperado:** el encabezado se lee entero, o hay la pista "Deslizá para ver más →" como en Tabla de permisos.
- **Obtenido:** se ve "Cuando entre la fecha de pedido y la de en" (Pixel) / "…y la" (SE), cortado en el borde. La tabla se puede deslizar (`overflow-x: auto`, 460 px de contenido en 378 / 341) pero solo por el encabezado y no hay pista. Las filas ("hasta 1 día", "hasta 7 días", "más de 7 días") se leen bien. 2 vueltas en cada equipo.
- **Evidencia:** `capturas/B-05-1.png` (Pixel) · `capturas/B-05-2.png` (SE) · `usuarios-plazos-{celular,se}-r3.png`
- **Regresión de:** no determinado (en `despues\celular-usuarios-plazos.png` ya se ve igual)

## 3. §6.1 del backlog (lo que toca al celular)
- Mis tareas en tarjetas con prioridad, tarea, obra, solicitante, entrega y estado; vencidos en rojo con "· vencido": **funciona**.
- Barra Tareas · Obras · Avisos · Más y "Más" según permisos: **funciona**.
- Detalle con Tomar / Bloquear / Completar; Completar con comentario y comprobante (archivo/foto): **funciona** (foto JPG subida desde el celular).
- Nuevo pedido en el celular: **funciona**. Obras con "N pedidos abiertos" y Pedidos de la obra en tarjetas: **funciona**.
- 390 px sin desplazamiento horizontal: **funciona** (412 y 375, 37 rutas).

## 4. Opiniones (gusto, no hallazgos)
- Todos los pedidos en el celular: los 6 filtros + fechas ocupan toda la primera pantalla; la primera tarjeta aparece recién al deslizar (igual en `despues\`). Un "Filtros" plegable ayudaría.
- En el SE, Inicio de Carla no muestra ninguna tarjeta completa en la primera pantalla (saludo + 3 filtros + "+ Nuevo pedido").
- Ventanas con títulos de 130+ caracteres (Completar, Reasignar): el título ocupa 6 renglones en tipografía grande y empuja el formulario; podría ir más chico o recortado con la tarea entera debajo.
- Detalle completado: el comprobante figura dos veces ("remito-QAS2R3-B.jpg · 26 KB" y debajo "remito-QAS2R3-B.jpg: Carla · 24/11/2026 12:25").

## 5. No probado
- **Pantalla de error del sistema (§3 de la ronda):** no la busqué (es del Lead). De pasada: un `page.route('**/api/pedidos**')` mío pegó también en el módulo de Vite `/src/features/pedidos/api/pedidos.api.ts` (500) y la pantalla quedó sin `main` hasta el timeout; no tengo captura de qué mostró, así que no lo reporto.
- **Ingreso real con código:** no se gastaron ingresos (sin cupo para el rol); la pantalla de código se vio con respuesta falsa.
- **"Salir"** en Más: no se tocó (regla de sesiones del seeder).
- **Tomar de verdad** desde la tarjeta: el `POST` se cortó para no tocar pedidos de otro prefijo; el tomar real se ve en el recorrido (Carla completó sin tomar).
- **Cámara real** del teléfono (Playwright no la emula): se subió un JPG generado.
- Teclado virtual tapando campos y gestos reales (pellizcar, deslizar con inercia): no emulables con fidelidad en Chromium headless.
- Regresión de B-04/B-05: sin git (no hago git); el Lead puede confirmarlo.

— Rol B (celular), subagente QA NexoSmart, 2026-11-24 · 0 ingresos reales · datos `QAS2R3-B` (obra 6, pedidos 92 y 93, proveedor 2, equipo 1).

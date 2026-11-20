# Borrador PED · Pedidos de punta a punta (PC) · Semana 2 · ronda 1

- **Fecha:** 2026-11-20, de 11:09 a 11:31 (hora de Argentina; base y logs en UTC = +3 h).
- **Código probado:** worktrees `_qa_s2` en `staging` (back `7e007dd`, front `d8f2433`). API :8340 y front :5316. Health 200 (0,27 s) al empezar; no hubo lentitud ni 503. Sin `errors-2026-11-20.log` (ningún error del back en toda la prueba) y `failed_jobs` = 0 al terminar.
- **Personas / sesiones:** Laura, Carla, Sofía, Pablo, Hernán y Diego (`sesiones/<persona>.json`), PC 1366×768, `es-AR`, `America/Argentina/Buenos_Aires`. Nunca se tocó "Salir".
- **Ingresos reales gastados: 0.**
- **Datos creados (prefijo `QAS2-PED`):**
  - Obra `QAS2-PED Reparación de losa tanque B` (id 1, N.º 9-801-2026, responsable Hernán), creada por Diego desde la pantalla.
  - Pedidos 3, 7, 10, 13, 16–21, 23, 24, 40, 41 y 43 (todos con `QAS2-PED` en la tarea). Los de prioridad en los bordes (16–21, 23, 40, 41) son de Laura para Laura, para no sumarle avisos a nadie.
- **Plazos (W-50):** los cambié a **2 / 4** de 11:21:51 a 11:22:17 y los volví a **1 / 7** desde la pantalla. Verificado en la base: `plazos_prioridad` = `1 | 7` (`updated_by` quedó en 1 = Diego, antes `NULL`). En esa ventana solo se cargó otro pedido ajeno (id 22, QAS2-AVI, a 13 días): da Normal con cualquiera de los dos plazos, así que no lo afectó.
- **Base:** no moví fechas a mano; nada que restaurar.
- **Scripts y salidas:** `C:\Users\GREEN\AppData\Local\Temp\claude\4fe530b1c11d8e8d\f4efcdda-aa94-4434-aafd-2e4822d4d74b\scratchpad\s2\PED\`
  - scripts: `lib.mjs`, `crear.mjs`, `01-obra.mjs` … `11-evidencia.mjs`;
  - salidas: `out\03.txt`, `05.txt`, `06.txt`, `07.txt`, `08.txt`, `09.txt`, `09b.txt`, `10.txt`.

## Funcionalidades

| F-XX | Estado | Pantallas | Evidencia | Nota |
|---|---|---|---|---|
| F-24 Crear | ✅ | W-08 `/pedidos/nuevo` | `F-24-nuevo-pedido-pc.png`, `F-36-nuevo-pedido-sin-obra-pc.png` | Laura → Carla con obra y todas las columnas: tarea, obra, sector, propietario, seguidora (Sofía), con quién, fecha de pedido, entrega, período, 2 links (Drive y Maps), 1 archivo PDF, aclaraciones con ñ y "m²". Antes de crear se lee "Al crear, Carla recibe el aviso."; después, "Pedido creado. Se le avisó a Carla.". El desplegable de propietario se achica a las personas del sector elegido. Sentido inverso: Carla → Laura (sin obra) y Diego → Hernán (con obra), con la misma confirmación. Si me lo pido a mí misma no aparece la línea del aviso y la confirmación es solo "Pedido creado.". |
| F-25 Fecha de pedido | ✅ | W-08 | `F-25-fecha-pedido-futura-pc.png` | Viene con hoy (20/11/2026). Futura: el campo tiene `max` = hoy y, escrita a mano, "La fecha de pedido no puede ser posterior a hoy." sin salir de la pantalla. Por API, 422 con el mismo texto. Hacia atrás (18-11) se acepta. |
| F-26 Prioridad | ⚠️ | W-08 · W-09 · W-32 | `F-26-prioridad-entrega-anterior-pc.png`, `F-26-corregida-a-mano-pc.png` | Bordes con plazos 1/7, en el formulario, en el detalle y en `prioridad-sugerida`: **0 días → Urgente, 1 → Urgente, 2 → Prioritario, 7 → Prioritario, 8 → Normal**, y entrega anterior al pedido (18-11 → 17-11) **→ Urgente** (DU-P08). Corrección a mano y "(corregida a mano)" OK. La cuenta dice "-1 días": **PED-04**. |
| F-27 Archivos o links | ✅ | W-08 · W-29 | `F-24-nuevo-pedido-pc.png`, `F-31-completado-pc.png` | Link con nombre para mostrar, link sin nombre (toma "Plano sector bombas.pdf" del link) y archivo PDF, en el alta y en el cierre. El historial los nombra sin URL. |
| F-28 Tomar | ✅ | W-09 · W-01 | `F-28-ya-lo-tomo-pc.png` | Propietaria toma → En curso ("Lo tomaste: el pedido pasó a En curso."). Desde el sector, quien toma queda propietaria (DU-P02). Dos a la vez: Sofía toma y Pablo, con la pantalla vieja, recibe 409 y lee "Ya lo tomó Sofía."; la pantalla se refresca con Sofía como propietaria y sin el botón Tomar. |
| F-29 Bloquear / retomar | ⚠️ | W-30 · W-09 | `F-29-bloqueado-pc.png` | Sin motivo: "Contá qué lo traba.". Con motivo: Bloqueada, "Bloqueado: …" en la cabecera, sin botón Completar; por API completar → 409 "Primero retomalo: está bloqueado.". Retomar → En curso. **Bloquear sin tomar** (Hernán, desde No iniciada) OK (DU-P03); **bloquear desde el sector** (Carla, en un pedido de Compras sin persona) la deja propietaria y Bloqueada (DU-P02). El error queda a la vista después de escribir: **PED-03**. |
| F-30 Comentar | ✅ | W-09 | `F-34-recorrido-pc.png` | Ida y vuelta Carla ↔ Laura con autor, fecha y hora ("Carla · 20/11/2026 11:14"), del más viejo al más nuevo. El comentario de reasignar y el de reabrir quedan en el hilo. |
| F-31 Completar con comprobante | ⚠️ | W-29 · W-09 | `F-31-completar-pc.png`, `F-31-completado-pc.png` | Sin comentario: "Contá cómo se resolvió.". Con comentario + archivo (`remito-0001-00004567 QAS2-PED.pdf`) **y** link (`Remito año 0001-00004567.pdf`): Completada, "Completado por Carla el 20/11/2026 11:14: …", comprobantes con quién y cuándo. El error queda a la vista después de escribir: **PED-03**. |
| F-32 Reasignar | ✅ | W-31 | `F-32-reasignar-persona-pc.png`, `F-32-reasignar-sector-pc.png`, `F-04-mis-tareas-en-el-sector-pc.png` | A una persona: Laura → Sofía (En curso sigue En curso) y Sofía → Pablo con el pedido **Bloqueado**: sigue Bloqueada con su motivo (DU-P04). Al sector: Pablo → Ingeniería: "En el sector Ingeniería de proyectos · Sin propietario · lo toma el primero que pueda", vuelve a **No iniciada** y aparece en Mis tareas de Laura, Sofía y Pablo con "Tomar"; Laura lo toma y desaparece de las de Sofía. Pañol y Logística (sin usuarios activos): 422 "Pañol y Logística no tiene usuarios activos: elegí una persona.". El desplegable no ofrece al propietario actual. |
| F-33 Editar | ✅ | W-32 `/pedidos/24/editar` | `F-33-editar-sugerida-pc.png`, `F-68-recalculo-al-editar-pc.png` | Tarea, entrega, obra → Sin obra → obra, link nuevo, prioridad manual Urgente; con la manual, cambiar la entrega mantiene Urgente y muestra "Calculada: Prioritario · 2 días" con **"Usar la calculada"**, que la vuelve Prioritario sola. Carla (propietaria, no la pidió) edita las aclaraciones pero no ve el desplegable de prioridad; por API, 403 "Solo quien lo pidió puede corregir la prioridad." (DU-P07). Guardar sin cambios: "No había cambios para guardar." y ninguna actividad nueva. **Editar no avisa:** avisos del pedido 24 = 1 antes y 1 después de 7 ediciones (DU-P24). |
| F-34 Recorrido | ⚠️ | W-09 | `F-34-recorrido-pc.png` | Completo y en orden: creó, links, "Sistema avisó a Carla (asignado)", tomó, comentó, bloqueó con motivo, retomó, completó, comprobantes, reasignó (de → a, "(sector) → No iniciada"), reabrió, editó, y una línea "Sistema avisó a …" por cada aviso (tomado, comentado, bloqueado, completado, reasignado). Nombre repetido en cada línea: **PED-01**; tipo de aviso crudo "(completado gente)": **PED-02**. |
| F-38 Propietario y seguidores | ✅ | W-08 · W-09 · W-28 | `F-07-tareas-que-sigo-pc.png` | Un solo propietario, con su sector. Sofía sumada al crear recibe tomado/comentado/bloqueado/completado (recorrido). Diego "Seguir" → "Ahora seguís este pedido.", aparece en Tareas que sigo y recibe el aviso del comentario de Carla; "dejar de seguir" desde W-28 lo saca ("No seguís ningún pedido."). Seguir uno propio por API: 422 "Ya recibís los avisos de este pedido.". Ver la observación 1. |
| F-65 Reabrir | ⚠️ | W-09 | `F-65-reabrir-pc.png`, `F-65-reabierto-pc.png` | Laura (quien pidió): "Vuelve a En curso con Carla, que recibe el aviso.", sin texto "Contá qué falta.", con texto vuelve a **En curso** con Carla, el comentario va al hilo, el cierre se limpia y los dos comprobantes siguen a la vista. Sofía (seguidora) no ve el botón y por API recibe 403 "Solo quien lo pidió puede reabrirlo.". El error queda a la vista después de escribir: **PED-03**. |
| F-04 Mis tareas | ✅ | W-01 `/` | `F-04-mis-tareas-pc.png`, `F-04-mis-tareas-en-el-sector-pc.png` | Refleja cada paso: el pedido nuevo aparece en la de Carla con "(26/11/2026 al 13/12/2026)", sale al completarse y vuelve En curso al reabrirse; los del sector aparecen con "Sin propietario · lo toma el primero que pueda" y Tomar. Contadores "4 abiertas · 0 vencidas". |
| F-05 Pedidos que pedí | ✅ | W-27 | `F-05-pedidos-que-pedi-pc.png` | El último movimiento sigue cada paso: "creado; nadie lo tomó todavía" → "Carla: 'QAS2-PED Falta la medida…'" → "Carla lo bloqueó: '…'" → sale de Abiertos al completarse. En Todos, el completado va al final ("completado"). |
| F-14 Historial (pedidos) | ✅ | W-32 | `F-14-historial-cambios-pc.png` | "Tarea: 'QAS2-PED Alquiler de hidro' → 'QAS2-PED Alquiler de hidrolavadora'", "Obra: QAS2-PED Reparación de losa tanque B → Sin obra", "Fecha de entrega: 30-11 → 02-12 (la prioridad sigue Normal: 10 → 12 días)", "Agregó link o archivo: ficha técnica", "Prioridad: Normal → Urgente (corregida a mano)", "Prioridad: Urgente (corregida a mano) → Prioritario", el más nuevo arriba. |
| F-36 Sin obra | ✅ | W-08 · W-09 | `F-36-nuevo-pedido-sin-obra-pc.png` | "Sin obra / Servicios generales" en el alta; en el detalle "Sin obra · Servicios generales" y en las listas "Servicios generales". |
| F-68 Plazos aplicados | ✅ | W-50 `/usuarios/plazos` · W-08 · W-32 | `F-68-plazos-2-4-pc.png`, `F-68-recalculo-al-editar-pc.png` | Con 2/4: "Guardamos los plazos. Valen para los pedidos que se carguen desde ahora."; 2 días → Urgente, 4 → Prioritario, 5 → Normal (pedido 23 creado Normal). El de 7 días cargado con 1/7 **siguió Prioritario**; al editarle la entrega a 6 días se recalculó con 2/4: "Fecha de entrega: 27-11 → 26-11 (la prioridad pasó de Prioritario a Normal)". Restaurado a 1/7 y verificado. |

Otros que toqué de paso: DU-P06 "Cargado por error" ✅ (pedido 10, duplicado: se completa con ese comentario; `DELETE /api/pedidos/10` → 405 "Esa acción no está disponible."). Campana: se ve el contador (Laura 4 → 9+) durante toda la prueba, y el mail de asignación y reasignación está en `laravel-2026-11-20.log` ("Te asignaron un pedido: QAS2-PED Alquiler de andamio…", "Te reasignaron un pedido: …"). El detalle lo prueba el rol AVI.

## Hallazgos

### PED-01 · El recorrido repite el nombre de quien hizo cada cosa
- **Severidad:** baja
- **Tipo:** consistencia-con-documento
- **F-XX / pantalla:** F-34 · W-09 Detalle del pedido · `/pedidos/{id}` (Historial del recorrido)
- **Persona:** Laura (Ingeniería de proyectos); se ve igual con Carla, Sofía, Pablo y Diego
- **Pasos:** 1. Laura crea un pedido para Carla. 2. Carla lo toma y comenta. 3. Abrir el pedido y mirar "Historial del recorrido".
- **Esperado:** "13-oct 16:20 · Laura creó el pedido", "13-oct 16:20 · sistema avisó a Carla (asignado)" (maqueta W-09; RN-PED-31).
- **Obtenido:** "20/11/2026 11:12 · Laura Laura creó el pedido", "20/11/2026 11:14 · Carla Carla lo tomó → En curso", "20/11/2026 11:12 · sistema Sistema avisó a Carla (asignado)". Se vio en todos los pedidos (3, 7, 10, 13, 24, 43).
- **Evidencia:** `capturas/PED-01-1.png` (pedido 3) · `capturas/PED-01-2.png` (pedido 7) · `capturas/F-34-recorrido-pc.png`
- **Causa probable (opcional):** el front pone el nombre (`Recorrido.tsx:29`, `{item.quien?.nombre ?? 'sistema'}`) y el texto que arma el back ya empieza con el nombre (`RecorridoQuery.php`, "Laura creó el pedido", "Sistema avisó a …").
- **Regresión de:** no

### PED-02 · El recorrido muestra el tipo de aviso como clave: "(completado gente)"
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-34 · W-09 Detalle del pedido · `/pedidos/{id}` (Historial del recorrido)
- **Persona:** Laura (Ingeniería de proyectos)
- **Pasos:** 1. Laura le pide algo a Carla. 2. Carla lo completa. 3. Laura abre el pedido y mira el recorrido. (Igual cuando Laura completa un pedido que pidió Carla: el aviso a Diego.)
- **Esperado:** un texto que una persona entienda, como en la lista de avisos: "Completado (de mi gente)" (`avisos/dominio.ts`), o "sistema avisó a Hernán, jefe de Laura (completado)".
- **Obtenido:** "Sistema avisó a Hernán (completado gente)" (pedido 3) y "Sistema avisó a Diego (completado gente)" (pedido 10).
- **Evidencia:** `capturas/PED-02-1.png` (pedido 10) · `capturas/PED-02-2.png` (pedido 3)
- **Causa probable (opcional):** `app/Modules/Pedidos/Application/Recorrido/RecorridoQuery.php:117` arma la línea con `str_replace('_', ' ', $aviso->tipo)`. Con los otros tipos ("asignado", "tomado", "reasignado") se lee bien de casualidad.
- **Regresión de:** no

### PED-03 · En Bloquear, Completar y Reabrir el "Contá…" en rojo queda después de escribir el texto
- **Severidad:** baja
- **Tipo:** validación
- **F-XX / pantalla:** F-29 · W-30 Bloquear · F-31 · W-29 Completar · F-65 · Reabrir (diálogos en `/pedidos/{id}`)
- **Persona:** Carla (Compras), Sofía (Ingeniería de proyectos) y Laura (Ingeniería de proyectos)
- **Pasos:** 1. Abrir Bloquear… (o Completar, o Reabrir (falta algo)). 2. Tocar el botón de confirmar con el campo vacío. 3. Escribir el motivo (o el comentario, o lo que falta).
- **Esperado:** al escribir, el error desaparece (el campo ya está bien). Así se comportan los formularios de la Semana 1.
- **Obtenido:** sigue en rojo "Contá qué lo traba." (Bloquear, Sofía, pedido 43), "Contá cómo se resolvió." (Completar, Carla, pedido 3) y "Contá qué falta." (Reabrir, Laura, pedido 3), con el campo lleno, hasta que se vuelve a tocar el botón. Al confirmar, anda bien.
- **Evidencia:** `capturas/PED-03-1.png` (Bloquear) · `capturas/PED-03-2.png` (Reabrir) · `out\03.txt` "diálogo completar lleno"
- **Causa probable (opcional):** los diálogos guardan los errores en un `useState` (`BloquearDialog.tsx:16`, igual en Completar y Reabrir) que solo se vuelve a armar al confirmar (`AccionDialog.tsx:46-50`); nada lo limpia al escribir.
- **Regresión de:** no

### PED-04 · Con la entrega anterior a la fecha de pedido se lee "-1 días"
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-26 · W-08 Nuevo pedido · `/pedidos/nuevo` y W-09 Detalle · `/pedidos/21`
- **Persona:** Laura (Ingeniería de proyectos)
- **Pasos:** 1. Nuevo pedido. 2. Fecha de pedido 18/11/2026, fecha de entrega 17/11/2026. 3. Mirar la ayuda de la prioridad; crear y mirar "Pedido · Entrega" en el detalle.
- **Esperado:** se acepta y queda Urgente (DU-P08, ✅), con una cuenta que una persona entienda, por ejemplo "Urgente · la entrega es 1 día antes del pedido".
- **Obtenido:** en el formulario, "Urgente · -1 días entre el pedido y la entrega." (con 15-11 → 12-11: "Urgente · -3 días entre el pedido y la entrega."); en el detalle, "18/11/2026 · 17/11/2026 · vencido (-1 días)".
- **Evidencia:** `capturas/PED-04-1.png` (formulario) · `capturas/PED-04-2.png` (detalle) · `capturas/PED-04-3.png` (-3 días)
- **Causa probable (opcional):** `textoDias()` en `src/features/pedidos/dominio.ts:95` arma "N días entre el pedido y la entrega" sin mirar el signo.
- **Regresión de:** no

### PED-05 · "Pedido creado. Se le avisó a …" vuelve a aparecer con F5 o Atrás
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-24 · W-09 Detalle del pedido · `/pedidos/{id}`
- **Persona:** Laura (Ingeniería de proyectos) y Diego (Gerencia de Administración)
- **Pasos:** 1. Crear un pedido (queda en el detalle con "Pedido creado."). 2. Tomarlo ("Lo tomaste: el pedido pasó a En curso."). 3. Ir a Inicio y volver con Atrás, o apretar F5 en el detalle.
- **Esperado:** la confirmación de la creación se ve una sola vez, después de crear.
- **Obtenido:** arriba del pedido, que ya está En curso, vuelve "Pedido creado." (pedidos 40 y 41, dos veces cada uno: Atrás y F5). A Diego le pasó al volver a abrir el pedido 13 ya Completado: "Pedido creado. Se le avisó a Hernán." encima de "Completada".
- **Evidencia:** `capturas/PED-05-1.png` · `out\09b.txt` ("[F5 1] F5: "Pedido creado."", "[F5 2] Atrás desde Inicio: "Pedido creado."") · `out\05.txt` ("P3 Diego tras completar")
- **Causa probable (opcional):** el aviso viaja en el estado del historial del navegador (`location.state`), que sobrevive al F5 y al Atrás.
- **Regresión de:** no

## Observaciones (no son hallazgos; las dejo para el Lead)

1. **El detalle no muestra los seguidores.** Laura sumó a Sofía al crear y en el pedido no hay forma de ver quién lo sigue (Sofía solo se ve en las líneas "Sistema avisó a Laura, Sofía…"). La maqueta W-09 tampoco los muestra y el documento no lo pide, por eso no lo reporto; la API sí los trae (`seguidores` del `PedidoDetalle`).
2. **`pedido_seguidores.created_at` queda en hora de Argentina** (11:12) mientras el resto de las tablas guarda UTC (`avisos`/`pedidos` 14:12): el `attach` no pone la hora y la toma el `DEFAULT CURRENT_TIMESTAMP` de MySQL con `time_zone = SYSTEM`. No se ve en ninguna pantalla.
3. En la ayuda de "Fecha de pedido" de **Editar** dice "Hoy, si no la cambiás. Para pedidos que llegaron antes por mail.", que tiene sentido al crear, no al editar.

## §6.1 de la semana 2 (lo que me tocó)

| Lo que hacen en la minuta hoy | Resultado |
|---|---|
| Cargar un pedido con todas sus columnas (tarea, obra o "Servicios generales", propietario, PROVEEDOR, fechas, prioridad, links, notas) | **funciona** (F-24, F-25, F-26, F-27, F-36) |
| Cambiar el desplegable de estado: En curso, Bloqueada con el motivo, Completada | **funciona** (F-28, F-29, F-31) |
| La ida y vuelta en Notas ("falta tal cosa") | **funciona** (F-30 y reabrir F-65) |
| Cambiar el propietario a mano | **funciona** (F-32, a persona y al sector) |
| Corregir una fecha o un link | **funciona** (F-33, con historial F-14) |
| Ver lo mío en una sola lista | **funciona** (F-04 y F-05 siguen cada paso) |
| Enterarse sin que lo arroben: asignación (también por mail), tomado, completado | **funciona** en lo que vi: líneas "Sistema avisó a…" del recorrido y mails de asignación y reasignación en el log. "Vence mañana" y el detalle de campana y mail: rol AVI. |

## No probado

- **Celular** (W-41…W-43) y teclado: rol CEL.
- **Avisos y mails en detalle** (texto, campana en ≤ 15 s, Mis avisos, "vence mañana"): rol AVI. Solo miré que la campana tenga contador y que los mails de asignación y reasignación estén en el log.
- **Reabrir con el propietario inactivo** (DU-P09) y **quitar un comprobante** (RN-PED-21): no quedaron en el camino; necesitan un usuario inactivo o un segundo cierre.
- **Todos los pedidos, Excel, calendario, Tareas de mi gente**: rol 3. **Pestaña Pedidos de la obra y W-22**: rol 4. **Ataques a los formularios, IDs ajenos y doble clic**: rol 5.
- **Período al revés o incompleto** (RN-PED-05): lo cubre el rol 5 con los formularios atacados.

— Rol 1 · PED · Pedidos de punta a punta (PC) · 2026-11-20 11:31 (Argentina) · 0 ingresos reales · plazos restaurados a 1/7.

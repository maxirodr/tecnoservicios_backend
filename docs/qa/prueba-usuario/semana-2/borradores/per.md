# Borrador PER · Permisos, entradas y errores · Semana 2 · ronda 1

- **Fecha:** 2026-11-20, de 11:58 a 12:28 (hora de Argentina; base y logs en UTC = +3 h).
- **Código probado:** worktrees `_qa_s2` en `staging` (back `7e007dd`, front `d8f2433`). API :8340 y front :5316. Health 200 al empezar y al terminar (0,57 s); no hubo 503 ni lentitud que afecte conclusiones. Sin `errors-2026-11-20.log` en todo el rol, `GET /api/errors` → "No logs for date 2026-11-20", `failed_jobs` = 0.
- **Personas / sesiones:** Laura, Carla y Diego (`sesiones/<persona>.json`, solo para cargar datos, reasignar mientras otro mira y la tabla de permisos; nunca "Salir") y tres usuarios propios con **ingreso real**, PC 1366×768, `es-AR`, `America/Argentina/Buenos_Aires`.
- **Ingresos reales gastados: 6 de 6** (sin 429 ni códigos quemados): `qa-per-ver` (1), `qa-per-sin` (1), `qa-per-edit` (1 inicial + **3 de sesión vencida**).
- **Datos creados (prefijo `QAS2-PER`):**
  - Sectores (Diego, `POST /api/sectores`): **QAS2-PER Ver** (14), **QAS2-PER Sin acceso** (15) y **QAS2-PER Editar** (16; nace Editar, lo agregué para tener un usuario mío con Editar para los formularios y la sesión vencida sin tocar sectores del seeder).
  - Usuarios (Diego): `qa-per-ver@…` (19, sector 14), `qa-per-sin@…` (20, sector 15), `qa-per-edit@…` (21, sector 16), sin jefe.
  - Pedidos 69–101 con `QAS2-PER` en la tarea (más 83 "QAS2-PER yyy…" de 2000 caracteres, por API). Adjuntos temporales sueltos (20 MB, id 24) de la prueba de la API.
- **Tabla de permisos:** por pantalla (W-15) puse Pedidos = **Ver** en QAS2-PER Ver y **Sin acceso** en QAS2-PER Sin acceso. Para el caso DU-P01 (3) ("le sacan el acceso **después** de asignarle pedidos") el sector Sin acceso estuvo en Editar de 12:00 a 12:03 (Diego por API) mientras Laura le asignaba el pedido 74 y la persona cargaba el 75; después, Sin acceso. **Al final** los dos volvieron a **Editar·todas** (como nace un sector) desde la pantalla. Verificado en la base: las 156 filas de los sectores 1–13 son idénticas a las de antes de empezar (`out\permisos-antes.tsv`); mis tres sectores quedan Pedidos/Equipos = Editar·todas, Obras = Ver·todas.
- **Base:** no moví fechas; nada que restaurar. **Redis:** solo `DEL` de la clave de sesión de `qa-per-edit` (uid 21, comprobado por el `login_web_…` de la clave), con la página quieta; 3 veces.
- **Scripts y salidas:** `C:\Users\GREEN\AppData\Local\Temp\claude\4fe530b1c11d8e8d\f4efcdda-aa94-4434-aafd-2e4822d4d74b\scratchpad\s2\PER\` — `lib.mjs`, `01-setup.mjs` … `13-restaurar.mjs`; salidas en `out\`; archivos de prueba en `files\` (0 B, 20 MB, 21 MB, `.exe` renombrado a `.pdf`, `.html`, PDF con "año ñandú" en el nombre).

## Funcionalidades

| F-XX | Estado | Pantallas | Evidencia | Nota |
|---|---|---|---|---|
| F-60 Tabla de permisos (Pedidos) | ✅ | W-15 `/usuarios/permisos` | `F-60-permisos-per-pc.png` | Cambiar dos celdas y "Guardar cambios": "Guardamos la tabla de permisos. Cada persona lo ve al pasar a otra pantalla o al recargar la página." Solo se tocaron las de mis sectores. |
| F-24 Crear | ✅ | W-08 `/pedidos/nuevo` | `F-24-crear-vacio-pc.png`, `F-24-crear-largos-pc.png`, `F-24-creado-dobleclic-pc.png`, `F-24-sesion-vencida-alta-1-pc.png`, `F-24-borrador-recuperado-alta-1-pc.png` (y `-2`) | Vacío: los 5 textos de RN-PED-02 sin enviar nada. Tarea de espacios: "Escribí la tarea.". 2001 / 151 / 5001: "La tarea puede tener hasta 2000 caracteres.", "Con quién puede tener hasta 150 caracteres.", "Las aclaraciones puede tener hasta 5000 caracteres." (0 envíos). **Doble clic en "Crear pedido": 1 POST, 1 pedido** (87, con 2 links y 1 archivo) y "Pedido creado. Se le avisó a Carla.". Ñ y emoji se guardan y se ven. Con **Ver**: "Solo podés ver esta sección · Tu sector puede ver los pedidos pero no cargar uno nuevo. Si lo necesitás, pedíselo a gerencia."; API 403 "No tenés permiso para hacer esto.". |
| F-25 Fecha de pedido | ✅ | W-08 · W-32 | `F-25-crear-fechas-pc.png` | Futura: "La fecha de pedido no puede ser posterior a hoy."; período al revés: "El fin del período no puede ser anterior al inicio."; solo inicio: "Completá inicio y fin del período."; entrega 2099: aviso "Revisá la fecha: es de 2099." (no bloquea). Entrega 16-11 con pedido 18-11: se crea y la prioridad dice "Urgente (sola)" (DU-P08). API: 31-02 y "mañana" → "Escribí la fecha como dd/mm/aaaa, por ejemplo 31/12/2099.". |
| F-26 Prioridad (quién la corrige) | ✅ | W-32 | — | Quien lo pidió: Urgente "(corregida a mano)", "Calculada: Prioritario · 7 días" y "Usar la calculada" la vuelve automática (base `prioritario, 0`). Tercero con Editar: el campo no aparece (`corregir_prioridad: false`) y por API `PATCH {prioridad}` y `{usar_prioridad_calculada}` → 403 "Solo quien lo pidió puede corregir la prioridad." (DU-P07). Con Ver: 403 "No tenés permiso para hacer esto.". |
| F-27 Archivos o links | ✅ | W-08 · W-29 | `F-27-link-javascript-pc.png`, `F-27-archivos-atacados-pc.png`, `F-31-comprobantes-atacados-pc.png` | `javascript:alert(1)` y `ftp://…`: "El link tiene que empezar con http:// o https://." (sin envío); espacios: "Pegá el link."; >1000: "El link puede tener hasta 1000 caracteres."; `http://` se acepta y se nombra "Remito año.pdf". Sin esquema (`drive.example/…`) el front le agrega `https://` y lo guarda (por API sin front: 422). 0 B: "El archivo está vacío. Elegí otro."; `.html`: "Ese tipo de archivo no se puede subir. Se aceptan PDF, imágenes (JPG, PNG, WEBP, HEIC), Excel, Word, CSV y TXT."; `.exe` como `.pdf`: 422 "Este archivo no es un PDF aunque termine en .pdf. Elegí otro."; **21 MB: "El archivo pesa 21 MB y el máximo es 20 MB."**; **20 MB exactos: se sube** (201). Igual en el cierre (comprobantes) y por API en los contextos `pedido` y `comprobante_cierre` (también `data:`, `file:`, archivo + link, nada, contexto inventado: 422 en español). Ningún 413 ni 500. |
| F-28 Tomar | ✅ | W-09 | `F-28-tomar-ajeno-403-ver-pc.png`, `F-28-tomar-propio-sin-acceso-pc.png` | Con Ver, lo propio: doble clic → 1 POST, 1 actividad `tomado`. Dos pestañas de la misma persona: la vieja recibe "Ya lo tomó QAS2-PER Ver." y se refresca. **Algo que ya no es suyo** (Laura lo reasigna a Carla mientras la Ver lo mira): "Solo el propietario puede tomarlo." en la cabecera y la pantalla se actualiza sin el botón. Tercero por API: 403 "Solo el propietario puede tomarlo.". Sin acceso toma lo suyo (74). |
| F-29 Bloquear / retomar | ⚠️ | W-30 | `F-29-bloquear-sin-tomar-ver-pc.png` | Vacío y espacios: "Contá qué lo traba."; con "Falta el dato del caño Ø 2" · ñandú 🚧" bloquea **desde No iniciada** (DU-P03), doble clic → 1 POST. Retomar con doble clic → 1 actividad. Tercero: 403 "Solo el propietario puede bloquearlo." / "…retomarlo.". Motivo de 2001 pegado: se guarda cortado sin aviso: **PER-01**. |
| F-30 Comentar | ⚠️ | W-09 | `F-30-comentar-seguir-con-ver-pc.png` | Con **Ver** comenta lo ajeno (DU-P01); vacío/espacios: "Escribí el comentario."; doble clic → 1 POST, 1 comentario; "ñ, tildes (acción) y emoji 👷‍♀️" se guardan bien (utf8mb4). Sin acceso comenta lo que pidió (75); en uno ajeno, API 404 "Pedido no encontrado.". 5001 pegado: se guarda cortado sin aviso: **PER-01**. |
| F-31 Completar con comprobante | ⚠️ | W-29 | `F-31-completar-bloqueado-409-ver-pc.png`, `F-31-comprobantes-atacados-pc.png`, `F-31-completado-dobleclic-pc.png` | **Completar un bloqueado** con la pantalla vieja (otra pestaña lo bloqueó): "Primero retomalo: está bloqueado." y la pantalla pasa a Bloqueada. Vacío: "Contá cómo se resolvió."; con comprobantes erróneos no se envía hasta quitarlos; doble clic → 1 POST, 1 cierre, 1 comprobante. Tercero: 403 "Solo el propietario puede completarlo.". Pedido que me pedí a mí: **PER-02**. |
| F-32 Reasignar | ✅ | W-31 | `F-32-reasignado-dobleclic-pc.png` | Vacío: "Elegí el propietario."; el propietario actual no está en la lista (API: 422 "Ya es el propietario."); Pañol: "Pañol y Logística no tiene usuarios activos: elegí una persona."; persona de sector Sin acceso: 422 "QAS2-PER Sin acceso no tiene acceso a Pedidos: elegí otra persona." (no se ofrece en la lista); sector + persona: "Elegí una persona o un sector."; comentario 5001 por API: 422. Doble clic → 1 POST, 1 `reasignado`. Ver o tercero: 403 "Solo el propietario o quien lo pidió pueden reasignarlo.". |
| F-33 Editar | ✅ | W-32 `/pedidos/{id}/editar` | `F-33-editar-atacado-pc.png`, `F-33-dos-pestanas-409-pc.png`, `F-33-tras-actualizar-pc.png`, `F-24-sesion-vencida-editar-1-pc.png`, `F-24-borrador-recuperado-editar-1-pc.png` | Vacío: "Escribí la tarea." / "Poné la fecha de entrega."; 2001, fecha futura y período al revés con sus textos. **Dos pestañas:** la segunda manda la versión vieja → 409 y "Alguien cambió esto mientras lo tenías abierto. Actualizá y revisá. Al actualizar vas a ver lo que está guardado ahora y se pierde lo que cambiaste acá." con **Actualizar**, que carga lo de la pestaña 1 sin pisar nada. Doble clic en Guardar → 1 PATCH. API: `updated_at` viejo → 409; "ayer" → 422 "El campo versión que tenías abierta no es una fecha válida."; `sector_id`, `propietario_id`, `estado`, `solicitante_id` se ignoran (200, base sin cambios); `tarea: null` → "Escribí la tarea.". Sin acceso no edita ni lo suyo (403, RN-PED-28 pide Editar). |
| F-65 Reabrir | ⚠️ | W-09 | — | Vacío: "Contá qué falta."; doble clic → 1 POST, 1 `reabierto`; reabrir uno abierto: 409 "No está completado: no hace falta reabrirlo."; **tercero** (Ver y Editar): 403 "Solo quien lo pidió puede reabrirlo.". 5001 pegado: cortado sin aviso: **PER-01**. |
| F-38 Seguir | ✅ | W-09 | `F-30-comentar-seguir-con-ver-pc.png` | Con Ver sigue lo ajeno (doble clic → 1 POST, 1 fila) y deja de seguir (0 filas). Sin acceso, ajeno: 404. |
| F-04 / F-05 Lo propio con Sin acceso | ✅ | W-01 · W-27 | `F-05-pedidos-que-pedi-sin-acceso-pc.png` | Con Pedidos = Sin acceso (puesto **después** de asignarle): Mis tareas (1) con el 74, Pedidos que pedí (1) con el 75, sus detalles abren y se toma / comenta (DU-P01 (3)). "Pedidos" del menú lleva a Equipos en reparación (tiene esa celda en Editar, RN-PED-42). |
| F-35 Todos los pedidos / exportar / calendario | ✅ | W-24 · W-03 | — | Ver: lista, filtros y exportar 200. Sin acceso: `/pedidos` y `/pedidos/nuevo` muestran "No tenés acceso a esta sección · Tu sector no tiene permiso para ver Pedidos. Si lo necesitás, pedíselo a gerencia."; `/calendario` igual con "Calendario"; API `GET /api/pedidos`, exportar y calendario: 403 "No tenés permiso para hacer esto.". |
| F-03 Buscador, grupo Pedidos | ✅ | W-18 | — | Sin acceso busca "QAS2-PER": solo el grupo Obras ("Sin resultados."), sin grupo Pedidos. |
| IDs ajenos e inexistentes | ✅ | W-09 | — | `/pedidos/999999` (Ver y Sin acceso) y un pedido de Compras con Sin acceso: "Pedido no encontrado · Puede que el link esté mal o que no tengas acceso a ese pedido." con "Volver a Mis tareas"; API 404 "Pedido no encontrado." también en `recorrido`, `tomar`, `comentarios`, `seguir`. Adjunto de un pedido fuera de alcance o inexistente: 404 "No encontramos lo que buscabas." (Ver, que sí lo ve: 200). `/api/pedidos/abc`: 404 "No encontramos lo que buscabas.". Nunca 500 ni pantalla en blanco. |
| Sesión vencida con borrador | ✅ | W-08 · W-32 | `F-24-sesion-vencida-*-pc.png`, `F-24-borrador-recuperado-*-pc.png` | 3 veces (alta ×2, editar ×1): guardar con la clave borrada → 419/401, "Tu sesión venció. Volvé a ingresar." en Ingreso; tras el **ingreso real** vuelve sola a la misma pantalla con "Recuperamos lo que estabas escribiendo." y todo lo escrito (ñ y emoji incluidos); se guarda bien (pedidos 98, 99; 101 editado). |
| F5 a mitad | ✅ | W-08 · W-32 | — | El navegador pregunta antes de salir (`beforeunload`); si se acepta, el formulario vuelve vacío y sin aviso. El borrador es solo para la sesión vencida (PU-004), así que no es hallazgo. |

### Matriz por sector y acción (lo que se probó, por pantalla ✱ y por API)

| Acción | Ver (QAS2-PER Ver) | Sin acceso (QAS2-PER Sin acceso) | Editar, tercero (QAS2-PER Editar) |
|---|---|---|---|
| Menú Pedidos | Todos los pedidos ✱ | Equipos en reparación ✱ | Todos los pedidos ✱ |
| Ver un pedido ajeno | ✅ ✱ (solo "Seguir" y "Comentar") | 404 entendible ✱ | ✅ ✱ ("Editar", "Seguir", "Comentar") |
| Ver lo propio (asignado / pedido) | ✅ ✱ | ✅ ✱ (DU-P01) | ✅ ✱ |
| Crear | "Solo podés ver esta sección" ✱ · 403 | "No tenés acceso a esta sección" ✱ · — | ✅ ✱ |
| Editar ajeno / propio | 403 | 403 / 403 | ✅ ✱ (sin prioridad si no lo pidió) |
| Comentar ajeno | ✅ ✱ | 404 | ✅ |
| Seguir ajeno | ✅ ✱ | 404 | ✅ (`seguir: true`) |
| Tomar ajeno | 403 ✱ "Solo el propietario puede tomarlo." | 404 | 403 |
| Tomar / bloquear / completar lo propio | ✅ ✱ | ✅ ✱ (tomar) | ✅ ✱ |
| Completar un bloqueado | 409 ✱ "Primero retomalo: está bloqueado." | — | 409 |
| Reabrir siendo tercero | 403 "Solo quien lo pidió puede reabrirlo." | — | 403 |
| Reasignar ajeno | 403 | — | 403 |
| Cambiar prioridad sin haberlo pedido | 403 | 403 | 403 "Solo quien lo pidió puede corregir la prioridad." |
| Todos / exportar / calendario | ✅ | 403 ✱ | ✅ |

## Hallazgos

### PER-01 · En los diálogos, un texto más largo que el máximo se guarda cortado sin avisar
- **Severidad:** baja
- **Tipo:** validación
- **F-XX / pantalla:** F-30 · W-09 "Escribir un comentario" · F-29 · W-30 "Motivo" · F-65 "¿Qué falta?" (Completar usa el mismo campo con `maxLength`) · `/pedidos/{id}`
- **Persona:** QAS2-PER Editar (Pedidos = Editar)
- **Pasos:** 1. Abrir un pedido propio. 2. Pegar 5001 caracteres en "Escribir un comentario" y tocar "Comentar". 3. "Bloquear…", pegar 2001 caracteres en "Motivo" y "Bloquear". 4. En uno completado que pedí, "Reabrir (falta algo)" con 5001 caracteres.
- **Esperado:** como en W-08 y W-32 ("La tarea puede tener hasta 2000 caracteres.") o como la API (422 "Es demasiado largo (máximo 5000 caracteres)."): un aviso y nada se guarda hasta que la persona lo acorte. Que se pierda el final de lo escrito sin decirlo no cumple "entiende siempre qué pasó" (§0).
- **Obtenido:** el campo corta en silencio (atributo `maxLength`) y se guarda: comentario de 5000, motivo de 2000, comentario de reapertura de 5000; los mensajes son de éxito ("El pedido pasó a Bloqueada.", "Reabrimos el pedido: volvió a En curso."). Reproducido 2 veces (pedidos 90/91 y 92/93).
- **Evidencia:** `logs/PER-01.log` (largos en la base) · `POST /api/pedidos/90/comentarios` → 201, `/90/bloquear` → 200, `/91/reabrir` → 200
- **Causa probable (opcional):** `TextoDialogo.tsx:29` y `Comentarios.tsx:74` (`maxLength={…}`) en lugar del mensaje de `pedidos.formularios.ts`.
- **Regresión de:** no

### PER-02 · Al completar un pedido que me pedí a mí, dice "Le avisamos a" mí mismo, y no se avisa a nadie
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-31 · W-29 Completar · `/pedidos/{id}`
- **Persona:** QAS2-PER Editar (solicitante y propietario del mismo pedido, RN-PED-01 "también a uno mismo")
- **Pasos:** 1. Crear un pedido para uno mismo. 2. Abrirlo y tocar "Completar". 3. Escribir el comentario y "Completar y avisar".
- **Esperado:** como al crear (RN-PED-08: "Si quien carga es el propietario, no se le avisa a nadie" y la confirmación es solo "Pedido creado."): botón "Completar", sin "le avisamos a…" en la ventana y "Pedido completado." al terminar. El actor nunca recibe lo suyo (`avisos.md`).
- **Obtenido:** la ventana dice "Al confirmar, el pedido pasa a Completada, le avisamos a QAS2-PER Editar con este comentario y queda en el historial…", el botón es "Completar y avisar" y al terminar "Pedido completado. Le avisamos a QAS2-PER Editar.". En la base no hay ningún aviso de esos pedidos (correcto): el texto promete algo que no pasa. Reproducido 2 veces (pedidos 94 y 96).
- **Evidencia:** `capturas/PER-02-1.png` · `logs/PER-02.log`
- **Causa probable (opcional):** `CompletarDialog.tsx:35` y `:54` usan el nombre del solicitante sin mirar si es quien completa.
- **Regresión de:** no

## §6.1 del backlog (lo que me toca)

- Cargar un pedido con todas sus columnas, con datos raros (ñ, emoji, largos, fechas al revés, links y archivos malos): **funciona** (mensajes claros, nada de 500).
- Cambiar el estado (En curso, Bloqueada con motivo, Completada) y que nadie más pueda hacerlo: **funciona** (403/409 con texto del backend).
- La ida y vuelta de comentarios con Ver y con lo propio en Sin acceso: **funciona** (salvo el corte silencioso de PER-01, bajo).
- Cambiar el propietario a mano (reasignar) con sus guardas: **funciona**.
- Corregir una fecha o un link (editar) con dos pestañas y con la sesión vencida: **funciona** (409 + Actualizar; borrador recuperado).

## Canal `errors`

`storage\logs\errors-2026-11-20.log` no existe al terminar (12:28) y `GET /api/errors` responde "No logs for date 2026-11-20": ninguno de mis ~110 ataques con 401/403/404/409/413/419/422 quedó registrado como error, y no hubo ningún 5xx.

## Observaciones (no son hallazgos)

1. Con Pedidos = Ver, "Todos los pedidos" pide `GET /api/pedidos/obras-opciones` y recibe 403 (queda en la consola del navegador). Es a propósito (`useOpcionesDeObra.ts`: "con Pedidos = Ver la API no lo da y no pasa nada"): el filtro Obra se arma con las obras de las filas. No va al canal `errors`.
2. Dos pestañas de la **misma** persona tomando: la segunda lee "Ya lo tomó QAS2-PER Ver." (su propio nombre). Entendible; es el texto de RN-PED-16.
3. Un link pegado sin `https://` el front lo completa con `https://` y lo acepta; la API sin front lo rechaza con "El link tiene que empezar con http:// o https://.". Ayuda a la persona, no es un agujero.
4. Loaders: solo aparecieron donde recarga ("Cargando el pedido…", "Cargando el recorrido…", "Cargando el historial…" al volver a una pantalla, "subiendo…" en el archivo de 20 MB); ninguno en un clic que no recarga. Ningún texto con `undefined`, `null`, `NaN`, inglés ni claves crudas en las pantallas recorridas.
5. Sin acceso no puede reabrir ni reasignar lo que pidió ni editar lo suyo (piden Editar, RN-PED-20/22/28 y §7.2); sí lo ve, lo toma, lo comenta y lo completa. Coincide con DU-P01 tal como está escrita.

## No probado

- Reabrir con propietario inactivo (DU-P09) y pedidos de carga inicial sin solicitante (DU-P10): fuera de mi fila y no hay datos de carga inicial.
- Alcance "sus obras / propias" de Pedidos: la consigna pedía Ver y Sin acceso; el 404 fuera de alcance se probó con Sin acceso.
- 413 real del servidor (archivo cortado por PHP): `upload_max_filesize`/`post_max_size` son 2 G en este entorno, así que 21 MB llega a la validación (422) y no se puede provocar el 413 sin cambiar la configuración.
- Celular: lo hace el rol 6.

— PER (rol 5) · 2026-11-20 12:28 · 6 ingresos reales · 2 hallazgos bajos, 0 medios/altos/bloqueantes.

# Hallazgos · Prueba de usuario Semana 1 · Ronda 3 · Tecnoservicios (NS-2026-025)

- **Fecha:** 2026-11-18
- **Entorno:** base `tecnoservicios_prueba_usuario` (MariaDB 10.4.32) · front `http://localhost:5316` (`1914ea8`) · API `http://localhost:8340` (`66160b1`)
- **Alcance:** solo lo nuevo de la ronda 3. El estado de los 52 PU verificados (PU-101…139 y los 13 ⚠️/❌ de la ronda 2) está en `verificacion.md`.
- **Conteo:** 11 hallazgos · bloqueante 1 · alta 1 · media 2 · baja 7 · **ninguna regresión** · 5 arreglos incompletos, con "Regresión de: no; arreglo incompleto de …" (PU-202, PU-203, PU-206, PU-207, PU-212)
- **Próximo número libre:** **PU-213** (PU-211 no se usa: se fusionó en PU-210, ver Consolidación)
- **Orden:** por severidad (bloqueante, alta, media, baja) y, dentro de cada una, por rol (A, B, C, CEL, BK, REG). Al final de cada **Evidencia**, `origen:` dice de qué borrador sale. Las capturas y los logs de los hallazgos se renombraron de `<ROL>-NN-n.png` a `PU-2XX-n.png` (tabla al final). Los borradores no se modificaron: siguen nombrando las capturas con el ID viejo.
- **Consolidación:**
  - C-02 y REG-03 son el mismo caso (el historial nombra los links por el dominio): van juntos en **PU-207**, con las evidencias de los dos.
  - (QC) CEL-01 y CEL-02 son el mismo problema (links de texto de 19–21 px de alto en el celular, `<Link>` en línea sin `min-height`, resto de PU-041): van juntos en **PU-210**, con las evidencias de los dos. El número PU-211 queda sin usar; las capturas `PU-211-1/2.png` se citan desde PU-210.
  - (QC) PU-206, PU-207 y PU-212 tenían "Regresión de: PU-1XX". Ninguno rompió algo que antes funcionaba: el 401 "Tenés que ingresar para seguir." es anterior a `f5b7500` (PU-133 solo cambió 419 y 429); el nombre por dominio lo guarda el back desde antes (`RegistrarLinkAction::dominioLegible`, que el arreglo de PU-112 no tocó); y `e062ff0` (PU-136) no tocó `AdjuntosField`. Pasan a "no; arreglo incompleto", como PU-202 y PU-203.
  - BK-01 (6 respuestas 500 en `/api/me` por `unserialize()`) **no es hallazgo**: lo causó el script del Verificador B, que restauró a mano su propia sesión en Redis con un salto de línea de más. Va como incidente del entorno en `reporte.md` (`logs/BK-01.log`).
  - Lo "esperado" del §1 del prompt no se reporta. Lo "a validar por Juani" está en `observaciones.md`.
  - Los F-XX de los borradores se normalizaron a la numeración de la ronda 2 (F-17 ficha, F-18 presupuestos, F-19 documentos y contactos, F-48 presupuesto por rubro, F-68 plazos).

### PU-201 · Presupuesto por rubro: la segunda pestaña o persona pisa sin aviso los montos que guardó la primera
- **Severidad:** bloqueante
- **Tipo:** funcional
- **F-XX / pantalla:** F-48 · W-13 Presupuesto por rubro · `/obras/12/presupuesto-por-rubro`
- **Persona:** Diego (Gerencia de Administración), en dos pestañas
- **Pasos:** 1. Abrir el presupuesto por rubro de la obra 2-971-2026 en dos pestañas (A y B). 2. En A, cargar MATERIALES `2000000` e INSUMOS `250.000` y tocar "Guardar". 3. En B, sin recargar, cargar SUBCONTRATOS `777.000` y tocar "Guardar".
- **Esperado:** "Alguien cambió esto mientras lo tenías abierto. Actualizá y revisá." con "Actualizar", como en Editar obra (PU-002), o que queden los dos cambios.
- **Obtenido:** B ve "Guardamos el presupuesto por rubro." y en la base solo queda `subcontratos=777000`: MATERIALES e INSUMOS de A se borraron sin aviso. El historial se lo atribuye a B: "materiales 1500000.50 → null, insumos 250000.00 → null". `PUT /api/presupuestos/12/montos → 200` las dos veces. Reproducido 2 veces (99 y 777.000).
- **Evidencia:** `capturas/PU-201-1.png` (A guardado) · `capturas/PU-201-2.png` (B: "Guardamos el presupuesto por rubro." con solo SUBCONTRATOS) · tabla `actividades` id 131 · scripts `REG/08-rubro.mjs`, `REG/08-rubro2.mjs` · origen: REG-02
- **Causa probable (opcional):** `app/Modules/Obras/Http/Requests/CargarMontosRequest.php` no pide ni compara `updated_at`. El PUT reemplaza todos los montos con lo que tenía la pantalla.

### PU-202 · Con MySQL caído, cualquier pedido con sesión tarda 38 s y responde 500 "El sistema tuvo un problema." en vez de 503, y no queda registrado
- **Severidad:** alta
- **Tipo:** error-servidor
- **F-XX / pantalla:** transversal · `GET /api/me`, `GET /api/proveedores` (cualquier pedido con sesión) · pestaña nueva en `/proveedores`
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Con Redis arriba, parar MySQL (`mysqladmin --user=root --host=127.0.0.1 shutdown`). 2. Con la sesión de Diego, `GET /api/me` y `GET /api/proveedores?page=1` (curl, `Accept: application/json`, de a uno, sin navegador). 3. Abrir `/proveedores` en una pestaña nueva.
- **Esperado:** (PU-138, `5c556c3`) 503 "El sistema no está disponible en este momento. Probá de nuevo en unos minutos." en pocos segundos (el health detecta la base caída en 1,2 s), con la falla registrada en el canal `errors`. La pestaña nueva muestra "… Apenas vuelva, esta pantalla se recupera sola.".
- **Obtenido:** `GET /api/me → 500` `{"message":"El sistema tuvo un problema. Volvé a intentar en un momento."}` en **38,62 s**, y `GET /api/proveedores → 500`, mismo texto, en **38,62 s**. En `errors-2026-11-18.log` **no hay ninguna entrada** de esos dos 500. En la salida de `artisan serve` aparece `PHP Fatal error: Maximum execution time of 30 seconds exceeded … Connector.php on line 67`. La pestaña nueva queda en "Cargando el sistema…" sin mensaje ni "Reintentar" durante todo el corte. Como `php artisan serve` atiende de a un pedido, el health y los demás pedidos se encolan detrás (17,8 s y timeouts de 30 s). Reproducido 2 veces: corte 1 (12:07–12:16 local, con navegador; una sola entrada `Maximum execution time of 30 seconds exceeded`, `FatalError`, a las 15:16:50 UTC) y corte 2 (12:18–12:20 local, curl, 2 pedidos). Al volver MySQL, las dos pestañas se recuperaron solas.
- **Evidencia:** `capturas/PU-202-1.png` ("Cargando el sistema…") · `logs/PU-202.log` (curl, health 1,23 s en el mismo corte, traza de `api.out`) · `BK\corte-mysql2-operador.log` (scratchpad del rol, no está en `logs/`) · origen: BK-03
- **Causa probable (opcional):** la traza de `api.out` muestra que el `PDOException [2002]` original se reporta y el reporte vuelve a ir a la base: `bootstrap/app.php:51` → `App\Http\Controllers\Api\Support\ErrorLogContext::fromRequest()` (`ErrorLogContext.php:34`) → `$request->user()` → `SessionGuard::user()` → `EloquentUserProvider::retrieveById()` → nueva conexión a MySQL caída (con el reintento de Laravel) dentro del reporte. Las esperas de conexión se suman hasta superar `max_execution_time` (30 s), el `FatalError` no pasa por `ServicioCaido` (→ 500 genérico) y la entrada del canal `errors` nunca se escribe. El health no lo sufre porque usa `SondaDeBase` con su propio timeout de 1 s.
- **Regresión de:** no; arreglo incompleto de PU-138 (back `5c556c3`; su test `ServicioCaidoTest` no pasa por el reporte con usuario autenticado)

### PU-203 · Tras un corte de Redis, la pantalla que ya estaba abierta no se recupera sola: queda "No pudimos cargar esta información … Reintentar"
- **Severidad:** media
- **Tipo:** loader
- **F-XX / pantalla:** transversal · probado en F-55 · W-14 Proveedores · `/proveedores` (pasa con cualquier listado o ficha que falle durante el corte)
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Con la app abierta en `/obras` (sesión ya cargada), parar Redis (`redis-cli shutdown save`). 2. Ir a "Proveedores" desde la barra. 3. Volver a levantar Redis y esperar a que `/api/health` dé 200. 4. Esperar sin tocar 8 s, 35 s y 70 s.
- **Esperado:** (prompt ronda 3, PU-138) la pantalla se recupera sola al volver el servicio, sin "Reintentar", igual que una pestaña abierta durante el corte.
- **Obtenido:** a los +8, +35 y +70 s sigue "No pudimos cargar esta información · El sistema no está disponible en este momento. Probá de nuevo en unos minutos. · Reintentar", aunque el reintento automático de la sesión ya dio `GET /api/me → 200` a los ~5 s: no se vuelve a pedir `GET /api/proveedores`. El mensaje dice "no está disponible" cuando ya lo está. La barra sí queda (✅). Solo se recupera al tocar otra sección ("Obras" → listado OK) o "Reintentar". En la misma corrida, la pestaña abierta durante el corte (con la sesión nunca cargada) sí volvió sola antes de los 8 s. Reproducido 2 veces (11:57 y 12:02 local).
- **Evidencia:** `capturas/PU-203-1.png` (corrida 1, +70 s) · `capturas/PU-203-2.png` (corrida 2, +70 s) · `capturas/PU-138-r3-1.png` (durante el corte, con barra) · `capturas/PU-138-r3-3.png` (pestaña B recuperada) · `logs/PU-203.log` · script `BK\cortes-app.mjs redis` / `redis3` · origen: BK-02
- **Causa probable (opcional):** el reintento cada 10 s (`SESION_REINTENTO_MS`, `src/shared/sesion/sesion.ts:43-48`, front `d468ada`) es solo de la query `['sesion']`. Las queries de pantalla usan los defaults de `src/app/queryClient.ts:40-45` (`retry: debeReintentar`, 2 reintentos, y `refetchOnWindowFocus`). Sin `refetchInterval` en error ni invalidación cuando la sesión vuelve a dar 200, quedan en error hasta que haya foco, navegación o "Reintentar".
- **Regresión de:** no; arreglo incompleto de PU-138 (front `d468ada`)

### PU-204 · Presupuesto por rubro y Plazos de la prioridad se van sin preguntar con cambios sin guardar
- **Severidad:** media
- **Tipo:** funcional
- **F-XX / pantalla:** F-48 · W-13 Presupuesto por rubro · `/obras/{id}/presupuesto-por-rubro` · F-68 Plazos de la prioridad · `/usuarios/plazos`
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. En la obra 2-971-2026, tocar "Presupuesto por rubro". 2. Escribir `123.456` en LOGISTICA, sin guardar. 3. Tocar "‹ Volver a la obra", o "Proveedores" en la barra, o Atrás, o F5. 4. En Plazos de la prioridad, cambiar "Urgente hasta" a 2 y tocar "Obras" en la barra o "Sectores".
- **Esperado:** "Tenés cambios sin guardar. ¿Salir igual?", como en Editar obra, Mi perfil y la Tabla de permisos (PU-006). Con F5, el aviso del navegador.
- **Obtenido:** en el presupuesto por rubro se va sin preguntar por las 4 vías (5 veces). Al volver, LOGISTICA está vacío ("") y se perdió lo escrito. Con 12 rubros por presupuesto, es fácil perder una carga entera. En Plazos pasa lo mismo, 2 veces (link de la barra y "Sectores").
- **Evidencia:** `capturas/PU-204-1.png` (rubro con 123.456 antes de salir) · `capturas/PU-204-2.png` (ficha, sin aviso) · `capturas/PU-204-3.png` (después de salir de Plazos con el cambio) · scripts `REG/08b-rubro-guard.mjs`, `REG/09-guard-otras.mjs` · origen: REG-01
- **Causa probable (opcional):** `PresupuestoPorRubroPantalla.tsx` y la pantalla de Plazos no usan `useCambiosSinGuardar`.

### PU-205 · Al pasar el mouse por un link con ñ o espacios se ve "%C3%B1" y "%20"
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-19 · W-20 Certificados y otros documentos (tabla, "editar" y alta) · F-18 · W-21 Agregar presupuesto adicional (oferta técnica) · F-16 · W-06 Nueva obra (oferta técnica). Es el mismo componente de adjuntos.
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. En la obra 2-821-2026, tocar "+ agregar presupuesto adicional", luego "Pegar link", pegar `https://drive.example/Obras 2026/oferta año ñandú.pdf` y tocar "Agregar link". 2. Pasar el mouse por el link "oferta año ñandú.pdf".
- **Esperado:** el link completo como lo escribió la persona: `https://drive.example/Obras 2026/oferta año ñandú.pdf` (igual que el campo "Carpeta de Drive" después del arreglo de PU-129).
- **Obtenido:** el cartelito muestra "https://drive.example/Obras%202026/oferta%20a%C3%B1o%20%C3%B1and%C3%BA.pdf". El nombre visible ("oferta año ñandú.pdf") está bien. Igual en Nueva obra y en la tabla y el "editar" de documentos ("https://drive.example/oferta-%C3%B1and%C3%BA"). Reproducido 3 veces (adicional, nueva obra y documento).
- **Evidencia:** `capturas/PU-205-1.png` (adicional) · `capturas/PU-205-2.png` (nueva obra). El cartel amarillo se dibujó con el valor exacto del atributo `title`, porque el navegador headless no muestra el tooltip nativo. Scripts `B/16-b01.mjs` y `B/03-links.mjs` · origen: B-01
- **Causa probable (opcional):** `src/shared/adjuntos/AdjuntoFila.tsx:55`: `title={adjunto.url ?? undefined}` usa la URL tal cual y no pasa por `urlLegible`, que sí se usa para el campo (`b760aa2`).

### PU-206 · Con la sesión vencida, el listado dice "Tenés que ingresar para seguir." en lugar de "Tu sesión venció. Volvé a ingresar."
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-55 · W-14 Proveedores · `/proveedores` (cualquier lista que falle con 401)
- **Persona:** Carla (Compras)
- **Pasos:** 1. Abrir `/proveedores`. 2. Que venza la sesión (simulado sacando la cookie de sesión solo en el navegador de la prueba). 3. Escribir algo en "Buscar proveedor".
- **Esperado:** el mismo texto que el resto de la app para la sesión vencida, "Tu sesión venció. Volvé a ingresar.", con la salida a ingresar (PU-133).
- **Obtenido:** en el lugar de la lista aparece "No pudimos cargar esta información" · "Tenés que ingresar para seguir." · "Reintentar". Al tocar "Reintentar" recién lleva a `/ingresar`, donde dice "Tu sesión venció. Volvé a ingresar.". Para la misma situación hay dos textos distintos, y "Reintentar" no anticipa que te va a llevar a ingresar. Reproducido 2 veces con Carla. Con Diego, un pedido a `/api/me` se adelantó y lo llevó directo a ingresar.
- **Evidencia:** `capturas/PU-206-1.png` · `capturas/PU-206-2.png` · `capturas/PU-206-3.png` (el camino de guardar sí termina en "Tu sesión venció…") · `GET /api/proveedores?page=1&buscar=zzqa3` → 401 · scripts `C/p20-sesion.mjs`, `C/p21-sesion-get.mjs` · origen: C-01
- **Causa probable (opcional):** `tecnoservicios_backend/app/Shared/Http/ApiExceptionRenderer.php:28` `401 => 'Tenés que ingresar para seguir.'`. El front (`src/shared/api/ApiError.ts:22`) le da prioridad al mensaje del backend, así que su "Tu sesión venció. Volvé a ingresar." para 401 no se usa.
- **Regresión de:** no; arreglo incompleto de PU-133 (back `f5b7500` unificó 419 y 429, pero dejó el 401 en "Tenés que ingresar para seguir.", que ya estaba antes y tiene prioridad sobre el texto del front)

### PU-207 · El historial de la obra nombra los links por el dominio ("agregado (drive.example)") y la ficha los nombra por el archivo
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-14 Historial de la obra · F-20 · W-23 Editar obra (Historial de cambios) · `/obras/{id}/editar`
- **Persona:** Diego (Gerencia de Administración), Hernán (Jefatura de Proyectos), QA Lead Reg (Gerencia de Administración)
- **Pasos:** 1. Nueva obra con la oferta como link `https://drive.example/QA3-C/oferta tecnica ñandú.pdf` (o la OC como link `https://drive.example/QA3-REG/oc 001.pdf`), sin "Nombre para mostrar". 2. En la ficha, "+ agregar certificado o documento" con el link `https://drive.example/QA3-C/certificado 1.pdf` (o un adicional con link). 3. Abrir "Editar" y mirar el Historial de cambios.
- **Esperado:** el mismo nombre que muestran la ficha y la tabla después de PU-112: "oferta tecnica ñandú.pdf", "certificado 1.pdf", "oc 001.pdf".
- **Obtenido:** la ficha muestra "oferta tecnica ñandú.pdf", "certificado 1.pdf" y "oc 001.pdf", pero el historial dice "Oferta técnica: agregado (drive.example)", "QA3-C Certificado N.º 1 · Archivo o link: agregado (drive.example)", "Archivo o link de la aprobación: agregado (drive.example)", "Adicional 2-971-2026 A19 · Oferta técnica: agregado (drive.example)" y "QA3-REG Certificado 706 · Archivo o link: agregado (drive.example)". Con links reales del Drive, todas las líneas van a decir "drive.google.com" y el historial no deja saber cuál se agregó. Visto en 4 obras (2-901-2026 y 2-902-2026 por C; 2-971-2026 y 2-975-2026 por REG), 6 links en total.
- **Evidencia:** `capturas/PU-207-1.png` · `capturas/PU-207-2.png` (C, obras 2-901 y 2-902) · `capturas/PU-207-3.png` (REG, obra 12) · `capturas/PU-207-4.png` (REG, obra 15) · `capturas/PU-209-1.png` (la ficha con el nombre bueno) · scripts `C/p23-hist-cap.mjs`, `REG/18-historial.mjs` · origen: C-02 + REG-03
- **Causa probable (opcional):** `app/Shared/Adjuntos/Application/RegistrarLinkAction.php:32-35` guarda como nombre el host, y `AdjuntosService.php:77` lo registra así en la actividad. El arreglo de PU-112 (front `e2605e5`/`3d48f82`) solo cambió cómo lo muestra el front.
- **Regresión de:** no; arreglo incompleto de PU-112 (front `e2605e5`/`3d48f82`: el historial no se cubrió; el back sigue guardando el dominio como nombre, como antes del arreglo)

### PU-208 · El historial pone comillas simples al certificado ("Agregó el certificado 'QA3-C Certificado N.º 1'")
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-14 Historial de la obra · `/obras/{id}/editar`
- **Persona:** Diego (Gerencia de Administración), Hernán (Jefatura de Proyectos)
- **Pasos:** 1. "+ agregar certificado o documento" en una obra → Guardar. 2. Abrir el historial.
- **Esperado:** el mismo criterio que se fijó con PU-128 (sin comillas simples, que no se usan en ningún otro texto), como en la línea del adicional: "Agregó el presupuesto adicional 2-…".
- **Obtenido:** "Agregó el certificado 'QA3-C Certificado N.º 1'" y "Agregó el certificado 'QA3-C Certificado N.º 2'". En el mismo historial, "Quitó …" y "Agregó el presupuesto adicional …" van sin comillas.
- **Evidencia:** `capturas/PU-208-1.png` · `capturas/PU-208-2.png` · origen: C-03
- **Causa probable (opcional):** `app/Modules/Obras/Application/Historial/DescripcionActividad.php:57` (`" '".self::texto(...)."'"`).

### PU-209 · La misma ficha muestra las fechas con dos formatos ("01/01/1900" y "31-dic-2099")
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-17 · W-07 Ficha de la obra · `/obras/{id}` · F-14 Historial
- **Persona:** Diego (Gerencia de Administración), Hernán (Jefatura de Proyectos)
- **Pasos:** 1. Obra con fechas previstas y un certificado con fecha. 2. Mirar la ficha y el historial.
- **Esperado:** una sola forma de escribir una fecha en todas las pantallas (por ejemplo "31/12/2099" o "31-dic-2099").
- **Obtenido:** en la ficha, "Fechas previstas 01/01/1900 → 31/12/2199", y en la tabla de certificados de la misma ficha, "31-dic-2099" (y "18-nov" en "Cargado"). En el historial también se mezclan: "18-nov 13:52" en "Cuándo" y "Fin previsto: 31/12/2199 → 30/06/2099" en "Qué cambió". Visto en las obras 9 y 10.
- **Evidencia:** `capturas/PU-209-1.png` (ficha) · `capturas/PU-209-2.png` (historial) · origen: C-04

### PU-210 · En el celular quedan links de texto de 19–21 px de alto: el número de la tarjeta de Obras (lo único que abre la obra), "‹ Volver a la obra", "‹ Proveedores" y el mail y el teléfono de los contactos
- **Severidad:** baja
- **Tipo:** celular
- **F-XX / pantalla:** F-15 · W-05 Obras (listado) `/obras` · F-48 Presupuesto por rubro `/obras/{id}/presupuesto-por-rubro` · F-56 Ficha de proveedor `/proveedores/{id}` · F-19 Ficha de obra, contactos `/obras/{id}`
- **Persona:** Diego (Gerencia de Administración), Carla (Compras), Hernán (Jefatura de Proyectos)
- **Pasos:** 1. En un Pixel 7 o en un iPhone SE (375×667), abrir Obras, el Presupuesto por rubro de una obra, la ficha de un proveedor y la ficha de una obra con contactos. 2. Medir con `boundingBox()` lo que se toca para entrar a una obra desde su tarjeta, y los links "‹ Volver a la obra", "‹ Proveedores" y los de mail y teléfono de los contactos.
- **Esperado:** 44 px de alto como mínimo (PU-041), o la tarjeta entera abre la obra. Son controles sueltos, no links dentro de una frase. El teléfono es justo lo que se toca en el celular para llamar.
- **Obtenido:** en las tarjetas de Obras, solo el número subrayado ("2-703-2026") es un link y mide "87×21" px; el resto de la tarjeta (nombre, cliente, planta, responsable) no responde al toque (las 3 tarjetas de "En ejecución", con Diego y con Hernán). "‹ Volver a la obra" mide "104×19", "‹ Proveedores" "97×21", el mail del contacto (dato ficticio de la factory) "227×21" y "291 455-1234" "97×21". Pasa igual en los dos celulares y se reprodujo en 2 corridas por celular.
- **Evidencia:** `capturas/PU-210-1.png` (Obras, iPhone SE) · `capturas/PU-210-2.png` (Obras, Pixel 7) · `capturas/PU-211-1.png` (ficha de proveedor, iPhone SE) · `capturas/PU-211-2.png` (ficha de obra, Pixel 7) · `CEL\out-cel-se.txt`, `out-cel-p7.txt` ("a "2-703-2026 · QA3-A Obra conflictos 3" 87×21") · origen: CEL-01 + CEL-02 (fusionados por QC)
- **Causa probable (opcional):** `ObrasTabla.tsx:48`, `PresupuestoPorRubroPantalla.tsx:41` y `ProveedoresPantalla.tsx:140`: son `<Link>` en línea sin `min-height`; los `mailto:` y `tel:` de los contactos tampoco lo tienen. La fila `tr` de la tarjeta de Obras no es clicable.

### PU-212 · Con teclado, "Pegar link" y "Agregar link" dejan el foco en la nada: en un diálogo, el foco sale del diálogo
- **Severidad:** baja
- **Tipo:** accesibilidad
- **F-XX / pantalla:** F-19 diálogo "Agregar certificado o documento" `/obras/{id}` · F-16 · W-06 Nueva obra `/obras/nueva` (oferta técnica). También deberían estar afectados los demás campos de adjuntos, porque usan el mismo componente.
- **Persona:** Hernán (Jefatura de Proyectos), PC 1366×768, solo teclado
- **Pasos:** 1. En la ficha de la obra 2-961-2026, abrir "+ agregar certificado o documento" con Enter. 2. Llegar con Tab a "Pegar link" y apretar Enter. 3. Escribir el link: no se escribe en ningún lado. 4. Ir con Tab al campo "Link", escribir el link, llegar a "Agregar link" y apretar Enter. 5. Esperar a que termine ("Agregando el link…").
- **Esperado:** después de "Pegar link", el foco va al campo "Link". Cuando termina "Agregar link", el foco queda en el diálogo, por ejemplo en el link agregado o en "Pegar link". Es lo mismo que ya se arregló para Guardar (PU-136).
- **Obtenido:** después de Enter en "Pegar link", el foco queda en `body` ("foco=BODY (FUERA del diálogo)"). Lo que se escribe se pierde, y al tocar "Agregar link" aparece "Pegá el link.". Durante "Agregar link" el foco se queda bien en el botón ("Agregando el link…"). Cuando termina, el botón desaparece y el foco vuelve a `body`, fuera del diálogo. El lector de pantalla no anuncia nada, y el siguiente Tab salta al primer control del diálogo. En Nueva obra pasa lo mismo: después de Enter en "Pegar link" y al terminar "Agregar link", el foco queda en `body`, y el Tab siguiente va a "+ agregar presupuesto adicional" y se saltea el "quitar" del link recién agregado. Reproducido: diálogo 4 veces (2 con red normal y 2 con 2,5 s), Nueva obra 3 veces.
- **Evidencia:** `capturas/PU-212-1.png` ("Pegá el link." después de escribir sin foco) · `capturas/PU-212-3.png`, `capturas/PU-212-4.png` (link agregado, foco en `body`) · `CEL\out-foco-doc-3.txt` ("agregar link +2500 ms: foco=BODY (FUERA del diálogo)") · `out-foco-link-2.txt`, `out-foco-link-3.txt` · origen: CEL-03
- **Causa probable (opcional):** `shared/adjuntos/AdjuntosField.tsx:166-197`: `setPegandoLink(true)` desmonta el botón "Pegar link" y `PegarLink` no enfoca su campo al montarse. Al terminar (`onAgregado` → `setPegandoLink(false)`), se desmonta `PegarLink` mientras el foco está en su botón. Seguramente "Cancelar" de `PegarLink` hace lo mismo (no se probó).
- **Regresión de:** no; arreglo incompleto de PU-136 (front `e062ff0` cubre los botones de guardar; no tocó `AdjuntosField`, cuyos botones ya perdían el foco)

## ID del borrador → PU-2XX

| ID del borrador | Rol | PU nuevo | Capturas y logs renombrados |
|---|---|---|---|
| REG-02 | Cazador de regresiones | PU-201 | `REG-02-1/2.png` → `PU-201-1/2.png` |
| BK-03 | QA backend | PU-202 | `BK-03-1.png` → `PU-202-1.png` · `logs/BK-03.log` → `logs/PU-202.log` |
| BK-02 | QA backend | PU-203 | `BK-02-1/2.png` → `PU-203-1/2.png` · `logs/BK-02.log` → `logs/PU-203.log` |
| REG-01 | Cazador de regresiones | PU-204 | `REG-01-1/2/3.png` → `PU-204-1/2/3.png` |
| B-01 | Verificador B | PU-205 | `B-01-1/2.png` → `PU-205-1/2.png` |
| C-01 | Verificador C | PU-206 | `C-01-1/2/3.png` → `PU-206-1/2/3.png` |
| C-02 | Verificador C | PU-207 | `C-02-1/2.png` → `PU-207-1/2.png` |
| REG-03 | Cazador de regresiones | PU-207 (fusionado con C-02) | `REG-03-1/2.png` → `PU-207-3/4.png` |
| C-03 | Verificador C | PU-208 | `C-03-1/2.png` → `PU-208-1/2.png` |
| C-04 | Verificador C | PU-209 | `C-04-1/2.png` → `PU-209-1/2.png` |
| CEL-01 | QA celular y accesibilidad | PU-210 | `CEL-01-1/2.png` → `PU-210-1/2.png` |
| CEL-02 | QA celular y accesibilidad | PU-210 (fusionado con CEL-01 por QC; PU-211 queda sin usar) | `CEL-02-1/2.png` → `PU-211-1/2.png` (se citan desde PU-210) |
| CEL-03 | QA celular y accesibilidad | PU-212 | `CEL-03-1/3/4.png` → `PU-212-1/3/4.png` (no hay `CEL-03-2.png`) |
| BK-01 | QA backend | — (incidente del entorno, ver `reporte.md` §2) | `logs/BK-01.log` sin renombrar |

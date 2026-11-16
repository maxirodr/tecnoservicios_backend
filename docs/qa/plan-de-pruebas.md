# Plan de pruebas · Etapa 1 · NS-2026-025 Tecnoservicios Bahía

> Qué se prueba, cómo, contra qué base y con qué datos, para que cada entrega semanal llegue al
> cliente probada y para poder demostrar los 4 criterios de aceptación del cap. 13 del Documento
> del sistema firmado. Trazabilidad función por función: [`matriz-trazabilidad.md`](matriz-trazabilidad.md).
> Historias y DoR/DoD: [`../backlog.md`](../backlog.md).

---

## 1. Estrategia: la pirámide

| Nivel | Herramienta | Qué cubre | Cuántos |
|---|---|---|---|
| **Unit de dominio** | PHPUnit (`tests/Unit/<Modulo>`) · Vitest (front: helpers y esquemas zod) | Reglas puras sin base ni HTTP: cálculo de prioridad por plazos, transiciones de estado (pedido y obra), `Importe` y `Cantidad` (2 y 3 decimales, negativos en devoluciones), `NumeroPresupuesto`, armado de la rendición (EPP → INSUMOS, solo total, saldo = presupuestado − imputado), jerarquía sin ciclos y "mi gente" recursiva, normalización de nombres de proveedor para "parecidos". | Muchos y rápidos. Toda regla con números o estados tiene su unit. |
| **Feature HTTP** | PHPUnit (`tests/Feature/<Modulo>`) contra MySQL real (`tecnoservicios_testing`) | **Cada endpoint** con, como mínimo: camino feliz · validación (422 con `errors`) · **permitido** según la tabla de permisos · **denegado** (403 para Ver que intenta editar y para Sin acceso) · **IDOR** (un id ajeno con alcance "sus obras" o "mis pedidos" → 403) · sobre JSON `{data, message, errors}` también en 401/403/404/422/429 · idempotencia (`Idempotency-Key` repetida → mismo recurso, no duplica) en los POST que crean · registro en `actividades` cuando corresponde. | Uno o más archivos por endpoint. |
| **E2E** | Playwright (front), dos suites: **mock** (`npm run e2e`, API simulada con `page.route`, puerto 5310) e **integración real** (`npm run e2e:integracion`: el front en 5312 contra este backend en 8320 sobre la base descartable `tecnoservicios_integracion`, ver §2) | **Camino feliz por feature** tal como lo hace una persona del sector, más el denegado visible más importante (botón que no aparece). Proyectos **PC** (1366×768, Chromium) y **celular** (390×844, emulación de teléfono) para las pantallas de celular (W-16, W-41…W-44, W-47) y un recorrido responsive por entrega. | Uno por feature (ver matriz). |

Reglas de la pirámide:

- **El nombre del test dice la regla**, en español sin tildes: `test_completar_pedido_sin_comentario_es_rechazado`,
  no `test_complete_validation`. Si el test falla, el nombre ya dice qué regla del cliente se rompió.
- **Permisos por matriz, nunca por rol.** Los tests de permiso arman el caso cambiando la celda de la
  tabla (no "usuario admin"), y hay al menos un test por módulo que cambia una celda y verifica que el
  cambio rige sin volver a entrar.
- **Datos sensibles por JSON, no por pantalla.** Monto de la obra y ganancia (F-54) se prueban
  verificando que **el campo no viene** en la respuesta para quien no lo puede ver.
- **Tiempo controlado.** Prioridad, vencidos, "falta 1 día" y "esta semana" se prueban con el reloj
  fijado (`Carbon::setTestNow` / `travelTo`), nunca dependiendo del día en que corre la suite.
- **Mail por cola, verificado con fakes** (`Mail::fake`, `Queue::fake`, `Notification::fake`): se
  verifica a quién, qué tipo y que **no** se mandó lo que no corresponde ("que no me explote el correo").
- **Test de arquitectura** (PHPUnit): `Domain` no importa `Illuminate\Http` ni controllers; los módulos
  solo se hablan por eventos o por su `Contracts/`; ninguna Policy pregunta por nombre de sector.

## 2. Base de tests separada

- Base: **`tecnoservicios_testing`** (MySQL real de Laragon, 127.0.0.1:3306). **Nunca** la de desarrollo
  (`tecnoservicios`) y **nunca** `DROP DATABASE` de ninguna base.
- `phpunit.xml` fija `DB_DATABASE=tecnoservicios_testing`. Además, un **guard** en el `TestCase` base
  aborta toda la corrida si la conexión activa no se llama exactamente `tecnoservicios_testing` (protege
  contra un `.env` o un `--env` mal puesto que apunte a la de desarrollo).
- Cada test arranca limpio: `RefreshDatabase` (migra una vez y envuelve cada test en transacción). Un test
  que necesita commit real (colas síncronas, locks de idempotencia) usa `DatabaseTruncation` **solo sobre
  la base de tests**. Ningún test deja filas para el siguiente: si un test falla solo en la corrida
  completa y no aislado, se trata como contaminación y se arregla.
- **Feature y E2E no corren en paralelo sobre la misma base.** En CI: unit → feature → E2E, secuencial.
  Dos runners a la vez sobre la misma base dan fallas falsas.
- La **integración real** (`tecnoservicios_frontend/playwright.integracion.config.ts`) levanta
  `php artisan serve --port=8320 --no-reload` con su propio entorno (`DB_DATABASE=tecnoservicios_integracion`,
  `SANCTUM_STATEFUL_DOMAINS=localhost:5312`, `CORS_ALLOWED_ORIGINS`, `SESSION_DOMAIN=localhost`,
  `MAIL_MAILER=log` con el canal `integracion` → `storage/logs/integracion-mail.log`, `QUEUE_CONNECTION=sync`,
  prefijo de Redis y carpeta de adjuntos propios) y el front en 5312 con `VITE_API_URL=http://localhost:8320`.
  El global setup **verifica que el backend apunta a `tecnoservicios_integracion`** (y que no hay config
  cacheada) antes de correr `migrate:fresh --seed` sobre esa base descartable; si no, corta. Nunca toca
  `tecnoservicios_testing` ni la de desarrollo. El código de ingreso se lee del mail logueado: no hay
  endpoints de test ni atajos en el código. Un solo worker (comparten base); el proyecto celular corre
  los caminos críticos (tag `@celular`) después del de PC.
- Redis de tests: base lógica distinta de la de desarrollo; el rate limiter se limpia en `setUp`.
- La configuración inicial de permisos y el catálogo de rubros llegan **por migración**: los tests usan
  esas migraciones (no seeders aparte), así lo que se prueba es lo que llega a producción.

## 3. Regla: una suite con 0 assertions no prueba nada

- `phpunit.xml` con `failOnRisky="true"`, `failOnWarning="true"` y `beStrictAboutTestsThatDoNotTestAnything="true"`:
  un test sin assertions **falla**.
- Antes de leer "verde", se lee el resumen: **cantidad de tests y de assertions**. Si la corrida tiene
  muchas fallas con 0 assertions, el problema es el entorno (MySQL o Redis de Laragon apagados, base
  inexistente), **no** el código: se arregla el entorno y se vuelve a correr; ese resultado no cuenta.
- Playwright: cada test termina con al menos un `expect` sobre algo que ve el usuario (texto, fila,
  número), no solo "la página cargó".
- Un test que se saltea (`markTestSkipped`, `test.skip`) sin una DU-XX o un issue enlazado en el
  mensaje **no se acepta** en el DoD.
- Los fixtures propios no alcanzan: además de los datos de la sección 4, cada entrega se prueba
  (por QC) con la carga que hace el cliente en su prueba semanal.

## 4. Datos de prueba: ficticios, con la forma real

Ningún nombre real de empleados, clientes o proveedores; ningún importe real. Los importes son
redondos e inventados. Pero la **forma** de los datos copia la de la minuta y las rendiciones
(análisis del 9-oct y organigrama del 16-oct), porque ahí están los casos borde.

Se arman con factories + un `EscenarioRealistaSeeder` (solo base de tests) con:

### 4.1 Usuarios, sectores y jerarquía
- Los **11 sectores** de arranque (Gerencia y Jefatura de Administración separadas, DU-01/DU-A01);
  **Pañol y Logística sin usuarios**.
- ~13 usuarios ficticios con jerarquía de 4 niveles: Dirección → gerencia (Operaciones /
  Administración) → jefatura (Proyectos / Administración) → personas (Ingeniería de proyectos ×2,
  Seguridad e Higiene, Compras, RRHH). Calidad depende de Dirección (staff).
- Un usuario **inactivo** con historial (autor de comentarios y responsable de una obra terminada).
- Una persona con mucha carga (≈4 de cada 10 pedidos a su nombre, como compras hoy) para probar carga
  por persona y Mis tareas con volumen.

### 4.2 Obras y presupuestos
- Obra con **1** presupuesto por rubro; obra con **2** (principal + adicional "A1"); obra con **3**
  (principal + 2 adicionales); obra con **2 presupuestos de años distintos**.
- **2 obras con solo el total** (como 2 de sus 6 rendiciones) y **1 obra sin presupuesto cargado**.
- Obra donde trabajan como **subcontratistas**: cliente (quien emite la OC) + planta distinta.
- **Mismo cliente con 3 obras** (para buscador y listado).
- Obra **sin OC** (la OC no es obligatoria) y obra con OC cargada después (historial de edición).
- Obras en los 3 estados; una **Terminada con pedidos abiertos** (hoy hay 18 abiertos en solapas
  ocultas) para F-21.
- N.º de presupuesto con revisión ("2-131-2026 REV01") y prefijos 1, 2 y 3.
- **Adjuntos (ADR-014):** una obra con la oferta técnica **subida como PDF** y otra con la oferta como
  **link** al Drive; una OC como PDF y un mail de aprobación como link; un certificado con una **foto
  HEIC** del celular y otro con un PDF **y** un link; una carpeta de Drive (solo link).

### 4.3 Pedidos (semana 2)

> Detalle por persona y por caso, con la jerarquía de 3 niveles y los destinatarios de cada aviso:
> §9.1. Lo de abajo es la lista de formas de la minuta que el escenario tiene que cubrir.

- Estados en la proporción de la minuta: casi todos Completada, algunos No iniciada, En curso y
  **Bloqueada siempre con motivo** ("esperando presupuesto", "reprogramar fecha").
- Pedidos de la carga inicial **sin fecha de pedido** (solo 18 de 39 solapas la tienen), **sin
  solicitante** (falta en el 78 %), **sin prioridad** (101 de 755) y un par **sin estado**.
- Prioridad elegida a mano que **no coincide** con la calculada (pasa en ~4 de cada 10): urgente con 0
  días, prioritario con 1 a 7, normal con 8 o más, y casos frontera exactos (1, 7 y 8 días).
- **Pedido sin obra** (Servicios generales: vehículo, pañol, curso) y **pedido de varias obras** de un
  mismo cliente cargado sin obra nombrando las obras en la tarea.
- Pedido con **período inicio–fin** (alojamiento de 7 personas, alquiler de equipo).
- Pedido con **varios archivos o links**: links a Drive, Maps y la página de un producto, más una
  foto subida desde el celular; y un pedido **completado con la foto del remito** como comprobante
  de cierre (lo que se probaba antes como "link al remito").
- Pedido completado por alguien cuyo superior **no** es el del solicitante (para verificar que el
  aviso va al superior **del solicitante**, DU-13).
- "Con quién / para quién" que **no es proveedor**: el cliente, un área interna, la propia empresa.
- Pedido completado y **reabierto** ("falta tal cosa acá").
- Pedido con entrega **mañana** abierto (recordatorio) y otro igual pero completado (sin recordatorio).
- Pedido de la obra administración → operaciones ("Planilla de cierre y control de obras").
- **Equipos en reparación** con los 5 estados (incluido "Para retiro") y las 3 aprobaciones; uno con
  el presupuesto del taller en PDF y el inventario como link.

### 4.4 Gastos y rendición (semana 3)
- **Factura repartida en varias obras**: una factura de ferretería con ~30 renglones a 3 obras, 4 rubros
  y renglones **sin obra** (stock, vehículos, oficina).
- **Renglón dividido en dos obras** (10 bolsas: 6 a una, 4 a otra) → dos renglones.
- Proporción de **renglones sin documento entre 34 % y 78 %** por obra.
- **Combustible por carga** (sin documento, con "rendido por" y **sin** el nombre del chofer en el
  concepto) y la factura posterior del mismo proveedor que **no** se imputa.
- **Consumo de stock propio** como renglón sin documento (hoy "ARCHIVO") y la **compra a stock sin obra**:
  la suma de la obra no la cuenta dos veces.
- **Retiros de la empresa hermana por remito**, varios retiros por fecha del mismo material; un
  remito como foto y otro como link.
- Una factura con su **PDF subido** y otra con **link** al escaneo; un gasto sin comprobante con la
  **foto de los tickets**; una factura **anulada** que conserva sus adjuntos.
- **Amortización de herramientas**: un renglón por obra.
- **Devolución de materiales** con cantidad negativa que **resta**.
- Renglones imputados a **EPP** que en el resumen suman a **INSUMOS**.
- **Typo de rubro "INSUMO"** (hoy hace que un gasto no sume): en la carga manual no se puede elegir;
  en la carga inicial se detecta y se mapea con revisión de administración (DU-21).
- **Bonificación** aplicada al precio, **moneda extranjera** convertida con la cotización en el detalle,
  **subcontrato** en adelanto y saldo (dos renglones).
- **HORAS LIQUIDADAS y FIJOS** como monto total por período, sin detalle por empleado.
- Un rubro **pasado** (gastado > presupuestado, ej. 104 %) y una obra con varios.
- **Proveedor genérico** para gastos sin ticket, y un proveedor **escrito de 4 formas** en los datos
  de arranque (para "parecidos").
- Una rendición **de referencia**: una obra ficticia con su planilla Excel ficticia armada a mano
  (General con rubros, EPP sumado a INSUMOS, ganancia y %), que es el **oráculo**: la rendición del
  sistema tiene que dar exactamente esos números.

### 4.5 Archivos de prueba para adjuntos (ADR-014)

En `tests/fixtures/adjuntos/`, todos **fabricados para el proyecto** (ningún documento real del
cliente, sin datos personales ni metadatos de ubicación):

| Archivo | Qué es | Para qué |
|---|---|---|
| `ejemplo.pdf` | PDF de 1 página con el texto "Documento de ejemplo" | subida válida (oferta, certificado, factura) |
| `ejemplo.jpg` · `ejemplo.png` · `ejemplo.webp` | imagen chica de un recuadro gris | subida válida de imagen |
| `foto-celular.heic` | foto HEIC generada (un recuadro), **sin EXIF de GPS** | subida desde el celular (iPhone) |
| `planilla-ejemplo.xlsx` · `nota-ejemplo.docx` · `datos.csv` · `texto.txt` | contenido inventado de 2 o 3 filas | resto de tipos permitidos |
| `ejecutable-renombrado.pdf` | bytes de un ejecutable (`MZ…`) con extensión `.pdf` | rechazo **por contenido** |
| `pagina.html` · `dibujo.svg` | HTML y SVG mínimos (el SVG con un `<script>` inofensivo) | rechazo de HTML y SVG |
| (generado en el test) | archivo de 20 MB + 1 byte, creado al vuelo y borrado al terminar | límite de tamaño |

Links de prueba: `https://drive.example/carpeta-ejemplo`, `https://maps.example/punto-ejemplo` (válidos);
`ftp://servidor-ejemplo/plano.pdf`, `javascript:alert(1)`, `file:///c:/ejemplo.pdf`, `drive.example/sin-esquema`
(inválidos en el backend; el front completa `https://` en el último). El disco de adjuntos de los
tests es uno temporal (`Storage::fake`), así ningún test escribe en `storage/app/private` real; la
purga de huérfanos se prueba con el reloj fijado (`travelTo` + 24 h).

## 5. Seguridad (en cada entrega que toca permisos o datos)

- Permitido / denegado por cada celda relevante de la tabla; un cambio de celda rige sin volver a entrar.
- IDOR: ids de obras, pedidos, gastos, usuarios y avisos ajenos → 403 (avisos de otro usuario: 404).
- Asignación masiva: campos que el usuario no debe tocar (`estado` en el alta de obra, `sector_id` y
  `jefe_id` en el propio perfil, `cargado_por`, `solicitante_id`) se ignoran o se rechazan.
- Sesión (ADR-015): inactivo no recibe código ni entra; desactivar cierra sesiones; código de un uso,
  10 min, 5 intentos, guardado hasheado y fuera de los logs; CSRF de Sanctum activo; rate limit al pedir
  código y al ingresar, por mail y por IP (429 con sobre JSON); sesión de 90 días desde el último uso.
- Datos sensibles: monto y ganancia fuera del JSON para quien no los ve; el buscador no devuelve grupos
  de módulos sin acceso; los mails no exponen datos de otras obras.
- Datos personales: el concepto de combustible no lleva el nombre del chofer (va en "rendido por").
- **Adjuntos (ADR-014)** — desde la semana 1: tipo validado **por contenido** (ejecutable renombrado,
  HTML y SVG rechazados); 20 MB máximo; links solo `http(s)`; descarga solo por
  `GET /api/adjuntos/{id}` con la Policy del recurso dueño, `Content-Disposition: attachment`, MIME
  detectado y `nosniff`; **IDOR en la descarga → 404** (id ajeno, obra fuera de "solo sus obras",
  temporal de otro usuario); no se asocia un adjunto ajeno ni de otro contexto; la `ruta` no viaja en
  el JSON; nada se sirve desde `public/`; huérfanos purgados a las 24 h. `seguridad.md` (todavía no
  existe) suma esta sección según ADR-014.

## 6. Criterios de entrada y salida por entrega

### Entrada (antes de empezar a probar la entrega N)
- Las historias de la semana cumplen DoR (sin DU bloqueantes abiertas).
- Base `tecnoservicios_testing` migrada desde cero sin errores, con el escenario realista cargado.
- La matriz tiene los nombres de los tests previstos de cada F-XX de la semana.
- La entrega anterior está aceptada por el cliente o sus observaciones están cargadas como historias.

### Salida (para publicar la entrega N al cliente)
- **Unit + feature + E2E en verde**, con el resumen de tests y assertions anotado (ninguna corrida
  con 0 assertions cuenta).
- Toda F-XX de la semana con al menos: 1 test feature de permitido, 1 de denegado, 1 de la regla
  principal y 1 E2E de camino feliz (PC; y celular si su pantalla lo es).
- Sin defectos **críticos ni altos** abiertos. Medios: con historia y fecha.
- Pint, Larastan nivel 8, ESLint y `tsc` del build de producción sin errores.
- Revisión de seguridad hecha (sección 5) si la entrega toca permisos o datos.
- QC recorrió cada pantalla nueva contra su maqueta (PC y, si aplica, celular) y anotó diferencias.
- Matriz actualizada (✅ por F-XX terminada, parciales explicados).
- Instrucciones de prueba para el cliente escritas en sus palabras ("Qué prueban ustedes" del roadmap).

| Entrega | Qué prueba el cliente (roadmap) | Qué mira QC además |
|---|---|---|
| Semana 1 · Base | Cargan 2 o 3 obras reales y sus usuarios. | Que cada persona entre y vea solo lo de su sector; que las obras con 2–3 presupuestos y las de solo total se carguen sin trabas. |
| Semana 2 · Pedidos | Lo usan en paralelo con la minuta unos días. | Que ningún pedido cargado en la minuta en esos días falte en el sistema; avisos que llegan y mails que **no** llegan; criterio global n.º 3 al 100 % (detalle: §9.6 y §9.7). |
| Semana 3 · Gastos y rendición | Cargan las facturas de una obra en curso y la comparan con su rendición en Excel. | Diferencia por rubro = 0 (con EPP en INSUMOS); cada diferencia se explica o es un defecto. |
| Semana 4 · Puesta en marcha | Arrancan; la minuta y las rendiciones en Excel quedan de consulta. | Conteos de la carga inicial contra el origen (pedidos abiertos, obras en ejecución, gastado por rubro). |

## 7. Cómo verifica QC los 4 criterios de aceptación globales (cap. 13)

| # | Criterio firmado | Cómo se prueba (automático) | Cómo lo verifica QC (con el cliente) |
|---|---|---|---|
| 1 | Cualquier gerente abre una obra y sabe cuánto lleva gastado contra lo presupuestado, por rubro, sin esperar al final. | Feature: la rendición de la obra de referencia da los números del oráculo rubro por rubro, también con EPP → INSUMOS, solo total, devoluciones negativas y horas/fijos por período. E2E: con una sesión de gerencia abierta en W-11, otra sesión (Administración) carga una factura; en ≤15 s (polling) y sin recargar, el rubro cambia. | Semana 3: el cliente carga las facturas de una obra en curso y compara con su Excel; QC registra la diferencia por rubro (objetivo 0). Una persona de cada gerencia abre una obra en PC y en celular (W-44) y lee presupuestado, gastado y saldo sin ayuda. |
| 2 | Una factura se carga una sola vez y queda imputada en todas las obras que corresponden. | Feature: una factura con renglones a 3 obras + sin obra aparece en la rendición de cada obra con su parte; la suma de renglones = total de la factura; anularla la saca de todas; el doble clic con la misma `Idempotency-Key` no duplica; la compra a stock sin obra y su consumo no se cuentan dos veces; la factura del combustible ya imputado por carga no se vuelve a imputar. | QC verifica que no existe ninguna pantalla para "volver a cargar" una factura en una obra: lo de W-46 son renglones sin documento. En la semana 3, la factura repartida del cliente se busca por número (F-03) y se ve en cada obra. |
| 3 | Ningún pedido se atrasa sin que el propietario se entere antes. | Feature con reloj fijado: al asignar o reasignar, aviso en el sistema + mail al propietario en el momento; el día anterior a la entrega, si sigue abierto (No iniciada, En curso o Bloqueada), recordatorio dentro del sistema, una sola vez (`dedupe_key`), sin mail; completado → sin recordatorio; vencidos marcados en Mis tareas. Caso borde: pedido cargado con entrega hoy o ya vencida (fecha de pedido corregida): el aviso de asignación llega igual en el momento. | Semana 2: durante el uso en paralelo, QC toma los pedidos que vencieron esos días y verifica en `avisos` que cada propietario tuvo el aviso antes de la fecha de entrega (objetivo: 100 %). Revisa también los pedidos "en el sector" sin persona (DU-16). |
| 4 | El equipo deja de usar la minuta en Excel porque el sistema le resulta más fácil. | No se automatiza: es adopción. Lo que sí se prueba es lo que la favorece: tiempos de carga (W-43: un pedido en menos de un minuto en el celular), que todo lo de la minuta tenga lugar en el sistema (columnas de la minuta ↔ campos del pedido, ver matriz F-24). | Semanas 2 y 4: QC cuenta, por semana, pedidos nuevos en el sistema vs. filas nuevas en la minuta, y usuarios que entraron al menos 3 días. Criterio de cierre del acompañamiento: 2 semanas seguidas sin filas nuevas en la minuta y todos los usuarios activos usándolo. Lo que la gente siga haciendo en el Excel se registra como hallazgo (qué le falta o qué le cuesta), sin agregar funciones fuera del documento. |

## 8. Defectos

- Severidad: **crítico** (pérdida o mezcla de datos, permiso roto, número de la rendición mal),
  **alto** (no se puede completar un recorrido del cap. 4), **medio** (se puede con rodeo), **bajo**
  (texto, estética).
- Cada defecto lleva F-XX, W-XX, pasos, datos usados (ficticios) y el test que lo reproduce. Se cierra
  cuando ese test está en verde en la suite.

## 9. Semana 2 · Pedidos (la minuta)

> Historias: HU-026…HU-060 ([`../backlog.md`](../backlog.md) §6). Tests previstos por F-XX:
> [`matriz-trazabilidad.md`](matriz-trazabilidad.md). Reglas: `../especificacion/pedidos.md` y
> `avisos.md` (RN-PED, RN-AVI; las escribe el Analista en paralelo). Donde una regla depende de una
> duda abierta (DU-Q01…DU-Q03 de PO + QA, DU-P01…DU-P24 del Analista) el test se escribe con la
> propuesta y la cita en un comentario arriba del test (`// DU-P04`), así se encuentra y se cambia en
> un lugar si Juani decide otra cosa.
> Todo lo de esta sección es **ficticio**: ningún nombre de empleado, cliente o proveedor real y
> ningún importe (los pedidos no tienen importes).

### 9.1 Datos de prueba con la forma de la minuta (`EscenarioPedidosSeeder`, solo base de tests)

Se suma al `EscenarioRealistaSeeder` (§4) y reusa sus obras. La **forma** sale del análisis de la
minuta del 9-oct (755 pedidos, 42 solapas, 18 variantes de columnas); los valores son inventados.

**Personas y jerarquía (3 niveles para "mi gente", más una rama aparte):**

| Persona (ficticia) | Sector | Depende de | Para qué está |
|---|---|---|---|
| Dora | Dirección | — | sin jefe: un pedido que pide ella no genera aviso de superior (DU-13) |
| Gastón | Gerencia de Operaciones | Dora | **nivel 1**: su gente = Hernán, Laura, Iván y Sofía |
| Hernán | Jefatura de Proyectos | Gastón | **nivel 2**: su gente = Laura e Iván; aparece como "(yo)" en W-26 |
| Laura | Ingeniería de proyectos | Hernán | **nivel 3**; solicitante frecuente (como hoy ingeniería) y responsable de dos obras |
| Iván | Ingeniería de proyectos | Hernán | nivel 3; completa pedidos de otros (superior distinto al del solicitante) |
| Sofía | Seguridad e Higiene | Gastón | otra rama de Operaciones |
| Diego | Gerencia de Administración | Dora | **otra rama**: no debe ver la gente de Gastón |
| Julia | Jefatura de Administración | Diego | jefatura de Compras y RRHH |
| Carla | Compras | Julia | **la más cargada**: ≈ 4 de cada 10 pedidos a su nombre (como compras hoy) |
| Rita | RRHH | Julia | habilitaciones, cursos |
| Pablo | Calidad | Dora | pide pedidos sin obra (auditoría interna) |
| Mario | Compras | Julia | segunda persona de Compras: pedidos "en el sector" llegan a Carla **y** a Mario |
| Tomás | Compras | Julia | **inactivo**: no recibe avisos ni mails; aparece con "(inactivo)" en lo histórico |

**Pedidos (unos 60, con las proporciones de la minuta):**

- **Estados:** la mayoría Completada; el resto repartido en No iniciada, En curso y **Bloqueada
  siempre con motivo** ("esperando presupuesto del taller", "buscar otra opción", "reprogramar
  fecha", "falta el talle de botines de 3 personas").
- **Con y sin obra:** 3 obras en ejecución con 5 a 15 pedidos cada una, 1 obra **Terminada con
  pedidos abiertos** (para W-22), 1 obra Confirmada **sin presupuesto por rubro** con pedidos
  (HU-019), y **pedidos sin obra** de Servicios generales: service de una camioneta, recarga de
  matafuegos de la oficina, documentación para una auditoría interna, renovación del seguro de
  vehículos. Un **pedido para varias obras** de un mismo cliente cargado sin obra ("Curso de
  trabajo en altura · obras Piso nave 3 y Losa tanque B").
- **Pedidos de 3 sectores distintos como propietario** en una misma obra: Compras (materiales,
  alquileres, alojamiento), Seguridad e Higiene (programa de seguridad, habilitaciones) y Jefatura
  de Administración (certificado), más uno de **Ingeniería pedido por Administración** ("Planilla de
  cierre y control de obras": el sentido administración → operaciones).
- **Período inicio–fin:** "Alojamiento 6 personas (26-oct al 6-nov)", "Alquiler de hidrolavadora
  (14 al 23-oct)", un obrador; uno que cruza de un mes al siguiente (para el calendario).
- **Varios archivos o links:** un pedido con link a una carpeta de Drive, uno a Maps y uno a la
  página de un producto, más una foto subida (`foto-celular.heic` de §4.5); otro con un PDF.
- **"Con quién / para quién" que no es proveedor:** el cliente ("Planta Ejemplo SA"), un área
  interna ("SyH"), la propia empresa ("la empresa"), un tercero de servicio ("instituto de
  capacitación de ejemplo") y un texto genérico ("Varios"), además de proveedores ficticios escritos
  a mano de dos formas distintas (no se unifican: es texto libre).
- **Prioridad:** calculada en los tres niveles con fechas frontera exactas (0, 1, 2, 7 y 8 días);
  **corregida a mano** en algunos que no coinciden con la calculada (pasa en ~4 de cada 10 en la
  minuta); un pedido con la **fecha de pedido corregida hacia atrás** (llegó antes por mail).
- **En el sector sin persona:** un pedido de Compras en el sector (llega a Carla y a Mario).
- **Completado por alguien cuyo superior no es el del solicitante:** pide Laura (jefe Hernán),
  completa Carla (jefa Julia) → el aviso de superior va a **Hernán**, no a Julia (DU-13).
- **Completado con la foto del remito** como comprobante de cierre y otro con un link al recibo.
- **Completado y reabierto** ("falta tal cosa acá") y vuelto a completar.
- **Reasignado** de Carla a Mario con la ida y vuelta intacta; uno reasignado al sector.
- **Seguidores:** Gastón sigue dos pedidos de Laura; Hernán es a la vez **superior y seguidor** de un
  pedido de Laura (debe recibir un solo aviso por evento).
- **Pedido de Tomás (inactivo)** abierto como propietario (para DU-P09) y completados suyos en el
  historial.
- **Equipos en reparación:** 5 equipos, uno en cada estado (incluido "Para retiro"), las 3
  aprobaciones, uno con el presupuesto del taller en PDF y el inventario como link, uno "Completada"
  devuelto a pañol.

Los pedidos de la **carga inicial** (sin fecha de pedido, sin solicitante, sin prioridad) son de la
semana 4 (§4.3, DU-20): en la semana 2 todo pedido nace por el alta y cumple DU-14.

### 9.2 Avisos y mail

Con `Notification::fake` / `Mail::fake` / `Queue::fake` y la tabla `avisos` real. Cada caso verifica
**a quién sí** y **a quién no** (lista cerrada de destinatarios, no "contiene").

| Caso | Le llega (en el sistema) | Mail | **No** le llega |
|---|---|---|---|
| Laura crea un pedido para Carla | Carla | Carla (cola `mail`) | Laura (lo hizo ella), Hernán |
| Laura crea un pedido para Compras en el sector | Carla y Mario | Carla y Mario | Tomás (inactivo), Laura |
| Carla lo toma | Laura y los seguidores | nadie (por defecto) | Carla |
| Carla lo bloquea "esperando presupuesto" | Laura y seguidores, con el motivo | nadie | Carla |
| Laura comenta | Carla y seguidores | nadie | Laura |
| Carla comenta | Laura y seguidores | nadie | Carla |
| Gastón (tercero) comenta | Laura y Carla, y los otros seguidores | nadie | Gastón |
| Carla completa | Laura (con el comentario), **Hernán** (superior de Laura), seguidores | nadie | Carla, **Julia** (superior de Carla) |
| Laura le pide algo a Hernán (su jefe) y Hernán lo completa | Laura | nadie | Hernán: no se avisa como superior de Laura (lo hizo él) |
| Dora pide y Carla completa | Dora | nadie | ningún superior (Dora no tiene jefe) |
| Hernán es superior **y** seguidor; Carla completa | Hernán **un solo** aviso | nadie | — |
| Laura reabre | Carla | nadie | Laura, Hernán |
| Laura reasigna de Carla a Mario | Mario | Mario | Carla (no está en la tabla de avisos) |
| Retomar un Bloqueada | nadie | nadie | (Retomar no avisa) |
| Laura activó "completado" por mail en Mis avisos; Carla completa | Laura | **Laura** | — |
| Seguidor con todo activado por mail | en el sistema | **nunca** por ser seguidor | — |

- **Nunca a uno mismo:** un test por tipo de evento con el autor como posible destinatario.
- **Recordatorio no repetido:** el comando corre 3 veces el mismo día (y dos procesos a la vez, con
  `DatabaseTruncation` y commit real) → **un** aviso por pedido y fecha de entrega (`dedupe_key`
  único); al cambiar la entrega, uno nuevo para la fecha nueva.
- **Mail solo la asignación (por defecto):** después de recorrer todo el ciclo de un pedido (crear,
  tomar, bloquear, retomar, comentar, completar, reabrir), `Mail::assertQueued` cuenta **exactamente
  1** mail (el de la asignación) y `assertNotQueued` para el resto.
- **Cola 1/s:** 5 mails encolados (pedido al sector de 5 personas) con el limitador de Redis: el job
  se libera y reintenta en vez de mandar dos en el mismo segundo; los 5 llegan (verificado con el
  reloj y el limitador, sin dormir el test).
- **El mail no frena:** con el transporte de mail fallando, el pedido se crea igual y el aviso en el
  sistema existe; el mail queda en `failed_jobs`.
- **Campana en vivo (E2E real):** con dos sesiones abiertas (Laura y Carla), Laura crea el pedido y
  el contador de Carla sube en ≤ 15 s sin recargar (ADR-005).

### 9.3 Pruebas de tiempo

- **Reloj fijo** con `Carbon::setTestNow` / `travelTo` en toda prueba que dependa de "hoy". Zona
  horaria de la app: `America/Argentina/Buenos_Aires`. Día de referencia: **miércoles 18-nov-2026,
  10:00** (semana del lunes 16 al domingo 22).
- **Fechas relativas** en factories y seeders: `today()->addDays(n)`, nunca fechas escritas a mano,
  así el escenario sirve cualquier día.
- **Prioridad:** entrega a +0, +1 (urgente), +2, +7 (prioritario), +8 (normal) días de la fecha de
  pedido; fecha de pedido corregida a −3 días; cambio de plazos en W-50 entre dos altas (el primero
  no cambia).
- **Vencidos:** abierto con entrega el martes 17 → vencido; con entrega hoy (miércoles 18) → **no**
  vencido ("hoy"); Completada con entrega pasada → no vencido; Bloqueada con entrega pasada →
  vencido. Corte del día a las 00:00 de Argentina: `travelTo('2026-11-18 23:59')` y `'2026-11-19
  00:00'` (DU-P14).
- **Esta semana (W-04):** reloj el miércoles 18; entregas el lunes 16 y martes 17 (vencidas), el
  miércoles 18, el domingo 22 (esta semana) y el lunes 23 (no). Ningún pedido cuenta en dos
  columnas. Repetir con el reloj en domingo 22 y en lunes 23 (cambio de semana).
- **Recordatorio:** reloj el martes 17 a las 7:00, 8:00 y 15:00 con entregas el miércoles 18
  (DU-P21: desde las 8:00); entrega el lunes 23 con el reloj el domingo 22 (días corridos, DU-Q01) y el
  viernes 20 (no debe salir todavía); pedido creado el martes 17 a las 16:00 con entrega el
  miércoles 18 → recordatorio en la corrida siguiente; pedido creado con entrega hoy → sin
  recordatorio, con aviso de asignación.
- **Calendario:** período que cruza de noviembre a diciembre aparece en los dos meses; vista semana
  del 16 al 22.

### 9.4 IDOR y alcance "propios" en Pedidos

La configuración inicial es Pedidos **E·T** para todos, así que estas pruebas **cambian la celda**
(nunca "usuario admin"; §1): Calidad pasa a Pedidos **V·P** o **E·P** ("propios" = los que pidió,
tiene, sigue o de su gente; `identidad.md` §6.1).

- Con la celda en propios, Pablo (Calidad) pide por id un pedido de Laura a Carla en el que no está
  → la misma respuesta que un pedido que no existe ("Pedido no encontrado", sin confirmar que existe;
  como la ficha de obra fuera de alcance; el código exacto lo fija spec pedidos.md). Igual para:
  detalle, recorrido, comentar, seguir, editar, tomar, completar, reasignar, reabrir, bajar un
  adjunto del pedido (404 de `GET /api/adjuntos/{id}`).
- **Listas:** Todos los pedidos, Servicios generales, calendario, buscador, Excel exportado, pestaña
  Pedidos de la obra **no incluyen** pedidos ajenos. Los **conteos** (columna "pedidos abiertos" y
  fila Servicios generales) cuentan **todos** los abiertos (propuesta DU-P17): se prueba que el número
  no abre nada y que la pestaña sí filtra. Un pedido que Pablo **sigue** sí aparece.
- **Mi gente con propios:** Gastón con la celda en propios ve los pedidos de Laura (su gente
  indirecta) y no los de Carla (otra rama).
- **Avisos ajenos:** `GET`/`PATCH` de un aviso de otra persona → 404; el contador nunca cuenta avisos
  de otro.
- **Asignación masiva:** `solicitante_id`, `estado`, `completado_por`, `prioridad_manual` sin la
  marca, `propietario_id` en la edición → se ignoran o se rechazan.
- **Sin acceso:** RRHH con Pedidos en Sin acceso → 403 en listas y en el alta; el ítem de la barra no
  aparece; Equipos en reparación con su propia celda (403 y adjuntos 404).
- **Cambio de celda en vivo:** se pasa la celda de E·T a V·P y el **próximo** pedido de esa persona ya
  rige (sin volver a entrar).

### 9.5 Celular

- Proyecto Playwright **celular** (390×844, emulación de teléfono) obligatorio para W-41, W-42, W-43 y
  W-47; tag `@celular` en los caminos críticos de la suite real.
- **Sin desplazamiento horizontal** medido en cada pantalla de pedidos (`scrollWidth ≤
  clientWidth`), y todos los botones dentro del ancho (mismo criterio que el arreglo de B-01).
- **Completar con la foto del remito:** `setInputFiles` con `foto-celular.heic` en el campo del
  comprobante; el pedido queda Completada y el adjunto se baja desde la PC.
- **Un pedido en menos de un minuto (criterio global n.º 4):** el E2E mide desde que toca "+ Nuevo
  pedido" hasta "✓ Pedido creado" con los datos de un pedido real ficticio; falla si pasa de 60 s
  (en la máquina de CI, sin red lenta simulada). QC lo repite a mano con una persona del cliente.
- **Barra de abajo:** Tareas · Obras · Avisos · Más con la semana 2 encendida; "Más" según el sector
  (Laura sin Proveedores ni Usuarios).
- QC recorre en un teléfono real (Android y, si hay, iPhone) Mis tareas, avisos, detalle, comentar,
  completar con foto, nuevo pedido y obras.

### 9.6 Criterio de aceptación global n.º 3 · "Ningún pedido se atrasa sin que el propietario se entere antes"

**Qué quiere decir, verificable:** para todo pedido abierto con fecha de entrega `E`, su
propietario (o cada persona activa del sector, si está sin persona) tiene **antes de `E`** al menos
un aviso dentro del sistema del pedido (asignación o reasignación en el momento, **y** "vence
mañana" el día anterior si seguía abierto), y la asignación le llegó **también por mail**. Además,
el día `E` y después lo ve marcado en Mis tareas (hoy / vencido).

**Prueba automática (feature, reloj fijado):** `CriterioGlobalTresTest::test_ningun_pedido_se_atrasa_sin_que_el_propietario_se_entere_antes`
1. Arma el escenario de §9.1 con 20 pedidos abiertos de entregas entre −2 y +10 días, propietarios
   de 4 sectores, uno en el sector sin persona, uno reasignado, uno con la entrega cambiada, uno
   Bloqueada y uno con la entrega hoy.
2. Avanza el reloj **hora por hora** durante 12 días corriendo el scheduler real
   (`schedule:run` con `travelTo`), y en el medio aplica las acciones del escenario (tomar,
   reasignar, cambiar la entrega, completar algunos).
3. Para **cada** pedido que estuvo abierto al llegar su entrega, verifica en `avisos`: existe un
   aviso `asignado` o `reasignado` para su propietario de ese momento con `created_at` anterior a la
   entrega, y uno `vence_manana` con `created_at` del día anterior (salvo los cargados con entrega
   hoy o pasada); y en la cola de mail, el mail de asignación a ese propietario. Verifica también que
   ninguno de esos avisos le llegó a quien hizo la acción y que el recordatorio no se repitió.
4. Los completados antes de la víspera **no** tienen recordatorio.

Tests de apoyo (matriz, §3 fila 3): `test_asignar_pedido_crea_aviso_al_propietario` ·
`test_pedido_al_sector_avisa_a_todas_sus_personas_activas` · `test_asignacion_envia_mail_por_cola_al_propietario` ·
`test_reasignar_pedido_avisa_al_nuevo_propietario_por_mail` · `test_recordatorio_se_crea_un_dia_antes_si_sigue_abierto` ·
`test_pedido_bloqueado_tambien_recibe_recordatorio` · `test_cambiar_la_entrega_genera_recordatorio_para_la_fecha_nueva` ·
`test_recordatorio_no_se_duplica_si_el_scheduler_corre_dos_veces` · `test_pedido_con_entrega_pasada_y_abierto_figura_vencido` ·
`test_el_recordatorio_esta_programado_en_el_scheduler`.

**E2E (real):** `pedidos.spec.ts › cargo un pedido y le llega el aviso al propietario` [cel]: Laura
crea un pedido para Carla; en la sesión de Carla la campana sube sin recargar, el pedido está en Mis
tareas y el mail de asignación aparece en el log de mail de la suite.

**Con el cliente (QC, durante los días en paralelo):** al final de cada día QC saca de la base del
entorno de prueba la lista de pedidos **abiertos cuya entrega fue ese día o antes** y, para cada
uno, busca en `avisos` el de asignación (y, si correspondía, el de "vence mañana") del propietario
con fecha anterior a la entrega, y en el log de la cola el mail. **Objetivo: 100 %.** Cada pedido
que falle es un defecto **crítico** (rompe un criterio firmado) con su test que lo reproduce. Revisa
aparte los pedidos que estuvieron "en el sector" sin persona y los de personas que se desactivaron
(DU-P09, DU-Q02). Además verifica en el entorno que el scheduler y el worker de mail estuvieron arriba todo
el período (HU-060): si se cayeron, el resultado no cuenta y se repite.

### 9.7 Uso en paralelo con la minuta (la prueba del cliente)

- **Antes:** el entorno del cliente con la semana 2 encendida, worker de mail y scheduler en
  Supervisord, subida ≥ 21 MB (B-04), las obras y usuarios reales que cargaron en la semana 1 con su
  "depende de" completo (sin eso no hay "mi gente" ni aviso al superior). QC verifica con un pedido
  de prueba ficticio que llegan el aviso, el mail y, al día siguiente, el recordatorio; después lo
  borra de la vista del cliente completándolo con "prueba".
- **Durante (unos días):** cada pedido nuevo de la minuta se carga también en el sistema. QC
  compara **cada día**: filas nuevas de la minuta vs. pedidos nuevos del sistema (por obra y tarea);
  estado de cada uno en los dos lados; los que no se cargaron en el sistema se anotan con el porqué
  (le faltó un campo, no tenía acceso, le costó) como **hallazgo**, sin agregar funciones fuera del
  documento.
- **Mails que no tienen que llegar:** QC pregunta a 3 personas (la más cargada, un solicitante
  frecuente y una jefatura) si recibieron algún mail que no fuera de asignación o de lo que
  activaron; se verifica en el log de la cola.
- **Después:** informe de QC de la entrega 2 (`../qc/entrega-2.md`) con el criterio n.º 3 medido,
  la comparación minuta ↔ sistema y los hallazgos.

### 9.8 Entrada y salida de la semana 2

Además de lo general de §6:

- **Entrada:** la matriz tiene los tests previstos de las 27 F-XX de la semana (✔, 16-nov); las
  historias "sí o sí" del backlog §6.1 sin dudas abiertas que cambien sus criterios (DU-P01, DU-P02,
  DU-P05, DU-P08, DU-P19, DU-P21 y DU-Q01 son las que tocan a esas historias: pedírselas a Juani primero); spec
  `pedidos.md` y `avisos.md` publicadas.
- **Salida:** `CriterioGlobalTresTest` en verde; E2E real de crear → avisar → tomar → completar con
  foto en PC y celular; ningún test de pedidos con `markTestSkipped` sin DU; `seguridad.md` con la
  revisión de Pedidos (alcance propios, IDOR de pedidos, avisos y adjuntos de pedido y equipo);
  revisión del Tech Lead con TL-03/07/08 cerrados (HU-059).

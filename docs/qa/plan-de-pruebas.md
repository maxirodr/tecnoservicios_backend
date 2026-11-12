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
| **E2E** | Playwright (front) contra el backend real sobre `tecnoservicios_testing` | **Camino feliz por feature** tal como lo hace una persona del sector, más el denegado visible más importante (botón que no aparece). Proyectos **PC** (1366×768, Chromium) y **celular** (390×844, emulación de teléfono) para las pantallas de celular (W-16, W-41…W-44, W-47) y un recorrido responsive por entrega. | Uno por feature (ver matriz). |

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
- El E2E levanta el backend con `APP_ENV=testing` sobre `tecnoservicios_testing` y lo resetea con un
  comando de semillas E2E que **se niega a correr** si la base no es la de tests.
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
- Los 10 sectores de arranque; **Pañol y Logística sin usuarios**.
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

### 4.3 Pedidos (semana 2)
- Estados en la proporción de la minuta: casi todos Completada, algunos No iniciada, En curso y
  **Bloqueada siempre con motivo** ("esperando presupuesto", "reprogramar fecha").
- Pedidos de la carga inicial **sin fecha de pedido** (solo 18 de 39 solapas la tienen), **sin
  solicitante** (falta en el 78 %), **sin prioridad** (101 de 755) y un par **sin estado**.
- Prioridad elegida a mano que **no coincide** con la calculada (pasa en ~4 de cada 10): urgente con 0
  días, prioritario con 1 a 7, normal con 8 o más, y casos frontera exactos (1, 7 y 8 días).
- **Pedido sin obra** (Servicios generales: vehículo, pañol, curso) y **pedido de varias obras** de un
  mismo cliente cargado sin obra nombrando las obras en la tarea.
- Pedido con **período inicio–fin** (alojamiento de 7 personas, alquiler de equipo).
- Pedido con **varios links** (Drive, Maps, página de un producto).
- "Con quién / para quién" que **no es proveedor**: el cliente, un área interna, la propia empresa.
- Pedido completado y **reabierto** ("falta tal cosa acá").
- Pedido con entrega **mañana** abierto (recordatorio) y otro igual pero completado (sin recordatorio).
- Pedido de la obra administración → operaciones ("Planilla de cierre y control de obras").
- **Equipos en reparación** con los 5 estados (incluido "Para retiro") y las 3 aprobaciones.

### 4.4 Gastos y rendición (semana 3)
- **Factura repartida en varias obras**: una factura de ferretería con ~30 renglones a 3 obras, 4 rubros
  y renglones **sin obra** (stock, vehículos, oficina).
- **Renglón dividido en dos obras** (10 bolsas: 6 a una, 4 a otra) → dos renglones.
- Proporción de **renglones sin documento entre 34 % y 78 %** por obra.
- **Combustible por carga** (sin documento, con "rendido por" y **sin** el nombre del chofer en el
  concepto) y la factura posterior del mismo proveedor que **no** se imputa.
- **Consumo de stock propio** como renglón sin documento (hoy "ARCHIVO") y la **compra a stock sin obra**:
  la suma de la obra no la cuenta dos veces.
- **Retiros de la empresa hermana por remito**, varios retiros por fecha del mismo material.
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

## 5. Seguridad (en cada entrega que toca permisos o datos)

- Permitido / denegado por cada celda relevante de la tabla; un cambio de celda rige sin volver a entrar.
- IDOR: ids de obras, pedidos, gastos, usuarios y avisos ajenos → 403 (avisos de otro usuario: 404).
- Asignación masiva: campos que el usuario no debe tocar (`estado` en el alta de obra, `sector_id` y
  `jefe_id` en el propio perfil, `cargado_por`, `solicitante_id`) se ignoran o se rechazan.
- Sesión: inactivo no entra; cambiar la contraseña o desactivar cierra sesiones; CSRF de Sanctum activo;
  rate limit en ingreso y recuperación (429 con sobre JSON).
- Datos sensibles: monto y ganancia fuera del JSON para quien no los ve; el buscador no devuelve grupos
  de módulos sin acceso; los mails no exponen datos de otras obras.
- Datos personales: el concepto de combustible no lleva el nombre del chofer (va en "rendido por").

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
| Semana 2 · Pedidos | Lo usan en paralelo con la minuta unos días. | Que ningún pedido cargado en la minuta en esos días falte en el sistema; avisos que llegan y mails que **no** llegan. |
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

# Especificación funcional · Gastos y facturas

> Módulo `app/Modules/Gastos` · Entrega: **semana 3** · Rol: Analista funcional · 2026-11-24
> Contrato: documento firmado v1.1 (cap. 2 "No incluye", cap. 3, cap. 4.3, cap. 8 F-39…F-47, cap. 9 "Cómo se calcula",
> cap. 10 F-57, cap. 13 criterios de aceptación n.º 1 y 2) + maquetas **W-10, W-14 ("Gastado por obra"), W-33, W-34,
> W-35, W-38 (alta rápida), W-46**, y W-11, W-13, W-18, W-36, W-37, W-48 para ver cómo se usa lo cargado.
> Material real: análisis de las seis rendiciones (`referencias/2026-10-09-rendiciones`) y de las planillas de junio
> (`referencias/2026-06-18-planillas-cliente`): libro "Gasto de obra" con `Fecha · Tipo de documento · Número ·
> Proveedor · Concepto · Tipo (rubro) · Cantidad · Precio unitario · Total`, una fila por ítem.
> Decisiones: D-006, D-022, D-033, D-035, D-036, D-039, D-040; definiciones C8…C15, C17, C18. ADR-003 (contratos),
> ADR-005 (polling), ADR-006 (matriz y alcance), **ADR-007** (los renglones son la única fuente del gastado), **ADR-008**
> (importes), ADR-010 (historial), ADR-011 (idempotencia), **ADR-014** (archivo o link).
> Dudas de esta semana ya resueltas el 12-nov (va la propuesta): **DU-08** (por rubro o solo total, por presupuesto:
> lo usa Rendición), **DU-17** (retiros: una sola carga para la rendición y el control por cantidad), **DU-18**
> (horas y fijos con período desde–hasta), **DU-19** (monto de la obra en W-13: no toca Gastos). También DU-A04
> (Ingeniería ve solo sus obras), DU-A15 (obra Terminada admite gastos), DU-A18 (proveedor genérico), DU-23 / ADR-014.
> Lo nuevo de esta especificación está en `docs/dudas.md` como **DU-G01…DU-G11**. **24-nov-2026: Juani las resolvió
> todas (va la propuesta)**, con los reemplazos del cruce con `rendicion.md`: **DU-G08 queda reemplazada por DU-R05**
> (permisos de la tabla aprobada, §8) y en notas de crédito va **DU-G05** (DU-R03 reemplazada). Las reglas que
> dependían de ellas son definitivas y citan "resuelta DU-GNN" o "resuelta DU-RNN".
> La **rendición** (W-11, W-12, W-36, W-37, W-44, W-48, F-48…F-54, F-66) está en [`rendicion.md`](rendicion.md); acá
> está **qué le garantiza Gastos** (§12.5). El contrato es uno solo, `Gastos\Contracts\GastadoDeObras`, con la firma de
> `rendicion.md` §12.4.1; Rendición ofrece `MaterialesDeObra` y `CatalogoDeRubros`. Lo que se cambió para que las dos
> especificaciones digan lo mismo está en §15.
> Personas, obras, proveedores, números e importes de este archivo son **ficticios** (los de las maquetas).

## 1. Propósito

"Cada factura o gasto se carga una sola vez, y cada renglón va a su obra y su rubro. En el mismo momento se resta en
la rendición de cada obra" (cap. 8). Hoy la misma factura se carga "en facturas, después en una rendición y después en
una obra" (Bruno, 16-oct); con el sistema se carga **una vez**, renglón por renglón, como su libro de gastos, y la
rendición, el % gastado de la obra, el control por cantidad y lo gastado por proveedor salen solos de esos renglones
(ADR-007). También entran los gastos que hoy rinden sin factura (tickets, combustible, consumo de stock, amortización,
retiros con remito, devoluciones, los montos de horas y fijos y el **parte de horas**, §5.13). **No** hay
vencimientos, condición de pago ni cuenta corriente de proveedores: siguen en Tango (cap. 8, D-033, D-040). Lo único
de pagos es la marca **paga / impaga** de cada factura, para quien paga (§5.14, DU-C03). El sistema no modela
aprobaciones: la factura llega ya aprobada (cap. 4.3).

## 2. Funcionalidades y pantallas cubiertas

| F-XX | Qué | Pantalla | Semana 3 |
|---|---|---|---|
| F-39 | Cargar una factura o gasto: tipo de documento, número, fecha, proveedor de la base y renglones con concepto, cantidad, precio unitario y total | W-10 | completa |
| F-40 | Repartir por renglón: cada renglón a una obra y un rubro; "sin obra" para stock, vehículos u oficina; un renglón a dos obras se divide | W-10 | completa |
| F-41 | Total = cantidad × precio, calculado solo; bonificación en el precio, moneda extranjera convertida, adelanto y saldo en dos renglones, sin IVA, cotización en el detalle | W-10 | completa |
| F-42 | Elegir el proveedor de la base, que trae sus datos; alta rápida si no existe | W-10 → W-38 | completa (el endpoint de alta ya existe, `proveedores.md` RN-PRV-10) |
| F-43 | La factura como **archivo o link** (opcional); el papel queda en la oficina | W-10 · W-34 | completa (ADR-014) |
| F-44 | Gastos sin comprobante: sin documento, proveedor genérico, "rendido por" | W-35 | completa |
| F-47 | Otros gastos sin factura: combustible por carga, consumo de stock, amortización (uno por obra), retiros con remito (archivo o link), devoluciones que restan | W-35 · W-46 | completa (W-46 se abre desde la rendición, con el permiso de §8) |
| F-45 | Gastos cargados: listado con filtros por obra, proveedor, rubro, fecha y tipo, con el total de lo filtrado | W-33 | completa |
| F-46 | Ver, corregir o anular un gasto, con quién lo cargó y cuándo | W-34 | completa |
| F-57 | Gastos del proveedor: lo imputado por obra, en su ficha | W-14 "Gastado por obra" | completa (Gastos implementa el puerto `GastadoPorProveedor`, `proveedores.md` §10.3) |
| F-54 (dato) | HORAS LIQUIDADAS y FIJOS como monto total por período (DU-18) | W-46, sección "Horas liquidadas y fijos por período" (resuelta DU-R04) | el registro, sus reglas, la sección y su endpoint (§5.5) |
| F-39 · F-54 (agregado DU-C02) | **Parte de horas**: las horas de cada empleado, una vez, y cada renglón va a su obra en HORAS LIQUIDADAS | W-10, tercer tipo de documento | §5.13 (pedido de Bruno, 26-nov) |
| F-45 · F-46 (agregado DU-C03) | **Facturas pagas o impagas**: marcar y desmarcar, filtro Pago y total de lo filtrado | W-33 · W-34 · W-18 | §5.14 (pedido de Bruno, 26-nov) |
| F-03 (gastos) | Buscador general: grupo **Gastos** por número de factura | W-18 | se suma el grupo |
| F-14 (gastos) | Quién hizo qué y cuándo en cada gasto | W-34 | completa |
| F-15 (aporte) | "% del presupuesto gastado" del listado de obras | W-05 | Gastos aporta el gastado (§12.5); el puerto `AvanceGastoDeObra` lo implementa Rendición |
| F-17 (aporte) | Pestaña Rendición de la ficha | W-07 · W-11 | la arma `rendicion.md`; Gastos aporta los renglones y "Últimos gastos imputados" (§12.5) |
| F-02 / F-13 (gastos) | Ítem **Gastos** de la barra (y "Gastos" en "Más" del celular) | W-17 · W-41 | se enciende (DU-A12) |

Se enciende en la barra el ítem **Gastos** (lista de módulos habilitados de DU-A12, `identidad.md` §6.4): abre
Gastos cargados (W-33), con la marca "(ver)" si solo tiene Ver (RN-IDE-12).

## 3. Glosario (palabras del cliente → código)

| Cliente | Código | Qué es |
|---|---|---|
| Gasto · comprobante | `Gasto` / `gastos` | Una carga: una factura, una rendición de tickets, un retiro con remito, una amortización, una devolución o un monto por período. Es la cabecera. |
| Factura | `tipo_documento = factura` | El comprobante del proveedor, con número. |
| Sin documento | `tipo_documento = sin_documento` | Todo lo que no es factura (su libro tiene el tipo de documento vacío). |
| Renglón · ítem | `GastoRenglon` / `gasto_renglones` | Una fila del libro de gastos: concepto, cantidad, precio unitario, total, obra y rubro. **Lo único que suma** (ADR-007). |
| Imputar | — | Mandar un renglón a una obra y un rubro: resta en su rendición. |
| Sin obra | `obra_id = null` | Renglón que no va a ninguna obra (stock, vehículos, oficina). No suma en ninguna rendición. |
| Rubro | `rubros` (clave) | Lista cerrada de su rendición (13, con EPP). |
| Proveedor genérico | `es_generico` | "Gastos sin comprobante" (DU-A18): el proveedor de lo que no tiene uno. |
| Rendido por | `rendido_por` | Quién rindió los gastos sin comprobante (puede no ser usuario: un supervisor de obra). |
| Qué se carga | `origen` | De qué carga vino: `factura`, `sin_comprobante`, `combustible`, `consumo_stock`, `amortizacion`, `retiro_remito`, `devolucion`, `monto_periodo`, `parte_horas` (DU-C02), `saldo_inicial` (semana 4). No se edita. |
| Parte de horas · parte diario | `origen = parte_horas` | Las horas de un período, un renglón por persona y obra, con su valor hora; todo en HORAS LIQUIDADAS (DU-C02, §5.13). |
| Empleado | `gasto_renglones.empleado` | La persona de un renglón del parte de horas (texto: no es un usuario del sistema). |
| Paga · impaga | `gastos.pagado_at`, `pagado_por` | Si la factura ya se pagó y cuándo (DU-C03, §5.14). Solo facturas; no toca la rendición. |
| Retiro · remito | `origen = retiro_remito` | Material retirado (hoy, de Tecnobahía) con su remito; la factura queda en Tango (C14). |
| Devolución | `origen = devolucion` | Material devuelto: renglón con cantidad y total negativos, resta (C15). También es cómo se carga una nota de crédito por material devuelto (resuelta DU-G05): **no hay tipo "nota de crédito"**. |
| Amortización | `origen = amortizacion` | Amortización de herramientas: un renglón por obra con el monto a mano. |
| Monto por período | `origen = monto_periodo` | HORAS LIQUIDADAS o FIJOS: un monto total por obra y período desde–hasta (C18, DU-18). |
| Material de la lista | `material_id` → `materiales` (Rendición) | Material presupuestado de la obra (W-48, DU-17); un renglón asociado cuenta como retirado o pedido. |
| Fuera de presupuesto | `material_id` → material con `fuera_de_presupuesto = true` | Lo retirado o pedido que no está en la lista (W-48, DU-17): se escribe con su unidad al cargar y Rendición lo crea en la obra (`MaterialesDeObra::crearFueraDePresupuesto`, resuelta DU-G03). |
| Anular | `anulado_at` | Dejarlo sin efecto: queda tachado y deja de sumar. No se borra (W-34). |

## 4. Datos

### 4.1 Gasto (`gastos`) — la cabecera

| Campo | Tipo | Oblig. | Validación | Fuente |
|---|---|---|---|---|
| tipo_documento | `factura` · `sin_documento` | **Sí** | lo fija la carga (§4.3); no se cambia al corregir (RN-GAS-41) | F-39 · W-10 "Factura / Sin documento" · sus rendiciones (solo esos dos valores) |
| origen | ver §4.3 | **Sí** | lo fija la carga; no se edita | modelo-datos · W-33 ("LOGISTICA · combustible") |
| numero | texto ≤ 40 | factura **Sí** · retiro **Sí** · resto no | sin espacios al borde; normalizado (RN-GAS-07) | F-39 · W-10 "Número" · modelo-datos |
| fecha | fecha | **Sí** | no posterior a hoy (Argentina); en `monto_periodo` es el "hasta" del período y puede ser posterior a hoy (los fijos del mes en curso, RN-GAS-27) | F-39 · W-10 · W-35 "Fecha de la rendición" · resuelta DU-R04 |
| proveedor_id | ref. proveedor | **Sí** (el genérico si no hay otro, §4.3) | existe; el genérico **no** en una factura (RN-GAS-06) | F-42 · F-44 · RN-PRV-14 |
| rendido_por | texto ≤ 120 | según §4.3 | sin espacios al borde | F-44 · W-35 "Laura ▾ (o texto libre)" · C12 |
| periodo_desde, periodo_hasta | fecha · fecha | `monto_periodo` **Sí** | hasta ≥ desde | F-54 · DU-18 |
| adjuntos | 0 a 10, contexto `comprobante_gasto` (o 0 a 1 `remito` en un retiro), cada uno archivo **o** link | No | `obras.md` §4.8 | F-43 · F-44 · F-47 · ADR-014 |
| cargado_por, created_at | ref. usuario · fecha y hora | automático | quien carga | F-46 · W-34 "Cargó: Diego · 17-oct 10:42" |
| updated_by, updated_at | ref. usuario · fecha y hora | automático | última corrección; `updated_at` es la versión (409, RN-GAS-39) | W-34 "Última edición: Diego · 17-oct 11:05" (ver §4.6) |
| anulado_at, anulado_por, motivo_anulacion | fecha y hora · ref. usuario · texto ≤ 2000 | al anular | motivo no vacío | W-34 "Anular" · modelo-datos |
| pagado_at, pagado_por | fecha · ref. usuario | No (solo facturas; vacío = **impaga**) | fecha ≤ hoy (Argentina); solo `pagos = editar` (RN-GAS-59) | DU-C03 |

El **total** del gasto no se guarda ni se tipea: es la suma de sus renglones (RN-GAS-11).

### 4.2 Renglón (`gasto_renglones`)

| Campo | Tipo | Oblig. | Validación | Fuente |
|---|---|---|---|---|
| orden | entero | automático | el orden en que se cargaron | modelo-datos |
| fecha | fecha | **Sí** | en una factura, siempre la de la factura; en un gasto sin comprobante, la del ticket (≤ hoy); en un monto por período, el "hasta"; en el resto, la de la cabecera. Es la **fecha efectiva** que usa la rendición (`rendicion.md` §4.3) | W-35 columna "Fecha" · su libro (la fecha va por fila) · **resuelta DU-G01** y DU-R04 |
| concepto | texto ≤ 250 | **Sí** | sin espacios al borde; no vacío | F-39 · W-10 "Concepto" |
| cantidad | decimal, 3 decimales | **Sí** | ≠ 0; negativa **solo** en devolución (y ahí obligatoria negativa) | F-39 · ADR-008 · C15 |
| precio_unitario | decimal, 2 decimales | **Sí** | ≥ 0 | F-39 · F-41 · ADR-008 |
| total | decimal, 2 decimales | automático | `cantidad × precio_unitario`, redondeado (RN-GAS-10) | F-41 "calculado solo" |
| obra_id | ref. obra · `null` = sin obra | **Sí elegir** (una obra **o** "(sin obra)") | la obra existe, en cualquier estado; con alcance "solo sus obras", una suya | F-40 · W-10 "(sin obra)" · DU-A15 |
| rubro_id | ref. rubro | **Sí** si hay obra; opcional sin obra | uno de los permitidos para esa carga (§4.3) | F-40 · cap. 9 · modelo-datos (CHECK) |
| material_id | ref. material de la obra | No (en un retiro, **sí**: de la lista o fuera de presupuesto) | un material de **esa** obra (`MaterialesDeObra::perteneceAObra`); la cantidad va en la unidad del material | DU-17 · W-46 · W-48 · resuelta DU-G03, DU-R06 |
| detalle | texto ≤ 250 | No | — | F-41 "la cotización en el detalle" · modelo-datos |
| empleado | texto ≤ 120 | parte de horas **Sí** · resto no | sin espacios al borde ni dobles; no vacío. Lo ven solo quienes tienen `gastos = editar` (RN-GAS-57) | DU-C02 |

### 4.3 Qué pide cada carga (`origen`)

| Carga | Pantalla | `tipo_documento` / `origen` | Número | Proveedor | Rendido por | Obra del renglón | Rubros que acepta | Cantidad | Material | Adjuntos |
|---|---|---|---|---|---|---|---|---|---|---|
| Factura | W-10 | `factura` / `factura` | **Sí** | **de la base, no el genérico** | — | obra o "(sin obra)" | los 11 que se imputan (RN-GAS-12) | > 0 | opcional (DU-17) | 0 a 10 `comprobante_gasto` |
| Gastos de obra sin ticket | W-35 | `sin_documento` / `sin_comprobante` | No | el genérico o uno de la base (resuelta DU-G02) | **Sí** (resuelta DU-G02) | obra o "(sin obra)" | los 11 | > 0 | — | 0 a 10 `comprobante_gasto` (tickets, fotos) |
| Combustible por carga | W-35 | `sin_documento` / `combustible` | No | ídem | No (el chofer, si se quiere: DU-G02) | obra o "(sin obra)" | los 11 (LOGISTICA por defecto) | > 0 | — | ídem |
| Consumo de stock propio | W-35 | `sin_documento` / `consumo_stock` | No | ídem | No | **obra** (el consumo es de una obra, C10) | los 11 | > 0 | — | ídem |
| Amortización de herramientas | W-46 | `sin_documento` / `amortizacion` | No | el genérico | — | la obra de la pantalla | **AMORTIZACION** | 1 (el importe va en el precio) | — | — |
| Retiro con remito | W-46 | `sin_documento` / `retiro_remito` | **Sí** (N.º de remito) | **de la base** (resuelta DU-G03) | — | la obra de la pantalla | los 11 (MATERIALES por defecto) | > 0 | **Sí**: de la lista, o "No está en la lista" (nombre y unidad) = fuera de presupuesto | 0 a 1 `remito` |
| Devolución de materiales | W-46 | `sin_documento` / `devolucion` | No | de la base, opcional (si no, el genérico) | — | la obra de la pantalla | los 11 (MATERIALES por defecto) | **< 0** | de la lista o fuera de presupuesto, opcional | — |
| Monto por período | W-46, sección "Horas liquidadas y fijos por período" (resuelta DU-R04) | `sin_documento` / `monto_periodo` | No | el genérico | — | la obra de la pantalla (**un** renglón) | **FIJOS** o **HORAS LIQUIDADAS** | 1 (el monto va en el precio) | — | — |
| Carga inicial · gastado | Importación (semana 4, `importacion.md` RN-IMP-23) | `sin_documento` / `saldo_inicial` | No | el genérico | — | obra | cualquiera de los 13 | 1 (precio = el importe) | — | — |
| Carga inicial · retirado | Importación (semana 4, `importacion.md` RN-IMP-29, DU-R09) | `sin_documento` / `saldo_inicial` | No | el genérico | — | obra | MATERIALES | = lo retirado (precio **0**, total 0) | **Sí**, de la lista | — |
| Parte de horas | W-10, tipo "Parte de horas" (DU-C02) | `sin_documento` / `parte_horas` | No | el genérico | — | obra o "(sin obra)", una por renglón | **HORAS LIQUIDADAS**, siempre | **las horas**, > 0, hasta 2 decimales | — | 0 a 1 `comprobante_gasto` (la planilla) |

### 4.4 Adjuntos (ADR-014, `identidad.md` §6.6)

| Contexto | Dueño | Cantidad | Ver / bajar | Asociar / quitar |
|---|---|---|---|---|
| `comprobante_gasto` | gasto (factura o sin comprobante) | 0 a 10 | quien puede ver el gasto (`gastos` ≥ ver, con alcance) | `gastos = editar` sobre ese gasto |
| `remito` | gasto con `origen = retiro_remito` | 0 a 1 | `gastos` ≥ ver **o** `control_cantidad` ≥ ver, con alcance | `gastos = editar` (W-46, W-34) **o** `control_cantidad = editar` (W-48), con alcance (`identidad.md` §6.6) |

W-10 dice "Link a la factura (opcional) · Pegar link de Drive, si la escanean · el papel queda en la oficina": con
ADR-014 el campo es **"Factura (opcional) · Subir archivo o pegar link"**. Un gasto **anulado conserva** sus adjuntos
(RN-IDE-48). Alias de `Relation::morphMap` (en `GastosServiceProvider`): `gasto`. Las implementaciones de
`AutorizaAdjuntos` (`GastoAdjuntos`) se registran por contexto en `ContextosAdjuntos`.

### 4.5 Importes, cantidades y redondeo (F-41, ADR-008, C9)

- Pesos argentinos, **netos sin IVA** (F-41, C9). No hay campo de IVA, de moneda ni de bonificación: la bonificación va
  ya aplicada al precio; la compra en moneda extranjera se carga convertida a pesos y la cotización se escribe en el
  **detalle** del renglón ("USD 1.200 a 1.050 = $ 1.260.000"). Qué es "neto" (resuelta DU-G04): en un comprobante que
  discrimina IVA se carga el **neto** (sin IVA ni percepciones); en uno que no lo discrimina (factura C, ticket,
  rendición sin comprobante) se carga **lo pagado**. Se confirma con María (administración) en la prueba de la semana 3.
- Precio unitario con **hasta 2 decimales**; cantidad con **hasta 3** (m², kg, litros). Más decimales: 422 (no se
  redondea en silencio; el formulario avisa antes de guardar).
- **Total del renglón = cantidad × precio unitario, redondeado a 2 decimales, mitad para arriba en valor absoluto**,
  como REDONDEAR de Excel (0,005 → 0,01; en negativos, −0,005 → −0,01). Es la misma regla que `rendicion.md` §4.3 (la
  rendición no vuelve a redondear). Lo calcula el backend (`Importe`, bcmath); el front lo muestra como vista previa.
- **Total del gasto = suma de los totales de sus renglones**, sin volver a redondear. Total por obra = suma de sus
  renglones.
- Topes: precio ≤ 9.999.999.999.999,99; cantidad ≤ 99.999.999.999,999; si el total no entra en 15 dígitos: 422 "El
  total del renglón es demasiado grande." Hasta **200 renglones** por gasto (técnica: una factura de ferretería trae ~30).
- En la API los importes y cantidades viajan como **string decimal** ("9800.00", "30.000"); en pantalla, formato es-AR
  con separador de miles y sin ",00" ("905.600", "9.800,50", "−39.200").

### 4.6 Ajustes al modelo de datos que pide esta especificación (para el DBA; no se cambian acá)

Aplicados en `modelo-datos.md` el 24-nov-2026.

1. `gasto_renglones.fecha` (date, no nula; en facturas = `gastos.fecha`; en montos por período = `periodo_hasta`) —
   resuelta DU-G01 y DU-R04. Índice `(obra_id, fecha)` para W-36, W-37 y W-48.
2. `gastos`: `timestamps` y `updated_by` (W-34 "Última edición: Diego · 17-oct 11:05"); el modelo no los lista.
   `gasto_renglones`: `timestamps`.
3. `gastos.observaciones` no aparece en W-10, W-35 ni W-46: queda sin usar en la Etapa 1 (no se expone).
4. El único anti-duplicado `numero_vigente` se arma sobre el **número normalizado** (RN-GAS-07) y solo para
   `tipo_documento = factura` no anuladas, como dice el modelo (resuelta DU-G10).
5. Los renglones no tienen **unidad**: la unidad es la del material (`materiales.unidad`), también en lo de fuera de
   presupuesto, que se crea con su unidad al cargar (resuelta DU-G03 con el contrato `MaterialesDeObra`).
6. `cantidad` negativa **solo** con `origen = devolucion` (resuelta DU-G05: no hay nota de crédito).
7. (DU-C02, 27-nov) `gasto_renglones.empleado` (varchar 120, null): la persona de cada renglón del parte de horas;
   `origen = parte_horas`. Sin tabla de empleados: es texto, como "rendido por".
8. (DU-C03, 27-nov) `gastos.pagado_at` (date, null) y `gastos.pagado_por` (FK users, null): factura paga y quién la
   marcó; vacío = impaga. Índice `(tipo_documento, pagado_at)` para el filtro Pago de W-33.

## 5. Reglas de negocio

### 5.1 Entrada al módulo (F-02, F-45; W-17, W-33)

- **RN-GAS-01** El ítem **Gastos** de la barra aparece con `gastos` ≥ Ver y abre **Gastos cargados** (W-33); con solo Ver, "Gastos (ver)" y sin botones de carga ni edición. Con `gastos = editar`, W-33 muestra **"+ Cargar factura / gasto"**, que abre W-10. En el celular, "Gastos" está en "Más" (W-41). *(F-02, W-17, W-33, RN-IDE-11, RN-IDE-12, DU-A12)*
- **RN-GAS-02** La carga es **una sola pantalla con dos modos**: arriba, **Tipo de documento: Factura · Sin documento**. "Factura" es W-10; "Sin documento" es W-35 (tickets, combustible, consumo de stock). Al cambiar de modo se conservan los renglones ya escritos (concepto, cantidad, precio, obra, rubro); se vacían número y proveedor. Amortización, retiros, devoluciones, horas y fijos se cargan desde la rendición de la obra (W-46, §5.4 y §5.5), con el permiso de §8.1. El tercer tipo, **"Parte de horas"**, es el de §5.13 (DU-C02). *(W-10 "Sin documento para lo que no tiene factura: tickets, combustible por carga y consumo de stock propio", W-35, W-11 "+ cargar renglón sin documento (W-46)")*

### 5.2 Cargar una factura (F-39…F-43; W-10, W-38)

- **RN-GAS-03** Cabecera de la factura: **Número**, **Fecha** y **Proveedor**, obligatorios; **Factura (opcional)**: archivo o link (0 a 10). Mensajes: "Poné el número de la factura.", "Poné la fecha.", "Elegí el proveedor." Abajo, "El papel queda en la oficina, como hoy." *(F-39, F-43, W-10, cap. 4.3, ADR-014)*
- **RN-GAS-04** **Fecha**: la de la factura, por defecto hoy (zona `config('app.zona_empresa')`); no puede ser posterior a hoy ("La fecha no puede ser posterior a hoy."); puede ser de cualquier día anterior (las facturas llegan después, también con la obra Terminada: DU-A15). *(F-39; técnica: una fecha futura es un error de tipeo)*
- **RN-GAS-05** **Proveedor**: se elige de la base con buscador (`GET /api/proveedores/opciones`, nombre o CUIT, RN-PRV-03) y **trae sus datos**: se ven su CUIT y su rubro habitual, y el rubro habitual se pone como rubro de los renglones que todavía no tienen rubro (cada renglón se puede cambiar). Si no existe, **"+ nuevo proveedor"** abre W-38 (alta rápida, con el aviso de parecidos); al guardar queda elegido y se vuelve a la factura sin perder nada. El link aparece solo con `proveedores = editar`. *(F-42, W-10, W-38, RN-PRV-08…10)*
- **RN-GAS-06** En una factura **no se puede elegir el proveedor genérico**: 422 "El proveedor genérico es solo para gastos sin documento." *(RN-PRV-14, F-44)*
- **RN-GAS-07** **Número**: se guarda sin espacios al borde y en mayúsculas. Si tiene la forma punto de venta–número (`3-12345`, `0003-00012345`, `00003 00012345`), se guarda **normalizado** con el punto de venta de al menos 4 dígitos y el número de 8: `0003-00012345` (con 5 dígitos de punto de venta queda `00003-00012345`). Si no tiene esa forma, se guarda tal cual. *(W-10 "0003-00012345"; resuelta DU-G10)*
- **RN-GAS-08** **Una factura no se carga dos veces**: no puede haber otra factura **no anulada** del **mismo proveedor** con el **mismo número normalizado**. Al salir del campo Número (o al elegir el proveedor) el formulario avisa: "Esa factura ya está cargada: 0003-00012345 de Corralón Centro (Diego, 17-oct)." con link a W-34; al guardar, 422 con el mismo texto y `errors.existente`. Lo garantiza el índice único (dos cargas a la vez: la segunda recibe el 422). El mismo número de **otro** proveedor se acepta. Una factura anulada no cuenta: se puede volver a cargar. *(criterio de aceptación n.º 2, modelo-datos `numero_vigente`; resuelta DU-G10)*
- **RN-GAS-09** **Renglones**: columnas **Concepto · Cantidad · Precio unitario · Total · Obra · Rubro** (como su libro de gastos) y "+ agregar renglón"; se quitan con "quitar". Al menos uno: "Cargá al menos un renglón." Cada renglón: concepto ("Escribí el concepto."), cantidad ("Poné la cantidad."; "La cantidad no puede ser 0."), precio ("Poné el precio unitario."; "El precio no puede ser negativo."), obra o "(sin obra)" ("Elegí la obra o '(sin obra)'.") y, si tiene obra, rubro ("Elegí el rubro."). Opcional: **detalle** (cotización, aclaraciones) y **material** (RN-GAS-14). Los errores se muestran en el renglón que corresponde ("Renglón 3: elegí el rubro."). *(F-39, F-40, W-10)*
- **RN-GAS-10** **Total del renglón** = cantidad × precio unitario, calculado solo, con el redondeo de §4.5; no se tipea. Una cantidad negativa en una factura: 422 "La cantidad va en negativo solo en una devolución." *(F-41, ADR-008, C15)*
- **RN-GAS-11** **Total de la factura** = suma de los renglones, y al lado **el reparto por obra**: "905.600 · Nave 3: 549.000 · Losa tanque B: 311.600 · sin obra: 45.000", en vivo mientras se carga. No hay un total para tipear aparte, así que **siempre suma**: la persona lo compara con el papel antes de guardar y, si no coincide, corrige los renglones (en general falta uno o hay un precio mal tipeado). Una diferencia de centavos por el redondeo del proveedor se acepta: la rendición se lleva en pesos. Si el papel da más, se puede sumar un renglón "Ajuste de redondeo"; no se cargan renglones negativos para bajar el total. Junto al botón: "Importes sin IVA (netos)". *(W-10 "Total de la factura (cantidad × precio, suma de renglones)", F-41)*
- **RN-GAS-12** **Rubros** que se ofrecen al imputar: los **11 de su lista para imputar gastos** (MATERIALES, INSUMOS, EPP, SUBCONTRATOS, LOGISTICA, AMORTIZACION, MANTENIMIENTO, OBRADORES/BAÑOS, SERVICIOS GENERALES, MAQUINAS/HERRAMIENTAS, VIATICOS). **FIJOS** y **HORAS LIQUIDADAS** no se ofrecen: se cargan como monto por período (§5.5) y HORAS LIQUIDADAS, también con el parte de horas (§5.13). Si llegan por la API: 422 "FIJOS y HORAS LIQUIDADAS se cargan como monto por período, desde la rendición." EPP se imputa como rubro propio; que en el resumen sume en INSUMOS lo hace la rendición. *(cap. 9 "Rubros", F-54, C17, C18; su columna auxiliar "Tipos" tiene esos 11)*
- **RN-GAS-13** **Obra del renglón**: cualquier obra existente, **en cualquier estado** (también Confirmada y Terminada); el selector muestra "N.º · nombre del trabajo" y "Obra terminada" al lado si lo está. **"(sin obra)"** deja `obra_id` vacío: lo que no es de ninguna obra (stock, vehículos, oficina) y la **compra a stock**, así no se cuenta dos veces con su consumo (RN-GAS-19). Sin obra el rubro es opcional. **Un renglón que va a dos obras se divide en dos renglones** (6 bolsas a una, 4 a la otra): no hay reparto por porcentaje. La factura **se carga completa**. *(F-40, D-035, C10, C11, W-10, DU-A15, RN-OBR-25, RN-OBR-34)*
- **RN-GAS-14** **Material** (control por cantidad): si la obra del renglón tiene materiales (W-48), el renglón puede asociarse a uno de **esa** obra ("Material: Cemento x 50 kg ▾", opcional; las opciones salen de `GET /api/obras/{obra}/materiales/opciones`, de Rendición) para que cuente como "pedido" en W-48; o elegir **"No está en la lista"** y escribir el material y su unidad, que queda como material **fuera de presupuesto** de la obra (`MaterialesDeObra::crearFueraDePresupuesto`, en la misma transacción; si ya existe con ese nombre en la obra, se usa ese). La cantidad va **en la unidad del material** (el formulario la muestra al lado: "bolsas"). Si el material no es de esa obra: 422 "El material Cemento x 50 kg es de otra obra: quitá el material o elegí uno de esta obra." (también al corregir la obra de un renglón que tiene material). Si la obra no tiene materiales, el campo ofrece solo "No está en la lista". En el formulario, cambiar la obra del renglón borra el material elegido. *(resuelta DU-17: "Un renglón de factura de otro proveedor puede asociarse a un material de la lista para contar como 'pedido'"; W-48 "lo que se compró fuera de presupuesto"; `rendicion.md` RN-REN-49, RN-REN-53 y §12.4.1 invariante 4; resuelta DU-R06)*
- **RN-GAS-15** Adelanto y saldo de un subcontrato van **en dos renglones** (o en sus dos facturas), como hoy; la moneda extranjera, **convertida**, con la cotización en el detalle; la bonificación, **aplicada al precio**. El sistema no calcula ninguna de las tres cosas. *(F-41, C9)*
- **RN-GAS-16** **Guardar factura**: en **una transacción** se guarda la cabecera, los renglones, los adjuntos asociados y la actividad `creado`; después del commit sale `GastoImputado` con las obras afectadas (invalida la rendición de cada una, ADR-007). Confirmación: "✓ Factura cargada. Ya se restó en la rendición de Piso industrial nave 3 y Reparación de losa tanque B." con **Ver la factura · Cargar otra** (este último vacía el formulario y genera otra `Idempotency-Key`). Si todos los renglones son sin obra: "✓ Factura cargada. No va a ninguna obra (todo sin obra)." *(W-10 "Al guardar, cada renglón resta en la rendición de su obra, en el rubro elegido.", cap. 8 "En el mismo momento se resta", ADR-011)*

### 5.3 Gastos sin comprobante (F-44, F-47; W-35)

- **RN-GAS-17** Modo **Sin documento** (W-35): **Qué se carga: Gastos de obra rendidos · Combustible por carga · Consumo de stock propio** (por defecto el primero; resuelta DU-G02), **Rendido por**, **Fecha de la rendición** (la cabecera, ≤ hoy), **Tickets / fotos** (archivo, foto con la cámara o link, 0 a 10, opcional) y los renglones con **Fecha · Concepto · Cantidad · Precio unitario · Total · Obra · Rubro** y "+ agregar renglón". Pie: "Total rendido · 113.500" y "Cada renglón va a su obra y su rubro y suma en la rendición, igual que una factura." *(F-44, W-35, ADR-014)*
- **RN-GAS-18** **Gastos de obra rendidos** (los personales de obra sin ticket): **como hoy, sin documento y con el proveedor genérico "Gastos sin comprobante"**, y con **rendido por** obligatorio ("Elegí o escribí quién rindió."): se elige una persona activa de la lista ("Laura ▾") **o se escribe** un nombre ("supervisor de obra Juan"); se guarda el nombre como texto. *(F-44, C12, W-35 "Laura ▾ (o texto libre: supervisor de obra)"; obligatorio: resuelta DU-G02)*
- **RN-GAS-19** **Combustible por carga**: un renglón por carga, a la obra, como hoy (rubro LOGISTICA por defecto). Si después llega la factura del proveedor del combustible, **no se carga**: queda en Tango. **Consumo de stock propio**: cada renglón va a una **obra** (es el consumo de esa obra, como hoy "ARCHIVO"; con "(sin obra)": 422 "El consumo de stock va a una obra."); la compra a stock se carga como factura **sin obra** (RN-GAS-13). El sistema no puede saber si la factura es la del combustible ya imputado: el formulario de factura no lo frena (lo dice el manual). *(F-47, C10, C13, W-35)*
- **RN-GAS-20** **Proveedor en sin documento**: por defecto el genérico; se puede elegir **uno de la base** (la estación de servicio del combustible, como hoy en su libro). En las listas, un gasto con el proveedor genérico muestra "—" en la columna Proveedor (W-33, W-11). *(resuelta DU-G02; W-33 y W-11 "Sin documento · —")*
- **RN-GAS-21** **Fecha de cada renglón**: la del ticket (≤ hoy), por defecto la de la rendición; es la fecha con la que el renglón entra en la rendición. Número: no se pide. *(W-35 columna "Fecha"; resuelta DU-G01)*

### 5.4 Renglones sin documento de la obra (F-47; W-46)

- **RN-GAS-22** W-46 **"{N.º} · {obra} · renglones sin documento"** se abre desde la rendición de la obra (W-11 "+ cargar renglón sin documento"; "+ cargar período" de las filas FIJOS y HORAS LIQUIDADAS abre directo su sección; W-48 "+ cargar retiro con remito" abre la de retiros) con la obra ya elegida (no se cambia). Cuatro secciones: **Amortización de herramientas**, **Retiros de Tecnobahía**, **Devolución de materiales** y **Horas liquidadas y fijos por período** (§5.5, resuelta DU-R04); cada una lista lo ya cargado de esa obra (no anulado) y permite cargar lo nuevo; cada carga es un gasto propio (§4.3) que se corrige o anula en W-34. Botón **Guardar** por sección. Quién carga cada sección: §8.1 (resuelta DU-R05). La obra puede estar en cualquier estado ("Obra terminada" arriba si lo está). *(W-46, W-11, W-48, DU-A15)*
- **RN-GAS-23** **Amortización de herramientas: un renglón por obra**, con el monto cargado a mano, como hoy: **Fecha · Concepto · Importe** (rubro AMORTIZACION, fijo). Concepto por defecto "Amortización de herramientas de la obra". Importe > 0 ("Poné el importe."). Si la obra ya tiene su amortización (no anulada), la sección la muestra ("Ya cargado: 310.000 · es el de AMORTIZACION en la rendición") con **Corregir** (abre W-34 en edición) y no deja cargar otra: 422 "Esta obra ya tiene su amortización (310.000): corregila." *(F-47 "un renglón por obra", W-46 "Un renglón por obra, con el monto cargado a mano, como hoy")*
- **RN-GAS-24** **Retiros con remito: un retiro = un remito.** Se carga **Fecha del retiro**, **N.º de remito** ("Poné el número de remito."), **Proveedor** de la base (obligatorio: "Elegí el proveedor."; se sugiere el último usado en un retiro), **Remito** (archivo, foto o link, opcional) y uno o más renglones **Material · Cantidad · Precio · Total · Rubro**. El **material** es obligatorio en cada renglón ("Elegí el material o 'No está en la lista'.") y se elige de la lista de la obra (W-48), con el precio presupuestado sugerido (se puede cambiar); **lo que no está en la lista se escribe con su unidad** ("Guantes y antiparras · par") y queda **"fuera de presupuesto"** (RN-GAS-14). La cantidad va en la unidad del material. Rubro por defecto MATERIALES. La factura del proveedor de los retiros queda en Tango. Un mismo N.º de remito del mismo proveedor ya cargado avisa sin frenar: "Ese remito ya está cargado (14-oct, Piso industrial nave 3). ¿Cargarlo igual?" *(F-47, C14, W-46, resuelta DU-17 "Cada retiro se carga una sola vez con su remito, eligiendo materiales de esa lista y sus cantidades: suma en 'retirado' de W-48 y resta en la rendición en su rubro. Lo que no está en la lista va a 'fuera de presupuesto'"; resuelta DU-G03 y DU-G10; W-48 "Fuera de presupuesto: Material · Unidad"; `rendicion.md` RN-REN-48, RN-REN-49)*
- **RN-GAS-25** **Devolución de materiales**: **Fecha · Material · Cantidad · Total · Rubro**, con la cantidad **en negativo** ("−4"; el formulario pone el signo); el precio sugerido es el presupuestado del material o el último precio de ese material en la obra; el total da negativo y **resta** en la rendición y en lo retirado de W-48. Material de la obra opcional (si no, se escribe el concepto: "Cemento x 50 kg (sobrante)"; sin material no toca W-48 y queda en "Renglones de MATERIALES sin material asociado", `rendicion.md` RN-REN-51). Proveedor opcional (el genérico si no se elige; el de la base si se le devuelve al proveedor). **No hay tipo "nota de crédito"**: una nota de crédito por material devuelto se carga como devolución; una que corrige el precio de una factura ya cargada se resuelve **corrigiendo la factura** en W-34 (queda en su historial); las demás quedan en Tango. Una cantidad positiva: 422 "En una devolución la cantidad va en negativo." *(F-47 "que las devoluciones de materiales resten", C15, cap. 9 "renglón negativo", W-46; resuelta DU-G03 y DU-G05, que reemplaza a DU-R03)*
- **RN-GAS-26** Al guardar cualquier sección: misma transacción, actividad y `GastoImputado` que la factura (RN-GAS-16); confirmación "✓ Retiro cargado. Ya suma en la rendición y en el control por cantidad." (o "✓ Amortización cargada." / "✓ Devolución cargada. Ya resta en la rendición." / "✓ Período cargado. Ya resta en la rendición."). *(W-46 "Todo suma (o resta) en la rendición de la obra, en su rubro")*

### 5.5 Montos por período: HORAS LIQUIDADAS y FIJOS (F-54 dato, DU-18)

Se cargan en la cuarta sección de W-46, **"Horas liquidadas y fijos por período"**, abierta desde la rendición
("+ cargar período" en las filas FIJOS y HORAS LIQUIDADAS de W-11, con la obra y el rubro elegidos; resuelta DU-R04).
Lo que la rendición hace con ellos está en [`rendicion.md`](rendicion.md) RN-REN-16…18; las reglas de la carga son
estas y `rendicion.md` las cita, no las repite.

- **RN-GAS-27** Un monto por período es un gasto `monto_periodo` de **una obra** (la de W-46) con **Rubro** (FIJOS / HORAS LIQUIDADAS), **Desde**, **Hasta**, **Importe** y **Concepto**: dos fechas, hasta ≥ desde ("El 'hasta' no puede ser anterior al 'desde'."; las dos: "Poné desde y hasta."), proveedor genérico y **un solo renglón**, con cantidad 1 y el **monto total** en el precio (> 0: "Poné el importe del período."), **sin detalle por empleado**. Concepto por defecto "Horas liquidadas 1 al 15-oct" / "Fijos 1 al 31-oct", editable. La **fecha del gasto y de su renglón es el "hasta"** (`periodo_hasta`), también si todavía no llegó (los fijos del mes en curso): es la fecha efectiva con la que la rendición ordena W-36 y filtra W-37; la rendición es acumulada, así que resta desde que se carga. El "desde" no puede ser posterior a hoy ("El período no puede empezar después de hoy."). Sirve para quincenas (horas, como hoy) y meses (fijos). *(F-54 "un monto total por período", C18, resuelta DU-18; resuelta DU-R04 y DU-G09)*
- **RN-GAS-28** Si para la misma obra y el mismo rubro ya hay un monto (no anulado) cuyo período **se superpone**, avisa sin frenar: "Ya hay HORAS LIQUIDADAS de Piso industrial nave 3 del 1 al 15-oct (1.300.000). ¿Cargar igual?"; se guarda solo si se confirma (`confirmar_superposicion: true`); si se cargó dos veces por error, se anula una (W-34). Es la misma regla que `rendicion.md` RN-REN-17. *(resuelta DU-G09 y DU-R04; D-006 "el sistema muestra, la persona decide")*

### 5.6 Gastos cargados (F-45; W-33)

- **RN-GAS-29** Columnas **Fecha · Documento · Proveedor · Obras · Rubros · Total · Cargó**. **Documento** según la carga, con **las mismas etiquetas que la rendición** (`rendicion.md` RN-REN-29, resuelta DU-R07): "Factura 0003-00012345" · "Sin documento" (gastos de obra rendidos y combustible) · "Consumo de stock" · "Amortización" · "Retiro · remito 0001-00000456" · "Devolución" · "Período 1 al 15-oct" (horas y fijos; el rubro dice cuál) · "Carga inicial". **Proveedor**: el nombre; "—" si es el genérico. **Obras**: las obras de sus renglones, cortas ("Piso nave 3 · Losa tanque B · sin obra"). **Rubros**: los de sus renglones, con "· combustible" si es combustible ("LOGISTICA · combustible"). **Cargó**: quien lo cargó. Tocando una fila se abre el detalle (W-34). *(F-45, W-33)*
- **RN-GAS-30** **Filtros componibles** (se suman entre sí): **Obra** (todas · "(sin obra)" · cada obra), **Proveedor** (todos · cada uno, el genérico como "Gastos sin comprobante (genérico)"), **Rubro** (todos · cada uno de los 13), **Fecha: desde → hasta** (inclusive, sobre la **fecha del comprobante**; por defecto, del 1 del mes en curso a hoy, como W-33 "1-oct → 19-oct") y **Tipo** (Factura y Sin documento · Factura · Sin documento). Cada filtro es una clase chica en `Gastos/Infrastructure/Consultas/` (`PorObra`, `SinObra`, `PorProveedor`, `PorRubro`, `FechaEntre`, `PorTipoDocumento`, `VisiblesPara`) sobre `GastosQuery`; el alcance de quien mira se aplica siempre antes (RN-GAS-48). Obra y Proveedor son selectores **con búsqueda** que leen `GET /api/gastos/filtros/obras` y `GET /api/gastos/filtros/proveedores` (§12.1): responden a quien tiene `gastos` ≥ ver, con su alcance, y no cortan en 20 (resuelta DU-G12; las opciones de carga siguen pidiendo poder cargar). *(F-45 "filtros por obra, proveedor, rubro, fecha y tipo de documento", W-33, arquitectura §3)*
- **RN-GAS-31** Un gasto aparece si **al menos uno de sus renglones** cumple los filtros de obra y rubro. Con filtro de obra o de rubro, la columna **Total** muestra **lo de los renglones que cumplen**, y debajo, en chico, el total del comprobante ("549.000 · de 905.600"), así "Total de lo filtrado" da lo mismo que su tabla dinámica filtrada. *(W-33 "El total de lo filtrado, como hoy lo sacan con tablas dinámicas"; resuelta DU-G06)*
- **RN-GAS-32** Pie: "**Total de lo filtrado · 6 comprobantes · 5.447.900**", sobre **todo** lo filtrado (no solo la página). Orden: fecha del comprobante, la más nueva primero; a igual fecha, el cargado más tarde primero. Paginado de a 50. *(W-33)*
- **RN-GAS-33** **Agrupar por: nada ▾ · proveedor · obra · rubro**, como sus tablas dinámicas: con un grupo elegido, la tabla se arma por grupos con su subtotal y cantidad de comprobantes, y un comprobante repartido aparece en cada grupo con **la parte que le toca** (la factura de Corralón Centro, en "obra": Piso nave 3 549.000, Losa tanque B 311.600, (sin obra) 45.000). Por rubro, **EPP es su propio grupo** (sumarlo a INSUMOS es del resumen de la rendición). Los grupos van del mayor subtotal al menor. *(W-33 "Agrupar por: nada ▾ (proveedor, obra o rubro, como sus tablas dinámicas)")*
- **RN-GAS-34** Los **anulados** se ven **tachados**, con la marca "anulada", y **no suman** en el total ni en los grupos; el pie lo dice: "6 comprobantes · 1 anulado (no suma)". *(W-34 "Anular la deja tachada y deja de sumar"; resuelta DU-G11)*
- **RN-GAS-35** Desde la rendición, "ver todos (W-33 filtrado por esta obra)" abre W-33 con **Obra** = esa obra y la fecha **sin límite** (toda la obra). *(W-11)*

### 5.7 Detalle, corregir y anular (F-46, F-14; W-34)

- **RN-GAS-36** **Detalle** (W-34): título "Factura 0003-00012345 · Corralón Centro" (o "Sin documento · rendido por Laura · 18-10-2026", "Retiro · remito 0001-00000456 · {proveedor}", "Amortización · Piso industrial nave 3", "Período 1 al 15-oct · HORAS LIQUIDADAS · Piso industrial nave 3"); datos **Tipo de documento · Número · Fecha · Proveedor** (con CUIT si tiene) · **Rendido por** · **Período** (si corresponde) · **Archivo** (cada adjunto, para bajar o abrir) · **Cargó: Diego · 17-oct 10:42** · **Última edición: Diego · 17-oct 11:05** (si se corrigió); los renglones **Concepto · Cantidad · Precio unitario · Total · Obra · Rubro** (más **Fecha** en sin documento, **Material** y **Detalle** si tienen) y "Total de la factura" con el reparto por obra; abajo, el **Historial**. Con `gastos = editar`: **Editar** y **Anular**. *(F-46, W-34)*
- **RN-GAS-37** **Corregir**: con `gastos = editar`, cualquier gasto no anulado, también de una obra Terminada. Se corrigen **todos** los datos de la cabecera (número, fecha, proveedor, rendido por, período, adjuntos) y los renglones (cambiar, agregar, quitar), con las mismas validaciones de la carga (incluido el duplicado, RN-GAS-08, sin contarse a sí mismo). No se cambian el **tipo de documento** ni **qué se cargó** (origen): una factura cargada como sin documento se **anula y se carga de nuevo**. Debe quedar al menos un renglón. Guardar sin cambios no registra nada. **Salvo la carga inicial** (`origen = saldo_inicial`): no se corrige, solo se anula (W-34 sin "Editar", `puede.editar = false`; `PATCH` 422 "La carga inicial no se corrige: anulala y volvé a importarla.", `importacion.md` RN-IMP-27). *(F-46 "corregir"; resuelta DU-G11; carga inicial: resuelta DU-I09)*
- **RN-GAS-38** **Al corregir, la rendición se recalcula en todas las obras afectadas**, las de antes y las de después (cambiar un renglón de obra lo saca de una y lo suma en la otra): `GastoCorregido` con la unión de las dos listas. *(cap. 8, ADR-007)*
- **RN-GAS-39** Corrección simultánea: el formulario manda el `updated_at` con que se abrió; si otra persona lo cambió mientras tanto, 409 "Alguien cambió esto mientras lo tenías abierto. Actualizá y revisá." y no se guarda nada (`VersionDeFila`). *(técnica, PU-002)*
- **RN-GAS-40** **Anular**: con `gastos = editar`, con **motivo obligatorio** ("Contá por qué se anula."). La ventana dice "Anular la factura 0003-00012345 · Deja de sumar en la rendición de Piso industrial nave 3 y Reparación de losa tanque B. No se puede deshacer." El gasto queda **tachado** con "Anulada por Diego el 18-oct 9:30: 'Cargada dos veces'.", **deja de sumar** en la rendición, el % gastado, el control por cantidad y lo gastado por proveedor, conserva sus adjuntos y su historial, y su número queda libre para volver a cargarlo bien. `GastoAnulado` después del commit. *(W-34 "Anular en lugar de borrar", "Anular la deja tachada y deja de sumar en la rendición", RN-IDE-48)*
- **RN-GAS-41** Un gasto anulado **no se corrige ni se vuelve a activar**: si se anuló por error, se carga de nuevo. Corregir o anular uno anulado: 409 "Está anulada: no se corrige. Si hace falta, cargala de nuevo." (con `data.anulado_at`). **No se borran gastos**: no hay ruta para borrar. *(W-34; resuelta DU-G11)*
- **RN-GAS-42** **Historial** (W-34), del más viejo al más nuevo, cada línea con fecha, hora y quién, en palabras del cliente: "17-oct 10:42 · Diego cargó la factura (5 renglones)", "17-oct 11:05 · Diego cambió el rubro de 'Guantes de trabajo': MATERIALES → INSUMOS", "Diego cambió la obra de 'Flete': (sin obra) → Piso industrial nave 3", "Carla cambió el precio de 'Cemento x 50 kg': 9.800 → 9.500 (total 294.000 → 285.000)", "Carla agregó el renglón 'Arena fina (m³)' · 2 × 40.000 · Piso industrial nave 3 · MATERIALES", "Carla quitó el renglón 'Flete'", "Diego cambió el número: 0003-00012354 → 0003-00012345", "Diego agregó el archivo factura.pdf" (por nombre, **sin URL ni ruta**), "Diego anuló la factura: 'Cargada dos veces'". Sale de `actividades` (`creado`, `editado`, `renglon_agregado`, `renglon_cambiado`, `renglon_quitado`, `adjunto_agregado`, `adjunto_quitado`, `anulado`), escrita por el caso de uso en la misma transacción. *(F-14 "también en gastos", F-46, W-34 "Quién la cargó y cuándo, y cada corrección posterior", ADR-010)*

### 5.8 Gastos del proveedor (F-57; W-14)

- **RN-GAS-43** Gastos implementa `Proveedores\Application\Ports\GastadoPorProveedor::porObra(ProveedorId, AlcanceObras)` (bind en `GastosServiceProvider`, reemplaza a `GastadoPorProveedorNulo`): **una consulta** sobre los renglones de gastos **no anulados** de ese proveedor, agrupada por obra, con **renglones** (cantidad de renglones) e **importe** (suma de totales, con las devoluciones restando); `null` = "(sin obra)". Con alcance "solo sus obras", solo las obras donde es responsable y sin la fila "(sin obra)" (RN-PRV-16). Filas ordenadas por cantidad de renglones y después por importe, de mayor a menor, como W-14; "(sin obra)" al final. El proveedor genérico también tiene su "Gastado por obra". Se arma sola con lo cargado (W-37 "no hay que actualizar ninguna tabla"). *(F-57, W-14, RN-PRV-15…17)*

### 5.9 Buscador general (F-03; W-18)

- **RN-GAS-44** El buscador suma el grupo **Gastos** (después de Proveedores): encuentra gastos por **número** de factura o de remito, con 3 o más dígitos, **ignorando ceros a la izquierda, guiones y espacios** ("12345", "3-12345" y "0003 00012345" encuentran `0003-00012345`). Cada resultado: "Factura 0003-00012345 · Corralón Centro · 17-oct · 905.600" (con alcance "solo sus obras", el importe es el de sus renglones). Los anulados también, con "(anulada)". Orden: fecha, la más nueva primero; los primeros 10 y la cantidad total. Sin `gastos` ≥ Ver el grupo no aparece ni viaja; respeta el alcance. `GastosEnBuscador implements FuenteBusqueda`, registrada como `gastos`. No busca en el concepto de los renglones ni por el nombre de la obra: un gasto de una obra encontrada por su nombre se ve desde la obra (grupo Obras → Rendición). *(F-03 "gastos por número de factura", W-18; resuelta DU-G07)*

### 5.10 Lo que Gastos aporta a Obras y a Rendición (F-15, F-17, F-49…F-53, F-66)

- **RN-GAS-45** **Todo lo gastado sale de los renglones** (ADR-007): Gastos implementa `Gastos\Contracts\GastadoDeObras` (firma en `rendicion.md` §12.4.1; lo que garantiza, §12.5) y publica los eventos `GastoImputado`, `GastoCorregido` y `GastoAnulado`. Rendición arma con eso la rendición (W-11, W-36, W-37, W-44), las rendiciones en curso (W-12), el control por cantidad (W-48) y el "% del presupuesto gastado" del listado de obras (W-05, puerto `AvanceGastoDeObra` de Obras, que implementa Rendición). Gastos **no** aplica EPP → INSUMOS ni compara contra lo presupuestado. *(ADR-007, F-15, F-49, F-53, F-66, obras.md §10.4)*
- **RN-GAS-46** "Últimos gastos imputados a esta obra" (W-11) son **renglones**: Fecha · Documento · Proveedor · Concepto · Rubro · Importe, los 10 más nuevos por fecha del renglón (resuelta DU-R07); los da `GastadoDeObras::renglonesDeObra` con `FiltroRenglones(limite: 10)`. Todo lo que suma en la rendición viene de renglones **no anulados**: facturas, sin comprobante, combustible, consumo de stock, amortización, retiros, devoluciones (negativas, lo único negativo: resuelta DU-G05), montos por período y carga inicial. *(W-11, ADR-007)*
- **RN-GAS-47** **Sin doble conteo por construcción** (ADR-007): la compra a stock va sin obra y su consumo con obra (RN-GAS-13, 19); el combustible y los retiros se imputan por carga o remito y su factura no se carga (RN-GAS-19, 24); un renglón tiene una sola obra (RN-GAS-13); un anulado no suma (RN-GAS-40); la amortización es una por obra (RN-GAS-23).

### 5.11 Visibilidad y alcance (ADR-006, DU-A04)

- **RN-GAS-48** Con **Gastos ≥ Ver, alcance todas**: se ven todos los gastos y todos sus renglones. Con **alcance "solo sus obras"** (configuración inicial: Ingeniería de proyectos): se ven solo los gastos con **al menos un renglón en una obra donde es responsable**; de cada uno, **solo esos renglones** (nunca los de otras obras ni los "sin obra"), y los totales (fila de W-33, detalle, buscador, "Gastado por obra") son **la suma de esos renglones**: "Total en tus obras: 549.000". W-34 agrega "Esta factura tiene además 3 renglones de otras obras o sin obra, que no ves." El **archivo de la factura** sí se baja (es el comprobante entero). Un gasto sin renglones en sus obras responde **404** (no se confirma que exista), también al bajar sus adjuntos. El alcance se calcula en el momento: si cambia el responsable de la obra, cambia lo que se ve. *(DU-A04 "si la rendición se limita a sus obras pero Gastos cargados muestra todas, la ingeniera ve por otro lado lo mismo que la rendición le oculta", identidad.md §6.1 "renglones de sus obras", RN-IDE-47; resuelta DU-G06)*
- **RN-GAS-49** Con **Gastos = Editar, alcance "solo sus obras"** (no está en la configuración inicial, pero la tabla lo permite): solo carga renglones a **sus** obras (sin "(sin obra)": "Con tu permiso solo podés imputar a tus obras."), y solo corrige o anula gastos cuyos renglones **son todos** de sus obras (si no, 403 "Esta factura tiene renglones de obras que no son tuyas: la corrige administración."). *(ADR-006)*

### 5.12 Idempotencia y concurrencia (ADR-011)

- **RN-GAS-50** Aceptan `Idempotency-Key` **todos los POST** del módulo: cargar (`POST /api/gastos`, cualquier carga) y anular. Mismo envío → misma respuesta y **un solo gasto**. Anular uno ya anulado responde 409 con el estado actual. Cargar, corregir y anular se hacen dentro de una transacción con el gasto **bloqueado para escritura** (`lockForUpdate`) al corregir o anular, y la amortización única por obra (RN-GAS-23) se verifica con la obra bloqueada, para que dos cargas a la vez no dejen dos. Los eventos salen **después del commit**. *(ADR-011, CLAUDE.md regla 5)*

### 5.13 Parte de horas (DU-C02, pedido de Bruno del 26-nov)

Hoy las horas se cargan obra por obra como un monto por período (§5.5). Con el parte se cargan **una vez** y cada
renglón va solo a su obra. La carga por período de W-46 sigue igual para FIJOS y para quien prefiera cargar las horas
como un total.

- **RN-GAS-51** En "Cargar factura / gasto" (W-10) hay un tercer **Tipo de documento: "Parte de horas"** (`tipo_documento = sin_documento`, `origen = parte_horas`). Lo carga quien tiene **`gastos = editar`**, con su alcance (RN-GAS-49: con "solo sus obras", solo a sus obras y sin "(sin obra)"). Proveedor: el genérico, siempre. En las listas el documento es "**Parte de horas 1 al 15-oct**" (W-33, W-34, W-11, W-36) y el título de W-34, "Parte de horas 1 al 15-oct · HORAS LIQUIDADAS". *(DU-C02 "Dónde", "Permiso para cargar")*
- **RN-GAS-52** **Cabecera**: **Desde** y **Hasta**, obligatorios, con las reglas y los textos de RN-GAS-27 ("Poné desde y hasta.", "El 'hasta' no puede ser anterior al 'desde'.", "El período no puede empezar después de hoy."); **Concepto** (≤ 250), por defecto "Horas 1 al 15-oct" (si viene vacío, lo pone el backend); **Planilla (opcional)**: archivo o link, 0 a 1 (contexto `comprobante_gasto`). La **fecha** del gasto y de cada renglón es el **Hasta** (`periodo_hasta`), como horas y fijos: es la fecha efectiva de la rendición y puede ser posterior a hoy. *(DU-C02 "Cabecera", "Fecha efectiva")*
- **RN-GAS-53** **Renglones**: **Empleado · Obra · Horas · Valor hora · Total**, uno por persona y obra (si a una persona le corresponden dos valores hora en la misma obra, van en dos renglones). **Empleado**: texto, obligatorio ("Renglón 2: escribí el empleado."), sin espacios al borde ni dobles. **Obra**: una obra **o** "(sin obra)", como en la factura (RN-GAS-09, 13). **Horas** (`cantidad`): mayor que cero ("Renglón 2: poné las horas."), hasta 2 decimales ("Renglón 2: hasta 2 decimales en las horas."). **Valor hora** (`precio_unitario`): mayor que cero ("Renglón 2: poné el valor hora."), hasta 2 decimales. **Total** = horas × valor hora, redondeado como cualquier renglón (§4.5); lo calcula el backend. **Rubro**: siempre **HORAS LIQUIDADAS** (lo que mande el front se ignora). **Concepto** de cada renglón: el de la cabecera. Hasta 200 renglones (RN-GAS-09). *(DU-C02 "Renglones", "Rubro")*
- **RN-GAS-54** **"Pegar desde Excel"** es del formulario: acepta filas copiadas de su planilla (empleado · obra por N.º o nombre · horas · valor hora) y llena los renglones; lo que no reconoce queda marcado para elegir a mano ("Elegí la obra." en la fila). El backend recibe los renglones ya armados, como cualquier carga. *(DU-C02 "Pegar desde Excel")*
- **RN-GAS-55** **Superposición con un monto por período**: si en una obra del parte ya hay **HORAS LIQUIDADAS cargadas como monto por período** (no anulado) cuyo período se superpone con el del parte, avisa sin frenar, una línea por obra: "Ya hay HORAS LIQUIDADAS de Piso industrial nave 3 del 1 al 15-oct (1.300.000) cargadas como monto por período. ¿Cargar igual?"; se guarda solo si se confirma (`confirmar_superposicion: true`), como RN-GAS-28. Al corregir, vuelve a avisar solo si cambió el período o se sumó una obra. *(DU-C02 "Superposición", resuelta DU-G09)*
- **RN-GAS-55b** **Al revés:** cargar HORAS LIQUIDADAS como monto por período (W-46) en una obra que ya tiene un **parte de horas** vigente cuyo período se superpone también avisa, con lo del parte en esa obra: "Ya hay HORAS LIQUIDADAS de Piso industrial nave 3 en el parte de horas del 10 al 25-oct (41.600). ¿Cargar igual?"; se guarda si se confirma. Los FIJOS no miran el parte. *(DU-C02, para no contar dos veces cargues por donde cargues)*
- **RN-GAS-56** **Al guardar**: un solo comprobante con sus renglones, en una transacción, con la actividad `creado` y `GastoImputado` con las obras después del commit (RN-GAS-16). Confirmación: "✓ Parte de horas cargado. Ya se restó en la rendición de Piso industrial nave 3 y Reparación de losa tanque B." (todo sin obra: "✓ Parte de horas cargado. No va a ninguna obra (todo sin obra)."). Se corrige y se anula en W-34 como cualquier gasto (RN-GAS-37…42); el historial nombra al empleado: "Diego cambió el valor hora de 'Juan Pérez': 5.200 → 5.500 (total 460.200 → 486.750)". **Empleados ya cargados**: el campo Empleado sugiere los nombres de partes anteriores (`GET /api/gastos/empleados?buscar=`, máx. 20, el más reciente primero; con `gastos = editar`, y con "solo sus obras", solo los de sus obras). *(DU-C02 "Al guardar", "sugiere los ya cargados")*
- **RN-GAS-57** **Privacidad del valor hora**: el **nombre** y el **valor hora** de cada renglón los ven solo quienes tienen **`gastos = editar`** (con su alcance). Los demás ven el parte **resumido por obra**, sin el detalle por persona: un renglón por obra con el concepto, las horas sumadas y el total ("Horas 1 al 15-oct · 120 h · 624.000"), sin empleado ni valor hora (`resumido: true`, `precio_unitario: null`). Vale en W-34, W-11 ("Últimos gastos"), W-36 y W-37; además, sin `gastos = editar` la **planilla** no viaja ni se baja (404) y el historial no muestra los cambios de renglones ni de la planilla del parte. El total por obra, el gastado y los totales de W-33 son los mismos para todos. *(DU-C02 "Privacidad (valor hora por persona)"; resumido por obra para que horas y total no dejen sacar el valor hora de cada uno)*

### 5.14 Facturas pagas o impagas (DU-C03, pedido de Bruno del 26-nov)

- **RN-GAS-58** El estado de pago es **solo de las facturas** (`tipo_documento = factura`). Cada factura arranca **Impaga** (`pagado_at` vacío). **No cambia la rendición**: el gastado, el % gastado y lo gastado por proveedor no dependen del pago. No hay vencimientos, condición de pago ni cuenta corriente (siguen en Tango). *(DU-C03 "Estado", "No cambia la rendición")*
- **RN-GAS-59** **Marcar como paga** (W-34): con **`pagos = editar`** y viendo la factura (`gastos` ≥ ver, con su alcance), con la **fecha del pago**, por defecto hoy (Argentina), no posterior a hoy ("La fecha no puede ser posterior a hoy."). Si ya está paga, se puede cambiar la fecha; con la misma fecha no registra nada. **Marcar como impaga** la deja sin fecha; si ya está impaga, no registra nada. Otro tipo de documento: 422 "Solo las facturas se marcan como pagas o impagas." Anulada: 409 "Está anulada: no se marca como paga ni impaga." Mandan el `updated_at` con que se abrió (409 RN-GAS-39) y aceptan `Idempotency-Key` (RN-GAS-50). Marcar cuenta como edición: cambia la versión y la "Última edición". Confirmación: "Marcamos la factura como paga." / "Marcamos la factura como impaga." *(DU-C03 "Marcar como paga")*
- **RN-GAS-60** **Historial** (W-34), solo para quien tiene `pagos` ≥ ver: "María marcó la factura como paga (20-11-2026)", "María cambió la fecha del pago: 20-11-2026 → 21-11-2026", "María marcó la factura como impaga" (acciones `pagada`, `impagada`). *(DU-C03 "Queda en el historial quién y cuándo")*
- **RN-GAS-61** **Gastos cargados (W-33)**, con `pagos` ≥ ver: cada factura trae su estado (insignia "Impaga" o "Paga 20/11/2026") y está el filtro **Pago: Todas · Impagas · Pagas** (`pago=impagas|pagas`), que deja solo facturas y se suma a los demás filtros (RN-GAS-30); el pie "Total de lo filtrado" suma lo filtrado (con Impagas, **es lo que se debe**; los anulados no suman, RN-GAS-34). Sin `pagos` ≥ ver el estado no viaja y el filtro responde 403 "No tenés acceso al estado de pago de las facturas." *(DU-C03 "Gastos cargados (W-33)")*
- **RN-GAS-62** **Detalle (W-34)**, con `pagos` ≥ ver: "Impaga" o "Paga · 20/11/2026 · marcó María"; con `pagos = editar` y la factura no anulada, **Marcar como paga** (con la fecha) o **Marcar como impaga** (`puede.marcar_pago`). **Buscador (W-18)**, con `pagos` ≥ ver: las facturas impagas llevan la marca "(impaga)" (`impaga: true`). *(DU-C03 "Marcar como paga", "Buscador")*
- **RN-GAS-63** **Permiso nuevo "Pagos de facturas"** (`pagos`, fila 13 de la tabla, ver o editar, **sin** alcance): ver muestra el estado y el filtro; editar marca y desmarca. Configuración inicial: **Editar** Gerencia de Administración y Jefatura de Administración · **Ver** Dirección y Compras · el resto **Sin acceso**; Gerencia la ajusta en la tabla de permisos (`identidad.md` §6.1, §6.3). *(DU-C03 "Permiso nuevo", "Configuración inicial")*

## 6. Estados y transiciones

| De | Acción | A | Quién | Requisitos | Evento |
|---|---|---|---|---|---|
| — | **Cargar** | Vigente | W-10 y W-35: `gastos = editar`; W-46: §8.1 (con su alcance) | §4.3, RN-GAS-03…28 | `GastoImputado {gastoId, obraIds}` |
| Vigente | **Corregir** | Vigente | `gastos = editar` (RN-GAS-49) | mismas validaciones + `updated_at` | `GastoCorregido {gastoId, obraIds (antes ∪ después)}` |
| Vigente | **Anular** | Anulada | `gastos = editar` (RN-GAS-49) | motivo | `GastoAnulado {gastoId, obraIds}` |
| Anulada | Corregir · Anular | — | — | **409** "Está anulada: no se corrige. Si hace falta, cargala de nuevo." | — |

No hay "desanular" ni borrar (RN-GAS-41). Cada acción registra su actividad en la misma transacción.

Estado de pago de una factura vigente (RN-GAS-58…60), aparte del de arriba: **Impaga** → *Marcar como paga (fecha)* →
**Paga** → *Marcar como impaga* → **Impaga**; quién: `pagos = editar`; requisitos: factura no anulada + `updated_at`;
sin evento (no toca la rendición); actividad `pagada` / `impagada`.

## 7. Pantallas: vacío, cargando, error y sin permiso

| Pantalla | Vacío | Cargando | Error | Sin permiso |
|---|---|---|---|---|
| W-33 Gastos cargados | Sin ningún gasto: "Todavía no hay gastos cargados." + "+ Cargar factura / gasto" (con editar). Con filtros: "No hay gastos con estos filtros." + "Limpiar filtros" | filas grises en la tabla; los filtros se pueden tocar | "No pudimos traer los gastos. Probá de nuevo." + **Reintentar** (lo escrito en los filtros no se pierde) | sin `gastos` ≥ ver: el ítem no está en la barra; por URL, "No tenés acceso a Gastos. Si lo necesitás, pedíselo a gerencia." (API 403) |
| W-34 Detalle | — | esqueleto de cabecera y renglones | "No pudimos traer este gasto. Probá de nuevo." | inexistente, ajeno o fuera de alcance: "No encontramos este gasto." (API 404) |
| W-10 / W-35 Carga | formulario con un renglón vacío; fecha = hoy | al guardar, el botón dice "Guardando…" y no se puede volver a tocar | 422: cada error en su campo o renglón, el formulario no se vacía; sin conexión: "No se pudo guardar. Revisá la conexión y probá de nuevo." (reintenta con la **misma** `Idempotency-Key`) | sin `gastos = editar`: no aparece el botón; por URL, "Solo administración y compras cargan gastos." (API 403) |
| W-46 Renglones sin documento | cada sección: "Todavía no hay amortización / retiros / devoluciones / períodos cargados en esta obra." | por sección | por sección, como W-10 | sin el permiso de §8.1 (`rendicion = editar`; para retiros, también `control_cantidad = editar`): W-11 y W-48 no muestran el acceso y la sección no deja guardar; por URL, 403 "No podés cargar en esta rendición." |
| W-14 "Gastado por obra" | "Todavía no hay gastos cargados de este proveedor." | filas grises | "No pudimos traer lo gastado. Probá de nuevo." | sin `gastos` ≥ ver: la sección no aparece (RN-PRV-16) |
| Grupo Gastos del buscador | "Gastos (0) · Sin resultados." | — | el grupo no aparece y los demás sí | sin `gastos` ≥ ver: el grupo no viaja |

**Cómo se leen dos cosas de esta especificación (QC S3, 25-nov-2026):**
- **Fechas (resuelta DU-G13):** dd/mm/aaaa en toda la app (PU-209). Donde este documento escribe "17-oct" o
  "02-oct", se lee "17/10/2026" o "02/10/2026". Los rangos de período siguen el formato de `fechas.ts`.
- **Botón mientras guarda (resuelta DU-G14):** como en el sistema de diseño: el botón conserva su etiqueta, muestra el
  indicador de carga, no se puede volver a tocar y le anuncia "Guardando…" al lector de pantalla (`aria-busy`). Donde
  dice "el botón dice 'Guardando…'", se cumple así.

## 8. Permisos

### 8.1 Fila de la matriz y alcance

| Función (fila) | Nivel | Qué habilita en este módulo | Alcance `propias` = |
|---|---|---|---|
| `gastos` | ver | W-33, W-34 (con historial y adjuntos), grupo Gastos del buscador, "Gastado por obra" (W-14, con `proveedores` ≥ ver) | renglones de obras donde es responsable (RN-GAS-48) |
| `gastos` | editar | además: cargar facturas y gastos sin comprobante (W-10, W-35), **corregir y anular cualquier gasto** (W-34, también los cargados desde W-46) | ídem (RN-GAS-49) |
| `rendicion` | editar | cargar desde la rendición, en W-46: amortización, retiros con remito, devoluciones y horas y fijos por período (`identidad.md` §6.1 fila 8) | obras donde es responsable |
| `control_cantidad` | editar | cargar retiros con remito (W-46 sección retiros, abierta desde W-48) y asociar o quitar su remito (`identidad.md` §6.1 fila 9, §6.6) | sus obras |
| `control_cantidad` | ver | bajar el remito de un retiro (`identidad.md` §6.6) | sus obras |
| `proveedores` | editar | "+ nuevo proveedor" desde la factura (W-38) | — |
| `gastos` | editar | cargar el **parte de horas** (W-10) y ver el empleado y el valor hora de cada renglón (RN-GAS-57) | ídem (RN-GAS-49) |
| `pagos` | ver | el estado de pago de las facturas en W-33, W-34 y el buscador, el filtro Pago y su historial (RN-GAS-60…62) | — (sin alcance) |
| `pagos` | editar | además: marcar como paga o impaga (RN-GAS-59) | — |

Configuración inicial (`identidad.md` §6.3): **Editar·todas** Gerencia de Administración, Jefatura de Administración y
Compras · **Ver·todas** Dirección, Gerencia de Operaciones y Jefatura de Proyectos · **Ver·solo sus obras** Ingeniería
de proyectos (DU-A04) · **Sin acceso** Calidad, Seguridad e Higiene, RRHH y Pañol y Logística (DU-A02, DU-A03).

**Cargas desde la rendición (resuelta DU-R05, que reemplaza a DU-G08):** se sigue la tabla aprobada de `identidad.md`
§6.1. W-46 (amortización, devoluciones, horas y fijos) pide **`rendicion = editar`** sobre esa obra; los **retiros con
remito**, `rendicion = editar` **o** `control_cantidad = editar`. Facturas y gastos sin comprobante (W-10, W-35) siguen
con `gastos = editar`, y **corregir o anular** cualquier gasto en W-34 también (fila 7, F-46). El endpoint es de Gastos
y su Policy (`GastoPolicy::cargar(origen, obra)`) aplica esta regla; es la misma que `rendicion.md` RN-REN-58. Con la
configuración inicial las tres celdas (`gastos`, `rendicion`, `control_cantidad` en Editar) las tienen las mismas
personas (Gerencia y Jefatura de Administración, Compras).

### 8.2 Por pantalla y endpoint

| Acción | Función | Nivel | Alcance | Errores |
|---|---|---|---|---|
| Listar (W-33), filtros, agrupar | `gastos` | ver | sí | 403 · 422 |
| Opciones de los filtros Obra y Proveedor de W-33, con búsqueda (resuelta DU-G12) | `gastos` | ver | sí: solo sus obras y los proveedores de sus renglones | 403 |
| Ver detalle e historial (W-34), bajar adjuntos | `gastos` (remito: o `control_cantidad`) | ver | sí | 404 fuera de alcance |
| Cargar factura o sin documento (W-10, W-35) | `gastos` | editar | sí (RN-GAS-49) | 403 · 404 (obra) · 422 |
| Cargar amortización, devolución o período (W-46) | `rendicion` | editar | sí | 403 · 404 (obra) · 422 |
| Cargar retiro con remito (W-46) | `rendicion` **o** `control_cantidad` | editar | sí | 403 · 404 (obra) · 422 |
| Corregir | `gastos` | editar | sí | 403 · 404 · 409 · 422 |
| Asociar o quitar el remito de un retiro (`PATCH` solo con `adjuntos` y `updated_at`, §4.4) | `gastos` **o** `control_cantidad` | editar | sí | 403 · 404 · 409 · 422 |
| Opciones de proveedores para cargar (`GET /api/proveedores/opciones`) | `proveedores` ≥ ver, o quien puede cargar (`gastos`, `rendicion` o `control_cantidad` en editar, DU-R05) | — | — | 403 |
| Anular | `gastos` | editar | sí | 403 · 404 · 409 · 422 |
| Opciones de obras y rubros; aviso de factura repetida | quien puede cargar (cualquiera de las tres celdas de arriba) | editar | sí | 403 |
| Opciones de materiales (`GET /api/obras/{obra}/materiales/opciones`, de Rendición) | `rendicion.md` §7.2 | — | sí | 403 · 404 |
| Lista de W-46 de una obra | `rendicion` **o** `control_cantidad` | ver (para cargar, la celda de cada sección) | sí | 403 · 404 |
| "Gastado por obra" del proveedor | `proveedores` ≥ ver **y** `gastos` ≥ ver | ver | sí | 403 · 404 |
| Grupo Gastos del buscador | `gastos` | ver | sí | el grupo no viaja |
| Cargar un parte de horas (W-10) · sugerencias de empleados | `gastos` | editar | sí (RN-GAS-49) | 403 · 422 |
| Ver el estado de pago (W-33, W-34, buscador) y filtrar por Pago | `pagos` (y `gastos` ≥ ver para la factura) | ver | el de `gastos` | estado ausente · filtro 403 |
| Marcar como paga o impaga | `pagos` (y ver la factura) | editar | el de `gastos` | 403 · 404 · 409 · 422 |

Configuración inicial de `pagos` (DU-C03): Editar Gerencia de Administración y Jefatura de Administración · Ver
Dirección y Compras · Sin acceso el resto.

El detalle trae `puede: {editar, anular, marcar_pago}` para mostrar u ocultar botones; la API vuelve a verificar todo (RN-IDE-14).
Cada endpoint tiene test de permitido, denegado e IDOR (ADR-006).

## 9. Avisos

Gastos **no** genera avisos: el documento firmado no tiene avisos de gastos (cap. 5 y 7). Los eventos de §6 los
escucha Rendición para recalcular (invalidar su cache), no Avisos.

## 10. Casos borde (datos ficticios)

| # | Caso | Resultado esperado |
|---|---|---|
| 1 | Diego carga la factura 0003-00012345 de Corralón Centro (17-10-2026) con los 5 renglones de W-10 | 201; total 905.600; por obra Nave 3 549.000 · Losa tanque B 311.600 · sin obra 45.000; `GastoImputado` con las dos obras; la rendición de cada una ya lo resta. |
| 2 | Carla carga otra vez "3-12345" de Corralón Centro | 422 "Esa factura ya está cargada: 0003-00012345 de Corralón Centro (Diego, 17-oct)." con link (RN-GAS-07, 08). |
| 3 | "0003-00012345" de Ferretería Norte | Se acepta (otro proveedor). |
| 4 | Diego anula la de Corralón Centro ("Cargada con el proveedor equivocado") y la vuelve a cargar | Las dos existen; la anulada tachada, no suma; la nueva se acepta. |
| 5 | Factura con el proveedor genérico | 422 RN-GAS-06. |
| 6 | Renglón "Cemento x 50 kg", 10 bolsas: 6 a Nave 3 y 4 a Losa tanque B | Se cargan **dos renglones** (6 y 4); no hay reparto por porcentaje (RN-GAS-13). |
| 7 | Renglón con obra y sin rubro | 422 "Renglón 2: elegí el rubro." |
| 8 | Renglón "Flete" sin obra y sin rubro | Se acepta (sin obra el rubro es opcional). |
| 9 | Renglón con rubro HORAS LIQUIDADAS en una factura | 422 RN-GAS-12. |
| 10 | Cantidad 3, precio 33,335 | 422 "Hasta 2 decimales en el precio." (el formulario lo avisó antes). Con precio 33,34: total 100,02. |
| 11 | Cantidad 2,5 m³ a 42.500 | Total 106.250. |
| 12 | Cantidad 0,333 kg a 1.000,01 | Total `round(333,003330, 2)` = 333,00. |
| 13 | Cantidad −4 en una factura | 422 "La cantidad va en negativo solo en una devolución." |
| 14 | Compra en dólares: "Disco diamantado importado", 2 × 118.000, detalle "USD 100 c/u a 1.180" | Se guarda en pesos; la cotización queda en el detalle. |
| 15 | Fecha de factura mañana | 422 "La fecha no puede ser posterior a hoy." (hoy = día de Argentina: a las 23:30 del 17-oct en Argentina, "18-oct" es mañana aunque el servidor esté en UTC). |
| 16 | Factura para la obra "Reparación de losa tanque B" Terminada | Se acepta; el selector y el detalle muestran "Obra terminada" (DU-A15). |
| 17 | Factura para una obra sin presupuesto cargado | Se acepta; la rendición muestra lo gastado (RN-OBR-34). |
| 18 | Renglón con material "Cemento x 50 kg" de la lista de **Impermeabilización cubierta** pero obra Nave 3 | 422 "El material Cemento x 50 kg es de otra obra: quitá el material o elegí uno de esta obra." (también si se corrige la obra de un renglón que tiene material). |
| 19 | Laura rinde con W-35: combustible 68.000 (15-oct), comida 42.000 (16-oct), peaje 3.500 (16-oct), "rendido por: Laura", foto HEIC de los tickets | 201; total rendido 113.500; proveedor genérico; cada renglón con su fecha; la foto se baja con su nombre. |
| 20 | Gastos de obra rendidos sin "rendido por" | 422 "Elegí o escribí quién rindió." (DU-G02). |
| 21 | "Rendido por: supervisor de obra Juan" (no es usuario) | Se guarda el texto. |
| 22 | Consumo de stock propio con un renglón "(sin obra)" | 422 "El consumo de stock va a una obra." |
| 23 | Retiro: remito 0001-00000456 del 14-oct, Endurecedor superficial (de la lista) 10 bolsas a 18.000 | Total 180.000 en MATERIALES; suma 10 en "retirado" de W-48 el 14-oct y 180.000 en la rendición. |
| 24 | Retiro con "Guantes y antiparras" que no está en la lista | Se elige "No está en la lista" y se escribe el material y la unidad ("par"); se crea como material fuera de presupuesto de Nave 3 y aparece en "Fuera de presupuesto" de W-48 con su unidad; el próximo retiro ya lo ofrece (resuelta DU-G03). Sin material ni "No está en la lista": 422 "Elegí el material o 'No está en la lista'." |
| 25 | Devolución: Cemento x 50 kg (sobrante) −4 a 9.800 | Total −39.200; resta en MATERIALES y en lo retirado del cemento. |
| 26 | Devolución con cantidad 4 | 422 "En una devolución la cantidad va en negativo." |
| 27 | Segunda amortización en Nave 3 | 422 "Esta obra ya tiene su amortización (310.000): corregila." Diego la corrige a 350.000 desde W-34: historial "Diego cambió el precio de 'Amortización de herramientas de la obra': 310.000 → 350.000". |
| 28 | Horas liquidadas Nave 3 del 1 al 15-oct por 1.300.000 y, después, otra del 10 al 25-oct | La segunda avisa "Ya hay HORAS LIQUIDADAS de Piso industrial nave 3 del 1 al 15-oct (1.300.000). ¿Cargar igual?"; se guarda solo si se confirma (resuelta DU-G09). |
| 29 | Monto por período con hasta 30-sep y desde 1-oct | 422 "El 'hasta' no puede ser anterior al 'desde'." |
| 29b | El 24-nov cargan Fijos de Nave 3 del 1 al 30-nov por 260.000 | Se acepta; la fecha del renglón es el 30-nov (`periodo_hasta`, resuelta DU-R04) y ya resta en la rendición (acumulada). |
| 29c | Llega una nota de crédito de Corralón Centro por 5 bolsas de cemento devueltas | Se carga como **devolución** en W-46 (−5, proveedor Corralón Centro); no hay tipo "nota de crédito" (resuelta DU-G05). Si la nota corrige el precio de una factura ya cargada, se corrige esa factura en W-34. |
| 30 | Diego cambia "Flete" de (sin obra) a Nave 3 | `GastoCorregido` con Nave 3; la rendición de Nave 3 suma 45.000 en LOGISTICA; historial con la línea de la obra. |
| 31 | Diego pasa "Guantes de trabajo" de Losa tanque B a Nave 3 | `GastoCorregido` con las **dos** obras: Losa tanque B resta 75.600 menos, Nave 3 suma 75.600. |
| 32 | Diego y Carla corrigen la misma factura a la vez | El segundo en guardar: 409 "Alguien cambió esto mientras lo tenías abierto. Actualizá y revisá." |
| 33 | Corregir una factura anulada | 409 "Está anulada: no se corrige. Si hace falta, cargala de nuevo." |
| 34 | Anular sin motivo | 422 "Contá por qué se anula." |
| 35 | Doble clic en "Guardar factura" con la misma `Idempotency-Key` | Un solo gasto; la misma respuesta 201. |
| 36 | W-33 con Obra = Piso industrial nave 3, del 1 al 19-oct | La factura de Corralón Centro aparece con "549.000 · de 905.600"; el total de lo filtrado suma 549.000 por ella (DU-G06). |
| 37 | W-33 agrupado por rubro con una factura que tiene EPP e INSUMOS | EPP e INSUMOS son grupos separados. |
| 38 | Laura (Ingeniería, Ver·solo sus obras, responsable de Nave 3) abre la factura de Corralón Centro | Ve los 2 renglones de Nave 3 (549.000), "Esta factura tiene además 3 renglones de otras obras o sin obra, que no ves.", baja el PDF; sin Editar ni Anular. |
| 39 | Laura abre por URL la factura 0005-00003310 de Hotel Puerto Ejemplo (solo Losa tanque B, de Hernán) | 404 "No encontramos este gasto."; su PDF también 404. |
| 40 | Laura pide `POST /api/gastos` | 403. |
| 41 | Sofía (Seguridad e Higiene, Gastos sin acceso) busca "12345" | El grupo Gastos no viaja; ve Obras, Pedidos y Proveedores según sus permisos. |
| 42 | Buscar "12345" | Grupo Gastos: "Factura 0003-00012345 · Corralón Centro · 17-oct · 905.600". |
| 43 | Dirección abre Corralón Centro en Proveedores | "Gastado por obra": Nave 3 · 4 renglones · 2.489.000 … (sin obra) · 1 · 45.000 · Total (RN-GAS-43). |
| 44 | Laura abre la ficha de Corralón Centro | No tiene `proveedores` ≥ ver (W-17): no ve Proveedores. |
| 45 | Carla (Compras) carga una factura de "Corralón Ñandú" que no está en la base | "+ nuevo proveedor" → W-38; al guardar vuelve a la factura con el proveedor elegido y los renglones intactos. |
| 46 | Factura con 201 renglones | 422 "Hasta 200 renglones por comprobante: dividilo en dos cargas." |
| 47 | Se manda `POST /api/gastos` con `total: "1.00"` en un renglón | Se ignora: el backend calcula el total. |
| 48 | Se asocia como factura un adjunto subido por otra persona o de contexto `pedido` | 422 "Ese adjunto no se puede usar acá." (RN-IDE-46). |
| 49 | Diego carga el parte del 1 al 15-oct: Juan Pérez 88,5 h a 5.200 en Nave 3 y 16 h en Losa tanque B; Pablo Gómez 8 h a 4.800 sin obra | 201; tres renglones en HORAS LIQUIDADAS con fecha 15-oct; totales 460.200 · 83.200 · 38.400; "✓ Parte de horas cargado. Ya se restó en la rendición de Piso industrial nave 3 y Reparación de losa tanque B."; `GastoImputado` con las dos obras. |
| 50 | Parte con un renglón sin empleado, otro con 0 h y otro con 8,125 h | 422 "Renglón 1: escribí el empleado.", "Renglón 2: poné las horas.", "Renglón 3: hasta 2 decimales en las horas." |
| 51 | Parte con rubro MATERIALES en un renglón (por la API) | Se guarda en HORAS LIQUIDADAS (se ignora). |
| 52 | Nave 3 ya tiene HORAS LIQUIDADAS del 1 al 15-oct por 1.300.000 (monto por período) y Diego carga un parte del 10 al 25-oct con un renglón en Nave 3 | Avisa "Ya hay HORAS LIQUIDADAS de Piso industrial nave 3 del 1 al 15-oct (1.300.000) cargadas como monto por período. ¿Cargar igual?"; se guarda solo si confirma. |
| 53 | Laura (Ingeniería, Gastos Ver·solo sus obras, responsable de Nave 3) abre el parte del caso 49 | Ve un renglón "Horas 1 al 15-oct · 88,5 h · 460.200" sin nombre ni valor hora, "Esta factura tiene además…" por los otros dos; sin planilla; en W-36 de Nave 3 lo mismo. |
| 54 | Laura pide `GET /api/gastos/empleados` | 403. |
| 55 | Carla (Compras, Pagos = Ver) abre la factura 0003-00012345 | Ve "Impaga", sin "Marcar como paga". `POST …/marcar-paga`: 403. |
| 56 | María (Jefatura de Administración, Pagos = Editar) la marca paga el 20-11 y después impaga | Historial "María marcó la factura como paga (20-11-2026)" y "María marcó la factura como impaga"; la rendición no cambia. |
| 57 | Marcar paga con fecha de mañana · marcar paga un retiro · marcar paga una factura anulada | 422 "La fecha no puede ser posterior a hoy." · 422 "Solo las facturas se marcan como pagas o impagas." · 409 "Está anulada: no se marca como paga ni impaga." |
| 58 | W-33 con Pago = Impagas, del 1 al 19-oct | Solo facturas sin pagar; el pie suma lo que se debe. Con Laura (Pagos sin acceso): 403 "No tenés acceso al estado de pago de las facturas." y sus filas sin `pago`. |

## 11. Celular y polling

- Mismas reglas y misma API que en la PC (F-13 "todas las funciones"), a 390 px sin desplazamiento horizontal: W-33 en
  **tarjetas** (documento · proveedor · fecha · total; obras y rubros debajo), filtros en una hoja que se abre con
  "Filtros"; W-34 con los renglones en tarjetas; W-10 / W-35 con cada renglón como tarjeta ("Renglón 2 · Arena gruesa
  (m³) · 6 × 42.500 = 255.000 · Nave 3 · MATERIALES") y el total fijo abajo. En W-35 y en el remito, **"Subir archivo"
  ofrece cámara o galería** (la foto HEIC se acepta tal cual, HU-025).
- W-33 abierto se actualiza cada **15 s** con la pestaña visible y al volver a enfocarla (ADR-005). Las acciones
  propias invalidan al instante W-33, W-34 y las consultas de rendición de las obras afectadas (arquitectura §4.3).

## 12. Contrato de API sugerido

Envelope `{ data, message, errors }` en todo; listados con `meta`/`links`. Fechas `YYYY-MM-DD`; timestamps ISO-8601
con zona `America/Argentina/Buenos_Aires`. Importes y cantidades como **string decimal**. Todos los POST aceptan
`Idempotency-Key`. Códigos: 401, 403, **404** (también fuera de alcance), **409** (anulado o versión vieja), 422
(errores por campo; los de renglón como `errors["renglones.2.rubro"]`). Rutas en `app/Modules/Gastos/routes.php`.

### 12.1 Endpoints

| Método y ruta | Payload | Respuesta `data` | Errores |
|---|---|---|---|
| `GET /api/gastos?obra_id=\|sin_obra&proveedor_id=&rubro=&desde=&hasta=&tipo=factura\|sin_documento&pago=impagas\|pagas&agrupar=proveedor\|obra\|rubro&page=` (`pago` pide `pagos` ≥ ver, RN-GAS-61) | — | `[GastoFila]` paginado de a 50; `meta: {total_filtrado, comprobantes, anulados, desde, hasta}`; con `agrupar`, `grupos: [{clave, nombre, subtotal, comprobantes, filas: [GastoFila]}]` | 403 · 422 |
| `GET /api/gastos/filtros/obras?buscar=` | — | `[{id, numero, nombre_trabajo}]` (filtro Obra de W-33: máx. 20 coincidencias por búsqueda, por nombre del trabajo o N.º; todas las obras o solo las suyas con alcance propias; resuelta DU-G12) | 403 |
| `GET /api/gastos/filtros/proveedores?buscar=` | — | `[{id, nombre, cuit, es_generico}]` (filtro Proveedor de W-33: los proveedores de los gastos que ve, con alcance propias solo los de sus renglones; máx. 20 coincidencias por búsqueda, por nombre o CUIT; resuelta DU-G12) | 403 |
| `GET /api/gastos/opciones` | — | `{rubros: [{clave, nombre, se_imputa: true\|false, carga_por_periodo}], origenes_sin_documento: ["sin_comprobante","combustible","consumo_stock"]}` | 403 |
| `GET /api/gastos/obras-opciones?buscar=` | — | `[{id, numero, nombre_trabajo, estado, tiene_materiales}]` (máx. 20, todas las obras o solo las suyas con alcance propias; usa `ObrasConsulta`) | 403 |
| `GET /api/gastos/factura-existente?proveedor_id=&numero=&excluir_id=` | — | `{existe: true, gasto: {id, numero, fecha, cargado_por}, mensaje}` o `{existe: false}` (RN-GAS-08). Con alcance (§8.2): si la factura es de una obra que no ve, `{existe: true, mensaje}` sin `gasto` (sin id, fecha ni quién) | 403 · 422 |
| `POST /api/gastos` | `GastoAlta` (§12.2) | `GastoDetalle` (201) + `obras_afectadas: [{id, nombre_trabajo}]` | 403 · 422 (obra o proveedor inexistente, en su campo, como §13) |
| `GET /api/gastos/{id}` | — | `GastoDetalle` | 404 |
| `PATCH /api/gastos/{id}` | campos de cabecera opcionales + `renglones` = **la lista completa** (los que traen `id` se conservan o cambian; sin `id`, nuevos; los que no vienen, se quitan) + `adjuntos: [ids]` (lista completa) + `updated_at` | `GastoDetalle` | 403 · 404 · 409 · 422 |
| `POST /api/gastos/{id}/anular` | `{motivo}` | `GastoDetalle` | 403 · 404 · 409 · 422 |
| `GET /api/gastos/{id}/historial` | — | `[{cuando, quien: {id, nombre}, que: "Diego cambió el rubro de 'Guantes de trabajo': MATERIALES → INSUMOS"}]` | 404 |
| `GET /api/obras/{obra}/renglones-sin-documento` | — | `{obra: {id, numero, nombre_trabajo, estado}, amortizacion: GastoDetalle\|null, retiros: [GastoDetalle], devoluciones: [GastoDetalle], periodos: [GastoDetalle], ultimo_proveedor_retiro: {id, nombre}\|null, puede: {amortizacion, retiros, devoluciones, periodos}}` (W-46; ruta del módulo Gastos; `puede` según §8.1) | 403 · 404 |
| `GET /api/gastos/empleados?buscar=` | — | `["Juan Pérez", "Pablo Gómez"]` (RN-GAS-56: máx. 20, el usado más reciente primero; `gastos = editar`; con alcance propias, solo de sus obras) | 403 |
| `POST /api/gastos/{id}/marcar-paga` | `{fecha?: "2026-11-20", updated_at}` (sin `fecha` = hoy) | `GastoDetalle`, `message` "Marcamos la factura como paga." | 403 · 404 · 409 · 422 |
| `POST /api/gastos/{id}/marcar-impaga` | `{updated_at}` | `GastoDetalle`, `message` "Marcamos la factura como impaga." | 403 · 404 · 409 · 422 |
| `GET /api/buscar?q=` (grupo de `GastosEnBuscador`) | — | §12.3 | grupo ausente sin `gastos` ≥ ver |
| `GET /api/proveedores/{id}/gastado-por-obra` (Proveedores) | — | `proveedores.md` §10.3, con datos | 403 · 404 |
| `POST /api/adjuntos` · `GET /api/adjuntos/{id}` (Shared) | contextos `comprobante_gasto`, `remito` | `obras.md` §10.5 | §4.4 |

Proveedores (`GET /api/proveedores/opciones`, `POST /api/proveedores`) y personas (`GET /api/usuarios/opciones`, para
"rendido por") ya existen y se usan tal cual. Los materiales de la obra se piden a **Rendición**
(`GET /api/obras/{obra}/materiales/opciones`, `rendicion.md` §12.2): Gastos no tiene un endpoint propio de materiales.

### 12.2 Payloads

```json
// GastoAlta — factura (W-10)
{
  "tipo_documento": "factura", "origen": "factura",
  "numero": "3-12345",                    // se guarda "0003-00012345" (RN-GAS-07)
  "fecha": "2026-10-17",
  "proveedor_id": 21,
  "adjuntos": [61],                       // ids temporales de POST /api/adjuntos (contexto "comprobante_gasto")
  "renglones": [
    { "concepto": "Cemento x 50 kg", "cantidad": "30", "precio_unitario": "9800.00", "obra_id": 12, "rubro": "materiales", "material_id": 301 },
    { "concepto": "Arena gruesa (m³)", "cantidad": "6", "precio_unitario": "42500.00", "obra_id": 12, "rubro": "materiales" },
    { "concepto": "Disco diamantado 350 mm", "cantidad": "2", "precio_unitario": "118000.00", "obra_id": 14, "rubro": "maquinas_herramientas" },
    { "concepto": "Guantes de trabajo (par)", "cantidad": "24", "precio_unitario": "3150.00", "obra_id": 14, "rubro": "insumos" },
    { "concepto": "Flete", "cantidad": "1", "precio_unitario": "45000.00", "obra_id": null, "rubro": "logistica" }  // obra_id: la clave tiene que venir (null = sin obra)
  ]
}
// GastoAlta — gastos de obra rendidos (W-35)
{ "tipo_documento": "sin_documento", "origen": "sin_comprobante", "fecha": "2026-10-18", "rendido_por": "Laura",
  "proveedor_id": null,                   // null = el genérico (resuelta DU-G02: o uno de la base)
  "adjuntos": [62],
  "renglones": [ { "fecha": "2026-10-15", "concepto": "Combustible camioneta", "cantidad": "1", "precio_unitario": "68000.00", "obra_id": 12, "rubro": "logistica" } ] }
// GastoAlta — retiro con remito (W-46)
{ "tipo_documento": "sin_documento", "origen": "retiro_remito", "numero": "0001-00000456", "fecha": "2026-10-14",
  "proveedor_id": 40, "adjuntos": [63],   // contexto "remito", máx. 1
  "renglones": [ { "material_id": 302, "concepto": "Endurecedor superficial (bolsa)", "cantidad": "10", "precio_unitario": "18000.00", "obra_id": 12, "rubro": "materiales" },
                 { "material_nuevo": { "nombre": "Guantes y antiparras", "unidad": "par" },   // "No está en la lista": fuera de presupuesto (MaterialesDeObra::crearFueraDePresupuesto)
                   "concepto": "Guantes y antiparras", "cantidad": "12", "precio_unitario": "4500.00", "obra_id": 12, "rubro": "epp" } ],
  "confirmar_remito_repetido": false }
// GastoAlta — devolución (W-46): cantidad negativa
{ "tipo_documento": "sin_documento", "origen": "devolucion", "fecha": "2026-10-17",
  "renglones": [ { "material_id": 300, "concepto": "Cemento x 50 kg (sobrante)", "cantidad": "-4", "precio_unitario": "9800.00", "obra_id": 12, "rubro": "materiales" } ] }
// GastoAlta — amortización (W-46)
{ "tipo_documento": "sin_documento", "origen": "amortizacion", "fecha": "2026-10-18",
  "renglones": [ { "concepto": "Amortización de herramientas de la obra", "cantidad": "1", "precio_unitario": "310000.00", "obra_id": 12, "rubro": "amortizacion" } ] }
// GastoAlta — monto por período (W-46, sección "Horas liquidadas y fijos por período"): un solo renglón; fecha = periodo_hasta
{ "tipo_documento": "sin_documento", "origen": "monto_periodo", "periodo_desde": "2026-10-01", "periodo_hasta": "2026-10-15",
  "renglones": [ { "concepto": "Horas liquidadas 1 al 15-oct", "cantidad": "1", "precio_unitario": "1300000.00", "obra_id": 12, "rubro": "horas_liquidadas" } ],
  "confirmar_superposicion": false }
// GastoAlta — parte de horas (W-10, DU-C02): cantidad = horas, precio_unitario = valor hora; rubro y concepto del renglón los pone el backend
{ "tipo_documento": "sin_documento", "origen": "parte_horas", "periodo_desde": "2026-10-01", "periodo_hasta": "2026-10-15",
  "concepto": "Horas 1 al 15-oct",        // opcional: vacío = "Horas 1 al 15-oct"
  "adjuntos": [64],                       // la planilla, 0 a 1, contexto "comprobante_gasto"
  "renglones": [ { "empleado": "Juan Pérez", "obra_id": 12, "cantidad": "88.5", "precio_unitario": "5200.00" },
                 { "empleado": "Juan Pérez", "obra_id": 14, "cantidad": "16", "precio_unitario": "5200.00" },
                 { "empleado": "Pablo Gómez", "obra_id": null, "cantidad": "8", "precio_unitario": "4800.00" } ],
  "confirmar_superposicion": false }
// Corregir un parte (PATCH): además de lo de siempre, "concepto" (cabecera) y "empleado" en cada renglón.

// GastoFila (W-33)
{ "id": 501, "fecha": "2026-10-17", "tipo_documento": "factura", "origen": "factura",
  "documento": "Factura 0003-00012345",
  "proveedor": { "id": 21, "nombre": "Corralón Centro", "es_generico": false },
  "obras": [ { "id": 12, "nombre_trabajo": "Piso industrial nave 3" }, { "id": 14, "nombre_trabajo": "Reparación de losa tanque B" }, null ],
  "rubros": ["materiales", "maquinas_herramientas", "insumos", "logistica"],
  "total": "905600.00", "total_filtrado": "549000.00",   // total_filtrado: solo con filtro de obra o rubro, o con alcance propias
  "cargado_por": { "id": 3, "nombre": "Diego" }, "anulado": false,
  "pago": { "estado": "paga", "pagado_at": "2026-11-20", "pagado_por": { "id": 5, "nombre": "María" } } }
  // "pago" viaja solo con pagos ≥ ver: null si no es factura; impaga = { "estado": "impaga", "pagado_at": null, "pagado_por": null }

// GastoDetalle = GastoFila +
{ "numero": "0003-00012345", "rendido_por": null, "periodo_desde": null, "periodo_hasta": null,
  "proveedor": { "id": 21, "nombre": "Corralón Centro", "cuit": "30-00000000-7", "es_generico": false },
  "adjuntos": [ /* Adjunto (obras.md §10.5), contexto "comprobante_gasto" o "remito" */ ],
  "renglones": [ { "id": 9001, "orden": 1, "fecha": "2026-10-17", "concepto": "Cemento x 50 kg", "cantidad": "30.000",
                   "precio_unitario": "9800.00", "total": "294000.00",
                   "obra": { "id": 12, "numero": "2-131-2026", "nombre_trabajo": "Piso industrial nave 3", "estado": "en_ejecucion" },
                   "rubro": { "clave": "materiales", "nombre": "MATERIALES" },
                   "material": { "id": 301, "nombre": "Cemento x 50 kg", "unidad": "bolsa" }, "detalle": null,
                   "empleado": null, "resumido": false } ],
  // Parte de horas: con gastos = editar, cada renglón con "empleado": "Juan Pérez"; sin él, uno por obra con
  // "id": null, "empleado": null, "precio_unitario": null, "cantidad" = horas sumadas y "resumido": true (RN-GAS-57).
  "por_obra": [ { "obra": { "id": 12, "nombre_trabajo": "Piso industrial nave 3" }, "total": "549000.00" }, { "obra": null, "total": "45000.00" } ],
  "renglones_ocultos": 0,                 // con alcance propias: cuántos no ve (RN-GAS-48)
  "cargado_at": "2026-10-17T10:42:00-03:00", "updated_at": "2026-10-17T11:05:00-03:00", "updated_by": { "id": 3, "nombre": "Diego" },
  "anulacion": null,                      // { "anulado_at": "…", "anulado_por": {id, nombre}, "motivo": "Cargada dos veces" }
  "puede": { "editar": true, "anular": true, "marcar_pago": true } }   // marcar_pago: pagos = editar, factura no anulada
```

### 12.3 Buscador

`GastosEnBuscador` (RN-GAS-44) devuelve en `GET /api/buscar?q=` el grupo
`{tipo: "gastos", cantidad, resultados: [{id, documento: "Factura 0003-00012345", proveedor: {id, nombre, es_generico}|null, fecha, total, anulado}]}`
(con alcance propias, `total` = sus renglones). El genérico (`es_generico: true`) se muestra "—" (HU-085). Con
`pagos` ≥ ver cada resultado suma `impaga: true|false` (true solo en facturas impagas: "(impaga)", RN-GAS-62).

### 12.4 Contratos con otros módulos

| Contrato | Dirección | Métodos | Uso |
|---|---|---|---|
| `Proveedores\Application\Ports\GastadoPorProveedor` | Gastos **implementa** (`GastadoPorProveedorEloquent` en `Gastos/Infrastructure`, bind en `GastosServiceProvider`) | `porObra(ProveedorId, AlcanceObras): GastadoPorObra` | W-14 (RN-GAS-43) |
| `Proveedores\Contracts\ProveedoresConsulta` | usa | `existe`, `resumen`, `generico`, `esGenerico` | validar el proveedor, "trae sus datos", el genérico |
| `Obras\Contracts\ObrasConsulta` | usa | `existe`, `resumen`, `opciones(texto, limite)`, `obrasDondeEsResponsable` | validar la obra, selector, alcance propias |
| `Rendicion\Contracts\MaterialesDeObra` | usa (**lo define `rendicion.md` §12.4.3**) | `opciones(obraId, ?texto): list<MaterialOpcion{id, nombre, unidad, fueraDePresupuesto, restaLlevar, precioPresupuestado}>` · `perteneceAObra(materialId, obraId): bool` · `crearFueraDePresupuesto(obraId, nombre, unidad, userId): int` (dentro de la transacción de Gastos) | RN-GAS-14, 24, 25 |
| `Rendicion\Contracts\CatalogoDeRubros` | usa (**lo define `rendicion.md` §12.4.3**) | `imputables(): list<{id, clave, nombre, cargaPorPeriodo}>` (13, en orden) · `resumeEn(clave): ?clave` | RN-GAS-12 (los 11 que se imputan en una factura = `imputables` sin los de carga por período), `GET /api/gastos/opciones` |
| `Identidad\Contracts\Permisos` | usa | `puede`, `alcance` | §8 |
| `Gastos\Contracts\GastadoDeObras` | Gastos **implementa** (`GastadoDeObrasEloquent`, bind en `GastosServiceProvider`) | firma en `rendicion.md` §12.4.1: `totalesPorRubro`, `renglonesDeObra`, `contarRenglonesPorMaterial` | Rendición (W-11, W-12, W-36, W-37, W-44, W-48, `AvanceGastoDeObra`) · §12.5 |
| Eventos `GastoImputado`, `GastoCorregido`, `GastoAnulado` (`Gastos\Domain\Events`) | Gastos **publica** (después del commit) | `{gastoId, obraIds: list<int>}` (`obraIds` sin el "sin obra"; en `GastoCorregido`, antes ∪ después) | Rendición invalida el cache de esas obras (ADR-007, `rendicion.md` §8) |
| `Shared\Busqueda\FuenteBusqueda` | implementa | `puedeBuscar`, `buscar` | RN-GAS-44 |
| `Shared\Adjuntos\AutorizaAdjuntos` | implementa (`GastoAdjuntos`) | `puedeVer`, `puedeEditar` | §4.4 |
| `Gastos\Contracts\CargaInicialDeGastos` | Gastos **implementa** (bind en `GastosServiceProvider`); la usa Importacion | `cargar(actorId, obraId, fechaCorte, tipo (gastado · retirado), list<RenglonInicial{rubroClave, concepto, cantidad, precio, ?materialId}>, list<string> historial): gastoId` (origen `saldo_inicial`, proveedor genérico, `GastoImputado` después del commit) · `vigente(obraId, tipo): ?CargaInicialVigente{gastoId, fechaCorte, cargo, cargadaEl, porClave}` · `renglonesHastaFecha(obraId, fecha): int` (otros orígenes, no anulados). Rechaza con `CargaRechazada` (los textos de la carga) | `importacion.md` §11.4, RN-IMP-23…29 |

Gastos usa los contratos de materiales y rubros de Rendición y Rendición usa el de gastos de Gastos: los tres van por
`Contracts/` (ADR-003), sin que ninguno use la `Infrastructure` del otro. La FK `gasto_renglones.rubro_id → rubros`
se mantiene (el catálogo es una tabla compartida, `modelo-datos.md`), pero el código de Gastos lee el catálogo por
`CatalogoDeRubros`, no por el modelo `Rendicion\Domain\Models\Rubro`.

### 12.5 Lo que Gastos le garantiza a Rendición: `Gastos\Contracts\GastadoDeObras`

La firma (interfaz, `FiltroRenglones` y `RenglonImputado`) es **una sola** y está en
[`rendicion.md` §12.4.1](rendicion.md); acá no se repite. Gastos la implementa en `GastadoDeObrasEloquent` y garantiza,
con un test por punto (§13, fila F-15 · F-17 · F-49…F-53 · F-66):

1. **Anulados nunca vienen** en `totalesPorRubro` ni en `renglonesDeObra`; `contarRenglonesPorMaterial` **sí** los
   cuenta (para que Rendición no deje quitar un material referenciado, RN-REN-53).
2. **Sin obra no viene** (solo renglones con `obra_id`); el rubro va **como se imputó** (EPP aparte: sumarlo a INSUMOS es
   de Rendición).
3. Importes como string decimal escala 2 y cantidades escala 3, **con signo**; lo único negativo son las devoluciones
   (resuelta DU-G05).
4. `RenglonImputado.fecha` = `gasto_renglones.fecha` (la **fecha efectiva**): en facturas, la de la factura; en gastos
   sin comprobante, la del ticket; en montos por período, `periodo_hasta` (resuelta DU-G01 y DU-R04). `FiltroRenglones`
   filtra y ordena por esa fecha.
5. `totalesPorRubro(obraIds)` resuelve **todas las obras en una consulta** (`GROUP BY obra_id, rubro_id`); una obra sin
   renglones viene con `[]`. `renglonesDeObra` trae proveedor y cabecera sin N+1.
6. `material_id` apunta siempre a un material de la **misma obra** del renglón (RN-GAS-14); todo retiro tiene material
   (de la lista o fuera de presupuesto, RN-GAS-24).
7. Horas y fijos: `origen = monto_periodo`, rubro `fijos` o `horas_liquidadas`, `periodo_desde ≤ periodo_hasta`, un
   renglón por gasto (RN-GAS-27).
8. Las cargas de W-46 se autorizan como dice §8.1 (resuelta DU-R05).
9. **Parte de horas** (DU-C02, RN-GAS-57): `FiltroRenglones.detallePorPersona` (por defecto `false`) decide cómo
   vienen sus renglones. Con `false`, uno por parte y obra con las horas y el total sumados, `resumido = true`,
   `empleado = null` (y `precioUnitario` sin sentido: Rendición no lo muestra); con `true` (quien mira tiene
   `gastos = editar` sobre esa obra, lo decide Rendición), uno por persona con `empleado`. Los totales por rubro no
   cambian. `RenglonImputado` suma al final `?string $empleado = null` y `bool $resumido = false`.

Qué saca Rendición de cada cosa: **por obra + rubro**, `totalesPorRubro`; **renglones** (últimos gastos, W-36, W-37,
W-48), `renglonesDeObra` con `FiltroRenglones` (`rubroClaves`, `desde`/`hasta`, `soloConMaterial`, `orden`, `limite`);
**retirado por material**, la suma de `cantidad` de los renglones con ese `materialId` (devoluciones incluidas, ADR-007);
**fuera de presupuesto (W-48)**, los renglones cuyo material es `fuera_de_presupuesto` (Rendición conoce sus
materiales); **"Facturas" por proveedor (W-37)**, contando `gastoId` distintos con `tipoDocumento = factura`; la
**etiqueta del documento**, con `origen`, `numero` y el período, según la tabla única de RN-GAS-29 / RN-REN-29. La
rendición se invalida con los tres eventos de §12.4.

## 13. Trazabilidad F-XX → RN → test

| F-XX | Reglas | Tests (PHPUnit, `tests/Feature/Gastos/…`, `tests/Unit/Gastos/…`) |
|---|---|---|
| F-39 | RN-GAS-03, 04, 09, 16 | `test_carga_factura_con_renglones` · `test_factura_exige_numero_fecha_y_proveedor` · `test_factura_exige_al_menos_un_renglon` · `test_renglon_exige_concepto_cantidad_precio_y_obra_o_sin_obra` · `test_fecha_futura_en_argentina_es_rechazada` · `test_cargar_dispara_gasto_imputado_con_las_obras_despues_del_commit` · `test_sin_gastos_editar_no_carga` · `test_doble_envio_crea_un_solo_gasto` |
| F-40 | RN-GAS-12, 13 | `test_renglon_con_obra_exige_rubro` · `test_renglon_sin_obra_admite_rubro_vacio` · `test_fijos_y_horas_no_se_imputan_en_una_factura` · `test_factura_a_obra_terminada_se_acepta` · `test_factura_a_obra_sin_presupuesto_se_acepta` · `test_epp_se_guarda_como_epp` |
| F-41 | RN-GAS-10, 11, 15, §4.5 | Unit `TotalDeRenglonTest`: `test_redondea_mitad_hacia_arriba`, `test_negativo_redondea_alejandose_de_cero`, `test_total_del_gasto_es_la_suma_sin_redondear_de_nuevo`; Feature: `test_backend_ignora_el_total_que_manda_el_front` · `test_precio_con_mas_de_dos_decimales_es_rechazado` · `test_cantidad_con_mas_de_tres_decimales_es_rechazada` · `test_cantidad_negativa_solo_en_devolucion` · `test_total_demasiado_grande_es_rechazado` · `test_hasta_doscientos_renglones` |
| F-42 | RN-GAS-05, 06 | `test_factura_rechaza_el_proveedor_generico` · `test_proveedor_inexistente_devuelve_422` · E2E `carga_factura_con_alta_rapida_de_proveedor` |
| F-43 | §4.4 | `test_factura_acepta_archivo_o_link` · `test_adjunto_de_otro_contexto_no_se_asocia` · `test_adjunto_de_gasto_fuera_de_alcance_devuelve_404` · `test_gasto_anulado_conserva_sus_adjuntos` |
| F-39 (duplicado) | RN-GAS-07, 08 | Unit `NumeroComprobanteTest` (formas de punto de venta–número); Feature: `test_misma_factura_del_mismo_proveedor_es_rechazada` · `test_mismo_numero_de_otro_proveedor_se_acepta` · `test_factura_anulada_se_puede_volver_a_cargar` · `test_dos_cargas_simultaneas_de_la_misma_factura_dejan_una` · `test_aviso_de_factura_existente` |
| F-44 | RN-GAS-17, 18, 20, 21 | `test_gasto_sin_comprobante_usa_el_proveedor_generico` · `test_rendido_por_es_obligatorio_en_gastos_de_obra_rendidos` · `test_rendido_por_acepta_texto_libre` · `test_cada_renglon_sin_documento_tiene_su_fecha` · `test_sin_documento_admite_un_proveedor_de_la_base` |
| F-47 | RN-GAS-19, 22…26 | `test_consumo_de_stock_exige_obra` · `test_amortizacion_una_por_obra` · `test_dos_amortizaciones_simultaneas_dejan_una` · `test_retiro_exige_numero_de_remito_y_proveedor` · `test_retiro_con_material_de_otra_obra_es_rechazado` · `test_retiro_exige_material_de_la_lista_o_no_esta_en_la_lista` · `test_retiro_de_lo_que_no_esta_en_la_lista_crea_material_fuera_de_presupuesto_con_su_unidad` · `test_remito_repetido_avisa_y_se_guarda_si_se_confirma` · `test_devolucion_exige_cantidad_negativa` · `test_devolucion_resta_en_el_gastado` · `test_no_hay_tipo_nota_de_credito` · `test_amortizacion_y_devolucion_piden_rendicion_editar` · `test_retiro_con_control_cantidad_editar_sin_rendicion_editar_se_acepta` · `test_corregir_un_retiro_pide_gastos_editar` |
| F-54 (dato) | RN-GAS-27, 28 | `test_monto_por_periodo_exige_desde_y_hasta` · `test_monto_por_periodo_solo_fijos_u_horas` · `test_monto_por_periodo_tiene_un_solo_renglon` · `test_fecha_del_periodo_es_el_hasta_aunque_sea_futura` · `test_desde_posterior_a_hoy_es_rechazado` · `test_superposicion_avisa_y_se_guarda_si_se_confirma` · `test_periodo_pide_rendicion_editar` |
| F-45 | RN-GAS-29…35 | `test_listado_ordena_por_fecha_desc` · `test_filtros_se_combinan` (uno por filtro) · `test_fecha_por_defecto_es_el_mes_en_curso` · `test_filtro_por_obra_suma_solo_sus_renglones` · `test_total_de_lo_filtrado_es_de_todo_no_de_la_pagina` · `test_agrupar_por_obra_reparte_la_factura` · `test_agrupar_por_rubro_deja_epp_aparte` · `test_anulado_se_lista_tachado_y_no_suma` · `test_proveedor_generico_se_muestra_como_guion` |
| F-46 · F-14 | RN-GAS-36…42 | `test_corregir_registra_valor_anterior_y_nuevo` · `test_corregir_sin_cambios_no_registra` · `test_corregir_obra_de_un_renglon_avisa_las_dos_obras` · `test_no_se_cambia_tipo_ni_origen` · `test_corregir_con_version_vieja_devuelve_409` · `test_anular_exige_motivo` · `test_anulado_deja_de_sumar` · `test_anulado_no_se_corrige_ni_se_vuelve_a_anular` · `test_no_hay_ruta_para_borrar_gastos` · `test_historial_informa_adjuntos_por_nombre_sin_url` · `test_cualquiera_con_gastos_editar_corrige` |
| F-57 | RN-GAS-43 | `test_gastado_por_obra_agrupa_por_obra_con_fila_sin_obra` · `test_gastado_por_obra_excluye_anulados` · `test_gastado_por_obra_resta_devoluciones` · `test_gastado_por_obra_con_alcance_propias_muestra_solo_sus_obras` · `test_gastado_por_obra_en_una_consulta` |
| F-03 | RN-GAS-44 | `test_buscador_encuentra_por_numero_sin_ceros_ni_guiones` · `test_buscador_encuentra_remitos` · `test_buscador_de_gastos_respeta_el_alcance` · `test_grupo_gastos_no_viaja_sin_permiso` · `test_buscador_marca_los_anulados` |
| F-15 · F-17 · F-49…F-53 · F-66 | RN-GAS-45…47, §12.5 | `GastadoDeObrasTest` (contrato de `rendicion.md` §12.4.1): `test_totales_por_rubro_excluye_anulados_y_sin_obra` · `test_totales_por_rubro_de_varias_obras_en_una_consulta` · `test_renglones_de_obra_filtra_y_ordena_por_fecha_efectiva` · `test_fecha_efectiva_de_un_periodo_es_periodo_hasta` · `test_renglones_con_material_incluyen_devoluciones_negativas` · `test_contar_renglones_por_material_incluye_anulados` · `test_renglones_de_obra_sin_n_mas_1` · `test_compra_a_stock_sin_obra_y_consumo_con_obra_no_se_cuentan_dos_veces` · `test_eventos_traen_todas_las_obras_afectadas` |
| F-61 / ADR-006 | RN-GAS-48, 49 | `test_alcance_propias_ve_solo_sus_renglones` · `test_alcance_propias_total_es_de_sus_renglones` · `test_gasto_sin_renglones_en_sus_obras_devuelve_404` · `test_alcance_propias_baja_el_archivo_de_la_factura` · `test_editar_propias_no_imputa_a_otras_obras_ni_sin_obra` · `test_editar_propias_no_corrige_facturas_con_renglones_ajenos` |
| Idempotencia | RN-GAS-50 | `test_cada_post_de_gastos_acepta_idempotency_key` (cargar y anular) |
| F-39 · F-54 (DU-C02) | RN-GAS-51…57 | `ParteDeHorasTest` (carga, validaciones, rubro fijo, fecha = hasta, superposición, permisos, sugerencias, corregir y anular) · `PrivacidadDelParteTest` (W-34, planilla, historial y, en `tests/Feature/Rendicion`, W-11 y W-36 resumidos sin `gastos = editar`) |
| F-45 · F-46 (DU-C03) | RN-GAS-58…63 | `EstadoDePagoTest` (marcar, desmarcar, fecha, solo facturas, anulada, versión, idempotencia, historial, permitido/denegado/IDOR) · `FiltroDePagoTest` (W-33, total de lo filtrado, sin permiso, buscador) · `ConfiguracionInicialTest` (fila `pagos`) |
| F-02 | RN-GAS-01 | front `menuPara.test.ts` → `muestra_gastos_con_ver_marcado`, `oculta_cargar_sin_editar` |
| F-13 | §11 | E2E (Playwright, 390 px): `celular_rinde_tickets_con_foto` · `celular_ve_gastos_en_tarjetas` |

## 14. Dudas

Resueltas que se aplican acá (`docs/dudas.md`): DU-17 (retiros, una sola carga: RN-GAS-14, 24, 25, §12.5), DU-18
(período desde–hasta: RN-GAS-27), DU-08 y DU-19 (no tocan Gastos: las usa Rendición), DU-A04 (Ingeniería ve solo sus
obras: RN-GAS-48), DU-A15 (obra Terminada admite gastos: RN-GAS-13), DU-A18 (genérico "Gastos sin comprobante"),
DU-23 / ADR-014 (archivo o link: §4.4).

**De esta especificación, resueltas el 24-nov-2026 (Juani: va la propuesta):**
DU-G01 (fecha por renglón: RN-GAS-21, §4.2, §12.5) · DU-G02 (W-35: qué se carga, proveedor opcional y "rendido por"
obligatorio: RN-GAS-17, 18, 20) · DU-G03 (retiros y devoluciones en W-46, aplicada con el contrato `MaterialesDeObra`:
RN-GAS-14, 24, 25, §4.6 punto 5) · DU-G04 (IVA cuando el comprobante no lo discrimina: §4.5) · DU-G05 (no hay nota de
crédito: RN-GAS-25; reemplaza a DU-R03) · DU-G06 (total con filtro y alcance "solo sus obras" en una factura repartida:
RN-GAS-31, 48) · DU-G07 (buscador: RN-GAS-44) · ~~DU-G08~~ **reemplazada por DU-R05** (permiso de las cargas desde la
rendición: §8.1) · DU-G09 (superposición de montos por período: RN-GAS-28; la fecha, la de DU-R04) · DU-G10 (número de
factura y "la misma factura": RN-GAS-07, 08) · DU-G11 (corregir y anular: RN-GAS-34, 37, 41).

**Pedidos del cliente (Bruno, 26-nov; van tal cual, `docs/dudas.md` "Cliente · pedidos de Bruno"):** DU-C02 (parte de
horas: §5.13, RN-GAS-51…57) · DU-C03 (facturas pagas o impagas: §5.14, RN-GAS-58…63).

**De `rendicion.md` que se aplican acá:** DU-R04 (sección de W-46 y fecha = `periodo_hasta`: RN-GAS-22, 27), DU-R05
(permisos de W-46: §8.1), DU-R06 (cantidad en la unidad del material: RN-GAS-14), DU-R07 (etiquetas de documento y
últimos 10: RN-GAS-29, 46), DU-R08 (contratos: §12.4, §12.5).

## 15. Alineación con `rendicion.md` (24-nov-2026)

Lo que se cambió en las dos especificaciones para que digan lo mismo (a favor del contrato firmado, la tabla de permisos
aprobada y `rendicion.md` §12.4.1):

| Tema | Antes (en esta especificación) | Ahora (igual en las dos) |
|---|---|---|
| Contrato Gastos → Rendición | `GastosDeObra` con 6 métodos propios | `GastadoDeObras` con la firma de `rendicion.md` §12.4.1 (§12.5 acá solo dice qué garantiza) |
| Eventos | `{gastoId, obraIds, materialIds}` | `{gastoId, obraIds}` (W-48 no se cachea) |
| Materiales | `opciones(ObraId)` · `esDeLaObra`; endpoint `GET /api/gastos/materiales-opciones` | `MaterialesDeObra`: `opciones` (con `precioPresupuestado`) · `perteneceAObra` · `crearFueraDePresupuesto`; un solo endpoint, el de Rendición |
| Fuera de presupuesto | retiro sin material, sin unidad ("—") | material fuera de presupuesto creado al cargar, con su unidad (W-48 firmada) |
| Rubros | modelo `Rubro` de Rendición por FK | `CatalogoDeRubros` (con `cargaPorPeriodo`); la FK queda |
| Permiso de W-46 | `gastos = editar` (DU-G08) | `rendicion = editar`; retiros también `control_cantidad = editar`; corregir y anular, `gastos = editar` (DU-R05) |
| Horas y fijos | pantalla en `rendicion.md`; fecha = "hasta" u hoy; varias obras por gasto | sección de W-46; fecha = `periodo_hasta` siempre; un renglón por gasto; mismos textos |
| Nota de crédito | — | no hay (DU-G05); el renglón 8 del caso de referencia de `rendicion.md` pasa a ser una devolución |
| Etiquetas de documento | "Remito N", "Horas 01/10–15/10", "Saldo inicial" | "Retiro · remito N", "Período 1 al 15-oct", "Carga inicial" (RN-REN-29) |
| Redondeo | "mitad hacia arriba" | "mitad para arriba en valor absoluto" (las dos dicen lo mismo con las mismas palabras) |
| Material de otra obra | "Ese material no es de la lista de …" | "El material Cemento x 50 kg es de otra obra: quitá el material o elegí uno de esta obra." |


## 16. Textos de la implementación (resuelta DU-G15, 25-nov-2026)

Textos que la implementación usa en situaciones donde esta especificación no tenía uno. Quedan como están y forman
parte de la especificación (informe `docs/qc/semana-3-tanda-1.md` §4, filas "queda"). Donde la especificación sí tenía
un texto, la implementación usa el de la especificación.

**Mensajes de la API (`message` y errores por campo):**

| Situación | Texto |
|---|---|
| Corrección guardada (PATCH) | "Guardamos los cambios." |
| Gasto anulado | "Anulamos la factura." / "Anulamos …" (con la etiqueta del documento) |
| Carga guardada (RN-GAS-16, RN-GAS-26), siempre con "✓ " adelante | "✓ Factura cargada. …" · "✓ Gasto cargado. Ya se restó en la rendición de …" / "✓ Gasto cargado. No va a ninguna obra (todo sin obra)." · "✓ Retiro cargado. …" · "✓ Amortización cargada." · "✓ Devolución cargada. …" · "✓ Período cargado. …" |
| Factura repetida de una obra que quien carga no ve (RN-GAS-08, alcance; sin id, fecha ni quién) | "Esa factura ya está cargada: 0003-00012345 de Corralón Centro." |
| Carga desde W-46 con un renglón de otra obra | "Todos los renglones van a la obra de la rendición." |
| Carga de un solo renglón (amortización, período) con más de uno | "Esta carga lleva un solo renglón." |
| Corrección con un renglón `id` que no es del gasto | "Ese renglón no es de este comprobante." |
| Falta `origen` | "Elegí qué se carga." |
| Valor fuera de la lista | "El valor no es válido." |
| Más de un adjunto donde va uno | "Son demasiados (máximo 1)." |
| Validaciones genéricas | "Completá este campo." · "Tiene que ser texto." · "Es demasiado largo…" · "Tiene que ser un número…" · "Está repetido." |
| Fecha mal escrita | "Escribí la fecha como dd/mm/aaaa, por ejemplo 31/12/2099." |
| Cantidad con más de 3 decimales | "Renglón N: hasta 3 decimales en la cantidad." |
| Cantidad fuera de rango | "Renglón N: la cantidad es demasiado grande." |
| "No está en la lista" sin nombre o sin unidad | "Renglón N: escribí el material." / "Renglón N: poné la unidad." |
| Historial de cargas que no son factura | "… cargó el gasto sin documento / el retiro / el período …", "… cambió quién rindió", "… cambió el período", "El sistema …" |
| Grupo de W-33 sin rubro | "(sin rubro)" |

**Pantallas:**

| Dónde | Texto |
|---|---|
| Carga, cantidad | "Hasta 3 decimales en la cantidad." · "Escribí la cantidad con números, por ejemplo 2,5." |
| Carga, precio | "Escribí el precio con números, por ejemplo 9.800,50." |
| Carga, "No está en la lista" | "Escribí el material." · "Escribí la unidad." · "Unidad" · "Por ejemplo: par, bolsa, m²." |
| Carga, material | "Elegí primero la obra." · "No pudimos traer los materiales…" · "Sin material" · " · fuera de presupuesto" |
| Factura repetida (RN-GAS-08) | "Ver la factura cargada" (link a W-34) |
| Aviso de superposición o repetido, sin cargar | "No, revisar" |
| Volver | "Volver a Gastos cargados" · "Volver al gasto" · "‹ Volver a la obra" |
| W-34, corregir | título "Editar · {documento}" · "Tu sector puede ver los gastos pero no corregirlos…" |
| W-34, historial vacío | "Todavía no hay cambios registrados." |
| W-34, anular | "Anulando…" · título "Anular · {documento}" cuando no es factura · "No va a ninguna obra (todo sin obra). No se puede deshacer." |
| Carga, obra del renglón | "Buscar obra" · "Por nombre del trabajo o N.º de presupuesto" · "No pudimos traer las obras. Probá de nuevo en un momento." |
| Carga, proveedor | "Buscar proveedor" · "Por nombre o CUIT." · "No pudimos traer los proveedores. Probá de nuevo en un momento." |
| Carga, rubro | "Sin rubro" · "Elegí…" |
| Carga, detalle del renglón | "Detalle (opcional)" · "La cotización o una aclaración." |
| W-35, rendido por | "Elegí de la lista o escribí el nombre (por ejemplo, supervisor de obra)." |
| Adjuntos | "Subir archivo, sacar una foto o pegar link" · "Archivo, foto o link." · "Subir archivo o pegar link" · "Factura (opcional)" |
| W-46, devoluciones | "Va en negativo: se descuenta." · "Si no elegís material: por ejemplo, “Cemento x 50 kg (sobrante)”." |
| W-46, amortización | "Rubro: AMORTIZACION" |
| W-46, períodos | "Un monto total por período, sin detalle por persona: quincenas…" · "Por defecto: “…”." |
| Confirmación de una carga sin documento (RN-GAS-16, en singular como el `message`) | título "Gasto cargado" · "Ver el gasto" · "Cargar otro" (la factura sigue "Factura cargada" · "Ver la factura" · "Cargar otra") |
| W-33, filtro Obra o Proveedor elegido que no viene en la búsqueda ni en las filas (DU-G12) | "Obra elegida" · "Proveedor elegido" |
| W-33 mientras busca (solo para el lector de pantalla; se ven filas grises) | "Buscando gastos…" |

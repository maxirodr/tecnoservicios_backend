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
> Lo nuevo de esta especificación está en **DU-G01…DU-G11** (abiertas, con propuesta; decide Juani). Las reglas que
> dependen de una de ellas dicen "propuesta DU-GNN" y se construyen con la propuesta.
> La **rendición** (W-11, W-12, W-36, W-37, W-44, W-48, F-48…F-54, F-66) está en [`rendicion.md`](rendicion.md); acá
> está **qué datos de gastos lee** (§12.5).
> Personas, obras, proveedores, números e importes de este archivo son **ficticios** (los de las maquetas).

## 1. Propósito

"Cada factura o gasto se carga una sola vez, y cada renglón va a su obra y su rubro. En el mismo momento se resta en
la rendición de cada obra" (cap. 8). Hoy la misma factura se carga "en facturas, después en una rendición y después en
una obra" (Bruno, 16-oct); con el sistema se carga **una vez**, renglón por renglón, como su libro de gastos, y la
rendición, el % gastado de la obra, el control por cantidad y lo gastado por proveedor salen solos de esos renglones
(ADR-007). También entran los gastos que hoy rinden sin factura (tickets, combustible, consumo de stock, amortización,
retiros con remito, devoluciones y los montos de horas y fijos). **No** hay vencimientos, condición ni estado de pago,
ni cuenta corriente de proveedores: siguen en Tango (cap. 8, D-033, D-040). El sistema no modela aprobaciones: la
factura llega ya aprobada (cap. 4.3).

## 2. Funcionalidades y pantallas cubiertas

| F-XX | Qué | Pantalla | Semana 3 |
|---|---|---|---|
| F-39 | Cargar una factura o gasto: tipo de documento, número, fecha, proveedor de la base y renglones con concepto, cantidad, precio unitario y total | W-10 | completa |
| F-40 | Repartir por renglón: cada renglón a una obra y un rubro; "sin obra" para stock, vehículos u oficina; un renglón a dos obras se divide | W-10 | completa |
| F-41 | Total = cantidad × precio, calculado solo; bonificación en el precio, moneda extranjera convertida, adelanto y saldo en dos renglones, sin IVA, cotización en el detalle | W-10 | completa |
| F-42 | Elegir el proveedor de la base, que trae sus datos; alta rápida si no existe | W-10 → W-38 | completa (el endpoint de alta ya existe, `proveedores.md` RN-PRV-10) |
| F-43 | La factura como **archivo o link** (opcional); el papel queda en la oficina | W-10 · W-34 | completa (ADR-014) |
| F-44 | Gastos sin comprobante: sin documento, proveedor genérico, "rendido por" | W-35 | completa |
| F-47 | Otros gastos sin factura: combustible por carga, consumo de stock, amortización (uno por obra), retiros con remito (archivo o link), devoluciones que restan | W-35 · W-46 | completa |
| F-45 | Gastos cargados: listado con filtros por obra, proveedor, rubro, fecha y tipo, con el total de lo filtrado | W-33 | completa |
| F-46 | Ver, corregir o anular un gasto, con quién lo cargó y cuándo | W-34 | completa |
| F-57 | Gastos del proveedor: lo imputado por obra, en su ficha | W-14 "Gastado por obra" | completa (Gastos implementa el puerto `GastadoPorProveedor`, `proveedores.md` §10.3) |
| F-54 (dato) | HORAS LIQUIDADAS y FIJOS como monto total por período (DU-18) | la pantalla la define `rendicion.md` | el registro, sus reglas y su endpoint (§5.5) |
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
| Qué se carga | `origen` | De qué carga vino: `factura`, `sin_comprobante`, `combustible`, `consumo_stock`, `amortizacion`, `retiro_remito`, `devolucion`, `monto_periodo`, `saldo_inicial` (semana 4). No se edita. |
| Retiro · remito | `origen = retiro_remito` | Material retirado (hoy, de Tecnobahía) con su remito; la factura queda en Tango (C14). |
| Devolución | `origen = devolucion` | Material devuelto: renglón con cantidad y total negativos, resta (C15). |
| Amortización | `origen = amortizacion` | Amortización de herramientas: un renglón por obra con el monto a mano. |
| Monto por período | `origen = monto_periodo` | HORAS LIQUIDADAS o FIJOS: un monto total por obra y período desde–hasta (C18, DU-18). |
| Material de la lista | `material_id` → `materiales` (Rendición) | Material presupuestado de la obra (W-48, DU-17); un renglón asociado cuenta como retirado o pedido. |
| Fuera de presupuesto | retiro sin `material_id` | Lo retirado que no está en la lista (W-48, DU-17). |
| Anular | `anulado_at` | Dejarlo sin efecto: queda tachado y deja de sumar. No se borra (W-34). |

## 4. Datos

### 4.1 Gasto (`gastos`) — la cabecera

| Campo | Tipo | Oblig. | Validación | Fuente |
|---|---|---|---|---|
| tipo_documento | `factura` · `sin_documento` | **Sí** | lo fija la carga (§4.3); no se cambia al corregir (RN-GAS-41) | F-39 · W-10 "Factura / Sin documento" · sus rendiciones (solo esos dos valores) |
| origen | ver §4.3 | **Sí** | lo fija la carga; no se edita | modelo-datos · W-33 ("LOGISTICA · combustible") |
| numero | texto ≤ 40 | factura **Sí** · retiro **Sí** · resto no | sin espacios al borde; normalizado (RN-GAS-07) | F-39 · W-10 "Número" · modelo-datos |
| fecha | fecha | **Sí** | no posterior a hoy (Argentina); en `monto_periodo`, el "hasta" o hoy si el "hasta" es posterior (RN-GAS-27) | F-39 · W-10 · W-35 "Fecha de la rendición" |
| proveedor_id | ref. proveedor | **Sí** (el genérico si no hay otro, §4.3) | existe; el genérico **no** en una factura (RN-GAS-06) | F-42 · F-44 · RN-PRV-14 |
| rendido_por | texto ≤ 120 | según §4.3 | sin espacios al borde | F-44 · W-35 "Laura ▾ (o texto libre)" · C12 |
| periodo_desde, periodo_hasta | fecha · fecha | `monto_periodo` **Sí** | hasta ≥ desde | F-54 · DU-18 |
| adjuntos | 0 a 10, contexto `comprobante_gasto` (o 0 a 1 `remito` en un retiro), cada uno archivo **o** link | No | `obras.md` §4.8 | F-43 · F-44 · F-47 · ADR-014 |
| cargado_por, created_at | ref. usuario · fecha y hora | automático | quien carga | F-46 · W-34 "Cargó: Diego · 17-oct 10:42" |
| updated_by, updated_at | ref. usuario · fecha y hora | automático | última corrección | W-34 "Última edición: Diego · 17-oct 11:05" (ver §4.6) |
| anulado_at, anulado_por, motivo_anulacion | fecha y hora · ref. usuario · texto ≤ 2000 | al anular | motivo no vacío | W-34 "Anular" · modelo-datos |

El **total** del gasto no se guarda ni se tipea: es la suma de sus renglones (RN-GAS-11).

### 4.2 Renglón (`gasto_renglones`)

| Campo | Tipo | Oblig. | Validación | Fuente |
|---|---|---|---|---|
| orden | entero | automático | el orden en que se cargaron | modelo-datos |
| fecha | fecha | **Sí** | en una factura, siempre la de la factura; en un gasto sin comprobante, la del ticket (≤ hoy); por defecto, la de la cabecera | W-35 columna "Fecha" · su libro (la fecha va por fila) · **propuesta DU-G01** |
| concepto | texto ≤ 250 | **Sí** | sin espacios al borde; no vacío | F-39 · W-10 "Concepto" |
| cantidad | decimal, 3 decimales | **Sí** | ≠ 0; negativa **solo** en devolución (y ahí obligatoria negativa) | F-39 · ADR-008 · C15 |
| precio_unitario | decimal, 2 decimales | **Sí** | ≥ 0 | F-39 · F-41 · ADR-008 |
| total | decimal, 2 decimales | automático | `cantidad × precio_unitario`, redondeado (RN-GAS-10) | F-41 "calculado solo" |
| obra_id | ref. obra · `null` = sin obra | **Sí elegir** (una obra **o** "(sin obra)") | la obra existe, en cualquier estado; con alcance "solo sus obras", una suya | F-40 · W-10 "(sin obra)" · DU-A15 |
| rubro_id | ref. rubro | **Sí** si hay obra; opcional sin obra | uno de los permitidos para esa carga (§4.3) | F-40 · cap. 9 · modelo-datos (CHECK) |
| material_id | ref. material de la obra | No | de la lista de **esa** obra (contrato de Rendición) | DU-17 · W-46 · W-48 |
| detalle | texto ≤ 250 | No | — | F-41 "la cotización en el detalle" · modelo-datos |

### 4.3 Qué pide cada carga (`origen`)

| Carga | Pantalla | `tipo_documento` / `origen` | Número | Proveedor | Rendido por | Obra del renglón | Rubros que acepta | Cantidad | Material | Adjuntos |
|---|---|---|---|---|---|---|---|---|---|---|
| Factura | W-10 | `factura` / `factura` | **Sí** | **de la base, no el genérico** | — | obra o "(sin obra)" | los 11 que se imputan (RN-GAS-12) | > 0 | opcional (DU-17) | 0 a 10 `comprobante_gasto` |
| Gastos de obra sin ticket | W-35 | `sin_documento` / `sin_comprobante` | No | el genérico (propuesta DU-G02: o uno de la base) | **Sí** (propuesta DU-G02) | obra o "(sin obra)" | los 11 | > 0 | — | 0 a 10 `comprobante_gasto` (tickets, fotos) |
| Combustible por carga | W-35 | `sin_documento` / `combustible` | No | ídem | No (el chofer, si se quiere: DU-G02) | obra o "(sin obra)" | los 11 (LOGISTICA por defecto) | > 0 | — | ídem |
| Consumo de stock propio | W-35 | `sin_documento` / `consumo_stock` | No | ídem | No | **obra** (el consumo es de una obra, C10) | los 11 | > 0 | — | ídem |
| Amortización de herramientas | W-46 | `sin_documento` / `amortizacion` | No | el genérico | — | la obra de la pantalla | **AMORTIZACION** | 1 (el importe va en el precio) | — | — |
| Retiro con remito | W-46 | `sin_documento` / `retiro_remito` | **Sí** (N.º de remito) | **de la base** (propuesta DU-G03) | — | la obra de la pantalla | los 11 (MATERIALES por defecto) | > 0 | de la lista, o ninguno = fuera de presupuesto | 0 a 1 `remito` |
| Devolución de materiales | W-46 | `sin_documento` / `devolucion` | No | de la base, opcional (si no, el genérico) | — | la obra de la pantalla | los 11 (MATERIALES por defecto) | **< 0** | de la lista, opcional | — |
| Monto por período | `rendicion.md` (F-54) | `sin_documento` / `monto_periodo` | No | el genérico | — | **obra** | **FIJOS** o **HORAS LIQUIDADAS** | 1 (el monto va en el precio) | — | — |
| Saldo inicial | Importación (semana 4) | `sin_documento` / `saldo_inicial` | No | el genérico | — | obra | cualquiera | 1 | — | — |

### 4.4 Adjuntos (ADR-014, `identidad.md` §6.6)

| Contexto | Dueño | Cantidad | Ver / bajar | Asociar / quitar |
|---|---|---|---|---|
| `comprobante_gasto` | gasto (factura o sin comprobante) | 0 a 10 | quien puede ver el gasto (`gastos` ≥ ver, con alcance) | `gastos = editar` sobre ese gasto |
| `remito` | gasto con `origen = retiro_remito` | 0 a 1 | `gastos` ≥ ver **o** `control_cantidad` ≥ ver, con alcance | `gastos = editar` |

W-10 dice "Link a la factura (opcional) · Pegar link de Drive, si la escanean · el papel queda en la oficina": con
ADR-014 el campo es **"Factura (opcional) · Subir archivo o pegar link"**. Un gasto **anulado conserva** sus adjuntos
(RN-IDE-48). Alias de `Relation::morphMap` (en `GastosServiceProvider`): `gasto`. Las implementaciones de
`AutorizaAdjuntos` (`GastoAdjuntos`) se registran por contexto en `ContextosAdjuntos`.

### 4.5 Importes, cantidades y redondeo (F-41, ADR-008, C9)

- Pesos argentinos, **netos sin IVA** (F-41, C9). No hay campo de IVA, de moneda ni de bonificación: la bonificación va
  ya aplicada al precio; la compra en moneda extranjera se carga convertida a pesos y la cotización se escribe en el
  **detalle** del renglón ("USD 1.200 a 1.050 = $ 1.260.000"). Qué es "neto" cuando el comprobante no discrimina IVA:
  propuesta DU-G04.
- Precio unitario con **hasta 2 decimales**; cantidad con **hasta 3** (m², kg, litros). Más decimales: 422 (no se
  redondea en silencio; el formulario avisa antes de guardar).
- **Total del renglón = cantidad × precio unitario, redondeado a 2 decimales "mitad hacia arriba"** (0,005 → 0,01; en
  negativos, −0,005 → −0,01). Lo calcula el backend (`Importe`, bcmath); el front lo muestra como vista previa.
- **Total del gasto = suma de los totales de sus renglones**, sin volver a redondear. Total por obra = suma de sus
  renglones.
- Topes: precio ≤ 9.999.999.999.999,99; cantidad ≤ 99.999.999.999,999; si el total no entra en 15 dígitos: 422 "El
  total del renglón es demasiado grande." Hasta **200 renglones** por gasto (técnica: una factura de ferretería trae ~30).
- En la API los importes y cantidades viajan como **string decimal** ("9800.00", "30.000"); en pantalla, formato es-AR
  con separador de miles y sin ",00" ("905.600", "9.800,50", "−39.200").

### 4.6 Ajustes al modelo de datos que pide esta especificación (para el DBA; no se cambian acá)

1. `gasto_renglones.fecha` (date, no nula; en facturas = `gastos.fecha`) — propuesta DU-G01.
2. `gastos`: `timestamps` y `updated_by` (W-34 "Última edición: Diego · 17-oct 11:05"); el modelo no los lista.
   `gasto_renglones`: `timestamps`.
3. `gastos.observaciones` no aparece en W-10, W-35 ni W-46: queda sin usar en la Etapa 1 (no se expone).
4. El único anti-duplicado `numero_vigente` se arma sobre el **número normalizado** (RN-GAS-07) y solo para
   `tipo_documento = factura` no anuladas, como dice el modelo.
5. Los renglones no tienen **unidad**: un retiro "fuera de presupuesto" (sin material) no la tiene; W-48 la muestra
   "—" (propuesta DU-G03; afecta a `rendicion.md`).

## 5. Reglas de negocio

### 5.1 Entrada al módulo (F-02, F-45; W-17, W-33)

- **RN-GAS-01** El ítem **Gastos** de la barra aparece con `gastos` ≥ Ver y abre **Gastos cargados** (W-33); con solo Ver, "Gastos (ver)" y sin botones de carga ni edición. Con `gastos = editar`, W-33 muestra **"+ Cargar factura / gasto"**, que abre W-10. En el celular, "Gastos" está en "Más" (W-41). *(F-02, W-17, W-33, RN-IDE-11, RN-IDE-12, DU-A12)*
- **RN-GAS-02** La carga es **una sola pantalla con dos modos**: arriba, **Tipo de documento: Factura · Sin documento**. "Factura" es W-10; "Sin documento" es W-35 (tickets, combustible, consumo de stock). Al cambiar de modo se conservan los renglones ya escritos (concepto, cantidad, precio, obra, rubro); se vacían número y proveedor. Amortización, retiros y devoluciones se cargan desde la rendición de la obra (W-46, §5.4); horas y fijos, desde la rendición (`rendicion.md`, §5.5). *(W-10 "Sin documento para lo que no tiene factura: tickets, combustible por carga y consumo de stock propio", W-35, W-11 "+ cargar renglón sin documento (W-46)")*

### 5.2 Cargar una factura (F-39…F-43; W-10, W-38)

- **RN-GAS-03** Cabecera de la factura: **Número**, **Fecha** y **Proveedor**, obligatorios; **Factura (opcional)**: archivo o link (0 a 10). Mensajes: "Poné el número de la factura.", "Poné la fecha.", "Elegí el proveedor." Abajo, "El papel queda en la oficina, como hoy." *(F-39, F-43, W-10, cap. 4.3, ADR-014)*
- **RN-GAS-04** **Fecha**: la de la factura, por defecto hoy (zona `config('app.zona_empresa')`); no puede ser posterior a hoy ("La fecha no puede ser posterior a hoy."); puede ser de cualquier día anterior (las facturas llegan después, también con la obra Terminada: DU-A15). *(F-39; técnica: una fecha futura es un error de tipeo)*
- **RN-GAS-05** **Proveedor**: se elige de la base con buscador (`GET /api/proveedores/opciones`, nombre o CUIT, RN-PRV-03) y **trae sus datos**: se ven su CUIT y su rubro habitual, y el rubro habitual se pone como rubro de los renglones que todavía no tienen rubro (cada renglón se puede cambiar). Si no existe, **"+ nuevo proveedor"** abre W-38 (alta rápida, con el aviso de parecidos); al guardar queda elegido y se vuelve a la factura sin perder nada. El link aparece solo con `proveedores = editar`. *(F-42, W-10, W-38, RN-PRV-08…10)*
- **RN-GAS-06** En una factura **no se puede elegir el proveedor genérico**: 422 "El proveedor genérico es solo para gastos sin documento." *(RN-PRV-14, F-44)*
- **RN-GAS-07** **Número**: se guarda sin espacios al borde y en mayúsculas. Si tiene la forma punto de venta–número (`3-12345`, `0003-00012345`, `00003 00012345`), se guarda **normalizado** con el punto de venta de al menos 4 dígitos y el número de 8: `0003-00012345` (con 5 dígitos de punto de venta queda `00003-00012345`). Si no tiene esa forma, se guarda tal cual. *(W-10 "0003-00012345"; propuesta DU-G10)*
- **RN-GAS-08** **Una factura no se carga dos veces**: no puede haber otra factura **no anulada** del **mismo proveedor** con el **mismo número normalizado**. Al salir del campo Número (o al elegir el proveedor) el formulario avisa: "Esa factura ya está cargada: 0003-00012345 de Corralón Centro (Diego, 17-oct)." con link a W-34; al guardar, 422 con el mismo texto y `errors.existente`. Lo garantiza el índice único (dos cargas a la vez: la segunda recibe el 422). El mismo número de **otro** proveedor se acepta. Una factura anulada no cuenta: se puede volver a cargar. *(criterio de aceptación n.º 2, modelo-datos `numero_vigente`; propuesta DU-G10)*
- **RN-GAS-09** **Renglones**: columnas **Concepto · Cantidad · Precio unitario · Total · Obra · Rubro** (como su libro de gastos) y "+ agregar renglón"; se quitan con "quitar". Al menos uno: "Cargá al menos un renglón." Cada renglón: concepto ("Escribí el concepto."), cantidad ("Poné la cantidad."; "La cantidad no puede ser 0."), precio ("Poné el precio unitario."; "El precio no puede ser negativo."), obra o "(sin obra)" ("Elegí la obra o '(sin obra)'.") y, si tiene obra, rubro ("Elegí el rubro."). Opcional: **detalle** (cotización, aclaraciones) y **material** (RN-GAS-14). Los errores se muestran en el renglón que corresponde ("Renglón 3: elegí el rubro."). *(F-39, F-40, W-10)*
- **RN-GAS-10** **Total del renglón** = cantidad × precio unitario, calculado solo, con el redondeo de §4.5; no se tipea. Una cantidad negativa en una factura: 422 "La cantidad va en negativo solo en una devolución." *(F-41, ADR-008, C15)*
- **RN-GAS-11** **Total de la factura** = suma de los renglones, y al lado **el reparto por obra**: "905.600 · Nave 3: 549.000 · Losa tanque B: 311.600 · sin obra: 45.000", en vivo mientras se carga. No hay un total para tipear aparte, así que **siempre suma**: la persona lo compara con el papel antes de guardar y, si no coincide, corrige los renglones (en general falta uno o hay un precio mal tipeado). Una diferencia de centavos por el redondeo del proveedor se acepta: la rendición se lleva en pesos. Si el papel da más, se puede sumar un renglón "Ajuste de redondeo"; no se cargan renglones negativos para bajar el total. Junto al botón: "Importes sin IVA (netos)". *(W-10 "Total de la factura (cantidad × precio, suma de renglones)", F-41)*
- **RN-GAS-12** **Rubros** que se ofrecen al imputar: los **11 de su lista para imputar gastos** (MATERIALES, INSUMOS, EPP, SUBCONTRATOS, LOGISTICA, AMORTIZACION, MANTENIMIENTO, OBRADORES/BAÑOS, SERVICIOS GENERALES, MAQUINAS/HERRAMIENTAS, VIATICOS). **FIJOS** y **HORAS LIQUIDADAS** no se ofrecen: se cargan como monto por período (§5.5). Si llegan por la API: 422 "FIJOS y HORAS LIQUIDADAS se cargan como monto por período, desde la rendición." EPP se imputa como rubro propio; que en el resumen sume en INSUMOS lo hace la rendición. *(cap. 9 "Rubros", F-54, C17, C18; su columna auxiliar "Tipos" tiene esos 11)*
- **RN-GAS-13** **Obra del renglón**: cualquier obra existente, **en cualquier estado** (también Confirmada y Terminada); el selector muestra "N.º · nombre del trabajo" y "Obra terminada" al lado si lo está. **"(sin obra)"** deja `obra_id` vacío: lo que no es de ninguna obra (stock, vehículos, oficina) y la **compra a stock**, así no se cuenta dos veces con su consumo (RN-GAS-19). Sin obra el rubro es opcional. **Un renglón que va a dos obras se divide en dos renglones** (6 bolsas a una, 4 a la otra): no hay reparto por porcentaje. La factura **se carga completa**. *(F-40, D-035, C10, C11, W-10, DU-A15, RN-OBR-25, RN-OBR-34)*
- **RN-GAS-14** **Material** (control por cantidad): si la obra del renglón tiene lista de materiales (W-48), el renglón puede asociarse a uno de **esa** lista ("Material: Cemento x 50 kg ▾", opcional) para que cuente como "pedido" en W-48. Si no es de esa obra: 422 "Ese material no es de la lista de Piso industrial nave 3." Si la obra no tiene lista, el campo no aparece. Cambiar la obra del renglón borra el material elegido. *(resuelta DU-17: "Un renglón de factura de otro proveedor puede asociarse a un material de la lista para contar como 'pedido'")*
- **RN-GAS-15** Adelanto y saldo de un subcontrato van **en dos renglones** (o en sus dos facturas), como hoy; la moneda extranjera, **convertida**, con la cotización en el detalle; la bonificación, **aplicada al precio**. El sistema no calcula ninguna de las tres cosas. *(F-41, C9)*
- **RN-GAS-16** **Guardar factura**: en **una transacción** se guarda la cabecera, los renglones, los adjuntos asociados y la actividad `creado`; después del commit sale `GastoImputado` con las obras afectadas (invalida la rendición de cada una, ADR-007). Confirmación: "✓ Factura cargada. Ya se restó en la rendición de Piso industrial nave 3 y Reparación de losa tanque B." con **Ver la factura · Cargar otra** (este último vacía el formulario y genera otra `Idempotency-Key`). Si todos los renglones son sin obra: "✓ Factura cargada. No va a ninguna obra (todo sin obra)." *(W-10 "Al guardar, cada renglón resta en la rendición de su obra, en el rubro elegido.", cap. 8 "En el mismo momento se resta", ADR-011)*

### 5.3 Gastos sin comprobante (F-44, F-47; W-35)

- **RN-GAS-17** Modo **Sin documento** (W-35): **Qué se carga: Gastos de obra rendidos · Combustible por carga · Consumo de stock propio** (por defecto el primero; propuesta DU-G02), **Rendido por**, **Fecha de la rendición** (la cabecera, ≤ hoy), **Tickets / fotos** (archivo, foto con la cámara o link, 0 a 10, opcional) y los renglones con **Fecha · Concepto · Cantidad · Precio unitario · Total · Obra · Rubro** y "+ agregar renglón". Pie: "Total rendido · 113.500" y "Cada renglón va a su obra y su rubro y suma en la rendición, igual que una factura." *(F-44, W-35, ADR-014)*
- **RN-GAS-18** **Gastos de obra rendidos** (los personales de obra sin ticket): **como hoy, sin documento y con el proveedor genérico "Gastos sin comprobante"**, y con **rendido por** obligatorio ("Elegí o escribí quién rindió."): se elige una persona activa de la lista ("Laura ▾") **o se escribe** un nombre ("supervisor de obra Juan"); se guarda el nombre como texto. *(F-44, C12, W-35 "Laura ▾ (o texto libre: supervisor de obra)"; obligatorio: propuesta DU-G02)*
- **RN-GAS-19** **Combustible por carga**: un renglón por carga, a la obra, como hoy (rubro LOGISTICA por defecto). Si después llega la factura del proveedor del combustible, **no se carga**: queda en Tango. **Consumo de stock propio**: cada renglón va a una **obra** (es el consumo de esa obra, como hoy "ARCHIVO"; con "(sin obra)": 422 "El consumo de stock va a una obra."); la compra a stock se carga como factura **sin obra** (RN-GAS-13). El sistema no puede saber si la factura es la del combustible ya imputado: el formulario de factura no lo frena (lo dice el manual). *(F-47, C10, C13, W-35)*
- **RN-GAS-20** **Proveedor en sin documento**: por defecto el genérico; se puede elegir **uno de la base** (la estación de servicio del combustible, como hoy en su libro). En las listas, un gasto con el proveedor genérico muestra "—" en la columna Proveedor (W-33, W-11). *(propuesta DU-G02; W-33 y W-11 "Sin documento · —")*
- **RN-GAS-21** **Fecha de cada renglón**: la del ticket (≤ hoy), por defecto la de la rendición; es la fecha con la que el renglón entra en la rendición. Número: no se pide. *(W-35 columna "Fecha"; propuesta DU-G01)*

### 5.4 Renglones sin documento de la obra (F-47; W-46)

- **RN-GAS-22** W-46 **"{N.º} · {obra} · renglones sin documento"** se abre desde la rendición de la obra (W-11 "+ cargar renglón sin documento") con la obra ya elegida (no se cambia). Tres secciones: **Amortización de herramientas**, **Retiros de Tecnobahía** y **Devolución de materiales**; cada una lista lo ya cargado de esa obra (no anulado) y permite cargar lo nuevo; cada carga es un gasto propio (§4.3) que se corrige o anula en W-34. Botón **Guardar** por sección. La obra puede estar en cualquier estado ("Obra terminada" arriba si lo está). *(W-46, W-11, DU-A15)*
- **RN-GAS-23** **Amortización de herramientas: un renglón por obra**, con el monto cargado a mano, como hoy: **Fecha · Concepto · Importe** (rubro AMORTIZACION, fijo). Concepto por defecto "Amortización de herramientas de la obra". Importe > 0 ("Poné el importe."). Si la obra ya tiene su amortización (no anulada), la sección la muestra ("Ya cargado: 310.000 · es el de AMORTIZACION en la rendición") con **Corregir** (abre W-34 en edición) y no deja cargar otra: 422 "Esta obra ya tiene su amortización (310.000): corregila." *(F-47 "un renglón por obra", W-46 "Un renglón por obra, con el monto cargado a mano, como hoy")*
- **RN-GAS-24** **Retiros con remito: un retiro = un remito.** Se carga **Fecha del retiro**, **N.º de remito** ("Poné el número de remito."), **Proveedor** de la base (obligatorio: "Elegí el proveedor."; se sugiere el último usado en un retiro), **Remito** (archivo, foto o link, opcional) y uno o más renglones **Material · Cantidad · Precio · Total · Rubro**. El **material** se elige de la lista de la obra (W-48), con el precio presupuestado sugerido (se puede cambiar); **lo que no está en la lista se escribe** como concepto y queda **"fuera de presupuesto"**. Rubro por defecto MATERIALES. La factura del proveedor de los retiros queda en Tango. Un mismo N.º de remito del mismo proveedor ya cargado avisa sin frenar: "Ese remito ya está cargado (14-oct, Piso industrial nave 3). ¿Cargarlo igual?" *(F-47, C14, W-46, resuelta DU-17 "Cada retiro se carga una sola vez con su remito, eligiendo materiales de esa lista y sus cantidades: suma en 'retirado' de W-48 y resta en la rendición en su rubro. Lo que no está en la lista va a 'fuera de presupuesto'"; propuesta DU-G03)*
- **RN-GAS-25** **Devolución de materiales**: **Fecha · Material · Cantidad · Total · Rubro**, con la cantidad **en negativo** ("−4"; el formulario pone el signo); el precio sugerido es el presupuestado del material o el último precio de ese material en la obra; el total da negativo y **resta** en la rendición y en lo retirado de W-48. Material de la lista opcional (si no, se escribe el concepto: "Cemento x 50 kg (sobrante)"). Proveedor opcional (el genérico si no se elige). Una cantidad positiva: 422 "En una devolución la cantidad va en negativo." *(F-47 "que las devoluciones de materiales resten", C15, cap. 9 "renglón negativo", W-46; propuesta DU-G03 y DU-G05)*
- **RN-GAS-26** Al guardar cualquier sección: misma transacción, actividad y `GastoImputado` que la factura (RN-GAS-16); confirmación "✓ Retiro cargado. Ya suma en la rendición y en el control por cantidad." (o "✓ Amortización cargada." / "✓ Devolución cargada. Ya resta en la rendición."). *(W-46 "Todo suma (o resta) en la rendición de la obra, en su rubro")*

### 5.5 Montos por período: HORAS LIQUIDADAS y FIJOS (F-54 dato, DU-18)

La pantalla (y desde dónde se abre) la define [`rendicion.md`](rendicion.md); acá, el dato y sus reglas, que valen
para cualquier pantalla que lo cargue.

- **RN-GAS-27** Un monto por período es un gasto `monto_periodo` con **período desde–hasta** (dos fechas, hasta ≥ desde: "El 'hasta' no puede ser anterior al 'desde'."; las dos: "Poné desde y hasta."), proveedor genérico y **un renglón por obra**, con rubro **FIJOS** o **HORAS LIQUIDADAS**, cantidad 1 y el **monto total** en el precio (> 0), **sin detalle por empleado**. Concepto por defecto "HORAS LIQUIDADAS 01/10 al 15/10". Una misma obra no puede repetirse en el mismo gasto con el mismo rubro. La fecha del gasto y de sus renglones es la del "hasta" o, si el "hasta" todavía no llegó (los fijos del mes en curso), la de hoy: así resta en la rendición desde que se carga. El "desde" no puede ser posterior a hoy ("El período no puede empezar después de hoy."). Sirve para quincenas (horas, como hoy) y meses (fijos). *(F-54 "un monto total por período", C18, resuelta DU-18)*
- **RN-GAS-28** Si para la misma obra y el mismo rubro ya hay un monto (no anulado) cuyo período **se superpone**, avisa sin frenar: "Ya hay HORAS LIQUIDADAS de Piso industrial nave 3 del 01/10 al 15/10 (1.300.000). ¿Cargar igual?"; se guarda solo si se confirma (`confirmar_superposicion: true`). *(propuesta DU-G09)*

### 5.6 Gastos cargados (F-45; W-33)

- **RN-GAS-29** Columnas **Fecha · Documento · Proveedor · Obras · Rubros · Total · Cargó**. **Documento** según la carga: "Factura 0003-00012345", "Sin documento", "Remito 0001-00000456", "Amortización", "Devolución", "Horas 01/10–15/10" / "Fijos 01/10–31/10", "Saldo inicial". **Proveedor**: el nombre; "—" si es el genérico. **Obras**: las obras de sus renglones, cortas ("Piso nave 3 · Losa tanque B · sin obra"). **Rubros**: los de sus renglones, con "· combustible" si es combustible ("LOGISTICA · combustible"). **Cargó**: quien lo cargó. Tocando una fila se abre el detalle (W-34). *(F-45, W-33)*
- **RN-GAS-30** **Filtros componibles** (se suman entre sí): **Obra** (todas · "(sin obra)" · cada obra), **Proveedor** (todos · cada uno, el genérico como "Gastos sin comprobante (genérico)"), **Rubro** (todos · cada uno de los 13), **Fecha: desde → hasta** (inclusive, sobre la **fecha del comprobante**; por defecto, del 1 del mes en curso a hoy, como W-33 "1-oct → 19-oct") y **Tipo** (Factura y Sin documento · Factura · Sin documento). Cada filtro es una clase chica en `Gastos/Infrastructure/Consultas/` (`PorObra`, `SinObra`, `PorProveedor`, `PorRubro`, `FechaEntre`, `PorTipoDocumento`, `VisiblesPara`) sobre `GastosQuery`; el alcance de quien mira se aplica siempre antes (RN-GAS-48). *(F-45 "filtros por obra, proveedor, rubro, fecha y tipo de documento", W-33, arquitectura §3)*
- **RN-GAS-31** Un gasto aparece si **al menos uno de sus renglones** cumple los filtros de obra y rubro. Con filtro de obra o de rubro, la columna **Total** muestra **lo de los renglones que cumplen**, y debajo, en chico, el total del comprobante ("549.000 · de 905.600"), así "Total de lo filtrado" da lo mismo que su tabla dinámica filtrada. *(W-33 "El total de lo filtrado, como hoy lo sacan con tablas dinámicas"; propuesta DU-G06)*
- **RN-GAS-32** Pie: "**Total de lo filtrado · 6 comprobantes · 5.447.900**", sobre **todo** lo filtrado (no solo la página). Orden: fecha del comprobante, la más nueva primero; a igual fecha, el cargado más tarde primero. Paginado de a 50. *(W-33)*
- **RN-GAS-33** **Agrupar por: nada ▾ · proveedor · obra · rubro**, como sus tablas dinámicas: con un grupo elegido, la tabla se arma por grupos con su subtotal y cantidad de comprobantes, y un comprobante repartido aparece en cada grupo con **la parte que le toca** (la factura de Corralón Centro, en "obra": Piso nave 3 549.000, Losa tanque B 311.600, (sin obra) 45.000). Por rubro, **EPP es su propio grupo** (sumarlo a INSUMOS es del resumen de la rendición). Los grupos van del mayor subtotal al menor. *(W-33 "Agrupar por: nada ▾ (proveedor, obra o rubro, como sus tablas dinámicas)")*
- **RN-GAS-34** Los **anulados** se ven **tachados**, con la marca "anulada", y **no suman** en el total ni en los grupos; el pie lo dice: "6 comprobantes · 1 anulado (no suma)". *(W-34 "Anular la deja tachada y deja de sumar"; propuesta DU-G11)*
- **RN-GAS-35** Desde la rendición, "ver todos (W-33 filtrado por esta obra)" abre W-33 con **Obra** = esa obra y la fecha **sin límite** (toda la obra). *(W-11)*

### 5.7 Detalle, corregir y anular (F-46, F-14; W-34)

- **RN-GAS-36** **Detalle** (W-34): título "Factura 0003-00012345 · Corralón Centro" (o "Sin documento · rendido por Laura · 18-10-2026", "Remito 0001-00000456 · {proveedor}", "Amortización · Piso industrial nave 3", "Horas liquidadas 01/10 al 15/10"); datos **Tipo de documento · Número · Fecha · Proveedor** (con CUIT si tiene) · **Rendido por** · **Período** (si corresponde) · **Archivo** (cada adjunto, para bajar o abrir) · **Cargó: Diego · 17-oct 10:42** · **Última edición: Diego · 17-oct 11:05** (si se corrigió); los renglones **Concepto · Cantidad · Precio unitario · Total · Obra · Rubro** (más **Fecha** en sin documento, **Material** y **Detalle** si tienen) y "Total de la factura" con el reparto por obra; abajo, el **Historial**. Con `gastos = editar`: **Editar** y **Anular**. *(F-46, W-34)*
- **RN-GAS-37** **Corregir**: con `gastos = editar`, cualquier gasto no anulado, también de una obra Terminada. Se corrigen **todos** los datos de la cabecera (número, fecha, proveedor, rendido por, período, adjuntos) y los renglones (cambiar, agregar, quitar), con las mismas validaciones de la carga (incluido el duplicado, RN-GAS-08, sin contarse a sí mismo). No se cambian el **tipo de documento** ni **qué se cargó** (origen): una factura cargada como sin documento se **anula y se carga de nuevo**. Debe quedar al menos un renglón. Guardar sin cambios no registra nada. *(F-46 "corregir"; propuesta DU-G11)*
- **RN-GAS-38** **Al corregir, la rendición se recalcula en todas las obras afectadas**, las de antes y las de después (cambiar un renglón de obra lo saca de una y lo suma en la otra): `GastoCorregido` con la unión de las dos listas. *(cap. 8, ADR-007)*
- **RN-GAS-39** Corrección simultánea: el formulario manda el `updated_at` con que se abrió; si otra persona lo cambió mientras tanto, 409 "Alguien cambió esto mientras lo tenías abierto. Actualizá y revisá." y no se guarda nada (`VersionDeFila`). *(técnica, PU-002)*
- **RN-GAS-40** **Anular**: con `gastos = editar`, con **motivo obligatorio** ("Contá por qué se anula."). La ventana dice "Anular la factura 0003-00012345 · Deja de sumar en la rendición de Piso industrial nave 3 y Reparación de losa tanque B. No se puede deshacer." El gasto queda **tachado** con "Anulada por Diego el 18-oct 9:30: 'Cargada dos veces'.", **deja de sumar** en la rendición, el % gastado, el control por cantidad y lo gastado por proveedor, conserva sus adjuntos y su historial, y su número queda libre para volver a cargarlo bien. `GastoAnulado` después del commit. *(W-34 "Anular en lugar de borrar", "Anular la deja tachada y deja de sumar en la rendición", RN-IDE-48)*
- **RN-GAS-41** Un gasto anulado **no se corrige ni se vuelve a activar**: si se anuló por error, se carga de nuevo. Corregir o anular uno anulado: 409 "Está anulada: no se corrige. Si hace falta, cargala de nuevo." (con `data.anulado_at`). **No se borran gastos**: no hay ruta para borrar. *(W-34; propuesta DU-G11)*
- **RN-GAS-42** **Historial** (W-34), del más viejo al más nuevo, cada línea con fecha, hora y quién, en palabras del cliente: "17-oct 10:42 · Diego cargó la factura (5 renglones)", "17-oct 11:05 · Diego cambió el rubro de 'Guantes de trabajo': MATERIALES → INSUMOS", "Diego cambió la obra de 'Flete': (sin obra) → Piso industrial nave 3", "Carla cambió el precio de 'Cemento x 50 kg': 9.800 → 9.500 (total 294.000 → 285.000)", "Carla agregó el renglón 'Arena fina (m³)' · 2 × 40.000 · Piso industrial nave 3 · MATERIALES", "Carla quitó el renglón 'Flete'", "Diego cambió el número: 0003-00012354 → 0003-00012345", "Diego agregó el archivo factura.pdf" (por nombre, **sin URL ni ruta**), "Diego anuló la factura: 'Cargada dos veces'". Sale de `actividades` (`creado`, `editado`, `renglon_agregado`, `renglon_cambiado`, `renglon_quitado`, `adjunto_agregado`, `adjunto_quitado`, `anulado`), escrita por el caso de uso en la misma transacción. *(F-14 "también en gastos", F-46, W-34 "Quién la cargó y cuándo, y cada corrección posterior", ADR-010)*

### 5.8 Gastos del proveedor (F-57; W-14)

- **RN-GAS-43** Gastos implementa `Proveedores\Application\Ports\GastadoPorProveedor::porObra(ProveedorId, AlcanceObras)` (bind en `GastosServiceProvider`, reemplaza a `GastadoPorProveedorNulo`): **una consulta** sobre los renglones de gastos **no anulados** de ese proveedor, agrupada por obra, con **renglones** (cantidad de renglones) e **importe** (suma de totales, con las devoluciones restando); `null` = "(sin obra)". Con alcance "solo sus obras", solo las obras donde es responsable y sin la fila "(sin obra)" (RN-PRV-16). Filas ordenadas por cantidad de renglones y después por importe, de mayor a menor, como W-14; "(sin obra)" al final. El proveedor genérico también tiene su "Gastado por obra". Se arma sola con lo cargado (W-37 "no hay que actualizar ninguna tabla"). *(F-57, W-14, RN-PRV-15…17)*

### 5.9 Buscador general (F-03; W-18)

- **RN-GAS-44** El buscador suma el grupo **Gastos** (después de Proveedores): encuentra gastos por **número** de factura o de remito, con 3 o más dígitos, **ignorando ceros a la izquierda, guiones y espacios** ("12345", "3-12345" y "0003 00012345" encuentran `0003-00012345`). Cada resultado: "Factura 0003-00012345 · Corralón Centro · 17-oct · 905.600" (con alcance "solo sus obras", el importe es el de sus renglones). Los anulados también, con "(anulada)". Orden: fecha, la más nueva primero; los primeros 10 y la cantidad total. Sin `gastos` ≥ Ver el grupo no aparece ni viaja; respeta el alcance. `GastosEnBuscador implements FuenteBusqueda`, registrada como `gastos`. No busca en el concepto de los renglones ni por el nombre de la obra: un gasto de una obra encontrada por su nombre se ve desde la obra (grupo Obras → Rendición). *(F-03 "gastos por número de factura", W-18; propuesta DU-G07)*

### 5.10 Lo que Gastos aporta a Obras y a Rendición (F-15, F-17, F-49…F-53, F-66)

- **RN-GAS-45** **Todo lo gastado sale de los renglones** (ADR-007): Gastos expone `Gastos\Contracts\GastosDeObra` (§12.5) y los eventos `GastoImputado`, `GastoCorregido` y `GastoAnulado`. Rendición arma con eso la rendición (W-11, W-36, W-37, W-44), las rendiciones en curso (W-12), el control por cantidad (W-48) y el "% del presupuesto gastado" del listado de obras (W-05, puerto `AvanceGastoDeObra` de Obras, que implementa Rendición). Gastos **no** aplica EPP → INSUMOS ni compara contra lo presupuestado. *(ADR-007, F-15, F-49, F-53, F-66, obras.md §10.4)*
- **RN-GAS-46** "Últimos gastos imputados a esta obra" (W-11) son **renglones**: Fecha · Documento · Proveedor · Concepto · Rubro · Importe, los más nuevos primero; los da `GastosDeObra::renglones` con límite. Todo lo que suma en la rendición viene de renglones **no anulados**: facturas, sin comprobante, combustible, consumo de stock, amortización, retiros, devoluciones (negativas), montos por período y saldo inicial. *(W-11, ADR-007)*
- **RN-GAS-47** **Sin doble conteo por construcción** (ADR-007): la compra a stock va sin obra y su consumo con obra (RN-GAS-13, 19); el combustible y los retiros se imputan por carga o remito y su factura no se carga (RN-GAS-19, 24); un renglón tiene una sola obra (RN-GAS-13); un anulado no suma (RN-GAS-40); la amortización es una por obra (RN-GAS-23).

### 5.11 Visibilidad y alcance (ADR-006, DU-A04)

- **RN-GAS-48** Con **Gastos ≥ Ver, alcance todas**: se ven todos los gastos y todos sus renglones. Con **alcance "solo sus obras"** (configuración inicial: Ingeniería de proyectos): se ven solo los gastos con **al menos un renglón en una obra donde es responsable**; de cada uno, **solo esos renglones** (nunca los de otras obras ni los "sin obra"), y los totales (fila de W-33, detalle, buscador, "Gastado por obra") son **la suma de esos renglones**: "Total en tus obras: 549.000". W-34 agrega "Esta factura tiene además 3 renglones de otras obras o sin obra, que no ves." El **archivo de la factura** sí se baja (es el comprobante entero). Un gasto sin renglones en sus obras responde **404** (no se confirma que exista), también al bajar sus adjuntos. El alcance se calcula en el momento: si cambia el responsable de la obra, cambia lo que se ve. *(DU-A04 "si la rendición se limita a sus obras pero Gastos cargados muestra todas, la ingeniera ve por otro lado lo mismo que la rendición le oculta", identidad.md §6.1 "renglones de sus obras", RN-IDE-47; propuesta DU-G06)*
- **RN-GAS-49** Con **Gastos = Editar, alcance "solo sus obras"** (no está en la configuración inicial, pero la tabla lo permite): solo carga renglones a **sus** obras (sin "(sin obra)": "Con tu permiso solo podés imputar a tus obras."), y solo corrige o anula gastos cuyos renglones **son todos** de sus obras (si no, 403 "Esta factura tiene renglones de obras que no son tuyas: la corrige administración."). *(ADR-006)*

### 5.12 Idempotencia y concurrencia (ADR-011)

- **RN-GAS-50** Aceptan `Idempotency-Key` **todos los POST** del módulo: cargar (`POST /api/gastos`, cualquier carga) y anular. Mismo envío → misma respuesta y **un solo gasto**. Anular uno ya anulado responde 409 con el estado actual. Cargar, corregir y anular se hacen dentro de una transacción con el gasto **bloqueado para escritura** (`lockForUpdate`) al corregir o anular, y la amortización única por obra (RN-GAS-23) se verifica con la obra bloqueada, para que dos cargas a la vez no dejen dos. Los eventos salen **después del commit**. *(ADR-011, CLAUDE.md regla 5)*

## 6. Estados y transiciones

| De | Acción | A | Quién | Requisitos | Evento |
|---|---|---|---|---|---|
| — | **Cargar** | Vigente | `gastos = editar` (con su alcance) | §4.3, RN-GAS-03…28 | `GastoImputado {gastoId, obraIds, materialIds}` |
| Vigente | **Corregir** | Vigente | `gastos = editar` (RN-GAS-49) | mismas validaciones + `updated_at` | `GastoCorregido {gastoId, obraIds (antes ∪ después), materialIds}` |
| Vigente | **Anular** | Anulada | `gastos = editar` (RN-GAS-49) | motivo | `GastoAnulado {gastoId, obraIds, materialIds}` |
| Anulada | Corregir · Anular | — | — | **409** "Está anulada: no se corrige. Si hace falta, cargala de nuevo." | — |

No hay "desanular" ni borrar (RN-GAS-41). Cada acción registra su actividad en la misma transacción.

## 7. Pantallas: vacío, cargando, error y sin permiso

| Pantalla | Vacío | Cargando | Error | Sin permiso |
|---|---|---|---|---|
| W-33 Gastos cargados | Sin ningún gasto: "Todavía no hay gastos cargados." + "+ Cargar factura / gasto" (con editar). Con filtros: "No hay gastos con estos filtros." + "Limpiar filtros" | filas grises en la tabla; los filtros se pueden tocar | "No pudimos traer los gastos. Probá de nuevo." + **Reintentar** (lo escrito en los filtros no se pierde) | sin `gastos` ≥ ver: el ítem no está en la barra; por URL, "No tenés acceso a Gastos. Si lo necesitás, pedíselo a gerencia." (API 403) |
| W-34 Detalle | — | esqueleto de cabecera y renglones | "No pudimos traer este gasto. Probá de nuevo." | inexistente, ajeno o fuera de alcance: "No encontramos este gasto." (API 404) |
| W-10 / W-35 Carga | formulario con un renglón vacío; fecha = hoy | al guardar, el botón dice "Guardando…" y no se puede volver a tocar | 422: cada error en su campo o renglón, el formulario no se vacía; sin conexión: "No se pudo guardar. Revisá la conexión y probá de nuevo." (reintenta con la **misma** `Idempotency-Key`) | sin `gastos = editar`: no aparece el botón; por URL, "Solo administración y compras cargan gastos." (API 403) |
| W-46 Renglones sin documento | cada sección: "Todavía no hay amortización / retiros / devoluciones cargados en esta obra." | por sección | por sección, como W-10 | sin `gastos = editar`: W-11 no muestra el acceso; por URL, 403 como W-10 |
| W-14 "Gastado por obra" | "Todavía no hay gastos cargados de este proveedor." | filas grises | "No pudimos traer lo gastado. Probá de nuevo." | sin `gastos` ≥ ver: la sección no aparece (RN-PRV-16) |
| Grupo Gastos del buscador | "Gastos (0) · Sin resultados." | — | el grupo no aparece y los demás sí | sin `gastos` ≥ ver: el grupo no viaja |

## 8. Permisos

### 8.1 Fila de la matriz y alcance

| Función (fila) | Nivel | Qué habilita en este módulo | Alcance `propias` = |
|---|---|---|---|
| `gastos` | ver | W-33, W-34 (con historial y adjuntos), grupo Gastos del buscador, "Gastado por obra" (W-14, con `proveedores` ≥ ver) | renglones de obras donde es responsable (RN-GAS-48) |
| `gastos` | editar | además: cargar (W-10, W-35, W-46, montos por período), corregir y anular | ídem (RN-GAS-49) |
| `proveedores` | editar | "+ nuevo proveedor" desde la factura (W-38) | — |
| `control_cantidad` | ver | además, bajar el remito de un retiro (`identidad.md` §6.6) | sus obras |

Configuración inicial (`identidad.md` §6.3): **Editar·todas** Gerencia de Administración, Jefatura de Administración y
Compras · **Ver·todas** Dirección, Gerencia de Operaciones y Jefatura de Proyectos · **Ver·solo sus obras** Ingeniería
de proyectos (DU-A04) · **Sin acceso** Calidad, Seguridad e Higiene, RRHH y Pañol y Logística (DU-A02, DU-A03).

Las cargas que se hacen desde la rendición (W-46 y los montos por período) piden **`gastos = editar`** (son gastos y se
corrigen en W-34 con ese permiso), no `rendicion = editar` como decía `identidad.md` §6.1 (propuesta DU-G08; con la
configuración inicial son las mismas personas).

### 8.2 Por pantalla y endpoint

| Acción | Función | Nivel | Alcance | Errores |
|---|---|---|---|---|
| Listar (W-33), filtros, agrupar | `gastos` | ver | sí | 403 · 422 |
| Ver detalle e historial (W-34), bajar adjuntos | `gastos` (remito: o `control_cantidad`) | ver | sí | 404 fuera de alcance |
| Cargar (factura, sin documento, W-46, monto por período) | `gastos` | editar | sí (RN-GAS-49) | 403 · 404 (obra) · 422 |
| Corregir | `gastos` | editar | sí | 403 · 404 · 409 · 422 |
| Anular | `gastos` | editar | sí | 403 · 404 · 409 · 422 |
| Opciones de obras, materiales, rubros; aviso de factura repetida | `gastos` | editar | sí | 403 |
| Lista de W-46 de una obra | `gastos` | ver (para cargar, editar) | sí | 403 · 404 |
| "Gastado por obra" del proveedor | `proveedores` ≥ ver **y** `gastos` ≥ ver | ver | sí | 403 · 404 |
| Grupo Gastos del buscador | `gastos` | ver | sí | el grupo no viaja |

El detalle trae `puede: {editar, anular}` para mostrar u ocultar botones; la API vuelve a verificar todo (RN-IDE-14).
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
| 18 | Renglón con material "Cemento x 50 kg" de la lista de **Impermeabilización cubierta** pero obra Nave 3 | 422 "Ese material no es de la lista de Piso industrial nave 3." |
| 19 | Laura rinde con W-35: combustible 68.000 (15-oct), comida 42.000 (16-oct), peaje 3.500 (16-oct), "rendido por: Laura", foto HEIC de los tickets | 201; total rendido 113.500; proveedor genérico; cada renglón con su fecha; la foto se baja con su nombre. |
| 20 | Gastos de obra rendidos sin "rendido por" | 422 "Elegí o escribí quién rindió." (DU-G02). |
| 21 | "Rendido por: supervisor de obra Juan" (no es usuario) | Se guarda el texto. |
| 22 | Consumo de stock propio con un renglón "(sin obra)" | 422 "El consumo de stock va a una obra." |
| 23 | Retiro: remito 0001-00000456 del 14-oct, Endurecedor superficial (de la lista) 10 bolsas a 18.000 | Total 180.000 en MATERIALES; suma 10 en "retirado" de W-48 el 14-oct y 180.000 en la rendición. |
| 24 | Retiro con "Guantes y antiparras" que no está en la lista | Se guarda sin material: aparece en "Fuera de presupuesto" de W-48 (DU-G03). |
| 25 | Devolución: Cemento x 50 kg (sobrante) −4 a 9.800 | Total −39.200; resta en MATERIALES y en lo retirado del cemento. |
| 26 | Devolución con cantidad 4 | 422 "En una devolución la cantidad va en negativo." |
| 27 | Segunda amortización en Nave 3 | 422 "Esta obra ya tiene su amortización (310.000): corregila." Diego la corrige a 350.000 desde W-34: historial "Diego cambió el precio de 'Amortización de herramientas de la obra': 310.000 → 350.000". |
| 28 | Horas liquidadas Nave 3 del 01/10 al 15/10 por 1.300.000 y, después, otra del 10/10 al 25/10 | La segunda avisa la superposición; se guarda solo si se confirma (DU-G09). |
| 29 | Monto por período con hasta 30/09 y desde 01/10 | 422 "El 'hasta' no puede ser anterior al 'desde'." |
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
| `GET /api/gastos?obra_id=\|sin_obra&proveedor_id=&rubro=&desde=&hasta=&tipo=factura\|sin_documento&agrupar=proveedor\|obra\|rubro&page=` | — | `[GastoFila]` paginado de a 50; `meta: {total_filtrado, comprobantes, anulados, desde, hasta}`; con `agrupar`, `grupos: [{clave, nombre, subtotal, comprobantes, filas: [GastoFila]}]` | 403 · 422 |
| `GET /api/gastos/opciones` | — | `{rubros: [{clave, nombre, se_imputa: true\|false, carga_por_periodo}], origenes_sin_documento: ["sin_comprobante","combustible","consumo_stock"]}` | 403 |
| `GET /api/gastos/obras-opciones?buscar=` | — | `[{id, numero, nombre_trabajo, estado, tiene_materiales}]` (máx. 20, todas las obras o solo las suyas con alcance propias; usa `ObrasConsulta`) | 403 |
| `GET /api/gastos/materiales-opciones?obra_id=` | — | `[{id, nombre, unidad, precio_presupuestado, fuera_de_presupuesto}]` (contrato de Rendición, §12.4) | 403 · 404 |
| `GET /api/gastos/factura-existente?proveedor_id=&numero=&excluir_id=` | — | `{existe: true, gasto: {id, numero, fecha, cargado_por}}` o `{existe: false}` (RN-GAS-08) | 403 · 422 |
| `POST /api/gastos` | `GastoAlta` (§12.2) | `GastoDetalle` (201) + `obras_afectadas: [{id, nombre_trabajo}]` | 403 · 404 (obra o proveedor) · 422 |
| `GET /api/gastos/{id}` | — | `GastoDetalle` | 404 |
| `PATCH /api/gastos/{id}` | campos de cabecera opcionales + `renglones` = **la lista completa** (los que traen `id` se conservan o cambian; sin `id`, nuevos; los que no vienen, se quitan) + `adjuntos: [ids]` (lista completa) + `updated_at` | `GastoDetalle` | 403 · 404 · 409 · 422 |
| `POST /api/gastos/{id}/anular` | `{motivo}` | `GastoDetalle` | 403 · 404 · 409 · 422 |
| `GET /api/gastos/{id}/historial` | — | `[{cuando, quien: {id, nombre}, que: "Diego cambió el rubro de 'Guantes de trabajo': MATERIALES → INSUMOS"}]` | 404 |
| `GET /api/obras/{obra}/renglones-sin-documento` | — | `{obra: {id, numero, nombre_trabajo, estado}, amortizacion: GastoDetalle\|null, retiros: [GastoDetalle], devoluciones: [GastoDetalle], ultimo_proveedor_retiro: {id, nombre}\|null}` (W-46; ruta del módulo Gastos) | 403 · 404 |
| `GET /api/buscar?q=` (grupo de `GastosEnBuscador`) | — | §12.3 | grupo ausente sin `gastos` ≥ ver |
| `GET /api/proveedores/{id}/gastado-por-obra` (Proveedores) | — | `proveedores.md` §10.3, con datos | 403 · 404 |
| `POST /api/adjuntos` · `GET /api/adjuntos/{id}` (Shared) | contextos `comprobante_gasto`, `remito` | `obras.md` §10.5 | §4.4 |

Proveedores (`GET /api/proveedores/opciones`, `POST /api/proveedores`) y personas (`GET /api/usuarios/opciones`, para
"rendido por") ya existen y se usan tal cual.

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
  "proveedor_id": null,                   // null = el genérico (DU-G02: o uno de la base)
  "adjuntos": [62],
  "renglones": [ { "fecha": "2026-10-15", "concepto": "Combustible camioneta", "cantidad": "1", "precio_unitario": "68000.00", "obra_id": 12, "rubro": "logistica" } ] }
// GastoAlta — retiro con remito (W-46)
{ "tipo_documento": "sin_documento", "origen": "retiro_remito", "numero": "0001-00000456", "fecha": "2026-10-14",
  "proveedor_id": 40, "adjuntos": [63],   // contexto "remito", máx. 1
  "renglones": [ { "material_id": 302, "concepto": "Endurecedor superficial (bolsa)", "cantidad": "10", "precio_unitario": "18000.00", "obra_id": 12, "rubro": "materiales" } ],
  "confirmar_remito_repetido": false }
// GastoAlta — devolución (W-46): cantidad negativa
{ "tipo_documento": "sin_documento", "origen": "devolucion", "fecha": "2026-10-17",
  "renglones": [ { "material_id": 300, "concepto": "Cemento x 50 kg (sobrante)", "cantidad": "-4", "precio_unitario": "9800.00", "obra_id": 12, "rubro": "materiales" } ] }
// GastoAlta — amortización (W-46)
{ "tipo_documento": "sin_documento", "origen": "amortizacion", "fecha": "2026-10-18",
  "renglones": [ { "concepto": "Amortización de herramientas de la obra", "cantidad": "1", "precio_unitario": "310000.00", "obra_id": 12, "rubro": "amortizacion" } ] }
// GastoAlta — monto por período (pantalla de rendicion.md)
{ "tipo_documento": "sin_documento", "origen": "monto_periodo", "periodo_desde": "2026-10-01", "periodo_hasta": "2026-10-15",
  "renglones": [ { "concepto": "HORAS LIQUIDADAS 01/10 al 15/10", "cantidad": "1", "precio_unitario": "1300000.00", "obra_id": 12, "rubro": "horas_liquidadas" } ],
  "confirmar_superposicion": false }

// GastoFila (W-33)
{ "id": 501, "fecha": "2026-10-17", "tipo_documento": "factura", "origen": "factura",
  "documento": "Factura 0003-00012345",
  "proveedor": { "id": 21, "nombre": "Corralón Centro", "es_generico": false },
  "obras": [ { "id": 12, "nombre_trabajo": "Piso industrial nave 3" }, { "id": 14, "nombre_trabajo": "Reparación de losa tanque B" }, null ],
  "rubros": ["materiales", "maquinas_herramientas", "insumos", "logistica"],
  "total": "905600.00", "total_filtrado": "549000.00",   // total_filtrado: solo con filtro de obra o rubro, o con alcance propias
  "cargado_por": { "id": 3, "nombre": "Diego" }, "anulado": false }

// GastoDetalle = GastoFila +
{ "numero": "0003-00012345", "rendido_por": null, "periodo_desde": null, "periodo_hasta": null,
  "proveedor": { "id": 21, "nombre": "Corralón Centro", "cuit": "30-00000000-7", "es_generico": false },
  "adjuntos": [ /* Adjunto (obras.md §10.5), contexto "comprobante_gasto" o "remito" */ ],
  "renglones": [ { "id": 9001, "orden": 1, "fecha": "2026-10-17", "concepto": "Cemento x 50 kg", "cantidad": "30.000",
                   "precio_unitario": "9800.00", "total": "294000.00",
                   "obra": { "id": 12, "numero": "2-131-2026", "nombre_trabajo": "Piso industrial nave 3", "estado": "en_ejecucion" },
                   "rubro": { "clave": "materiales", "nombre": "MATERIALES" },
                   "material": { "id": 301, "nombre": "Cemento x 50 kg", "unidad": "bolsa" }, "detalle": null } ],
  "por_obra": [ { "obra": { "id": 12, "nombre_trabajo": "Piso industrial nave 3" }, "total": "549000.00" }, { "obra": null, "total": "45000.00" } ],
  "renglones_ocultos": 0,                 // con alcance propias: cuántos no ve (RN-GAS-48)
  "cargado_at": "2026-10-17T10:42:00-03:00", "updated_at": "2026-10-17T11:05:00-03:00", "updated_by": { "id": 3, "nombre": "Diego" },
  "anulacion": null,                      // { "anulado_at": "…", "anulado_por": {id, nombre}, "motivo": "Cargada dos veces" }
  "puede": { "editar": true, "anular": true } }
```

### 12.3 Buscador

`GastosEnBuscador` (RN-GAS-44) devuelve en `GET /api/buscar?q=` el grupo
`{tipo: "gastos", cantidad, resultados: [{id, documento: "Factura 0003-00012345", proveedor: {id, nombre}|null, fecha, total, anulado}]}`
(con alcance propias, `total` = sus renglones).

### 12.4 Contratos con otros módulos

| Contrato | Dirección | Métodos | Uso |
|---|---|---|---|
| `Proveedores\Application\Ports\GastadoPorProveedor` | Gastos **implementa** (`GastadoPorProveedorEloquent` en `Gastos/Infrastructure`, bind en `GastosServiceProvider`) | `porObra(ProveedorId, AlcanceObras): GastadoPorObra` | W-14 (RN-GAS-43) |
| `Proveedores\Contracts\ProveedoresConsulta` | usa | `existe`, `resumen`, `generico`, `esGenerico` | validar el proveedor, "trae sus datos", el genérico |
| `Obras\Contracts\ObrasConsulta` | usa | `existe`, `resumen`, `opciones(texto, limite)`, `obrasDondeEsResponsable` | validar la obra, selector, alcance propias |
| `Rendicion\Contracts\MaterialesDeObra` | usa (**lo define `rendicion.md`**) | `opciones(ObraId): list<{id, nombre, unidad, precioPresupuestado, fueraDePresupuesto}>` · `esDeLaObra(MaterialId, ObraId): bool` | RN-GAS-14, 24, 25 |
| `Rendicion\Domain\Models\Rubro` | lee por FK | catálogo, `carga_por_periodo` | RN-GAS-12 |
| `Identidad\Contracts\Permisos` | usa | `puede`, `alcance` | §8 |
| `Gastos\Contracts\GastosDeObra` | Gastos **expone** | §12.5 | Rendición (W-11, W-12, W-36, W-37, W-44, W-48, `AvanceGastoDeObra`) |
| Eventos `GastoImputado`, `GastoCorregido`, `GastoAnulado` | Gastos **publica** (después del commit) | `{gastoId, obraIds: list<int>, materialIds: list<int>}` (`obraIds` sin el "sin obra"; en `GastoCorregido`, antes ∪ después) | Rendición invalida el cache de esas obras (ADR-007) |
| `Shared\Busqueda\FuenteBusqueda` | implementa | `puedeBuscar`, `buscar` | RN-GAS-44 |
| `Shared\Adjuntos\AutorizaAdjuntos` | implementa (`GastoAdjuntos`) | `puedeVer`, `puedeEditar` | §4.4 |

Gastos usa el contrato de materiales de Rendición y Rendición usa el de gastos de Gastos: los dos van por `Contracts/`
(ADR-003), sin que ninguno use la `Infrastructure` del otro.

### 12.5 Lo que lee Rendición: `Gastos\Contracts\GastosDeObra`

Implementación `GastosDeObraEloquent`. **Todos los métodos excluyen los gastos anulados**, devuelven importes como
string decimal (escala 2, cantidades escala 3) y el rubro **como se imputó** (EPP aparte: sumarlo a INSUMOS es de
Rendición). No aplican alcance: lo aplica quien llama (Rendición sabe qué obras puede ver cada uno).

| Método | Devuelve | Para |
|---|---|---|
| `gastadoPorRubro(ObraId): array<rubroClave, numeric-string>` | suma de `total` por rubro de los renglones de la obra (devoluciones restan) | W-11, W-44 |
| `gastadoPorObraYRubro(list<ObraId>): array<obraId, array<rubroClave, numeric-string>>` | lo mismo para varias obras en **una consulta** (las obras sin gastos vienen con `[]`) | W-12, `AvanceGastoDeObra` (% gastado de W-05) |
| `renglones(ObraId, FiltroRenglones, Orden, ?int limite): list<RenglonImputado>` | renglones de la obra; `FiltroRenglones {rubros?: list<clave>, proveedorId?, desde?, hasta?, origenes?: list<origen>, conMaterial?: bool}` sobre la **fecha del renglón**; `Orden` = fecha (desc por defecto), proveedor o total | W-11 "Últimos gastos imputados", W-36, W-37 |
| `movimientosDeMateriales(ObraId): list<MovimientoMaterial>` | renglones de la obra **con** `material_id`: `{materialId, renglonId, gastoId, fecha, cantidad, total, origen, numero}` (retiros, devoluciones negativas y renglones de factura asociados) | W-48 "retirado o pedido por fecha", total retirado, resta llevar |
| `retirosFueraDePresupuesto(ObraId): list<RenglonImputado>` | renglones `origen = retiro_remito` **sin** `material_id` | W-48 "Fuera de presupuesto" |
| `materialTieneMovimientos(MaterialId): bool` | si algún renglón (no anulado o anulado) lo usa | que Rendición no deje quitar un material con retiros |

```text
RenglonImputado {
  renglonId, gastoId, orden,
  fecha,                    // la del renglón (DU-G01); en facturas = la de la factura; en montos por período = el "hasta" (u hoy, RN-GAS-27)
  tipoDocumento,            // "factura" | "sin_documento"
  origen,                   // factura | sin_comprobante | combustible | consumo_stock | amortizacion | retiro_remito | devolucion | monto_periodo | saldo_inicial
  numero|null,              // de factura o de remito
  documento,                // texto listo: "Factura 0003-00012345", "Sin documento", "Remito 0001-00000456"…
  proveedor {id, nombre, esGenerico},
  concepto, cantidad, precioUnitario, total,   // negativos en devoluciones
  obraId|null, rubroClave|null, materialId|null, detalle|null,
  rendidoPor|null, periodoDesde|null, periodoHasta|null
}
```

Qué saca Rendición de cada cosa: **por obra + rubro**, `gastadoPorRubro` / `gastadoPorObraYRubro`; **fecha**, la del
renglón (el "Período" de W-37 filtra por esa fecha); **anulados**, nunca vienen; **retiros y devoluciones**, por
`origen` (y las devoluciones con cantidad y total negativos); **cantidades de materiales**, `movimientosDeMateriales`
(lo retirado de un material = suma de sus `cantidad`, devoluciones incluidas, ADR-007); **"Facturas" por proveedor
(W-37)**, contando `gastoId` distintos con `tipoDocumento = factura`. La rendición se invalida con los tres eventos
de §12.4 (traen las obras y los materiales afectados).

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
| F-47 | RN-GAS-19, 22…26 | `test_consumo_de_stock_exige_obra` · `test_amortizacion_una_por_obra` · `test_dos_amortizaciones_simultaneas_dejan_una` · `test_retiro_exige_numero_de_remito_y_proveedor` · `test_retiro_con_material_de_otra_obra_es_rechazado` · `test_retiro_sin_material_queda_fuera_de_presupuesto` · `test_remito_repetido_avisa_y_se_guarda_si_se_confirma` · `test_devolucion_exige_cantidad_negativa` · `test_devolucion_resta_en_el_gastado` |
| F-54 (dato) | RN-GAS-27, 28 | `test_monto_por_periodo_exige_desde_y_hasta` · `test_monto_por_periodo_solo_fijos_u_horas` · `test_monto_por_periodo_un_renglon_por_obra_y_rubro` · `test_superposicion_avisa_y_se_guarda_si_se_confirma` |
| F-45 | RN-GAS-29…35 | `test_listado_ordena_por_fecha_desc` · `test_filtros_se_combinan` (uno por filtro) · `test_fecha_por_defecto_es_el_mes_en_curso` · `test_filtro_por_obra_suma_solo_sus_renglones` · `test_total_de_lo_filtrado_es_de_todo_no_de_la_pagina` · `test_agrupar_por_obra_reparte_la_factura` · `test_agrupar_por_rubro_deja_epp_aparte` · `test_anulado_se_lista_tachado_y_no_suma` · `test_proveedor_generico_se_muestra_como_guion` |
| F-46 · F-14 | RN-GAS-36…42 | `test_corregir_registra_valor_anterior_y_nuevo` · `test_corregir_sin_cambios_no_registra` · `test_corregir_obra_de_un_renglon_avisa_las_dos_obras` · `test_no_se_cambia_tipo_ni_origen` · `test_corregir_con_version_vieja_devuelve_409` · `test_anular_exige_motivo` · `test_anulado_deja_de_sumar` · `test_anulado_no_se_corrige_ni_se_vuelve_a_anular` · `test_no_hay_ruta_para_borrar_gastos` · `test_historial_informa_adjuntos_por_nombre_sin_url` · `test_cualquiera_con_gastos_editar_corrige` |
| F-57 | RN-GAS-43 | `test_gastado_por_obra_agrupa_por_obra_con_fila_sin_obra` · `test_gastado_por_obra_excluye_anulados` · `test_gastado_por_obra_resta_devoluciones` · `test_gastado_por_obra_con_alcance_propias_muestra_solo_sus_obras` · `test_gastado_por_obra_en_una_consulta` |
| F-03 | RN-GAS-44 | `test_buscador_encuentra_por_numero_sin_ceros_ni_guiones` · `test_buscador_encuentra_remitos` · `test_buscador_de_gastos_respeta_el_alcance` · `test_grupo_gastos_no_viaja_sin_permiso` · `test_buscador_marca_los_anulados` |
| F-15 · F-17 · F-49…F-53 · F-66 | RN-GAS-45…47, §12.5 | `test_gastado_por_rubro_excluye_anulados` · `test_gastado_por_obra_y_rubro_en_una_consulta` · `test_renglones_filtra_por_fecha_del_renglon` · `test_movimientos_de_materiales_incluye_devoluciones_negativas` · `test_retiros_fuera_de_presupuesto` · `test_compra_a_stock_sin_obra_y_consumo_con_obra_no_se_cuentan_dos_veces` · `test_eventos_traen_las_obras_y_materiales_afectados` |
| F-61 / ADR-006 | RN-GAS-48, 49 | `test_alcance_propias_ve_solo_sus_renglones` · `test_alcance_propias_total_es_de_sus_renglones` · `test_gasto_sin_renglones_en_sus_obras_devuelve_404` · `test_alcance_propias_baja_el_archivo_de_la_factura` · `test_editar_propias_no_imputa_a_otras_obras_ni_sin_obra` · `test_editar_propias_no_corrige_facturas_con_renglones_ajenos` |
| Idempotencia | RN-GAS-50 | `test_cada_post_de_gastos_acepta_idempotency_key` (cargar y anular) |
| F-02 | RN-GAS-01 | front `menuPara.test.ts` → `muestra_gastos_con_ver_marcado`, `oculta_cargar_sin_editar` |
| F-13 | §11 | E2E (Playwright, 390 px): `celular_rinde_tickets_con_foto` · `celular_ve_gastos_en_tarjetas` |

## 14. Dudas

Resueltas que se aplican acá (`docs/dudas.md`): DU-17 (retiros, una sola carga: RN-GAS-14, 24, 25, §12.5), DU-18
(período desde–hasta: RN-GAS-27), DU-08 y DU-19 (no tocan Gastos: las usa Rendición), DU-A04 (Ingeniería ve solo sus
obras: RN-GAS-48), DU-A15 (obra Terminada admite gastos: RN-GAS-13), DU-A18 (genérico "Gastos sin comprobante"),
DU-23 / ADR-014 (archivo o link: §4.4).

**Nuevas (abiertas, con propuesta; se construye con la propuesta):**
DU-G01 (fecha por renglón: RN-GAS-21, §4.2, §12.5) · DU-G02 (W-35: qué se carga, proveedor opcional y "rendido por"
obligatorio: RN-GAS-17, 18, 20) · DU-G03 (retiros y devoluciones en W-46: RN-GAS-24, 25, §4.6 punto 5) · DU-G04 (IVA
cuando el comprobante no lo discrimina: §4.5) · DU-G05 (notas de crédito: RN-GAS-25) · DU-G06 (total con filtro y
alcance "solo sus obras" en una factura repartida: RN-GAS-31, 48) · DU-G07 (buscador: RN-GAS-44) · DU-G08 (permiso de
las cargas desde la rendición: §8.1) · DU-G09 (superposición de montos por período: RN-GAS-28) · DU-G10 (número de
factura y "la misma factura": RN-GAS-07, 08) · DU-G11 (corregir y anular: RN-GAS-34, 37, 41).

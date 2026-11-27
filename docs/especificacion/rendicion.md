# Especificación funcional · Rendición en vivo y control de materiales por cantidad

> Módulo `app/Modules/Rendicion` · Entrega: **semana 3** · Rol: Analista funcional · 2026-11-24
> Contrato: documento firmado v1.1 (cap. 3, 4.4, 9 "Rubros", "Control por cantidad" y "Cómo se calcula", 13 criterio
> de aceptación n.º 1; F-15, F-17, F-48…F-54, F-66) + maquetas **W-11, W-12, W-36, W-37, W-44, W-48**, y W-05 / W-47
> (% gastado), W-07 / W-47 (pestaña Rendición), W-13 (de dónde sale lo presupuestado).
> Material real (estructura, **sin ningún dato real**): análisis de las seis rendiciones (9-oct) y de las planillas (18-jun):
> hojas "General", "Gasto de obra", tablas dinámicas, "Planilla control retiro/consumo materiales" y "Real vs Presup";
> auditoría de gaps del 19-oct (Parte 2, §4 y §7). Decisiones: D-007, D-035, D-039, D-040; definiciones C9…C19 y C24.
> ADR-003 (puertos), ADR-005 (polling), ADR-006 (matriz y alcance), **ADR-007** (una sola fuente: los renglones),
> ADR-008 (importes y cantidades), ADR-010 (recorrido), ADR-011 (idempotencia), ADR-014 (archivo o link).
> **Gastos** (carga de facturas y gastos, W-10, W-14, W-33, W-34, W-35, W-38, W-46) está en [`gastos.md`](gastos.md): acá **no**
> se especifica cómo se carga un gasto, solo **qué lee la rendición de Gastos** (§12.4, contrato único
> `Gastos\Contracts\GastadoDeObras`, que implementa Gastos) y qué invariantes da por ciertas; Rendición le ofrece a Gastos
> `MaterialesDeObra` y `CatalogoDeRubros` (§12.4.3). Lo presupuestado lo carga Obras (W-13, `obras.md` RN-OBR-31…37).
> Lo "Propuesto" del documento está **aprobado**. Dudas de esta semana **resueltas el 12-nov (va la propuesta)**: DU-08 (obra
> mixta), DU-17 (retiros con una sola carga), DU-18 (período desde–hasta), DU-19 (monto del presupuesto en W-13); también se
> aplican DU-09, DU-21, DU-A03, DU-A04 y DU-A05. Lo nuevo está en `docs/dudas.md` como **DU-R01…DU-R09**. **24-nov-2026: Juani
> las resolvió todas (va la propuesta)**, con los reemplazos del cruce con `gastos.md`: **DU-R03 queda reemplazada por DU-G05**
> (no hay nota de crédito en la Etapa 1) y DU-R05 reemplaza a DU-G08. Las reglas que dependían de ellas son definitivas y citan
> "resuelta DU-RNN" o "resuelta DU-GNN". Lo que se cambió para que las dos especificaciones digan lo mismo está en §17.
> Obras, personas, proveedores e importes de este archivo son **ficticios**.

## 1. Propósito

"No saber recién al final si perdimos o no" (Bruno, 16-oct). La rendición de cada obra **deja de armarse al final**: se calcula
sola, en el momento, con lo que se carga en Gastos, y muestra lo mismo que hoy su hoja "General" (por rubro: presupuestado,
gastado y lo que queda), el detalle como su libro "Gasto de obra" y sus tablas dinámicas (por rubro y por proveedor), una vista
con **todas las rendiciones en curso**, y la planilla de **retiro/consumo de materiales** (presupuestado, retirado, resta llevar).
La rendición **no tiene carga propia de importes** (ADR-007): todo lo gastado sale de los renglones de Gastos, todo lo
presupuestado sale de W-13; lo único que se carga en este módulo es la **lista de materiales del presupuesto** (W-48, DU-17).

La prueba del cliente de la semana 3 es: **"cargan las facturas de una obra en curso y la comparan con su rendición en Excel"**.
Por eso cada número de este documento tiene su fórmula, qué entra, qué no y cómo se redondea (§4.3), un caso de referencia con el
resultado esperado rubro por rubro (§9, test de aceptación) y una guía para comparar contra su planilla (§13).

## 2. Funcionalidades y pantallas cubiertas

| F-XX | Qué | Pantalla | Semana 3 |
|---|---|---|---|
| F-48 (alimenta) | Lo presupuestado por rubro sale de los presupuestos de la obra (W-13, cargado en semana 1); la rendición lo **lee**, no lo carga | W-13 → W-11 | lectura (`ObrasConsulta::presupuestadoPorRubro`) |
| F-49 | Rendición de la obra: por rubro, presupuestado, gastado, saldo y % consumido; se recalcula con cada gasto | W-11 · W-44 | completa |
| F-50 | Rubros pasados en rojo | W-11 · W-36 · W-44 · W-12 | completa |
| F-51 | Detalle de un rubro: todos los renglones que lo componen | W-36 | completa |
| F-52 | Gastos por proveedor de la obra: proveedor, concepto, cantidad y total | W-37 | completa |
| F-53 | Todas las rendiciones en curso (obras En ejecución) | W-12 | completa |
| F-54 | EPP suma en INSUMOS · HORAS LIQUIDADAS y FIJOS por período · monto de la obra, ganancia y % | W-11 (la carga de horas y fijos es la sección "Horas liquidadas y fijos por período" de W-46, `gastos.md` §5.5, resuelta DU-R04) | completa (reglas y vista) |
| F-66 | Control de materiales por cantidad: lista del presupuesto, retirado por fecha, resta llevar, fuera de presupuesto | W-48 | completa |
| F-15 (rendición) | Columna "% del presupuesto gastado" del listado de obras, en rojo si se pasó | W-05 · W-47 | puerto `AvanceGastoDeObra` (reemplaza a `AvanceGastoDeObraNulo`) |
| F-17 (rendición) | Pestaña **Rendición** de la ficha de la obra (y el dato "Rendición: 55 % gastado" de la cabecera) | W-07 · W-47 → W-44 | completa |
| F-13 (rendición) | La rendición en el celular | W-44 · W-47 | completa |
| F-14 (rendición) | Quién cargó y cambió la lista de materiales y cuándo | W-48 | registro en `actividades` (sin pantalla de historial en la Etapa 1) |
| F-03 · W-18 | Buscador general | — | **no aplica**: el grupo "Gastos" (por número de factura) es de `gastos.md`; la rendición no suma grupo |
| F-62 (compatibilidad) | Los renglones de la carga inicial (gastado acumulado por rubro, semana 4) suman como cualquier otro | — | regla RN-REN-08 |

Se enciende en la barra (lista de módulos de DU-A12) **Rendiciones** → W-12; en el celular, "Más → Rendiciones" (W-41).
**Exportar a Excel**: el contrato no lo pide para la rendición (W-11, W-12, W-36, W-37 y W-48 no lo muestran): **fuera**.

## 3. Glosario (palabras del cliente → código)

| Cliente | Código | Qué es |
|---|---|---|
| Rendición | `RendicionDeObraQuery` (read model, sin tabla) | La hoja "General" de la obra, calculada en el momento. |
| Rubro | `rubros` (13 filas, `clave`) | La lista cerrada de su rendición. 12 se presupuestan y tienen fila en el resumen; **EPP** se imputa y en el resumen suma en INSUMOS. |
| Presupuestado | `presupuesto_montos` vía `ObrasConsulta` | La suma de todos los presupuestos de la obra, rubro por rubro (W-13). |
| Sin desglose por rubro | `PresupuestadoDeObra::sinDesglose` | Lo de los presupuestos cargados "solo total" (DU-08). |
| Gastado / imputado | `gasto_renglones.total` vía `Gastos\Contracts\GastadoDeObras` | La suma de los renglones de la obra de gastos no anulados. |
| Saldo (en su planilla, "Ganancia") | — | Presupuestado − gastado, por rubro y total. Negativo = se pasó. |
| % consumido | — | Gastado ÷ presupuestado. |
| Rubro pasado | — | Gastó más de lo presupuestado: se ve en rojo (F-50). |
| Monto de la obra (en su planilla, "Cobrado" o "TOTAL OBRA") | `presupuestos.monto_obra` | Lo que se le cobra al cliente, sin IVA: suma de los montos de cada presupuesto (C19, DU-19). **No es la cobranza.** |
| Ganancia prevista · ganancia al día | — | Monto − presupuestado · monto − gastado (§4.3, resuelta DU-R01). |
| Horas y fijos por período | renglones de origen `monto_periodo` | Un monto total por período desde–hasta, sin detalle por empleado (C18, DU-18). |
| Material de la lista | `materiales` (`fuera_de_presupuesto = false`) | Un material del presupuesto con su unidad, cantidad y precio (W-48, DU-17). |
| Fuera de presupuesto | `materiales.fuera_de_presupuesto = true` | Lo que se retiró o pidió y no estaba en la lista (su planilla de retiros). |
| Retirado / pedido | renglones con `material_id` | Lo que se llevó a la obra: retiros con remito y renglones de factura asociados a un material (DU-17). |
| Resta llevar | — | Cantidad presupuestada − total retirado ("de las 50 que tenía, pedí 20, me quedan 30"). |
| Sin obra | `gasto_renglones.obra_id = null` | Stock, vehículos, oficina: no entra en ninguna rendición (C10). |
| Anulado | `gastos.anulado_at` | Gasto anulado: deja de sumar y queda en el historial (W-34). |

## 4. Datos

### 4.1 Lo que la rendición lee y no guarda (ADR-007)

| Dato | De dónde | Contrato | Qué trae |
|---|---|---|---|
| Presupuestado por rubro, sin desglose y total | Obras (`presupuesto_montos`, W-13) | `Obras\Contracts\ObrasConsulta::presupuestadoPorRubro` (existe) + extensiones de §12.4.2 | por rubro (12 claves, sin EPP), `sinDesglose`, `total`, y cada presupuesto con su `modo_carga` |
| Monto de la obra | Obras (`presupuestos.monto_obra`, W-13) | extensión `montoDeObra` (§12.4.2) | total y por presupuesto; `null` si ningún presupuesto tiene monto |
| Gastado por rubro y renglones | Gastos (`gasto_renglones` + `gastos`) | `Gastos\Contracts\GastadoDeObras` (§12.4.1, **nuevo**) | totales por obra y rubro; renglones con fecha, documento, proveedor, concepto, cantidad, precio, total, rubro, material |
| Obra (número, trabajo, cliente, estado, responsable) | Obras | `ObrasConsulta::resumen` / `obrasEnEjecucion` | — |
| Alcance "solo sus obras" | Obras | `ObrasConsulta::esResponsable`, `obrasDondeEsResponsable` | — |

### 4.2 Material (`materiales`) — F-66, W-48, DU-17 (dueño: Rendicion)

| Campo | Tipo | Oblig. | Validación | Fuente |
|---|---|---|---|---|
| obra_id | ref. obra | Sí | la obra existe (cualquier estado) | W-48 |
| presupuesto_id | ref. presupuesto de **esa** obra · null | No | si viene, es de la obra; si la obra tiene un solo presupuesto, se completa solo | modelo-datos ("de qué presupuesto sale") · "Real vs Presup" (columna PRESUPUESTO en una de sus rendiciones) |
| nombre | texto ≤ 150 | **Sí** | sin espacios al borde; **no se repite en la obra** (sin distinguir mayúsculas, tildes ni espacios dobles): "Ya está en la lista: Cemento x 50 kg." | W-48 "Material" |
| unidad | texto ≤ 20 | **Sí** | "bolsa", "m³", "paño", "barra", "kg"… | W-48 "Unidad" |
| cantidad_presupuestada | cantidad decimal(14,3) | **Sí** en la lista · vacía en fuera de presupuesto | ≥ 0, hasta 3 decimales: "Poné la cantidad presupuestada." | W-48 "Presup." · DU-17 |
| precio_presupuestado | importe decimal(15,2) | No | ≥ 0, hasta 2 decimales | W-48 "Precio unit." · DU-17 ("material, unidad, cantidad, precio") |
| fuera_de_presupuesto | booleano | Sí | `true` ⇔ `cantidad_presupuestada` vacía | W-48 "Fuera de presupuesto" |
| created_by, updated_by, timestamps | — | — | `updated_at` es la versión (409) | F-14 · ADR-010 |

**Lo retirado no se guarda acá** (ADR-007): es la suma de las cantidades de los renglones de Gastos con ese `material_id`.
Ajustes al modelo que pide esta especificación: §16.

### 4.3 Qué es cada número (fórmula, qué entra, qué no, redondeo)

Reglas comunes a todos los números:
- **Importes**: pesos, **netos sin IVA** (C9), `DECIMAL(15,2)`, viajan como string decimal (`"2701827.63"`, ADR-008). Las sumas y
  restas son **exactas al centavo** (bcmath, escala 2): **no se redondea ningún total**; lo único redondeado es el total de cada
  renglón, que calcula Gastos: `round(cantidad × precio_unitario, 2)`, **mitad para arriba en valor absoluto** (como REDONDEAR de
  Excel: 1.265.432,125 → 1.265.432,13; −0,125 → −0,13). En pantalla: con centavos, los dos decimales ("2.701.827,63"); sin
  centavos, ninguno ("700.000"); negativos con signo ("−28.000") (PU-113, `formatearImporte`).
- **Porcentajes**: se calculan sobre los importes exactos con bcmath escala 6 y se **redondean a 2 decimales, mitad para arriba**
  (`"54.04"`, así viajan en la API). En pantalla se muestran **enteros**, redondeando ese valor de 2 decimales mitad para arriba
  ("54 %"). Si el divisor es 0, el porcentaje es `null` y se ve **"—"**.
- **Período**: la rendición, W-12, W-05 y W-48 son **acumuladas: todo lo imputado a la obra desde siempre, hasta este momento**, sin
  corte de fecha (un renglón con fecha futura también suma: los fijos del mes en curso, con fecha `periodo_hasta`). Solo W-37 tiene
  filtro de período (RN-REN-37).
- **Siempre fuera**: renglones **sin obra**, renglones de **otra obra** y renglones de gastos **anulados**. **Siempre dentro**, con su
  signo: devoluciones (negativas: lo único negativo, no hay nota de crédito, resuelta DU-G05), horas y fijos por período,
  amortización, retiros, consumo de stock, combustible, gastos sin comprobante y carga inicial. **No se filtra por origen**.

| # | Número | Fórmula | Qué entra | Qué no entra | Fuente |
|---|---|---|---|---|---|
| N1 | **Presupuestado del rubro R** | Σ montos de R de los presupuestos de la obra cargados **por rubro** | los 12 rubros presupuestables; un rubro vacío en W-13 cuenta 0 | EPP (no se presupuesta); presupuestos `solo_total` (van a N2) y `sin_cargar` | cap. 9 "la suma de todos sus presupuestos, rubro por rubro" · RN-OBR-32 |
| N2 | **Sin desglose por rubro** | Σ totales de los presupuestos `solo_total` | — | — | DU-08 · RN-OBR-37 |
| N3 | **Presupuestado de la obra** | Σ N1 de los 12 rubros + N2 | todos los presupuestos cargados | `sin_cargar` | `PresupuestadoDeObra::total` |
| N4 | **Gastado del rubro R** (resumen) | Σ `total` de los renglones de la obra con rubro R **más**, si R es INSUMOS, los de rubro **EPP** | ver reglas comunes | ver reglas comunes | ADR-007 · C17 |
| N5 | **Gastado de la obra** | Σ `total` de todos los renglones de la obra (= Σ N4 de los 12 rubros) | ídem | ídem | — |
| N6 | **Saldo del rubro** | N1 − N4 (con N1 `null`, `null`) | — | — | cap. 9 "presupuestado menos lo imputado" (su columna "Ganancia") |
| N7 | **Saldo de la obra** | N3 − N5 | — | — | — |
| N8 | **% consumido del rubro** | N4 ÷ N1 × 100; `null` si N1 es 0 o `null` | — | — | W-11 "% consumido" |
| N9 | **% consumido de la obra** (también el "% gastado" de W-05 y W-12) | N5 ÷ N3 × 100; `null` si N3 = 0 | — | — | W-05 · W-12 |
| N10 | **Rubro pasado** | hay al menos un presupuesto **por rubro** cargado **y** N4 > N1 (estricto, al centavo) | también un rubro con N1 = 0 y gasto > 0 | obra sin presupuesto por rubro (solo total o sin cargar): no se marca ningún rubro | F-50 · resuelta DU-R02 (obra mixta) |
| N11 | **Obra pasada** (rojo en W-05, W-12, total de W-11) | hay algún presupuesto cargado **y** N5 > N3 | — | obra sin presupuesto cargado | RN-OBR-04 |
| N12 | **Rubros pasados de la obra** (W-12) | cantidad de rubros con N10 | — | `null` ("—") si la obra no tiene presupuesto por rubro | F-53 |
| N13 | **Monto de la obra** | Σ `monto_obra` de sus presupuestos (los vacíos no suman); `null` si ninguno tiene | — | — | C19 · DU-19 · RN-OBR-36 |
| N14 | **Ganancia prevista** y su % | N13 − N3 · (N13 − N3) ÷ N13 × 100 | — | — | W-11 "Ganancia prevista: 7.600.000 (21 %)" |
| N15 | **Ganancia al día** y su % | N13 − N5 · (N13 − N5) ÷ N13 × 100 | — | — | su hoja "General": Ganancia = Cobrado − Total gastado; % = Ganancia ÷ Cobrado · resuelta DU-R01 |
| N16 | **Retirado de un material en una fecha** (W-48) | Σ `cantidad` de los renglones con ese `material_id` y esa fecha efectiva | retiros, renglones de factura asociados, devoluciones asociadas (negativas) | anulados | DU-17 · W-48 |
| N17 | **Total retirado** | Σ N16 de todas las fechas | — | — | W-48 |
| N18 | **Resta llevar** | `cantidad_presupuestada` − N17 (puede ser negativa: se llevó de más) | — | fuera de presupuesto (no tiene) | W-48 · cap. 9 |
| N19 | **Total $ de un material** | Σ `total` de sus renglones | — | anulados | W-48 "Total" · ADR-007 |

**Fecha efectiva de un renglón** (la que ordena W-36, filtra W-37 y arma las columnas de W-48): la **fecha del renglón**
(`gasto_renglones.fecha`, resuelta DU-G01): en una factura, la de la factura; en un gasto sin comprobante, la del ticket (W-35);
en horas y fijos, **el último día del período** (`periodo_hasta`, resuelta DU-R04), también si todavía no llegó. La guarda Gastos
y viaja en el contrato (§12.4.1).

## 5. Reglas de negocio

### Qué suma y qué no (F-49; ADR-007, C10…C15)

- **RN-REN-01** **Una sola fuente**: lo gastado de una obra es la suma de los renglones de Gastos con esa obra, de gastos no anulados (N4, N5). La rendición no tiene ninguna otra tabla ni carga de importes; si algo no está en un renglón, no está en la rendición. *(ADR-007, D-040)*
- **RN-REN-02** **Una factura repartida** entre varias obras resta en cada obra solo lo de sus renglones; los renglones **sin obra** (stock, vehículos, oficina, el flete de W-10) no restan en ninguna. *(F-40, criterio de aceptación n.º 2, C10, C11)*
- **RN-REN-03** **Anular** un gasto lo saca de la rendición en el momento (todos sus renglones); corregirlo (cambiar importe, rubro u obra de un renglón) mueve el número en la obra y el rubro de antes y en los de ahora. La rendición no muestra los anulados en ninguna lista (W-36, W-37, W-48, últimos gastos). *(W-34 "Anular la deja tachada y deja de sumar en la rendición")*
- **RN-REN-04** **Negativos**: una **devolución de materiales** resta en la obra y el rubro del renglón (renglón negativo); si está asociada a un material, también resta en su "total retirado" (W-48). **No hay nota de crédito** en la Etapa 1 (resuelta DU-G05, que reemplaza a DU-R03): una por material devuelto se carga como devolución; una que corrige el precio se resuelve corrigiendo la factura, y la rendición muestra el importe corregido. Un rubro puede quedar con gastado negativo (solo devoluciones): su saldo supera lo presupuestado y su % es negativo ("−3 %"). *(C15, W-46 "renglón negativo")*
- **RN-REN-05** **Cada renglón tiene un rubro de la lista cerrada** (Gastos lo garantiza: con obra, rubro obligatorio). No hay "INSUMO" contra "INSUMOS": un gasto siempre suma donde corresponde. *(cap. 9 "Al ser una lista cerrada…")*
- **RN-REN-06** **Obra en cualquier estado**: Confirmada, En ejecución o Terminada tienen su rendición y se consulta igual (una obra Terminada no se pierde). Solo **W-12** se limita a las En ejecución. *(F-22, RN-OBR-09, RN-OBR-25, resuelta DU-A15)*
- **RN-REN-07** **Sin presupuesto cargado** (todos los presupuestos `sin_cargar`): la rendición se ve igual, con el gastado por rubro, presupuestado, saldo y % en "—", ningún rubro en rojo y el aviso "Esta obra todavía no tiene presupuesto cargado. Igual se cargan gastos y se ve lo gastado." (con el acceso "Cargar el presupuesto por rubro" a W-13 si tiene `presupuesto_rubro` = editar). *(cap. 4.4 "Sin presupuesto cargado, igual se cargan gastos y se ve lo gastado", RN-OBR-34)*
- **RN-REN-08** Los renglones de la **carga inicial** (gastado acumulado por rubro de las obras en ejecución, F-62, semana 4) suman como cualquier otro, en su rubro, y en el detalle se ven con el documento "Carga inicial". *(F-62, resuelta DU-21)*

### Presupuestado (F-48 alimenta; W-13; DU-08)

- **RN-REN-09** **Por rubro**: el presupuestado de cada rubro es la suma de ese rubro en todos los presupuestos de la obra cargados por rubro (principal y adicionales) (N1). Cambiar W-13 cambia la rendición en el momento (evento `PresupuestoMontosCambiados`). *(cap. 9, RN-OBR-32, W-13 "De acá sale la columna Presupuestado")*
- **RN-REN-10** **Solo total** (todos los presupuestos de la obra `solo_total`): se compara **el total contra lo gastado**, como en dos de sus seis rendiciones. W-11 muestra igual los 12 rubros con su **gastado**, y presupuestado, saldo y % en "—"; la fila "**Sin desglose por rubro**" con el total presupuestado; la fila **Total** con presupuestado, gastado, saldo y % (N3, N5, N7, N9); ningún rubro en rojo (solo el total, si se pasó). Texto arriba de la tabla: "Esta obra tiene solo el presupuesto total: se compara el total contra lo gastado, como en su planilla." *(C16, cap. 9, W-11, resuelta DU-08)*
- **RN-REN-11** **Obra mixta** (al menos uno por rubro y al menos uno solo total): los rubros muestran lo **desglosado** (N1), una fila "**Sin desglose por rubro**" con N2 y la lista de esos presupuestos ("1-200-2026 A1"), con gastado, saldo y % en "—" (ningún gasto se imputa a "sin desglose": todo renglón tiene rubro), y el Total suma todo (N3). Cada rubro se marca pasado **contra lo desglosado**, con la leyenda: "1.000.000 de esta obra no tienen desglose por rubro (presupuesto 1-200-2026 A1): cada rubro se compara solo contra lo desglosado." *(resuelta DU-08; marca de pasados en obra mixta: resuelta DU-R02)*
- **RN-REN-12** Un presupuesto **sin cargar** en una obra que tiene otros cargados no suma y se avisa arriba de la tabla: "El presupuesto 1-200-2026 A2 todavía no tiene montos cargados: no suma en lo presupuestado." (uno por presupuesto). *(RN-OBR-21: un adicional puede quedar "sin cargar" para administración)*
- **RN-REN-13** La rendición **no edita** lo presupuestado: para corregirlo se va a W-13 (acceso "Presupuesto por rubro" con `presupuesto_rubro` ≥ ver), donde queda el historial (resuelta DU-09). Ver la columna Presupuestado de la rendición **no** pide `presupuesto_rubro`: alcanza con `rendicion` ≥ ver. *(identidad.md §6.1 fila 5 "Ver: W-13 y la columna Presupuestado" ⊂ rendición, cap. 3)*

### EPP en INSUMOS (F-54, C17)

- **RN-REN-14** **EPP se imputa como rubro y en el resumen suma en INSUMOS** (`rubros.resume_en_rubro_id`): W-11, W-44, W-12, W-05 y los pasados usan INSUMOS + EPP (N4). La fila dice "INSUMOS · incluye EPP, como en su hoja General". EPP **no tiene fila propia** ni presupuesto. *(F-54, C17, W-11, análisis de rendiciones: `General!INSUMOS = INSUMOS + EPP`)*
- **RN-REN-15** El **detalle de INSUMOS** (W-36) lista los renglones de INSUMOS y de EPP juntos; los de EPP llevan la marca "EPP" y el pie suma aparte "de los cuales EPP: 87.000", para poder compararlo con su tabla dinámica, donde EPP figura separado. El detalle de EPP solo no existe (`/rubros/epp` → 404). *(F-51, C17; marca y subtotal: resuelta DU-R07)*

### Horas liquidadas y fijos por período (F-54, C18, DU-18)

- **RN-REN-16** **HORAS LIQUIDADAS y FIJOS restan** en la rendición como cualquier rubro: su gastado es la suma de los **montos por período** cargados para la obra, sin detalle por empleado (no se liquidan sueldos, D-009). Se presupuestan como los demás (RN-OBR-35). *(F-54, cap. 4.4 "después le cargo montos de mano de obra y me los va restando", C18)*
- **RN-REN-17** **Período desde–hasta** (dos fechas: quincena para horas, mes para fijos, o el que usen), **un renglón por período y por obra** en su rubro. La **carga** (sección "Horas liquidadas y fijos por período" de W-46, endpoint y guardado) es de Gastos (origen `monto_periodo`, una sola fuente) y sus reglas y textos son los de `gastos.md` RN-GAS-27 y RN-GAS-28, que acá no se repiten: rubro `horas_liquidadas` o `fijos`; hasta ≥ desde; importe mayor que cero; concepto por defecto "Horas liquidadas 1 al 15-oct" / "Fijos 1 al 31-oct", editable; la fecha efectiva es **el último día del período** (`periodo_hasta`). Si ya hay un período del **mismo rubro y obra que se superpone**, **no se frena**: se avisa "Ya hay HORAS LIQUIDADAS de Ampliación de nave de ensayo del 1 al 15-oct (950.000). ¿Cargar igual?" y se guarda si confirma. *(resuelta DU-18; dónde se carga, fecha efectiva y aviso de superposición: resuelta DU-R04 y DU-G09)*
- **RN-REN-18** En W-11 las filas FIJOS y HORAS LIQUIDADAS dicen debajo "monto por período" y "monto por período, sin detalle por persona" (W-11), y quien puede cargarlas (`rendicion` = editar, RN-REN-58) ve el acceso "+ cargar período" que abre esa sección de W-46 con la obra y el rubro elegidos. En W-36 cada renglón se ve con el documento "Período 1 al 15-oct". *(W-11, resuelta DU-R04)*
- **RN-REN-62** **Parte de horas** (DU-C02, `gastos.md` §5.13): las HORAS LIQUIDADAS también llegan del parte de horas que se carga en W-10, una vez, con un renglón por persona y obra; cada obra resta lo suyo, con fecha efectiva el "hasta" del parte, y el gastado de HORAS LIQUIDADAS es la suma de los montos por período **y** de los renglones del parte. El monto por período de W-46 sigue igual. En W-11 ("Últimos gastos") y W-36 el documento es "Parte de horas 1 al 15-oct". *(DU-C02 "Cada obra ve en su rendición lo suyo")*
- **RN-REN-63** **Privacidad del detalle por persona** (`gastos.md` RN-GAS-57): quien tiene **`gastos = editar`** sobre la obra ve en W-11 y W-36 un renglón por persona con el **empleado** (`empleado: "Juan Pérez"`), las horas y el valor hora. Los demás (por ejemplo Ingeniería en sus obras, que mira la rendición con `rendicion` ≥ ver) ven el parte **resumido**: un renglón por parte con el concepto, las horas sumadas en Cantidad ("120") y el total, sin empleado y con el Precio unitario vacío ("—"; `precio_unitario: null`, `resumido: true`). W-37 agrupa por concepto, así que se ve igual para todos (el concepto del parte, horas y total, sin nombres). Los totales de la fila HORAS LIQUIDADAS, el pie de W-36 y W-12 son los mismos para todos. *(DU-C02 "Privacidad (valor hora por persona)")*

### Saldo, porcentaje y rubros pasados (F-49, F-50)

- **RN-REN-19** **Saldo** = presupuestado − gastado, por rubro y total (N6, N7); en su planilla esa columna se llama "Ganancia". Un saldo negativo se ve **en rojo** con signo ("−70.000"). *(cap. 9 "Cómo se calcula", W-11)*
- **RN-REN-20** **% consumido** = gastado ÷ presupuestado (N8, N9), con la barra y el número entero; con presupuestado 0 o sin presupuesto, "—". *(W-11, W-12, W-44)*
- **RN-REN-21** **Rubro pasado** = gastó **más** de lo presupuestado (estricto: 100 % justo no es pasado; al centavo). Se ve en rojo la fila (saldo, % y barra) en W-11, el encabezado de W-36 y la tarjeta en W-44. Un rubro **con gasto y sin presupuesto** (vacío en W-13) en una obra con presupuesto por rubro **también** es pasado, con % "—" y la marca "sin presupuesto". Un rubro sin presupuesto y sin gasto no se marca. *(F-50, W-11 "la barra se pone roja si un rubro pasó lo presupuestado")*
- **RN-REN-22** **Obra pasada** (N11): el total de W-11, la fila de W-12 y el "% gastado" de W-05 se ven en rojo cuando el gastado de la obra supera su presupuestado total (también en una obra solo total). *(RN-OBR-04, F-15 "en rojo si se pasó")*

### Monto de la obra y ganancia (F-54, C19, DU-19)

- **RN-REN-23** Debajo del total, el bloque "**Monto de la obra y ganancia**", que **solo ven quienes tienen `monto_obra` ≥ ver** para esa obra (con su alcance); para el resto la API **no incluye** la clave `monto` (ni vacía) y la pantalla no deja hueco. Texto: "Monto de la obra: 16.300.000 · Costo presupuestado: 12.150.000 · Ganancia prevista: 4.150.000 (25 %)" (N13, N3, N14, W-11) y debajo "Gastado hasta hoy: 6.727.161,68 · Ganancia al día: 9.572.838,32 (59 %)" (N5, N15), que es la cuenta de su hoja General (Cobrado − Total gastado). *(F-54, C19, cap. 3 "lo ven gerencias y administración", RN-OBR-36; ganancia al día: resuelta DU-R01)*
- **RN-REN-24** Si ningún presupuesto tiene monto: "Todavía no se cargó el monto de la obra." (con el acceso a W-13 si tiene `monto_obra` = editar). Si falta el monto de algún presupuesto, se suma lo que hay y se avisa "El presupuesto 1-200-2026 A1 no tiene monto cargado: no suma en el monto de la obra." Sin presupuesto cargado (N3 = 0), la ganancia prevista y su % se ven "—". El monto **no se carga en la rendición** (se carga en W-13, RN-OBR-36). *(DU-19)*
- **RN-REN-25** Ganancia negativa (se pierde plata) se ve en rojo con signo. El % de ganancia es sobre el monto de la obra (como su planilla: Ganancia ÷ Cobrado). *(análisis de rendiciones, auditoría §4.4)*

### Rendición de la obra (F-49; W-11, pestaña Rendición de W-07)

- **RN-REN-26** W-11 es la **pestaña Rendición** de la ficha de la obra (W-07), con las pestañas **Pedidos · Rendición · Documentos y contactos** y el acceso **Control por cantidad** (W-48, con `control_cantidad` ≥ ver). Encabezado: "2-131-2026 · Piso industrial nave 3" y el estado; arriba "● Se actualiza sola con cada gasto cargado" y, a quien no puede cargar nada desde la rendición, "· solo lectura" (W-44). *(F-17, W-07, W-11, W-44)*
- **RN-REN-27** Tabla **Rubro · Presupuestado · Gastado · Saldo · % consumido** con **los 12 rubros siempre, en el orden de su hoja General** (`rubros.orden`: MATERIALES, INSUMOS, SUBCONTRATOS, LOGISTICA, AMORTIZACION, MANTENIMIENTO, OBRADORES/BAÑOS, SERVICIOS GENERALES, MAQUINAS/HERRAMIENTAS, VIATICOS, FIJOS, HORAS LIQUIDADAS), aunque estén en 0; después, si corresponde, "Sin desglose por rubro" (RN-REN-10/11), y la fila **Total de la obra**. Tocando un rubro se abre su detalle (W-36). *(W-11, cap. 4.4 "Es el resumen de su hoja General")*
- **RN-REN-28** Abajo, "**Últimos gastos imputados a esta obra**": los **10 renglones más recientes** por fecha efectiva (a igual fecha, el cargado último primero), con **Fecha · Documento · Proveedor · Concepto · Rubro · Importe**; "**ver todos**" abre Gastos cargados filtrado por esta obra (W-33, con `gastos` ≥ ver) y "**+ cargar renglón sin documento**" abre W-46 con la obra elegida (con el permiso de RN-REN-58: `rendicion` = editar, o `control_cantidad` = editar solo para la sección de retiros). Tocando un renglón se abre su gasto (W-34, con `gastos` ≥ ver; sin ese permiso la fila no es un vínculo). *(W-11; cantidad: resuelta DU-R07)*
- **RN-REN-29** **Documento** (columna de W-11, W-36; **las mismas etiquetas que W-33 y W-34**, `gastos.md` RN-GAS-29): "Factura 0003-00012345" · "Sin documento" (gastos sin comprobante y combustible) · "Consumo de stock" · "Amortización" · "Retiro · remito 0001-00000077" · "Devolución" · "Período 1 al 15-oct" (horas y fijos) · "Carga inicial". **Proveedor**: el nombre; si es el proveedor genérico de los gastos sin comprobante, "—" (W-11; todo gasto tiene proveedor: el genérico si no se eligió otro, `gastos.md` §4.3). *(W-11, W-36; etiquetas: resuelta DU-R07)*

### Detalle de un rubro (F-51; W-36)

- **RN-REN-30** "‹ Volver a la rendición" y el encabezado del rubro: "MATERIALES · Presupuestado 5.000.000 · Gastado 2.701.827,63 (54 %) · Saldo 2.298.172,37" (en rojo si está pasado; en obra solo total, "Presupuestado —"). *(W-36 "Arriba, lo presupuestado, lo gastado y el saldo del rubro")*
- **RN-REN-31** Tabla **Fecha · Documento · Proveedor · Concepto · Cantidad · Precio unitario · Total** con **todos** los renglones del rubro (sin páginas: la rendición más grande tiene ≈165 gastos, ADR-007), de gastos no anulados. Pie: "Total MATERIALES · 5 renglones · 2.701.827,63" (= el gastado de W-11, siempre). Orden por **fecha** (por defecto, la más nueva primero), **proveedor** (A→Z) o **total** (mayor primero), tocando el encabezado; a igual valor, el cargado último primero. *(F-51, W-36 "Orden por fecha, proveedor o total")*
- **RN-REN-32** Cantidades con hasta 3 decimales, sin ceros de más ("12,5", "100"); precio y total como importes. Tocando un renglón se abre su gasto (W-34) con `gastos` ≥ ver. *(W-36, ADR-008)*

### Gastos por proveedor (F-52; W-37)

- **RN-REN-33** En la pestaña Rendición, el selector **Por rubro · Por proveedor**: "Por rubro" es la tabla de W-11; "Por proveedor" es W-37. *(W-37)*
- **RN-REN-34** **Por proveedor y, adentro, por concepto**: cada proveedor con su **total** y la cantidad de **comprobantes** con número (facturas; un retiro con remito no cuenta como factura: "—"; no hay notas de crédito, resuelta DU-G05), y debajo sus conceptos con **cantidad** sumada y **total**. Columnas **Proveedor / concepto · Cantidad · Total · Facturas**. Los conceptos se agrupan por el texto del concepto (sin distinguir mayúsculas, tildes ni espacios dobles); los proveedores por total, mayor primero; los conceptos de cada proveedor, ídem. *(F-52, W-37, sus tablas dinámicas "gastos ampliados por proveedor" y "facturas por proveedor")*
- **RN-REN-35** Los renglones del **proveedor genérico** (sin comprobante, consumo de stock, amortización, devoluciones sin proveedor, horas y fijos) van en un grupo "**Sin documento**", **siempre al final**, con "—" en Facturas. *(W-37 "Sin documento · 68.000 · —"; agrupación: resuelta DU-R07)*
- **RN-REN-36** Pie: "6 grupos · 6.727.161,68 · 4 comprobantes" con el total de **todo lo filtrado**. Se muestran los primeros 10 grupos y "ver los N restantes" (como W-37 "Mostrando 4 de 9 proveedores"). Sin filtros, el total es el gastado de la obra (N5). *(W-37; resuelta DU-R07)*
- **RN-REN-37** Filtros **Rubro: todos ▾** (los 12 del resumen; INSUMOS incluye EPP) y **Período: toda la obra ▾** (toda la obra · este mes · el mes anterior · desde–hasta, por fecha efectiva, en hora de Argentina). Con filtro de período el pie aclara "del 1-nov al 30-nov". *(W-37 "Rubro: todos ▾ · Período: toda la obra ▾"; opciones del período: resuelta DU-R07)*

### Todas las rendiciones en curso (F-53; W-12)

- **RN-REN-38** **Rendiciones** en la barra abre W-12: "Rendiciones en curso · obras En ejecución · ordenadas por % consumido". Una fila por obra **En ejecución** que la persona puede ver (alcance, RN-REN-55), con **Obra** (N.º de presupuesto y, debajo, el trabajo) · **Cliente** · **Presupuestado** (N3) · **Gastado** (N5) · **% consumido** (N9) · **Rubros pasados** (N12). Tocando una obra se abre su rendición (W-11). Acá no se carga nada. *(F-53, W-12)*
- **RN-REN-39** **Orden**: % consumido de mayor a menor; las obras sin presupuesto cargado ("—") al final, por N.º de presupuesto; a igual %, por N.º. La fila y el % van en rojo si la obra está pasada (N11). Una obra **solo total** muestra "Rubros pasados: —". *(W-12, RN-OBR-07)*
- **RN-REN-40** Fila final "**Todas las obras en ejecución**" = la suma de las filas que se ven: Σ presupuestado, Σ gastado, % = Σ gastado ÷ Σ presupuestado, Σ rubros pasados (las "—" no suman). Una obra que pasa a Terminada sale de W-12 en la próxima lectura (sigue en su ficha). Sin obras en ejecución visibles: "No hay obras en ejecución." *(W-12 "Todas las obras en ejecución · 56.100.000 · 36.187.000 · 65 % · 4", RN-OBR-25)*
- **RN-REN-41** W-12 se arma con **una consulta por agregado para todas las obras** (presupuestado y gastado agrupados por obra), no una por obra. *(modelo-datos §3, ADR-007)*

### Listado y ficha de obras (F-15, F-17; W-05, W-07, W-47)

- **RN-REN-42** Rendicion implementa el puerto `Obras\Application\Ports\AvanceGastoDeObra` (reemplaza a `AvanceGastoDeObraNulo`): `porcentajesGastados(ids)` = N9 por obra como string de 2 decimales, `null` si la obra no tiene presupuesto cargado o N3 = 0; `pasadas(ids)` = N11 por obra. **Una consulta por agregado para toda la página.** Obras decide a quién mostrarlo (RN-OBR-04: solo con `rendicion` ≥ ver para esa obra; si no, "—"). El mismo número es el "Rendición: 55 % gastado" de la cabecera de la ficha (W-07) y la barra de W-47. *(F-15, W-05 "El % gastado sale de la rendición en vivo", obras.md §10.4)*
- **RN-REN-43** La pestaña **Rendición** de la ficha aparece con `rendicion` ≥ ver para esa obra (`puede.ver_rendicion` de la ficha); sin permiso, la pestaña no aparece. Desde la semana 3 desaparece "Disponible en la próxima entrega" de esa pestaña. *(F-17, W-07, obras.md §7)*

### Celular (F-13; W-44, W-47)

- **RN-REN-44** **W-44** (pestaña Rendición de la obra en el celular, W-47): misma API y mismos números que W-11. Arriba, el total: "Presupuestado 12.150.000 · Gastado 6.727.161,68 · Total de la obra 55 % · Saldo 5.422.838,32", y "Se actualiza sola · solo lectura". Después, **una tarjeta por rubro**: "LOGISTICA 109 %" y debajo "300.000 · gastado 328.000 · −28.000" (rojo si está pasado). *(W-44 "Mismos números que en la PC (W-11)")*
- **RN-REN-45** **Orden de las tarjetas**: los más consumidos arriba: primero los rubros **con gasto y sin presupuesto** (pasados, "sin presupuesto"), después por % consumido (2 decimales) de mayor a menor, a igual % por el orden del rubro; al final los que no tienen ni presupuesto ni gasto. En obra solo total, las tarjetas muestran solo el gastado, por gastado de mayor a menor. Tocando una tarjeta se ve su detalle (W-36 en tarjetas: fecha, documento, concepto, total). *(W-44 "Ordenado: los más consumidos arriba", "Tocando uno se ven sus gastos (W-36)")*
- **RN-REN-46** El bloque de monto y ganancia (RN-REN-23) se muestra también en el celular, al final, a quien tiene `monto_obra` ≥ ver. En el celular no se carga nada desde la rendición (W-44 "Operaciones solo ve; la carga la hace administración"). W-12 en el celular: una tarjeta por obra con N.º, trabajo, "% consumido" y "N rubros pasados". *(F-13 "todas las funciones", W-41 "Más → Rendiciones", W-44)*

### Control de materiales por cantidad (F-66; W-48; DU-17)

- **RN-REN-47** **La lista de materiales del presupuesto la carga administración una vez**, en W-48: "+ Agregar material" con **Material · Unidad · Cantidad presupuestada · Precio unitario (opcional) · Presupuesto** (este último solo si la obra tiene más de uno). Se corrige después (cantidad, precio, nombre, unidad) con registro de quién, cuándo y antes → después, como el presupuesto por rubro (resuelta DU-09). Lo hace `control_cantidad` = editar. *(F-66 "Lo carga administración", resuelta DU-17, resuelta DU-A05, ADR-010)*
- **RN-REN-48** **Cada retiro se carga una sola vez, con su remito** (archivo o link, ADR-014), en la carga de retiros de Gastos (W-46, `gastos.md`), eligiendo materiales de esa lista y sus cantidades: **suma en "retirado" de W-48 y resta en la rendición en su rubro** con el mismo renglón. W-48 tiene el acceso "**+ cargar retiro con remito**", que abre esa misma carga con la obra elegida. Un **renglón de factura** de otro proveedor también puede asociarse a un material de la obra (W-10, `gastos.md` RN-GAS-14) y cuenta como "pedido" en la misma columna. Quién carga el retiro: RN-REN-58. *(resuelta DU-17, F-47, F-66 "lo retirado o pedido por fecha", W-46)*
- **RN-REN-49** **Lo que no está en la lista** va a **"Fuera de presupuesto"**: al cargar el retiro (o un renglón de factura) se elige "No está en la lista" y se escribe el material y su unidad (W-48 firmada muestra "Unidad" en esa sección; resuelta DU-G03 con este contrato); se crea en la obra como material fuera de presupuesto (contrato `MaterialesDeObra::crearFueraDePresupuesto`, §12.4.3) y la próxima vez ya se elige. Si después se le completa la **cantidad presupuestada** en W-48, pasa a la lista. *(resuelta DU-17, W-48 "Una sección aparte para lo que se compró fuera de presupuesto", resuelta DU-G03)*
- **RN-REN-50** **Tabla de la lista**: **Material · Unidad · Presup. · una columna por fecha de retiro · Total retirado · Resta llevar · Precio unit. · Total**. Retirado por fecha = N16, Total retirado = N17, **Resta llevar = N18** (en rojo si es negativo: "se llevó 6 más"), **Precio unit. = el presupuestado** de la lista ("—" si no se cargó), **Total = N19** (lo que de verdad se gastó en ese material, el mismo número que suma la rendición). Columnas de fecha: **las 6 fechas más recientes con movimientos**, de la más vieja a la más nueva; las anteriores se juntan en una columna "**Hasta 25-sep**" (la fecha más nueva de las agrupadas). Pie: "“Resta llevar” = presupuestado − total retirado. Las fechas son los retiros o pedidos de cada material." *(W-48; precio y total: resuelta DU-R06; cantidad de columnas: resuelta DU-R06)*
- **RN-REN-51** **Total de la lista** = Σ N19 de los materiales de la lista. Debajo, para que se vea que cierra con la rendición: "Gastado en MATERIALES en la rendición: 2.701.827,63" y, si no coincide, "Renglones de MATERIALES sin material asociado: −49.252,50" (la diferencia, con acceso a W-36 de MATERIALES). *(W-48 "Total MATERIALES (igual al gastado de la rendición, W-11 y W-36)"; línea de diferencia: resuelta DU-R06)*
- **RN-REN-52** **Fuera de presupuesto**: **Material · Unidad · Fecha · Cantidad · Total · Rubro**, una fila por renglón asociado a un material fuera de presupuesto (por fecha, la más nueva arriba), con su rubro (puede ser INSUMOS o EPP: no cuenta en el total de la lista). *(W-48)*
- **RN-REN-53** **Unidad**: la cantidad de un retiro o de un renglón asociado va **en la unidad del material** (el formulario la muestra al lado: "bolsas"); no hay conversión de unidades. **Quitar un material**: solo si **ningún renglón** lo usa (tampoco uno anulado); si no, 422 "Cemento x 50 kg ya tiene 2 retiros o pedidos: no se puede quitar. Corregí la cantidad presupuestada." Un presupuesto adicional con materiales no se puede quitar (completa RN-OBR-24). *(ADR-007, RN-OBR-24; unidad: resuelta DU-R06)*
- **RN-REN-54** **Celular**: W-48 en tarjetas por material: "Cemento x 50 kg · 96 de 150 bolsas · resta 54 · 945.648" y abajo "Fuera de presupuesto"; solo lectura (la lista se carga en la PC). Una obra sin lista: "Todavía no hay materiales cargados para esta obra." con "+ Agregar material" (con `control_cantidad` = editar). *(F-13, W-48)*

### Permisos y alcance (ADR-006; cap. 3)

- **RN-REN-55** **`rendicion` ≥ ver** habilita W-11, W-36, W-37, W-44 y W-12, con su **alcance**: con "solo sus obras" (Ingeniería de proyectos, configuración inicial V·P) se ven **solo las obras donde la persona es responsable**; una obra fuera de alcance responde **404** (no se confirma que exista) en todas las rutas de la obra, y W-12 lista solo las suyas (también en la fila total). Sin `rendicion`: 403 en W-12 y en la rendición de cualquier obra; el ítem "Rendiciones" no aparece. *(cap. 3 "las ingenieras ven la rendición de sus obras", F-53 "cada ingeniera, las de sus obras", resuelta DU-A04, identidad.md RN-IDE-47)*
- **RN-REN-56** **`monto_obra` ≥ ver** (con su alcance, para esa obra) agrega el bloque de monto y ganancia (RN-REN-23); sin él la clave no viaja. **`control_cantidad` ≥ ver** habilita W-48 (lectura) y **= editar** la lista de materiales (RN-REN-47). **`gastos` ≥ ver** hace vínculos los renglones (W-34) y "ver todos" (W-33). *(cap. 3, identidad.md §6.1 filas 6, 7 y 9)*
- **RN-REN-57** Todo lo que se **ve** en la rendición sale de calcular; **lo que se carga** se carga en Gastos, en W-13 y en la lista de W-48, cada uno con su permiso. Ver en la rendición los renglones (proveedor, concepto, precio) **no** pide `gastos` ≥ ver: son el detalle de la rendición (F-51, F-52 "según permisos" = `rendicion`). *(D-039 "edita solo administración; operaciones solo ve", W-17 "Rendiciones (ver)")*
- **RN-REN-58** **Cargas que se hacen desde la rendición** (W-46: amortización, retiros, devoluciones; horas y fijos por período): según la tabla aprobada de `identidad.md` §6.1 (fila 8, "Editar: cargas que se hacen desde la rendición (W-46, horas y fijos por período)") las habilita **`rendicion` = editar** sobre esa obra; los **retiros con remito** también **`control_cantidad` = editar** (fila 9), cualquiera de las dos. **Corregir y anular** cualquier gasto, también los cargados desde W-46, es de W-34 con **`gastos` = editar** (fila 7, F-46). El remito (contexto `remito`) se ve con `gastos` ≥ ver o `control_cantidad` ≥ ver y se asocia con `gastos` = editar o `control_cantidad` = editar (§6.6). El endpoint es de Gastos y su Policy aplica esta regla; es la misma que `gastos.md` §8.1. En la configuración inicial las tres celdas coinciden (administración y compras). *(identidad.md §6.1 y §6.6 `remito`; resuelta DU-R05, que reemplaza a DU-G08)*

### En vivo (cap. 13 "un gasto cargado se ve en la rendición en el momento"; ADR-005, ADR-007)

- **RN-REN-59** **Quien carga** ve el cambio **al instante**: el front invalida la rendición, W-12 y el listado de las obras afectadas al guardar, corregir o anular un gasto (y al guardar W-13 o la lista de W-48). **Los demás** la ven en **≤ 15 s**: W-11, W-44, W-12 y W-48 se vuelven a pedir cada 15 s con la pestaña visible y al volver a enfocarla. *(ADR-005, arquitectura §4.3)*
- **RN-REN-60** La rendición se **cachea por obra** (Redis, clave `rendicion:obra:{id}`, solo los agregados, sin nada que dependa de quién mira) y se **invalida** después del commit con `GastoImputado`, `GastoCorregido`, `GastoAnulado` (todas las obras afectadas, antes y después) y `PresupuestoMontosCambiados`. Invalidar es **pasar a la generación siguiente** de la obra (`rendicion:obra:{id}:g{n}`): una lectura que empezó antes del commit guarda en la clave vieja, que ya nadie lee (QC S3 M-15). El monto de la obra y los permisos **no** se cachean (se leen en cada pedido). W-48 no se cachea. Respuesta con `actualizada_at` (hora de Argentina): "Actualizada 10:42". *(ADR-007; decisión técnica)*
- **RN-REN-61** **Idempotencia y concurrencia** (solo la lista de materiales, lo único que se escribe acá): `POST` con `Idempotency-Key` (mismo envío → un solo material); `PATCH` con `updated_at` → 409 "Alguien cambió esto mientras lo tenías abierto. Actualizá y revisá." (como PU-201). *(ADR-011)*

## 6. Estados de cada pantalla

| Pantalla | Cargando | Vacío | Error | Sin permiso / fuera de alcance |
|---|---|---|---|---|
| W-11 / pestaña Rendición | esqueleto de la tabla con los 12 rubros (sin números) | sin gastos: tabla con gastado 0 y "Todavía no hay gastos cargados en esta obra." en lugar de los últimos gastos; sin presupuesto: RN-REN-07 | "No pudimos cargar la rendición. Probá de nuevo." + **Reintentar** (si falla el polling, se deja lo último que se vio con "No pudimos actualizar · hace 2 minutos") | sin `rendicion`: la pestaña no aparece; por URL, 403 "No tenés acceso a la rendición." · fuera de alcance: 404 "No encontramos esta obra." |
| W-36 | esqueleto de la tabla | "No hay gastos en MATERIALES todavía." | ídem + Reintentar | ídem W-11; rubro inexistente o `epp`: 404 "Ese rubro no tiene detalle propio." |
| W-37 | esqueleto | "No hay gastos en esta obra con estos filtros." | ídem | ídem W-11 |
| W-12 | esqueleto de 4 filas | "No hay obras en ejecución." (con alcance propias: "No tenés obras en ejecución a tu cargo.") | ídem | sin `rendicion`: el ítem no aparece; por URL, 403 |
| W-44 | esqueleto de tarjetas | como W-11 | ídem | como W-11 |
| W-48 | esqueleto | "Todavía no hay materiales cargados para esta obra." (+ "Agregar material" con editar) · sin fuera de presupuesto: la sección no aparece | ídem | sin `control_cantidad`: el acceso no aparece; por URL, 403 · fuera de alcance: 404 |
| "% gastado" (W-05, W-07, W-47) | — | "—" (sin presupuesto, o sin permiso: RN-OBR-04) | "—" (el listado no se cae si falla el puerto: se registra el error) | "—" |

**Cómo se leen dos cosas de esta especificación (QC S3, 25-nov-2026):**
- **Fechas (resuelta DU-G13):** dd/mm/aaaa en toda la app (PU-209). Donde este documento escribe "02-oct", "Hasta
  25-sep" o "del 1-nov al 30-nov", se lee "02/10/2026", "Hasta 25/09/2026" o "del 01/11/2026 al 30/11/2026". Los
  rangos de período siguen el formato de `fechas.ts`.
- **Botón mientras guarda (resuelta DU-G14):** el botón conserva su etiqueta, muestra el indicador de carga y le anuncia
  "Guardando…" al lector de pantalla (`aria-busy`), como en el sistema de diseño.

## 7. Permisos

### 7.1 Filas de la matriz y alcance

| Función (fila) | Nivel | Qué habilita en este módulo | Alcance `propias` = |
|---|---|---|---|
| `rendicion` | ver | W-11 (pestaña), W-36, W-37, W-44, W-12; "% gastado" en W-05/W-07 (lo aplica Obras) | obras donde es responsable |
| `rendicion` | editar | además: las cargas de la rendición (W-46: amortización, retiros, devoluciones, "+ cargar período"), que valida la Policy de Gastos (RN-REN-58, resuelta DU-R05) | ídem |
| `monto_obra` | ver | bloque Monto de la obra y ganancia | ídem |
| `control_cantidad` | ver / editar | W-48 y bajar el remito / agregar, corregir y quitar materiales; "+ cargar retiro con remito" (W-46, sección retiros) | ídem |
| `gastos` | ver / editar | vínculos a W-34 y W-33 / corregir y anular en W-34 lo cargado desde la rendición | ídem |
| `presupuesto_rubro` | ver / editar | acceso a W-13 desde la rendición (no hace falta para ver la columna Presupuestado) | ídem |

Configuración inicial (`identidad.md` §6.3): Rendición E·T Gerencia y Jefatura de Administración y Compras, V·T Dirección,
Gerencia de Operaciones y Jefatura de Proyectos, **V·P Ingeniería de proyectos**; Monto de obra V·T Dirección y Gerencia de
Operaciones, E·T Gerencia y Jefatura de Administración; Control por cantidad E·T administración y Compras, V·T Dirección,
Gerencia de Operaciones y Jefatura de Proyectos, V·P Ingeniería; Calidad, Seguridad e Higiene, RRHH y Pañol sin acceso.

### 7.2 Por pantalla y endpoint

| Acción | Función | Nivel | Alcance | Errores |
|---|---|---|---|---|
| `GET /api/obras/{id}/rendicion` (W-11, W-44) | `rendicion` | ver | sí | 403 · 404 |
| `GET /api/obras/{id}/rendicion/rubros/{clave}` (W-36) | `rendicion` | ver | sí | 403 · 404 |
| `GET /api/obras/{id}/rendicion/por-proveedor` (W-37) | `rendicion` | ver | sí | 403 · 404 · 422 |
| `GET /api/rendiciones` (W-12) | `rendicion` | ver | filtra | 403 |
| `GET /api/obras/{id}/control-cantidad` (W-48) | `control_cantidad` | ver | sí | 403 · 404 |
| `POST /api/obras/{id}/materiales` · `PATCH` · `DELETE /api/materiales/{id}` | `control_cantidad` | editar | sí | 403 · 404 · 409 · 422 |
| `GET /api/obras/{id}/materiales/opciones` (para W-46 y W-10) | `control_cantidad` ≥ ver **o** `gastos` = editar **o** `rendicion` = editar | — | sí | 403 · 404 |
| Bloque `monto` dentro de la rendición | `monto_obra` | ver | sí | (se omite) |

La respuesta de la rendición trae `puede: {ver_monto_obra, abrir_gastos, ver_todos_los_gastos, cargar_sin_documento,
cargar_periodo, ver_control_cantidad, ver_presupuesto_rubro}` para mostrar u ocultar accesos; la API vuelve a verificar todo
(RN-IDE-14). Cada endpoint tiene test de permitido, denegado e IDOR (ADR-006).

## 8. Eventos

| Evento | Lo dispara | Qué hace la rendición |
|---|---|---|
| `GastoImputado {gastoId, obraIds}` | Gastos (alta) | invalida `rendicion:obra:{id}` de cada obra |
| `GastoCorregido {gastoId, obraIds}` (antes ∪ después) | Gastos (corrección) | ídem |
| `GastoAnulado {gastoId, obraIds}` | Gastos (anulación) | ídem |
| `PresupuestoMontosCambiados {obraId}` | Obras (W-13, existe) | ídem |
| `MaterialCambiado {materialId, obraId, accion}` | Rendicion (lista de W-48) | solo `actividades` (W-48 no se cachea) |

La rendición **no genera avisos** (no hay avisos de rendición en el cap. 5 ni en el cap. 9; "los rubros que se pasaron se marcan
en rojo", no se avisan).

## 9. Caso de referencia (test de aceptación)

Datos **ficticios**. Es el test `RendicionCasoDeReferenciaTest` (feature, base de tests) y el E2E de la prueba de la semana 3: con
estos datos cargados por la API de Gastos y de Obras, cada número de abajo tiene que dar **exacto, al centavo**.

### 9.1 Obra y presupuestos

Obra **"Ampliación de nave de ensayo"** (ficticia), cliente "Planta Ejemplo SA", En ejecución, responsable Laura (Ingeniería).

| Presupuesto | Modo | Monto de la obra |
|---|---|---|
| 1-200-2026 REV01 (principal) | **por rubro**: MATERIALES 5.000.000 · INSUMOS 800.000 · SUBCONTRATOS 1.200.000 · LOGISTICA 300.000 · AMORTIZACION 200.000 · MANTENIMIENTO **vacío** · OBRADORES/BAÑOS 150.000 · SERVICIOS GENERALES **vacío** · MAQUINAS/HERRAMIENTAS 400.000 · VIATICOS 600.000 · FIJOS 500.000 · HORAS LIQUIDADAS 2.000.000 → total 11.150.000 | 15.000.000 |
| 1-200-2026 A1 (adicional) | **solo total**: 1.000.000 | 1.300.000 |

Lista de materiales (W-48), del presupuesto principal: Cemento x 50 kg · bolsa · 150 · 9.800 — Hormigón H-21 · m³ · 40 · 100.000 —
Endurecedor superficial · bolsa · 50 · 18.000 — Malla sima · paño · 30 · 21.000.

Otra obra en ejecución, **"Obra de control"** (2-300-2026, ficticia, responsable Hernán): principal por rubro con SUBCONTRATOS
250.000 y MATERIALES 750.000 (total 1.000.000).

### 9.2 Renglones cargados (en Gastos)

| # | Comprobante (origen) | Fecha efectiva | Proveedor | Concepto | Cant. | Precio unit. | Total | Obra | Rubro | Material | ¿Suma? |
|---|---|---|---|---|---|---|---|---|---|---|---|
| 1 | Factura 0001-00000101 | 02-oct | Corralón Ejemplo | Cemento x 50 kg | 100 | 9.850,50 | 985.050,00 | Ampliación | MATERIALES | Cemento | sí |
| 2 | ídem | 02-oct | Corralón Ejemplo | Guantes de trabajo (par) | 20 | 3.100,00 | 62.000,00 | Ampliación | **EPP** | Guantes (fuera de presupuesto) | sí → INSUMOS |
| 3 | ídem | 02-oct | Corralón Ejemplo | Disco de corte 230 mm | 10 | 4.333,33 | 43.333,30 | Ampliación | INSUMOS | — | sí |
| 4 | ídem | 02-oct | Corralón Ejemplo | Flete | 1 | 45.000,00 | 45.000,00 | **sin obra** | LOGISTICA | — | **no** |
| 5 | Factura 0004-00000020 | 08-oct | Hormigonera Ejemplo | Hormigón H-21 (m³) | 12,5 | 101.234,57 | **1.265.432,13** (1.265.432,125 redondeado) | Ampliación | MATERIALES | Hormigón | sí |
| 6 | ídem | 08-oct | Hormigonera Ejemplo | Flete hormigón | 1 | 260.000,00 | 260.000,00 | Ampliación | LOGISTICA | — | sí |
| 7 | Factura 0002-00000555 (**anulada**) | 10-oct | Ferretería Ejemplo | Tornillos autoperforantes | 500 | 12,00 | 6.000,00 | Ampliación | INSUMOS | — | **no** |
| 8 | **Devolución** al proveedor (resuelta DU-G05: no hay nota de crédito) | 12-oct | Corralón Ejemplo | Cemento x 50 kg (devuelto al corralón) | −5 | 9.850,50 | **−49.252,50** | Ampliación | MATERIALES | — (sin material asociado) | sí (resta) |
| 9 | Retiro · remito 0001-00000077 | 14-oct | Tecnobahía | Endurecedor superficial (bolsa) | 30 | 18.000,00 | 540.000,00 | Ampliación | MATERIALES | Endurecedor | sí |
| 10 | Sin documento (rendido por: supervisor de obra) | 15-oct | genérico | Comida cuadrilla (6 personas) | 6 | 7.000,00 | 42.000,00 | Ampliación | VIATICOS | — | sí |
| 11 | ídem | 15-oct | genérico | Combustible camioneta | 1 | 68.000,00 | 68.000,00 | Ampliación | LOGISTICA | — | sí |
| 12 | Consumo de stock | 16-oct | — | Antiparras | 10 | 2.500,00 | 25.000,00 | Ampliación | **EPP** | — | sí → INSUMOS |
| 13 | Factura 0003-00000300 | 18-oct | Taller Ejemplo | Repuesto bomba de agua | 1 | 35.000,00 | 35.000,00 | Ampliación | MANTENIMIENTO | — | sí |
| 14 | Factura 0007-00000045 | 19-oct | Subcontratista Ejemplo | Adelanto 50 % demolición | 1 | 700.000,00 | 700.000,00 | Ampliación | SUBCONTRATOS | — | sí |
| 15 | ídem | 19-oct | Subcontratista Ejemplo | Andamio | 1 | 300.000,00 | 300.000,00 | **Obra de control** | SUBCONTRATOS | — | **no** (suma en la otra obra) |
| 16 | Devolución | 20-oct | — | Cemento x 50 kg (sobrante) | −4 | 9.850,50 | **−39.402,00** | Ampliación | MATERIALES | Cemento | sí (resta) |
| 17 | Amortización | 31-oct | — | Amortización de herramientas | 1 | 190.000,00 | 190.000,00 | Ampliación | AMORTIZACION | — | sí |
| 18 | Período 1 al 15-oct | 15-oct | — | Horas liquidadas 1 al 15-oct | 1 | 950.000,00 | 950.000,00 | Ampliación | HORAS LIQUIDADAS | — | sí |
| 19 | Período 16 al 31-oct | 31-oct | — | Horas liquidadas 16 al 31-oct | 1 | 1.130.000,75 | 1.130.000,75 | Ampliación | HORAS LIQUIDADAS | — | sí |
| 20 | Período 1 al 31-oct | 31-oct | — | Fijos 1 al 31-oct | 1 | 260.000,00 | 260.000,00 | Ampliación | FIJOS | — | sí |
| 21 | Período 1 al 30-nov | 30-nov | — | Fijos 1 al 30-nov | 1 | 260.000,00 | 260.000,00 | Ampliación | FIJOS | — | sí |

### 9.3 Resultado esperado · W-11 (y W-44)

| Rubro | Presupuestado | Gastado | Saldo | % (API · pantalla) | Pasado |
|---|---|---|---|---|---|
| MATERIALES | 5.000.000 | 2.701.827,63 (1 + 5 + 8 + 9 + 16) | 2.298.172,37 | 54.04 · 54 % | no |
| INSUMOS · incluye EPP | 800.000 | 130.333,30 (3 + 2 + 12) | 669.666,70 | 16.29 · 16 % | no |
| SUBCONTRATOS | 1.200.000 | 700.000 | 500.000 | 58.33 · 58 % | no |
| LOGISTICA | 300.000 | 328.000 (6 + 11) | −28.000 | 109.33 · 109 % | **sí** |
| AMORTIZACION | 200.000 | 190.000 | 10.000 | 95.00 · 95 % | no |
| MANTENIMIENTO | 0 | 35.000 | −35.000 | null · — | **sí** (sin presupuesto) |
| OBRADORES/BAÑOS | 150.000 | 0 | 150.000 | 0.00 · 0 % | no |
| SERVICIOS GENERALES | 0 | 0 | 0 | null · — | no |
| MAQUINAS/HERRAMIENTAS | 400.000 | 0 | 400.000 | 0.00 · 0 % | no |
| VIATICOS | 600.000 | 42.000 | 558.000 | 7.00 · 7 % | no |
| FIJOS | 500.000 | 520.000 (20 + 21) | −20.000 | 104.00 · 104 % | **sí** |
| HORAS LIQUIDADAS | 2.000.000 | 2.080.000,75 (18 + 19) | −80.000,75 | 104.00 · 104 % | **sí** |
| Sin desglose por rubro (1-200-2026 A1) | 1.000.000 | — | — | — | — |
| **Total de la obra** | **12.150.000** | **6.727.161,68** | **5.422.838,32** | 55.37 · 55 % | no |

- Leyenda de obra mixta (RN-REN-11): "1.000.000 de esta obra no tienen desglose por rubro (presupuesto 1-200-2026 A1): cada rubro se compara solo contra lo desglosado."
- **No suman**: renglón 4 (sin obra, 45.000), 7 (anulado, 6.000) y 15 (otra obra, 300.000). Si sumaran, el total daría 7.078.161,68.
- Monto y ganancia (con `monto_obra` ≥ ver): "Monto de la obra: 16.300.000 · Costo presupuestado: 12.150.000 · Ganancia prevista: 4.150.000 (25 %)" (25.46) y "Gastado hasta hoy: 6.727.161,68 · Ganancia al día: 9.572.838,32 (59 %)" (58.73). Laura (Ingeniería, sin `monto_obra`): el JSON **no tiene** `monto`.
- W-44: orden de las tarjetas MANTENIMIENTO (sin presupuesto) · LOGISTICA 109 % · FIJOS 104 % · HORAS LIQUIDADAS 104 % (empate a 2 decimales → orden del rubro) · AMORTIZACION 95 % · SUBCONTRATOS 58 % · MATERIALES 54 % · INSUMOS 16 % · VIATICOS 7 % · OBRADORES/BAÑOS 0 % · MAQUINAS/HERRAMIENTAS 0 % · SERVICIOS GENERALES (—).

### 9.4 Resultado esperado · W-36, W-37, W-48, W-12 y W-05

- **W-36 MATERIALES** (fecha, la más nueva primero): 20-oct Devolución · — · Cemento x 50 kg (sobrante) · −4 · 9.850,50 · −39.402 ·
  14-oct Retiro · remito 0001-00000077 · Tecnobahía · Endurecedor superficial (bolsa) · 30 · 18.000 · 540.000 · 12-oct Devolución ·
  Corralón Ejemplo · Cemento x 50 kg (devuelto al corralón) · −5 · 9.850,50 · −49.252,50 · 08-oct Factura 0004-00000020 ·
  Hormigonera Ejemplo · Hormigón H-21 (m³) · 12,5 · 101.234,57 · 1.265.432,13 · 02-oct Factura 0001-00000101 · Corralón Ejemplo ·
  Cemento x 50 kg · 100 · 9.850,50 · 985.050. Pie: "Total MATERIALES · 5 renglones · 2.701.827,63".
- **W-36 INSUMOS**: 16-oct Consumo de stock · Antiparras · 25.000 [EPP] · 02-oct Disco de corte 230 mm · 43.333,30 · 02-oct
  Guantes de trabajo (par) · 62.000 [EPP] (a igual fecha, el cargado último primero: 3 antes que 2). Pie: "Total INSUMOS · 3
  renglones · 130.333,30 · de los cuales EPP: 87.000". El tornillo anulado no aparece.
- **W-36 HORAS LIQUIDADAS**: 31-oct "Período 16 al 31-oct" 1.130.000,75 · 15-oct "Período 1 al 15-oct" 950.000. Pie 2.080.000,75.
- **W-37** (todos los rubros, toda la obra): Hormigonera Ejemplo 1.525.432,13 · 1 (Hormigón H-21 (m³) 12,5 · 1.265.432,13; Flete
  hormigón 1 · 260.000) · Corralón Ejemplo 1.041.130,80 · 1 (Cemento x 50 kg 100 · 985.050; Guantes de trabajo (par) 20 · 62.000;
  Disco de corte 230 mm 10 · 43.333,30; Cemento x 50 kg (devuelto al corralón) −5 · −49.252,50) · Subcontratista Ejemplo 700.000 · 1 · Tecnobahía
  540.000 · — · Taller Ejemplo 35.000 · 1 · **Sin documento** (al final) 2.885.598,75 · — (Fijos 1 al 31-oct, Fijos 1 al 30-nov,
  Horas liquidadas ×2, Amortización, Combustible, Comida, Antiparras, Cemento sobrante −4 · −39.402). Pie: "6 grupos ·
  6.727.161,68 · 4 comprobantes" (la devolución al corralón no es un comprobante con número). Con **Período 1-nov → 30-nov**: solo "Sin documento · Fijos 1 al 30-nov · 260.000".
  Con **Rubro: INSUMOS**: Corralón Ejemplo 105.333,30 · 1 · Sin documento 25.000 · total 130.333,30.
- **W-48 lista**: columnas de fecha 02-oct · 08-oct · 14-oct · 20-oct (4 fechas, sin "Hasta").

  | Material | Unidad | Presup. | 02-oct | 08-oct | 14-oct | 20-oct | Total retirado | Resta llevar | Precio unit. | Total |
  |---|---|---|---|---|---|---|---|---|---|---|
  | Cemento x 50 kg | bolsa | 150 | 100 | | | −4 | 96 | 54 | 9.800 | 945.648 |
  | Hormigón H-21 | m³ | 40 | | 12,5 | | | 12,5 | 27,5 | 100.000 | 1.265.432,13 |
  | Endurecedor superficial | bolsa | 50 | | | 30 | | 30 | 20 | 18.000 | 540.000 |
  | Malla sima | paño | 30 | | | | | 0 | 30 | 21.000 | 0 |

  Total de la lista **2.751.080,13** · "Gastado en MATERIALES en la rendición: 2.701.827,63" · "Renglones de MATERIALES sin material
  asociado: −49.252,50" (la devolución al corralón del renglón 8, cargada sin material). Fuera de presupuesto: Guantes de trabajo · par · 02-oct · 20 · 62.000 · EPP.
- **W-12**: Ampliación de nave de ensayo · Planta Ejemplo SA · 12.150.000 · 6.727.161,68 · 55 % · 4 — Obra de control · 1.000.000 ·
  300.000 · 30 % · 1 (SUBCONTRATOS 120 %). Total: 13.150.000 · 7.027.161,68 · **53 %** (53.44) · 5. Laura (V·P, responsable solo
  de la primera) ve una fila y el total de esa fila.
- **W-05**: "% del presupuesto gastado" 55 % y 30 %, ninguno en rojo; `porcentajesGastados` = `{ampliación: "55.37", control: "30.00"}`.
- **Anular** la factura 0004-00000020 (renglones 5 y 6): MATERIALES baja a 1.436.395,50, LOGISTICA a 68.000 (deja de estar
  pasada), total a 5.201.729,55 (42.81 → 43 %), Hormigón vuelve a 0 retirado; en la próxima lectura (≤ 15 s para otros, al
  instante para quien anula).

## 10. Casos borde (datos ficticios)

| # | Caso | Resultado esperado |
|---|---|---|
| 1 | Obra recién creada, sin presupuesto ni gastos | W-11 con los 12 rubros en 0 y "—", el aviso de RN-REN-07 y "Todavía no hay gastos cargados en esta obra."; W-05 "—"; en W-12 (si está En ejecución) al final con "—". |
| 2 | Obra sin presupuesto cargado con 3 gastos | Gastado por rubro y total; ningún rubro en rojo; `porcentajesGastados` = `null`. |
| 3 | Obra solo total (un presupuesto de 3.600.000) con 432.000 gastados en 3 rubros | Rubros con gastado y "—"; "Sin desglose por rubro 3.600.000"; Total 3.600.000 · 432.000 · 3.168.000 · 12 %; W-12 "Rubros pasados: —". |
| 4 | Solo total con gastado 3.700.000 | Total en rojo (103 %); ningún rubro en rojo; W-05 en rojo. |
| 5 | Un rubro gasta exactamente lo presupuestado (950.000 de 950.000) | 100 %, saldo 0, **no** pasado. Con un centavo más (950.000,01), pasado. |
| 6 | El adicional A2 está "sin cargar" | No suma; aviso "El presupuesto 1-200-2026 A2 todavía no tiene montos cargados: no suma en lo presupuestado." |
| 7 | Administración corrige en W-13 MATERIALES de 5.000.000 a 5.500.000 | La rendición lo muestra en la próxima lectura (evento `PresupuestoMontosCambiados`); 49 %. |
| 8 | Un renglón de EPP se corrige a INSUMOS en W-34 | INSUMOS no cambia en el resumen (EPP ya sumaba ahí); en W-36 pierde la marca "EPP" y el subtotal de EPP baja. |
| 9 | Un renglón se corrige de la obra A a la obra B | Baja en A y sube en B en la misma lectura (`GastoCorregido` con las dos obras). Si tenía un material de A, Gastos rechaza el cambio (§12.4.1, obligación 4). |
| 10 | Rubro con solo una devolución (−39.402) y presupuesto 100.000 | Gastado −39.402, saldo 139.402, % "−39 %", no pasado. |
| 11 | Período de horas 16-oct al 31-oct cargado dos veces | Aviso "Ya hay HORAS LIQUIDADAS de Ampliación de nave de ensayo del 16 al 31-oct (1.130.000,75). ¿Cargar igual?"; si confirma, suma dos veces (se corrige anulando una). |
| 12 | Período con fin antes del inicio | 422 "El 'hasta' no puede ser anterior al 'desde'." (Gastos, RN-GAS-27). |
| 13 | Laura (Ingeniería, V·P) abre la rendición de una obra de Hernán | 404 "No encontramos esta obra." La suya: 200, sin `monto`. |
| 14 | Calidad (sin `rendicion`) pide `GET /api/rendiciones` | 403; no ve "Rendiciones" en la barra; en W-05 ve "—" en "% gastado". |
| 15 | Diego (Gerencia de Administración) abre W-11 | Ve el bloque de monto y ganancia y los accesos "+ cargar renglón sin documento" y "+ cargar período". |
| 16 | Gerencia de Operaciones (Rendición V·T, Monto V·T) | Ve todo, con "· solo lectura"; sin accesos de carga; ve monto y ganancia. |
| 17 | Monto cargado solo en el principal | Suma ese y avisa "El presupuesto 1-200-2026 A1 no tiene monto cargado: no suma en el monto de la obra." |
| 18 | Gastado supera el monto de la obra | Ganancia al día negativa, en rojo ("−250.000 (−2 %)"). |
| 19 | Obra pasa a Terminada | Sale de W-12; su rendición sigue igual desde la ficha (pestaña Terminadas de W-05). |
| 20 | Obra Confirmada con un gasto | Rendición visible desde la ficha; no aparece en W-12. |
| 21 | Renglón con fecha futura (el 24-nov cargan Fijos del 1 al 30-nov: fecha efectiva 30-nov) | Suma (acumulado, sin corte); en W-37 "este mes" entra. Una factura fechada mañana no llega: Gastos la rechaza (RN-GAS-04). |
| 22 | `GET …/rendicion/rubros/epp` | 404 "Ese rubro no tiene detalle propio." (EPP se ve dentro de INSUMOS). |
| 23 | Retiro de 6 bolsas de cemento de más (total retirado 156 de 150) | "Resta llevar" −6 en rojo; no frena la carga (el sistema muestra, no decide, D-006). |
| 24 | Quitar "Malla sima" (sin renglones) | Se quita y queda en `actividades`. Quitar "Cemento x 50 kg" (con renglones) → 422 "Cemento x 50 kg ya tiene 2 retiros o pedidos: no se puede quitar. Corregí la cantidad presupuestada." |
| 25 | Agregar "cemento  X 50 KG" a una lista que ya tiene "Cemento x 50 kg" | 422 "Ya está en la lista: Cemento x 50 kg." |
| 26 | Material fuera de presupuesto "Guantes de trabajo" al que le cargan cantidad presupuestada 40 | Pasa a la lista, con su retirado (20) y resta llevar 20; sale de "Fuera de presupuesto". |
| 27 | Dos personas corrigen la cantidad del mismo material | La segunda recibe 409 "Alguien cambió esto mientras lo tenías abierto. Actualizá y revisá." |
| 28 | Un material con retiros en 9 fechas distintas | 6 columnas con las fechas más nuevas y "Hasta {fecha}" con la suma de las 3 más viejas. |
| 29 | Renglón de factura asociado al material con precio bonificado al 100 % (precio 0) | Suma en cantidad retirada; 0 en el total; la rendición no cambia. |
| 30 | Anular un gasto cuyo renglón era un retiro | Sale del retirado de W-48 y de la rendición; el material sigue sin poder quitarse (el renglón anulado lo referencia). |
| 31 | Doble clic en "Agregar material" con la misma `Idempotency-Key` | Un solo material; la segunda respuesta es la misma 201. |
| 32 | Obra con 165 gastos (≈ 500 renglones) | W-11 responde en < 300 ms sin cache y con 2 consultas de agregado; W-36 sin páginas. |
| 33 | W-12 con 15 obras en ejecución | 3 consultas en total (obras, presupuestado, gastado), no una por obra. |
| 34 | La rendición queda abierta 10 minutos mientras Diego carga 3 facturas en otra PC | Se actualiza sola cada 15 s; "Actualizada 10:42". |

## 11. Celular y polling

- W-11, W-44, W-12 y W-48 se vuelven a pedir cada **15 s** con la pestaña visible y al volver a enfocarla (ADR-005); con la pestaña
  oculta, no. Las acciones propias (guardar un gasto, W-13, la lista de W-48) invalidan al instante las consultas afectadas.
- Celular: W-44 (RN-REN-44…46), W-12 en tarjetas, W-36 en tarjetas, W-48 en tarjetas (RN-REN-54); 390 px sin desplazamiento
  horizontal; todo de lectura.

## 12. Contrato de API sugerido

Envelope `{ data, message, errors }`. Fechas `YYYY-MM-DD`; timestamps ISO-8601 con zona `America/Argentina/Buenos_Aires`;
importes y cantidades como **string decimal** (ADR-008); porcentajes como string de 2 decimales o `null`. Códigos: 401, 403, **404**
(también fuera de alcance), 409 (versión), 422 (por campo). Rutas en `app/Modules/Rendicion/routes.php`.

### 12.1 Rendición

| Método y ruta | Payload / query | Respuesta `data` | Errores |
|---|---|---|---|
| `GET /api/obras/{id}/rendicion` | — | `RendicionDeObra` | 403 · 404 |
| `GET /api/obras/{id}/rendicion/rubros/{clave}?orden=fecha\|proveedor\|total&dir=asc\|desc` | `clave` ∈ los 12 del resumen | `DetalleDeRubro` | 403 · 404 · 422 (orden inválido) |
| `GET /api/obras/{id}/rendicion/por-proveedor?rubro=&desde=&hasta=` | `rubro` ∈ los 12; `desde`/`hasta` opcionales (hasta ≥ desde) | `PorProveedor` | 403 · 404 · 422 |
| `GET /api/rendiciones` | — | `RendicionesEnCurso` | 403 |

```json
// RendicionDeObra (caso de referencia, recortado)
{
  "obra": { "id": 21, "numero": "1-200-2026", "nombre_trabajo": "Ampliación de nave de ensayo", "cliente": "Planta Ejemplo SA", "estado": "en_ejecucion" },
  "actualizada_at": "2026-11-24T10:42:00-03:00",
  "presupuesto": {
    "situacion": "mixta",                 // sin_presupuesto | por_rubro | solo_total | mixta
    "presupuestos": [
      { "id": 40, "numero": "1-200-2026", "revision": "REV01", "es_principal": true,  "modo_carga": "por_rubro",  "total": "11150000.00" },
      { "id": 41, "numero": "1-200-2026 A1", "revision": null, "es_principal": false, "modo_carga": "solo_total", "total": "1000000.00" }
    ],
    "sin_cargar": []                      // números de los presupuestos sin cargar (RN-REN-12)
  },
  "rubros": [
    { "clave": "materiales", "nombre": "MATERIALES", "incluye": [], "carga_por_periodo": false,
      "presupuestado": "5000000.00", "gastado": "2701827.63", "saldo": "2298172.37", "porcentaje": "54.04", "pasado": false, "sin_presupuesto": false },
    { "clave": "insumos", "nombre": "INSUMOS", "incluye": ["epp"], "carga_por_periodo": false,
      "presupuestado": "800000.00", "gastado": "130333.30", "saldo": "669666.70", "porcentaje": "16.29", "pasado": false, "sin_presupuesto": false },
    { "clave": "mantenimiento", "nombre": "MANTENIMIENTO", "incluye": [], "carga_por_periodo": false,
      "presupuestado": "0.00", "gastado": "35000.00", "saldo": "-35000.00", "porcentaje": null, "pasado": true, "sin_presupuesto": true }
    /* … los 12, en orden; en obra solo total o sin presupuesto: presupuestado/saldo/porcentaje null y pasado false */
  ],
  "sin_desglose": { "presupuestado": "1000000.00", "presupuestos": ["1-200-2026 A1"] },   // null si no hay
  "total": { "presupuestado": "12150000.00", "gastado": "6727161.68", "saldo": "5422838.32", "porcentaje": "55.37", "pasado": false, "rubros_pasados": 4 },
  "monto": {                              // la clave NO viaja sin monto_obra ≥ ver
    "monto_obra": "16300000.00",
    "por_presupuesto": [ { "numero": "1-200-2026", "monto": "15000000.00" }, { "numero": "1-200-2026 A1", "monto": "1300000.00" } ],
    "sin_monto": [],
    "ganancia_prevista": "4150000.00", "porcentaje_prevista": "25.46",
    "ganancia_al_dia": "9572838.32",   "porcentaje_al_dia": "58.73"
  },
  "ultimos_gastos": [
    { "renglon_id": 921, "gasto_id": 310, "fecha": "2026-11-30", "documento": "Período 1 al 30-nov", "proveedor": null,
      "concepto": "Fijos 1 al 30-nov", "rubro": { "clave": "fijos", "nombre": "FIJOS" }, "total": "260000.00",
      "empleado": null }                  // parte de horas con gastos = editar: el nombre (RN-REN-63)
    /* … hasta 10 */
  ],
  "puede": { "ver_monto_obra": true, "abrir_gastos": true, "ver_todos_los_gastos": true, "cargar_sin_documento": true,
             "cargar_periodo": true, "ver_control_cantidad": true, "ver_presupuesto_rubro": true }
}
// DetalleDeRubro
{
  "rubro": { "clave": "insumos", "nombre": "INSUMOS", "incluye": ["epp"] },
  "presupuestado": "800000.00", "gastado": "130333.30", "saldo": "669666.70", "porcentaje": "16.29", "pasado": false,
  "subtotal_epp": "87000.00",            // solo en INSUMOS
  "cantidad_renglones": 3,
  "renglones": [
    { "renglon_id": 912, "gasto_id": 305, "fecha": "2026-10-16", "documento": "Consumo de stock", "proveedor": null,
      "concepto": "Antiparras", "cantidad": "10.000", "precio_unitario": "2500.00", "total": "25000.00", "rubro_clave": "epp",
      "empleado": null, "resumido": false }   // parte de horas: "empleado" con gastos = editar; si no, resumido: true y precio_unitario: null (RN-REN-63)
  ]
}
// PorProveedor
{
  "filtros": { "rubro": null, "desde": null, "hasta": null },
  "grupos": [
    { "proveedor": { "id": 8, "nombre": "Hormigonera Ejemplo" }, "sin_documento": false, "total": "1525432.13", "comprobantes": 1,
      "conceptos": [ { "concepto": "Hormigón H-21 (m³)", "cantidad": "12.500", "total": "1265432.13" },
                     { "concepto": "Flete hormigón", "cantidad": "1.000", "total": "260000.00" } ] },
    { "proveedor": null, "sin_documento": true, "total": "2885598.75", "comprobantes": null, "conceptos": [ /* … */ ] }
  ],
  "total": "6727161.68", "comprobantes": 4, "cantidad_grupos": 6
}
// RendicionesEnCurso
{
  "obras": [
    { "obra": { "id": 21, "numero": "1-200-2026", "nombre_trabajo": "Ampliación de nave de ensayo" }, "cliente": "Planta Ejemplo SA",
      "presupuestado": "12150000.00", "gastado": "6727161.68", "porcentaje": "55.37", "pasado": false, "rubros_pasados": 4 }
  ],
  "total": { "presupuestado": "13150000.00", "gastado": "7027161.68", "porcentaje": "53.44", "rubros_pasados": 5 }
}
```

### 12.2 Control por cantidad

| Método y ruta | Payload | Respuesta `data` | Errores |
|---|---|---|---|
| `GET /api/obras/{id}/control-cantidad` | — | `ControlPorCantidad` | 403 · 404 |
| `POST /api/obras/{id}/materiales` | `{nombre, unidad, cantidad_presupuestada, precio_presupuestado?, presupuesto_id?}` | `Material` (201) | 403 · 404 · 422 |
| `PATCH /api/materiales/{id}` | los mismos campos, opcionales, + `updated_at?` (completar `cantidad_presupuestada` en uno fuera de presupuesto lo pasa a la lista) | `Material` | 403 · 404 · 409 · 422 |
| `DELETE /api/materiales/{id}` | — | `null` | 403 · 404 · 422 (tiene renglones, RN-REN-53) |
| `GET /api/obras/{id}/materiales/opciones?buscar=` | — | `[{id, nombre, unidad, fuera_de_presupuesto, resta_llevar, precio_presupuestado}]` (máx. 30; la lista primero). Es **el único** endpoint de materiales: lo usan W-10 y W-46 (Gastos no tiene uno propio) | 403 · 404 |

```json
// ControlPorCantidad (caso de referencia, recortado)
{
  "columnas": { "fechas": ["2026-10-02", "2026-10-08", "2026-10-14", "2026-10-20"], "hasta": null },  // hasta: {"fecha": "2026-09-25"} si se agrupan
  "materiales": [
    { "id": 1, "nombre": "Cemento x 50 kg", "unidad": "bolsa", "presupuesto": { "id": 40, "numero": "1-200-2026" },
      "cantidad_presupuestada": "150.000", "precio_presupuestado": "9800.00",
      "retirado_por_fecha": { "2026-10-02": "100.000", "2026-10-20": "-4.000" }, "retirado_hasta": null,
      "total_retirado": "96.000", "resta_llevar": "54.000", "total": "945648.00", "updated_at": "2026-11-20T09:00:00-03:00" }
  ],
  "total_lista": "2751080.13",
  "materiales_en_rendicion": { "gastado": "2701827.63", "sin_material": "-49252.50" },
  "fuera_de_presupuesto": [
    { "material_id": 5, "nombre": "Guantes de trabajo", "unidad": "par", "fecha": "2026-10-02", "cantidad": "20.000",
      "total": "62000.00", "rubro": { "clave": "epp", "nombre": "EPP" }, "renglon_id": 902, "gasto_id": 300 }
  ],
  "puede": { "editar": true, "cargar_retiro": true }
}
```

### 12.3 Buscador

La rendición no agrega grupo al buscador general (F-03 la cubre Gastos con el número de factura).

### 12.4 Contratos con otros módulos

#### 12.4.1 Lo que la rendición necesita de Gastos — `Gastos\Contracts\GastadoDeObras` (nuevo; lo implementa Gastos)

```php
namespace App\Modules\Gastos\Contracts;

interface GastadoDeObras
{
    /**
     * Gastado por obra y rubro, SIN aplicar EPP→INSUMOS (lo hace Rendicion). Excluye anulados y renglones sin obra;
     * incluye negativos. Una sola consulta para todas las obras (W-12, W-05). Obra sin renglones: [].
     *
     * @param  list<int>  $obraIds
     * @return array<int, array<string, numeric-string>>  obraId => [rubroClave => total "2701827.63"]
     */
    public function totalesPorRubro(array $obraIds): array;

    /**
     * Renglones de una obra de gastos no anulados (W-11 últimos gastos, W-36, W-37, W-48). Sin N+1 (proveedor y gasto cargados).
     *
     * @return list<RenglonImputado>
     */
    public function renglonesDeObra(int $obraId, FiltroRenglones $filtro): array;

    /**
     * Cuántos renglones usan cada material, ANULADOS INCLUIDOS (para no quitar un material referenciado, RN-REN-53).
     *
     * @param  list<int>  $materialIds
     * @return array<int, int>
     */
    public function contarRenglonesPorMaterial(array $materialIds): array;
}

final readonly class FiltroRenglones
{
    /** @param list<string>|null $rubroClaves  null = todos (Rendicion pasa ['insumos','epp'] para INSUMOS) */
    public function __construct(
        public ?array $rubroClaves = null,
        public ?string $desde = null,          // fecha efectiva, inclusive (Y-m-d)
        public ?string $hasta = null,
        public bool $soloConMaterial = false,  // W-48
        public string $orden = 'fecha',        // fecha | proveedor | total
        public string $dir = 'desc',
        public ?int $limite = null,            // 10 para "últimos gastos"
    ) {}
}

final readonly class RenglonImputado
{
    public function __construct(
        public int $renglonId,
        public int $gastoId,
        public string $fecha,                  // fecha EFECTIVA (§4.3) = gasto_renglones.fecha: en facturas, la de la factura;
                                               // horas y fijos: periodo_hasta (resuelta DU-G01 y DU-R04)
        public string $origen,                 // factura | sin_comprobante | combustible | consumo_stock | amortizacion |
                                               // retiro_remito | devolucion | monto_periodo | saldo_inicial (no hay nota_credito: DU-G05)
        public string $tipoDocumento,          // factura | sin_documento
        public ?string $numero,                // N.º de factura o de remito
        public ?int $proveedorId,
        public ?string $proveedorNombre,
        public bool $proveedorGenerico,
        public string $concepto,
        public string $cantidad,               // "12.500" (escala 3, con signo)
        public string $precioUnitario,         // "101234.57"
        public string $total,                  // "1265432.13" (con signo)
        public string $rubroClave,             // nunca null: el renglón tiene obra
        public ?int $materialId,
        public ?string $periodoDesde,          // solo monto_periodo
        public ?string $periodoHasta,
        public ?string $rendidoPor,
    ) {}
}
```

**Eventos que Rendicion escucha** (en `Gastos\Domain\Events`, después del commit): `GastoImputado`, `GastoCorregido`,
`GastoAnulado`, cada uno con `gastoId` y `obraIds: list<int>` = **todas** las obras afectadas (en una corrección, las de antes
**y** las de después).

**Invariantes que la rendición da por ciertas** (Gastos las garantiza y las testea):
1. `total = round(cantidad × precio_unitario, 2)`, mitad para arriba en valor absoluto, calculado en el backend.
2. Renglón con obra ⇒ rubro de la lista cerrada (CHECK del modelo).
3. Anular es por gasto (cabecera) y saca a todos sus renglones; no se borra nada.
4. `material_id` solo apunta a un material **de la misma obra del renglón** (lo valida con `MaterialesDeObra::perteneceAObra`); si
   se cambia la obra de un renglón con material, se rechaza: "El material Cemento x 50 kg es de otra obra: quitá el material o
   elegí uno de esta obra."
5. Horas y fijos: origen `monto_periodo`, rubro `horas_liquidadas` o `fijos`, `periodo_desde` ≤ `periodo_hasta`, un renglón por
   período y por obra (RN-REN-17).
6. Negativos solo en devoluciones (resuelta DU-G05: no hay nota de crédito).
7. Cargas "desde la rendición" (W-46, horas y fijos) autorizadas según RN-REN-58 (resuelta DU-R05).
8. Todo retiro tiene material (de la lista o fuera de presupuesto, creado con `MaterialesDeObra::crearFueraDePresupuesto`) y su
   cantidad va en la unidad del material (`gastos.md` RN-GAS-14, RN-GAS-24).

Lo que Gastos garantiza de cada invariante, con su test, está en `gastos.md` §12.5.

#### 12.4.2 Obras — lo que se usa y lo que hay que sumar al contrato

| Contrato | Estado | Métodos | Uso |
|---|---|---|---|
| `Obras\Contracts\ObrasConsulta` | existe | `existe`, `resumen`, `esResponsable`, `obrasDondeEsResponsable`, `presupuestadoPorRubro(obraId): PresupuestadoDeObra` | W-11, alcance |
| `PresupuestadoDeObra` | **ampliar** | sumar `presupuestos: list<{id, numero, revision, esPrincipal, modoCarga, total}>` (para `situacion`, "sin cargar" y saber si hay presupuesto por rubro: N10, RN-REN-07, RN-REN-12) | W-11, W-12 |
| `ObrasConsulta::presupuestadoDeObras(list<obraId>): array<int, PresupuestadoDeObra>` | **nuevo** | una consulta para todas las obras | W-12, puerto `AvanceGastoDeObra` |
| `ObrasConsulta::montoDeObra(obraId): ?MontoDeObra {total, porPresupuesto: list<{numero, monto|null}>}` | **nuevo** | `null` si ningún presupuesto tiene monto; Rendicion decide si lo muestra (`monto_obra`) | RN-REN-23, 24 |
| `ObrasConsulta::obrasEnEjecucion(?list<obraId> soloEstas): list<ObraResumen>` | **nuevo** | las En ejecución (o las de esa lista, para el alcance) | W-12 |
| `Obras\Application\Ports\AvanceGastoDeObra` | Rendicion **implementa** (`AvanceGastoDeObraEloquent`) | `porcentajesGastados`, `pasadas` (RN-REN-42) | W-05, W-07, W-47 |
| `Obras\Application\Ports\MaterialesDePresupuesto` | **nuevo puerto**; Rendicion implementa | `tieneMateriales(presupuestoId): bool` | RN-OBR-24 (no quitar un adicional con materiales) |
| Evento `PresupuestoMontosCambiados {obraId}` | existe | — | invalida el cache (RN-REN-60) |

#### 12.4.3 Lo que Rendicion expone

| Contrato | Para | Métodos |
|---|---|---|
| `Rendicion\Contracts\MaterialesDeObra` | Gastos (W-10, W-46) | `opciones(obraId, ?texto): list<MaterialOpcion{id, nombre, unidad, fueraDePresupuesto, restaLlevar, precioPresupuestado}>` (el precio, para sugerirlo en el retiro: RN-GAS-24) · `perteneceAObra(materialId, obraId): bool` · `crearFueraDePresupuesto(obraId, nombre, unidad, userId): int` (dentro de la transacción de Gastos; si ya existe por nombre en la obra, devuelve ese id) |
| `Rendicion\Contracts\CatalogoDeRubros` | Gastos, Obras, Proveedores | `imputables(): list<{id, clave, nombre, cargaPorPeriodo}>` (13, en orden; `cargaPorPeriodo` para que Gastos no ofrezca FIJOS y HORAS LIQUIDADAS en una factura, RN-GAS-12) · `presupuestables()` (12) · `resumeEn(clave): ?clave` |
| `Shared\Adjuntos\AutorizaAdjuntos` | — | Rendicion **no** tiene adjuntos propios: el remito es del retiro (contexto `remito`, Gastos). |

Alias de `Relation::morphMap` (en `RendicionServiceProvider`): `material` (para `actividades`).

## 13. Cómo comparar con su Excel (prueba del cliente de la semana 3)

Para que la comparación "rubro por rubro" sea justa, el entorno de prueba muestra al lado de W-11, en el instructivo de la entrega,
esta tabla de equivalencias (sale del análisis de sus seis rendiciones):

| Su hoja "General" | En el sistema | Ojo |
|---|---|---|
| Gastado por rubro | Gastado (N4) | En su planilla EPP figura en la tabla dinámica pero suma en INSUMOS en el General: igual que acá. Renglones con "INSUMO" mal escrito en su Excel no suman en INSUMOS; acá no pasa. |
| Presupuestado por rubro | Presupuestado (N1) | Acá es lo cargado en W-13; en su planilla sale del costeo con un factor por rubro. Si cargan los mismos números, da igual. |
| "Ganancia" por rubro | Saldo (N6) | Mismo cálculo (presupuestado − gastado). |
| "% rent por rubro" | **no es el mismo número** | Su planilla hace ganancia ÷ gastado (y en el total ganancia ÷ presupuestado); W-11 muestra % consumido = gastado ÷ presupuestado (aprobado en W-11). |
| Fila Total (en dos de sus planillas, contra el TOTAL OBRA de venta) | Total de la obra = costo presupuestado | La venta está en "Monto de la obra" (N13). |
| Cobrado · Ganancia · % | Monto de la obra · Ganancia al día · % (resuelta DU-R01) | "Cobrado" en su planilla es la suma de los montos asignados, no la cobranza. |
| Renglón único "Materiales Tecnobahía" | Un renglón por retiro | El total de MATERIALES da igual si se cargan todos los retiros. |
| FIJOS con costo financiero por pago diferido (en una de sus planillas) | Solo lo que se cargue como período | El costo financiero no entra (usa fechas de cobro, D-034). |
| Importes que ustedes tipean con IVA | Netos sin IVA (C9) | Si su planilla tiene algún importe con IVA, va a diferir. |
| Totales de renglón sin redondear | Total de cada renglón redondeado a 2 decimales (ADR-008) | Puede haber diferencias de centavos por rubro si su planilla no redondea renglón por renglón. |
| Total de un mes en Gastos cargados (W-33) | W-33 filtra por la **fecha del comprobante**; la rendición (W-11, W-36, W-37) suma por la **fecha del renglón** (DU-G01) | En los gastos sin comprobante con tickets de días distintos (W-35) y en los períodos, el total de un mes en W-33 y el de W-36/W-37 pueden diferir. El total de la obra da igual. |
| Fijos o horas del mes en curso | Un período ("Fijos 1 al 30-nov") queda fechado en `periodo_hasta`, aunque sea futura (RN-GAS-27) | Cargado el 24-nov, queda fechado el 30-nov: la rendición ya lo resta, pero no aparece en el W-33 por defecto (del 1 del mes a hoy) hasta que se amplíe la fecha. |
| Varias filas de amortización | Una sola amortización por obra (RN-GAS-23) | Si en su planilla hay varias filas de amortización, se cargan sumadas en una. |
| Tabla dinámica por proveedor | W-37 agrupa por **proveedor**, no por tipo de documento | Un gasto sin comprobante o un combustible con un proveedor real va bajo ese proveedor, no en "Sin documento"; "Sin documento" junta solo lo cargado con el proveedor genérico (RN-REN-35). |

## 14. Trazabilidad F-XX → RN → test

| F-XX | Reglas | Tests (PHPUnit, `tests/Feature/Rendicion/…`, `tests/Unit/Rendicion/…`) |
|---|---|---|
| F-49 | RN-REN-01…08, 19, 20, 26…29 | `test_caso_de_referencia_da_cada_rubro_al_centavo` (§9, una aserción por fila) · `test_renglon_sin_obra_no_suma` · `test_renglon_de_otra_obra_no_suma` · `test_gasto_anulado_no_suma_ni_aparece` · `test_correccion_mueve_el_gastado_entre_obras` · `test_devolucion_resta` · `test_devolucion_sin_material_resta_en_el_rubro_y_no_toca_w48` · `test_no_filtra_por_origen` · `test_obra_terminada_tiene_rendicion` · `test_obra_sin_presupuesto_muestra_lo_gastado` · `test_muestra_los_doce_rubros_en_orden` · `test_ultimos_gastos_son_diez_por_fecha` · `test_renglon_de_carga_inicial_suma` · Unit `CalculoDeRendicionTest`: `test_saldo_es_presupuestado_menos_gastado`, `test_porcentaje_redondea_mitad_para_arriba_a_dos_decimales`, `test_porcentaje_nulo_sin_presupuesto`, `test_sumas_exactas_al_centavo_sin_float` |
| F-48 · F-54 (presupuesto) | RN-REN-09…13 | `test_presupuestado_suma_todos_los_presupuestos_por_rubro` · `test_solo_total_compara_total_contra_gastado` · `test_obra_mixta_muestra_sin_desglose` · `test_presupuesto_sin_cargar_no_suma_y_avisa` · `test_cambio_en_w13_invalida_el_cache` · `test_ver_presupuestado_no_pide_presupuesto_rubro` |
| F-54 (EPP) | RN-REN-14, 15 | `test_epp_suma_en_insumos_en_el_resumen` · `test_detalle_de_insumos_marca_epp_y_subtotal` · `test_detalle_de_epp_solo_devuelve_404` |
| F-54 (horas y fijos) | RN-REN-16…18 | `test_horas_y_fijos_por_periodo_restan_en_su_rubro` · `test_fecha_efectiva_de_un_periodo_es_su_ultimo_dia` (con Gastos, también si es futura) · `test_periodo_superpuesto_avisa_y_no_frena` (Gastos) |
| F-54 (parte de horas, DU-C02) | RN-REN-62, 63 | `PrivacidadDelParteTest` (Rendición): `test_rendicion_muestra_el_parte_por_persona_con_gastos_editar` · `test_rendicion_muestra_el_parte_resumido_sin_gastos_editar` · `test_el_parte_suma_en_horas_liquidadas` |
| F-50 | RN-REN-21, 22 | `test_rubro_pasado_es_estricto_al_centavo` · `test_rubro_con_gasto_y_sin_presupuesto_es_pasado` · `test_solo_total_no_marca_rubros` · `test_obra_mixta_marca_contra_lo_desglosado` · `test_obra_pasada_se_marca` |
| F-54 (monto) | RN-REN-23…25 | `test_monto_y_ganancias_con_monto_obra_ver` · `test_monto_no_viaja_sin_permiso` · `test_monto_respeta_alcance_propias` · `test_presupuesto_sin_monto_avisa` · `test_ganancia_negativa` |
| F-51 | RN-REN-30…32 | `test_detalle_lista_todos_los_renglones_del_rubro` · `test_pie_del_detalle_igual_al_gastado` · `test_detalle_ordena_por_fecha_proveedor_y_total` · `test_renglon_es_vinculo_solo_con_gastos_ver` |
| F-52 | RN-REN-33…37 | `test_por_proveedor_agrupa_por_proveedor_y_concepto` · `test_cuenta_comprobantes_con_numero` · `test_sin_documento_va_al_final` · `test_filtro_por_rubro_y_periodo` · `test_total_sin_filtros_es_el_gastado` |
| F-53 | RN-REN-38…41 | `test_rendiciones_en_curso_solo_en_ejecucion` · `test_ordena_por_porcentaje_y_sin_presupuesto_al_final` · `test_total_suma_las_filas_visibles` · `test_alcance_propias_ve_solo_sus_obras` · `test_rendiciones_en_curso_no_hace_una_consulta_por_obra` |
| F-15 · F-17 | RN-REN-42, 43 | `test_avance_gasto_devuelve_porcentajes_en_una_consulta` · `test_avance_gasto_nulo_sin_presupuesto` · `test_pasadas_marca_mas_de_cien` · (Obras, ahora con datos) `test_porcentaje_gastado_se_oculta_sin_permiso_de_rendicion` · `test_en_ejecucion_ordena_por_porcentaje_gastado` |
| F-13 | RN-REN-44…46, 54 | E2E (Playwright, 390 px): `celular_rendicion_en_tarjetas_mas_consumidos_arriba` · `celular_mismos_numeros_que_la_pc` |
| F-66 | RN-REN-47…53 | `test_lista_de_materiales_la_carga_control_cantidad_editar` · `test_nombre_de_material_no_se_repite_en_la_obra` · `test_retirado_es_la_suma_de_los_renglones_del_material` · `test_devolucion_asociada_resta_del_retirado` · `test_resta_llevar_puede_ser_negativa` · `test_columnas_de_fecha_seis_y_hasta` · `test_total_de_la_lista_y_diferencia_con_materiales` · `test_fuera_de_presupuesto_por_renglon` · `test_no_se_quita_un_material_con_renglones_aunque_esten_anulados` · `test_completar_cantidad_pasa_a_la_lista` · `test_adicional_con_materiales_no_se_quita` · `test_cambio_de_material_queda_en_actividades` · Contrato: `test_crear_fuera_de_presupuesto_reusa_el_mismo_nombre` · `test_crear_fuera_de_presupuesto_exige_unidad` · `test_pertenece_a_obra` |
| Permisos | RN-REN-55…58 | `test_rendicion_fuera_de_alcance_devuelve_404` · `test_sin_rendicion_devuelve_403` · `test_control_cantidad_ver_no_edita` · `test_opciones_de_materiales_con_gastos_editar` · `test_opciones_de_materiales_traen_el_precio_presupuestado` · (Gastos) `test_amortizacion_y_devolucion_piden_rendicion_editar` · `test_retiro_con_control_cantidad_editar_sin_rendicion_editar_se_acepta` · uno de permitido / denegado / IDOR por endpoint |
| En vivo | RN-REN-59…61 | `test_gasto_imputado_invalida_la_rendicion_de_cada_obra` · `test_gasto_corregido_invalida_obras_de_antes_y_despues` · `test_monto_no_se_cachea` · `test_material_doble_envio_crea_uno_solo` · `test_material_con_version_vieja_devuelve_409` · E2E `cargar_factura_y_verla_en_la_rendicion` |

## 15. Dudas

Resueltas que se aplican acá (`docs/dudas.md`): DU-08 (obra mixta), DU-09 (corregir lo presupuestado), DU-17 (una sola carga de
retiros), DU-18 (período desde–hasta), DU-19 (monto del presupuesto en W-13), DU-21 (carga inicial con equivalencias), DU-A03,
DU-A04, DU-A05 (celdas de Rendición, Control por cantidad e Ingeniería "solo sus obras"), DU-A15 (obra Terminada).

**De esta especificación, resueltas el 24-nov-2026 (Juani: va la propuesta; `docs/dudas.md` sección "Analista funcional ·
especificaciones de la semana 3 · Rendición"):** DU-R01 (ganancia al día además de la prevista) · DU-R02 (rubros pasados en obra
mixta) · ~~DU-R03~~ **reemplazada por DU-G05** (no hay nota de crédito: RN-REN-04, §9.2 renglón 8) · DU-R04 (horas y fijos: dónde se
cargan, qué fecha usan, superposición) · DU-R05 (qué permiso habilita las cargas "desde la rendición") · DU-R06 (W-48: precio, total,
columnas de fecha, diferencia con MATERIALES, unidad) · DU-R07 (lo que W-11/W-36/W-37 no dicen: etiquetas, últimos 10, grupo "Sin
documento", período, marca EPP) · DU-R08 (técnica: contratos nuevos con Obras y Gastos) · DU-R09 (retirado antes del arranque en la
carga inicial, semana 4).

**Pedidos del cliente (Bruno, 26-nov):** DU-C02 (parte de horas: RN-REN-62, 63; `gastos.md` §5.13).

**De `gastos.md` que se aplican acá:** DU-G01 (fecha del renglón = fecha efectiva), DU-G03 (fuera de presupuesto en la misma
carga, con su unidad), DU-G05 (sin nota de crédito), DU-G09 (superposición de períodos).

## 16. Desvíos y ajustes al modelo de datos

| Dónde | Modelo (`modelo-datos.md`) | Lo que pide esta especificación | Motivo |
|---|---|---|---|
| `materiales` | `obra_id, presupuesto_id, nombre, unidad, cantidad_presupuestada, precio_presupuestado, fuera_de_presupuesto` | + `nombre_clave` (normalizado) con único `(obra_id, nombre_clave)`; + `created_by`, `updated_by`, `timestamps`; CHECK `fuera_de_presupuesto = (cantidad_presupuestada IS NULL)`; índice `obra_id` | nombre no repetido en la obra (RN-REN-47), F-14, versión para 409 |
| `gasto_renglones` (Gastos) | `cantidad` negativa solo si `origen = devolucion` | **sin cambios** (resuelta DU-G05: no hay nota de crédito) | §9 renglón 8 es una devolución |
| `gasto_renglones` (Gastos) | sin fecha propia (la del gasto) | + `fecha` (date, no nula) = **fecha efectiva**: en facturas la de la factura; en W-35 la del ticket; en períodos `periodo_hasta`; índice `(obra_id, fecha)` | W-35 tiene fecha por renglón; resuelta DU-G01 y DU-R04 |
| `presupuesto_montos` / contrato | `PresupuestadoDeObra {porRubro, sinDesglose, total}` | + presupuestos con `modoCarga`; versión por lote; `montoDeObra` | §12.4.2, DU-R08 |
| Puertos de Obras | `PedidosDeObra`, `AvanceGastoDeObra` | + `MaterialesDePresupuesto` | RN-OBR-24 "ni, desde la semana 3, materiales del control por cantidad" |
| Cache (ADR-007) | invalidan `GastoImputado`, `GastoCorregido`, `GastoAnulado` | + `PresupuestoMontosCambiados` (ya lo dice obras.md §10.4); eventos con **todas** las obras afectadas | RN-REN-60 |
| Consultas §3 | presupuestado `GROUP BY rubro_id`; gastado `WHERE obra_id = ? AND anulado_at IS NULL GROUP BY rubro_id` | **sin cambios**; W-12 y W-05 en su versión agrupada por `obra_id` | — |

Aplicados en `modelo-datos.md` el 24-nov-2026.

## 17. Alineación con `gastos.md` (24-nov-2026)

Lo que se cambió en las dos especificaciones para que digan lo mismo (a favor del contrato firmado, la tabla de permisos
aprobada y §12.4.1 de esta especificación). La misma tabla, vista desde Gastos, está en `gastos.md` §15.

| Tema | Antes (en esta especificación) | Ahora (igual en las dos) |
|---|---|---|
| Nota de crédito | renglón negativo propio, etiqueta "Nota de crédito N", comprobante en W-37 (DU-R03) | no hay (DU-G05). El renglón 8 del caso de referencia pasa a ser una **devolución** al proveedor por el mismo importe (−5 × 9.850,50 = −49.252,50): W-11, W-36, W-48 y W-12 dan igual; en W-37 Corralón Ejemplo cuenta 1 comprobante y el pie, 4 |
| Fecha efectiva | "la del renglón si Gastos la guarda; si no, la del comprobante" | siempre `gasto_renglones.fecha` (DU-G01); en períodos, `periodo_hasta` también si es futura (en Gastos decía "u hoy") |
| Horas y fijos | reglas y textos propios ("El fin del período no puede ser anterior al inicio.") | la carga y sus textos son los de `gastos.md` RN-GAS-27/28; acá solo lo que hace la rendición |
| Caso borde 21 | "factura fechada mañana" suma | Gastos no acepta facturas futuras (RN-GAS-04): el caso es el de los fijos del mes en curso |
| Fuera de presupuesto | lo crea el retiro con `crearFueraDePresupuesto` | igual, y Gastos lo dice igual (antes guardaba el retiro sin material y sin unidad); también desde un renglón de factura |
| `MaterialesDeObra::opciones` | sin precio | + `precioPresupuestado` (Gastos lo sugiere en el retiro); un solo endpoint de materiales, el de Rendición |
| `CatalogoDeRubros::imputables` | `{id, clave, nombre}` | + `cargaPorPeriodo` |
| Permisos de W-46 | `rendicion` = editar; retiros también `control_cantidad` | igual, y ahora también en Gastos (DU-G08 reemplazada); corregir y anular, `gastos` = editar; remito según `identidad.md` §6.6 |
| Proveedor "sin proveedor" | grupo "Sin documento" con genérico **o sin proveedor** | todo gasto tiene proveedor (el genérico si no se eligió otro) |
| Etiquetas de documento | solo acá | las mismas en W-33/W-34 (`gastos.md` RN-GAS-29) |

## 18. Textos de la implementación (resuelta DU-G15, 25-nov-2026)

Textos que la implementación usa en situaciones donde esta especificación no tenía uno. Quedan como están y forman
parte de la especificación (informe `docs/qc/semana-3-tanda-1.md` §4, filas "queda"). Donde la especificación sí tenía
un texto, la implementación usa el de la especificación. Los de la carga de W-46 están en `gastos.md` §16.

**Mensajes de la API:**

| Situación | Texto |
|---|---|
| Material inexistente, de otra obra o fuera de alcance (404) | "No encontramos este material." |
| Material sin nombre o sin unidad | "Escribí el material." / "Escribí la unidad." |
| Material con nombre demasiado largo | "Es demasiado largo (máximo 150 caracteres)." |
| Material asociado a un presupuesto de otra obra | "Ese presupuesto no es de esta obra." |
| Cantidad o precio del material | "Escribí la cantidad como número, por ejemplo 12,5." / "Escribí el precio como número, por ejemplo 12,5." · "… lleva como máximo N decimales." · "… no puede ser negativa/o." · "… es demasiado grande." |
| Material agregado, corregido o quitado (W-48; el front lo muestra tal cual) | "Material agregado a la lista." · "Material corregido." · "Material quitado de la lista." |
| Quitar un presupuesto que tiene materiales (RN-OBR-24) | "Este presupuesto ya tiene materiales del control por cantidad: no se puede quitar." |
| Etiqueta de un período que cruza meses (RN-REN-29) | "Período 25-oct al 10-nov" |

**Pantallas:**

| Dónde | Texto |
|---|---|
| W-11 sin `rendicion`, por URL | "No tenés acceso a la rendición." + "Si lo necesitás, pedíselo a gerencia." |
| W-11, obra sin monto (con `monto_obra = editar`) | "Cargar el monto de la obra" (acceso a W-13) |
| W-48, acciones | "corregir" · "quitar" · "pasar a la lista" · título "Corregir · X" · "Guardar" · "Guardando…" |
| W-48, ayudas del material | "Por ejemplo Cemento x 50 kg." · "bolsa, m³, paño, barra, kg…" · "Escribí la unidad, por ejemplo bolsa o m³." |
| W-48, quitar | título "Quitar · X" · "Quitar material" · "Quitando…" · "¿Quitar X de la lista? Es para un material cargado por error: …" |
| W-48, validaciones del formulario | "La cantidad no puede ser negativa." · "Escribí la cantidad con números, por ejemplo 12,5." · "La cantidad puede tener hasta 3 decimales." · "La cantidad es demasiado grande." · "… puede tener hasta N caracteres." |
| Ficha y listado de obras, rubro pasado | " · se pasó del presupuesto" (cabecera de la ficha y lector de pantalla del listado) |
| W-37, queda un solo proveedor sin mostrar | "ver el restante" (con más: "ver los N restantes") |

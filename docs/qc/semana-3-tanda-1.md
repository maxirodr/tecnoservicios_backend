# QC S3: Gastos y Rendición en `main` (2026-11-25)

**Qué se revisó:**
- back `main` @ `598f8b4`: módulos Gastos y Rendición y los cambios de Obras en `882b2af..main`;
- front `main` @ `76acdde`: `0cbdc4d..main`;
- contra `gastos.md`, `rendicion.md`, `backlog.md` §7 y §3, la matriz de `identidad.md` y DU-G/DU-R.

**Cómo se revisó:** solo lectura. No se corrieron tests, Pint, phpstan ni el build.

**Rutas:**
- `B/` = `C:\NexoSmart\Projects\tecnoservicios_project\tecnoservicios_backend\`;
- `F/` = `C:\NexoSmart\Projects\tecnoservicios_project\tecnoservicios_frontend\src\`;
- `BG/` = `B/app/Modules/Gastos/`, `BR/` = `B/app/Modules/Rendicion/`;
- `FG/` = `F/features/gastos/`, `FR/` = `F/features/rendicion/`.

**Severidad:** como QA. "(verificado)" significa que lo leí yo en el código. "a verificar" significa que no se pudo cerrar sin correr el sistema.

**Conteo:**

| Severidad | Cantidad |
|---|---|
| Bloqueante | 3 |
| Alta | 3 |
| Media | 17 |
| Baja | 28 |

Textos fuera de la spec: 76 filas en el §4.

**Nota:** los 3 bloqueantes son de contrato front ↔ back. Es justo lo que tiene que cruzar Integración S3 (punto 2 del brief). Si esa rama ya los arregla, hay que confirmarlo cuando se mergee.

---

## 1. Criterios no cumplidos o mal cumplidos

### Bloqueantes

**BLQ-1 · HU-071 CA-7 / RN-GAS-35 · "ver todos" desde la rendición muestra solo el mes en curso (verificado).**
- **Qué hace hoy el front:**
  - `F/shared/rutas.ts:40` arma `/gastos?obra=X&fecha=todas`.
  - `FG/components/lista/filtrosUrl.ts:31-33` convierte `fecha=todas` en `desde=''` y `hasta=''`.
  - `FG/api/gastos.api.ts:59-60` **no manda** las claves vacías: `if (f.desde !== '') params.set('desde', …)`.
- **Qué hace el back:** `BG/Http/Requests/ListarGastosRequest.php:47` hace `$porDefecto = ! $this->exists('desde') && ! $this->exists('hasta')`. Sin las claves, aplica del 1 del mes a hoy. Su docblock (l.15-16) dice que "sin límite" son `desde=` y `hasta=` **enviados vacíos**.
- **Caso:**
  - Desde W-11 se toca "ver todos" en una obra con facturas de octubre.
  - W-33 muestra solo noviembre, y el "Total de lo filtrado" no coincide con el gastado de W-11.
  - Pasa lo mismo cuando el usuario borra las dos fechas a mano.

**BLQ-2 · HU-071 CA-4 / RN-GAS-33 · Agrupar por obra o por proveedor rompe W-33 (verificado).**
- `BG/Application/Lecturas/AgruparGastos.php:93-101`: `claveVisible()` devuelve `(int)` para obra y proveedor.
- `FG/api/gastos.schemas.ts:121` declara `clave: z.string().nullable()`.
- **Caso:** con `agrupar=obra` o `agrupar=proveedor`, el parse de zod falla y la pantalla muestra "No pudimos traer los gastos. Probá de nuevo." Solo anda por rubro.
- El fixture del test del front usa `clave: '12'` (string), por eso los tests no lo detectan.
- Son las "tablas dinámicas por proveedor" de la prueba del cliente (§7.1).

**BLQ-3 · HU-076 CA-3 / RN-REN-24 · Obra sin monto: la rendición no abre para quien ve el monto (verificado).**
- `BR/Http/Resources/RendicionResource.php:37-38` manda `'monto' => null` cuando `montoDeObra` es null (spec §4 l.88 y §12.4.2 l.697: "`null` si ningún presupuesto tiene monto").
- `FR/api/rendicion.schemas.ts:99` declara `monto: montoDeObraSchema.optional()`, que acepta la clave ausente pero **no `null`**.
- **Caso:**
  - Dirección, Gerencia de Operaciones o Administración abren W-11 de una obra cargada en la semana 1 sin monto.
  - Ven "No pudimos cargar la rendición. Probá de nuevo." en lugar de la tabla.
  - El texto "Todavía no se cargó el monto de la obra." (`FR/components/MontoYGanancia.tsx:40-41`) es código muerto.
- Ningún fixture usa `monto: null`.

### Altas

**ALT-1 · HU-071 CA-2 / RN-GAS-30 · Los filtros de W-33 usan endpoints de carga: 403 para quien solo ve (verificado).**
- `FG/components/lista/FiltrosGastos.tsx:30-36,52-53` usa `gastos/obras-opciones`, `gastos/opciones` y `proveedores/opciones`.
- En el back:
  - `BG/routes.php:24-25` protege esos endpoints con `PuedeCargarGastos` (spec §8.2 l.330: "quien puede cargar").
  - `proveedores/opciones` exige `proveedores ≥ ver` o `gastos = editar`.
- **Caso:** Laura (Ingeniería, `gastos` ver), Dirección y Jefatura de Proyectos.
  - El filtro Obra lista solo las obras de las filas a la vista.
  - El filtro Proveedor sale vacío o incompleto.
- La spec no define un endpoint de opciones para filtrar: es un hueco de la spec que el front tapó con el de carga. Hay que decidir: el back abre las opciones a `gastos ≥ ver` con alcance, o se agrega una lectura nueva.

**ALT-2 · RN-GAS-30 "cada obra · cada proveedor" · Los filtros traen como máximo 20, sin búsqueda.**
- **Dónde:**
  - el front pide las opciones con texto vacío (`FiltrosGastos.tsx:31,52`);
  - el back corta en 20: `MAX_OBRAS = 20` (`BG/.../OpcionesDeCargaQuery.php`) y `MAXIMO = 20` (`OpcionesProveedoresQuery.php`).
- **Caso:** el proveedor número 21 no se puede elegir en el filtro, y no se puede reproducir su tabla dinámica por proveedor.

**ALT-3 · RN-GAS-30 y §7 (W-33 vacío) · Con la vista por defecto, cargar facturas de octubre deja W-33 con un mensaje engañoso (a verificar en pantalla).**
- `FG/components/lista/filtrosUrl.ts:81-83`: `hayFiltros()` es false con los filtros por defecto (mes en curso), así que se muestra "Todavía no hay gastos cargados." (`EstadoListaGastos.tsx:27`).
- La spec (§7 l.287) reserva ese texto para "Sin ningún gasto". Con el mes en curso filtrado corresponde "No hay gastos con estos filtros." + "Limpiar filtros".
- **Caso:** en la prueba cargan las facturas de octubre de su libro, entran a W-33 y creen que no se guardó nada.

### Medias

| ID | Criterio | Archivo:línea | Caso |
|---|---|---|---|
| M-1 | §8.2 l.330 (aviso de factura repetida: "alcance sí") | `BG/Application/Carga/CargaRepetida.php:46-53` (verificado: sin filtro de alcance) | Quien carga solo en sus obras consulta `factura-existente` y obtiene id, número, fecha y quién cargó facturas de obras que no ve. |
| M-2 | DoD §3 y brief ("cada endpoint: permitido, denegado e IDOR") | `B/tests/Feature/Gastos/*` | Faltan estos tests: IDOR de `factura-existente`; 404 de PATCH sobre un gasto entero fuera de alcance; anular denegado (sin `gastos=editar`) e IDOR; historial denegado; renglón `id` de otro gasto en PATCH; el contexto de adjuntos `remito` sin ningún test (descarga con `control_cantidad ≥ ver`, tope de 1, quitar). |
| M-3 | §4.4, fila remito ("asociar/quitar … o `control_cantidad = editar`") (a verificar) | `BG/routes.php:30` (PATCH con `gastos,editar`) | Quien solo tiene `control_cantidad = editar` no puede asociar un remito a un retiro ya cargado. |
| M-4 | RN-GAS-14 / §4.3 (material solo en los orígenes que lo llevan) (a verificar) | `FG/.../RenglonFila.tsx:60-61` contra `BG/.../MaterialDelRenglon.php:33` | En W-35 "Gastos de obra rendidos" el front ofrece Material (también "No está en la lista"). El back lo descarta sin avisar, y W-48 difiere de lo que escribió el usuario. |
| M-5 | RN-GAS-13 (cada renglón elige su obra) | `FG/.../Renglones.tsx:66-83` | El select de obra del renglón solo tiene las 20 de un campo aparte "Buscar obra". Una obra fuera de esas 20 obliga a buscarla en otro campo. |
| M-6 | RN-GAS-08 y regla "mensajes del back tal cual" | `FG/components/carga/FacturaRepetida.tsx:25` | El front arma el aviso con la fecha de la factura en dd/mm/aaaa: "(Diego, 10/10/2026)". El back manda `mensaje` con la fecha de carga: "(Diego, 17-oct)". El aviso al salir del campo y el 422 al guardar dicen fechas distintas. |
| M-7 | DU-R05 (retiro con `control_cantidad = editar`) (a verificar) | `FG/components/sin-documento/SeccionRetiros.tsx`, que usa `proveedores/opciones` | Sin `proveedores ≥ ver`, la lista de proveedores da 403 y el retiro no se puede guardar. |
| M-8 | HU-071 CA-1 / W-33 (orden Fecha · Documento) | `FG/.../TablaGastos.tsx:39-40` | El orden de columnas es Documento · Fecha. |
| M-9 | §7 "Sin permiso" de W-33 y W-46 | `FG/rutas.tsx:41`; `FG/pantallas/RenglonesSinDocumentoPantalla.tsx:56` | Por URL sin permiso se ve el genérico "No tenés acceso a esta sección…" y no "No tenés acceso a Gastos. Si lo necesitás, pedíselo a gerencia." / "No podés cargar en esta rendición." |
| M-10 | HU-074 CA-6 / RN-REN-31 ("= el gastado de W-11, siempre") | `FR/hooks/useRendicion.ts:43-61` | W-36 y W-37 no tienen polling. Con otra PC cargando, el pie "Total MATERIALES" de W-36 queda viejo mientras W-11 se actualiza. |
| M-11 | HU-082 CA-3 / regla "mensajes del back tal cual" | `FR/components/materiales/MaterialDialog.tsx:79-80`; `QuitarMaterialDialog.tsx:32` | El front muestra mensajes de éxito propios ("Agregamos X a la lista.") en lugar de los del back ("Material agregado a la lista.", `BR/Http/Controllers/MaterialController.php:57,65,72`). |
| M-12 | HU-083 CA-1 (columnas "02-oct", "Hasta 25-sep") y HU-078 CA-3 (pie "del 1-nov al 30-nov") | `FR/components/materiales/TablaMateriales.tsx:46,51`; `FR/components/PorProveedor.tsx:57` | Salen "02/10/2026", "Hasta 25/09/2026" y "del 01/11/2026 al 30/11/2026". `fechas.ts` no tiene formato corto: la spec y la regla "usar fechas.ts" chocan, y hay que decidir. |
| M-13 | RN-REN-07 (el link a W-13 aparece con `presupuesto_rubro = editar`) | `FR/components/AvisosPresupuesto.tsx:22-25` | Con `ver_presupuesto_rubro` (≥ ver) se muestra "Cargar el presupuesto por rubro". Un usuario que solo ve llega a W-13 y no puede cargar. |
| M-14 | §12.2 (contrato W-48) (a verificar) | `FR/api/rendicion.schemas.ts:181` contra `BR/Http/Resources/MaterialResource.php:26` | `presupuesto.numero` puede venir null (`numeroDe(): ?string`). El front lo exige string, así que W-48 entera fallaría el parse. |
| M-15 | RN-REN-59 (la rendición se actualiza en ≤ 15 s) (a verificar, race) | `BR/Application/Rendicion/GastadoEnCache.php:34-38` | Usa `remember` de 600 s y el listener solo hace `forget`. Una lectura de W-11 que empezó antes del commit de un gasto puede reescribir los totales viejos después del `forget`. La obra queda vieja hasta 10 minutos, mientras W-12 y W-05 leen directo de la base y muestran otro número. La ventana es chica (dura lo que la consulta). |
| M-16 | Backlog §7.1 y rendicion.md §9 ("cargados por la API de Gastos y de Obras") | `B/tests/Feature/Rendicion/RendicionCasoDeReferenciaTest.php` (fake de `GastadoDeObras`, presupuestos insertados en la base y el evento de anular disparado a mano, l.203) | El brief de la tanda 1 permitía el fake. El criterio global queda sin probar de punta a punta: redondeo real de 1.265.432,125, proveedor genérico real en W-37 y la cadena anular → evento → cache. Es el punto 1 de Integración S3. |
| M-17 | HU-087 ("con `gastos` y `rendicion`") | `F/shared/modulos.ts` (clave `rendiciones`); `parseModulosEntregados` descarta las claves desconocidas sin avisar | Si en el deploy se escribe `rendicion` como dice la HU, el módulo no se enciende y no hay error. El brief de la tanda 2 (l.253) dice `rendiciones`. Hay que unificar el backlog. |

### Bajas

- **Gastos, back:**
  - `BG/.../VersionDeFila.php:29,56` acepta `updated_at` como `sometimes nullable`: sin él gana la última escritura, sin 409 (HU-072 CA-5). El front sí lo manda.
  - `B/tests/Feature/Gastos/FacturaRepetidaTest.php:48-59` prueba el índice con un `DB::insert` y no el 422 de la acción (HU-065 CA-4).
  - `CargasDesdeLaRendicionTest.php:49` no es un test de concurrencia: solo verifica que hubo un `for update`.
  - `RenglonesSinDocumentoQuery.php:38-40` devuelve 404 antes de chequear el permiso: quien no tiene acceso distingue si la obra existe.
  - `RenglonesSinDocumentoQuery.php:84-90`: `ultimo_proveedor_retiro` es el último retiro global, sin alcance.
  - `GastadoDeObrasEloquent.php:38-48`: una obra sin renglones no tiene clave; §12.4.1 dice `[]`. Hoy no tiene impacto (`?? []`).
  - Gastos lee modelos de otros módulos (`Rendicion\Domain\Models\Material` en `MaterialDelRenglon.php:9`, `Obras\Domain\Models\Obra` en `CargaRepetida.php:14`, tablas `obras` y `presupuestos` en `GastadoPorProveedorEloquent.php:31-32`), contra §12.4 ("por Contracts/").
  - Una obra o un proveedor inexistente da 422 y §12.1 dice 404; la spec se contradice con su propio test de §13.
  - `updated_at` sale en UTC (`GastoDetalleResource.php:62`) y §12 pide hora de Argentina.
  - `REGEXP_REPLACE` con lookahead en `GastosEnBuscador.php`: a verificar que lo soporte el MySQL de producción.
- **Rendición, back:**
  - `DetalleDeRubroRequest` y `PorProveedorRequest` validan antes del acceso: quien no tiene acceso, con `?orden=x`, recibe 422 en vez de 403/404.
  - `DocumentoDelRenglon.php:24` duplica `EtiquetaDocumento` de Gastos y ya difiere ("Retiro" sin número). RN-REN-29 pide las mismas etiquetas.
  - `FichaObraQuery.php:37` y `ListarObrasQuery.php:105`: con N3 = 0 y gastado > 0, `pasada` queda null y W-05 no pinta rojo (a verificar contra N11).
  - `Situacion.php:31-40`: un presupuesto por rubro con todos los rubros vacíos cuenta como "por rubro" y marca todo como pasado (a verificar contra N10, "cargado").
  - `RendicionesEnCursoQuery.php:57-64`: el total de W-12 suma el gastado de obras sin presupuesto (sigue RN-REN-40 al pie de la letra e infla el %; que lo confirme el analista).
  - El listado de obras recalcula dos veces: 4 consultas en vez de 2 (RN-REN-42).
  - `QuitarMaterialAction.php:35-46` cuenta fuera de la transacción: con un retiro simultáneo da 500 en vez de 422.
  - `nombre_clave` de 150 caracteres con `Str::ascii` puede alargarse ("ß" → "ss") y dar 500.
  - El back manda `sin_presupuesto` en `DetalleDeRubro`, que no está en §12.1.
- **Front:**
  - HU-074 CA-5: en el celular no aparece "Todavía no hay gastos cargados en esta obra." (`FR/components/RendicionDeLaObra.tsx:74`).
  - HU-079: sin `ver_rendicion`, por URL `/obras/:id/rendicion` muestra la primera pestaña en vez de "No tenés acceso a la rendición.".
  - "Pasar a la lista" hace un PATCH sin versión, así que no hay 409 (RN-REN-61; la spec tampoco manda `updated_at` en "fuera").
  - HU-069 / RN-GAS-25 y RN-GAS-24: no se sugiere "el último precio de ese material en la obra" (`FG/.../renglonMaterial.ts:39-49`).
  - HU-085: el genérico sale como "· Gastos sin comprobante" y no como "—" (`FG/buscador/ResultadosGastos.tsx:20`, back `GastosEnBuscador.php:62` sin `es_generico`).
  - El 422 de la segunda amortización no se muestra con su link "Corregir" (`SeccionAmortizacion.tsx:92,105,118`).
  - `EditarGastoPantalla.tsx` no mira `puede.editar` antes de mostrar el formulario.
  - Los negativos salen con guion "-" y no con "−" (`F/shared/dominio/importes.ts:18` con `Intl`), en el front y en los textos del back (`Legible.php:36`).
  - W-37 en el celular es una tabla de 4 columnas: a verificar que no haya scroll horizontal a 375 px.

### Lo que cumple

- **Gastos, back:** HU-061…HU-070 y HU-072…HU-073 cumplen. HU-084 y HU-085 cumplen en lo de Gastos, salvo lo listado arriba.
- **Rendición, back:** HU-074…HU-083 cumplen. HU-086 cumple: migración con CHECK y `down`, contratos implementados con `bind`.
- **Front:** cumple el resto de los criterios de W-10, W-35, W-38, W-46, W-34, W-11, W-44, W-12 y W-48, según el detalle por HU de las revisiones.

## 2. Reglas de dinero

| Regla | Estado | Evidencia |
|---|---|---|
| Redondeo `round(cantidad × precio, 2)`, mitad para arriba en valor absoluto, en el back | ✅ | `BG/.../TotalDeRenglon.php:28-37`: bcmath con escala 5, ±0,005 alejándose de cero y corte a 2 decimales. El `total` del front no se lee (`GastosRequest.php:113-125`). Sin `round()`, `(float)` ni `number_format` en ninguno de los dos módulos (grep). |
| Total de cabecera = suma sin segundo redondeo | ✅ | `TotalDeRenglon::suma`. |
| Importes como string en JSON y en el front | ✅ | Casts `decimal:2/3`. Recursos, `meta.total_filtrado`, subtotales y % (string de 2 decimales, `Porcentaje.php`). Front: sin `parseFloat` ni aritmética. El total provisional usa BigInt con la misma regla (`FG/dominio/decimales.ts:28-49`). `Number()` se usa solo para formatear y ordenar. |
| Negativos solo en devoluciones | ✅ (solo en la aplicación) | `BG/.../ReglasDeRenglones.php:122-127` y precio negativo rechazado (`:138`); front `validacion.ts:49-51`. Baja: la base no tiene CHECK. |
| EPP → INSUMOS | ✅ | Gastos no lo mapea: EPP es su propio grupo en W-33 (RN-GAS-33). Rendición lo suma en INSUMOS con `subtotal_epp` (`DetalleDeRubroQuery.php`, `CalculoDeRendicion.php`). |
| Presupuesto solo total y mixto | ✅ | `sin_desglose`. La comparación por rubro es solo contra la parte desglosada (`CalculoDeRendicion.php:28-44`, `Situacion`). Ver las bajas de N10/N11. |
| Períodos (horas y fijos) | ✅ | Un renglón con cantidad 1, rubro FIJOS u HORAS LIQUIDADAS, fecha del renglón = `periodo_hasta`, superposición inclusiva por obra y rubro que avisa y guarda si se confirma (`CargaRepetida.php:120-143`). |
| Anulados | ✅ | Fuera de `totalesPorRubro`, `renglonesDeObra`, W-33, grupos, proveedor y W-46. `contarRenglonesPorMaterial` los cuenta, como pide el contrato. |
| Corrección: el evento lleva obras antes ∪ después | ✅ | `CorregirGastoAction.php:64,77`, test `CorregirYAnularTest.php:123`. Todos los eventos son `ShouldDispatchAfterCommit`. |
| % gastado / ganancia (DU-R01) | ✅ | Porcentajes con bcmath, mitad para arriba en valor absoluto; división por 0 → null; ganancia al día = monto − gastado. El front redondea a entero con BigInt (`F/shared/dominio/porcentajes.ts:15-24`) y el rojo sale de las banderas `pasado` del back. |
| Caso de referencia §9.3/§9.4 al centavo | ✅ con el fake (`RendicionCasoDeReferenciaTest`) | No afirma los conceptos "Sin documento" y Hormigonera dentro de W-37 ni `presupuestos[].revision`. Falta de punta a punta (M-16). |

## 3. Permisos y alcance

**Back, Gastos:**

| Endpoint | Celda de la matriz | "Solo sus obras" | IDOR | Tests permitido / denegado / IDOR |
|---|---|---|---|---|
| GET `/api/gastos` | `gastos` ver (`BG/routes.php:23`) | sí, `VisiblesPara` | n/a | ✓ / ✓ / ✓ alcance |
| GET `/gastos/opciones` | cualquiera de las 3 de editar (`PuedeCargarGastos.php:40`) | n/a | n/a | ✓ / ✓ / n/a |
| GET `/gastos/obras-opciones` | ídem | sí | n/a | ✓ / ✓ / ✓ |
| GET `/gastos/factura-existente` | ídem | **no (M-1)** | **no** | ✓ / ✓ / **falta** |
| POST `/api/gastos` | según el origen (`AccesoGasto.php:127-136`) + `AlcanceDeCarga::exigir` | sí | 403 en obra ajena | ✓ / ✓ / ✓ |
| GET `/gastos/{id}` | `gastos` ver + `veGasto` | sí, renglones y totales filtrados | 404 | ✓ / ✓ / ✓ |
| PATCH `/gastos/{id}` | `gastos` editar + `exigirAlcance` | sí | 404 | ✓ / ✓ / **falta** el 404 de un gasto entero ajeno (M-2) |
| POST `/gastos/{id}/anular` | `gastos` editar | sí | 404 | ✓ / **falta** / **falta** (M-2) |
| GET `/gastos/{id}/historial` | `gastos` ver | sí | 404 | ✓ / **falta** / ✓ |
| GET `/obras/{obra}/renglones-sin-documento` | `rendicion` o `control_cantidad` ≥ ver, en el caso de uso | sí | 403 | ✓ / ✓ / ✓ (baja: 404 antes que 403) |
| Adjuntos `comprobante_gasto` | `GastoAdjuntos` | sí | 404 | ✓ |
| Adjuntos `remito` | `GastoAdjuntos.php:42-51` | sí | — | **sin tests (M-2)** |

**Back, Rendición:** todo por matriz con alcance. No se encontraron chequeos por rol en ninguno de los dos módulos (grep).

| Endpoint | Celda | Alcance / IDOR | Tests |
|---|---|---|---|
| GET `/api/rendiciones` | `rendicion` ver (`BR/.../AccesoRendicion.php:106-115`) | solo las obras donde es responsable | ✓ |
| GET `/obras/{id}/rendicion` (+ `rubros/{clave}`, `por-proveedor`) | `rendicion` ver; `monto` solo con `monto_obra` ver (`RendicionDeObraQuery.php:45`, la clave no viaja) | 404 | ✓ / ✓ / ✓ (`PermisosDeRendicionTest:27-38,75-83`) |
| GET `/obras/{id}/control-cantidad` | `control_cantidad` ver | 404 | ✓ |
| GET `…/materiales/opciones` | `control_cantidad` ≥ ver y (`gastos` o `rendicion` = editar) | 404 | ✓ |
| POST, PATCH y DELETE de materiales | `control_cantidad` editar | 404 "No encontramos este material." | ✓ |

**Front:**
- Los botones de carga dependen de `puede.*` del back.
- Laura no ve "+ Cargar" ni accesos a W-46; el celular queda en solo lectura; "Gastos (ver)" sale en la barra.
- Problemas: ALT-1, M-7, M-9, M-13 y la baja de `EditarGastoPantalla`.

## 4. Textos que no están en la spec

La propuesta por defecto es "cambia por el de la spec" cuando la spec tiene un texto para ese caso, y "queda" cuando la spec no dice nada y el texto es una validación o ayuda razonable. Juani decide.

| # | Texto | Dónde | Propuesta |
|---|---|---|---|
| 1 | "Guardamos los cambios." | `BG/Http/Controllers/GastoController.php:62` | queda (no hay texto en la spec) |
| 2 | "Anulamos la factura." / "Anulamos …" | `BG/Http/Controllers/AnularGastoController.php:32` | queda |
| 3 | "Gasto cargado. Ya se restó en la rendición de …" / "Gasto cargado. No va a ninguna obra (todo sin obra)." | `BG/.../MensajeDeCarga.php:44-45` | queda, con "✓" como RN-GAS-16 |
| 4 | Confirmaciones sin "✓" | `BG/.../MensajeDeCarga.php:40-45` | cambia: la spec las escribe con "✓" (si el front lo agrega, unificar en un solo lado) |
| 5 | "Todos los renglones van a la obra de la rendición." | `BG/.../ReglasDeRenglones.php:22` | queda |
| 6 | "Esta carga lleva un solo renglón." | `BG/.../ReglasDeRenglones.php:44` | queda |
| 7 | "Ese renglón no es de este comprobante." | `BG/.../CorregirGastoAction.php:34` | queda |
| 8 | "Obra no encontrada." | `BG/.../RenglonesSinDocumentoQuery.php:38` | cambia: "No encontramos esta obra." |
| 9 | "No tenés permiso para hacer esto." (403 de W-46 y W-33) | `BG/.../BuscarGasto.php:72`; `B/app/Shared/Http/ApiExceptionRenderer.php:31` | cambia: "No tenés acceso a Gastos. Si lo necesitás, pedíselo a gerencia." / "No podés cargar en esta rendición." (§7) |
| 10 | "Elegí qué se carga." | `BG/Http/Requests/CargarGastoRequest.php:50-51` | queda |
| 11 | "El valor no es válido." | `CargarGastoRequest.php:65`, `ListarGastosRequest.php:32`, `GastosRequest.php:74-75` | queda |
| 12 | "Son demasiados (máximo 1)." | `BG/.../ValidarGasto.php:59` | queda |
| 13 | "Completá este campo." · "Tiene que ser texto." · "Es demasiado largo…" · "Tiene que ser un número…" · "Está repetido." | `GastosRequest.php:66-77` | queda |
| 14 | "Escribí la fecha como dd/mm/aaaa, por ejemplo 31/12/2099." (la API recibe Y-m-d) | `GastosRequest.php:76,90`; `BR/Http/Requests/RendicionRequest.php:31` | queda (lo ve el usuario a través del front) |
| 15 | "Renglón N: hasta 3 decimales en la cantidad." | `GastosRequest.php:81` | queda (la spec solo tiene "Hasta 2 decimales en el precio.") |
| 16 | "Renglón N: la cantidad es demasiado grande." | `GastosRequest.php:82-83` | queda |
| 17 | "Renglón N: escribí el material." / "poné la unidad." | `GastosRequest.php:91-92` | queda |
| 18 | "El material X es de otra obra…" usado para "No está en la lista" en un renglón sin obra | `BG/.../MaterialDelRenglon.php:47-49` | cambia: el texto de la spec existe, pero está aplicado al caso equivocado |
| 19 | Historial: "cargó el gasto sin documento / el retiro / el período…", "cambió quién rindió", "cambió el período", "El sistema …" | `BG/.../TextoDeHistorial.php:22-23,36,59-64` | queda (la spec da solo los ejemplos de factura) |
| 20 | "(sin rubro)" como nombre de grupo | `BG/.../AgruparGastos.php:20` | queda |
| 21 | "No encontramos este material." | `BR/.../BuscarMaterial.php:22` | queda |
| 22 | "Escribí el material." / "Escribí la unidad." | `BR/Http/Requests/GuardarMaterialRequest.php:46-47`; `MaterialesDeObraEloquent.php:72-73` | queda |
| 23 | "Ese presupuesto no es de esta obra." | `BR/.../ReglasDeLaLista.php:68` | queda |
| 24 | "Escribí {la cantidad / el precio} como número, por ejemplo 12.5." · "… lleva como máximo N decimales." · "… no puede ser negativa/o." · "… es demasiado grande." | `BR/Http/Rules/DecimalNoNegativo.php:42-54` | cambia el ejemplo a "12,5" (coma, como el resto); el resto queda |
| 25 | "Es demasiado largo (máximo 150 caracteres)." | `MaterialesDeObraEloquent.php:72` | queda |
| 26 | "Material agregado a la lista." · "Material corregido." · "Material quitado de la lista." | `BR/Http/Controllers/MaterialController.php:57,65,72` | queda (y el front lo muestra tal cual, M-11) |
| 27 | "Este presupuesto ya tiene materiales del control por cantidad: no se puede quitar." | `B/app/Modules/Obras/.../QuitarPresupuestoAction.php` | queda (RN-OBR-24 no tiene texto) |
| 28 | "Retiro" (sin número) | `BR/.../DocumentoDelRenglon.php:24` | cambia: "Retiro · remito N", igual que Gastos |
| 29 | "Período 25-oct al 10-nov" (período que cruza meses) | `BR/.../DocumentoDelRenglon.php:44` | queda (igual que Gastos) |
| 30 | "-39.200" con guion | `BG/.../Legible.php:36`; `F/shared/dominio/importes.ts:18` | cambia a "−" (U+2212), si se considera importante |
| 31 | "Hasta 3 decimales en la cantidad." | `FG/mensajes.ts:19` | queda |
| 32 | "Escribí la cantidad con números, por ejemplo 2,5." / "Escribí el precio con números, por ejemplo 9.800,50." | `FG/mensajes.ts:20,24` | queda |
| 33 | "Escribí el material." / "Escribí la unidad." | `FG/mensajes.ts:30-31` | queda |
| 34 | "✓ Gastos cargados. Ya se restó…" / "…No va a ninguna obra (todo sin obra)." | `FG/components/carga/textos.ts:11-15` | cambia: mostrar el `message` del back (regla "mensajes del back tal cual") |
| 35 | "Gastos cargados" (h1) · "Ver los gastos" · "Cargar otros" | `FG/.../GastoCargado.tsx:19,26,29` | cambia: "Ver la factura · Cargar otra" (en sin documento, decidir el plural) |
| 36 | "Ver la factura cargada" | `FG/.../FacturaRepetida.tsx:26`; `CabeceraFactura.tsx:71` | queda (la spec dice "con link a W-34") |
| 37 | "Guardar igual" | `FG/.../GastoFormulario.tsx:162` | cambia: "¿Cargar igual?" / "¿Cargarlo igual?" |
| 38 | "No, revisar" | `FG/.../SeccionSinDocumento.tsx:128` | queda |
| 39 | "Con tu permiso podés ver lo cargado, pero no cargar en esta sección." | `FG/.../SeccionSinDocumento.tsx:56` | cambia: "No podés cargar en esta rendición." |
| 40 | "Obra no encontrada" + "Puede que el link esté mal o que no tengas acceso a esa obra." | `FG/pantallas/RenglonesSinDocumentoPantalla.tsx:33-34` | cambia: "No encontramos esta obra." |
| 41 | "Si lo necesitás, pedíselo a gerencia." (403 de W-46) | `RenglonesSinDocumentoPantalla.tsx:77` | cambia: "No podés cargar en esta rendición." |
| 42 | "No tenés acceso a esta sección" (título) | `FG/components/lista/EstadoListaGastos.tsx:18` | cambia: "No tenés acceso a Gastos. Si lo necesitás, pedíselo a gerencia." |
| 43 | "Volver a Gastos cargados" / "Volver al gasto" / "‹ Volver a la obra" | `CargarGastoPantalla.tsx:37`; `EditarGastoPantalla.tsx:23,91`; `RenglonesSinDocumentoPantalla.tsx:115` | queda |
| 44 | "Editar · {documento}" | `EditarGastoPantalla.tsx:97` | queda |
| 45 | "Guardamos los cambios del gasto." | `EditarGastoPantalla.tsx:119` | cambia: el `message` del back |
| 46 | "Tu sector puede ver los gastos pero no corregirlos…" | `EditarGastoPantalla.tsx:56-61` | queda |
| 47 | "Cargando…" · "Cargando el gasto…" · "Cargando los gastos…" · "Buscando gastos…" · "Cargando los renglones sin documento…" · "Cargando el historial…" | `FG/rutas.tsx:17`; `EditarGastoPantalla.tsx:49`; `GastoPantalla.tsx:75`; `GastosPantalla.tsx:56,66`; `RenglonesSinDocumentoPantalla.tsx:68`; `HistorialGasto.tsx:18` | cambia: §7 pide filas grises o esqueleto, sin texto |
| 48 | "Todavía no hay cambios registrados." | `FG/.../HistorialGasto.tsx:22` | queda |
| 49 | "Anulando…" | `FG/.../AnularDialog.tsx:61` | queda |
| 50 | "Anular · {título}" (cuando no es factura) | `FG/components/detalle/textosDetalle.ts:59` | queda (la spec solo tiene "Anular la factura N") |
| 51 | "No va a ninguna obra (todo sin obra). No se puede deshacer." | `textosDetalle.ts:66-69` | queda |
| 52 | "{nombre} (genérico)" en el detalle | `FG/components/detalle/CabeceraGasto.tsx:20` | cambia: "Gastos sin comprobante (genérico)", como en los filtros |
| 53 | "Buscar obra" + "Por nombre del trabajo o N.º de presupuesto: las encontradas aparecen en cada renglón." + "No pudimos traer las obras. Probá de nuevo en un momento." | `FG/.../Renglones.tsx:67-71` | queda (queda atado a M-5) |
| 54 | "Buscar proveedor" + "Por nombre o CUIT." + "No pudimos traer los proveedores. Probá de nuevo en un momento." | `FG/components/carga/campos/CampoProveedor.tsx:92-98` | queda |
| 55 | "Elegí primero la obra." · "No pudimos traer los materiales…" · "Material que no está en la lista" · "Unidad" · "Por ejemplo: par, bolsa, m²." · "Sin material" · " · fuera de presupuesto" | `FG/.../CampoMaterial.tsx:51-53,72,78,87,98` | cambia "Material que no está en la lista" por "No está en la lista"; el resto queda |
| 56 | "Sin rubro" / "Elegí…" | `FG/.../CamposDelRenglon.tsx:90,50` | queda |
| 57 | "Detalle (opcional)" + "La cotización o una aclaración." | `FG/.../RenglonFila.tsx:179` | queda (dice lo mismo que la spec) |
| 58 | "Elegí de la lista o escribí el nombre (por ejemplo, supervisor de obra)." | `FG/.../CabeceraSinDocumento.tsx:68` | queda |
| 59 | "Subir archivo, sacar una foto o pegar link" / "Archivo, foto o link." · "Subir archivo o pegar link" + "Factura (opcional)" | `CabeceraSinDocumento.tsx:116`; `SeccionRetiros.tsx:125`; `CabeceraFactura.tsx:119-129` | queda (dice lo mismo) |
| 60 | "Va en negativo: se descuenta." / "Si no elegís material: por ejemplo, “Cemento x 50 kg (sobrante)”." | `FG/components/sin-documento/RenglonesMaterial.tsx:76,60` | queda |
| 61 | "Rubro: AMORTIZACION" | `FG/.../SeccionAmortizacion.tsx:130` | queda |
| 62 | "Un monto total por período, sin detalle por persona: quincenas…" / "Por defecto: “…”." | `FG/.../SeccionPeriodos.tsx:175,152` | queda |
| 63 | Fechas "17/10/2026" y "18/10/2026 09:30" (factura repetida, anulada, "Cargó", buscador) | varios de `FG/` | decidir: la spec usa "17-oct"; PU-209 fijó dd/mm/aaaa para toda la app |
| 64 | El botón de guardar mantiene su etiqueta y agrega un spinner ("Guardando…" solo para el lector de pantalla) | `F/shared/ui/Button.tsx` | decidir: §7 dice "el botón dice 'Guardando…'" |
| 65 | "Si lo necesitás, pedíselo a gerencia." | `FR/components/RendicionDeLaObra.tsx:98` | queda (va junto con "No tenés acceso a la rendición.", el mismo patrón de §7) |
| 66 | "pasado" / "pasado · sin presupuesto" | `FR/components/Cifras.tsx:32` | cambia: "sin presupuesto" (RN-REN-21) |
| 67 | "cargar período" y "cargar retiro con remito" (con ícono) | `FR/components/TablaRubros.tsx:28`; `FR/pantallas/ControlCantidadPantalla.tsx:150` | cambia: "+ cargar período" / "+ cargar retiro con remito" |
| 68 | "Sin desglose por rubro (…): 1.000.000" (celular) | `FR/components/TarjetasRubros.tsx:103` | cambia: la leyenda de presupuesto mixto de la spec (hoy se repite) |
| 69 | "Cargar el monto de la obra" | `FR/components/MontoYGanancia.tsx:33` | queda (la spec dice "acceso a W-13" sin texto) |
| 70 | "(—)" después de Gastado | `FR/components/DetalleRubro.tsx:31` | cambia: solo "Presupuestado —" |
| 71 | "Cargando los gastos del rubro…" · "Ordenando…" · "Cargando los gastos por proveedor…" · "Buscando gastos…" · "Cargando el control por cantidad…" | `DetalleRubro.tsx:89,118`; `PorProveedor.tsx:97,107`; `ControlCantidadPantalla.tsx:108` | cambia: esqueleto (§6) |
| 72 | "Mostrando N de M ·" / "ver el restante" | `FR/components/PorProveedor.tsx:48,154` | cambia: "Mostrando 4 de 9 proveedores" / "ver los N restantes" |
| 73 | "No pudimos actualizar: se ve lo último que llegó." | `ControlCantidadPantalla.tsx:167` | cambia: "No pudimos actualizar · hace 2 minutos" |
| 74 | "corregir" · "quitar" · "pasar a la lista" · "Corregir · X" · "Guardar" · "Guardando…" · ayudas "Por ejemplo Cemento x 50 kg." y "bolsa, m³, paño, barra, kg…" | `FR/components/materiales/TablaMateriales.tsx:102,107`; `PartesControl.tsx:120`; `MaterialDialog.tsx:90,103,123,127` | queda |
| 75 | "Agregamos X a la lista." / "Guardamos los cambios de X." · "Quitar · X" · "Quitar material" · "Quitando…" · "¿Quitar X de la lista? Es para un material cargado por error: …" · "Quitamos X de la lista." · "Escribí la unidad, por ejemplo bolsa o m³." · "La cantidad no puede ser negativa." · "Escribí la cantidad con números, por ejemplo 12,5." · "La cantidad puede tener hasta 3 decimales." · "La cantidad es demasiado grande." · "… puede tener hasta N caracteres." | `MaterialDialog.tsx:79-80`; `QuitarMaterialDialog.tsx:32,37,49,56-57`; `materialFormulario.ts:20-32,44,48` | cambian los de éxito por el `message` del back (M-11); el resto queda |
| 76 | " · se pasó del presupuesto" (cabecera de la ficha y texto para lector de pantalla del listado) | `F/features/obras/components/ficha/CabeceraObra.tsx`; `ObrasTabla.tsx` | queda (la spec dice "Rendición: 55 % gastado" sin sufijo) |

**Total:** 76 filas, unas 120 cadenas sueltas.
- Proponen **cambiar**: 25 (#4, 8, 9, 18, 24, 28, 30, 34, 35, 37, 39, 40, 41, 42, 45, 47, 52, 55, 66, 67, 68, 70, 71, 72, 73), más los mensajes de éxito de #75.
- Para **decidir**: 2 (#63, #64).

## 5. Riesgos para la prueba del cliente ("comparan con su rendición en Excel")

1. **BLQ-1:**
   - Desde W-11, "ver todos" muestra solo noviembre.
   - Si comparan el "Total de lo filtrado" de W-33 contra el gastado de la obra, no coincide.
   - Es la verificación que más van a hacer.
2. **BLQ-2:** agrupar por proveedor o por obra (sus tablas dinámicas) da una pantalla de error.
3. **BLQ-3:**
   - Gerencia y Administración, que son quienes prueban, no pueden abrir la rendición de una obra sin monto cargado.
   - Si la obra elegida tiene monto, la prueba pasa, pero cualquier otra obra de la semana 1 falla.
4. **ALT-3:**
   - Cargan facturas de octubre, W-33 abre en noviembre y dice "Todavía no hay gastos cargados."
   - Van a creer que no se guardaron, y riesgo de cargarlas dos veces. El índice único lo frena con el 422.
5. **ALT-1 y ALT-2:** filtros incompletos para quien solo consulta y para más de 20 proveedores u obras.
6. **Diferencias que son de la spec, a escribir en la guía de comparación de `rendicion.md` §13:**
   - W-33 filtra por la fecha del comprobante y la rendición por la fecha del renglón (DU-G01). En W-35 (tickets de días distintos) y en los períodos, los totales mensuales de W-33 y de W-36/W-37 pueden diferir.
   - Un "Fijos 1 al 30-nov" cargado el 24-nov queda fechado el 30-nov: no aparece en el W-33 por defecto, pero la rendición ya lo resta.
   - Precios con más de 2 decimales se rechazan: hay que redondear a mano, y el redondeo por renglón da diferencias de centavos contra Excel (ya está en §13).
   - Una sola amortización por obra: si en el Excel tienen varias filas, hay que sumarlas.
   - W-37 agrupa por proveedor, no por tipo de documento: un sin comprobante o un combustible con proveedor real va bajo ese proveedor, no en "Sin documento".
   - W-12 suma al total el gastado de las obras sin presupuesto.
7. **M-10 y M-15:** W-36 y W-37 no se refrescan solas, y existe la ventana de cache vieja de M-15. Si cargan en una PC y miran en otra, pueden ver números desfasados entre W-11, W-36 y W-12 hasta navegar (o hasta 10 minutos en el peor caso de M-15).
8. **M-16:** el caso de referencia nunca corrió de punta a punta por la API. Es el criterio global n.º 1 y depende de Integración S3.
9. **M-17:** con `rendicion` en `VITE_MODULOS_ENTREGADOS`, Rendiciones no aparece y no hay ningún error.
10. **Formato:** las fechas dd/mm/aaaa en W-48 y W-37 no coinciden con la maqueta, y los negativos salen con guion. No cambian los números.

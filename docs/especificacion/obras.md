# Especificación funcional · Obras (el legajo) y presupuesto por rubro

> Módulo `app/Modules/Obras` (+ el dato "presupuestado" que usa `Rendicion`) · Entrega: **semana 1**
> Rol: Analista funcional · 2026-11-12
> Contrato: documento firmado v1.1 (cap. 4.1, 4.5, 6, 9 "Rubros" y "Cómo se calcula") + maquetas
> W-05, W-06, W-07, W-13, W-20, W-21, W-22, W-23, W-47. Decisiones: D-016, D-017, D-022, D-026,
> D-027, D-028, D-039; definiciones aprobadas C1, C2, C16, C17, C18, C19. ADR-006/008/010/011.
> Lo "Propuesto" del documento está **aprobado**. Lo no especificado → `docs/dudas.md` (DU-A..).
> Clientes, obras, personas e importes de este archivo son **ficticios** (los de las maquetas).

## 1. Propósito

"Identificamos por obra, no por cliente" (16-oct). La obra es el centro del sistema: un trabajo
ganado = un presupuesto aprobado (más sus adicionales) con su OC/OT/contrato/mail de aprobación,
para un cliente. Se entra a la obra y adentro está todo lo suyo. En semana 1 se entrega el legajo
(alta, ficha, documentos y contactos, edición con historial, estados) y el **presupuesto por
rubro** de cada presupuesto; las pestañas Pedidos y Rendición se llenan en semanas 2 y 3 con los
contratos de §10.4.

## 2. Funcionalidades y pantallas cubiertas

| F-XX | Qué | Pantalla | Semana 1 |
|---|---|---|---|
| F-15 | Listado por estado con N.º, trabajo, cliente/planta, responsable, pedidos abiertos, % gastado | W-05 · W-47 | completo; "pedidos abiertos" = 0 y "% gastado" = "—" hasta semanas 2/3 (contratos §10.4) |
| F-03 (parcial) | Buscar obras por N.º de presupuesto o palabra clave | W-05 · W-47 (W-18 es semana 2) | completo dentro del listado de obras |
| F-16 | Alta de obra | W-06 | completo |
| F-17 | Ficha con pestañas Pedidos · Rendición · Documentos y contactos | W-07 · W-47 | cabecera y Documentos completos; Pedidos y Rendición con contrato |
| F-18 | Presupuestos de la obra: principal + adicionales | W-21 · W-20 | completo |
| F-19 | Documentos y contactos; certificados; carpeta de Drive | W-20 | completo |
| F-20 | Editar con registro del cambio | W-23 | completo |
| F-21 | Cambiar estado; al pasar a Terminada, ver pedidos abiertos | W-22 | completo; lista vacía hasta semana 2 (contrato §10.4) |
| F-22 | Terminadas consultables | W-05 | completo |
| F-23 | Fechas previstas de inicio y fin | W-06 · W-23 | completo |
| F-48 | Presupuesto por rubro de cada presupuesto, o solo el total | W-13 · W-21 | completo |
| F-14 (obras) | Registro de quién hizo qué y cuándo en la obra | W-23 | completo |

Fila **"Pedidos sin obra"** de W-05: se especifica acá (RN-OBR-08); su contenido es de semana 2.

## 3. Glosario

| Cliente | Código | Qué es |
|---|---|---|
| Obra / legajo | `Obra` / `obras` | Trabajo ganado. Agrupa 1 a N presupuestos. |
| N.º de presupuesto | `NumeroPresupuesto` (VO) | "N-NNN-AAAA" (p. ej. `2-131-2026`); el adicional puede llevar sufijo `A1`. El de la obra es el del **principal**. |
| Revisión | `Revision` (VO) | "REV01", "REV02"… del mismo presupuesto. |
| Presupuesto principal / adicional | `Presupuesto` · `es_principal` | El que abrió la obra / los que se suman después ("A1"). |
| Oferta técnica | `presupuesto_url` | Link al PDF/planilla del presupuesto en el Drive. |
| OC / OT / contrato / mail de aprobación | `aprobacion_tipo` `oc`·`ot`·`contrato`·`mail` | Cómo aprobó el cliente ese presupuesto. No es obligatorio. |
| Cliente · planta | `cliente`, `planta` | Quién contrata; planta = dónde trabajan si entran como subcontratistas de otra empresa. |
| Responsable de obra | `responsable_id` | La persona a cargo (hoy la fila 1 de cada solapa de la minuta). |
| Solicitante en planta / comprador | `obra_contactos.tipo` `solicitante`·`comprador` | Los dos contactos del cliente: a uno lo llama operaciones, al otro administración. |
| Estado | `ObraEstado`: `confirmada` · `en_ejecucion` · `terminada` | Confirmada · En ejecución · Terminada. |
| Rubro | `Rubro` / `rubros` | Lista cerrada de su rendición (§4.6). |
| Presupuesto por rubro | `presupuesto_montos` | Cuánto hay para gastar en cada rubro, por presupuesto. |
| Solo el total | `modo_carga = solo_total` | Presupuesto cargado con un único monto, sin rubros (2 de sus 6 rendiciones). |
| Monto de la obra | `presupuestos.monto_obra` | Lo que se le cobra al cliente por ese presupuesto (sin IVA); la suma da el monto de la obra (C19). |
| Pedidos abiertos | — | Pedidos de la obra en No iniciada, En curso o Bloqueada. |
| Pedidos sin obra | — | Hoy la solapa "Servicios generales". |

## 4. Datos

### 4.1 Obra (`obras`) — tabla "Datos de la obra" (cap. 6), W-06, W-23

| Campo | Tipo | Oblig. | Validación | Fuente |
|---|---|---|---|---|
| presupuesto principal → numero | `NumeroPresupuesto` | **Sí** | RN-OBR-10…13 | cap. 6 "N.º de presupuesto y revisión: Sí" · W-06 "*" |
| presupuesto principal → revision | `Revision` | **Sí**, precargada en `REV01` (DU-05) | RN-OBR-14 | cap. 6 "N.º de presupuesto y revisión: Sí" · W-06 "REV01" |
| nombre_trabajo | texto ≤ 200 | **Sí** | no vacío | cap. 6 · W-06 "*" |
| cliente | texto ≤ 150 | **Sí** | no vacío; el campo sugiere los clientes ya usados (RN-OBR-17) | cap. 6 "Cliente sí" · W-06 "Cliente * ▾" |
| planta | texto ≤ 150 | No | — | cap. 6 "planta (si…)" · W-06 "Planta (si aplica)" |
| responsable_id | ref. usuario activo | **Sí** | cualquier usuario activo | cap. 6 · W-06 "*" |
| presupuesto principal → presupuesto_url | URL ≤ 500 | **Sí** (DU-A14) | `http(s)://` | cap. 6 "Link al presupuesto (oferta técnica): Sí" |
| presupuesto principal → aprobacion_tipo / numero / url | ver §4.3 | No | RN-OBR-16 | cap. 6 "No (la OC no siempre llega)" · D-027 |
| contacto solicitante: nombre, email, telefono | texto ≤ 120 · mail · texto ≤ 50 | **Sí** los tres (DU-A14) | mail con formato; teléfono libre | cap. 6 "Contacto solicitante… y comprador: nombre, mail, teléfono: Sí" |
| contacto comprador: nombre, email, telefono | ídem | **Sí** los tres (DU-A14) | ídem | ídem |
| estado | `ObraEstado` | Sí | nace `confirmada`; no se edita en el formulario | cap. 6 "Sí (arranca en Confirmada)" · W-06 · W-23 |
| fecha_inicio_prevista | fecha | No | — | F-23 · W-06 |
| fecha_fin_prevista | fecha | No | si están las dos, fin ≥ inicio | F-23 · W-06 |
| carpeta_drive_url | URL ≤ 500 | No | `http(s)://` | F-19 · W-07 · W-20 |
| created_by, created_at | — | — | — | W-20 "Cargado", W-23 "Creó la obra" |
| terminada_at, terminada_por | — | — | se completan al pasar a Terminada | F-22 |

### 4.2 Presupuesto (`presupuestos`) — F-18, W-21, W-20

| Campo | Tipo | Oblig. | Validación | Fuente |
|---|---|---|---|---|
| obra_id | ref. obra | Sí | — | — |
| es_principal | booleano | Sí | **exactamente uno** por obra; no se cambia | W-07 "(principal)", W-20 "Tipo" |
| numero | `NumeroPresupuesto` | **Sí** | RN-OBR-10…13 | W-21 "N.º de presupuesto *" |
| revision | `Revision` | principal Sí (§4.1) · adicional No (W-20 muestra "—" en el A1; DU-A14) | RN-OBR-14 | W-21 "Revisión" |
| presupuesto_url | URL | principal Sí (§4.1) · adicional No (DU-A14) | `http(s)://` | W-21 "Link al presupuesto" |
| descripcion | texto ≤ 200 | No | — | W-21 "Descripción" (aprobado) |
| aprobacion_tipo | `oc`·`ot`·`contrato`·`mail` · null | No | RN-OBR-16 | W-20 "Documento" |
| aprobacion_numero | texto ≤ 60 | No | — | W-06 "N.º de OC", W-21 "N.º de OC (si ya está)" |
| aprobacion_url | URL | No | `http(s)://` | W-06, W-21 "Link a OC / mail de aprobación" |
| modo_carga | `sin_cargar`·`por_rubro`·`solo_total` | Sí (default `sin_cargar`) | RN-OBR-31 | C16 · W-13 |
| monto_obra | importe (decimal 15,2, ≥ 0) | No | solo lo lee/escribe quien tiene `monto_obra` (RN-OBR-36) | C19 · F-54 · DU-19 |
| created_by, created_at | — | — | — | W-20 "Cargado: 10-oct · Laura" |

### 4.3 Documento de aprobación (dentro de `presupuestos`) — W-20

Uno por presupuesto, opcional (W-20: columna "De qué presupuesto"). Se muestra en la tabla
"OC / OT / contrato / mail de aprobación" con **Documento · N.º · Link · De qué presupuesto**.
Etiquetas: `oc` → "Orden de compra", `ot` → "Orden de trabajo", `contrato` → "Contrato",
`mail` → "Mail de aprobación".

### 4.4 Contactos (`obra_contactos`) — W-06, W-20

Dos por obra, uno de cada tipo: **Solicitante en planta** y **Comprador**, cada uno con nombre,
mail y teléfono (todos obligatorios, §4.1). Único (obra, tipo).

### 4.5 Certificados (`obra_documentos`, tipo `certificado`) — W-20 (aprobado)

| Campo | Tipo | Oblig. | Validación |
|---|---|---|---|
| nombre | texto ≤ 120 | Sí | p. ej. "Certificado N.º 1" |
| fecha | fecha | Sí | W-20 columna "Fecha" |
| url | URL | Sí | `http(s)://` (es un link: el archivo vive en el Drive, D-022) |
| created_by, created_at | — | — | — |

### 4.6 Rubros (`rubros`) — lista cerrada, cap. 9, por migración

| Orden | Clave | Nombre (como en sus planillas) | Se presupuesta (W-13) | Se imputa (semana 3) | En el resumen suma en | Carga por período |
|---|---|---|---|---|---|---|
| 1 | `materiales` | MATERIALES | Sí | Sí | — | No |
| 2 | `insumos` | INSUMOS | Sí | Sí | — | No |
| 3 | `epp` | EPP | **No** | Sí | **INSUMOS** | No |
| 4 | `subcontratos` | SUBCONTRATOS | Sí | Sí | — | No |
| 5 | `logistica` | LOGISTICA | Sí | Sí | — | No |
| 6 | `amortizacion` | AMORTIZACION | Sí | Sí | — | No |
| 7 | `mantenimiento` | MANTENIMIENTO | Sí | Sí | — | No |
| 8 | `obradores_banos` | OBRADORES/BAÑOS | Sí | Sí | — | No |
| 9 | `servicios_generales` | SERVICIOS GENERALES | Sí | Sí | — | No |
| 10 | `maquinas_herramientas` | MAQUINAS/HERRAMIENTAS | Sí | Sí | — | No |
| 11 | `viaticos` | VIATICOS | Sí | Sí | — | No |
| 12 | `fijos` | FIJOS | Sí | Sí | — | **Sí** (monto total por período, semana 3) |
| 13 | `horas_liquidadas` | HORAS LIQUIDADAS | Sí | Sí | — | **Sí** (ídem, sin detalle por empleado) |

Al ser lista cerrada evita "INSUMO" por "INSUMOS" (cap. 9). No se edita desde pantallas en la
Etapa 1. W-13 muestra los 12 que se presupuestan, en este orden.

### 4.7 Presupuesto por rubro (`presupuesto_montos`) — F-48, W-13

| Campo | Tipo | Oblig. | Validación |
|---|---|---|---|
| presupuesto_id | ref. | Sí | — |
| rubro_id | ref. rubro presupuestable · null | — | null solo en `solo_total` |
| monto | importe decimal(15,2), viaja como string `"10900000.00"` | Sí | ≥ 0, ≤ 9.999.999.999.999,99; neto sin IVA (C9) |

## 5. Reglas de negocio

### Listado y búsqueda (F-15, F-22, F-03, W-05, W-47)

- **RN-OBR-01** Tres pestañas, en este orden: **En ejecución (n) · Confirmadas (n) · Terminadas (n)**; abre en En ejecución ("primero las activas"). Cada pestaña muestra su cantidad. *(F-15, W-05, D-028)*
- **RN-OBR-02** Columnas: **N.º de presupuesto** (el del principal; debajo, en chico, la revisión si tiene y "+ N adicional/es": "REV01 + 1 adicional") · **Trabajo** · **Cliente / planta** (planta debajo, en chico) · **Responsable de obra** · **Pedidos abiertos** · **% del presupuesto gastado** (barra + %). *(F-15, W-05)*
- **RN-OBR-03** "Pedidos abiertos" sale del contrato `PedidosDeObra` (§10.4). Semana 1 el puerto nulo devuelve `null` y se muestra **"—"** (no 0, para no decir "no hay pedidos" cuando el módulo todavía no está; DU-A12). *(F-15)*
- **RN-OBR-04** "% gastado" = gastado ÷ presupuestado de la obra, del contrato `AvanceGastoDeObra` (§10.4); en rojo si pasa el 100 %. Sin presupuesto cargado o hasta semana 3: **"—"**. Solo se muestra a quien tiene `rendicion` ≥ ver para esa obra; si no, "—". *(F-15 aprobado, W-05)*
- **RN-OBR-05** Buscador "Buscar por N.º de presupuesto, cliente o trabajo…": encuentra la obra si el texto está en el **N.º de cualquiera de sus presupuestos** (principal o adicional), en el **cliente**, en el **nombre del trabajo** y, si se aprueba DU-11, en la **planta**. Sin distinguir mayúsculas ni tildes. Para números se ignoran los separadores: "2131", "2-131" y "2 131 2026" encuentran `2-131-2026`. *(F-03 "N.º de presupuesto, está ok, con alguna palabra clave", W-05)*
- **RN-OBR-06** La búsqueda y el filtro **"Responsable: todos ▾"** se aplican a las tres pestañas a la vez y los contadores muestran lo que queda.
- **RN-OBR-07** Orden dentro de cada pestaña: En ejecución por % gastado de mayor a menor (como W-05 y W-12; sin dato, al final por N.º); Confirmadas por fecha de alta, la más nueva primero; Terminadas por fecha en que se terminó, la más nueva primero. *(DU-A20)*
- **RN-OBR-08** Fila **"Servicios generales · pedidos que no son de una obra"**: al final de la pestaña **En ejecución**, con la cantidad de pedidos sin obra abiertos (contrato `PedidosDeObra::contarAbiertosSinObra()`) y sin % gastado; lleva a Pedidos sin obra (W-25, semana 2). Se muestra solo a quien tiene `pedidos` ≥ ver y no se muestra mientras hay texto en el buscador. Semana 1: no se muestra (no hay módulo Pedidos, DU-A12). *(W-05 aprobado, F-36)*
- **RN-OBR-09** Las obras **Terminadas** no se pierden: se consultan igual (ficha, documentos, historial, pedidos y rendición). *(F-22, D-017)*

### N.º de presupuesto y revisión

- **RN-OBR-10** Formato: **`P-NNN-AAAA`** como en la minuta ("CLIENTE N-NNN-AAAA REV01"): `P` = prefijo de 1 dígito (hoy 1, 2 o 3; su significado no hace falta), `NNN` = número de 1 a 4 dígitos que se guarda con **al menos 3** (`31` → `031`), `AAAA` = año de 4 dígitos entre 2000 y el año siguiente al actual. Opcional, **sufijo de adicional** `A` + 1 o 2 dígitos ("A1"). Forma guardada y mostrada: `2-131-2026`, `2-131-2026 A1`. *(minuta, rendiciones, W-20/W-21, DU-05)*
- **RN-OBR-11** **Validación tolerante** (lo que se escribe → lo que se guarda):

  | Se escribe | Se guarda |
  |---|---|
  | `2-131-2026` · ` 2 131 2026 ` · `2/131/2026` · `2.131.2026` · `2_131_2026` | `2-131-2026` |
  | `2-31-2026` | `2-031-2026` |
  | `2-131-2026 a1` · `2-131-2026-A1` · `2-131-2026A1` | `2-131-2026 A1` |
  | `2-131-2026 REV01` en el campo número (con la revisión vacía) | número `2-131-2026` + revisión `REV01` |
  | `2-131-2026 REV02` con la revisión cargada `REV01` | 422 "El número trae REV02 pero la revisión dice REV01." |
  | `2-131-26` | 422 "Escribí el año con 4 dígitos, por ejemplo 2-131-2026." |
  | `3-115117-2026` | 422 "Eso parecen dos presupuestos: cargá uno como principal y el otro como adicional." |
  | `PLANTA 2-131-2026` o texto sin número | 422 "Escribí solo el número, por ejemplo 2-131-2026." |

- **RN-OBR-12** **Unicidad**: el N.º (con su sufijo, **sin** la revisión) no se repite en ningún presupuesto de ninguna obra, en ningún estado. Se compara la forma guardada (así `2-31-2026` choca con `2-031-2026`). 422: "El presupuesto 2-131-2026 ya está en la obra 'Piso industrial nave 3'." con link a la obra. *(DU-05; DU-A16: el modelo de datos proponía único por número + revisión)*
- **RN-OBR-13** Una **revisión nueva** (REV01 → REV02) **no** es otro presupuesto: se edita la revisión del mismo presupuesto y queda en el historial. *(minuta: "REV01, rev02…" en el nombre de la misma solapa)*
- **RN-OBR-14** Revisión, tolerante: `REV01`, `rev01`, `Rev 1`, `REV1`, `REV-1`, `R1`, `1` → **`REV01`**; `rev02` → `REV02`. En el principal es obligatoria y el formulario la trae precargada en `REV01`; en un adicional, vacía = sin revisión ("—"). Otro texto: 422 "Escribí la revisión como REV01." *(minuta, DU-05)*
- **RN-OBR-15** Al agregar un adicional, el N.º viene **sugerido** como el del principal + el siguiente sufijo libre (`2-131-2026 A1`, después `A2`); se puede cambiar por un número propio (en sus rendiciones hay adicionales con número propio y de otro año). *(rendiciones, W-21)*

### Alta, edición, documentos y contactos (F-16, F-18, F-19, F-20, F-23)

- **RN-OBR-16** El documento de aprobación es **opcional** y se puede agregar después: "La OC no es obligatoria para arrancar; se puede agregar después" / "El N.º de OC se completa cuando llega; no frena la obra". Si se carga, debe tener tipo y al menos el número o el link; si no se elige tipo pero hay número o link, se asume **Orden de compra**. *(D-027, W-06, W-23)*
- **RN-OBR-17** Cliente: texto con **sugerencias** de los clientes ya cargados en otras obras (el desplegable "▾" de W-06), para escribirlo siempre igual; se puede escribir uno nuevo. No hay alta de clientes aparte: "el cliente es un dato de la obra". *(D-026, W-06, DU-06; modelo-datos)*
- **RN-OBR-18** La obra **nace Confirmada** y con eso ya se le pueden cargar pedidos. "Con estos datos ya se pueden cargar pedidos a la obra." *(cap. 4.1, W-06)*
- **RN-OBR-19** El alta crea, en una sola operación, la obra, su presupuesto principal, sus dos contactos y, si se cargaron con "+ agregar presupuesto adicional", sus adicionales. Si algo falla, no se crea nada. *(W-06)*
- **RN-OBR-20** Una obra tiene **uno o más** presupuestos (hoy 1 a 3); los adicionales se agregan cuando aparecen, a la misma obra, desde la ficha o desde Documentos (W-21) con N.º, revisión, link, OC si la hay y descripción. *(F-18, cap. 4.1, W-21)*
- **RN-OBR-21** Al guardar un adicional, quien tenga `presupuesto_rubro = editar` pasa directo a cargar sus montos (botón **"Guardar y cargar montos por rubro →"**); quien no, ve solo **"Guardar"** y el adicional queda "sin cargar" para administración. *(W-21, cap. 3)*
- **RN-OBR-22** Editar (W-23) es el mismo formulario del alta con los datos cargados, incluidos contactos y presupuestos. **El estado no se edita acá** ("se cambia con 'Cambiar estado' (W-22)"). El presupuesto principal no se puede convertir en adicional ni al revés. *(F-20, W-23)*
- **RN-OBR-23** Certificados: se agregan como links con nombre y fecha; se pueden corregir o quitar, y queda en el historial. La ficha tiene el link **"Abrir carpeta de Drive de la obra"** si está cargado. El sistema guarda links, no copias. *(F-19, W-20, D-022)*
- **RN-OBR-24** No se borran obras ni el presupuesto principal en la Etapa 1: se corrigen. Un **adicional** cargado por error se puede quitar mientras no tenga montos por rubro cargados (ni, desde la semana 3, materiales del control por cantidad); queda en el historial. *(DU-A17)*
- **RN-OBR-25** Obras **Terminadas** se pueden seguir editando (corregir datos, sumar un certificado final) con el mismo permiso. *(DU-A15)*

### Historial de cambios (F-20, F-14, W-23)

- **RN-OBR-26** Cada alta, cambio de dato, presupuesto agregado o editado, cambio de montos, contacto, certificado y cambio de estado queda en `actividades` con **quién, cuándo y qué cambió (antes → después)**, en la misma transacción. *(F-14, F-20, ADR-010)*
- **RN-OBR-27** Se muestra abajo de W-23, el más nuevo arriba, con **Cuándo · Quién · Qué cambió**, en palabras del cliente: "N.º de OC: (vacío) → 4500-000123", "Link a OC: agregado" (los links se informan como agregado/cambiado/quitado, sin la URL), "Agregó el presupuesto adicional 2-131-2026 A1", "Contacto comprador: mail corregido", "Cambió el estado: En ejecución → Terminada", "Creó la obra". *(W-23)*
- **RN-OBR-28** Las líneas del historial sobre **montos por rubro** solo las ve quien tiene `presupuesto_rubro` ≥ ver, y las de **monto de la obra** solo quien tiene `monto_obra` ≥ ver (los demás no las ven, ni sus valores). *(cap. 3)*

### Estados (F-21, F-22, W-22)

- **RN-OBR-29** Estados: **Confirmada → En ejecución → Terminada**. Transiciones permitidas: Confirmada → En ejecución y En ejecución → Terminada. Volver un paso atrás: pendiente de DU-07; saltar de Confirmada a Terminada no se permite (DU-07: "avanzar sigue siendo de a un paso"); el dominio guarda las transiciones en una tabla (`ObraEstado::puedePasarA()`) para cambiarlo sin tocar casos de uso. *(F-21, D-028)*
- **RN-OBR-30** Cambian el estado el **responsable de esa obra** (siempre, regla fija) y quien tenga **Estado de obra = editar** (inicial: Gerencia y Jefatura de Administración). Se cambia **solo** desde "Cambiar estado / pasar a Terminada" (W-22). *(F-21, ADR-006)*

### Presupuesto por rubro y monto de la obra (F-48, F-54 dato, C16, C19)

- **RN-OBR-31** Cada presupuesto se carga **o por rubro o con el total**, no mezclado: `por_rubro` = un monto por cada rubro presupuestable (vacío = "—", cuenta 0), `solo_total` = un único monto. Pasar de uno a otro reemplaza lo cargado (el front pide confirmación) y queda en el historial. Mientras no se cargó nada, `sin_cargar`. *(F-48, C16, W-13)*
- **RN-OBR-32** W-13: **una columna por presupuesto** (principal y adicionales, con su N.º y revisión) y una columna **"Total de la obra"** = suma por rubro de todos los presupuestos; fila **Total** por columna. Al sumar un adicional aparece una columna más. *(W-13, cap. 9 "Lo presupuestado de la obra = la suma de todos sus presupuestos, rubro por rubro")*
- **RN-OBR-33** Lo carga quien tenga **Presupuesto por rubro = editar** (inicial: administración y compras). Se carga una vez al darlo de alta y se puede corregir después; cada corrección queda en el historial. *(cap. 3 "Lo carga administración, una sola vez", F-48; DU-09)*
- **RN-OBR-34** Una obra **sin presupuesto cargado** existe igual y se le podrán cargar gastos (semana 3): "Sin presupuesto cargado, igual se cargan gastos y se ve lo gastado". *(cap. 4.4)*
- **RN-OBR-35** FIJOS y HORAS LIQUIDADAS se **presupuestan** como cualquier rubro; lo que cambia (monto total por período, sin detalle por empleado) es cómo se **imputa** el gasto, en semana 3. EPP **no** se presupuesta: se imputa como rubro y en el resumen suma en INSUMOS. *(cap. 9, C17, C18)*
- **RN-OBR-36** **Monto de la obra**: cada presupuesto puede tener su monto (lo que se cobra, sin IVA); el monto de la obra es la suma. Lo **ven** y **cargan** solo quienes tienen **Monto de obra y ganancia** ≥ ver / = editar; para el resto la API **no incluye** el campo (ni siquiera vacío). Dónde se carga en pantalla: DU-19 (propuesta: una fila "Monto del presupuesto" por presupuesto en W-13). *(cap. 3, F-54, C19)*
- **RN-OBR-37** Si una obra mezcla presupuestos por rubro y solo total, la comparación de la rendición (semana 3) se resuelve según DU-08 (fila "Sin desglose por rubro"); en W-13 la columna "solo total" muestra el total en la fila Total y "—" en los rubros.

## 6. Estados y transiciones

| Estado | Cuándo | Quién lo cambia | Qué pasa |
|---|---|---|---|
| **Confirmada** (`confirmada`) | Al crear la obra | — (inicial) | Ya se le pueden cargar pedidos. Aparece en "Confirmadas". |
| **En ejecución** (`en_ejecucion`) | Arranca el trabajo | Responsable de la obra · `estado_obra = editar` | Aparece en "En ejecución" y en Rendiciones en curso (W-12, semana 3). |
| **Terminada** (`terminada`) | Se terminó | Responsable de la obra · `estado_obra = editar` | Antes de confirmar, la ventana muestra los **pedidos abiertos** con su propietario y estado y los accesos "completar · reasignar" (W-29/W-31, semana 2). **No bloquea** (DU-12): si hay pedidos abiertos, el botón "Pasar a Terminada" envía la confirmación explícita. Se guarda quién y cuándo. Sale de las activas y queda consultable. |

Flujo de W-22 (contrato con Pedidos):

1. El front abre la ventana y pide `GET /api/obras/{id}/pedidos-abiertos`.
2. Muestra "Esta obra tiene N pedidos abiertos" + tabla **Tarea · Propietario · Estado · completar · reasignar**, o nada si N = 0.
3. "Pasar a Terminada" → `POST /api/obras/{id}/estado {estado: "terminada", confirmo_pedidos_abiertos: <ids vistos>}`.
4. El backend vuelve a consultar: si hay pedidos abiertos que no estaban en la lista confirmada (alguien cargó uno mientras tanto), responde **409** con la lista actualizada y no cambia el estado.

En semana 1 `PedidosDeObra` devuelve siempre `[]`, así que el paso 2 no muestra tabla y el 4 nunca da 409.

## 7. Permisos

| Acción | Función (fila) | Nivel | Alcance "propias" = |
|---|---|---|---|
| Ver listado, ficha, Documentos y contactos, historial | `obras` | ver | obras donde soy responsable |
| Alta de obra | `obras` | editar | (con `propias`, solo puede ponerse a sí mismo como responsable) |
| Editar datos, contactos, fechas, certificados, carpeta de Drive | `obras` | editar | obras donde soy responsable |
| Agregar / editar presupuesto adicional (N.º, revisión, link, OC, descripción) | `obras` | editar | ídem |
| Cambiar estado | responsable de esa obra **o** `estado_obra` | editar | — |
| Ver W-13 y los montos por rubro | `presupuesto_rubro` | ver | ídem |
| Cargar / corregir montos por rubro o total | `presupuesto_rubro` | editar | ídem |
| Ver monto de la obra (y ganancia en semana 3) | `monto_obra` | ver | ídem |
| Cargar monto de cada presupuesto | `monto_obra` | editar | ídem |
| Ver "% gastado" y la pestaña Rendición | `rendicion` | ver | ídem |
| Ver la pestaña Pedidos y la fila "Servicios generales" | `pedidos` | ver | (semana 2) |

Configuración inicial (de `identidad.md` §6.3): Obras = Ver para todos y Editar para Gerencia de
Operaciones, Jefatura de Proyectos, Ingeniería de proyectos, Gerencia y Jefatura de Administración;
Estado de obra = Editar para Gerencia y Jefatura de Administración (+ responsable); Presupuesto por
rubro = Editar administración y compras, Ver operaciones (Ingeniería solo sus obras, DU-A04);
Monto de obra = Ver gerencias y Dirección, Editar administración.

La ficha recibe `puede: {editar, cambiar_estado, ver_montos, cargar_montos, ver_monto_obra, cargar_monto_obra, ver_rendicion, ver_pedidos}` para mostrar u ocultar botones; la API vuelve a verificar todo.

## 8. Avisos

El módulo Obras **no genera avisos** en la Etapa 1 (el documento no los define: los avisos son
del pedido, cap. 7). Al pasar a Terminada no se avisa a nadie.

## 9. Casos borde (datos ficticios)

| # | Caso | Resultado esperado |
|---|---|---|
| 1 | Alta sin OC: `2-131-2026`, "Piso industrial nave 3", Planta Ejemplo SA / Nave 3, responsable Laura, link a la oferta, dos contactos | 201, estado Confirmada, documento de aprobación vacío. Aparece en "Confirmadas". |
| 2 | Alta con `2-131-2026` cuando ya existe en otra obra terminada | 422 RN-OBR-12 con el nombre de la obra. |
| 3 | Adicional `2-131-2026 a1` | Se guarda `2-131-2026 A1`; W-13 suma una columna; el listado dice "REV01 + 1 adicional". |
| 4 | Adicional con número propio `2-140-2026` en la obra de `2-131-2026` | Se acepta (no repetido). Al buscar "2-140" aparece la obra de 2-131. |
| 5 | Se escribe `2-31-2026` y existe `2-031-2026` | 422 RN-OBR-12. |
| 6 | Revisión `rev 2` | Se guarda `REV02`; historial "Revisión: REV01 → REV02". |
| 7 | Fechas previstas inicio 20/11/2026 y fin 10/11/2026 | 422 "La fecha de fin no puede ser anterior a la de inicio." |
| 8 | Laura (Ingeniería) pasa a En ejecución una obra de la que es responsable; Hernán (Jefatura de Proyectos, Estado de obra = Ver) intenta lo mismo en esa obra; después Diego (Jefatura de Administración) | Laura: 200. Hernán: 403. Diego: 200. |
| 9 | Pasar a Terminada una obra Confirmada | 422 "De Confirmada se pasa primero a En ejecución." (DU-07). |
| 10 | Semana 2: la ventana muestra 3 pedidos abiertos; mientras, Carla carga un cuarto; se confirma con los 3 ids | 409 con los 4; el estado no cambia. |
| 11 | Carla (Compras, Obras = Ver) intenta editar la obra | 403; en la ficha no ve "Editar" ni "+ agregar presupuesto adicional". |
| 12 | Laura agrega el adicional A1 | Ve solo "Guardar"; el adicional queda "sin cargar". Diego lo abre en W-13 y carga sus montos. |
| 13 | Diego carga el principal por rubro (MATERIALES 10.900.000, INSUMOS 1.500.000, …) y después lo pasa a "solo total" 25.650.000 | Se reemplazan los 12 montos por el total, con confirmación; queda en el historial (visible solo con Presupuesto por rubro ≥ ver). |
| 14 | Monto negativo `-150000` en LOGISTICA | 422 "El monto no puede ser negativo." |
| 15 | Monto `1.500.000,50` escrito así en el front | El front lo convierte a `"1500000.50"`; el backend recibe string decimal (ADR-008). Un número con más de 2 decimales: 422. |
| 16 | Laura (sin Monto de obra) pide la ficha | El JSON de presupuestos **no tiene** la clave `monto_obra`; el historial no muestra las líneas de monto. |
| 17 | Búsqueda "planta ejemplo" | Trae las obras de Planta Ejemplo SA en las tres pestañas, con sus contadores. Búsqueda "nave" trae la de trabajo "Piso industrial nave 3" (y, si se aprueba DU-11, también las de planta "Nave 3"). |
| 18 | El responsable de una obra queda inactivo | La obra sigue con él ("Hernán (inactivo)"); al editarla hay que elegir uno activo si se cambia el campo, no antes. |
| 19 | Doble clic en "Guardar obra" con la misma `Idempotency-Key` | Se crea una sola obra; la segunda respuesta es la misma (201). |
| 20 | Link "drive.google.com/..." sin `https://` | El front completa `https://`; el backend exige `http(s)://`. |

## 10. Contrato de API sugerido

Envelope `{ data, message, errors }`; listados con `meta`/`links`. Importes como **string decimal**.
Fechas `YYYY-MM-DD`. POST que crean con `Idempotency-Key`. Códigos: 401, 403, 404, 409 (estado
con pedidos nuevos), 422.

### 10.1 Obras

| Método y ruta | Payload | Respuesta `data` | Errores |
|---|---|---|---|
| `GET /api/obras?estado=en_ejecucion\|confirmada\|terminada&buscar=&responsable_id=&page=` | — | `[ObraFila]`; `meta: {contadores: {en_ejecucion, confirmada, terminada}, pedidos_sin_obra_abiertos: int\|null, …paginación}` | 403 |
| `GET /api/obras/clientes?buscar=` | — | `["Planta Ejemplo SA", "Terminal Sur SA"]` (distintos, máx. 10) | 403 |
| `POST /api/obras` | `ObraAlta` | `ObraFicha` (201) | 403 · 422 |
| `GET /api/obras/{id}` | — | `ObraFicha` | 403 · 404 |
| `PATCH /api/obras/{id}` | campos de `ObraAlta` sin `presupuesto` ni `adicionales` (+ `contactos`) | `ObraFicha` | 403 · 404 · 422 |
| `GET /api/obras/{id}/historial?page=` | — | `[{cuando, quien: {id, nombre}, que: "N.º de OC: (vacío) → 4500-000123"}]` paginado | 403 · 404 |
| `GET /api/obras/{id}/pedidos-abiertos` | — | `[{id, tarea, propietario: {id, nombre}\|null, sector: {id, nombre}, estado}]` | 403 · 404 |
| `POST /api/obras/{id}/estado` | `{estado, confirmo_pedidos_abiertos: [ids]}` | `ObraFicha` | 403 · 404 · 409 `{errors: {pedidos_abiertos: [...]}}` · 422 transición no permitida |

```json
// ObraAlta
{
  "nombre_trabajo": "Piso industrial nave 3",
  "cliente": "Planta Ejemplo SA", "planta": "Nave 3",
  "responsable_id": 7,
  "fecha_inicio_prevista": null, "fecha_fin_prevista": null,
  "carpeta_drive_url": null,
  "presupuesto": {
    "numero": "2-131-2026", "revision": "REV01",
    "presupuesto_url": "https://drive.example/oferta-2-131-2026",
    "aprobacion": { "tipo": "oc", "numero": "4500-000123", "url": null }
  },
  "adicionales": [],
  "contactos": {
    "solicitante": { "nombre": "Jefe de mantenimiento (ejemplo)", "email": "mantenimiento@planta-ejemplo.com", "telefono": "291 000-0000" },
    "comprador":   { "nombre": "Analista de compras (ejemplo)",  "email": "compras@planta-ejemplo.com",      "telefono": "291 000-0001" }
  }
}
// ObraFila
{
  "id": 12, "numero_presupuesto": "2-131-2026", "revision": "REV01", "adicionales": 1,
  "nombre_trabajo": "Piso industrial nave 3", "cliente": "Planta Ejemplo SA", "planta": "Nave 3",
  "responsable": { "id": 7, "nombre": "Laura", "activo": true },
  "estado": "en_ejecucion", "pedidos_abiertos": null,
  "porcentaje_gastado": null, "pasado_de_presupuesto": null
}
// ObraFicha = ObraFila + 
{
  "fecha_inicio_prevista": null, "fecha_fin_prevista": null, "carpeta_drive_url": null,
  "terminada_at": null, "terminada_por": null, "created_at": "...", "created_by": { "id": 7, "nombre": "Laura" },
  "presupuestos": [ {
    "id": 30, "numero": "2-131-2026", "revision": "REV01", "es_principal": true, "descripcion": null,
    "presupuesto_url": "https://…", "aprobacion": { "tipo": "oc", "numero": "4500-000123", "url": null },
    "modo_carga": "por_rubro", "created_at": "2026-10-01", "created_by": { "id": 7, "nombre": "Laura" }
    /* "monto_obra": "36500000.00" solo con monto_obra ≥ ver */
  } ],
  "contactos": { "solicitante": { … }, "comprador": { … } },
  "certificados": [ { "id": 3, "nombre": "Certificado N.º 1", "fecha": "2026-09-30", "url": "https://…" } ],
  "puede": { "editar": true, "cambiar_estado": true, "ver_montos": false, "cargar_montos": false,
             "ver_monto_obra": false, "cargar_monto_obra": false, "ver_rendicion": true, "ver_pedidos": true }
}
```

### 10.2 Presupuestos, documentos y certificados

| Método y ruta | Payload | Respuesta `data` | Errores |
|---|---|---|---|
| `POST /api/obras/{id}/presupuestos` | `{numero, revision?, presupuesto_url?, descripcion?, aprobacion?}` | `Presupuesto` (201) | 403 · 404 · 422 |
| `GET /api/obras/{id}/presupuestos/sugerir-numero` | — | `{numero: "2-131-2026 A2"}` | 403 |
| `PATCH /api/presupuestos/{id}` | `{numero?, revision?, presupuesto_url?, descripcion?, aprobacion?}` | `Presupuesto` | 403 · 404 · 422 |
| `DELETE /api/presupuestos/{id}` | — | `null` | 403 · 404 · 422 (es el principal o ya tiene montos, RN-OBR-24) |
| `POST /api/obras/{id}/certificados` | `{nombre, fecha, url}` | `Certificado` (201) | 403 · 422 |
| `PATCH /api/certificados/{id}` · `DELETE /api/certificados/{id}` | `{nombre?, fecha?, url?}` | `Certificado` · `null` | 403 · 404 · 422 |

### 10.3 Presupuesto por rubro y monto de la obra

| Método y ruta | Payload | Respuesta `data` | Errores |
|---|---|---|---|
| `GET /api/obras/{id}/presupuesto-por-rubro` | — | ver abajo | 403 · 404 |
| `PUT /api/presupuestos/{id}/montos` | `{modo: "por_rubro", montos: {"materiales": "10900000.00", "insumos": "1500000.00", "subcontratos": null, …}}` o `{modo: "solo_total", total: "25650000.00"}` | igual que el GET de la obra | 403 · 404 · 422 (rubro no presupuestable, negativo, más de 2 decimales) |
| `PUT /api/presupuestos/{id}/monto-obra` | `{monto_obra: "36500000.00" \| null}` | `Presupuesto` | 403 (sin `monto_obra = editar`) · 422 |

```json
// GET /api/obras/12/presupuesto-por-rubro
{
  "rubros": [ { "clave": "materiales", "nombre": "MATERIALES" }, … 12 … ],
  "presupuestos": [
    { "id": 30, "numero": "2-131-2026", "revision": "REV01", "es_principal": true, "modo_carga": "por_rubro",
      "montos": { "materiales": "10900000.00", "insumos": "1500000.00", "subcontratos": "4500000.00", … },
      "total": "25650000.00" },
    { "id": 31, "numero": "2-131-2026 A1", "revision": null, "es_principal": false, "modo_carga": "por_rubro",
      "montos": { "materiales": "1500000.00", "subcontratos": null, … }, "total": "3250000.00" }
  ],
  "total_obra": { "por_rubro": { "materiales": "12400000.00", … }, "total": "28900000.00" }
}
```

### 10.4 Contratos con otros módulos (puertos)

Obras **define** los puertos y los otros módulos los **implementan** (así Obras no depende de
Pedidos ni de Rendición). En semana 1 se registran implementaciones nulas.

| Puerto (`App\Modules\Obras\Application\Ports`) | Métodos | Implementa | Semana 1 |
|---|---|---|---|
| `PedidosDeObra` | `abiertosDe(ObraId): list<PedidoAbiertoResumen>` · `contarAbiertosPorObra(list<ObraId>): array<int,?int>` (una consulta para toda la página) · `contarAbiertosSinObra(): ?int` | Pedidos (semana 2) | `PedidosDeObraNulo`: `[]` y `null` (= "todavía no hay módulo Pedidos") |
| `AvanceGastoDeObra` | `porcentajesGastados(list<ObraId>): array<int, ?Porcentaje>` · `pasadas(list<ObraId>): array<int,bool>` | Rendicion (semana 3) | `AvanceGastoDeObraNulo`: todo `null` |

`PedidoAbiertoResumen` = `{id, tarea, propietarioId|null, propietarioNombre|null, sectorId, sectorNombre, estado}`.
"Abierto" = `no_iniciada`, `en_curso` o `bloqueada`.

Y **expone** a los demás `App\Modules\Obras\Contracts\ObrasConsulta`:
`existe(ObraId)`, `resumen(ObraId): {id, numero, nombreTrabajo, cliente, estado, responsableId}`,
`esResponsable(UserId, ObraId): bool` (para el alcance "propias" de todas las funciones de obra),
`obrasDondeEsResponsable(UserId): list<ObraId>`, `presupuestadoPorRubro(ObraId)` (para la
rendición de semana 3: suma de todos los presupuestos, con EPP fuera de la lista porque no se
presupuesta). Eventos: `ObraCreada`, `ObraEstadoCambiado {obraId, de, a}`, `PresupuestoMontosCambiados {obraId}` (invalida el cache de la rendición, ADR-007).

## 11. Trazabilidad F-XX → RN → test

| F-XX | Reglas | Tests (PHPUnit, `tests/Feature/Obras/…` y `tests/Unit/Obras/…`) |
|---|---|---|
| F-15 | RN-OBR-01…04, 06, 07 | `test_listado_separa_en_ejecucion_confirmadas_y_terminadas_con_contadores` · `test_fila_muestra_revision_y_cantidad_de_adicionales` · `test_pedidos_abiertos_salen_del_puerto_en_una_consulta` · `test_porcentaje_gastado_es_nulo_sin_rendicion` · `test_porcentaje_gastado_se_oculta_sin_permiso_de_rendicion` · `test_filtro_por_responsable` · `test_en_ejecucion_ordena_por_porcentaje_gastado` |
| F-03 | RN-OBR-05 | `test_busca_por_numero_de_cualquier_presupuesto_de_la_obra` · `test_busca_numero_ignorando_separadores` · `test_busca_por_cliente_planta_o_trabajo_sin_tildes` |
| F-16 | RN-OBR-16…19, §4.1 | `test_obra_nace_confirmada` · `test_alta_sin_oc_es_valida` · `test_alta_exige_numero_trabajo_cliente_responsable_link_y_contactos` · `test_alta_crea_obra_presupuesto_y_contactos_en_una_transaccion` · `test_aprobacion_con_numero_sin_tipo_se_toma_como_oc` · `test_clientes_sugeridos_son_los_ya_usados` · `test_alta_con_idempotency_key_no_duplica` · `test_solo_obras_editar_da_de_alta` |
| F-17 | §7 | `test_ficha_informa_que_puede_hacer_el_usuario` · `test_ficha_no_incluye_monto_obra_sin_permiso` |
| F-18 | RN-OBR-10…15, 20, 21 | Unit `NumeroPresupuestoTest`: `test_normaliza_separadores`, `test_completa_numero_a_tres_digitos`, `test_normaliza_sufijo_de_adicional`, `test_separa_revision_escrita_en_el_numero`, `test_rechaza_anio_de_dos_digitos`, `test_rechaza_dos_presupuestos_juntos`, `test_rechaza_texto_sin_numero`; Unit `RevisionTest`: `test_normaliza_variantes_a_rev_dos_digitos`; Feature: `test_numero_de_presupuesto_es_unico_en_todas_las_obras` · `test_unicidad_ignora_la_revision` · `test_obra_tiene_exactamente_un_principal` · `test_sugiere_siguiente_sufijo_de_adicional` · `test_adicional_puede_tener_numero_propio` |
| F-19 | RN-OBR-16, 23 | `test_documento_de_aprobacion_se_agrega_despues` · `test_certificado_exige_nombre_fecha_y_link` · `test_quitar_certificado_queda_en_historial` · `test_links_exigen_http_o_https` |
| F-20 / F-14 | RN-OBR-22, 26…28 | `test_editar_registra_antes_y_despues` · `test_editar_no_cambia_el_estado` · `test_link_cambiado_se_informa_sin_url` · `test_historial_oculta_montos_sin_permiso` · `test_historial_oculta_monto_obra_sin_permiso` · `test_principal_no_se_convierte_en_adicional` |
| F-21 | RN-OBR-29, 30, §6 | `test_confirmada_pasa_a_en_ejecucion` · `test_en_ejecucion_pasa_a_terminada` · `test_confirmada_no_salta_a_terminada` · `test_responsable_cambia_estado_de_su_obra` · `test_estado_obra_editar_cambia_cualquier_obra` · `test_sin_permiso_ni_responsable_no_cambia_estado` · `test_terminar_con_pedidos_nuevos_no_confirmados_devuelve_409` · `test_terminar_guarda_quien_y_cuando` · `test_pedidos_abiertos_vacio_en_semana_1` |
| F-22 | RN-OBR-09, 24, 25 | `test_obra_terminada_se_consulta_completa` · `test_obra_terminada_se_puede_editar` · `test_adicional_sin_montos_se_puede_quitar` · `test_principal_o_adicional_con_montos_no_se_quita` |
| F-23 | §4.1 | `test_fechas_previstas_son_opcionales` · `test_fin_previsto_no_anterior_al_inicio` |
| F-48 | RN-OBR-31…35, 37, §4.6 | `test_migracion_carga_los_trece_rubros_en_orden` · `test_epp_no_se_presupuesta_y_resume_en_insumos` · `test_presupuesto_se_carga_por_rubro_o_total_no_mezclado` · `test_pasar_a_solo_total_reemplaza_los_rubros` · `test_total_de_la_obra_suma_todos_los_presupuestos_por_rubro` · `test_monto_negativo_es_rechazado` · `test_importes_viajan_como_string_decimal` · `test_solo_presupuesto_rubro_editar_carga_montos` · `test_cambio_de_montos_dispara_presupuesto_montos_cambiados` |
| F-54 (dato) | RN-OBR-36 | `test_monto_obra_solo_lo_carga_monto_obra_editar` · `test_monto_obra_se_omite_del_json_sin_permiso` |
| W-05 fila sin obra | RN-OBR-08 | `test_meta_trae_pedidos_sin_obra_solo_con_permiso_de_pedidos` |

## 12. Dudas

Ya abiertas por QA (se respetan sus propuestas): DU-05 (formato, revisión y unicidad del N.º),
DU-06 (cliente con sugerencias), DU-07 (volver un paso atrás), DU-08 (presupuestos mixtos), DU-09
(corregir el presupuesto por rubro), DU-11 (buscar por planta), DU-12 (Terminada con pedidos
abiertos no frena), DU-19 (dónde se carga el monto de la obra).
Nuevas de este módulo: DU-A12 (semana 1 sin Pedidos/Gastos: columnas y fila "Servicios generales"),
DU-A14 (obligatoriedad: tabla del cap. 6 vs. maquetas), DU-A15 (Terminada editable y con cargas
nuevas), DU-A16 (unicidad del N.º vs. modelo de datos y tope de dígitos), DU-A17 (borrar
obra/presupuesto), DU-A20 (orden de los listados), DU-A21 (ajustes al modelo de datos). Detalle en `docs/dudas.md`.

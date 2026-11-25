# Especificación funcional · Carga inicial (puesta en marcha)

> Módulo `app/Modules/Importacion` · Entrega: **semana 4** · Rol: Analista funcional · 2026-11-25
> Contrato: documento firmado v1.1, **cap. 12** (F-62 "Carga inicial: el sistema arranca con los pedidos abiertos de la
> minuta y las obras en ejecución con su gastado acumulado (un total por rubro)", quién: **"NexoSmart con
> administración"**, pantalla "—"; F-63 "Capacitación y acompañamiento del arranque: el equipo arranca usándolo, no
> estudiándolo", quién: NexoSmart, pantalla "—"), **cap. 9** (lista cerrada de rubros: "evita errores de tipeo como
> 'INSUMO' en lugar de 'INSUMOS'"), **cap. 13** (criterio de aceptación n.º 4: "El equipo deja de usar la minuta en Excel
> porque el sistema le resulta más fácil") y **cap. 14** (semana 4: "Arrancan a usarlo; la minuta y las rendiciones en
> Excel quedan de consulta").
> Material real (solo los análisis; los .xlsx no se abren ni se copian): análisis de la minuta
> (`referencias/2026-10-09-minuta/analisis-minuta.md`: 755 filas, 42 abiertas, 18 variantes de columnas, solicitante en el
> 22 %, fecha de pedido en 18 de 39 solapas, ~179 filas que son documentos), de las seis rendiciones
> (`referencias/2026-10-09-rendiciones/analisis-rendiciones.md`: hoja "General" por rubro, planilla de retiro de
> materiales con "resta llevar") y de las planillas de junio (`referencias/2026-06-18-planillas-cliente/analisis-planillas.md`).
> Decisiones que van **tal cual**: **DU-20** (tareas importadas marcadas "carga inicial", con lo que tengan; las abiertas
> sin entrega o sin propietario se listan antes del arranque para que el sector las complete; lo que es documento va a
> Documentos de la obra), **DU-21** (las obras no se cargan incompletas: planilla de faltantes por obra que completa
> administración; el gastado acumulado se mapea a la lista cerrada con una tabla de equivalencias que **revisa y aprueba
> administración**; nada se mapea solo), **DU-R09** (lo ya retirado por material entra como renglón de carga inicial con
> importe 0, en la columna "Hasta {fecha}" de W-48), **DU-P10** (solicitante y fecha de pedido vacíos solo en carga
> inicial; sin solicitante, la reabre cualquiera con Tareas = Editar), **DU-A19** (sin fusión de proveedores) y **DU-C01**
> (en todo lo que ve el usuario "pedido" se dice **"tarea"**; las claves internas siguen siendo `pedidos`).
> Reglas de los otros módulos que se respetan: `obras.md` (§4.1 obligatorios, RN-OBR-10…16, 29, 31…37), `pedidos.md`
> (§4.6, RN-PED-03, 07, 11…14, 61), `gastos.md` (§4.3 fila "Carga inicial", RN-GAS-29, 37, 40), `rendicion.md` (RN-REN-08,
> 29, 47, 50, 53), `avisos.md` (RN-AVI-15). ADR-003 (contratos entre módulos), ADR-007 (los renglones son la única fuente
> del gastado), ADR-008 (importes), ADR-010 (historial), ADR-011 (idempotencia), ADR-014 (archivo o link), ADR-015
> (primer usuario por comando).
> Lo nuevo de esta especificación va a `docs/dudas.md` como **DU-I01…DU-I09** (abiertas): cada regla que depende de una
> se escribe con la propuesta y cita "propuesta DU-INN" hasta que Juani decida.
> Personas, obras, mails, números e importes de este archivo son **ficticios** (los de las maquetas). Fechas en
> dd/mm/aaaa (DU-G13).

## 1. Propósito

Que el primer día el sistema ya tenga **lo que está vivo**: las tareas abiertas de la minuta, con su propietario y su
entrega, y las obras en ejecución con su presupuesto y **lo gastado hasta el corte, rubro por rubro**, para que desde ese
día la rendición en vivo arranque del número correcto y nadie tenga que volver al Excel para saber dónde está parado.
"El equipo arranca usándolo, no estudiándolo" (F-63): nadie carga a mano lo que ya estaba escrito.

Se hace **una sola vez**, la hace **NexoSmart con administración** (F-62): NexoSmart arma las plantillas a partir de la
minuta y las rendiciones, administración las revisa, completa lo que falta y **aprueba** las equivalencias y los números;
NexoSmart corre la carga en el servidor. No es una función que el cliente use después: por eso no tiene pantalla.

**Qué no hace:** no trae las tareas **completadas** (la minuta queda de consulta, cap. 14), no trae el **detalle** de
los gastos (solo un total por rubro, F-62), no trae horas por empleado (D-009), no carga **proveedores** (propuesta
DU-I04) ni usuarios (los carga gerencia en W-39, F-58).

## 2. Funcionalidades y pantallas cubiertas

| F-XX | Qué | Pantalla | Semana 4 |
|---|---|---|---|
| F-62 | Carga inicial: tareas abiertas de la minuta y obras en ejecución con su gastado acumulado por rubro | — (comandos en el servidor, §11) | completa |
| F-62 (efecto) | Marca "Carga inicial" en la tarea y en su recorrido | W-09 · W-42 (detalle, celular) | se agrega (RN-IMP-40) |
| F-62 (efecto) | El gastado acumulado como renglones "Carga inicial" en la rendición | W-11 · W-36 · W-37 · W-33 · W-34 (ya existen) | sin pantalla nueva (RN-REN-08, RN-GAS-29) |
| F-62 (efecto) | Lo retirado hasta el corte en el control por cantidad | W-48 | columna "Hasta {fecha} · Carga inicial" (RN-IMP-30) |
| F-62 (efecto) | Certificados y documentos de la minuta en la obra | W-20 (Documentos de la obra, ya existe) | sin pantalla nueva |
| F-63 | Capacitación y acompañamiento | — (servicio) | acá solo lo que el sistema necesita para arrancar (§12) y la guía por sector como documentación |

## 3. Glosario (palabras del cliente → código)

| Palabra | En el código | Qué es |
|---|---|---|
| Carga inicial | `pedidos.carga_inicial = true` · `gastos.origen = saldo_inicial` | Lo que entró por esta importación (DU-20, DU-21). |
| Lote | `tipo` de corrida: `obras` · `tareas` | Cada una de las dos cargas, en ese orden (RN-IMP-02). |
| Plantilla | archivo `.xlsx` con hojas y columnas fijas (§5) | Lo que prepara NexoSmart y completa administración. |
| Ensayo | corrida sin `--confirmar` | Valida todo, arma el informe y **no guarda nada** (RN-IMP-03). |
| Confirmar | corrida con `--confirmar --aprobo=…` | Guarda de verdad lo que el ensayo aprobado dijo (RN-IMP-04). |
| Informe | `.xlsx` que devuelve cada corrida (§5.8) | Resumen, faltantes, avisos y control de números, para administración. |
| Faltante | fila del informe con "Bloquea: Sí" | Un dato sin el cual esa obra o esa tarea **no entra** (DU-21, DU-20). |
| Aviso (del informe) | fila del informe con "Bloquea: No" | Algo que entra igual pero conviene mirar (obra sin presupuesto por rubro). No es un aviso de la campana. |
| Fecha de corte | `fecha_corte` de cada obra | Hasta qué día (inclusive) está el gastado acumulado y el retirado (propuesta DU-I02). |
| Equivalencia de rubro | hoja "Rubros" | "INSUMO" → INSUMOS, aprobada por administración (DU-21). |
| Huella | SHA-256 del archivo | Identifica el archivo ensayado y aprobado (RN-IMP-05). |
| Corrida | `importacion_corridas` | Registro de cada ensayo o carga: quién aprobó, cuándo, cuántas filas (§11.3). |
| Apertura | — | El día en que se le pasa el link al equipo y la minuta queda de consulta (§12). |

## 4. Cómo se hace la carga: comandos, no pantalla

### 4.1 Las dos opciones

| | (a) Comandos que corre NexoSmart en el servidor, con plantillas que prepara administración, ensayo e informe | (b) Pantalla de importación dentro del sistema |
|---|---|---|
| Contrato | F-62 dice "NexoSmart con administración" y pantalla "—" | Habría que inventar una W-XX que el documento no tiene |
| Veces que se usa | una, más los ensayos | una; la pantalla queda sin uso para siempre (o hay que esconderla) |
| Revisión y aprobación de administración | el informe de ensayo va a administración; aprueban equivalencias en la plantilla y el informe por mail; NexoSmart confirma ese mismo archivo (huella) | habría que construir estados de "borrador / aprobado", permisos nuevos y una pantalla de equivalencias |
| Archivos reales | entran a una carpeta privada del servidor y se borran a los 30 días; nunca pasan por el repo ni por el navegador | suben por la API: pasan por el límite de 20 MB, el log de errores con el `input` (SEG-02) y los adjuntos |
| Costo en la semana 4 | contratos de carga en cada módulo + lector + informe | todo lo de (a) + pantalla, permisos, estados y pruebas E2E |

**Recomendación: (a).** Es lo que firmaron ("NexoSmart con administración", sin pantalla), es lo más chico que cumple, y
deja a administración haciendo lo que sabe hacer —completar una planilla y aprobar números— sin aprender una pantalla
que no van a volver a ver. La revisión no se pierde: **nada se guarda sin un ensayo aprobado del mismo archivo**.

### 4.2 El circuito

```
NexoSmart arma la primera versión de las plantillas (desde la minuta y las rendiciones, en su equipo, sin versionar)
   → administración la revisa y completa (Rubros, Personas, faltantes de obras; cada sector, sus tareas)
   → NexoSmart corre el ENSAYO en el servidor → informe (.xlsx) a administración por la carpeta de Drive del proyecto
   → se repite hasta que el informe no tiene faltantes (o administración acepta dejar afuera lo que falta)
   → administración aprueba el informe por mail ("Apruebo el informe {huella corta}")
   → NexoSmart hace el respaldo de la base y CONFIRMA ese mismo archivo → informe final
   → QC compara conteos contra el origen → apertura
```

## 5. Plantillas de entrada (columnas exactas)

Dos archivos `.xlsx`, uno por lote. Los genera vacíos `importacion:plantilla` (§11.1) con los encabezados, las listas
desplegables (rubros, sectores, estados) y una hoja **"Cómo completar"** con lo de esta sección en palabras del cliente.
Las hojas y columnas se leen **por su nombre exacto** (el orden no importa; columnas de más se ignoran). Fila 1 =
encabezados; desde la fila 2, datos; filas totalmente vacías se ignoran.

**Formatos comunes:** fechas como fecha de Excel o texto `dd/mm/aaaa`; importes como número de Excel o texto con punto de
miles y coma decimal ("1.250.000,50"), **hasta 2 decimales**, sin signo `$`; cantidades hasta 3 decimales; links con
`http://` o `https://`; listas sin distinguir mayúsculas. "Sí/No" acepta "si", "sí", "no".

### 5.1 Archivo `obras` · hoja **Obras** (una fila por obra)

| Columna | Obligatoria | Formato | Va a | Fuente |
|---|---|---|---|---|
| N.º de presupuesto | **Sí** | `P-NNN-AAAA` (tolerante, RN-OBR-11) | presupuesto principal → `numero`; **clave de la obra** en todo el archivo | obras.md §4.1 |
| Revisión | **Sí** | `REV01` (tolerante, RN-OBR-14) | principal → `revision` | obras.md §4.1 |
| Trabajo | **Sí** | texto ≤ 200 | `nombre_trabajo` | obras.md §4.1 |
| Cliente | **Sí** | texto ≤ 150 | `cliente` | obras.md §4.1 |
| Planta | No | texto ≤ 150 | `planta` | obras.md §4.1 |
| Responsable de obra (mail) | **Sí** | mail de un usuario activo del sistema | `responsable_id` | obras.md §4.1 |
| Estado | **Sí** | En ejecución · Confirmada | estado final de la obra (RN-IMP-21; Confirmada: propuesta DU-I07) | F-62 |
| Inicio previsto · Fin previsto | No | fecha · fecha (fin ≥ inicio) | `fecha_inicio_prevista`, `fecha_fin_prevista` | obras.md §4.1 |
| Carpeta de Drive | No | link | `carpeta_drive_url` | obras.md §4.1 |
| Oferta técnica (link) | **Sí** | link | adjunto `presupuesto` del principal (ADR-014; el archivo se puede subir después en la ficha) | obras.md §4.1 |
| Solicitante en planta · nombre / mail / teléfono | **Sí** las tres | texto ≤ 120 · mail · texto ≤ 50 | contacto `solicitante` | obras.md §4.4 |
| Comprador · nombre / mail / teléfono | **Sí** las tres | ídem | contacto `comprador` | obras.md §4.4 |
| Fecha de corte | **Sí** si la obra tiene filas en Gastado o retirado en Materiales | fecha, no posterior a hoy | fecha de los renglones de carga inicial (RN-IMP-23) | propuesta DU-I02 |
| Total gastado según su planilla | No (control) | importe | se compara con la suma de la hoja Gastado (RN-IMP-24) | su hoja "General" |

### 5.2 Archivo `obras` · hoja **Presupuestos** (una fila por presupuesto; el principal solo si trae montos u OC)

| Columna | Obligatoria | Formato | Va a |
|---|---|---|---|
| Obra | **Sí** | N.º de presupuesto principal de la hoja Obras | a qué obra pertenece |
| N.º de presupuesto | **Sí** | `P-NNN-AAAA` o con sufijo `A1` (RN-OBR-10, 15); igual a "Obra" para el principal | `presupuestos.numero` (si no es el principal, es un **adicional**) |
| Revisión | No (adicional) | `REV01` | `revision` |
| Descripción | No | texto ≤ 200 | `descripcion` |
| Oferta técnica del adicional (link) | No | link | adjunto `presupuesto` |
| Documento de aprobación | No | Orden de compra · Orden de trabajo · Contrato · Mail de aprobación | `aprobacion_tipo` (RN-OBR-16) |
| N.º de OC/OT | No | texto ≤ 60 | `aprobacion_numero` |
| Link al documento de aprobación | No | link | adjunto `aprobacion` |
| Cómo se carga | No | Por rubro · Solo total · (vacío = sin cargar) | `modo_carga` (RN-OBR-31) |
| MATERIALES · INSUMOS · SUBCONTRATOS · LOGISTICA · AMORTIZACION · MANTENIMIENTO · OBRADORES/BAÑOS · SERVICIOS GENERALES · MAQUINAS/HERRAMIENTAS · VIATICOS · FIJOS · HORAS LIQUIDADAS | Si "Por rubro" (vacío = 0) | importe ≥ 0 | `presupuesto_montos` por rubro (12 presupuestables; EPP no, RN-OBR-35) |
| Total | Si "Solo total" | importe ≥ 0 | `presupuesto_montos` sin rubro |
| Monto del presupuesto | No | importe ≥ 0 | `monto_obra` (RN-OBR-36) |

Los nombres de las 12 columnas de rubros son los del sistema (los de su hoja "General"); acá no hay equivalencias:
NexoSmart los pasa a la columna que corresponde al armar la plantilla.

### 5.3 Archivo `obras` · hoja **Gastado** (una fila por obra y rubro, como su hoja "General")

| Columna | Obligatoria | Formato | Va a |
|---|---|---|---|
| Obra | **Sí** | N.º de presupuesto principal | obra |
| Rubro como figura en su planilla | **Sí** | texto, tal cual ("INSUMO", "LOGÍSTICA", "EPP") | se traduce con RN-IMP-16 |
| Gastado hasta el corte | **Sí** | importe ≥ 0, 2 decimales | renglón de carga inicial (RN-IMP-23) |

### 5.4 Archivo `obras` · hoja **Rubros** (tabla de equivalencias, DU-21)

| Columna | Obligatoria | Formato | Qué es |
|---|---|---|---|
| Como figura en su planilla | **Sí** | texto ("INSUMO") | lo escrito en la hoja Gastado; una fila por forma distinta |
| Rubro del sistema | **Sí** | uno de los 13 (lista desplegable): MATERIALES · INSUMOS · EPP · SUBCONTRATOS · LOGISTICA · AMORTIZACION · MANTENIMIENTO · OBRADORES/BAÑOS · SERVICIOS GENERALES · MAQUINAS/HERRAMIENTAS · VIATICOS · FIJOS · HORAS LIQUIDADAS | a qué rubro va |
| Aprobado | **Sí** | Sí · No | lo marca administración; solo "Sí" se usa (RN-IMP-16) |
| Aprobó | **Sí** si Aprobado = Sí | nombre de quien aprobó | queda en el historial del gasto (RN-IMP-17) |

### 5.5 Archivo `obras` · hoja **Materiales** (lista de W-48 y retirado hasta el corte, DU-R09; propuesta DU-I06)

| Columna | Obligatoria | Formato | Va a |
|---|---|---|---|
| Obra | **Sí** | N.º de presupuesto principal | obra |
| Material | **Sí** | texto ≤ 150, no repetido en la obra (rendicion.md §4.2) | `materiales.nombre` |
| Unidad | **Sí** | texto ≤ 20 ("bolsa", "m³") | `unidad` |
| Cantidad presupuestada | **Sí** | cantidad ≥ 0, 3 decimales | `cantidad_presupuestada` |
| Precio unitario presupuestado | No | importe ≥ 0 | `precio_presupuestado` |
| Presupuesto | **Sí** si la obra tiene más de un presupuesto | N.º del presupuesto | `presupuesto_id` |
| Retirado hasta el corte | No | cantidad ≥ 0, 3 decimales, en la unidad del material | renglón de carga inicial con importe 0 (RN-IMP-29) |

### 5.6 Archivo `tareas` · hoja **Tareas** (una fila por fila de la minuta que se trae)

| Columna | Obligatoria | Formato | Va a |
|---|---|---|---|
| Clave | **Sí**, única en el archivo | texto ≤ 120: solapa + fila de la minuta ("2-131-2026 · fila 12", "Servicios generales · fila 3") | clave de idempotencia (RN-IMP-08) |
| Qué es | **Sí** | Tarea · Documento | tarea (§6.6) o documento de la obra (§6.7) |
| Obra | **Sí** | N.º de presupuesto principal · "Sin obra" | `obra_id` (null = sin obra) |
| Tarea | **Sí** | texto ≤ 2000 | `tarea` (en Documento: nombre del documento, ≤ 120) |
| Con quién | No | texto ≤ 150 (la columna PROVEEDOR de la minuta) | `con_quien` |
| Prioridad | No | Urgente · Prioritario · Normal · P3 · (vacía) | RN-IMP-35 |
| Propietario | **Sí** en Tarea | como figura en la minuta (nombre o mail) · "(en el sector)" | `propietario_id` (RN-IMP-15) |
| Sector | **Sí** si Propietario = "(en el sector)" | un sector activo (lista) | `sector_id` |
| Solicitante | No | como figura en la minuta | `solicitante_id` o vacío (DU-20) |
| Estado | **Sí** en Tarea | No iniciada · En curso · Bloqueada · Completada | `estado` (Completada no se importa, RN-IMP-32) |
| Fecha de pedido | No | fecha, no posterior a hoy | `fecha_pedido` o vacía (DU-20) |
| Fecha de entrega | **Sí** en Tarea | fecha | `fecha_entrega` |
| Inicio del período · Fin del período | No | las dos o ninguna; fin ≥ inicio | `periodo_inicio`, `periodo_fin` (RN-PED-05) |
| Links | No (en Documento, **Sí**) | hasta 10, uno por línea dentro de la celda | adjuntos `pedido` o `documento_obra`, tipo link |
| Notas | **Sí** si Bloqueada | texto ≤ 5000 (≤ 2000 si Bloqueada) | `motivo_bloqueo` (Bloqueada) o `aclaraciones` (las demás) |
| Tipo de documento | **Sí** en Documento | Certificado · Otro | `obra_documentos.tipo` |
| Fecha del documento | **Sí** en Certificado | fecha | `obra_documentos.fecha` |

### 5.7 Archivo `tareas` · hoja **Personas** (cómo figura cada uno en la minuta)

| Columna | Obligatoria | Formato | Qué es |
|---|---|---|---|
| Como figura en la minuta | **Sí** | texto ("Cari", "Diego M.", "diego@ejemplo.com") | lo escrito en Propietario o Solicitante |
| Mail del usuario en el sistema | **Sí** | mail de un usuario del sistema | a quién corresponde |

### 5.8 Informe que se le devuelve a administración (lo genera cada corrida)

Archivo `informe-{obras|tareas}-{ensayo|confirmada}-{AAAAMMDD-HHMM}.xlsx` en la carpeta de la importación (RN-IMP-12).

| Hoja | Columnas | Para qué |
|---|---|---|
| **Resumen** | Huella (12 primeros caracteres) · Lote · Modo (Ensayo / Confirmada) · Fecha y hora · Aprobó · y por obra (lote obras): N.º de presupuesto · Trabajo · Estado · ¿Entra? (Sí / Ya estaba / No: N faltantes) · Presupuestado total · Gastado a cargar · Total según su planilla · Diferencia · Materiales · Con retirado · Fecha de corte; (lote tareas): Filas · Tareas que entran · Completadas (no se importan) · Documentos que entran · Ya importadas · Con faltantes | la foto de qué entra y qué no |
| **Faltantes** | Hoja · Fila · Obra o Clave · Columna · Qué falta (texto de §8) · Lo completa (Administración · el sector {nombre}) · Bloquea (Sí) | lo que hay que completar en la plantilla para que entre |
| **Para completar por sector** | Sector · Clave · Obra · Tarea · Qué falta | una pestaña lista para pasarle a cada sector (DU-20) |
| **Avisos** | Hoja · Fila · Obra o Clave · Qué pasa · Bloquea (No) | lo que entra igual pero conviene mirar |
| **Rubros por revisar** | Como figura · Obras donde aparece · Importe total · Rubro sugerido | formas sin equivalencia aprobada; la sugerencia es solo una ayuda, **no se aplica** (DU-21) |
| **Personas por revisar** | Como figura · Filas · Usuario sugerido | ídem para nombres de la minuta |
| **Control por rubro** | Obra · Rubro del sistema · Importe a cargar · De qué filas viene ("INSUMO 80.000 + INSUMOS 45.500,50") | lo que administración compara con su "General" |

## 6. Reglas de negocio

### 6.1 Método y corridas

- **RN-IMP-01** La carga inicial se hace **solo con comandos en el servidor** (§11), que corre NexoSmart. No hay pantalla, ni ruta de la API, ni permiso nuevo en la matriz: `app/Modules/Importacion/routes.php` queda sin rutas. *(F-62 "NexoSmart con administración", pantalla "—"; §4)*
- **RN-IMP-02** **Dos lotes, en este orden:** primero **obras** (obras, presupuestos, montos, gastado, materiales y retirado), después **tareas** (tareas abiertas y documentos de la minuta). Una tarea o un documento de una obra que todavía no está en el sistema es un faltante (RN-IMP-33). *(F-62)*
- **RN-IMP-03** **Ensayo por defecto:** sin `--confirmar`, el comando lee el archivo, valida todo por los mismos caminos que la carga real (dentro de una transacción que se deshace al final), arma el informe y **no guarda nada**: ni datos, ni actividades, ni eventos, ni avisos. Termina con "Ensayo: no se guardó nada. {n} obras: {a} entran, {b} con faltantes ({f}). Informe: {ruta}" (o el equivalente de tareas). *(DU-21 "revisa y aprueba administración antes de importar")*
- **RN-IMP-04** **Confirmar** exige las tres cosas: `--confirmar`; `--aprobo={mail}` de un usuario **activo** con `rendicion = editar`, `obras = editar` y `estado_obra = editar` (lote obras) o con `pedidos = editar` (lote tareas); y un **ensayo del mismo archivo** (misma huella) hecho antes. Si falta alguna: "No se guardó nada: {motivo}." ("primero corré el ensayo de este archivo", "{mail} no es un usuario activo", "{mail} no puede aprobar la carga de obras"). Confirmar vuelve a validar todo y guarda **solo lo que no tiene faltantes**. *(DU-21; F-62 "con administración")*
- **RN-IMP-05** **Huella:** SHA-256 de los bytes del archivo. El informe muestra los 12 primeros caracteres; administración aprueba "el informe {huella}" y se confirma ese archivo. Un archivo tocado después del ensayo (aunque sea un espacio) tiene otra huella y pide otro ensayo. *(técnica)*
- **RN-IMP-06** **Una corrida a la vez** (bloqueo `importacion`): si hay otra en curso, "Hay otra carga en curso. Esperá a que termine." y no se hace nada. *(técnica)*
- **RN-IMP-07** **Transacción por obra:** todo lo de una obra (obra, presupuestos, montos, contactos, oferta, materiales, gastado y retirado) se guarda junto o no se guarda nada; en tareas, **una transacción por fila**. Si una obra o una fila falla por un error inesperado, no queda nada de ella, la corrida **sigue** con las demás, el informe lo marca ("Error inesperado: no se guardó nada de esta obra.") y la corrida termina "con errores". Si el proceso se corta, lo confirmado queda y la corrida queda "interrumpida": se vuelve a correr y entra solo lo que faltaba (RN-IMP-08). *(técnica, ADR-011)*
- **RN-IMP-08** **Idempotencia** (correr dos veces no duplica): una **obra** se reconoce por el N.º de su presupuesto principal; el **gastado** y el **retirado** de una obra, por su carga inicial vigente (no anulada); una **tarea** o un **documento**, por su **Clave**, registrada en la corrida que la importó. Lo que ya está se cuenta como "Ya importada" y no se toca. *(QA F-62 "idempotente (se puede correr dos veces)", ADR-011)*
- **RN-IMP-09** **Por los casos de uso de cada módulo:** Importacion no escribe tablas ajenas (`arquitectura.md` §2); usa el contrato de carga inicial de cada módulo (§11.4), con **las mismas validaciones que la pantalla**, salvo las excepciones de esta especificación (sin solicitante y sin fecha de pedido en tareas de carga inicial, origen `saldo_inicial`, renglón con importe 0 para el retirado). *(ADR-003, arquitectura §2 "usa los casos de uso de los demás módulos")*
- **RN-IMP-10** **Quién figura:** en todo lo que se guarda, el "cargó" / "creó" es quien aprobó (`--aprobo`), y la actividad lleva la marca de carga inicial (RN-IMP-39). La tarea no tiene solicitante por eso: el solicitante es el de la minuta o ninguno (DU-20). *(F-14, ADR-010)*

### 6.2 Archivos y datos reales

- **RN-IMP-11** **Formato:** `.xlsx` con las hojas y columnas de §5. Si falta una hoja obligatoria (Obras; Tareas) o una columna obligatoria, la corrida no empieza: "Falta la columna 'Fecha de corte' en la hoja Obras." Una hoja opcional vacía o ausente (Presupuestos, Gastado, Rubros, Materiales, Personas) no frena. *(técnica)*
- **RN-IMP-12** **Dónde viven los archivos:** en una carpeta **privada del servidor**, fuera del repositorio y fuera de `public` (`IMPORTACION_DIR`, por defecto `storage/app/private/importacion/`, solo legible por el usuario del servidor). El comando **solo lee archivos de esa carpeta** ("El archivo tiene que estar en la carpeta de la importación."). Los informes se escriben ahí. Los `.xlsx` reales **nunca** se suben al repositorio, ni a un issue, ni a un test; los tests usan archivos ficticios armados por el propio test. A los **30 días de la apertura** NexoSmart borra la carpeta (archivos e informes); en la base quedan solo las corridas con su huella y sus conteos (§11.3). Administración y NexoSmart se pasan los archivos por la carpeta de Drive del proyecto (cap. 14). *(regla n.º 1 de los datos del cliente; seguridad.md SEG-02)*
- **RN-IMP-13** **Nada de datos en los logs:** la consola y los logs muestran solo conteos, la huella corta y la ruta del informe. Un error inesperado se registra con la corrida, la hoja, la fila y el tipo de error, **sin el valor de ninguna celda** (ni nombres, ni mails, ni importes). El detalle con valores está solo en el informe. *(seguridad.md SEG-02)*
- **RN-IMP-14** **Lectura tolerante, sin adivinar:** se quitan espacios al borde y dobles; las listas no distinguen mayúsculas ni tildes ("en ejecucion" = En ejecución); un importe con más de 2 decimales, una cantidad con más de 3 o una fecha que no se entiende son **faltantes**, no se redondean ni se interpretan ("La fecha '31/11/2026' no existe."). *(ADR-008, cap. 9)*

### 6.3 Personas y rubros

- **RN-IMP-15** **Personas:** si lo escrito en Propietario o Solicitante es **exactamente el mail** de un usuario del sistema, es esa persona; si no, se busca en la hoja **Personas**. Un **propietario** que no está en Personas, cuyo mail no es de un usuario, que está **inactivo** o cuyo sector no tiene Tareas ≥ Ver (RN-PED-03) es un **faltante** de esa tarea. Un **solicitante** que no se encuentra **no frena**: la tarea entra con "Solicitante: —" y el informe lo avisa ("No encontramos a 'Guido' en Personas: queda sin solicitante."). Un solicitante inactivo se guarda igual (es un dato del pasado). *(DU-20 "solicitante vacío si no figura", DU-P10, RN-PED-03)*
- **RN-IMP-16** **Rubros, nada se mapea solo:** el rubro escrito en la hoja Gastado se toma directo **solo** si es igual al nombre de un rubro del sistema (sin contar mayúsculas ni espacios al borde): "INSUMOS", "insumos ". Cualquier otra forma —"INSUMO", "LOGÍSTICA" con tilde, "Materiales TB", "VARIOS"— necesita una fila en la hoja **Rubros** con **Aprobado = Sí**; si no la tiene, o dice "No", o el rubro del sistema no es uno de los 13, es un **faltante** de esa obra ("El rubro 'INSUMO' no tiene equivalencia aprobada en la hoja Rubros."). El informe trae la forma en "Rubros por revisar" con un rubro **sugerido**, que no se aplica. *(DU-21 "nada se mapea solo sin esa revisión", cap. 9)*
- **RN-IMP-17** **La equivalencia queda escrita:** el historial del gasto de carga inicial dice qué equivalencias se usaron y quién las aprobó ("Equivalencias aprobadas por Carla: INSUMO → INSUMOS, LOGÍSTICA → LOGISTICA."), para que cualquiera sepa de dónde salió cada número. *(DU-21, F-14)*

### 6.4 Obras

- **RN-IMP-18** **Obligatorios de la obra:** los de `obras.md` §4.1, con sus mismas validaciones: N.º de presupuesto y revisión, trabajo, cliente, responsable (usuario activo), oferta técnica (como **link**: un archivo se sube después en la ficha, ADR-014) y los dos contactos con nombre, mail y teléfono. Estado: **En ejecución** o **Confirmada** (propuesta DU-I07); otro valor es faltante. *(obras.md §4.1, RN-OBR-10…19; DU-21)*
- **RN-IMP-19** **Una obra incompleta no se carga** (DU-21): con **un** faltante en la obra, en sus presupuestos, en su gastado o en sus materiales, **no entra nada de esa obra** (ni sus tareas, que quedan con el faltante "La obra {N.º} no está en el sistema"). El informe lista todos los faltantes juntos, no de a uno. *(DU-21 "las obras no se cargan incompletas: planilla de faltantes por obra que completa administración")*
- **RN-IMP-20** **Obra que ya está en el sistema** (su N.º principal ya existe, por ejemplo porque administración la cargó a mano en producción): **no se cambia** nada de la obra, de sus presupuestos ni de sus montos (aviso "La obra ya está en el sistema: sus datos y presupuestos no se tocan; si falta algo, se completa en la ficha y en W-13."); su **gastado** y sus **materiales** sí entran si cumplen las reglas de abajo. *(RN-IMP-08)*
- **RN-IMP-21** La obra **nace Confirmada** (RN-OBR-18) y, si la plantilla dice En ejecución, pasa a **En ejecución** en la misma transacción, por la transición permitida (RN-OBR-29), con su registro en el historial. No genera avisos (no hay avisos de obras). *(obras.md RN-OBR-18, 29)*
- **RN-IMP-22** **Presupuestos:** de la hoja Presupuestos, con las reglas de `obras.md`: N.º único en todas las obras (RN-OBR-12), adicional con sufijo o número propio (RN-OBR-15), aprobación opcional (RN-OBR-16), **por rubro o solo total, no mezclado** (RN-OBR-31; un "Por rubro" con un Total, o un "Solo total" con rubros, es faltante), monto del presupuesto opcional (RN-OBR-36). Una obra **sin presupuesto cargado** o **solo con total** entra igual (RN-OBR-34) con un aviso: "La obra no tiene presupuesto por rubro: la rendición va a mostrar lo gastado sin compararlo por rubro." *(F-48, RN-OBR-31…37, RN-REN-07, RN-REN-10)*

### 6.5 Gastado acumulado y retirado

- **RN-IMP-23** **El gastado entra como una carga inicial por obra:** un gasto `sin_documento` / `saldo_inicial` con fecha = **fecha de corte** de la obra, proveedor **genérico**, "cargó" = quien aprobó, y **un renglón por rubro del sistema** (las filas que van al mismo rubro se suman: "INSUMO 80.000 + INSUMOS 45.500,50" → INSUMOS 125.500,50), con cantidad 1, precio unitario = el importe, total = el importe, obra = la obra, concepto "Gastado hasta el {fecha de corte} según su rendición". Un rubro en 0 no genera renglón; un importe negativo es faltante ("El gastado no puede ser negativo."). **EPP** queda en EPP (en el resumen suma a INSUMOS, C17); FIJOS y HORAS LIQUIDADAS entran como cualquier rubro (la carga inicial acepta los 13, `gastos.md` §4.3). La fecha de corte no puede ser posterior a hoy. *(F-62 "un total por rubro", DU-21, gastos.md §4.3, RN-REN-08)*
- **RN-IMP-24** **Control contra su planilla:** si la obra trae "Total gastado según su planilla", la suma de su hoja Gastado tiene que dar **igual al centavo**; si no, faltante: "La suma por rubro (2.347.500,50) no da el total de su planilla (2.437.500,50): diferencia 90.000." *(QA F-62 "conteos contra el origen")*
- **RN-IMP-25** **Sin doble conteo:** si la obra ya tiene en el sistema renglones **no anulados** de otro origen con fecha **hasta la fecha de corte inclusive**, el gastado de esa obra es faltante: "La obra ya tiene 3 renglones cargados con fecha hasta el 30/11/2026: se contarían dos veces. Anulalos o cargá el acumulado sin ellos." Los renglones con fecha **posterior** al corte no molestan: son lo cargado después. *(ADR-007, RN-GAS-47)*
- **RN-IMP-26** **Lo posterior al corte se carga en el sistema:** lo que tenga fecha después del corte va por W-10, W-35 o W-46 como cualquier gasto, y **no** se suma al acumulado. HORAS LIQUIDADAS y FIJOS: el acumulado incluye los **períodos enteros** ya liquidados; el primer período que se carga en W-46 empieza el día siguiente al último incluido. *(propuesta DU-I02; gastos.md §5.5)*
- **RN-IMP-27** **Una carga inicial de gastado vigente por obra.** No se **corrige** en W-34 (no tiene "Editar"; `PATCH` 422 "La carga inicial no se corrige: anulala y volvé a importarla."); se **anula** como cualquier gasto (RN-GAS-40, con motivo) y se vuelve a importar el gastado de esa obra con la plantilla corregida (la obra ya existe: RN-IMP-20). *(propuesta DU-I09; RN-GAS-40)*
- **RN-IMP-28** **Lista de materiales:** de la hoja Materiales, con las validaciones de W-48 (`rendicion.md` §4.2): nombre no repetido en la obra, unidad obligatoria, cantidad presupuestada ≥ 0, presupuesto de **esa** obra si tiene más de uno. Un material que ya está en la obra (mismo nombre normalizado) no se duplica; si la cantidad presupuestada difiere, aviso "Cemento x 50 kg ya está en la lista con 120: no se cambia." *(propuesta DU-I06; RN-REN-47)*
- **RN-IMP-29** **Retirado hasta el corte (DU-R09):** si hay al menos un material con retirado > 0, una segunda carga inicial de la obra (`sin_documento` / `saldo_inicial`, fecha = corte, proveedor genérico) con **un renglón por material**: cantidad = lo retirado (en la unidad del material), precio unitario **0**, total **0**, rubro MATERIALES, `material_id` = ese material, concepto "Retirado hasta el {fecha de corte} (el importe está en el gastado acumulado)". **No suma plata** (el importe ya está en el gastado por rubro) y "Resta llevar" da bien desde el primer día. Retirado 0 o vacío: sin renglón. Una vigente por obra, como RN-IMP-27. *(DU-R09 opción b, RN-REN-50)*
- **RN-IMP-30** **W-48:** los renglones de carga inicial de un material se muestran en una **primera columna "Hasta {fecha de corte} · Carga inicial"**, que **no** cuenta entre las 6 fechas más recientes de RN-REN-50. Si además hay que agrupar fechas viejas posteriores al corte, se juntan en esa misma columna con la fecha más nueva agrupada: "Hasta {fecha} · incluye carga inicial". *(DU-R09 "columna 'Hasta {fecha de arranque}' con la etiqueta 'Carga inicial'"; ajusta RN-REN-50, §16)*
- **RN-IMP-31** Retirado mayor que lo presupuestado **no frena**: aviso "Cemento x 50 kg: retirado 160 de 150 presupuestadas (resta llevar −10)." y en W-48 se ve en rojo como cualquier exceso (RN-REN-50). *(RN-REN-50, N18)*

### 6.6 Tareas abiertas

- **RN-IMP-32** **Solo lo abierto:** entran las filas "Tarea" en **No iniciada, En curso o Bloqueada**. Las **Completada** no se importan (se cuentan en el Resumen: "Completadas (no se importan): 12"). Una fila sin estado es faltante "Poné el estado." *(F-62 "los pedidos abiertos", análisis de la minuta §1 "sin estado 8")*
- **RN-IMP-33** **Para entrar, una tarea necesita:** Clave única; Tarea; Obra que esté **en el sistema y no Terminada**, o "Sin obra"; **propietario** (persona activa de un sector con Tareas ≥ Ver) **o** "(en el sector)" con un **Sector** activo con Tareas ≥ Ver; Estado; **Fecha de entrega**; y si está **Bloqueada**, el motivo en Notas. Si falta algo, **no entra**: queda en "Faltantes" y en "Para completar por sector" (sector = el del propietario, o el de la columna Sector, o "Administración" si no hay ninguno), para que el sector lo complete **antes de la apertura**. Lo que siga faltando el día de la apertura no entra (propuesta DU-I03) y el sector lo carga a mano en W-08. *(DU-20 "los abiertos sin fecha de entrega o sin propietario se listan antes del arranque para que el sector los complete", DU-14, RN-PED-02, 03)*
- **RN-IMP-34** **Cómo entra:** con `carga_inicial = true`, el **estado de la minuta**, el propietario y su sector (o en el sector), **solicitante** el de la minuta si se encuentra (si no, vacío), **fecha de pedido** la de la minuta o vacía (no posterior a hoy: si no, faltante), entrega, período (los dos o ninguno), "con quién" (≤ 150; si es más largo, faltante "'Con quién' tiene más de 150 caracteres."), los **links** (hasta 10, solo http/https; uno inválido es faltante) como adjuntos `pedido` tipo link, y las **Notas**: si está Bloqueada, como **motivo de bloqueo**; si no, como **aclaraciones**. Una **En curso** tiene que tener propietario: "(en el sector)" + En curso es faltante "Una tarea En curso tiene que tener propietario: elegí quién la tiene." *(DU-20, pedidos.md §4.6, RN-PED-05, 06, 24)*
- **RN-IMP-35** **Prioridad:** la de la minuta (Urgente, Prioritario, Normal). Si está vacía o es **P3** (nunca se usó): calculada con los plazos vigentes (W-50) si están las dos fechas; si no, **Normal**. Queda **corregida a mano** (`prioridad_manual`, con la marca "(corregida a mano)" y la sugerencia de RN-PED-13) **solo** si están las dos fechas y la de la minuta difiere de la calculada. Una tarea sin fecha de pedido conserva su prioridad hasta que alguien cargue la fecha (RN-PED-14). *(DU-20 "prioridad la de la minuta (o calculada si hay las dos fechas; si no, Normal)", RN-PED-11…14)*
- **RN-IMP-36** Las vencidas (entrega anterior a hoy) entran igual y se ven **vencidas** desde el primer día, como cualquier tarea. *(F-04)*
- **RN-IMP-37** **Sin avisos ni mails al importar:** importar una tarea **no** dispara el aviso de asignación ni ningún otro, en la campana ni por mail (nadie recibe 40 mails el día de la apertura; cada uno ve sus tareas en Mis tareas). Desde la importación, las tareas funcionan como cualquier otra: el **recordatorio "vence mañana"** (en el sistema, no por mail) sale por su fecha, y tomar, bloquear, comentar, completar o reasignar avisan como siempre (sin solicitante, RN-AVI-15). *(propuesta DU-I05; RN-AVI-05, 15, 16)*

### 6.7 Documentos de la minuta

- **RN-IMP-38** **Lo que es documento va a la obra:** las filas "Documento" (presupuestos, OC, certificados que hoy viven en la minuta) entran a **Documentos de la obra** (W-20) como **Certificado** (con su fecha, obligatoria) u **Otro**, con nombre = la columna Tarea (≤ 120) y **sus links** (1 a 10; sin link es faltante "Un documento necesita al menos un link."). Entran sin importar el estado de la fila en la minuta (un documento no se "completa"), solo de obras que están en el sistema; "Sin obra" en un documento es faltante. La OC de un presupuesto se puede cargar en cambio en la hoja Presupuestos (documento de aprobación). *(DU-20 "los que son documentos (presupuesto, OC, certificado) van a Documentos de la obra y no a pedidos"; propuesta DU-I08; obras.md §4.5)*

### 6.8 Recorrido e historial

- **RN-IMP-39** Cada cosa importada deja su actividad (ADR-010), con `user_id` = quien aprobó y la marca `carga_inicial`, y se lee así:
  - **Tarea** (recorrido, W-09): "30/11/2026 18:10 · Carga inicial desde la minuta (aprobó Carla)" como primera línea;
  - **Obra** (historial, W-23): "Carla creó la obra (carga inicial)" y "Carla cambió el estado: Confirmada → En ejecución (carga inicial)";
  - **Gasto** (historial, W-34): "Carla cargó la carga inicial (5 renglones, gastado hasta el 30/11/2026)" + la línea de equivalencias de RN-IMP-17;
  - **Material** y **documento**: "Carla agregó … (carga inicial)".
  *(F-14, F-34, ADR-010)*

## 7. Cómo aparece lo importado en el sistema

- **RN-IMP-40** **Tarea:** en el detalle (W-09, y W-42 en el celular) una marca **"Carga inicial"** al lado del estado, con el texto de ayuda "Vino de la minuta al arrancar el sistema."; "Solicitante: —" y "Pedida: —" si no los tiene (RN-PED-61). En los listados (Mis tareas, Tareas de mi gente, Todas las tareas, calendario) se ve como cualquier tarea, sin marca. La API ya devuelve `carga_inicial` (`PedidoFilaResource`); el detalle también. *(DU-20 "marcados carga inicial")*
- **RN-IMP-41** **Gasto:** en Gastos cargados (W-33) y en el detalle (W-34) el documento es **"Carga inicial"** y el proveedor "—" (RN-GAS-29); título de W-34: "Carga inicial · {obra} · gastado hasta el {fecha de corte}" o "· retirado hasta el {fecha de corte}"; solo tiene **Anular** (RN-IMP-27). *(RN-GAS-29, RN-GAS-36)*
- **RN-IMP-42** **Rendición:** los renglones suman como cualquier otro en su rubro (RN-REN-08): W-11 arranca con el gastado al corte, W-36 muestra el renglón "Carga inicial · Gastado hasta el 30/11/2026 según su rendición", "Últimos gastos" lo incluye hasta que haya 10 más nuevos, W-37 lo agrupa en el proveedor "—" (grupo "Sin documento", RN-REN-29), W-12 y el % de W-05 lo cuentan. El retirado (importe 0) aparece en W-36 de MATERIALES con importe 0 y en W-48 en su columna (RN-IMP-30). *(RN-REN-08, RN-REN-29, ADR-007)*
- **RN-IMP-43** **Quién lo ve:** lo importado se ve con la tabla de permisos como todo lo demás (ADR-006): Ingeniería ve la carga inicial de **sus** obras; el monto de la obra solo quien tiene "Monto de la obra y ganancia". No hay nada que "solo vea la importación". *(ADR-006, identidad.md §6.3)*

## 8. Textos exactos

**Consola** (lo único que muestra; RN-IMP-13):

| Situación | Texto |
|---|---|
| Ensayo de obras | "Ensayo: no se guardó nada. 3 obras: 2 entran, 1 con faltantes (2). Informe: {ruta}" |
| Ensayo de tareas | "Ensayo: no se guardó nada. 8 filas: 3 tareas y 1 documento entran, 1 completada no se importa, 3 con faltantes. Informe: {ruta}" |
| Confirmada | "Carga confirmada (aprobó Carla). 2 obras guardadas, 0 ya estaban, 1 con faltantes. Informe: {ruta}" |
| Segunda vez | "Carga confirmada (aprobó Carla). 0 obras guardadas, 2 ya estaban, 1 con faltantes. Informe: {ruta}" |
| Sin ensayo previo | "No se guardó nada: primero corré el ensayo de este archivo." |
| Aprobó inválido | "No se guardó nada: carla@ejemplo.com no es un usuario activo." · "No se guardó nada: carla@ejemplo.com no puede aprobar la carga de obras." |
| Otra corrida en curso | "Hay otra carga en curso. Esperá a que termine." |
| Archivo fuera de la carpeta | "El archivo tiene que estar en la carpeta de la importación." |
| Falta una columna | "Falta la columna '{columna}' en la hoja {hoja}." |
| Error inesperado en una obra | "Obra {N.º}: error inesperado, no se guardó nada de esta obra (ver informe)." |

**Faltantes** (columna "Qué falta" del informe; uno por dato, todos juntos):

| Dato | Texto |
|---|---|
| Obligatorio vacío | "Falta {columna}." ("Falta la fecha de corte.", "Falta el mail del comprador.") |
| N.º de presupuesto | los de RN-OBR-11/12 ("Ese N.º de presupuesto ya está en Piso industrial nave 3.") |
| Responsable | "No hay un usuario activo con el mail laura@ejemplo.com." |
| Link | "El link tiene que empezar con http:// o https://." |
| Estado de la obra | "El estado tiene que ser En ejecución o Confirmada." |
| Fecha de corte futura | "La fecha de corte no puede ser posterior a hoy." |
| Rubro | "El rubro 'INSUMO' no tiene equivalencia aprobada en la hoja Rubros." · "La equivalencia de 'VARIOS' dice 'No': ese gastado no entra hasta que se decida su rubro." |
| Importe | "El gastado no puede ser negativo." · "El importe '12,345' tiene más de 2 decimales." |
| Control | "La suma por rubro ({x}) no da el total de su planilla ({y}): diferencia {z}." |
| Doble conteo | "La obra ya tiene {n} renglones cargados con fecha hasta el {corte}: se contarían dos veces. Anulalos o cargá el acumulado sin ellos." |
| Carga inicial vigente | "La obra ya tiene su carga inicial de gastado (aprobó Carla el 30/11/2026). Para cargarla de nuevo, anulá esa primero." |
| Presupuesto | "Un presupuesto se carga por rubro o con el total, no las dos cosas." · "El presupuesto 1-200-2026 A1 no es de esta obra." |
| Tarea: estado | "Poné el estado." |
| Tarea: entrega | "Falta la fecha de entrega." |
| Tarea: propietario | "Falta el propietario: una persona o '(en el sector)' con el sector." · "No encontramos a 'Cari' en la hoja Personas." · "Carla está inactiva: elegí a quién se la pasás." · "Pablo es de un sector sin acceso a Tareas." |
| Tarea: En curso sin dueño | "Una tarea En curso tiene que tener propietario: elegí quién la tiene." |
| Tarea: Bloqueada | "Está Bloqueada sin motivo: escribí el motivo en Notas." |
| Tarea: obra | "La obra 2-131-2026 no está en el sistema: cargala en la hoja Obras o pasá la tarea a 'Sin obra' nombrando la obra en la tarea." · "La obra 2-131-2026 está Terminada: completá la tarea en la minuta o pasala a 'Sin obra' nombrando la obra en la tarea." |
| Clave | "La clave '2-131-2026 · fila 12' está repetida en las filas 14 y 30." |
| Documento | "Un documento necesita al menos un link." · "Un certificado necesita su fecha." · "Un documento tiene que ser de una obra." |

**Avisos del informe** (no bloquean): "La obra ya está en el sistema: sus datos y presupuestos no se tocan; si falta algo, se
completa en la ficha y en W-13." · "La obra no tiene presupuesto por rubro: la rendición va a mostrar lo gastado sin
compararlo por rubro." · "No encontramos a 'Guido' en Personas: queda sin solicitante." · "Prioridad 'P3': se calculó
(Prioritario)." / "…: queda Normal." · "Cemento x 50 kg ya está en la lista con 120: no se cambia." · "Cemento x 50 kg:
retirado 160 de 150 presupuestadas (resta llevar −10)." · "Fila Completada: no se importa."

**En pantalla:** marca "Carga inicial" (ayuda: "Vino de la minuta al arrancar el sistema."); documento "Carga inicial"
(ya existe en `EtiquetaDocumento`); W-48 "Hasta 30/11/2026 · Carga inicial"; W-34 sin "Editar" y `PATCH` 422 "La carga
inicial no se corrige: anulala y volvé a importarla."

## 9. Permisos, avisos y seguridad

| Qué | Quién | Regla |
|---|---|---|
| Correr los comandos | NexoSmart, con acceso al servidor | RN-IMP-01; no hay ruta HTTP ni función en la matriz |
| Aprobar el lote obras (`--aprobo`) | usuario activo con `rendicion = editar`, `obras = editar` y `estado_obra = editar` (inicial: Gerencia y Jefatura de Administración) | RN-IMP-04 |
| Aprobar el lote tareas (`--aprobo`) | usuario activo con `pedidos = editar` (inicial: todos; en la práctica, quien coordinó con los sectores) | RN-IMP-04 |
| Aprobar equivalencias de rubros | administración, en la hoja Rubros (columna Aprobó) | RN-IMP-16, DU-21 |
| Completar faltantes de tareas | cada sector, en la hoja Tareas (por la pestaña "Para completar por sector") | RN-IMP-33, DU-20 |
| Ver lo importado | la matriz, como todo (ADR-006) | RN-IMP-43 |
| Anular una carga inicial de gastado | `gastos = editar` (RN-GAS-40) | RN-IMP-27 |

**Avisos:** ninguno por la importación (RN-IMP-37). Los eventos `GastoImputado` de las cargas iniciales sí salen
después del commit: solo invalidan el cache de la rendición (no avisan a nadie).

**Seguridad (para la revisión de la semana):** archivos solo en la carpeta privada y borrados a los 30 días
(RN-IMP-12); el comando no acepta rutas fuera de esa carpeta; sin valores en logs (RN-IMP-13); el informe tiene datos del
cliente y viaja solo por la carpeta de Drive del proyecto; los links importados pasan por la misma validación http/https
que la pantalla (FE-01).

## 10. Casos borde (datos ficticios)

| # | Caso | Resultado esperado |
|---|---|---|
| 1 | Tarea abierta de una obra que **no** está en la hoja Obras ni en el sistema | No entra; faltante "La obra … no está en el sistema…" (RN-IMP-33). |
| 2 | Tarea abierta de una obra **Terminada** (de las solapas ocultas de la minuta) | No entra; faltante "…está Terminada: completá la tarea en la minuta o pasala a 'Sin obra'…" (propuesta DU-I07). |
| 3 | Tarea de una obra **Confirmada** (todavía no empezó) | La obra se carga Confirmada en la hoja Obras (sin gastado) y la tarea entra (propuesta DU-I07). |
| 4 | "INSUMO" en la hoja Gastado, sin fila en Rubros | La obra entera no entra; faltante; "Rubros por revisar" sugiere INSUMOS sin aplicarlo (RN-IMP-16, 19). |
| 5 | "LOGÍSTICA" con tilde | Igual que 4: necesita su fila aprobada (RN-IMP-16). |
| 6 | "INSUMO" y "INSUMOS" en la misma obra, los dos aprobados a INSUMOS | Un solo renglón INSUMOS con la suma (RN-IMP-23). |
| 7 | Obra en ejecución **sin presupuesto por rubro** (solo total o sin cargar) | Entra; aviso; la rendición muestra lo gastado y, si hay total, el total contra lo gastado (RN-IMP-22, RN-REN-07, 10). |
| 8 | Obra con gastado y **sin fecha de corte** | Faltante "Falta la fecha de corte."; no entra (RN-IMP-19). |
| 9 | En **producción** ya hay una factura de la obra del 25/11/2026 (cargada a mano antes de importar) y el corte es el 30/11/2026 | El gastado de esa obra es faltante por doble conteo (RN-IMP-25); administración anula la factura o la descuenta del acumulado. |
| 10 | Lo que el cliente cargó **en la prueba de la semana 3** (en el entorno de prueba) | No pasa a producción (propuesta DU-I01): no hay doble conteo. Si Juani decidiera arrancar en la misma base, aplica el caso 9. |
| 11 | Factura del 02/12/2026 (después del corte) que está en su Excel | No va en el acumulado; se carga en W-10 (RN-IMP-26). |
| 12 | HORAS LIQUIDADAS: acumulado hasta la quincena 16/11–30/11 | El primer período en W-46 es 01/12–15/12 (RN-IMP-26). |
| 13 | Se corre el ensayo, se arregla un espacio en la plantilla y se confirma sin ensayar | "No se guardó nada: primero corré el ensayo de este archivo." (huella distinta, RN-IMP-05). |
| 14 | Se confirma dos veces el mismo archivo | La segunda: "0 obras guardadas, 2 ya estaban"; tareas "Ya importadas"; nada duplicado (RN-IMP-08). |
| 15 | Se corta la luz a mitad de la carga de obras | Las obras confirmadas quedan enteras; la que estaba en curso no deja nada; se vuelve a correr y entra lo que faltaba (RN-IMP-07, 08). |
| 16 | El gastado de una obra se importó mal (una equivalencia equivocada) **antes** de la apertura | Se restaura el respaldo previo y se vuelve a confirmar (RN-IMP-44). |
| 17 | Lo mismo **después** de la apertura | Se anula la carga inicial en W-34 ("Equivalencia equivocada") y se importa de nuevo el gastado de esa obra (RN-IMP-27, 20). |
| 18 | Tarea Bloqueada sin nada en Notas | Faltante "Está Bloqueada sin motivo…" (RN-IMP-34). |
| 19 | Tarea "En curso" con "(en el sector)" | Faltante "Una tarea En curso tiene que tener propietario…" (RN-IMP-34). |
| 20 | Propietario "Cari" (apodo) sin fila en Personas | Faltante; "Personas por revisar" sugiere Carla (RN-IMP-15). |
| 21 | Solicitante "Guido", que no tiene usuario | Entra con "Solicitante: —"; aviso (RN-IMP-15). La reabre cualquiera con Tareas = Editar (RN-PED-61). |
| 22 | Prioridad vacía, pedida 20/11, entrega 25/11 (plazos 0–1 / 2–7 / 8+) | Prioritario calculada, no manual (RN-IMP-35). |
| 23 | Prioridad URGENTE, pedida 20/11, entrega 05/12 (calculada Normal) | Urgente "(corregida a mano)", con la sugerencia "Calculada: Normal" (RN-IMP-35, RN-PED-13). |
| 24 | Prioridad PRIORITARIO sin fecha de pedido | Prioritario; no se recalcula hasta que tenga fecha de pedido (RN-PED-14). |
| 25 | Fila de la minuta "Certificado N°2" sin propietario, Completada, con link | Entra como Certificado en Documentos de la obra si tiene fecha (RN-IMP-38). |
| 26 | "OC del cliente" sin link | Faltante "Un documento necesita al menos un link." (RN-IMP-38). |
| 27 | Tarea con entrega mañana importada a las 18:00 | No hay aviso de asignación ni mail; el recordatorio "vence mañana" aparece en la campana en la corrida de las 19:00 (RN-IMP-37, RN-AVI-17). |
| 28 | Material "Malla sima" con retirado 0 | Entra a la lista; sin renglón de retirado (RN-IMP-29). |
| 29 | Material con retirado y la obra sin fecha de corte | Faltante "Falta la fecha de corte." (§5.1). |
| 30 | Retirado de un material que **no** está en la lista (fuera de presupuesto) | No se importa por la plantilla: lo retirado fuera de presupuesto antes del corte ya está en el gastado; desde la apertura, se carga con su remito (RN-REN-49). |
| 31 | Una tarea importada resulta ser de otra obra | Se corrige en W-32 como cualquier tarea (RN-PED-28; la obra es editable, DU-P05). |
| 32 | Una tarea importada por error (ya estaba hecha) | Se completa con el comentario "Cargada por error en la carga inicial" (no hay borrar tareas). |
| 33 | `--aprobo` de alguien de Compras para el lote obras | "No se guardó nada: … no puede aprobar la carga de obras." (RN-IMP-04). |

## 11. Comandos y contratos (sugeridos)

### 11.1 Comandos (`app/Modules/Importacion/Infrastructure/Console`, como `CrearAdministradorCommand`)

| Comando | Qué hace |
|---|---|
| `php artisan importacion:plantilla {obras\|tareas}` | Deja en la carpeta de la importación la plantilla vacía (§5), con listas desplegables (rubros, sectores activos, estados) y la hoja "Cómo completar". |
| `php artisan importacion:obras {archivo}` | **Ensayo** del lote obras (RN-IMP-03). |
| `php artisan importacion:obras {archivo} --confirmar --aprobo={mail}` | Carga real del lote obras (RN-IMP-04). |
| `php artisan importacion:tareas {archivo}` · `… --confirmar --aprobo={mail}` | Ídem para tareas y documentos. |
| `php artisan importacion:corridas` | Lista las corridas: fecha, lote, modo, huella corta, aprobó, estado, conteos (§11.3). |

`{archivo}` es un nombre dentro de `IMPORTACION_DIR` (RN-IMP-12). Código de salida 0 si terminó (aunque haya faltantes),
1 si no empezó o terminó "con errores".

### 11.2 Lectura

Lector de `.xlsx` con `openspout/openspout` (ya está en `composer.json`), detrás de un repositorio de lectura por hoja
(`arquitectura.md` §3: "Repository · Solo en Importacion (lectores de xlsx)"). Fechas de Excel (número de serie) y texto
dd/mm/aaaa; importes y cantidades a string decimal (ADR-008) sin pasar por `float`.

### 11.3 Registro de corridas (tablas de Importacion)

**`importacion_corridas`**: `id` · `lote` (`obras`·`tareas`) · `modo` (`ensayo`·`confirmada`) · `huella` (char 64) ·
`aprobo_id` (FK users, null en ensayo) · `estado` (`en_curso`·`terminada`·`con_errores`·`interrumpida`) · `resumen`
(json: solo conteos) · `informe` (nombre del archivo del informe, sin ruta) · `created_at`, `terminada_at`.
**`importacion_filas`**: `corrida_id` · `hoja` · `clave` (N.º de obra, `gastado:{obra}`, `retirado:{obra}` o la Clave de
la tarea) · `resultado` (`importada`·`ya_estaba`·`faltante`·`no_se_importa`) · `sujeto_type`, `sujeto_id` (lo creado) ·
índice `(hoja, clave, resultado)`. **Sin valores de celdas** (RN-IMP-13). Para el DBA: migración nueva del módulo,
reversible.

### 11.4 Contratos que usa Importacion (cada módulo implementa el suyo, ADR-003)

| Contrato | Módulo | Métodos (sugeridos) | Reglas |
|---|---|---|---|
| `Identidad\Contracts\UsuariosConsulta` | Identidad | `porMail(mail): ?{id, nombre, activo, sectorId}` · `sectorPorNombre(nombre): ?{id, activo}` · `puede(userId, funcion, nivel)` (ya en `Permisos`) | RN-IMP-04, 15, 33 |
| `Obras\Contracts\CargaInicialDeObras` | Obras | `existe(numeroPrincipal): ?obraId` · `crear(actorId, DatosObraInicial): obraId` (usa `CrearObraAction` + `CambiarEstadoObraAction` + `CargarMontosAction` + `CargarMontoObraAction`, adjuntos tipo link) · `agregarDocumento(actorId, obraId, DatosDocumento): id` · `estado(obraId)` | RN-IMP-18…22, 38 |
| `Rendicion\Contracts\MaterialesDeObra` (ampliado) | Rendicion | `agregarALaLista(actorId, obraId, DatosMaterial): materialId` (mismas validaciones que W-48; si ya existe por nombre, devuelve el existente y dice si difiere) | RN-IMP-28 |
| `Rendicion\Contracts\CatalogoDeRubros` | Rendicion | `imputables()` (ya existe) | RN-IMP-16 |
| `Gastos\Contracts\CargaInicialDeGastos` | Gastos | `cargar(actorId, obraId, fechaCorte, list<RenglonInicial{rubroId, concepto, cantidad, precio, ?materialId}>, historial): gastoId` (origen `saldo_inicial`, proveedor genérico, `GastoImputado` después del commit) · `vigente(gastoId): bool` · `renglonesHastaFecha(obraId, fecha): int` (otros orígenes, no anulados) | RN-IMP-23…29 |
| `Pedidos\Contracts\CargaInicialDeTareas` | Pedidos | `crear(actorId, DatosTareaInicial): pedidoId` (`carga_inicial = true`, valida como el alta salvo solicitante y fecha de pedido, **no** dispara `PedidoAsignado`) | RN-IMP-32…37 |

`Shared\Adjuntos\AdjuntosService` ya recibe el actor por parámetro (TL-07), así que los links se asocian desde la consola.

## 12. Arranque: lo que el sistema necesita (F-62 y lo que F-63 pide del sistema)

### 12.1 Antes de cargar

- **RN-IMP-44** **Respaldo y deshacer:** antes de **cada** `--confirmar`, NexoSmart hace un respaldo de la base de
  producción. **Hasta la apertura** (nadie usó el sistema todavía), una carga mal hecha se deshace **restaurando ese
  respaldo** y confirmando de nuevo. **Después de la apertura** no se restaura nada (se perdería lo que cargó el equipo):
  se corrige con las pantallas de siempre: la carga inicial de gastado o de retirado se anula y se vuelve a importar
  (RN-IMP-27); una tarea se edita (W-32) o se completa con "Cargada por error en la carga inicial"; una obra se edita
  (W-23); un material se corrige en W-48 (no se quita si tiene retirado, RN-REN-53). *(técnica; RN-GAS-41 "no se borran
  gastos")*
- **RN-IMP-45** **Entorno:** el arranque es en **producción, con la base limpia** (propuesta DU-I01): lo cargado en las
  pruebas de las semanas 1 a 3 (entorno de prueba) **no** pasa. El primer usuario lo crea NexoSmart con
  `identidad:crear-administrador` (ADR-015); **gerencia carga los usuarios en W-39** de producción, cada uno con su sector
  y su jefatura (F-58; ~13 personas, 11 sectores, Pañol y Logística sin usuario por ahora); si en las pruebas cambiaron
  algo de la tabla de permisos (W-15), lo repiten en producción. Las obras que cargaron en la prueba entran por la hoja
  Obras (NexoSmart puede copiarlas a la plantilla para que administración solo revise).
- **RN-IMP-46** **Proveedores:** no se importan (propuesta DU-I04): el gastado entra por rubro con el proveedor genérico y
  cada proveedor se da de alta con su primera factura (W-38, con el aviso de parecidos, RN-PRV-11). La "lista de
  equivalencias de proveedores" de DU-A19 no se usa porque no hay proveedores en la carga.

### 12.2 Secuencia (D = día de la apertura)

| Cuándo | Quién | Qué | Listo si |
|---|---|---|---|
| D−5 | NexoSmart | Producción con la versión de la semana 4, migraciones, primer usuario, módulos encendidos (las cuatro entregas) | `/api/health` 200; entra el primer usuario |
| D−5 | Gerencia | Usuarios por sector en W-39 (y la tabla de permisos si la cambiaron) | cada persona de la hoja Personas tiene usuario activo |
| D−5 | NexoSmart | Primera versión de las plantillas desde la minuta y las rendiciones (en su equipo, sin versionar) → carpeta de Drive | — |
| D−4…D−2 | Administración y sectores | Completan faltantes, aprueban la hoja Rubros, completan "Para completar por sector" | — |
| D−4…D−2 | NexoSmart | Ensayos → informes, las veces que haga falta | informe sin faltantes (o aceptados por administración) |
| D−1 (después del corte) | Administración | Pone el gastado y el retirado **al corte**; aprueba por mail el último informe de ensayo de cada lote (con su huella) | mail recibido |
| D−1 | NexoSmart | Respaldo → confirma obras → respaldo → confirma tareas → informes finales | §12.3 |
| D | NexoSmart | Pasa el link al equipo; la minuta y las rendiciones en Excel quedan de consulta (cap. 14) | todos entran |

### 12.3 Qué mira QC antes de la apertura (conteos contra el origen)

- **Tareas:** filas abiertas de la minuta = tareas importadas + filas con faltantes (cada una con su motivo) + filas que
  administración decidió dejar afuera. Ninguna diferencia sin explicar.
- **Obras:** las obras En ejecución de su lista están todas, o con su faltante en el informe.
- **Gastado:** por obra y rubro, el gastado de W-11 **al centavo** igual a su hoja "General" al corte (la hoja "Control
  por rubro" del informe dice de qué filas viene cada número; EPP se ve dentro de INSUMOS como en su General).
- **Retirado:** en W-48, "Resta llevar" de cada material igual a su planilla de retiro al corte.
- **Personas:** cada propietario de una tarea importada entra al sistema y la ve en Mis tareas (en PC y en el celular).
- **Sin avisos:** la campana de los propietarios no tiene avisos de asignación de la carga y no salió ningún mail.

### 12.4 F-63: lo que el sistema necesita y la guía por sector

F-63 es servicio de NexoSmart, no software: no hay pantalla de capacitación ni "modo tutorial". Lo que el sistema tiene
que tener el día D: cada persona con su usuario, su sector y su jefatura (RN-IMP-45), sus tareas abiertas en Mis tareas
(RN-IMP-33), y las obras en ejecución con su rendición al día (RN-IMP-23). Como **entregable de documentación** (no
pantalla) se propone una **guía de una página por sector** en `docs/entregas/semana-4/` ("Tu primer día": qué ves al
entrar, cómo cargar una tarea, cómo tomarla y completarla, y para administración y compras cómo cargar una factura y leer
la rendición), con capturas de datos ficticios. El cierre del acompañamiento se mide como dice el plan de pruebas
(criterio n.º 4): **dos semanas seguidas sin filas nuevas en la minuta**.

## 13. Caso de referencia (test de aceptación, datos ficticios)

**Usuarios:** Carla (Jefatura de Administración, aprueba), Laura (Ingeniería de proyectos), Diego (Compras), Sofía
(Calidad); hoja Personas: "Cari" **sin** fila, "Diego" → diego@ejemplo.com, "Laura" → laura@ejemplo.com. Plazos 0–1 / 2–7 /
8+. Hoy = 30/11/2026.

**Lote obras:**

| Obra | Hoja Obras | Gastado (como figura → importe) | Materiales | Resultado |
|---|---|---|---|---|
| 1-200-2026 "Ampliación de nave de ensayo", Planta Ejemplo SA, Laura, En ejecución, corte 30/11/2026, total según su planilla 2.347.500,50 | completa; presupuestos como `rendicion.md` §9.1 (principal por rubro 11.150.000, A1 solo total 1.000.000) | MATERIALES 1.250.000 · INSUMO 80.000 · INSUMOS 45.500,50 · EPP 12.000 · LOGÍSTICA 60.000 · HORAS LIQUIDADAS 900.000 | Cemento x 50 kg · bolsa · 150 · 9.800 · retirado 96 — Hormigón H-21 · m³ · 40 · 100.000 · retirado 12 — Malla sima · paño · 30 · 21.000 · retirado 0 | **Entra.** Gasto carga inicial con 5 renglones: MATERIALES 1.250.000 · INSUMOS 125.500,50 · EPP 12.000 · LOGISTICA 60.000 · HORAS LIQUIDADAS 900.000 (= 2.347.500,50). W-11: INSUMOS · incluye EPP **137.500,50**; total gastado **2.347.500,50**. Gasto de retirado con 2 renglones en 0. W-48: "Hasta 30/11/2026 · Carga inicial" Cemento 96 (resta 54), Hormigón 12 (resta 28), Malla 0 (resta 30). |
| 2-131-2026 "Piso industrial nave 3", En ejecución, sin mail del comprador | falta el mail | VARIOS 15.000 (sin fila en Rubros) | — | **No entra**: 2 faltantes ("Falta el mail del comprador.", "El rubro 'VARIOS' no tiene equivalencia aprobada…"). |
| 2-140-2026 "Reparación de losa tanque B", Confirmada | completa, sin presupuestos | — | — | **Entra** Confirmada; aviso "no tiene presupuesto por rubro". |

Hoja Rubros: INSUMO → INSUMOS (Sí, Carla) · LOGÍSTICA → LOGISTICA (Sí, Carla).

**Lote tareas:**

| Clave | Fila | Resultado |
|---|---|---|
| 1-200-2026 · fila 2 | Documento, Certificado "Certificado N°1", 15/11/2026, 1 link | Entra a Documentos de 1-200-2026. |
| 1-200-2026 · fila 8 | "Solicitud de materiales N°3", Diego, En curso, PRIORITARIO, entrega 03/12/2026, sin solicitante ni fecha de pedido | Entra: Prioritario, no manual, "Solicitante: —", "Pedida: —", marca "Carga inicial". |
| 1-200-2026 · fila 9 | "Alquiler de minipala", propietario Laura, solicitante Diego, Bloqueada, Notas "esperando presupuesto", pedida 20/11, entrega 25/11, prioridad vacía | Entra Bloqueada con ese motivo, Prioritario calculada, **vencida**. |
| 1-200-2026 · fila 10 | Completada | No se importa. |
| 1-200-2026 · fila 11 | Diego, No iniciada, sin entrega | Faltante "Falta la fecha de entrega." (sector Compras). |
| 1-200-2026 · fila 12 | Propietario "Cari" | Faltante "No encontramos a 'Cari'…" (sector Administración). |
| 2-131-2026 · fila 4 | obra no importada | Faltante "La obra 2-131-2026 no está en el sistema…". |
| Servicios generales · fila 3 | "Service camioneta", Sin obra, "(en el sector)" Compras, No iniciada, entrega 04/12/2026 | Entra en el sector Compras (sin propietario). |

**Se espera:** ensayo de obras "3 obras: 2 entran, 1 con faltantes (2)", base sin cambios; confirmar y volver a confirmar
el mismo archivo = 0 duplicados; ensayo de tareas "8 filas: 3 tareas y 1 documento entran, 1 completada no se importa, 3
con faltantes"; después de confirmar, **0 avisos y 0 mails**, Mis tareas de Diego con la fila 8 y la de Servicios
generales (por su sector), y la rendición de 1-200-2026 con los números de arriba **al centavo**. Es el test
`CargaInicialCasoDeReferenciaTest` (feature) y su E2E de lectura (`carga-inicial.spec.ts › la obra importada muestra su
rendición y la tarea su marca`).

## 14. Trazabilidad F-XX → RN → test

| F-XX | Reglas | Tests (PHPUnit `tests/Feature/Importacion/…`, `tests/Unit/Importacion/…`) |
|---|---|---|
| F-62 (método) | RN-IMP-01…10 | `test_ensayo_no_guarda_nada_ni_dispara_eventos` · `test_confirmar_sin_ensayo_del_mismo_archivo_no_guarda` · `test_confirmar_exige_aprobo_activo_con_permiso` · `test_importar_dos_veces_no_duplica` · `test_una_obra_que_falla_no_deja_nada_y_la_corrida_sigue` · `test_una_corrida_a_la_vez` · `test_lo_importado_figura_cargado_por_quien_aprobo` |
| F-62 (datos) | RN-IMP-11…14 | `test_falta_columna_obligatoria_no_empieza` · `test_solo_lee_archivos_de_la_carpeta_de_importacion` · `test_logs_no_tienen_valores_de_celdas` · Unit `LectorPlantillaTest`: `test_fechas_de_excel_y_texto`, `test_importe_formato_argentino`, `test_mas_de_dos_decimales_es_faltante` |
| F-62 (rubros, personas) | RN-IMP-15…17 | `test_rubro_mal_escrito_queda_para_revision` · `test_equivalencia_no_aprobada_no_se_usa` · `test_tilde_tambien_pide_equivalencia` · `test_historial_del_gasto_dice_las_equivalencias` · `test_propietario_sin_usuario_es_faltante` · `test_solicitante_no_encontrado_queda_vacio` |
| F-62 (obras) | RN-IMP-18…22 | `test_obra_incompleta_no_carga_nada` · `test_obra_entra_en_ejecucion_con_historial` · `test_obra_existente_no_se_toca` · `test_presupuesto_por_rubro_y_total_mezclado_es_faltante` · `test_obra_sin_presupuesto_entra_con_aviso` |
| F-62 (gastado) | RN-IMP-23…27 | `test_saldo_inicial_por_rubro_suma_en_la_rendicion` · `test_formas_del_mismo_rubro_se_suman_en_un_renglon` · `test_suma_distinta_del_total_de_su_planilla_es_faltante` · `test_renglones_hasta_el_corte_bloquean_por_doble_conteo` · `test_renglones_despues_del_corte_no_bloquean` · `test_carga_inicial_no_se_corrige_se_anula` · `test_anulada_permite_volver_a_importar` |
| F-62 · F-66 (retirado) | RN-IMP-28…31 | `test_retirado_entra_con_importe_cero_y_no_suma` · `test_resta_llevar_da_bien_desde_el_primer_dia` · `test_w48_columna_hasta_corte_carga_inicial` · `test_material_existente_no_se_duplica` |
| F-62 (tareas) | RN-IMP-32…37 | `test_importar_minuta_trae_solo_pedidos_abiertos` · `test_sin_entrega_o_sin_propietario_queda_para_el_sector` · `test_bloqueada_usa_notas_como_motivo` · `test_en_curso_sin_propietario_es_faltante` · `test_prioridad_de_la_minuta_calculada_o_normal` · `test_prioridad_distinta_de_la_calculada_queda_manual` · `test_importar_no_genera_avisos_ni_mails` · `test_tarea_de_obra_terminada_es_faltante` |
| F-62 (documentos) | RN-IMP-38 | `test_documento_va_a_documentos_de_la_obra` · `test_documento_sin_link_es_faltante` |
| F-62 (pantallas) | RN-IMP-39…43 | `test_recorrido_dice_carga_inicial_desde_la_minuta` · `test_detalle_trae_carga_inicial` · front `DetalleTarea.test.tsx › muestra_la_marca_carga_inicial` · E2E `carga-inicial.spec.ts` |
| F-62 (caso de referencia) | §13 | `CargaInicialCasoDeReferenciaTest` |
| F-62 / F-63 (arranque) | RN-IMP-44…46, §12 | checklist de §12.2 y conteos de §12.3 (QC, no automatizado) |

## 15. Dudas

Resueltas que se aplican tal cual: DU-20, DU-21, DU-R09, DU-P10, DU-14, DU-A19, DU-C01, DU-G13. Nuevas (abiertas, en
`docs/dudas.md` como **DU-I01…DU-I09**; las reglas se escriben con la propuesta):

| Duda | Propuesta en una línea | Reglas |
|---|---|---|
| DU-I01 · Dónde se arranca | Producción con la base limpia; lo de las pruebas no pasa | RN-IMP-45, caso 10 |
| DU-I02 · Fecha de corte | Una por obra (el último día que tiene al día su rendición), no posterior a hoy; lo posterior se carga en el sistema | §5.1, RN-IMP-23, 26 |
| DU-I03 · Tareas que el sector no completa | No entran; quedan en el informe y se cargan a mano | RN-IMP-33 |
| DU-I04 · Proveedores | No se importan; se dan de alta con la primera factura | RN-IMP-46 |
| DU-I05 · Avisos al importar | Ninguno; el recordatorio "vence mañana" sí, por su fecha | RN-IMP-37 |
| DU-I06 · Lista de materiales | Entra por la plantilla, junto con lo retirado | §5.5, RN-IMP-28 |
| DU-I07 · Obras que no están en ejecución | Confirmadas entran (para sus tareas); de Terminadas no entra nada | §5.1, RN-IMP-18, 33 |
| DU-I08 · Qué documentos de la minuta | Todas las filas documento de las obras que entran, con link, sin importar su estado | RN-IMP-38 |
| DU-I09 · Corregir la carga inicial | No se edita; se anula y se vuelve a importar | RN-IMP-27 |

## 16. Alineación con otras especificaciones (para quien las mantiene; no se cambian acá)

| Dónde | Dice hoy | Lo que pide esta especificación |
|---|---|---|
| `gastos.md` §4.3, fila "Carga inicial" | cantidad 1, material "—", un gasto | Dos cargas iniciales por obra: **gastado** (cantidad 1, precio = importe, rubro cualquiera de los 13) y **retirado** (cantidad = retirado, precio 0, rubro MATERIALES, **material obligatorio**) (RN-IMP-23, 29, DU-R09). |
| `gastos.md` RN-GAS-37 | se corrige cualquier gasto no anulado | Salvo `origen = saldo_inicial`: solo se anula (RN-IMP-27, propuesta DU-I09). |
| `gastos.md` §12.4 | sin contrato de carga inicial | `Gastos\Contracts\CargaInicialDeGastos` (§11.4). |
| `rendicion.md` RN-REN-50 | 6 fechas más recientes + "Hasta {fecha}" | Primera columna fija "Hasta {corte} · Carga inicial" fuera de las 6 (RN-IMP-30). |
| `rendicion.md` §12.4.3 | `MaterialesDeObra` sin alta en la lista | `agregarALaLista` (§11.4). |
| `pedidos.md` §4.6 | "Fecha de entrega y propietario los completa el sector antes del arranque" | Sin completar, no entran (propuesta DU-I03); "(en el sector)" vale salvo En curso (RN-IMP-34); prioridad manual si difiere de la calculada (RN-IMP-35); marca en el detalle (RN-IMP-40). |
| `pedidos.md` / `avisos.md` | — | La carga inicial no dispara `PedidoAsignado` (RN-IMP-37, propuesta DU-I05). |
| `proveedores.md` RN-PRV-11 | "La carga inicial entra depurada, con una lista de equivalencias de proveedores" | No hay proveedores en la carga inicial (propuesta DU-I04). |
| `modelo-datos.md` | Importacion "(registro de corridas)" sin detalle | `importacion_corridas`, `importacion_filas` (§11.3). |
| `qa/matriz-trazabilidad.md` F-62 | "spec pendiente" | Reglas y tests de §14. |

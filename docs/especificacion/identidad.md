# Especificación funcional · Identidad (ingreso, usuarios, sectores, permisos y plazos)

> Módulo `app/Modules/Identidad` · Entrega: **semana 1** · Rol: Analista funcional · 2026-11-12
> Contrato: documento firmado v1.1 (cap. 3, 5, 11, 13) + maquetas W-15, W-16, W-17, W-19, W-39, W-40, W-50.
> Decisiones técnicas: ADR-006 (matriz), ADR-009 (autenticación), ADR-010 (auditoría), ADR-011 (idempotencia),
> ADR-014 (adjuntos: archivo o link; acá solo sus permisos, §6.6).
> Lo "Propuesto" del documento está **aprobado**. Lo que no está en el documento va a `docs/dudas.md` (DU-A..).
> **Dudas resueltas por Juani el 12-nov-2026** (va la propuesta en todas; DU-13 con el texto firmado):
> las reglas que dependían de una duda son definitivas y citan "resuelta DU-XX".
> Todos los nombres, mails y obras de este archivo son **ficticios** (los de las maquetas).

## 1. Propósito

Que cada persona entre al sistema con su usuario, vea solo lo que su sector tiene habilitado y que
la empresa (Dirección y las dos gerencias) decida y cambie cuando quiera **quién entra, en qué
sector está, de quién depende y qué puede ver o editar cada sector**. "Hoy son fijos… se puede
personalizar" (16-oct). Además, gerencia define los **plazos de la prioridad** de los pedidos.

## 2. Funcionalidades y pantallas cubiertas

| F-XX | Qué | Pantalla | Semana 1 |
|---|---|---|---|
| F-01 | Ingreso con usuario y contraseña; recuperación por mail | W-16 | completa |
| F-02 | Menú según los permisos del sector | W-17 (y barra inferior W-41) | completa (solo módulos entregados, resuelta DU-A12) |
| F-10 | Mi perfil: mis datos, mi sector, cambiar contraseña, mis avisos por mail | W-19 | **sin "Mis avisos"** (se activa en semana 2 con Pedidos) |
| F-58 | Usuarios: alta, edición y baja (activo/inactivo), con sector y jefatura | W-15 · W-39 | completa |
| F-59 | Sectores: agregar, renombrar, sacar | W-40 | completa |
| F-60 | Tabla de permisos sector × función: editar / ver / sin acceso (+ alcance) | W-15 | completa |
| F-61 | Visibilidad por jerarquía | W-01 · W-26 | **solo el dato** "de quién depende" y el cálculo de "mi gente"; las pantallas que lo usan son de semana 2 |
| F-68 | Plazos de la prioridad | W-50 | completa (los pedidos que la usan llegan en semana 2) |

## 3. Glosario (palabras del cliente → código)

| Cliente | Código | Qué es |
|---|---|---|
| Usuario | `User` / `users` | Una persona con mail de la empresa. No hay usuarios compartidos (W-16). |
| Sector | `Sector` / `sectores` | Caja del organigrama (Compras, Ingeniería de proyectos…). Se le asignan los permisos. |
| Depende de / jefatura | `users.jefe_id` | La persona de la que depende. Base de "mi gente" y del "superior". |
| Mi gente | `MiGente` (query) | Todas las personas que dependen de mí, directa o indirectamente (recursivo). |
| Tabla de permisos | `permisos` | Matriz sector × función. Cada celda: nivel + alcance. |
| Función (fila de la tabla) | `funciones` | Lo que se habilita: Pedidos, Obras, Rendición… (catálogo cerrado, §6.1). |
| Editar / Ver / Sin acceso | `editar` / `ver` / `sin_acceso` | Nivel de la celda. Editar incluye ver. |
| Todas / solo sus obras | `todas` / `propias` | Alcance de la celda (ADR-006). "Sus obras" = obras donde la persona es responsable de obra; en pedidos = los que pidió, tiene, sigue o son de su gente. |
| Activo / Inactivo | `users.activo` | Inactivo no entra, pero no se borra: su historial queda (W-39). |
| Plazos de la prioridad | `plazos_prioridad` | Hasta cuántos días de anticipación un pedido es urgente o prioritario (W-50). |

## 4. Datos

### 4.1 Usuario (`users`) — W-39, W-15, W-19

| Campo | Tipo | Oblig. | Validación | Fuente |
|---|---|---|---|---|
| nombre | texto ≤ 120 | Sí | sin espacios al borde; no vacío | W-39 "Nombre *" |
| email | mail ≤ 190 | Sí | formato mail; se guarda en minúsculas y sin espacios; **único** entre todos los usuarios (activos e inactivos) | W-39 "Mail *", W-16 |
| sector_id | ref. sector activo | Sí | el sector existe y está activo | W-39 "Sector *" |
| jefe_id | ref. usuario | No | no puede ser él mismo; no puede generar un ciclo (RN-IDE-22); al asignar, el jefe debe estar activo | W-39 "Depende de (jefatura)", F-58 |
| telefono | texto ≤ 50 | No | libre (se muestra "(opcional)") | W-39, W-19 |
| activo | booleano | Sí (default `true`) | — | W-39 "Activo o inactivo" |
| password | hash | Sí (la elige la persona) | `Password::defaults()` mínimo 10 caracteres (ADR-009) | F-01 |

El usuario **no** tiene rol propio: lo que puede hacer sale de la celda de su sector (F-60).
"Dirección ve todo" (F-61) se resuelve con la configuración de la matriz (Dirección con alcance
`todas` en todo) y, para la visibilidad de tareas de semana 2, con `sectores.es_direccion`
(modelo-datos).

### 4.2 Sector (`sectores`) — W-40

| Campo | Tipo | Oblig. | Validación | Fuente |
|---|---|---|---|---|
| nombre | texto ≤ 80 | Sí | único sin distinguir mayúsculas ni tildes ("Compras" = "compras" = "Cómpras") | W-40 |
| activo | booleano | Sí | "sacar" = archivar (RN-IDE-31) | W-40 |
| es_direccion | booleano | Sí | solo el sector Dirección (por migración) | F-61 |

Columnas de W-40: **Sector · Usuarios** (cantidad de usuarios activos) **· renombrar · sacar**.

### 4.3 Permiso (`permisos`) — W-15

| Campo | Tipo | Oblig. | Validación |
|---|---|---|---|
| sector_id | ref. sector | Sí | — |
| funcion | clave del catálogo §6.1 | Sí | debe existir |
| nivel | `sin_acceso` · `ver` · `editar` | Sí | `editar` solo si la función lo admite (§6.1) |
| alcance | `todas` · `propias` | Sí | `propias` solo si la función lo admite; si no, siempre `todas` |

Único por (sector, función). Hay **siempre** una celda por cada sector activo × función.

### 4.4 Plazos de la prioridad (`plazos_prioridad`) — W-50

| Campo | Tipo | Oblig. | Validación | Valor inicial (por migración) |
|---|---|---|---|---|
| urgente_hasta_dias | entero | Sí | 0 ≤ n ≤ 365 | **1** (cap. 4.2: "arrancan en hasta 1 día") |
| prioritario_hasta_dias | entero | Sí | `urgente_hasta_dias` < n ≤ 365 | **7** ("hasta 7 días") |
| (normal) | — | — | "más de `prioritario_hasta_dias`" (no se carga: se deriva) | "más de 7" |
| updated_by, updated_at | — | — | — | — |

## 5. Reglas de negocio

### Ingreso y recuperación (F-01, W-16)

- **RN-IDE-01** Se entra con **mail de la empresa + contraseña**. Cada uno con su usuario; no hay usuarios compartidos. *(F-01, W-16, ADR-009)*
- **RN-IDE-02** "Recordarme en este equipo" mantiene la sesión abierta en ese navegador después de cerrarlo (cookie *remember*). Sin tildarlo, la sesión vence por inactividad (`SESSION_LIFETIME`, 120 min). *(W-16)*
- **RN-IDE-03** Si mail o contraseña no coinciden, el mensaje es siempre el mismo: **"Mail o contraseña incorrectos."** (no revela si el mail existe). *(ADR-009)*
- **RN-IDE-04** Un usuario **inactivo no entra**, aunque la contraseña sea correcta: "Tu usuario está inactivo. Si lo necesitás, pedíselo a gerencia." *(W-39 "Un usuario inactivo no entra")*
- **RN-IDE-05** Máximo **5 intentos de ingreso por minuto** por mail + IP; el sexto devuelve 429 "Demasiados intentos. Probá de nuevo en un minuto." *(ADR-009)*
- **RN-IDE-06** "Olvidé mi contraseña": la persona escribe su mail y el sistema responde **siempre** "Te mandamos un mail a {mail} para elegir una nueva." exista o no el usuario; solo se manda el mail si el usuario existe y está activo. Máximo 3 pedidos por hora por mail. *(W-16, ADR-009)*
- **RN-IDE-07** El link del mail sirve **una sola vez** y vence a los **60 minutos**. Vencido o usado: "El link venció. Pedí uno nuevo desde 'Olvidé mi contraseña'." *(ADR-009)*
- **RN-IDE-08** Al elegir la contraseña nueva se cierran las demás sesiones de esa persona y queda adentro. *(técnica, seguridad)*
- **RN-IDE-09** Un usuario recién dado de alta recibe un mail para **elegir su contraseña** (mismo flujo y mismo link que la recuperación, con vencimiento de 7 días). Si no lo usó a tiempo, usa "Olvidé mi contraseña" o gerencia se lo **reenvía** desde W-15 con el botón "Reenviar mail de bienvenida" (manda un link nuevo de 7 días e invalida el anterior; solo para usuarios activos que todavía no eligieron contraseña). Nadie (ni gerencia) ve ni escribe contraseñas ajenas. *(ADR-009 · resuelta DU-03 · W-16 "Si no tenés usuario, pedíselo a gerencia")*
- **RN-IDE-10** Los mails de ingreso (bienvenida y recuperación) son **de la cuenta**, no "avisos": salen desde la semana 1 aunque los avisos por mail (F-09) se activen en semana 2. Van por cola, desde noreply con reply-to real. *(ADR-012)*

### Menú según permisos (F-02, W-17, W-41)

- **RN-IDE-11** La barra es **una sola para todos**, en este orden: Inicio · Pedidos · Obras · Calendario · Gastos · Rendiciones · Proveedores · Usuarios. Cada ítem aparece solo si la celda del sector de la persona lo habilita (tabla §6.4). *(W-17, decisiones Sprint 0 n.º 14)*
- **RN-IDE-12** Si la persona solo tiene **ver** en una función que tiene carga (Gastos, Rendiciones), el ítem se muestra con la marca **"(ver)"** y la pantalla no muestra botones de carga ni edición. *(W-17: "Gastos (ver)", "Rendiciones (ver)")*
- **RN-IDE-13** "Tareas de mi gente" y "Carga por persona" (dentro de Pedidos) **no son filas de la matriz**: aparecen a quien **tiene gente a cargo** (al menos un usuario activo que dependa de él). *(W-17 "Gerencias y jefaturas ven además…", F-06, F-12, F-61)*
- **RN-IDE-14** El menú es comodidad, no seguridad: **cada endpoint** vuelve a verificar la celda (403 si no corresponde). Un cambio en la matriz vale desde el **próximo pedido** al servidor; el front vuelve a pedir `GET /api/me` al enfocar la pestaña y al recibir un 403. *(ADR-006)*
- **RN-IDE-15** En el celular la barra de abajo es **Tareas · Obras · Avisos · Más**; "Más" abre el resto de los ítems de RN-IDE-11 que la persona puede ver. *(W-41)*

### Mi perfil (F-10, W-19)

- **RN-IDE-16** La persona puede cambiar su **nombre** y su **teléfono**. Ve, sin poder cambiarlos, su **mail**, su **sector** y **de quién depende** ("Hernán (Proyectos)"): "Sector y jefatura los cambia gerencia". *(W-19)*
- **RN-IDE-17** Cambiar la contraseña pide la **actual**, la nueva y su repetición; la nueva cumple RN de §4.1 y no puede ser igual a la actual. Cierra las demás sesiones. *(W-19, ADR-009)*
- **RN-IDE-18** **Semana 1:** la sección "Mis avisos" no se muestra. Se activa en semana 2 con: avisos dentro del sistema "siempre", por mail solo la asignación (y la reasignación) tildada por defecto, y cada persona puede tildar más. *(F-09, F-10, W-19; fuera de alcance de esta entrega)*

### Usuarios (F-58, W-15, W-39)

- **RN-IDE-19** Dan de alta, editan y desactivan usuarios quienes tengan **Usuarios y permisos = Editar** (configuración inicial: Dirección, Gerencia de Operaciones, Gerencia de Administración). *(F-58, W-39)*
- **RN-IDE-20** **No se borra a nadie**: la baja es pasar a **Inactivo**. Sus pedidos, obras e historial quedan como están, con su nombre. Se puede volver a activar. *(W-39)*
- **RN-IDE-21** El listado (W-15) muestra **Nombre · Mail · Sector · Activo**; por defecto solo activos, con un filtro "ver inactivos". Busca por nombre o mail. *(W-15)*
- **RN-IDE-22** "Depende de" es opcional (Dirección no depende de nadie). No puede ser la misma persona ni armar un círculo (si Laura depende de Hernán, Hernán no puede depender de Laura ni de nadie que dependa de Laura): "Así se arma un círculo: {nombre} ya depende de {nombre}." *(F-58, F-61)*
- **RN-IDE-23** **Mi gente** = todas las personas que dependen de mí, directa o indirectamente (Dirección → Gerencia → Jefatura → Ingeniería). El **superior** de una persona es su jefe **directo**. El aviso de "completado" de semana 2 va al solicitante y al **superior del solicitante** (`jefe_id` de quien pidió, no de quien completó); si el solicitante no tiene jefatura, no hay aviso extra. Identidad lo expone en `Identidad\Contracts\Jerarquia::superiorDe(UserId): ?UserId`. *(F-61, cap. 4.2 y F-31 "a su superior", decisiones Sprint 0 n.º 6, resuelta DU-13 con el texto firmado)*
- **RN-IDE-24** Al desactivar a alguien que tiene gente a cargo u obras como responsable, la pantalla avisa cuántas ("Hernán tiene 2 personas a cargo y es responsable de 3 obras. Quedan como están hasta que las cambies.") y **no bloquea**. *(resuelta DU-A08)*
- **RN-IDE-25** Nadie se puede desactivar a sí mismo, y el sistema no deja que quede **ningún usuario activo con Usuarios y permisos = Editar** (por desactivar, por cambiar de sector o por cambiar la matriz): "Tiene que quedar al menos una persona que pueda administrar usuarios y permisos." *(técnica, resuelta DU-A09)*
- **RN-IDE-26** El mail no se restringe a un dominio; queda la variable opcional `MAIL_DOMINIOS_PERMITIDOS` (vacía = sin restricción). *(resuelta DU-A11)*
- **RN-IDE-27** El cambio de sector de un usuario cambia sus permisos desde el próximo pedido al servidor (RN-IDE-14). No cambia sus pedidos ni sus obras.

### Sectores (F-59, W-40)

- **RN-IDE-28** Arrancan, por migración, los **11 sectores** de §6.2 (organigrama). Supervisión de obra **no** es sector: no usa el sistema (cap. 3). *(cap. 3, análisis del organigrama, resueltas DU-01 y DU-A01)*
- **RN-IDE-29** Agregar, renombrar y sacar sectores: quien tenga **Usuarios y permisos = Editar**. *(F-59: "Dirección y las dos gerencias")*
- **RN-IDE-30** Un sector **nuevo** aparece como una fila más en la tabla de permisos con: **Pedidos = Editar·todas**, **Equipos en reparación = Editar·todas** (igual que Pedidos), **Obras = Ver·todas**, el resto **Sin acceso**. *(W-40, cap. 3 "Todos pueden pedir", cap. 6 "Quién: Todos"; resuelta DU-A06)*
- **RN-IDE-31** **Sacar** un sector: solo si **no tiene usuarios activos** ("Antes de sacar Pañol y Logística, pasá a sus 2 usuarios a otro sector."). Si nunca se usó (sin usuarios ni pedidos) se borra; si tiene historia se **archiva**: sale de los desplegables y de la matriz, y lo histórico conserva su nombre. *(resuelta DU-A07)*
- **RN-IDE-32** Renombrar no cambia nada más: usuarios, permisos y pedidos siguen en el mismo sector.
- **RN-IDE-33** "Pañol y Logística" arranca **sin usuarios**: existe y se le da usuario cuando quieran. *(cap. 3, W-15, W-40)*

### Tabla de permisos (F-60, W-15)

- **RN-IDE-34** Para cada sector y cada función se elige **Editar, Ver o Sin acceso**, y en las funciones de obra además **todas** o **solo sus obras**. La cambian quienes tengan **Usuarios y permisos = Editar**, cuando quieran. *(F-60, W-15, ADR-006)*
- **RN-IDE-35** Editar incluye ver. Sin acceso oculta la función del menú y responde 403 en la API.
- **RN-IDE-36** El sistema arranca con la **configuración inicial de §6.3**, cargada **por migración** (idempotente por clave; no por seeder). *(cap. 3, decisiones Sprint 0 n.º 6)*
- **RN-IDE-37** Cada cambio de celda queda registrado en `actividades` (quién, cuándo, antes → después). No tiene pantalla en la Etapa 1. *(ADR-010, técnica)*
- **RN-IDE-38** Las funciones que no admiten editar o alcance no muestran esas opciones (p. ej. Plazos de la prioridad no tiene "solo sus obras").
- **RN-IDE-39** Reglas que **no son matriz** (viven en Policies y no se pueden quitar desde la tabla, porque las dice el documento): el responsable de obra cambia el estado de **su** obra (F-21); el propietario toma, bloquea y completa su pedido; quien lo pidió lo reabre (semana 2). *(ADR-006)*

### Plazos de la prioridad (F-68, W-50)

- **RN-IDE-40** Los cambian quienes tengan **Plazos de la prioridad = Editar** (inicial: las dos gerencias). *(F-68 "Gerencia")*
- **RN-IDE-41** Regla: con `d` = días **corridos** entre la fecha de pedido y la de entrega: `d ≤ urgente_hasta_dias` → **Urgente**; `d ≤ prioritario_hasta_dias` → **Prioritario**; si no → **Normal**. Si la entrega es anterior al pedido (`d < 0`), Urgente. Sin calendario de feriados. *(cap. 4.2, F-26, W-50; días corridos: resuelta DU-04)*
- **RN-IDE-42** El cambio vale para los **pedidos que se carguen desde ese momento** y **no recalcula** los ya cargados: la prioridad se guarda en el pedido. *(W-50, aprobado)*
- **RN-IDE-43** Si después se cambia la fecha de entrega de un pedido ya cargado (W-32, semana 2), su prioridad se recalcula con los plazos **vigentes al momento de esa edición**, salvo que haya sido corregida a mano: en ese caso se mantiene la manual y el formulario muestra la calculada como sugerencia. *(resuelta DU-A13)*
- **RN-IDE-44** El módulo Identidad expone `Identidad\Contracts\PlazosPrioridad::calcular(fechaPedido, fechaEntrega): Prioridad` para que Pedidos lo use en semana 2.

### Adjuntos: quién sube y quién baja (ADR-014)

Donde el documento firmado decía "link" para un documento, ahora se puede **subir el archivo o
poner el link** (ADR-014, amplía D-022). Los adjuntos no son una fila de la matriz: **heredan el
permiso del recurso dueño** (pedido, presupuesto, documento de la obra, gasto, retiro, equipo).

- **RN-IDE-45** **Subir** (`POST /api/adjuntos`) lo puede cualquier usuario **activo y autenticado**: el adjunto queda **sin dueño** (temporal) y solo lo ve quien lo subió. Si en 24 h no se asocia a un recurso, se purga (archivo físico incluido). *(ADR-014)*
- **RN-IDE-46** **Asociar** un adjunto (crear o editar el recurso con `adjuntos: [ids]`) exige **editar** en la función del recurso dueño (p. ej. `obras = editar` para un certificado; en un pedido, la regla del pedido). Solo se pueden asociar adjuntos temporales **subidos por la misma persona** y del **contexto** que corresponde al campo; si no: 422 "Ese adjunto no se puede usar acá." *(ADR-014, ADR-006)*
- **RN-IDE-47** **Bajar** (`GET /api/adjuntos/{id}`) exige poder **ver el recurso dueño**, con su alcance ("solo sus obras" incluido): si no podés ver el pedido, no podés bajar su comprobante. Sin permiso o con un id ajeno (IDOR): **404** (no confirma que exista). Un adjunto temporal solo lo baja quien lo subió. *(ADR-014, ADR-006)*
- **RN-IDE-48** **Quitar** un adjunto de un recurso exige el mismo permiso que editarlo; borra el archivo físico y queda en `actividades` (quién, cuándo, nombre del adjunto). Un gasto **anulado** conserva sus adjuntos. *(ADR-014, ADR-010)*

## 6. Permisos

### 6.1 Catálogo de funciones (filas de la tabla) — cargado por migración

Derivado del cap. 3 (configuración inicial), de las columnas de W-15 (Obras · Pedidos · Gastos y
facturas · Presupuesto por rubro · Rendición · Proveedores · Usuarios), de W-17 y de la columna
"Quién" de cada F-XX que dice "según permisos" o nombra a un sector. Se separaron en filas
propias las cosas que el documento asigna a **otra gente** que la del módulo que las contiene:

| Orden | Clave | Nombre en W-15 | Qué habilita (Ver / Editar) | Admite editar | Admite "solo sus obras" | Por qué es una fila | Entrega |
|---|---|---|---|---|---|---|---|
| 1 | `pedidos` | Pedidos | Ver: Todos los pedidos (W-24), Sin obra (W-25), calendario (W-03), pestaña Pedidos de la obra. Editar: crear, comentar, seguir, editar pedidos, con sus archivos o links y el comprobante de cierre (F-24…F-38, F-65; ADR-014). | Sí | Sí (los que pidió, tiene, sigue o de su gente) | Columna de W-15; F-35 "se puede restringir en la tabla de permisos"; F-33 "según la tabla de permisos" | 2 |
| 2 | `equipos_reparacion` | Equipos en reparación | Ver y editar la lista de equipos (W-49), con su presupuesto e inventario (archivo o link, ADR-014). | Sí | No | F-67 "Quién: según permisos" | 2 |
| 3 | `obras` | Obras | Ver: listado, ficha, documentos y contactos, historial (F-15, F-17, F-19, F-22). Editar: alta, edición, presupuestos adicionales, documentos (archivo o link), contactos, fechas previstas (F-16, F-18, F-19, F-20, F-23). | Sí | Sí (obras donde es responsable) | Columna de W-15; F-20 "según permisos" | **1** |
| 4 | `estado_obra` | Estado de obra | Editar: cambiar el estado de cualquier obra (W-22). El responsable de **su** obra puede siempre (RN-IDE-39). | Sí | No | F-21: lo cambian "responsable de obra y administración", distinto de quien edita la obra | **1** |
| 5 | `presupuesto_rubro` | Presupuesto por rubro | Ver: W-13 y la columna "Presupuestado". Editar: cargar y corregir montos por rubro o el total (F-48). | Sí | Sí | Columna de W-15; cap. 3 "lo carga administración" (no operaciones, que da de alta la obra) | **1** |
| 6 | `monto_obra` | Monto de obra y ganancia | Ver: monto de la obra, ganancia y % (W-11, ficha). Editar: cargar el monto de cada presupuesto. | Sí (resuelta DU-19) | Sí | Cap. 3 y F-54: "lo ven gerencias y administración", más restringido que la rendición | **1** (dato) / 3 (rendición) |
| 7 | `gastos` | Gastos y facturas | Ver: Gastos cargados (W-33), detalle (W-34), "Gastado por obra" del proveedor (W-14). Editar: cargar, corregir, anular, con la factura o los tickets como archivo o link (F-39…F-47; ADR-014). | Sí | Sí (renglones de sus obras) | Columna de W-15 | 3 |
| 8 | `rendicion` | Rendición | Ver: rendición de la obra, en curso, detalle de rubro, por proveedor (W-11, W-12, W-36, W-37, W-44). Editar: cargas que se hacen desde la rendición (W-46, horas y fijos por período). | Sí | Sí | Columna de W-15; cap. 3 "las ingenieras ven la rendición de sus obras" | 3 |
| 9 | `control_cantidad` | Control por cantidad | Ver: W-48. Editar: cargar materiales presupuestados y retiros con remito (archivo o link, ADR-014). | Sí | Sí | F-66: "edita administración · ve operaciones" | 3 |
| 10 | `proveedores` | Proveedores | Ver: base y ficha (W-14). Editar: alta y edición (W-38), alta rápida desde la factura (F-42). | Sí | No | Columna de W-15; F-55/F-56 "administración y compras" | **1** |
| 11 | `usuarios_permisos` | Usuarios y permisos | Ver: usuarios, sectores y tabla. Editar: alta/edición/baja de usuarios, sectores y la tabla (F-58, F-59, F-60). | Sí | No | Columna "Usuarios" de W-15 | **1** |
| 12 | `plazos_prioridad` | Plazos de la prioridad | Ver: W-50. Editar: cambiar los plazos (F-68). | Sí | No | F-68: "gerencia", distinto de quien administra usuarios | **1** |

No son filas: **Inicio / Mis tareas / Pedidos que pedí / Tareas que sigo / Mi perfil / Avisos**
(todos, F-04, F-05, F-07, F-10, F-64), **Tareas de mi gente / Carga por persona** (jerarquía,
RN-IDE-13) y **Buscador** (respeta los permisos de cada resultado, W-18).

### 6.2 Sectores iniciales (por migración)

| # | Sector | Del organigrama | Usuarios al arrancar |
|---|---|---|---|
| 1 | Dirección | Dirección General | los carga la empresa (semana 1) |
| 2 | Calidad | Aseguramiento de Calidad (staff de Dirección) | ídem |
| 3 | Gerencia de Operaciones | Gerencia de Operaciones | ídem |
| 4 | Jefatura de Proyectos | Jefatura de Proyectos | ídem |
| 5 | Ingeniería de proyectos | Ingeniería de Proyectos | ídem |
| 6 | Seguridad e Higiene | Seguridad e Higiene | ídem |
| 7 | Gerencia de Administración | Gerencia de Administración y Finanzas | ídem |
| 8 | Jefatura de Administración | Jefatura de Administración | ídem |
| 9 | Compras | Departamento de Compras | ídem |
| 10 | RRHH | nombrado en la reunión 16-oct (dentro de administración) | ídem |
| 11 | Pañol y Logística | Pañol y Logística Central | **sin usuario** (cap. 3) |

W-15/W-40 muestran 10 filas con nombres cortos ("Operaciones", "Proyectos", "Administración");
se separa Administración en Gerencia y Jefatura porque la celda de W-15 "Usuarios: Editar — solo
la gerencia" no se puede expresar por sector de otra forma (resuelta DU-01). Los nombres se pueden renombrar. Nombres: resuelta DU-A01.

### 6.3 Configuración inicial (sector × función)

Leyenda: **E** = Editar · **V** = Ver · **—** = Sin acceso · **·T** = todas · **·P** = solo sus obras.
**Negrita** = sale explícita del documento; *cursiva* = celda "a completar" en W-15 que arranca
según la DU indicada (todas resueltas el 12-nov: va la propuesta). Esta tabla es **definitiva**
para la migración.

| Sector | Pedidos | Equipos en reparación | Obras | Estado de obra | Presupuesto por rubro | Monto de obra y ganancia | Gastos y facturas | Rendición | Control por cantidad | Proveedores | Usuarios y permisos | Plazos de la prioridad |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| Dirección | **E·T** | *E·T* (DU-01) | **V·T** | **V** | **V·T** | **V·T** | **V·T** | **V·T** | **V·T** | **V** | **E** | **V** |
| Calidad | **E·T** | *E·T* (DU-01) | **V·T** | — | *—* (A02) | — | *—* (A02) | *—* (A02) | — | *—* (A02) | **—** | — |
| Gerencia de Operaciones | **E·T** | *E·T* (DU-01) | **E·T** | **V** + responsable | **V·T** | **V·T** | **V·T** | **V·T** | **V·T** | *V* (DU-02) | **E** | **E** |
| Jefatura de Proyectos | **E·T** | *E·T* (DU-01) | **E·T** | **V** + responsable | **V·T** | — | **V·T** | **V·T** | **V·T** | *V* (DU-02) | **—** | — |
| Ingeniería de proyectos | **E·T** | *E·T* (DU-01) | **E·T** | **V** + responsable | *V·P* (A04) | — | *V·P* (A04) | **V·P** | *V·P* (A04) | **—** (W-17) | **—** | — |
| Seguridad e Higiene | **E·T** | *E·T* (DU-01) | **V·T** | — | *—* (A02) | — | *—* (A02) | *—* (A02) | — | *—* (A02) | **—** | — |
| Gerencia de Administración | **E·T** | *E·T* (DU-01) | **E·T** | **E** | **E·T** | **E·T** | **E·T** | **E·T** | **E·T** | **E** | **E** | **E** |
| Jefatura de Administración | **E·T** | *E·T* (DU-01) | **E·T** | **E** | **E·T** | **E·T** | **E·T** | **E·T** | **E·T** | **E** | **—** ("solo la gerencia") | — |
| Compras | **E·T** | *E·T* (DU-01) | **V·T** | — | **E·T** | — | **E·T** | **E·T** | *E·T* (A05) | **E** | **—** | — |
| RRHH | **E·T** | *E·T* (DU-01) | **V·T** | — | *—* (A03) | — | *—* (A03) | *—* (A03) | — | *—* (A03) | **—** | — |
| Pañol y Logística | **E·T** | *E·T* (DU-01) | **V·T** | — | *—* (A02) | — | *—* (A02) | *—* (A02) | — | *—* (A02) | **—** | — |

De dónde sale cada columna:

- **Pedidos**: E·T para todos — cap. 3 "Todos pueden pedir y le pueden pedir a cualquiera… Todos ven todos los pedidos de todas las obras"; W-15 (Editar en todas las filas, incluida Pañol). Que cada uno vea en su inicio sus tareas y las de su gente es jerarquía (F-61), no matriz.
- **Equipos en reparación**: F-67 "según permisos" y no figura en W-15 → resuelta DU-01 (como hoy la solapa de la minuta: todos la ven y la tocan).
- **Obras**: Ver·T para todos — cap. 6, columna "Quién" de F-15, F-17, F-19, F-22 = "Todos". Editar — F-16 "Operaciones · también administración" y F-18 "Operaciones y administración"; cap. 3 "Gerencia de Operaciones · Jefatura de Proyectos · Ingeniería de proyectos: dan de alta las obras". Compras no es "administración" en el documento (siempre dice "administración y compras").
- **Estado de obra**: F-21 "Responsable de obra y administración" → E para Gerencia y Jefatura de Administración; el responsable de su obra lo cambia por regla fija (RN-IDE-39), por eso los sectores de operaciones quedan en V "+ responsable". Dirección V por "ve todo".
- **Presupuesto por rubro**: cap. 3 "Lo carga administración"; W-15 Editar para Administración y Compras y Ver para Operaciones, Proyectos e Ingeniería.
- **Monto de obra y ganancia**: cap. 3, F-54 "Lo ven gerencias y administración" → V para las dos gerencias, E para administración (quien la carga, F-54 "Quién: Administración"); Dirección V.
- **Gastos y facturas / Rendición**: cap. 3 "Editan administración y compras. Operaciones ve y no puede tocar nada; las ingenieras ven la rendición de sus obras"; W-15 y W-17 ("Gastos (ver)", "Rendiciones (ver)" para Ingeniería). Alcance de Ingeniería en Gastos: solo sus obras (resuelta DU-A04).
- **Control por cantidad**: F-66 "Edita administración · ve operaciones". Compras edita (resuelta DU-A05).
- **Proveedores**: F-55/F-56 "Administración y compras"; W-17 "Administración tiene todo lo de gastos, proveedores y rendición" y para Ingeniería "No ve Proveedores… mientras la tabla de permisos no se los dé" (por eso Ingeniería arranca en Sin acceso: DU-A02 ajusta DU-02, ambas resueltas). Gerencia de Operaciones y Jefatura de Proyectos: Ver, como DU-02 (ya ven los gastos).
- **Usuarios y permisos**: F-58/F-59/F-60 "Dirección y las dos gerencias"; W-15 "Administración: Editar — solo la gerencia".
- **Plazos de la prioridad**: F-68 "Gerencia" → las dos gerencias; Dirección V por "ve todo".
- **Dirección**: cap. 3 "Ve todo" y F-61 "Dirección ve todo" → V·T en todo lo que W-15 dejó "a completar"; E en Pedidos y Usuarios (W-15).
- **"a completar"** (W-15: "el resto de la tabla la completan ustedes"): arrancan en **Sin acceso** (resuelta DU-A02); RRHH sin acceso a Gastos, Presupuesto, Rendición y Proveedores (resuelta DU-A03).

### 6.4 Menú (F-02) — qué celda habilita cada ítem

| Ítem | Aparece si | "(ver)" si |
|---|---|---|
| Inicio | siempre | — |
| Pedidos | `pedidos` ≥ V (o `equipos_reparacion` ≥ V: entra directo a Equipos en reparación) | — |
| Obras | `obras` ≥ V | — |
| Calendario | `pedidos` ≥ V | — |
| Gastos | `gastos` ≥ V | `gastos` = V |
| Rendiciones | `rendicion` ≥ V | `rendicion` = V |
| Proveedores | `proveedores` ≥ V | — |
| Usuarios | `usuarios_permisos` ≥ V **o** `plazos_prioridad` ≥ V (solo ve las pestañas que le tocan: Usuarios · Sectores · Tabla de permisos · Plazos de la prioridad) | — |

Semana 1: los ítems de módulos todavía no entregados (Pedidos, Calendario, Gastos, Rendiciones) no se muestran aunque la celda los habilite, con una lista de módulos habilitados por configuración (no por la matriz); cada entrega los enciende (resuelta DU-A12).

### 6.5 Permisos de las pantallas de este módulo

| Acción | Función | Nivel | Alcance |
|---|---|---|---|
| Ingresar, recuperar contraseña | — (público) | — | — |
| Ver / editar mi perfil, cambiar mi contraseña | — (autenticado) | — | — |
| Listar usuarios, ver sectores y tabla | `usuarios_permisos` | ver | — |
| Alta, edición, activar/desactivar usuario, reenviar mail de bienvenida | `usuarios_permisos` | editar | — |
| Agregar, renombrar, sacar sector | `usuarios_permisos` | editar | — |
| Cambiar celdas de la tabla | `usuarios_permisos` | editar | — |
| Ver plazos de la prioridad | `plazos_prioridad` | ver | — |
| Cambiar plazos | `plazos_prioridad` | editar | — |
| Lista corta de usuarios activos / sectores (para desplegables de responsable, propietario, jefe) | — (autenticado) | — | — |

### 6.6 Permisos de los adjuntos (ADR-014)

| Acción | Quién | Si no |
|---|---|---|
| Subir un archivo o registrar un link (`POST /api/adjuntos`) → id temporal | cualquier usuario activo (RN-IDE-45) | 401 |
| Asociarlo al recurso (crear/editar con `adjuntos: [ids]`) | **editar** en la función del dueño, y el adjunto es temporal, propio y del contexto correcto (RN-IDE-46) | 403 sin permiso · 422 adjunto ajeno, ya usado o de otro contexto |
| Bajarlo (`GET /api/adjuntos/{id}`) | **ver** el recurso dueño, con su alcance (RN-IDE-47) | 404 |
| Quitarlo del recurso | **editar** el recurso dueño (RN-IDE-48) | 403 |

| Contexto (`adjuntos.contexto`) | Recurso dueño | Función para ver / editar | Semana |
|---|---|---|---|
| `presupuesto`, `aprobacion` | presupuesto de la obra | `obras` (alcance: obras donde es responsable) | **1** |
| `documento_obra` | documento de la obra (certificado u otro) | `obras` | **1** |
| `pedido`, `comprobante_cierre` | pedido | `pedidos` + reglas del pedido (completa el propietario) | 2 |
| `equipo_presupuesto`, `equipo_inventario` | equipo en reparación | `equipos_reparacion` | 2 |
| `comprobante_gasto` | gasto | `gastos` | 3 |
| `remito` | retiro | `gastos` (W-46) / `control_cantidad` (W-48) | 3 |

## 7. Estados y transiciones

**Usuario**: `activo` ⇄ `inactivo`. Lo cambia `usuarios_permisos = editar`, nunca sobre sí mismo
(RN-IDE-25). Inactivo: no entra, sus sesiones abiertas se cierran, no aparece en los desplegables
nuevos, pero sí con su nombre en todo lo histórico ("Hernán (inactivo)").

**Sector**: `activo` → `archivado` (sacar con historia) o borrado (sin historia). Un archivado no vuelve
desde la pantalla en la Etapa 1 (se agrega uno nuevo con el mismo nombre si hiciera falta: el
único se controla solo entre activos).

## 8. Avisos

Este módulo no genera avisos del sistema. Manda dos mails **de cuenta** (RN-IDE-10):

| Cuándo | A quién | Asunto | Contenido |
|---|---|---|---|
| Alta de usuario | al nuevo usuario | "Tu usuario en el sistema de Tecnoservicios" | quién lo dio de alta, su mail de ingreso y el botón "Elegir mi contraseña" (vence en 7 días) |
| Olvidé mi contraseña | al usuario activo | "Elegí una contraseña nueva" | botón "Elegir contraseña nueva" (vence en 60 min) y "Si no lo pediste, ignorá este mail." |

## 9. Casos borde (datos ficticios)

| # | Caso | Resultado esperado |
|---|---|---|
| 1 | Laura escribe `LAURA@empresa-ejemplo.com ` | Entra: el mail se compara en minúsculas y sin espacios. |
| 2 | Carla (inactiva) pone bien su contraseña | No entra: "Tu usuario está inactivo…" (RN-IDE-04). |
| 3 | Alguien pide recuperar `nadie@empresa-ejemplo.com` | Ve el mismo mensaje que si existiera; no se manda nada. |
| 4 | Gerencia da de alta a Pablo con `diego@empresa-ejemplo.com` (ya existe, inactivo) | 422 "Ese mail ya es de otro usuario (Diego, inactivo). Podés reactivarlo." |
| 5 | Gerencia pone que Hernán depende de Laura, y Laura ya depende de Hernán | 422 por círculo (RN-IDE-22). |
| 6 | El único usuario de Gerencia de Administración con Usuarios = Editar es Diego; Dirección y Gerencia de Operaciones no tienen usuarios todavía; Diego se pasa a Compras | 422 RN-IDE-25. |
| 7 | Gerencia cambia la celda Ingeniería × Rendición de V·P a V·T mientras Laura tiene abierta la rendición de una obra que no es suya | Desde el próximo refresco la puede ver; antes, la API la habría negado con 403. |
| 8 | Se saca "RRHH" que tiene 0 usuarios y nunca tuvo pedidos | Se borra; desaparece de W-40 y de la tabla. |
| 9 | Se saca "Calidad" con 1 usuario activo | 422 RN-IDE-31. |
| 10 | Se agrega el sector "Laboratorio" | Aparece en la tabla con Pedidos E·T, Equipos en reparación E·T, Obras V·T, resto Sin acceso. |
| 11 | Se renombra "Compras" a "compras " | 422: ya existe (comparación sin mayúsculas, tildes ni espacios). |
| 12 | Gerencia pone Urgente hasta 3 y Prioritario hasta 2 | 422 "Prioritario tiene que ser mayor que Urgente." |
| 13 | Gerencia pone Urgente 0, Prioritario 5. Un pedido del lunes para el mismo lunes | Urgente (d = 0). Uno para el sábado (d = 5): Prioritario; para el domingo (d = 6): Normal. |
| 14 | Un pedido cargado con plazos 1/7 quedó Prioritario (d = 5); gerencia cambia a 1/4 | Sigue Prioritario (RN-IDE-42). |
| 15 | Hernán (Jefatura de Proyectos) de quien dependen Laura y Sofía, y Sofía de quien depende Pablo | "Mi gente" de Hernán = Laura, Sofía y Pablo. Superior de Pablo = Sofía. |
| 16 | Diego cambia su propia contraseña poniendo mal la actual | 422 "La contraseña actual no coincide." |
| 17 | Una persona con Usuarios = Ver intenta `PATCH /api/usuarios/{id}` | 403. |
| 18 | Link de recuperación usado dos veces | La segunda: "El link venció…" (RN-IDE-07). |
| 19 | Pablo no usó el mail de bienvenida en 7 días; gerencia toca "Reenviar mail de bienvenida" | Le llega un link nuevo de 7 días; el viejo deja de servir (RN-IDE-09). Sobre un usuario que ya eligió contraseña: 422 "Ya eligió su contraseña: puede usar 'Olvidé mi contraseña'." |
| 20 | Pablo (Ingeniería) completa un pedido que pidió Carla (Compras), que depende de Diego | Aviso a Carla y a **Diego** (superior de la solicitante), no a Sofía, superior de Pablo (RN-IDE-23, DU-13). |
| 21 | Laura (Ingeniería, Obras = Editar·solo sus obras) pide `GET /api/adjuntos/{id}` del certificado de una obra donde no es responsable, adivinando el id | 404; no se confirma que el adjunto exista (RN-IDE-47). |
| 22 | Carla sube un PDF (id temporal 41) y Diego intenta asociar el 41 a un certificado de otra obra | 422 "Ese adjunto no se puede usar acá." (RN-IDE-46: solo adjuntos temporales propios). |
| 23 | Carla sube una foto y no la asocia a nada | Nadie más la ve; a las 24 h el comando programado la borra, con el archivo físico (RN-IDE-45). |

## 10. Contrato de API sugerido

Envelope en todo: `{ "data": …, "message": string|null, "errors": object|null }`; listados con
`meta` y `links` dentro. Sanctum SPA: `GET /sanctum/csrf-cookie` antes de ingresar. Códigos:
401 sin sesión, 403 sin permiso, 404, 409 conflicto, 422 validación (`errors.<campo>: [mensajes]`),
429 límite. Los POST que crean aceptan `Idempotency-Key` (ADR-011).

| Método y ruta | Payload | Respuesta `data` | Errores |
|---|---|---|---|
| `POST /api/auth/ingresar` | `{email, password, recordarme: bool}` | `UsuarioActual` | 422 RN-IDE-03/04 · 429 |
| `POST /api/auth/salir` | — | `null` | 401 |
| `POST /api/auth/olvide-contrasena` | `{email}` | `null`, `message` RN-IDE-06 | 422 formato · 429 |
| `POST /api/auth/elegir-contrasena` | `{token, email, password, password_confirmation}` | `UsuarioActual` (queda adentro) | 422 token vencido o contraseña inválida |
| `GET /api/me` | — | `UsuarioActual` | 401 |
| `PATCH /api/me` | `{nombre?, telefono?}` | `UsuarioActual` | 422 |
| `PUT /api/me/contrasena` | `{actual, nueva, nueva_confirmation}` | `null` | 422 |
| `GET /api/usuarios?buscar=&sector_id=&activo=true\|false\|todos&page=` | — | `[Usuario]` paginado | 403 |
| `GET /api/usuarios/opciones?buscar=` | — | `[{id, nombre, sector: {id, nombre}}]` solo activos | 401 |
| `POST /api/usuarios` | `{nombre, email, sector_id, jefe_id?, telefono?}` | `Usuario` (201) + manda el mail RN-IDE-09 | 403 · 422 |
| `GET /api/usuarios/{id}` | — | `Usuario` + `{personas_a_cargo: int, obras_como_responsable: int}` | 403 · 404 |
| `PATCH /api/usuarios/{id}` | `{nombre?, email?, sector_id?, jefe_id?, telefono?, activo?}` | `Usuario` | 403 · 404 · 422 (RN-IDE-22, RN-IDE-25) |
| `POST /api/usuarios/{id}/reenviar-bienvenida` | — | `null`, `message` "Le mandamos de nuevo el mail a {mail}." | 403 · 404 · 422 (inactivo o ya eligió contraseña) · 429 (3 por hora) |
| `GET /api/sectores?incluir_archivados=false` | — | `[{id, nombre, usuarios_activos: int, activo}]` | 401 |
| `POST /api/sectores` | `{nombre}` | `Sector` (201), con sus celdas iniciales RN-IDE-30 | 403 · 422 |
| `PATCH /api/sectores/{id}` | `{nombre}` | `Sector` | 403 · 404 · 422 |
| `DELETE /api/sectores/{id}` | — | `{resultado: "borrado"\|"archivado"}` | 403 · 404 · 422 RN-IDE-31 / RN-IDE-25 |
| `GET /api/permisos` | — | `{funciones: [Funcion], sectores: [{id, nombre}], celdas: [{sector_id, funcion, nivel, alcance}]}` | 403 |
| `PUT /api/permisos` | `{celdas: [{sector_id, funcion, nivel, alcance}]}` (solo las cambiadas; todo o nada) | igual que GET | 403 · 422 (nivel/alcance no admitido, RN-IDE-25) |
| `GET /api/plazos-prioridad` | — | `{urgente_hasta_dias, prioritario_hasta_dias, updated_at, updated_by: {id, nombre}\|null}` | 403 |
| `PUT /api/plazos-prioridad` | `{urgente_hasta_dias, prioritario_hasta_dias}` | igual que GET | 403 · 422 |

Formas:

```json
// UsuarioActual
{
  "id": 7, "nombre": "Laura", "email": "laura@empresa-ejemplo.com", "telefono": null,
  "sector": { "id": 5, "nombre": "Ingeniería de proyectos" },
  "jefe": { "id": 4, "nombre": "Hernán", "sector": "Jefatura de Proyectos" },
  "tiene_gente": false,
  "permisos": {
    "pedidos": { "nivel": "editar", "alcance": "todas" },
    "obras": { "nivel": "editar", "alcance": "todas" },
    "rendicion": { "nivel": "ver", "alcance": "propias" },
    "proveedores": { "nivel": "sin_acceso", "alcance": "todas" }
  }
}
// Funcion
{ "clave": "rendicion", "nombre": "Rendición", "admite_editar": true, "admite_alcance": true, "orden": 8 }
```

`permisos` trae **las 12 claves** siempre. El front arma el menú con la tabla §6.4 (una sola
función `menuPara(permisos, tieneGente)` testeada) y la API decide (RN-IDE-14).

Adjuntos (transversal, `app/Shared`, ADR-014): `POST /api/adjuntos` (multipart para archivo, JSON
para link) → id temporal; el recurso recibe `adjuntos: [ids]`; `GET /api/adjuntos/{id}` baja el
archivo con la autorización de RN-IDE-47. Contrato completo y validaciones (tipos por contenido,
20 MB, links `http(s)`) en `obras.md` §10.5, que es el primer módulo que los usa.

## 11. Trazabilidad F-XX → RN → test

| F-XX | Reglas | Tests (PHPUnit, `tests/Feature/Identidad/…`) |
|---|---|---|
| F-01 | RN-IDE-01…10 | `test_ingresa_con_mail_de_la_empresa_y_contrasena` · `test_mail_se_compara_sin_mayusculas_ni_espacios` · `test_credenciales_incorrectas_dan_el_mismo_mensaje` · `test_usuario_inactivo_no_puede_ingresar` · `test_sexto_intento_en_un_minuto_devuelve_429` · `test_olvide_contrasena_no_revela_si_el_mail_existe` · `test_link_de_recuperacion_vence_a_los_60_minutos_y_sirve_una_vez` · `test_elegir_contrasena_cierra_las_otras_sesiones` · `test_alta_de_usuario_manda_mail_para_elegir_contrasena` · `test_recordarme_deja_cookie_remember` |
| F-02 | RN-IDE-11…15 | `test_me_devuelve_las_doce_funciones_con_nivel_y_alcance` · `test_me_indica_si_tiene_gente_a_cargo` · `test_endpoint_responde_403_aunque_el_menu_lo_oculte` · `test_cambio_en_la_matriz_vale_desde_el_proximo_pedido` · front: `menuPara.test.ts` → `oculta_gastos_sin_acceso`, `marca_ver_en_rendiciones_de_ingenieria`, `muestra_usuarios_si_solo_tiene_plazos` |
| F-10 | RN-IDE-16…18 | `test_perfil_permite_cambiar_nombre_y_telefono` · `test_perfil_no_permite_cambiar_mail_sector_ni_jefe` · `test_cambiar_contrasena_pide_la_actual` · `test_nueva_contrasena_no_puede_ser_igual_a_la_actual` |
| F-58 | RN-IDE-09, 19…27 | `test_solo_usuarios_permisos_editar_da_de_alta_usuarios` · `test_reenviar_bienvenida_manda_link_nuevo_e_invalida_el_anterior` · `test_no_reenvia_bienvenida_a_quien_ya_eligio_contrasena` · `test_superior_para_aviso_de_completado_es_el_jefe_del_solicitante` · `test_mail_de_usuario_es_unico_incluso_entre_inactivos` · `test_desactivar_no_borra_y_conserva_historial` · `test_usuario_inactivo_sale_de_las_opciones` · `test_jefe_no_puede_ser_el_mismo_usuario` · `test_jefe_no_puede_armar_un_circulo` · `test_mi_gente_incluye_subordinados_indirectos` · `test_superior_es_el_jefe_directo` · `test_desactivar_jefe_informa_personas_y_obras_sin_bloquear` · `test_nadie_se_desactiva_a_si_mismo` · `test_no_queda_sin_administrador_de_usuarios` |
| F-59 | RN-IDE-28…33 | `test_migracion_crea_los_once_sectores_del_organigrama` · `test_nombre_de_sector_unico_sin_mayusculas_ni_tildes` · `test_sector_nuevo_nace_con_pedidos_y_equipos_editar_y_obras_ver` · `test_no_se_saca_un_sector_con_usuarios_activos` · `test_sector_sin_historia_se_borra_y_con_historia_se_archiva` · `test_renombrar_sector_conserva_usuarios_y_permisos` |
| F-60 | RN-IDE-34…39, §6.3 | `test_migracion_carga_el_catalogo_de_doce_funciones` · `test_migracion_carga_la_configuracion_inicial_del_cap_3` (una aserción por celda de §6.3) · `test_migracion_de_permisos_es_idempotente` · `test_no_acepta_editar_en_funcion_que_no_lo_admite` · `test_no_acepta_propias_en_funcion_sin_alcance` · `test_cambio_de_celda_queda_en_actividades` · `test_cambio_de_matriz_es_todo_o_nada` |
| F-61 | RN-IDE-22, 23 | (ver F-58) · `test_dato_depende_de_se_ve_en_mi_perfil` |
| F-68 | RN-IDE-40…44 | `test_migracion_arranca_plazos_en_1_y_7` · `test_prioritario_debe_ser_mayor_que_urgente` · `test_solo_plazos_editar_cambia_los_plazos` · unit `PlazosPrioridadTest`: `test_dias_hasta_urgente_es_urgente`, `test_limite_prioritario_es_inclusivo`, `test_mas_que_prioritario_es_normal`, `test_entrega_anterior_al_pedido_es_urgente`, `test_cambiar_plazos_no_recalcula_pedidos_cargados` (semana 2) |
| Adjuntos (ADR-014) | RN-IDE-45…48, §6.6 | `tests/Feature/Shared/Adjuntos/…`: `test_usuario_autenticado_sube_adjunto_y_recibe_id_temporal` · `test_sin_sesion_no_sube_adjuntos` · `test_adjunto_temporal_solo_lo_baja_quien_lo_subio` · `test_no_se_asocia_adjunto_subido_por_otro` · `test_no_se_asocia_adjunto_de_otro_contexto` · `test_descarga_sin_permiso_de_ver_el_dueno_devuelve_404` · `test_descarga_respeta_alcance_solo_sus_obras` · `test_quitar_adjunto_borra_el_archivo_y_queda_en_actividades` · `test_adjunto_huerfano_se_purga_a_las_24_horas` |

## 12. Dudas

**Todas resueltas por Juani el 12-nov-2026: va la propuesta** (detalle y resolución en
`docs/dudas.md`). Las de este módulo: DU-01 (Gerencia de Administración separada; columnas Equipos en
reparación y Monto de obra), DU-02 y DU-A02 (celdas "a completar"; Ingeniería sin Proveedores por
W-17), DU-03 (primera contraseña con mail y reenvío), DU-04 (días corridos), DU-13 (superior **del
solicitante**, con el texto firmado: RN-IDE-23), DU-A01 (11 sectores y 12 filas), DU-A03 (RRHH),
DU-A04 (Ingeniería solo sus obras), DU-A05 (Compras edita control por cantidad), DU-A06 (sector
nuevo), DU-A07 (sacar sector), DU-A08 (desactivar jefe/responsable), DU-A09 (guardas anti-bloqueo),
DU-A10 (primer usuario por comando), DU-A11 (dominio del mail), DU-A12 (semana 1 sin
Pedidos/Gastos), DU-A13 (prioridad al editar la entrega). DU-23 → ADR-014 (adjuntos, §6.6). No
quedan dudas abiertas en este módulo.

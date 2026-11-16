# Especificación funcional · Avisos (campana, lista, mail y recordatorio)

> Módulo `app/Modules/Avisos` · Entrega: **semana 2** · Rol: Analista funcional · 2026-11-16
> Contrato: documento firmado v1.1 (cap. 4.2, 5 "F-07…F-10, F-64", 7 "Avisos del pedido", 13 "un aviso aparece
> apenas pasa" y criterio de aceptación n.º 3) + maquetas W-02, W-19 (sección "Mis avisos"), W-41 (avisos en el
> celular), W-45. Decisiones: D-020, D-030, D-031. ADR-005 (polling 15 s), ADR-011 (idempotencia), **ADR-012
> (avisos)**, ADR-015 (el mail del código de ingreso **no** es un aviso).
> Los eventos que disparan avisos y sus datos están en [`pedidos.md`](pedidos.md) §8.
> Lo "Propuesto" del documento está **aprobado**. Resueltas que se aplican: DU-13 (superior **del solicitante**),
> DU-16 (pedido en el sector: aviso y recordatorio a todo el sector), DU-22 (comentario: a la otra parte y a los
> seguidores, nunca al autor). Lo nuevo (abierto, `docs/dudas.md`): **DU-P19, DU-P21…DU-P24** de esta especificación,
> **DU-P09** (propietario inactivo, de `pedidos.md`) y **DU-Q01** (PO + QA: el recordatorio de un lunes sale el domingo).
> Personas, obras y textos de este archivo son **ficticios** (los de las maquetas).

## 1. Propósito

"Los pedidos se pierden. La minuta no avisa: hay que dejar un comentario y arrobar a la persona" (cap. 1). Que cada
uno **se entere al momento** de lo que pasa con sus pedidos sin preguntar ni revisar el mail ("no que me explote el
correo… algo que lo vamos a tener abierto y que ahí te aparezca", D-031), y que **ningún pedido se atrase sin que el
propietario se entere antes** (criterio de aceptación n.º 3). El sistema avisa; no decide (D-006).

## 2. Funcionalidades y pantallas cubiertas

| F-XX | Qué | Pantalla | Semana 2 |
|---|---|---|---|
| F-08 | Avisos dentro del sistema, en el momento, con campana y contador | W-02 · W-41 | completa |
| F-09 | Avisos por mail: la asignación (y la reasignación) por mail; cada persona activa más | W-19 | completa |
| F-10 (parte avisos) | Mi perfil · "Mis avisos": qué llega además por mail (RN-IDE-18 se enciende) | W-19 | completa |
| F-07 (parte avisos) | El seguidor recibe dentro del sistema los mismos avisos que quien lo pidió | W-28 · W-02 | completa |
| F-37 | Recordatorio un día antes de la entrega, solo en el sistema | W-02 · W-41 | completa |
| F-64 | Todos los avisos, leídos y no leídos, con filtros | W-45 | completa |

## 3. Glosario

| Cliente | Código | Qué es |
|---|---|---|
| Aviso | `Aviso` / `avisos` | Una línea en la campana y en "Todos los avisos" de una persona. |
| Campana / contador | `GET /api/avisos/no-leidos/contador` | El número de avisos no leídos arriba a la derecha (y en "Avisos" del celular). |
| Leído / no leído | `avisos.leido_at` | No leído = con el punto ●. |
| Tipo de aviso | `avisos.tipo` | Asignado, Reasignado, Tomado, Bloqueado, Comentario nuevo, Completado, Completado (de mi gente), Reabierto, Vence mañana. |
| Mis avisos (por mail) | `preferencias_aviso` | Qué tipos llegan **además** por mail. |
| Recordatorio / "vence mañana" | tipo `vence_manana` | Aviso al propietario cuando falta un día para la entrega y el pedido sigue abierto (F-37). |
| Superior | `Jerarquia::superior(solicitante)` | El jefe **directo de quien pidió** (resuelta DU-13). |
| Seguidor | `pedido_seguidores` | Recibe en el sistema lo mismo que quien lo pidió (F-07). |
| Actor | `avisos.actor_id` | Quien provocó el aviso ("Carla tomó…"). `null` = el sistema (recordatorio). |

## 4. Datos

### 4.1 Aviso (`avisos`) — modelo-datos (Avisos, Planificado)

| Campo | Tipo | Nota |
|---|---|---|
| user_id | ref. usuario | destinatario |
| tipo | texto ≤ 30 | catálogo §4.3 |
| pedido_id | ref. pedido, null | todos los avisos de la Etapa 1 son de un pedido; queda null-able para etapas siguientes |
| actor_id | ref. usuario, null | quien lo provocó; null = sistema |
| datos | JSON | **foto del momento** para mostrarlo sin otra consulta: `{tarea, obra: {id, nombre_trabajo}\|null, actor_nombre, solicitante_nombre?, extracto?, comprobantes?}`; `extracto` = comentario, motivo o comentario de cierre recortado a 200 caracteres |
| dedupe_key | texto ≤ 120, **único** | §6 (ADR-011/012) |
| leido_at | fecha y hora, null | null = no leído |
| created_at | fecha y hora | "hace 10 min" |

Índice `(user_id, leido_at, created_at)` (contador barato, ADR-005).

### 4.2 Preferencia (`preferencias_aviso`)

`user_id`, `tipo`, `por_mail` (bool); único `(user_id, tipo)`. **Sin fila = el valor por defecto** del catálogo
§4.3. Solo se guardan filas para los tipos que se pueden cambiar.

### 4.3 Catálogo de tipos

| Tipo (`tipo`) | En W-19 / W-45 | Lo recibe | En el sistema | Por mail por defecto | ¿Se puede cambiar el mail? |
|---|---|---|---|---|---|
| `asignado` | "Me asignan un pedido" · Asignado | el propietario nuevo (o las personas del sector) | siempre | **sí** | no: siempre por mail (propuesta DU-P19) |
| `reasignado` | "Me reasignan un pedido" · Reasignado | el propietario nuevo (o las personas del sector) | siempre | **sí** | no: siempre por mail (propuesta DU-P19) |
| `tomado` | "Toman (En curso) un pedido que pedí" · Tomado | solicitante, seguidores | siempre | no | sí |
| `completado` | "Completan un pedido que pedí" · Completado | solicitante, seguidores | siempre | no | sí |
| `completado_gente` | (no tiene fila en W-19) · Completado (de mi gente) | el superior del solicitante | siempre | no | **no**: solo en el sistema (F-31 "dentro del sistema, a su superior") |
| `bloqueado` | "Bloquean un pedido que pedí, con el motivo" · Bloqueado | solicitante, seguidores | siempre | no | sí |
| `comentado` | "Comentan en un pedido mío" · Comentario nuevo | la otra parte, seguidores | siempre | no | sí |
| `reabierto` | "Reabren un pedido que completé" (fila nueva en W-19, propuesta DU-P19) · Reabierto | el propietario | siempre | no | sí |
| `vence_manana` | "Vence mañana una tarea mía" · Vence mañana | el propietario (o las personas del sector) | siempre | no | **no**: solo en el sistema (F-37 "sin llenar el mail") — la casilla "vence mañana" de W-19 no va (propuesta DU-P19) |

## 5. Reglas de negocio

### Generales (ADR-012)

- **RN-AVI-01** Los avisos los generan los **eventos de dominio de Pedidos** (`pedidos.md` §8): un listener por evento en el módulo Avisos, que corre **después del commit** del caso de uso, encolado en la cola `default` (3 intentos). Quién recibe qué lo decide una clase pura `Avisos\Domain\DestinatariosPorEvento` (sin framework, testeable en unit). Un aviso aparece en la campana **en ≤ 15 s** (ADR-005: "un aviso aparece apenas pasa", cap. 13). *(ADR-012, ADR-005, F-08)*
- **RN-AVI-02** **Nadie recibe aviso de lo que hizo él mismo** (el actor se saca siempre de los destinatarios). *(ADR-012, resuelta DU-22)*
- **RN-AVI-03** **Solo reciben avisos los usuarios activos**: un inactivo no entra y no se le genera ni aviso ni mail. *(W-39, RN-IDE-10)*
- **RN-AVI-04** **Un solo aviso por persona y por evento.** Si alguien tiene dos papeles en el mismo evento (por ejemplo, es seguidor y además el superior de quien pidió), recibe uno solo, el de su papel más directo: propietario > solicitante > superior > seguidor. *(técnica, para no duplicar)*
- **RN-AVI-05** **Solo generan avisos los eventos de la tabla §6.** Retomar, editar (aunque cambie la entrega), seguir, sumar un seguidor y los equipos en reparación **no** avisan. *(cap. 7 "Avisos del pedido"; edición: propuesta DU-P24)*
- **RN-AVI-06** Cada aviso tiene un **`dedupe_key` único** (§6): un reintento del listener o un doble envío no lo duplica, y el mail sale **solo si el aviso se acaba de crear**. *(ADR-011, ADR-012)*
- **RN-AVI-07** El texto se arma en el backend con las **palabras del cliente** y con la **foto del momento** (`datos`): si después cambia la tarea o la obra, el aviso viejo conserva lo que decía. Comentarios y motivos se recortan a 200 caracteres ("…"). *(W-02, W-45)*

### Quién recibe cada aviso (cap. 7 "Avisos del pedido")

- **RN-AVI-08** **Asignación** (al crear): al **propietario**, en el sistema y por mail. Si quien carga es el propietario, no hay aviso. *(cap. 7 "Le asignan un pedido → al propietario, en el sistema y por mail", F-09)*
- **RN-AVI-09** **Reasignación**: al **nuevo propietario**, en el sistema y por mail, con el comentario si lo hay. Si se deja **en el sector**, a **todas las personas activas del sector** (menos quien reasigna). El propietario anterior y quien lo pidió **no** reciben aviso (la tabla del cap. 7 no los nombra; lo ven en el pedido). *(cap. 7 "o se lo reasignan", F-32, resuelta DU-16)*
- **RN-AVI-10** **Tomado** (pasa a En curso) y **bloqueado** (con el motivo): a **quien lo pidió** y a los **seguidores**. *(cap. 7 "El pedido pasa a En curso → al que lo pidió", "Bloquean… un pedido mío → al que lo pidió", F-07)*
- **RN-AVI-11** **Comentario nuevo**: a **la otra parte** (comenta quien lo pidió → al propietario; comenta el propietario → a quien lo pidió) y a los **seguidores**, nunca al autor. Si comenta **un tercero** (un seguidor u otra persona con Pedidos = Editar), a quien lo pidió **y** al propietario, y a los seguidores. Si el pedido está **en el sector**, "el propietario" son las personas activas del sector. Solo en el sistema, salvo que la persona lo active por mail. *(resuelta DU-22; tercero y sector: propuesta DU-P23)*
- **RN-AVI-12** **Completado**: a **quien lo pidió**, con el comentario de cierre y la marca "· comprobante" si tiene; a **su superior** (`Jerarquia::superior` **del solicitante**, tipo `completado_gente`, **solo dentro del sistema**); y a los **seguidores**. Si quien lo pidió no tiene jefatura (Dirección) o su superior está inactivo, no hay aviso extra; si el superior es quien completó, tampoco (RN-AVI-02). *(cap. 7 "Al que lo pidió y a su superior", F-31, W-02, resuelta DU-13)*
- **RN-AVI-13** **Reabierto**: al **propietario**, con el comentario ("falta algo"). Si el propietario está inactivo, quien reabre elige en el mismo paso a quién pasárselo y el aviso va a ese nuevo propietario (o a las personas activas del sector). *(cap. 7 "reabren un pedido que completé → al propietario", F-65; inactivo: propuesta DU-P09)*
- **RN-AVI-14** **Seguidores**: reciben, **dentro del sistema**, los mismos avisos que quien lo pidió: **tomado, bloqueado, comentario nuevo y completado**. No reciben asignación, reasignación, reapertura ni recordatorio. *(F-07, W-28 "Quien sigue un pedido recibe dentro del sistema los mismos avisos que quien lo pidió")*
- **RN-AVI-15** Pedido de **carga inicial sin solicitante**: los avisos "a quien lo pidió" y "a su superior" no tienen destinatario; los de los seguidores y el propietario salen igual. *(resuelta DU-20, `pedidos.md` RN-PED-61)*

### Recordatorio "vence mañana" (F-37)

- **RN-AVI-16** Un comando programado (`avisos:recordatorios`, **cada hora** con `schedule:work` bajo Supervisord, nunca crontab) busca los pedidos **abiertos** (No iniciada, En curso, Bloqueada) con **fecha de entrega = mañana** (días **corridos**, zona Argentina) por `Pedidos\Contracts\PedidosParaRecordar` y crea el aviso **`vence_manana`** para el **propietario** o, si está en el sector, para **todas las personas activas del sector**. **Solo dentro del sistema**, nunca por mail. *(F-37, cap. 4.2 "sin llenar el mail", ADR-012, resueltas DU-04 y DU-16)*
- **RN-AVI-17** Sale **a partir de las 8:00** (hora de Argentina; W-02 "hoy 8:00 · recordatorio"): la primera corrida desde las 8:00 genera los del día y las siguientes de cada hora levantan lo que se cargó después (un pedido cargado a las 15:00 con entrega mañana recibe el recordatorio en la corrida de las 16:00). *(W-02; hora: propuesta DU-P21; días corridos también para el recordatorio — un pedido para el lunes se recuerda el domingo: propuesta DU-Q01)*
- **RN-AVI-18** **No se repite**: `dedupe_key = recordatorio:{pedido}:{fecha_entrega}:{persona}`. Si cambian la entrega a otro día, puede salir otro recordatorio para la fecha nueva. No sale para pedidos que vencen **hoy** o ya vencidos (eso lo marcan las listas en rojo), ni para completados. No va al solicitante ni a los seguidores. *(F-37, ADR-012 — la clave suma la persona porque un pedido en el sector tiene varios destinatarios)*

### Por mail (F-09, F-10)

- **RN-AVI-19** **Por defecto, por mail llega solo la asignación y la reasignación**; cada persona puede activar más en Mi perfil (§4.3). **Nunca** por mail: "vence mañana" (F-37) y "completado de mi gente" (F-31). *(F-09, W-19 "Por mail llega solo la asignación", D-031; qué se puede activar: propuesta DU-P19)*
- **RN-AVI-20** El mail sale **siempre por la cola `mail`** (`AvisoPedidoMail extends App\Shared\Mail\QueuedMailable`; worker a **1 mail por segundo**, Resend en producción), **desde noreply** con reply-to configurable (`MAIL_REPLY_TO_*`), **uno por persona**. Al enviarse se vuelve a mirar que la persona siga activa y con el mail activado para ese tipo; si no, no se manda. Nunca `Mail::send()` crudo. *(ADR-012, CLAUDE.md regla 8)*
- **RN-AVI-21** El mail es corto, en las palabras del cliente: asunto (§6), quién y qué, tarea, obra (o "Servicios generales"), entrega, prioridad, el comentario o motivo si lo hay, el botón **"Ver el pedido"** (`FRONTEND_URL/pedidos/{id}`; pide ingresar si no hay sesión) y al pie "Este aviso es automático. Respondé desde el sistema, en el pedido. Podés elegir qué avisos te llegan por mail en Mi perfil." Sin importes ni adjuntos. *(F-09, D-031)*

### Campana y lista (F-08, F-64; W-02, W-45, W-41)

- **RN-AVI-22** **Campana** arriba a la derecha con el **número de no leídos** ("9+" desde 10). Se actualiza **cada 15 s con la pestaña visible y al volver a enfocarla** (`GET /api/avisos/no-leidos/contador`, consulta sobre el índice `user_id, leido_at`); las acciones propias la refrescan al instante. *(F-08, ADR-005)*
- **RN-AVI-23** Tocando la campana se abre el panel **"Avisos"** con los **5 más recientes** (los no leídos con ●), cada uno con su texto y "hace 10 min" / "hoy 8:00" / "viernes 11:05"; abajo **"Marcar todo como leído"** y **"Ver todos los avisos"** (W-45), y la nota "Por mail llega solo la asignación; el resto, acá adentro. Cada uno puede sumar más avisos por mail en su perfil (W-19)." **Tocando un aviso** se marca leído y se abre el pedido (W-09). *(W-02)*
- **RN-AVI-24** **Todos los avisos** (W-45): lista completa, **la más nueva arriba**, paginada de a 25, con **Todos · No leídos (n) · Leídos** y **Tipo: todos ▾**; columnas **● · Aviso · Obra · Cuándo** ("Piso nave 3" o "Servicios generales"; "19-oct 9:20"); "Marcar todo como leído". Tocando uno se marca leído y se abre el pedido. *(F-64, W-45)*
- **RN-AVI-25** **Celular** (W-41): "Avisos" en la barra de abajo con el contador; la pantalla es la misma lista (no leídos con ●, "hace 10 min"), en tarjetas. *(F-13, W-41, RN-IDE-15)*
- **RN-AVI-26** Cada uno ve y marca **solo sus avisos**: un id ajeno responde **404**. Marcar leído es idempotente (marcar uno ya leído deja la fecha original). Los avisos **no se borran** en la Etapa 1. Si el pedido de un aviso ya no se puede ver (cambió la tabla de permisos), el aviso sigue en la lista y al abrirlo se ve "No tenés acceso a este pedido." (404 del pedido). *(ADR-006; sin purga y pedido no visible: propuesta DU-P22)*

### Mi perfil · Mis avisos (F-10; W-19)

- **RN-AVI-27** En **Mi perfil** se enciende la sección **"Mis avisos"** (RN-IDE-18): tabla **Aviso · En el sistema · Además por mail**, una fila por tipo de §4.3 que tiene fila en W-19 (más "Reabren un pedido que completé", propuesta DU-P19), "siempre" en la columna del sistema y una casilla en la de mail. Asignación y reasignación se ven **tildadas y sin poder destildarse**; el resto se tilda o destilda y se guarda al tocar la casilla. El cambio vale **desde el próximo aviso**. *(F-10, W-19, RN-IDE-18)*

### Contratos que expone Avisos

- **RN-AVI-28** `Avisos\Contracts\AvisosDelPedido::enviadosDe(pedidoId)` devuelve los avisos generados para un pedido, agrupados por evento (`{tipo, destinatarios: [{id, nombre}], created_at}`), para las líneas "sistema avisó a Carla (asignado)" del recorrido (`pedidos.md` RN-PED-31). `Avisos\Contracts\DestinatariosDeAviso::deAsignacion(Participantes, actorId)` devuelve a quiénes les llega la asignación o reasignación con las mismas reglas de §5, para el "Se le avisó a Carla" de W-43 (`pedidos.md` RN-PED-08). *(ADR-003: los módulos se hablan por `Contracts/`)*

## 6. Tabla de avisos: evento → destinatarios → canal → texto → `dedupe_key`

`{a}` = id de la actividad del evento (la registra Pedidos en la transacción; viaja en el evento), `{u}` = id del
destinatario, `{p}` = id del pedido. Textos con datos ficticios. "Sistema" = campana, W-45 y W-41.

| Evento | Destinatario | Tipo | Canal | Texto en el sistema (ejemplo) | Asunto del mail | `dedupe_key` |
|---|---|---|---|---|---|---|
| `PedidoAsignado` (crear) | propietario | `asignado` | sistema + **mail** | "**Laura** te asignó **Alquiler de andamio para el sector de bombas** · Reparación de losa tanque B" | "Te asignaron un pedido: Alquiler de andamio para el sector de bombas" | `asignado:{a}:{u}` |
| `PedidoReasignado` a persona | nuevo propietario | `reasignado` | sistema + **mail** | "**Carla** te reasignó **Compra de discos de corte diamantados** · Reparación de losa tanque B: 'Diego tiene la cuenta con el proveedor de discos.'" | "Te reasignaron un pedido: Compra de discos de corte diamantados" | `reasignado:{a}:{u}` |
| `PedidoReasignado` al sector | cada persona activa del sector | `reasignado` | sistema + **mail** | "**Carla** dejó en **Compras** (tu sector) **Compra de discos de corte diamantados**: lo toma el primero que pueda" | "Hay un pedido para Compras: Compra de discos de corte diamantados" | `reasignado:{a}:{u}` |
| `PedidoTomado` | quien lo pidió | `tomado` | sistema (+ mail si lo activó) | "**Carla** tomó tu pedido **Solicitud de materiales N.º 3** → pasó a En curso" | "Carla tomó tu pedido: Solicitud de materiales N.º 3" | `tomado:{a}:{u}` |
| `PedidoTomado` | seguidores | `tomado` | sistema (+ mail si lo activó) | "**Carla** tomó **Solicitud de materiales N.º 3** (que seguís) → En curso" | ídem | `tomado:{a}:{u}` |
| `PedidoBloqueado` | quien lo pidió · seguidores | `bloqueado` | sistema (+ mail si lo activó) | "**Carla** bloqueó tu pedido **Envío de EPP a obra**: 'Falta el talle de botines de 3 personas de la cuadrilla.'" (seguidor: "…bloqueó **Envío de EPP a obra** (que seguís)…") | "Carla bloqueó tu pedido: Envío de EPP a obra" | `bloqueado:{a}:{u}` |
| `PedidoComentado` (comenta quien lo pidió) | propietario (o el sector) · seguidores | `comentado` | sistema (+ mail si lo activó) | "**Laura** comentó en **Solicitud de materiales N.º 3**: '30 bolsas. Dejé el cómputo actualizado en el Drive.'" | "Laura comentó en Solicitud de materiales N.º 3" | `comentado:{a}:{u}` |
| `PedidoComentado` (comenta el propietario) | quien lo pidió · seguidores | `comentado` | ídem | "**Diego** comentó en tu pedido **Certificado N.º 3**: 'Mandame la medición de la semana 42.'" | "Diego comentó en tu pedido: Certificado N.º 3" | `comentado:{a}:{u}` |
| `PedidoComentado` (comenta un tercero, DU-P23) | quien lo pidió · propietario · seguidores | `comentado` | ídem | "**Hernán** comentó en **Envío de EPP a obra**: 'Talles: 41, 42 y 44'" | "Hernán comentó en Envío de EPP a obra" | `comentado:{a}:{u}` |
| `PedidoCompletado` | quien lo pidió | `completado` | sistema (+ mail si lo activó) | "**Carla** completó tu pedido **Alquiler de obrador**: 'Entregado en obra, adjunto remito' · comprobante" | "Carla completó tu pedido: Alquiler de obrador" | `completado:{a}:{u}` |
| `PedidoCompletado` | superior **de quien lo pidió** | `completado_gente` | **solo sistema** | "**Carla** completó **Solicitud de materiales N.º 3**, que pidió **Laura**: 'Entregado en obra, recibió el encargado.'" | — | `completado_gente:{a}:{u}` |
| `PedidoCompletado` | seguidores | `completado` | sistema (+ mail si lo activó) | "**Carla** completó **Alquiler de obrador** (que seguís): 'Entregado en obra, adjunto remito'" | "Carla completó Alquiler de obrador" | `completado:{a}:{u}` |
| `PedidoReabierto` | propietario (si estaba inactivo: el que se elige al reabrir, DU-P09) | `reabierto` | sistema (+ mail si lo activó) | "**Laura** reabrió **Solicitud de materiales N.º 3** (falta algo): 'Faltaron 2 bolsas.'" | "Laura reabrió un pedido que completaste: Solicitud de materiales N.º 3" | `reabierto:{a}:{u}` |
| Recordatorio (scheduler, F-37) | propietario o cada persona activa del sector | `vence_manana` | **solo sistema** | "Vence mañana: **Informe fotográfico semanal** · Piso industrial nave 3" | — | `recordatorio:{p}:{fecha_entrega}:{u}` |
| Retomar · editar · seguir · equipos | — | — | — | (no avisan, RN-AVI-05) | — | — |

En todos los casos se saca al actor (RN-AVI-02), a los inactivos (RN-AVI-03) y se deja un solo aviso por persona
(RN-AVI-04). El cuerpo del mail repite el texto del sistema y agrega tarea, obra, entrega, prioridad y "Ver el
pedido" (RN-AVI-21).

## 7. Permisos

Los avisos **no son una fila de la matriz** (`identidad.md` §6.1: "Avisos" es de todos).

| Acción | Quién | Si no |
|---|---|---|
| Ver mis avisos, el contador, marcar leído / todos | el usuario autenticado, **solo los suyos** | 401 · 404 (aviso ajeno) |
| Ver y cambiar "Mis avisos" por mail | el usuario autenticado, sobre sí mismo | 401 · 422 (tipo que no se puede cambiar) |
| Abrir el pedido desde un aviso | las reglas de visibilidad del pedido (`pedidos.md` §7) | 404 |

## 8. Casos borde (datos ficticios)

| # | Caso | Resultado esperado |
|---|---|---|
| 1 | Laura le pide un pedido a Carla | Aviso `asignado` a Carla en el sistema y mail "Te asignaron un pedido: …" por la cola `mail`. Laura no recibe nada. |
| 2 | Carla se carga un pedido a sí misma | No hay aviso ni mail (RN-AVI-02). |
| 3 | Doble clic en "Crear pedido" con la misma `Idempotency-Key` | Un pedido, **un** aviso, **un** mail. |
| 4 | El listener de asignación falla y la cola lo reintenta | El reintento choca con el `dedupe_key`: no se duplica el aviso ni el mail. |
| 5 | Carla deja un pedido en Compras (3 personas activas, ella incluida; una cuarta inactiva) | Avisos y mails a las otras 2 activas; ni a Carla ni a la inactiva. |
| 6 | Diego toma un pedido que pidió Laura y que siguen Sofía y Hernán (superior de Laura) | `tomado` a Laura, Sofía y Hernán (este último como seguidor: el superior solo recibe el completado). |
| 7 | Carla completa un pedido que pidió Laura (jefe: Hernán), seguido por Hernán | Laura: `completado`; Hernán: **un solo** aviso, `completado_gente` (superior > seguidor, RN-AVI-04), solo en el sistema. |
| 8 | Hernán completa un pedido que pidió Laura, de quien es jefe | Laura recibe `completado`; Hernán no recibe nada (es el actor). |
| 9 | Completa un pedido que pidió Diego (Dirección, sin jefe) | Solo Diego; no hay aviso a un superior. |
| 10 | Laura comenta en un pedido de Carla seguido por Sofía | `comentado` a Carla y a Sofía; no a Laura. |
| 11 | Carla responde | `comentado` a Laura y a Sofía; no a Carla. |
| 12 | Sofía (seguidora) comenta | A Laura y a Carla (propuesta DU-P23); no a Sofía. |
| 13 | Comentan en un pedido que está en el sector Compras | "El propietario" son las personas activas de Compras (propuesta DU-P23). |
| 13b | Pedido abierto de Carla con entrega el lunes | El recordatorio sale el **domingo** desde las 8:00 (días corridos, DU-Q01); el lunes el pedido aparece en Mis tareas como "hoy". |
| 14 | Lunes 9:00: pedido abierto de Carla con entrega el martes | A partir de las 8:00 del lunes, `vence_manana` a Carla, solo en el sistema. La corrida de las 10:00 no lo repite. |
| 15 | El mismo pedido: el lunes a las 11 le cambian la entrega al miércoles | El martes a las 8:00 sale otro recordatorio (clave con la fecha nueva). Carla no recibe aviso por la edición (propuesta DU-P24). |
| 16 | Pedido con entrega hoy, o completado, o con entrega mañana pero ya Completada | No hay recordatorio. |
| 17 | Pedido en el sector con entrega mañana | Recordatorio a cada persona activa del sector. |
| 18 | Pedido Bloqueado con entrega mañana | Recordatorio al propietario (sigue abierto). |
| 19 | Laura activó "Completan un pedido que pedí" por mail | Al completarse le llega también el mail; a Hernán (su superior) nunca por mail. |
| 20 | Laura intenta destildar "Me asignan un pedido" | 422 "La asignación siempre llega por mail." (propuesta DU-P19). |
| 21 | Laura intenta activar "Vence mañana" por mail | 422 "Este aviso llega solo dentro del sistema." |
| 22 | A Carla le asignan un pedido y antes de que salga el mail la desactivan | El aviso queda; el mail no se manda (RN-AVI-20). |
| 23 | Carla pide `POST /api/avisos/{id}/leido` de un aviso de Laura | 404. |
| 24 | Laura marca leído un aviso ya leído | 200; `leido_at` no cambia. |
| 25 | Hay 12 no leídos | La campana muestra "9+"; W-45 "No leídos (12)". |
| 26 | Cambian la tarea de un pedido después de avisar | El aviso viejo conserva la tarea vieja (RN-AVI-07). |
| 27 | Pedido de carga inicial sin solicitante que se completa | No hay aviso a solicitante ni superior; los seguidores, si los hay, sí. |
| 28 | Comentario de 600 caracteres | El aviso muestra los primeros 200 con "…"; el pedido tiene el texto completo. |

## 9. Contrato de API sugerido

Envelope `{ data, message, errors }`; rutas en `app/Modules/Avisos/routes.php`. Todo con sesión.

| Método y ruta | Payload | Respuesta `data` | Errores |
|---|---|---|---|
| `GET /api/avisos/no-leidos/contador` | — | `{no_leidos: 3}` | 401 |
| `GET /api/avisos?leidos=todos\|no\|si&tipo=&por_pagina=5\|25&page=` | — | `[Aviso]` paginado, la más nueva arriba; `meta: {no_leidos}` | 401 · 422 |
| `POST /api/avisos/{id}/leido` | — | `Aviso` | 401 · 404 |
| `POST /api/avisos/leer-todos` | — | `{marcados: 3}` | 401 |
| `GET /api/me/avisos-por-mail` | — | `[{tipo, nombre, por_mail, se_puede_cambiar}]` (orden de W-19) | 401 |
| `PUT /api/me/avisos-por-mail` | `{preferencias: {"tomado": true, "comentado": false}}` | igual que el GET | 401 · 422 (tipo inexistente o que no se puede cambiar) |

Los POST de marcar leído son idempotentes por sí mismos (no necesitan `Idempotency-Key`).

```json
// Aviso
{
  "id": 501, "tipo": "completado",
  "texto": "Carla completó tu pedido Alquiler de obrador: 'Entregado en obra, adjunto remito' · comprobante",
  "pedido": { "id": 88, "tarea": "Alquiler de obrador" },
  "obra": { "id": 12, "nombre_trabajo": "Piso industrial nave 3" },     // null = "Servicios generales"
  "actor": { "id": 11, "nombre": "Carla" },                              // null = el sistema
  "leido": false, "created_at": "2026-10-05T16:40:00-03:00"
}
```

El texto lo arma el backend (`texto`); el front resalta en negrita los nombres con `actor.nombre` y `pedido.tarea`.

## 10. Trazabilidad F-XX → RN → test

| F-XX | Reglas | Tests (PHPUnit, `tests/Feature/Avisos/…`, `tests/Unit/Avisos/…`) |
|---|---|---|
| F-08 | RN-AVI-01…15, 22, 23 | Unit `DestinatariosPorEventoTest` (una aserción por fila de §6): `test_asignacion_va_al_propietario`, `test_reasignacion_al_sector_va_a_todas_las_personas_activas`, `test_tomado_va_al_solicitante_y_seguidores`, `test_bloqueado_lleva_el_motivo`, `test_comentario_del_solicitante_va_al_propietario`, `test_comentario_del_propietario_va_al_solicitante`, `test_comentario_de_un_tercero_va_a_las_dos_partes`, `test_completado_va_al_solicitante_y_al_superior_del_solicitante`, `test_completado_no_va_al_superior_de_quien_completo`, `test_reabierto_va_al_propietario`, `test_nadie_recibe_aviso_de_lo_que_hizo_el_mismo`, `test_inactivo_no_recibe_avisos`, `test_un_solo_aviso_por_persona_y_evento`, `test_sin_solicitante_no_hay_aviso_al_superior`; Feature: `test_aviso_se_crea_despues_del_commit` · `test_reintento_del_listener_no_duplica_por_dedupe_key` · `test_retomar_y_editar_no_generan_avisos` · `test_aviso_conserva_el_texto_del_momento` · `test_contador_cuenta_solo_mis_no_leidos` |
| F-09 · F-10 | RN-AVI-19…21, 27 | `test_asignacion_sale_por_mail_por_defecto` · `test_reasignacion_sale_por_mail_por_defecto` · `test_tomado_no_sale_por_mail_si_no_lo_activo` · `test_tomado_sale_por_mail_si_lo_activo` · `test_mail_sale_por_la_cola_mail_desde_noreply` · `test_aviso_al_superior_nunca_sale_por_mail` · `test_no_se_puede_destildar_la_asignacion` · `test_no_se_puede_activar_vence_manana_por_mail` · `test_mail_no_sale_si_el_usuario_se_desactivo` · `test_mail_lleva_el_link_al_pedido` · `test_preferencias_sin_fila_usan_el_valor_por_defecto` |
| F-07 | RN-AVI-14 | `test_seguidor_recibe_tomado_bloqueado_comentado_y_completado` · `test_seguidor_no_recibe_asignacion_ni_recordatorio` |
| F-37 | RN-AVI-16…18 | `test_recordatorio_avisa_al_propietario_un_dia_antes` · `test_recordatorio_en_el_sector_avisa_a_todas_las_personas` · `test_recordatorio_no_se_repite_en_la_siguiente_corrida` · `test_recordatorio_sale_de_nuevo_si_cambia_la_entrega` · `test_recordatorio_no_sale_antes_de_las_8` · `test_recordatorio_ignora_completados_y_los_que_vencen_hoy` · `test_recordatorio_nunca_sale_por_mail` · `test_recordatorio_usa_dias_corridos_en_hora_argentina` |
| F-64 | RN-AVI-24…26 | `test_lista_de_avisos_filtra_leidos_no_leidos_y_tipo` · `test_marcar_leido_es_idempotente` · `test_marcar_todos_como_leidos` · `test_aviso_ajeno_devuelve_404` · `test_lista_ordena_la_mas_nueva_arriba` |
| Contratos | RN-AVI-28 | `test_avisos_del_pedido_agrupa_por_evento_para_el_recorrido` · `test_destinatarios_de_asignacion_coinciden_con_los_avisos_creados` |

## 11. Dudas

Resueltas que se aplican: DU-13, DU-16, DU-22 (`docs/dudas.md`). **Nuevas (abiertas):** DU-P19 (qué avisos se pueden
pedir por mail; asignación fija; "vence mañana" fuera del mail; fila "reabierto" en W-19), DU-P21 (hora del
recordatorio), DU-P22 (avisos que no se borran y pedido que ya no se ve), DU-P23 (comentario de un tercero y pedido en
el sector), DU-P24 (editar la entrega no avisa); de `pedidos.md`, DU-P09 (reabrir con propietario inactivo); de PO +
QA, DU-Q01 (el recordatorio de un lunes, el domingo).

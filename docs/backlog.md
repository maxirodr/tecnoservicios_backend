# Backlog · Etapa 1 · NS-2026-025 Tecnoservicios Bahía

> **Contrato:** el Documento del sistema v1.1 firmado (4-nov-2026), funcionalidades F-01…F-68 y
> pantallas W-01…W-50. Todo lo marcado "Propuesto" está **aprobado**. Lo de "No incluye" y
> "Etapas siguientes" (Tango, cobranzas, calendario de pagos, sueldos, IA, WhatsApp, acceso de
> clientes, cotizaciones, herramientas, indicadores) queda **fuera**.
>
> **Regla de oro:** este backlog no agrega, no quita y no cambia funcionalidad. Lo que el
> documento no dice y hace falta para escribir un criterio de aceptación va a
> [`dudas.md`](dudas.md) con su propuesta. Las historias que dependen de una duda abierta
> quedan **⛔ bloqueadas** hasta que se decida.
>
> **12-nov-2026:** Juani aprobó todas las dudas (DU-01…DU-22, DU-A01…DU-A21: va la propuesta;
> DU-13 con el texto firmado: aviso al solicitante y a **su** superior) y la DU-23 / ADR-014
> (**archivo o link** donde el documento decía "link"; la carpeta de Drive sigue siendo solo link).
> No queda ninguna historia ⛔.
>
> **13-nov-2026 (ADR-015, Juani):** se entra con el mail y un **código por mail**, sin contraseña.
> Cambian HU-001 y HU-005 (criterios nuevos), y HU-002 y HU-004 quedan **reemplazadas**. Lo viejo
> queda tachado como historia. Cambia F-01 del documento firmado: hay que avisarle al cliente.
>
> **16-nov-2026 (semana 2 bajada a historias):** semana 1 cerrada y en `main` (QC apta, B-01…B-05
> arreglados con su test). La §6 detalla la semana 2 en **35 historias (HU-026…HU-060)**; 22 tienen
> un criterio ⛔ que depende de dudas nuevas abiertas (**DU-Q01…DU-Q03** de PO + QA y **DU-P01…DU-P24** del
> Analista; decide Juani). **19-nov-2026: Juani las resolvió todas (va la propuesta); no queda ningún ⛔.** Las semanas 3 y 4 siguen como épicas (§7).
>
> **24-nov-2026 (semana 3 bajada a historias):** semana 2 aprobada por el cliente. La §7 detalla la semana 3 en
> **27 historias (HU-061…HU-087)** con las specs `especificacion/gastos.md` y `especificacion/rendicion.md`, alineadas
> entre sí el mismo día. Las dudas nuevas (**DU-G01…DU-G11** y **DU-R01…DU-R09**) las resolvió Juani el 24-nov (va la
> propuesta; DU-G08 reemplazada por DU-R05 y DU-R03 por DU-G05): **no queda ningún ⛔**. La semana 4 sigue como épicas.
>
> Trazabilidad completa F-XX ↔ W-XX ↔ tests: [`qa/matriz-trazabilidad.md`](qa/matriz-trazabilidad.md).
> Estrategia de pruebas: [`qa/plan-de-pruebas.md`](qa/plan-de-pruebas.md).
>
> Todos los nombres, obras, proveedores e importes de los ejemplos son **ficticios**.

---

## 1. Roadmap firmado (cap. 14)

| Semana | Qué se entrega | Qué prueba el cliente | F-XX que arrancan esa semana |
|---|---|---|---|
| **1 · Base** | Ingreso, usuarios, sectores y permisos (cap. 11) · Obras con sus presupuestos por rubro y contactos (cap. 6) · Proveedores (cap. 10) | Cargan dos o tres obras reales y sus usuarios. | 22 |
| **2 · Pedidos (la minuta)** | Pedidos en los dos sentidos, con propietario, sector, estados, prioridad y avisos · Mis tareas, calendario y carga por persona · Celular · Equipos en reparación (cap. 5 y 7) | Lo usan en paralelo con la minuta durante unos días. | 27 |
| **3 · Gastos y rendición** | Carga única de facturas y gastos por renglón · Rendición en vivo y vista de todas las rendiciones · Horas y fijos · Control de materiales por cantidad (cap. 8 y 9) | Cargan las facturas de una obra en curso y la comparan con su rendición en Excel. | 17 |
| **4 · Puesta en marcha** | Carga inicial con la minuta y las rendiciones al día del arranque · Capacitación por sector · Arranque y acompañamiento (cap. 12) | Arrancan a usarlo; la minuta y las rendiciones en Excel quedan de consulta. | 2 |
| | | **Total** | **68** |

### Funciones que se reparten entre semanas

Se cuentan en la semana en que **arrancan**. Lo que se completa después queda escrito acá y en la
matriz, para que nadie dé por terminada una función a medias. **Semana 1: entregada** (QC apta,
13-nov). **Semana 2: entregada** (prueba en paralelo aprobada). **Semana 3: lo que entra ahora**, con la historia que lo construye (§7).

| F-XX | Semana 1 (entregada) | Semana 2 (entregada · HU) | Semana 3 (entra ahora · HU) |
|---|---|---|---|
| F-03 Buscador general | Obras (N.º de presupuesto, cliente, planta, nombre del trabajo) y proveedores (nombre) | Pedidos por texto · HU-057 | Gastos por número de factura o de remito · HU-085 |
| F-10 Mi perfil | Mis datos, mi sector, de quién dependo (~~cambiar la contraseña~~: no hay contraseña, ADR-015) | "Mis avisos" (qué avisos llegan además por mail) · HU-047 | — |
| F-14 Registro de quién hizo qué | En obras (alta, edición, cambio de estado, presupuestos) | En pedidos (edición y recorrido) · HU-031, HU-037 | En gastos (quién cargó, cada corrección y la anulación) · HU-072, HU-073 |
| F-15 Listado de obras | Pestañas por estado, N.º de presupuesto, trabajo, cliente y planta, responsable | Columna "pedidos abiertos" y fila de Servicios generales · HU-055 (celular: HU-053) | Columna "% del presupuesto gastado" (en rojo si se pasó) · HU-081 |
| F-17 Ficha de la obra | Datos de la obra y pestaña Documentos y contactos | Pestaña Pedidos · HU-054 (celular: HU-053) | Pestaña Rendición · HU-079 |
| F-21 Cambiar el estado de la obra | Confirmada → En ejecución → Terminada, por quien corresponde, con registro | Al pasar a Terminada se muestran los pedidos abiertos para completarlos o reasignarlos (no frena, DU-12) · HU-056 | — |
| F-61 Visibilidad por jerarquía | El dato "depende de" (jefatura) en cada usuario | Mis tareas en el inicio y Tareas de mi gente · HU-038, HU-040 | — |
| F-68 Plazos de la prioridad | Gerencia define y guarda los plazos (W-50) | La prioridad de cada pedido nuevo se calcula con esos plazos (F-26) · HU-027 | — |
| F-48 Presupuesto por rubro | Se carga por presupuesto (W-13) | — | Alimenta la columna "Presupuestado" de la rendición · HU-074 |
| F-57 Gastos del proveedor | — (la ficha del proveedor existe desde la semana 1) | — | Lo imputado al proveedor, por obra · HU-084 |

Además, la semana 2 completa dos casos que la semana 1 dejó escritos para cuando hubiera pedidos:
sacar un sector y desactivar a una persona que tienen pedidos abiertos (F-58, F-59 · HU-058).

---

## 2. Definition of Ready (DoR)

Una historia entra a la semana solo si cumple **todo**:

- [ ] Tiene su **F-XX** y su **W-XX** (o "—" si no tiene pantalla, como la carga inicial).
- [ ] Sus **criterios de aceptación son verificables** (Dado / Cuando / Entonces, sin "debería
      andar bien").
- [ ] Las **reglas del Analista están escritas** en `docs/especificacion/` (RN-XX) y enlazadas
      desde la matriz. Mientras la spec no exista, las reglas de esta historia son las de sus
      criterios.
- [ ] Los **casos de QA están definidos** en la matriz (tests backend con nombre de la regla y
      test E2E del camino feliz).
- [ ] **Sin dudas bloqueantes**: ninguna DU-XX abierta que cambie sus criterios.

## 3. Definition of Done (DoD)

Una historia está terminada cuando:

- [ ] La revisó el **Tech Lead** (código, arquitectura modular, permisos por matriz y no por rol).
- [ ] Sus **tests feature y E2E están en verde contra la base de tests** (`tecnoservicios_testing`),
      con assertions reales (una suite con 0 assertions no prueba nada).
- [ ] **Sin errores** de lint ni de tipos (Pint, Larastan nivel 8, ESLint, `tsc` del build de
      producción).
- [ ] La **matriz de trazabilidad** está actualizada (estado ✅ y nombres reales de los tests).
- [ ] La pantalla se **verificó contra su maqueta** en PC y, si aplica, en celular.
- [ ] Tuvo **revisión de seguridad** si toca permisos o datos (permitido / denegado / IDOR /
      datos sensibles que no viajan en el JSON a quien no los puede ver).
- [ ] La **documentación está al día** (spec, dudas cerradas, manual si cambia algo que usa el
      cliente).
- [ ] **Un humano puede probarla de punta a punta** en el entorno de prueba, con datos
      ficticios, sin ayuda del equipo.

---

## 4. Configuración inicial de permisos (cap. 3 y W-15)

Es la base de los "permisos involucrados" de cada historia. Sectores de arranque (W-40), **11**
con los nombres del organigrama (resueltas DU-01 y DU-A01): Dirección · Calidad · Gerencia de
Operaciones · Jefatura de Proyectos · Ingeniería de proyectos · Seguridad e Higiene · Gerencia de
Administración · Jefatura de Administración · Compras · RRHH · Pañol y Logística (sin usuario por
ahora). Configuración inicial completa, celda por celda: `especificacion/identidad.md` §6.3
(12 filas; definitiva).

| Módulo (fila de W-15) | Configuración inicial firmada |
|---|---|
| Pedidos | Todos editan: todos piden y le pueden pedir a cualquiera; todos ven todos los pedidos de todas las obras. |
| Gastos y facturas | Editan Administración y Compras. Ven Operaciones, Proyectos e Ingeniería de proyectos. |
| Presupuesto por rubro | Lo carga Administración (editan Administración y Compras según W-15). Ven Operaciones, Proyectos e Ingeniería. |
| Rendición | Editan Administración y Compras. Ven Operaciones y Proyectos; Ingeniería de proyectos ve **la de sus obras**. |
| Monto de la obra y ganancia | Lo ven gerencias y administración. |
| Usuarios, sectores y permisos | Editan Dirección y las dos gerencias (en Administración, solo la gerencia). |
| Plazos de la prioridad (fila propia, DU-A01) | Editan las dos gerencias; Dirección ve. |
| Obras | Ven todos. Editan Gerencia de Operaciones, Jefatura de Proyectos, Ingeniería de proyectos, Gerencia y Jefatura de Administración (resuelta DU-02). |
| Estado de obra · Control por cantidad · Equipos en reparación (filas propias, DU-01/DU-A01) | Estado: editan Gerencia y Jefatura de Administración, más el responsable de **su** obra. Control por cantidad: editan administración y Compras (DU-A05). Equipos: todos editan, como Pedidos. |
| Proveedores | Editan administración y Compras; ven Dirección, Gerencia de Operaciones y Jefatura de Proyectos; Ingeniería sin acceso (W-17, DU-A02). |
| Resto de las celdas "a completar" | Arrancan en **Sin acceso** (DU-A02, DU-A03); Ingeniería ve solo sus obras en Gastos, Presupuesto por rubro, Rendición y Control por cantidad (DU-A04). |

Notación usada abajo: `modulo:nivel` (nivel `ver` o `editar`), `(propias)` = alcance solo sobre
las obras donde la persona es responsable.

---

## 5. Semana 1 · Base (detalle)

**25 historias** en 7 épicas. Resumen de estado DoR: **25 ✅ listas · 0 ⛔ bloqueadas** (las 6
que estaban ⛔ — HU-005, HU-010, HU-012, HU-015, HU-018 y HU-023 — se destrabaron con la
resolución del 12-nov; se suma la HU-025, transversal).

| Épica | Historias | ✅ | ⛔ |
|---|---|---|---|
| A · Ingreso y perfil | HU-001…HU-004 | 4 | 0 |
| B · Usuarios, sectores y permisos | HU-005…HU-011 | 7 | 0 |
| C · Obras y presupuestos por rubro | HU-012…HU-019 | 8 | 0 |
| D · Proveedores | HU-020…HU-021 | 2 | 0 |
| E · Buscador parcial | HU-022 | 1 | 0 |
| F · Navegación | HU-023…HU-024 | 2 | 0 |
| G · Transversal | HU-025 | 1 | 0 |

> Orden de construcción sugerido: B (HU-005, HU-009, HU-010) → A → F → G (HU-025) → C → D → E.
> Sin permisos aplicados no se puede probar ninguna otra historia con permitido/denegado, y sin
> adjuntos no se puede dar de alta una obra (la oferta técnica es obligatoria).

### Épica A · Ingreso y perfil

#### HU-001 · Entrar al sistema con el mail de la empresa
- **F-01 (cambiado por ADR-015) · W-16**
- **Como** cualquier persona de la empresa **quiero** entrar con mi mail y un código que me llega
  por mail, desde la PC o el celular, **para** usar el sistema sin compartir usuario y sin tener que
  acordarme de una contraseña.
- **Criterios de aceptación** (ADR-015, 13-nov)
  1. **Dado** un usuario activo, **cuando** escribe su mail y toca "Pedir código", **entonces** le
     llega un mail (por cola, desde noreply) con un código de 6 dígitos, y la pantalla le pide el código.
  2. **Dado** el código del mail, **cuando** lo escribe dentro de los 10 minutos, **entonces** entra y
     ve su pantalla de inicio. El código sirve una sola vez.
  3. **Dado** un mail que no existe o de un usuario **inactivo** (W-39), **cuando** pide el código,
     **entonces** ve el mismo mensaje "Si el mail está registrado, te llegó un código." y no se manda nada.
  4. **Dado** un código incorrecto, vencido (más de 10 minutos) o ya usado, **cuando** intenta entrar,
     **entonces** ve "El código no es válido o venció." (el mismo mensaje para un inactivo o un mail
     que no existe). Al 5.º intento fallido el código deja de servir y tiene que pedir otro.
  5. **Dado** que pidió un código nuevo, **cuando** escribe el anterior, **entonces** no sirve.
  6. **Dado** que está adentro, **cuando** cierra el navegador y vuelve dentro de los 90 días,
     **entonces** sigue adentro; cada vez que lo usa se renuevan los 90 días (no hay "recordarme").
  7. **Dado** que está adentro, **cuando** toca "Salir", **entonces** la sesión se cierra y
     cualquier pantalla interna lo devuelve al ingreso.
  8. **Dado** muchos pedidos de código o intentos seguidos desde el mismo mail o la misma IP,
     **cuando** intenta otra vez, **entonces** el sistema le pide esperar (429 con el sobre JSON).
  9. **Dado** un celular (ancho 390 px), **cuando** abre el ingreso, **entonces** la pantalla se
     usa sin zoom ni desplazamiento horizontal (W-16: "la misma pantalla sirve en la PC y en el
     celular").
- ~~Criterios del 12-nov (mail y contraseña): 1. entra con mail y contraseña correctos · 2. mensaje
  único "El mail o la contraseña no son correctos" · 3. inactivo con contraseña correcta no entra ·
  4. "Recordarme en este equipo" · 5. Salir · 6. 429 tras 5 intentos fallidos · 7. celular 390 px.~~
  **Reemplazados por ADR-015** (los que siguen valiendo pasaron arriba: el 5 al 7, el 6 al 8 y el 7 al 9).
- **Permisos:** ninguno (pantalla pública).
- **Dependencias:** HU-005 (existen usuarios); mail por cola configurado.
- **DoR:** ✅ listo.

#### HU-002 · ~~Recuperar la contraseña por mail~~ — reemplazada por ADR-015
> **13-nov (ADR-015):** ya no hay contraseña que recuperar: quien no puede entrar pide otro código
> (HU-001). La historia queda tachada como registro; no se construye.

- **F-01 · W-16**
- ~~**Como** cualquier persona **quiero** pedir un mail para elegir una contraseña nueva **para**~~
  ~~no depender de nadie si me la olvido.~~
- ~~**Criterios de aceptación**~~
  1. ~~**Dado** un mail de un usuario activo, **cuando** toca "Olvidé mi contraseña" y lo ingresa,~~
     ~~**entonces** recibe un mail (por cola, desde noreply) con un link para elegir una contraseña nueva.~~
  2. ~~**Dado** un mail que no existe o de un usuario inactivo, **cuando** pide recuperar, **entonces**~~
     ~~ve el mismo mensaje de confirmación que en el caso 1 y no se envía ningún mail.~~
  3. ~~**Dado** el link del mail, **cuando** elige una contraseña nueva válida, **entonces** puede~~
     ~~entrar con ella y la anterior deja de servir.~~
  4. ~~**Dado** un link ya usado o vencido (60 minutos), **cuando** lo abre, **entonces** ve que el~~
     ~~link no sirve más y puede pedir otro.~~
- ~~**Permisos:** ninguno.~~
- ~~**Dependencias:** HU-001; mail por cola configurado.~~
- **DoR:** reemplazada (ADR-015).

#### HU-003 · Ver mi perfil
- **F-10 (parte semana 1) · W-19**
- **Como** cualquier persona **quiero** ver mis datos, mi sector y de quién dependo **para**
  saber cómo estoy cargado en el sistema.
- **Criterios de aceptación**
  1. **Dado** un usuario adentro, **cuando** abre "Mi perfil", **entonces** ve su nombre, mail,
     sector, de quién depende y teléfono (si tiene).
  2. **Dado** su perfil, **cuando** intenta cambiar su sector o su jefatura, **entonces** no puede:
     se muestran como texto con la aclaración "Sector y jefatura los cambia gerencia (W-39)".
  3. **Dado** que manda por API un cambio de `sector_id` o `jefe_id` sobre su propio perfil,
     **entonces** el backend lo ignora o lo rechaza (403/422) y no cambia nada.
- **Fuera de esta semana:** el bloque "Mis avisos" (semana 2, con los avisos).
- **Permisos:** ninguno especial (todo usuario activo ve su propio perfil).
- **Dependencias:** HU-001, HU-005.
- **DoR:** ✅ listo.

#### HU-004 · ~~Cambiar mi contraseña~~ — reemplazada por ADR-015
> **13-nov (ADR-015):** no hay contraseña que cambiar; Mi perfil (W-19) no tiene esa sección. La
> historia queda tachada como registro; no se construye.

- **F-10 · W-19**
- ~~**Como** cualquier persona **quiero** cambiar mi contraseña desde mi perfil **para** mantener~~
  ~~mi cuenta segura.~~
- ~~**Criterios de aceptación**~~
  1. ~~**Dado** su contraseña actual correcta y una nueva válida (mínimo 8 caracteres) repetida~~
     ~~igual, **cuando** guarda, **entonces** la nueva queda vigente y ve la confirmación.~~
  2. ~~**Dado** una contraseña actual incorrecta, **cuando** guarda, **entonces** ve el error en el~~
     ~~campo y no cambia nada (422).~~
  3. ~~**Dado** que cambió la contraseña, **cuando** otra sesión suya abierta en otro equipo hace~~
     ~~su siguiente pedido al servidor, **entonces** esa sesión queda cerrada.~~
- ~~**Permisos:** ninguno especial.~~
- ~~**Dependencias:** HU-003.~~
- **DoR:** reemplazada (ADR-015).

### Épica B · Usuarios, sectores y permisos

#### HU-005 · Dar de alta un usuario en su sector y con su jefatura
- **F-58 · F-61 (dato "depende de") · W-39 · W-15**
- **Como** Dirección o gerencia **quiero** dar de alta a una persona con su sector y de quién
  depende **para** que entre al sistema y vea lo que le corresponde.
- **Criterios de aceptación**
  1. **Dado** `usuarios:editar`, **cuando** carga nombre, mail, sector y (opcional) "depende de"
     y teléfono y guarda, **entonces** el usuario queda **activo** y aparece en la lista de W-15.
  2. **Dado** un mail que ya tiene otro usuario (activo o inactivo), **cuando** guarda,
     **entonces** ve "Ya hay un usuario con ese mail" y no se crea (422).
  3. **Dado** que falta nombre, mail o sector, **cuando** guarda, **entonces** ve el error en cada
     campo que falta (sector es obligatorio: todo usuario tiene sector).
  4. **Dado** el usuario nuevo, **cuando** se guarda, **entonces** puede entrar enseguida con su mail
     pidiendo un código (HU-001); no se le manda ningún mail al darlo de alta (ADR-015).
  5. **Dado** un doble clic en "Guardar", **cuando** llegan los dos envíos con la misma
     `Idempotency-Key`, **entonces** se crea un solo usuario (ADR-011).
  - ~~4. Recibe un mail de bienvenida con un link para elegir su contraseña que dura 7 días · 5.
    "Reenviar mail de bienvenida" en W-15 con link nuevo que invalida el anterior.~~ **Reemplazados
    por ADR-015** (13-nov): no hay contraseña ni mail de bienvenida.
  6. **Dado** "depende de", **cuando** se elige, **entonces** solo se ofrecen usuarios activos y
     no se puede elegir a sí mismo.
  7. **Dado** un usuario sin `usuarios:editar`, **cuando** llama al alta por API, **entonces**
     recibe 403 y no se crea nada.
- **Permisos:** `usuarios:editar` (Dirección y las dos gerencias).
- **Dependencias:** HU-007 (existen sectores), HU-009.
- **DoR:** ✅ listo (DU-03, mail de bienvenida y reenvío, reemplazada por ADR-015).

#### HU-006 · Editar y desactivar un usuario
- **F-58 · F-61 · W-39 · W-15**
- **Como** Dirección o gerencia **quiero** corregir los datos de una persona, cambiarla de sector
  o de jefatura, o desactivarla **para** que el sistema refleje el organigrama sin perder el
  historial.
- **Criterios de aceptación**
  1. **Dado** `usuarios:editar`, **cuando** cambia el sector de una persona, **entonces** desde su
     siguiente pedido al servidor ve lo que permite su sector nuevo.
  2. **Dado** que cambia "depende de" de A para que dependa de B, **cuando** B depende (directa o
     indirectamente) de A, **entonces** ve "Esa jefatura armaría un círculo" y no se guarda (422).
  3. **Dado** un usuario activo, **cuando** lo pasa a **Inactivo**, **entonces** no puede entrar
     más (HU-001 caso 3), sus sesiones abiertas se cierran y sigue apareciendo como autor en el
     historial y como responsable en las obras donde lo era.
  4. **Dado** cualquier usuario, **cuando** se busca "borrar", **entonces** no existe la opción: solo
     Activo / Inactivo (W-39).
  5. **Dado** un usuario inactivo, **cuando** se lo vuelve a Activo, **entonces** puede volver a entrar
     con su mail pidiendo un código (ADR-015; antes: "con su contraseña anterior").
  6. **Dado** que una persona intenta desactivarse a sí misma, **entonces** el sistema no lo permite
     (evita quedarse afuera por error).
- **Permisos:** `usuarios:editar`.
- **Dependencias:** HU-005.
- **DoR:** ✅ listo.

#### HU-007 · Sectores: agregar, renombrar y sacar
- **F-59 · W-40**
- **Como** Dirección o gerencia **quiero** mantener la lista de sectores del organigrama **para**
  ordenar a la gente y sus permisos como trabaja la empresa.
- **Criterios de aceptación**
  1. **Dado** la instalación, **cuando** se abre W-40, **entonces** están los 11 sectores de arranque
     (sección 4), con "Pañol y Logística" en 0 usuarios.
  2. **Dado** `usuarios:editar`, **cuando** agrega un sector con nombre nuevo, **entonces** aparece en
     la lista y como **una fila más en la tabla de permisos** (W-15), con Pedidos y Equipos en
     reparación en "Editar", Obras en "Ver" y el resto en "Sin acceso" (resuelta DU-A06).
  3. **Dado** un nombre que ya existe (sin distinguir mayúsculas ni tildes), **cuando** agrega o
     renombra, **entonces** ve el error y no se guarda.
  4. **Dado** un sector renombrado, **cuando** se guarda, **entonces** sus usuarios y sus permisos
     siguen iguales y se ve el nombre nuevo en todas las pantallas.
  5. **Dado** un sector con usuarios **activos**, **cuando** intenta sacarlo, **entonces** ve "Antes
     de sacar {sector}, pasá a sus N usuarios a otro sector." y no se saca (422).
  6. **Dado** un sector que nunca se usó (sin usuarios ni pedidos), **cuando** lo saca, **entonces**
     se borra y desaparece de la lista y de la tabla de permisos.
  7. **Dado** un sector con historia (usuarios inactivos o pedidos), **cuando** lo saca, **entonces**
     se **archiva**: sale de los desplegables y de la tabla, y lo histórico conserva su nombre
     (resuelta DU-A07).
- **Permisos:** `usuarios:editar`.
- **Dependencias:** HU-009.
- **DoR:** ✅ listo. (El caso de sacar un sector que tenga pedidos asignados "al sector" se
  agrega en la semana 2, con los pedidos.)

#### HU-008 · Tabla de permisos: ver y cambiar
- **F-60 · W-15**
- **Como** Dirección o gerencia **quiero** elegir, para cada sector y cada módulo, Editar, Ver o
  Sin acceso **para** decidir quién ve y quién edita cada cosa, y cambiarlo cuando quiera.
- **Criterios de aceptación**
  1. **Dado** `usuarios:editar`, **cuando** abre la tabla, **entonces** ve una fila por sector y una
     columna por módulo, cada celda con Editar / Ver / Sin acceso, y en Rendición la opción de
     alcance "sus obras".
  2. **Dado** que cambia una celda y guarda, **cuando** una persona de ese sector hace su siguiente
     pedido al servidor, **entonces** ya rige el permiso nuevo (no hace falta salir y volver a entrar).
  3. **Dado** un cambio que dejaría **0 usuarios activos** con Editar en "Usuarios y permisos",
     **cuando** guarda, **entonces** ve "Tiene que quedar al menos una persona que pueda administrar
     usuarios y permisos." y no se guarda (resuelta DU-A09).
  4. **Dado** cada cambio de celda, **cuando** se guarda, **entonces** queda registrado quién lo
     cambió, cuándo y el valor anterior.
  5. **Dado** `usuarios:ver` o sin acceso, **cuando** manda un cambio por API, **entonces** recibe 403.
- **Permisos:** `usuarios:editar` (ver la tabla: `usuarios:ver`).
- **Dependencias:** HU-009.
- **DoR:** ✅ listo.

#### HU-009 · Que la tabla de permisos se cumpla en todo el sistema
- **F-60 · F-02 · W-15 · W-17**
- **Como** gerencia **quiero** que lo que pongo en la tabla se aplique en cada pantalla y en cada
  acción **para** que nadie vea ni toque lo que no le corresponde, aunque escriba la dirección a mano.
- **Criterios de aceptación**
  1. **Dado** un sector con "Sin acceso" en un módulo, **cuando** una persona de ese sector pide
     cualquier dato de ese módulo por API o por URL, **entonces** recibe 403 con el sobre JSON y la
     pantalla muestra "No tenés acceso a esta sección".
  2. **Dado** "Ver", **cuando** intenta crear, editar o borrar, **entonces** recibe 403 y en la
     pantalla no aparecen los botones de edición.
  3. **Dado** alcance "sus obras" (Ingeniería en Rendición), **cuando** pide la rendición de una obra
     donde **no** es responsable, **entonces** recibe 403 (no 404 con datos ni 200 vacío), aunque
     conozca el id (IDOR).
  4. **Dado** el catálogo de permisos, **cuando** se revisa el código, **entonces** ninguna regla
     pregunta por el nombre de un sector o de una persona: todas leen la tabla (test de arquitectura).
- **Permisos:** todos los de la tabla.
- **Dependencias:** HU-010.
- **DoR:** ✅ listo.

#### HU-010 · Arrancar con la configuración inicial firmada
- **F-60 · cap. 3 · W-15**
- **Como** la empresa **quiero** que el sistema arranque con la configuración de permisos que
  aprobamos **para** empezar a usarlo sin armar la tabla de cero.
- **Criterios de aceptación**
  1. **Dado** una base nueva, **cuando** se instala el sistema (migraciones, no solo seeders),
     **entonces** la tabla de permisos tiene exactamente la configuración de la sección 4.
  2. **Dado** la configuración inicial, **cuando** una persona de Operaciones intenta editar un
     gasto, **entonces** recibe 403 ("Operaciones ve y no puede tocar nada").
  3. **Dado** la configuración inicial, **cuando** una persona de Ingeniería de proyectos abre la
     rendición de una obra donde es responsable, **entonces** la ve; de otra obra, 403.
  4. **Dado** la configuración inicial, **cuando** alguien de Ingeniería pide el monto de la obra y
     la ganancia, **entonces** esos datos **no vienen en el JSON** (no alcanza con ocultarlos en
     pantalla).
  5. **Dado** Administración, **cuando** se aplica "Usuarios: Editar, solo la gerencia" (W-15),
     **entonces** el sector Gerencia de Administración edita usuarios y la Jefatura de
     Administración no (sectores separados, resuelta DU-01).
  6. **Dado** la configuración inicial, **cuando** se revisa celda por celda, **entonces** coincide
     con las 12 filas × 11 sectores de `especificacion/identidad.md` §6.3 (una aserción por celda).
- **Permisos:** —
- **Dependencias:** HU-007, catálogo de módulos.
- **DoR:** ✅ listo (resueltas DU-01, DU-02 y DU-A01…DU-A06: filas, sectores y celdas de arranque).

#### HU-011 · Plazos de la prioridad
- **F-68 (parte semana 1) · W-50**
- **Como** gerencia **quiero** definir con cuántos días de anticipación un pedido es urgente,
  prioritario o normal **para** que la prioridad automática refleje cómo trabajamos.
- **Criterios de aceptación**
  1. **Dado** la instalación, **cuando** se abre W-50, **entonces** muestra los plazos de arranque:
     urgente hasta 1 día, prioritario hasta 7 días, normal más de 7.
  2. **Dado** `plazos_prioridad:editar`, **cuando** cambia "urgente hasta" o "prioritario hasta" a números
     enteros donde urgente < prioritario, **entonces** se guardan y "normal" se muestra como "más de
     <prioritario> días".
  3. **Dado** urgente ≥ prioritario, un número negativo o vacío, **cuando** guarda, **entonces** ve el
     error y no se guarda (422).
  4. **Dado** un cambio de plazos, **cuando** se guarda, **entonces** queda registrado quién lo
     cambió, cuándo y los valores anteriores.
  5. **Dado** un usuario sin `plazos_prioridad:editar`, **cuando** intenta guardar, **entonces** 403.
- **Fuera de esta semana:** el cálculo de la prioridad de cada pedido (F-26), en **días corridos**
  y sin feriados (resuelta DU-04), y la regla de que el cambio vale solo para los pedidos nuevos
  (semana 2).
- **Permisos:** `plazos_prioridad:editar` (las dos gerencias; fila propia, resuelta DU-A01). W-50
  vive en la sección Usuarios.
- **Dependencias:** HU-009.
- **DoR:** ✅ listo.

### Épica C · Obras y presupuestos por rubro

#### HU-012 · Dar de alta una obra
- **F-16 · F-23 · W-06**
- **Como** Operaciones (o Administración) **quiero** abrir el legajo de una obra ganada con lo
  mínimo **para** empezar a cargarle pedidos.
- **Criterios de aceptación**
  1. **Dado** `obras:editar`, **cuando** carga N.º de presupuesto y revisión, nombre del trabajo,
     la oferta técnica (**archivo o link**), cliente, responsable de obra y los dos contactos
     (solicitante en planta y comprador, cada uno con nombre, mail y teléfono) y guarda,
     **entonces** la obra queda creada en estado **Confirmada** con ese presupuesto como principal.
  2. **Dado** que no carga OC / OT / contrato / mail de aprobación (ni archivo o link ni número),
     **cuando** guarda, **entonces** la obra se crea igual (la OC no es obligatoria).
  3. **Dado** que falta cualquier dato obligatorio (N.º, revisión, nombre del trabajo, oferta
     técnica como archivo o link, cliente, responsable, o nombre/mail/teléfono de cualquiera de los
     dos contactos), **cuando** guarda, **entonces** ve el error en cada campo y no se crea (422).
  4. **Dado** planta y fechas previstas de inicio y fin, **cuando** las carga, **entonces** se
     guardan; si no las carga, la obra se crea igual. Si el fin previsto es anterior al inicio
     previsto, ve el error.
  5. **Dado** que manda `estado` en el alta (por ejemplo "Terminada"), **cuando** guarda,
     **entonces** el backend lo ignora: siempre nace Confirmada.
  6. **Dado** un N.º de presupuesto que ya está en otra obra (comparado normalizado y con su sufijo,
     sin la revisión: `2-31-2026` = `2-031-2026`), **cuando** guarda, **entonces** ve "El presupuesto
     … ya está en la obra '…'" con el link a esa obra y no se crea (422). Formato `P-NNN-AAAA` con
     sufijo opcional ` A1`; revisión `REVnn` precargada en `REV01` (resueltas DU-05 y DU-A16).
  7. **Dado** el cliente, **cuando** lo escribe, **entonces** ve sugerencias de los clientes ya
     usados en otras obras, puede elegir uno o escribir uno nuevo, y si lo escrito se parece a uno
     existente ve el aviso (resuelta DU-06).
  8. **Dado** el alta guardada, **entonces** queda registrado quién la creó y cuándo (F-14).
  9. **Dado** un mail de contacto mal escrito o un link (oferta, OC o carpeta de Drive) que no empieza
     con `http(s)://`, **cuando** guarda, **entonces** ve el error.
  10. **Dado** la oferta técnica como **archivo** (PDF, imagen —también HEIC del celular—, Excel o
      Word, hasta 20 MB), **cuando** la sube, **entonces** queda en la obra con su nombre y tamaño;
      un tipo no permitido o un archivo más grande se rechaza con el motivo (HU-025).
  11. **Dado** la carpeta de Drive de la obra, **entonces** solo se puede pegar el link (no se
      sube una carpeta).
- **Permisos:** `obras:editar` (Gerencia de Operaciones, Jefatura de Proyectos, Ingeniería de
  proyectos, Gerencia y Jefatura de Administración — resuelta DU-02).
- **Dependencias:** HU-005 (responsable = usuario activo), HU-009, HU-025 (adjuntos).
- **DoR:** ✅ listo (resueltas DU-05, DU-06, DU-A14 y DU-A16; adjuntos por ADR-014).

#### HU-013 · Listado de obras por estado
- **F-15 (parte semana 1) · F-22 · W-05**
- **Como** cualquier persona **quiero** ver las obras separadas en En ejecución, Confirmadas y
  Terminadas **para** encontrar una rápido y consultar las terminadas sin que se pierdan.
- **Criterios de aceptación**
  1. **Dado** `obras:ver`, **cuando** abre Obras, **entonces** ve tres pestañas con su cantidad: En
     ejecución, Confirmadas y Terminadas, y arranca en En ejecución (primero las activas).
  2. **Dado** cada fila, **entonces** muestra N.º de presupuesto con su revisión (y "+ N adicional"
     si tiene), trabajo, cliente y planta juntos, y responsable de obra.
  3. **Dado** una obra pasada a Terminada, **cuando** se abre la pestaña Terminadas, **entonces**
     está ahí y se puede abrir su ficha con todo lo suyo (no se pierde nada).
  4. **Dado** el filtro "Responsable", **cuando** elige una persona, **entonces** ve solo sus obras.
  5. **Dado** más de 25 obras en una pestaña, **entonces** la lista se pagina sin perder la pestaña
     ni el filtro.
- **Fuera de esta semana:** "pedidos abiertos" y la fila de Servicios generales (semana 2), "% del
  presupuesto gastado" en rojo si se pasó (semana 3). Las columnas se muestran desde ya con "—"
  para no rediseñar la tabla después.
- **Permisos:** `obras:ver`.
- **Dependencias:** HU-012.
- **DoR:** ✅ listo.

#### HU-014 · Ficha de la obra con Documentos y contactos
- **F-17 (parte semana 1) · F-19 · W-07 · W-20**
- **Como** cualquier persona **quiero** entrar a una obra y tener a mano sus presupuestos, su OC,
  sus certificados, la carpeta de Drive y los contactos **para** no buscarlos en el Drive ni en el mail.
- **Criterios de aceptación**
  1. **Dado** `obras:ver`, **cuando** abre una obra, **entonces** ve su encabezado (N.º, trabajo,
     cliente y planta, responsable, estado, fechas previstas) y las pestañas Pedidos · Rendición ·
     Documentos y contactos.
  2. **Dado** la pestaña Documentos y contactos, **entonces** ve cada presupuesto (principal y
     adicionales) con su revisión y su oferta técnica (archivo o link); la OC / OT / contrato / mail
     de aprobación con número y su archivo o link; los certificados y otros documentos con sus
     archivos o links; el link a la carpeta de Drive de la obra; y los contactos solicitante y
     comprador con mail y teléfono.
  3. **Dado** `obras:editar`, **cuando** agrega un certificado (nombre + fecha + uno o más archivos
     o links) o el link a la carpeta de Drive, **entonces** queda en la pestaña y en el registro de
     la obra.
  4. **Dado** un link, **cuando** lo toca, **entonces** se abre en una pestaña nueva; **dado** un
     archivo, **cuando** lo toca, **entonces** se baja con su nombre original (solo si puede ver la
     obra: con un id ajeno o sin permiso, 404).
  5. **Dado** `obras:editar`, **cuando** quita un certificado o uno de sus archivos o links,
     **entonces** desaparece de la pestaña, el archivo se borra y queda en el registro de la obra.
  6. **Dado** el id de una obra que no existe, **cuando** abre la ficha, **entonces** ve "Obra no
     encontrada" (404 con el sobre JSON).
- **Fuera de esta semana:** el contenido de las pestañas Pedidos (semana 2) y Rendición (semana 3);
  hasta entonces muestran "Disponible en la próxima entrega".
- **Permisos:** `obras:ver` (también para bajar los archivos); agregar o quitar certificados,
  documentos y carpeta: `obras:editar`.
- **Dependencias:** HU-012, HU-025.
- **DoR:** ✅ listo.

#### HU-015 · Agregar un presupuesto adicional a la obra
- **F-18 · W-21**
- **Como** Operaciones o Administración **quiero** sumar a la misma obra un presupuesto adicional
  (como el "A1") **para** no abrir otra obra y que su plata se sume a la de la obra.
- **Criterios de aceptación**
  1. **Dado** `obras:editar`, **cuando** carga N.º de presupuesto (y, si los tiene, revisión, la
     oferta técnica como archivo o link, N.º y archivo o link de la OC y una descripción) y guarda,
     **entonces** el adicional queda en la obra; quien tiene `presupuesto_rubro:editar` pasa a cargar
     sus montos por rubro (HU-018) y el resto ve solo "Guardar".
  2. **Dado** una obra con 1 presupuesto, **cuando** se agregan 2 adicionales, **entonces** la obra
     tiene 3 y los muestra en orden de alta (como en sus rendiciones: 1 a 3 por obra).
  3. **Dado** que falta el N.º, **cuando** guarda, **entonces** ve el error; en un adicional la
     revisión, la oferta técnica, la OC y la descripción son opcionales (resuelta DU-A14).
  4. **Dado** el N.º sugerido (`2-131-2026 A1`, después `A2`), **cuando** lo deja o lo cambia por un
     número propio con formato válido, **entonces** se guarda (resuelta DU-05).
  5. **Dado** una obra **Terminada**, **cuando** agrega un adicional, **entonces** se permite (la
     obra se consulta igual) y queda registrado.
  6. **Dado** el alta del adicional, **entonces** queda en el registro de la obra (F-14).
- **Permisos:** `obras:editar`.
- **Dependencias:** HU-012, HU-025.
- **DoR:** ✅ listo (resueltas DU-05 y DU-A14).

#### HU-016 · Editar los datos de la obra con registro del cambio
- **F-20 · F-14 (obras) · W-23**
- **Como** quien tiene permiso **quiero** corregir o completar los datos de la obra (por ejemplo el
  N.º de OC cuando llega) **para** tenerla al día sabiendo quién cambió qué.
- **Criterios de aceptación**
  1. **Dado** `obras:editar`, **cuando** abre Editar, **entonces** ve el mismo formulario del alta
     con los datos cargados, **sin** el estado (el estado se cambia con su botón, W-22).
  2. **Dado** que cambia uno o más datos y guarda, **entonces** abajo, en el historial, aparece una
     línea por dato con quién, cuándo, valor anterior y valor nuevo.
  3. **Dado** que guarda sin cambiar nada, **entonces** no se agrega ninguna línea al historial.
  4. **Dado** que manda `estado` por la edición, **entonces** el backend lo ignora.
  5. **Dado** `obras:ver`, **cuando** intenta editar por API, **entonces** 403.
  6. **Dado** las mismas validaciones del alta (HU-012), **entonces** aplican igual en la edición.
- **Permisos:** `obras:editar`.
- **Dependencias:** HU-012.
- **DoR:** ✅ listo.

#### HU-017 · Cambiar el estado de la obra
- **F-21 (parte semana 1) · W-22**
- **Como** responsable de obra o Administración **quiero** pasar la obra de Confirmada a En
  ejecución y de En ejecución a Terminada **para** que el listado muestre dónde está cada una.
- **Criterios de aceptación**
  1. **Dado** una obra Confirmada y la persona que es su **responsable de obra**, **cuando** la pasa
     a En ejecución, **entonces** cambia de pestaña en el listado y queda registrado quién y cuándo.
  2. **Dado** una persona de **Gerencia o Jefatura de Administración** (`estado_obra:editar`), **cuando** cambia el
     estado de cualquier obra, **entonces** se permite.
  3. **Dado** una persona de Operaciones que **no** es responsable de esa obra, **cuando** intenta
     cambiar el estado, **entonces** recibe 403 (el estado lo cambian el responsable y Administración).
  4. **Dado** una obra En ejecución, **cuando** la pasan a Terminada, **entonces** sale de las activas
     y queda en Terminadas con todo lo suyo.
  5. **Dado** el cambio de estado, **entonces** solo se hace desde W-22 (la edición no lo cambia).
  6. **Dado** una obra pasada por error, **cuando** quien puede cambiar el estado la vuelve **un paso
     atrás** (Terminada → En ejecución, En ejecución → Confirmada), **entonces** se permite y queda
     registrado; saltar de Confirmada a Terminada (o al revés) da error (resuelta DU-07).
- **Fuera de esta semana:** la lista de pedidos abiertos al pasar a Terminada (semana 2): muestra
  los abiertos y **no frena** el pase (resuelta DU-12).
- **Permisos:** el **responsable de esa obra** (regla fija) o `estado_obra:editar` (Gerencia y
  Jefatura de Administración).
- **Dependencias:** HU-012.
- **DoR:** ✅ listo.

#### HU-018 · Cargar el presupuesto por rubro de cada presupuesto
- **F-48 · W-13**
- **Como** Administración **quiero** cargar una sola vez, por cada presupuesto de la obra, cuánto
  hay para gastar en cada rubro (o solo el total) **para** que la rendición tenga contra qué comparar.
- **Criterios de aceptación**
  1. **Dado** `presupuesto_rubro:editar`, **cuando** abre W-13, **entonces** ve una fila por rubro
     (MATERIALES · INSUMOS · SUBCONTRATOS · LOGISTICA · AMORTIZACION · MANTENIMIENTO ·
     OBRADORES/BAÑOS · SERVICIOS GENERALES · MAQUINAS/HERRAMIENTAS · VIATICOS · FIJOS · HORAS
     LIQUIDADAS, escritos como en sus planillas), una columna por presupuesto y la columna Total de
     la obra = suma de las columnas.
  2. **Dado** que carga importes (pesos, sin IVA, hasta 2 decimales, sin negativos) y guarda,
     **entonces** quedan guardados y el total de la obra se recalcula.
  3. **Dado** que EPP no es fila (se suma a INSUMOS en el resumen), **cuando** se intenta guardar un
     importe en un rubro EPP o en un rubro que no está en la lista (por ejemplo "INSUMO"),
     **entonces** el backend lo rechaza (422): la lista es cerrada.
  4. **Dado** un presupuesto del que solo se conoce el total, **cuando** elige "solo el total" y
     carga un importe, **entonces** queda guardado como total sin rubros (como hoy en 2 de sus 6
     rendiciones). "Por rubro" o "solo total" se elige **por presupuesto**; si la obra mezcla los
     dos, W-13 muestra el total del de solo total en la fila Total y "—" en sus rubros, y la
     rendición (semana 3) lo suma en la fila "Sin desglose por rubro" (resuelta DU-08).
  5. **Dado** una obra sin presupuesto por rubro cargado, **entonces** la obra existe igual y se
     puede trabajar (los gastos se cargan igual en la semana 3).
  6. **Dado** Operaciones o Ingeniería (`ver`), **cuando** abre W-13, **entonces** lo ve sin poder
     editar; por API, 403 al guardar.
  7. **Dado** un presupuesto ya cargado, **cuando** Administración corrige un importe, **entonces**
     se guarda y queda en el historial con valor anterior, quién y cuándo (resuelta DU-09).
  8. **Dado** `monto_obra:editar`, **cuando** carga en W-13 la fila "Monto del presupuesto" de cada
     presupuesto, **entonces** se guarda; quien no tiene `monto_obra:ver` no ve la fila y el dato no
     viaja en el JSON (resuelta DU-19).
- **Permisos:** `presupuesto_rubro:editar` (Administración y Compras); `presupuesto_rubro:ver`
  (Operaciones, Proyectos, Ingeniería).
- **Dependencias:** HU-012, HU-015.
- **DoR:** ✅ listo (resueltas DU-08, DU-09 y DU-19).

#### HU-019 · Que una obra sin presupuesto por rubro igual se pueda usar
- **F-48 · F-16 · W-07**
- **Como** Operaciones **quiero** que una obra recién dada de alta sirva para cargarle pedidos
  aunque Administración todavía no cargó su presupuesto por rubro **para** no frenar el arranque.
- **Criterios de aceptación**
  1. **Dado** una obra Confirmada sin presupuesto por rubro, **cuando** se abre su ficha, **entonces**
     se ve normal y en la pestaña Documentos el presupuesto aparece con "Presupuesto por rubro: sin
     cargar" (visible solo para quien ve Presupuesto por rubro).
  2. **Dado** esa misma obra, **cuando** la elige un pedido (semana 2), **entonces** está disponible
     (el chequeo de "obra elegible para pedidos" no mira el presupuesto por rubro).
- **Permisos:** `obras:ver`, `presupuesto_rubro:ver`.
- **Dependencias:** HU-012, HU-014.
- **DoR:** ✅ listo.

### Épica D · Proveedores

#### HU-020 · Base de proveedores con buscador
- **F-55 · W-14**
- **Como** Administración o Compras **quiero** una sola lista de proveedores con buscador **para**
  elegirlos siempre igual al cargar facturas.
- **Criterios de aceptación**
  1. **Dado** `proveedores:ver`, **cuando** abre Proveedores, **entonces** ve la lista ordenada por
     nombre, con CUIT y rubro habitual si los tiene, paginada.
  2. **Dado** el buscador, **cuando** escribe parte del nombre ("hormig"), **entonces** ve los
     proveedores que lo contienen, sin distinguir mayúsculas ni tildes.
  3. **Dado** un proveedor elegido, **cuando** abre su ficha, **entonces** ve sus datos; el bloque
     "Gastado por obra" muestra "Disponible cuando se carguen gastos" hasta la semana 3 (F-57).
  4. **Dado** la base, **entonces** no hay saldos, vencimientos ni cuenta corriente (siguen en Tango).
  5. **Dado** sin acceso a Proveedores, **cuando** pide la lista por API, **entonces** 403.
- **Permisos:** `proveedores:ver` / `proveedores:editar` (Administración y Compras editan; ven Dirección,
  Gerencia de Operaciones y Jefatura de Proyectos; Ingeniería y el resto sin acceso — resueltas DU-02 y DU-A02).
- **Dependencias:** HU-009.
- **DoR:** ✅ listo.

#### HU-021 · Alta y edición de proveedor con aviso de parecidos
- **F-56 · W-38**
- **Como** Administración o Compras **quiero** dar de alta un proveedor una sola vez, con solo el
  nombre obligatorio, y que el sistema me avise si ya hay uno parecido **para** no cargar el mismo
  proveedor de cuatro formas distintas, como pasa hoy.
- **Criterios de aceptación**
  1. **Dado** `proveedores:editar`, **cuando** carga solo el nombre y guarda, **entonces** el
     proveedor queda creado.
  2. **Dado** CUIT, rubro habitual (de la lista cerrada de rubros, EPP incluido) y contacto
     (teléfono y mail), **cuando** los carga, **entonces** se guardan; un CUIT con formato o dígito
     verificador inválido muestra el error.
  3. **Dado** que escribe un nombre, **cuando** hay proveedores parecidos ya cargados (por ejemplo
     "Hormigonera del Sur" contra "Hormigonera Sur SRL" y "Hormigones del Sur"), **entonces** ve la
     lista "Parecidos ya cargados" y **puede guardar igual**.
  4. **Dado** un nombre idéntico a uno existente o un CUIT que ya tiene otro proveedor, **cuando**
     guarda, **entonces** ve "Ya existe …" o "Ese CUIT ya es de …" con el link a ese proveedor y no
     se guarda (422; resuelta DU-10).
  5. **Dado** un proveedor, **cuando** lo edita, **entonces** se guardan los cambios con quién y cuándo.
  6. **Dado** la ficha, **entonces** no existe "borrar" (F-56 es alta y edición).
- **Permisos:** `proveedores:editar`.
- **Dependencias:** HU-020.
- **DoR:** ✅ listo (resuelta DU-10).

### Épica E · Buscador parcial

#### HU-022 · Buscador general: obras y proveedores
- **F-03 (parte semana 1) · W-18**
- **Como** cualquier persona **quiero** un solo buscador arriba donde escribo una parte del N.º de
  presupuesto, del cliente o del trabajo **para** encontrar la obra sin recorrer solapas, como hoy
  en la minuta.
- **Criterios de aceptación**
  1. **Dado** que escribe "087", **cuando** busca, **entonces** encuentra la obra 1-087-2026 (y sus
     adicionales) por N.º de presupuesto parcial.
  2. **Dado** que escribe parte del cliente, de la planta o del nombre del trabajo ("tanque"), **entonces** ve las
     obras que lo contienen, sin distinguir mayúsculas ni tildes, de cualquier estado (las terminadas
     también, marcadas "Terminada").
  3. **Dado** que escribe parte del nombre de un proveedor, **entonces** lo ve en el grupo Proveedores
     **solo si** tiene acceso a Proveedores.
  4. **Dado** los resultados, **entonces** se agrupan por tipo (Obras, Proveedores) con su cantidad y
     cada uno abre su ficha.
  5. **Dado** menos de 2 caracteres, **cuando** escribe, **entonces** no busca todavía.
  6. **Dado** sin acceso a un módulo, **cuando** busca, **entonces** ese grupo no aparece ni viaja en
     la respuesta.
- **Fuera de esta semana:** pedidos por texto (semana 2) y gastos por número de factura (semana 3).
  La planta entra en la búsqueda (resuelta DU-11).
- **Permisos:** `obras:ver`, `proveedores:ver`.
- **Dependencias:** HU-013, HU-020.
- **DoR:** ✅ listo.

### Épica F · Navegación

#### HU-023 · Barra de arriba según el sector
- **F-02 · W-17**
- **Como** cualquier persona **quiero** ver en la barra solo los módulos que mi sector tiene
  habilitados **para** no perderme en pantallas que no uso.
- **Criterios de aceptación**
  1. **Dado** la barra única (Inicio · Pedidos · Obras · Calendario · Gastos · Rendiciones ·
     Proveedores · Usuarios), **cuando** una persona entra, **entonces** ve solo los módulos con
     Ver o Editar en su sector; "Sin acceso" no aparece. Cada ítem sale de las claves del catálogo
     (`identidad.md` §6.1 y §6.4): Pedidos ← `pedidos` o `equipos_reparacion`; Obras ← `obras`;
     Calendario ← `pedidos`; Gastos ← `gastos`; Rendiciones ← `rendicion`; Proveedores ←
     `proveedores`; Usuarios ← `usuarios_permisos` o `plazos_prioridad`. `GET /api/me` trae siempre
     las 12 claves (el front alinea su `FUNCIONES_NAV`, D-UI-01).
  2. **Dado** que gerencia cambia la tabla, **cuando** la persona navega a otra pantalla o vuelve a
     enfocar la pestaña, **entonces** la barra refleja el cambio sin volver a entrar.
  3. **Dado** que escribe a mano la dirección de un módulo sin acceso, **entonces** ve "No tenés
     acceso a esta sección" (y el backend responde 403).
  4. **Dado** la configuración inicial, **entonces** Ingeniería ve Gastos y Rendiciones en modo solo
     lectura y Administración ve todo lo de Gastos, Proveedores y Rendición (W-17).
  5. **Dado** un módulo que todavía no se entregó (Pedidos, Calendario, Gastos y Rendiciones en la
     semana 1), **entonces** no se muestra en la barra hasta su semana.
- **Permisos:** todos (lectura del propio permiso).
- **Dependencias:** HU-009.
- **DoR:** ✅ listo (resueltas DU-01, DU-A01 y DU-A12; claves de función en `identidad.md` §6.1).

#### HU-024 · Inicio de la semana 1 y celular básico
- **F-02 · F-13 (base) · W-17 · W-16**
- **Como** cualquier persona **quiero** que al entrar la primera semana llegue a una pantalla útil,
  también desde el celular, **para** probar lo entregado sin perderme.
- **Criterios de aceptación**
  1. **Dado** la semana 1 (todavía no hay Mis tareas), **cuando** entra, **entonces** llega al
     listado de Obras (o a Usuarios si no tiene acceso a Obras).
  2. **Dado** un celular de 390 px, **cuando** recorre ingreso, obras, ficha de obra y proveedores,
     **entonces** se pueden usar sin desplazamiento horizontal de la página (las pantallas
     específicas de celular W-41…W-47 llegan en la semana 2).
- **Permisos:** —
- **Dependencias:** HU-023.
- **DoR:** ✅ listo.

### Épica G · Transversal

#### HU-025 · Adjuntos: subir el archivo o poner el link
- **ADR-014 (amplía D-022) · DU-23** · F-16, F-18 (oferta técnica y OC/OT/contrato/mail) · F-19
  (certificados y documentos de la obra) · F-27 (pedido) · F-31 (comprobante de cierre) · F-43, F-44
  (factura y tickets) · F-47, F-66 (remito de retiros) · F-67 (presupuesto e inventario de equipos)
- **W-06, W-20, W-21** (semana 1) · W-08, W-43, W-29, W-42, W-49 (semana 2) · W-10, W-35, W-46, W-48
  (semana 3)
- **Como** cualquier persona que carga un documento **quiero** subir el archivo (también una foto
  desde el celular) o pegar el link al Drive, según lo que tenga a mano, **para** no tener que
  subirlo primero al Drive para poder cargarlo.
- **Criterios de aceptación**
  1. **Dado** un usuario activo, **cuando** sube un PDF, una imagen (JPG, PNG, WEBP o HEIC/HEIF del
     iPhone), un Excel, un Word, un CSV o un TXT de hasta 20 MB, **entonces** recibe el id del
     adjunto con nombre, tamaño y tipo detectado, y el formulario lo muestra con "quitar".
  2. **Dado** un archivo de otro tipo (ejecutable, HTML, SVG) **o** con la extensión cambiada (un
     `.exe` renombrado a `.pdf`), **cuando** lo sube, **entonces** se rechaza (422) porque el tipo se
     valida **por contenido**, con el mensaje de los tipos aceptados.
  3. **Dado** un archivo de más de 20 MB, **cuando** lo sube, **entonces** ve "El archivo pesa N MB y
     el máximo es 20 MB." y no se guarda.
  4. **Dado** un link, **cuando** lo pega, **entonces** se acepta solo si empieza con `http://` o
     `https://` (no `javascript:`, `file:`, `ftp:` ni texto suelto).
  5. **Dado** un adjunto subido, **cuando** se guarda el recurso (por ejemplo, la obra), **entonces**
     queda asociado en la misma operación; si el recurso no se guarda, el adjunto queda temporal y
     **se borra a las 24 h** (fila y archivo).
  6. **Dado** un archivo de una obra, **cuando** lo baja alguien que puede ver esa obra, **entonces**
     lo recibe como descarga con su nombre original; **cuando** lo pide alguien que no la puede ver
     (otro sector sin acceso, o Ingeniería con "solo sus obras" en una obra ajena), aunque conozca el
     id, **entonces** recibe 404 y no se confirma que exista.
  7. **Dado** `obras:editar`, **cuando** quita un adjunto, **entonces** se borra el archivo físico y
     queda en el historial de la obra con el nombre del archivo (sin la URL ni la ruta).
  8. **Dado** el celular, **cuando** toca "Subir archivo", **entonces** puede elegir la cámara o la
     galería; la foto HEIC se acepta tal cual.
  9. **Dado** la carpeta de Drive de la obra, **entonces** solo admite link.
- **Seguridad:** disco privado (`storage/app/private/adjuntos/AAAA/MM/`) con nombre UUID, nunca
  servido desde `public/`; descarga solo por `GET /api/adjuntos/{id}` con la Policy del recurso
  dueño, `Content-Disposition: attachment`, MIME detectado y `X-Content-Type-Options: nosniff`;
  IDOR → 404; solo se asocian adjuntos temporales **propios** y del **contexto** del campo; la
  `ruta` no viaja en el JSON; límite de subidas por minuto; borrar deja actividad (ADR-010); entra
  en el backup diario. `seguridad.md` (a crear, ADR-014) suma estos tests (tipo rechazado, tamaño, IDOR).
- **Fuera de esta semana:** los contextos de pedidos y equipos (semana 2) y de gastos y retiros
  (semana 3) reusan este mecanismo sin cambios; cada historia de esas semanas solo agrega su
  contexto y su prueba.
- **Permisos:** subir: cualquier usuario activo; asociar y quitar: editar el recurso dueño; bajar:
  ver el recurso dueño (`identidad.md` §6.6).
- **Dependencias:** HU-009 (permisos aplicados).
- **DoR:** ✅ listo (resuelta DU-23 → ADR-014; contrato en `obras.md` §10.5).

---

## 6. Semana 2 · Pedidos (la minuta) (detalle)

> Bajada a historias el 16-nov-2026, con la semana 1 cerrada y en `main` (QC: apta, B-01…B-05
> arreglados). Contrato: cap. 4.2, 5 y 7 del documento firmado, maquetas W-01…W-04, W-07…W-09,
> W-18, W-19, W-22, W-24…W-32, W-41…W-43, W-45, W-47, W-49, W-50, ADR-005, ADR-011, ADR-012 y
> ADR-014 (contextos `pedido`, `comprobante_cierre`, `equipo_presupuesto`, `equipo_inventario`).
> Las reglas finas (RN-PED-NN, RN-AVI-NN) las escribe el Analista en `especificacion/pedidos.md`
> y `especificacion/avisos.md`; acá los criterios citan "spec pedidos.md" o "spec avisos.md" sin
> número. Si la spec y un criterio no coinciden, se corrige el criterio antes de construir.

**35 historias** (HU-026…HU-060) en 12 épicas. Resumen de estado DoR: **35 ✅ listas, sin criterios ⛔**: las
dudas que trababan criterios de 22 de ellas se resolvieron el 19-nov-2026 (va la propuesta): DU-Q01…DU-Q03
(PO + QA) y DU-P01…DU-P24 (Analista, abiertas en paralelo al escribir `especificacion/pedidos.md` y
`avisos.md`), todas al final de [`dudas.md`](dudas.md). Cada historia se construye con la propuesta
de su duda; si la decisión es otra, cambia **solo** el criterio marcado, no la historia.

| Épica | Historias | ✅ | Criterio que dependía de una duda (resuelta el 19-nov) |
|---|---|---|---|
| H · Crear y seguir pedidos | HU-026…HU-031 | 6 | HU-026 (P01, P08), HU-027 (P07), HU-029 (P01, P23), HU-030 (P11), HU-031 (P05, P06, P08) |
| I · Estados y cierre | HU-032…HU-037 | 6 | HU-032 (P01), HU-033 (P03), HU-034 (P02), HU-035 (P04), HU-036 (P09) |
| J · Mis tareas e inicio | HU-038…HU-039 | 2 | HU-038 (P14) |
| K · Jefaturas: mi gente y carga por persona | HU-040…HU-041 | 2 | HU-040 (P09, P14), HU-041 (P14) |
| L · Todos los pedidos y sin obra | HU-042…HU-043 | 2 | HU-043 (P13) |
| M · Calendario | HU-044 | 1 | — |
| N · Avisos y mail | HU-045…HU-049 | 5 | HU-046 (P23, P24), HU-047 (P19), HU-048 (Q01, P19, P21) |
| O · Equipos en reparación | HU-050 | 1 | HU-050 (Q03, P18) |
| P · Celular | HU-051…HU-053 | 3 | — |
| Q · Integración con Obras | HU-054…HU-056 | 3 | HU-055 (P17), HU-056 (P12) |
| R · Buscador: pedidos por texto | HU-057 | 1 | HU-057 (P15) |
| S · Arrastre de semana 1 | HU-058…HU-060 | 3 | HU-058 (Q02, P09) |

> **Orden de construcción sugerido:** S (HU-059 deuda técnica que toca Pedidos, HU-060 encendido)
> → H (HU-026, HU-027, HU-028) → N (HU-046 destinatarios, HU-045, HU-047, HU-048) → I → J → Q →
> L → P → K → M → R → O → H (HU-030, HU-031) → N (HU-049) → S (HU-058). Sin crear pedidos no hay
> nada que avisar, y sin los avisos de asignación y "vence mañana" no se puede probar el criterio
> de aceptación global n.º 3.

### 6.1 La prueba del cliente de la semana 2

**Qué prueban (roadmap firmado):** "Lo usan en paralelo con la minuta durante unos días." Cada
pedido nuevo que cargan en la minuta lo cargan también en el sistema, lo resuelven en los dos y
comparan. Arrancan con las obras y los usuarios reales que cargaron en la semana 1.

**Tiene que estar sí o sí** (sin esto la prueba en paralelo no se puede hacer o deja de ser
justa contra la minuta):

| Lo que hacen en la minuta hoy | Historias que lo sostienen |
|---|---|
| Cargar un pedido con todas sus columnas (tarea, obra o "Servicios generales", propietario, PROVEEDOR, fechas, prioridad, links, notas) | HU-026, HU-027, HU-028, HU-043 |
| Mirar la planilla de la obra y la planilla completa, filtrar | HU-042, HU-054 |
| Cambiar el desplegable de estado: En curso, Bloqueada con el motivo en Notas, Completada | HU-032, HU-033, HU-034 |
| La ida y vuelta en Notas ("falta tal cosa") | HU-029 |
| Cambiar el propietario a mano | HU-035 |
| Corregir una fecha o un link | HU-031 |
| Enterarse sin que lo arroben: asignación (también por mail), tomado, completado, "vence mañana" | HU-045, HU-046, HU-047, HU-048 |
| Ver lo mío en una sola lista (hoy no existe: es lo que van a comparar) | HU-038, HU-039 |
| Desde el celular (hoy la minuta no les aparece) | HU-051, HU-052 |
| Que el entorno mande los mails, corra el recordatorio y muestre Pedidos en la barra | HU-060 |

Se entregan en la misma semana pero **la prueba no depende de ellas** (si alguna se atrasa, se
entrega en la semana 3 sin frenar la prueba; se le dice a Juani el miércoles): HU-030 (seguir),
HU-036 (reabrir), HU-037 (recorrido completo; el registro se guarda igual desde el primer día),
HU-040 y HU-041 (mi gente y carga por persona), HU-044 (calendario), HU-049 (todos los avisos),
HU-050 (equipos en reparación), HU-053 (obras en el celular), HU-055 y HU-056 (columna, fila y
cierre de obra con pedidos), HU-057 (buscador), HU-058 y HU-059.

**Señales de que la prueba peligra** (se miran el miércoles de la semana 2):
- Crear un pedido y que al propietario le llegue el aviso en la campana **y** el mail no está en
  verde de punta a punta en la suite de integración real (front contra backend).
- El recordatorio "vence mañana" no corre solo en el entorno de prueba (scheduler en Supervisord).
- El celular no permite comentar y completar un pedido con la foto del remito.

**Qué mira QC durante los días en paralelo** (detalle en `qa/plan-de-pruebas.md` §9): que ningún
pedido cargado en la minuta esos días falte en el sistema; que cada propietario haya tenido el
aviso antes de la entrega (criterio global n.º 3, objetivo 100 %); que los mails que **no** tienen
que salir no salgan.

### Épica H · Crear y seguir pedidos

#### HU-026 · Cargar un pedido
- **F-24 · F-25 · F-38 · W-08**
- **Como** cualquier persona de la empresa **quiero** pedirle algo a otra persona y su sector, de
  una obra o sin obra, con todo lo que necesita para resolverlo **para** que no se pierda como hoy
  en la minuta y no tener que arrobarla.
- **Criterios de aceptación**
  1. **Dado** `pedidos:editar`, **cuando** carga tarea, obra (o "Sin obra / Servicios generales"),
     sector, propietario y fecha de entrega y toca "Crear pedido", **entonces** el pedido queda en
     **No iniciada**, con **solicitante = quien lo cargó** (no se elige ni viaja en el pedido
     HTTP: si lo manda, se ignora) y la confirmación "Al crear, {propietario} recibe el aviso".
  2. **Dado** que falta la tarea, la obra (o la marca "sin obra"), el sector, el propietario o la
     fecha de entrega, **cuando** guarda, **entonces** ve el error en cada campo y no se crea
     (422; fecha de entrega obligatoria: resuelta DU-14).
  3. **Dado** el campo "Fecha de pedido", **cuando** abre el formulario, **entonces** viene con
     el día de hoy y la puede corregir hacia atrás (un pedido que llegó antes por mail). Una fecha
     de pedido futura se rechaza; una entrega anterior a la fecha de pedido queda **Urgente**
     (identidad.md RN-IDE-41). Tope de la fecha de pedido: **DU-P08 ✅**.
  4. **Dado** "Con quién / para quién", **cuando** escribe cualquier texto (un proveedor, el
     cliente, un área, la propia empresa), **entonces** se guarda tal cual: es texto libre, como
     la columna PROVEEDOR de la minuta, y **no** se elige de la base de proveedores.
  5. **Dado** un período inicio–fin (alojamiento, alquiler), **cuando** lo carga, **entonces** se
     guarda; es opcional; un fin anterior al inicio da error.
  6. **Dado** el propietario, **entonces** es **una sola persona** activa y el sector se completa
     con el suyo (se puede cambiar el sector antes de elegir a la persona: el desplegable de
     personas se filtra por sector). No se puede cargar un segundo propietario: otra persona va
     como seguidora (HU-030). Una persona de un sector con Pedidos en Sin acceso igual ve y
     resuelve lo suyo: **DU-P01 ✅**.
  7. **Dado** que cualquiera le puede pedir a cualquiera, **cuando** una persona de Calidad le pide
     algo a Compras, o Administración le pide a Ingeniería (la planilla de cierre), **entonces**
     se crea igual (los dos sentidos, cap. 4.2).
  8. **Dado** una obra **Terminada**, **cuando** la elige, **entonces** se permite y el formulario
     muestra "Obra terminada" al lado del nombre (resuelta DU-A15). Una obra sin presupuesto por
     rubro cargado se puede elegir igual (HU-019 c2). Cómo es el selector de obras: DU-P20.
  9. **Dado** un doble clic en "Crear pedido", **cuando** llegan los dos envíos con la misma
     `Idempotency-Key`, **entonces** se crea un solo pedido y sale un solo aviso (ADR-011).
  10. **Dado** el alta, **entonces** queda en el registro del pedido "creó el pedido" con quién y
      cuándo (F-14) y se dispara el aviso de asignación (HU-046).
  11. **Dado** un usuario sin `pedidos:editar`, **cuando** llama al alta por API, **entonces** 403
      y no se crea nada.
- **Reglas:** spec pedidos.md (alta, obligatorios, fechas).
- **Permisos:** `pedidos:editar` (inicial: todos los sectores, E·T).
- **Dependencias:** HU-059 (contratos), HU-005 (usuarios), HU-012 (obras), HU-027 (prioridad).
- **DoR:** ✅ listo (dudas resueltas el 19-nov-2026: va la propuesta).

#### HU-027 · Prioridad automática, corregible a mano
- **F-26 · F-68 (parte semana 2) · W-08 · W-32 · W-50**
- **Como** quien carga un pedido **quiero** que la prioridad se calcule sola según la anticipación
  y poder corregirla **para** no discutir cada vez si es urgente y que los plazos los fije gerencia.
- **Criterios de aceptación**
  1. **Dado** los plazos de arranque (urgente hasta 1 día, prioritario hasta 7), **cuando** la
     entrega está a 0 o 1 día corridos de la fecha de pedido, **entonces** el formulario muestra
     **Urgente**; a 2…7 días, **Prioritario**; a 8 o más, **Normal**, con el texto "N días entre el
     pedido y la entrega" (días corridos, sin feriados: resuelta DU-04; RN-IDE-41).
  2. **Dado** que cambia la fecha de pedido o la de entrega en el formulario, **entonces** la
     prioridad sugerida se actualiza en el momento.
  3. **Dado** que quien carga elige otra prioridad a mano, **cuando** guarda, **entonces** se guarda
     la elegida y queda marcada como corregida a mano (si elige la misma que la calculada, no).
  4. **Dado** que gerencia cambia los plazos en W-50, **cuando** se cargan pedidos nuevos,
     **entonces** usan los plazos nuevos y **los ya cargados no cambian** (RN-IDE-42).
  5. **Dado** un pedido cargado, **cuando** se edita la fecha de entrega (HU-031), **entonces** la
     prioridad se recalcula con los plazos **vigentes** en ese momento; si había sido corregida a
     mano, se mantiene la manual y el formulario muestra la calculada como sugerencia (resuelta
     DU-A13; RN-IDE-43). Quién la corrige al editar y cómo vuelve a la calculada: **DU-P07 ✅**
     (propuesta: solo quien lo pidió, con "Usar la calculada").
  6. **Dado** el cálculo, **entonces** el backend lo hace con `PlazosPrioridad::calcular` (no confía
     en la prioridad calculada que mande el front) y guarda la prioridad en el pedido.
- **Reglas:** identidad.md RN-IDE-41…44 · spec pedidos.md.
- **Permisos:** los de crear y editar el pedido.
- **Dependencias:** HU-011 (W-50), HU-026.
- **DoR:** ✅ listo (dudas resueltas el 19-nov-2026: va la propuesta).

#### HU-028 · Archivos o links del pedido
- **F-27 · ADR-014 (contexto `pedido`) · W-08 · W-43**
- **Como** quien pide **quiero** dejar en el pedido uno o varios links (Drive, Maps, la página de un
  producto) o subir el archivo o la foto **para** que quien lo resuelve tenga todo a mano.
- **Criterios de aceptación**
  1. **Dado** el formulario del pedido, **cuando** pega varios links `http(s)` y sube uno o más
     archivos (PDF, imagen —también HEIC del celular—, Excel, Word, CSV, TXT, hasta 20 MB),
     **entonces** quedan todos en el pedido, cada uno con su nombre, y se ven en el detalle.
  2. **Dado** un link que no empieza con `http://` o `https://`, o un archivo de tipo no permitido
     (validado por contenido) o de más de 20 MB, **entonces** se rechaza con el motivo (HU-025).
  3. **Dado** un adjunto del pedido, **cuando** lo baja alguien que puede ver el pedido,
     **entonces** lo recibe; **cuando** lo pide alguien que no lo puede ver (sector sin acceso a
     Pedidos o fuera de su alcance "propios"), aunque conozca el id, **entonces** 404.
  4. **Dado** un adjunto temporal subido por otra persona o de otro contexto, **cuando** se lo
     quiere asociar al pedido, **entonces** 422 "Ese adjunto no se puede usar acá." (RN-IDE-46).
  5. **Dado** que se agrega o se quita un link o archivo (al crear o al editar), **entonces** queda
     en el registro del pedido con el nombre (sin la URL ni la ruta del archivo).
- **Permisos:** asociar y quitar: los de editar el pedido; bajar: ver el pedido (identidad.md §6.6).
- **Dependencias:** HU-025, HU-026.
- **DoR:** ✅ listo.

#### HU-029 · Detalle del pedido y comentarios
- **F-30 · W-09 · W-42**
- **Como** quien pidió o quien resuelve **quiero** ver el pedido completo y escribir la ida y vuelta
  ("falta tal cosa acá") dentro del pedido **para** no perderla en la columna Notas sin saber
  quién la escribió.
- **Criterios de aceptación**
  1. **Dado** un pedido que puede ver, **cuando** lo abre, **entonces** ve tarea, prioridad, estado,
     obra (u "Sin obra"), solicitante y su sector, propietario y su sector (o "En el sector
     {sector}"), con quién, fecha de pedido y de entrega con los días entre ambas, período,
     aclaraciones, archivos y links, motivo de bloqueo si está Bloqueada y comentario y
     comprobante de cierre si está Completada.
  2. **Dado** un comentario, **cuando** lo envía, **entonces** aparece al final con **autor, fecha y
     hora**, y queda en el recorrido "comentó".
  3. **Dado** un comentario vacío, **entonces** no se envía (422).
  4. **Dado** un comentario guardado, **entonces** no se edita ni se borra (es el registro de la ida
     y vuelta).
  5. **Dado** un doble envío del mismo comentario con la misma `Idempotency-Key`, **entonces** queda
     uno solo y sale un solo aviso.
  6. **Dado** que comenta, **entonces** avisa según HU-046 (a la otra parte y a los seguidores,
     nunca al autor; resuelta DU-22).
  7. **Dado** los botones del detalle, **entonces** cada persona ve solo los que puede usar: Tomar,
     Bloquear, Retomar y Completar el propietario; Reasignar el propietario y el solicitante;
     Reabrir el solicitante; Editar quien tiene `pedidos:editar`; Seguir / Dejar de seguir todos.
     El backend vuelve a verificar cada acción (403).
  8. **Dado** el id de un pedido que no existe o que la persona no puede ver, **entonces** ve "Pedido
     no encontrado" (sobre JSON, sin confirmar que existe).
- **Reglas:** spec pedidos.md (detalle, comentarios).
- **Permisos:** ver: `pedidos:ver` con su alcance; comentar: quien lo pidió, el propietario y
  cualquiera que lo vea con `pedidos:editar` (spec pedidos.md). Lo propio con Sin acceso: **DU-P01 ✅**;
  comentario de un tercero o en un pedido en el sector: **DU-P23 ✅**.
- **Dependencias:** HU-026.
- **DoR:** ✅ listo (dudas resueltas el 19-nov-2026: va la propuesta).

#### HU-030 · Seguir un pedido y Tareas que sigo
- **F-07 · F-38 (seguidores) · W-08 · W-09 · W-28**
- **Como** cualquier persona **quiero** seguir un pedido de otro, o sumar a alguien como seguidor al
  cargarlo, **para** enterarme de cómo va sin que se mezcle con lo que tengo que hacer yo.
- **Criterios de aceptación**
  1. **Dado** cualquier pedido que puede ver, **cuando** toca "Seguir", **entonces** el pedido
     aparece en su pestaña **Tareas que sigo** (W-28), con prioridad, tarea, obra, propietario,
     entrega, estado y "dejar de seguir".
  2. **Dado** el alta del pedido, **cuando** quien carga agrega seguidores, **entonces** quedan
     como seguidores (F-38: "si hace falta otra persona, se la suma como seguidora"). Después de
     creado, cada uno se suma o se saca a sí mismo: **DU-P11 ✅**. Sumarse no avisa a nadie.
  3. **Dado** un seguidor, **cuando** toman, bloquean, comentan o completan el pedido, **entonces**
     recibe **dentro del sistema** el mismo aviso que el solicitante, y **nunca por mail** por ser
     seguidor (HU-046).
  4. **Dado** "dejar de seguir", **entonces** sale de la lista y deja de recibir esos avisos.
  5. **Dado** que ya lo sigue, **cuando** toca "Seguir" otra vez o lo agregan de nuevo, **entonces**
     no se duplica (uno por persona y pedido).
  6. **Dado** el propietario o el solicitante, **entonces** no se ofrecen como seguidores de su
     propio pedido (ya reciben sus avisos).
- **Reglas:** spec pedidos.md (seguidores) · spec avisos.md (destinatarios).
- **Permisos:** `pedidos:editar` sobre un pedido que puede ver (spec pedidos.md; F-07 "cualquiera").
- **Dependencias:** HU-026, HU-046.
- **DoR:** ✅ listo (dudas resueltas el 19-nov-2026: va la propuesta).

#### HU-031 · Editar un pedido con registro del cambio
- **F-33 · F-14 (pedidos) · W-32**
- **Como** quien tiene permiso **quiero** corregir la tarea, las fechas, los links o las
  aclaraciones de un pedido **para** tenerlo al día sabiendo qué se cambió y quién.
- **Criterios de aceptación**
  1. **Dado** `pedidos:editar`, **cuando** abre Editar, **entonces** ve tarea, fecha de pedido,
     fecha de entrega, período, archivos y links, prioridad, con quién y aclaraciones con los datos
     cargados, como W-32.
  2. **Dado** que cambia uno o más datos y guarda, **entonces** abajo, en "Historial de cambios",
     aparece una línea por dato con quién, cuándo, valor anterior y valor nuevo ("Fecha de
     entrega: 21-10 → 23-10 (la prioridad sigue Normal: 9 → 11 días)").
  3. **Dado** obra y sector, **entonces** se cambian según **DU-P05 ✅** (propuesta: la obra se
     puede corregir en Editar, con registro; el sector se ve sin desplegable y se cambia con
     Reasignar, HU-035). El propietario y el estado nunca se cambian desde Editar (tienen sus botones).
  4. **Dado** que guarda sin cambiar nada, **entonces** no se agrega ninguna línea.
  5. **Dado** que cambia la entrega, **entonces** la prioridad se recalcula según HU-027 c5 y, si el
     pedido sigue abierto, el recordatorio "vence mañana" se calcula con la fecha nueva (HU-048).
  6. **Dado** las validaciones del alta (HU-026, incluida **DU-P08 ✅**), **entonces** aplican igual.
  7. **Dado** `pedidos:ver` (o sin acceso), **cuando** edita por API, **entonces** 403.
  8. **Dado** un pedido **Completada**, **cuando** se edita, **entonces** se permite igual (corregir
     un link o una aclaración) y queda en el historial.
  9. **Dado** un pedido cargado por error (repetido, o a la persona equivocada), **entonces** se
     sigue **DU-P06 ✅** (propuesta: no se borra; se completa con el comentario "Cargado por error",
     como el criterio de no borrar de W-34 y W-39).
  10. **Dado** que se edita la entrega o la tarea, **entonces** no se avisa a nadie (la edición no
      está en la tabla de avisos del cap. 7; **DU-P24 ✅**).
- **Reglas:** spec pedidos.md (edición) · ADR-010.
- **Permisos:** `pedidos:editar` con su alcance (F-33 "según la tabla de permisos").
- **Dependencias:** HU-026, HU-027, HU-028.
- **DoR:** ✅ listo (dudas resueltas el 19-nov-2026: va la propuesta).

### Épica I · Estados y cierre

#### HU-032 · Tomar un pedido
- **F-28 · W-09 · W-42**
- **Como** propietario **quiero** tomar el pedido **para** que quien lo pidió sepa que ya está en
  curso sin tener que preguntar.
- **Criterios de aceptación**
  1. **Dado** un pedido **No iniciada** del que es propietario, **cuando** toca "Tomar → En curso",
     **entonces** pasa a **En curso**, queda en el recorrido "lo tomó" y avisa al solicitante y a
     los seguidores (HU-046).
  2. **Dado** un pedido **en el sector** (sin persona), **cuando** lo toma cualquier persona activa
     de ese sector, **entonces** pasa a ser su propietario y queda En curso (resuelta DU-16).
  3. **Dado** alguien que no es el propietario (ni del sector, si está en el sector), **cuando**
     intenta tomarlo por API, **entonces** 403.
  4. **Dado** un pedido que ya está En curso, Bloqueada o Completada, **cuando** intenta tomarlo,
     **entonces** 409 con el estado actual y sin un segundo aviso (ADR-011).
  5. **Dado** que dos personas del sector lo toman a la vez, **entonces** lo toma una sola y la otra
     ve "Ya lo tomó {persona}." (409).
- **Reglas:** spec pedidos.md (estados, `PedidoStateMachine`).
- **Permisos:** ser el propietario (o del sector, si está en el sector); regla fija, no matriz. Con
  Pedidos en Sin acceso: **DU-P01 ✅**.
- **Dependencias:** HU-026, HU-046.
- **DoR:** ✅ listo (dudas resueltas el 19-nov-2026: va la propuesta).

#### HU-033 · Bloquear con motivo y retomar
- **F-29 · W-30 · W-42**
- **Como** propietario **quiero** marcar un pedido como Bloqueada con el motivo, como hoy en la
  minuta, **para** que quien lo pidió sepa por qué está trabado y lo destrabe.
- **Criterios de aceptación**
  1. **Dado** un pedido En curso (o No iniciada: **DU-P03 ✅**) del que es propietario, **cuando** toca "Bloquear…" y
     escribe el motivo, **entonces** pasa a **Bloqueada**, se ve en rojo en las listas, el motivo
     queda en el detalle y en el recorrido, y avisa al solicitante y a los seguidores **con el
     motivo** (HU-046).
  2. **Dado** el motivo vacío, **entonces** no se bloquea (422 "Escribí el motivo.").
  3. **Dado** un pedido Bloqueada, **cuando** el propietario toca "Retomar", **entonces** vuelve a
     **En curso** y queda en el recorrido. Retomar no genera aviso (no está en la tabla de avisos
     del cap. 7).
  4. **Dado** alguien que no es el propietario, **cuando** intenta bloquear o retomar, **entonces**
     403.
  5. **Dado** un pedido Completada, **cuando** intenta bloquearlo, **entonces** 409.
  6. **Dado** un pedido Bloqueada, **entonces** sigue contando como abierto (Mis tareas, vencidos,
     recordatorio, pedidos abiertos de la obra).
- **Reglas:** spec pedidos.md.
- **Permisos:** propietario.
- **Dependencias:** HU-032.
- **DoR:** ✅ listo (dudas resueltas el 19-nov-2026: va la propuesta).

#### HU-034 · Completar con comentario y comprobante
- **F-31 · ADR-014 (contexto `comprobante_cierre`) · W-29 · W-42**
- **Como** propietario **quiero** cerrar el pedido dejando cómo se resolvió y, si corresponde, el
  remito o el recibo (el link o la foto) **para** que quien lo pidió y su superior se enteren y
  quede constancia.
- **Criterios de aceptación**
  1. **Dado** un pedido **No iniciada o En curso** del que es propietario, **cuando** escribe el
     comentario y toca "Completar y avisar", **entonces** pasa a **Completada** con quién y cuándo,
     el comentario de cierre queda en el detalle y en el recorrido (resuelta DU-15: se puede
     completar sin tomarlo antes; el recorrido lo dice).
  2. **Dado** el comentario vacío, **entonces** no se completa (422).
  3. **Dado** el comprobante, **cuando** sube la foto del remito desde el celular, un PDF, o pega
     uno o más links, **entonces** quedan en el pedido como comprobante de cierre; es opcional.
  4. **Dado** que se completa, **entonces** avisan dentro del sistema el **solicitante** (con el
     comentario), el **superior del solicitante** (`jefe_id` de quien pidió, no de quien completó;
     resuelta DU-13) y los seguidores; **ningún mail** salvo que la persona lo haya activado en Mis
     avisos (HU-046, HU-047).
  5. **Dado** un pedido **Bloqueada**, **cuando** intenta completarlo, **entonces** 409 "Primero
     retomalo." (resuelta DU-15).
  6. **Dado** un pedido **en el sector** (sin persona), **entonces** se sigue **DU-P02 ✅** (propuesta:
     cualquier persona activa del sector lo puede completar y queda como propietaria; DU-16 resuelta
     solo dice "tomar").
  7. **Dado** un pedido ya Completada, **cuando** se vuelve a enviar "completar" (doble clic),
     **entonces** 409 con el estado actual y sin un segundo aviso (ADR-011).
  8. **Dado** alguien que no es el propietario, **entonces** 403.
- **Reglas:** spec pedidos.md · spec avisos.md · ADR-014.
- **Permisos:** propietario.
- **Dependencias:** HU-028, HU-032, HU-046.
- **DoR:** ✅ listo (dudas resueltas el 19-nov-2026: va la propuesta).

#### HU-035 · Reasignar a otra persona o dejarlo en el sector
- **F-32 · W-31**
- **Como** propietario o quien lo pidió **quiero** pasarle el pedido a otra persona o dejarlo en el
  sector **para** que se resuelva aunque yo no esté, sin perder lo que ya se habló.
- **Criterios de aceptación**
  1. **Dado** un pedido abierto, **cuando** el propietario o el solicitante elige "Una persona"
     (activa, de cualquier sector) y toca "Reasignar y avisar", **entonces** esa persona pasa a ser
     el propietario, el sector pasa a ser el suyo y le llega el aviso **en el sistema y por mail**
     (HU-046, HU-047).
  2. **Dado** "El sector", **cuando** elige un sector y confirma, **entonces** el pedido queda **sin
     persona** en ese sector, aparece en Mis tareas de todas sus personas activas y a todas les
     llega el aviso de asignación en el sistema y por mail (resuelta DU-16).
  3. **Dado** el comentario opcional, **entonces** queda como comentario del pedido.
  4. **Dado** la reasignación, **entonces** los comentarios, archivos e historial anteriores siguen
     en el pedido y el recorrido dice "reasignó de {anterior} a {nuevo}".
  5. **Dado** un tercero (ni propietario ni solicitante), **cuando** intenta reasignar por API,
     **entonces** 403.
  6. **Dado** un pedido Completada, **entonces** no se reasigna (409): primero se reabre (HU-036).
  7. **Dado** el estado al reasignar, **entonces** sigue **DU-P04 ✅** (propuesta: el estado no cambia,
     como hoy que solo cambian el nombre). Dejarlo en un sector sin usuarios activos no se puede
     (propuesta DU-P02).
- **Reglas:** spec pedidos.md · spec avisos.md.
- **Permisos:** propietario o solicitante del pedido (F-32).
- **Dependencias:** HU-026, HU-046, HU-047.
- **DoR:** ✅ listo (dudas resueltas el 19-nov-2026: va la propuesta).

#### HU-036 · Reabrir un pedido completado
- **F-65 · W-09**
- **Como** quien lo pidió **quiero** reabrir un pedido completado cuando falta algo **para** que
  vuelva al propietario con el comentario de qué falta, como cuando hoy "nos devuelven alguno".
- **Criterios de aceptación**
  1. **Dado** un pedido Completada que pidió, **cuando** toca "Reabrir (falta algo)" y escribe el
     comentario, **entonces** vuelve a **En curso** con el mismo propietario, el comentario queda en
     la ida y vuelta y le llega el aviso al **propietario** (HU-046).
  2. **Dado** el comentario vacío, **entonces** no se reabre (422).
  3. **Dado** alguien que no es el solicitante, **cuando** intenta reabrir, **entonces** 403.
  4. **Dado** un pedido que no está Completada, **entonces** 409.
  5. **Dado** un pedido reabierto, **entonces** vuelve a contar como abierto (Mis tareas, vencidos,
     recordatorio, pedidos abiertos de la obra) y conserva el comentario y el comprobante del
     cierre anterior en el recorrido.
  6. **Dado** un propietario que ya está inactivo, **entonces** se sigue **DU-P09 ✅**.
- **Reglas:** spec pedidos.md.
- **Permisos:** solicitante del pedido (F-65 "quien lo pidió").
- **Dependencias:** HU-034.
- **DoR:** ✅ listo (dudas resueltas el 19-nov-2026: va la propuesta).

#### HU-037 · Recorrido del pedido
- **F-34 · F-14 (pedidos) · W-09**
- **Como** cualquier persona que ve el pedido **quiero** ver quién lo creó, tomó, bloqueó, comentó,
  reasignó, completó y reabrió, y cuándo, **para** saber qué pasó sin preguntar.
- **Criterios de aceptación**
  1. **Dado** un pedido con varias acciones, **cuando** abre "Historial del recorrido", **entonces**
     ve una línea por acción con fecha y hora, quién (o "sistema") y qué hizo, en orden.
  2. **Dado** los avisos que salieron, **entonces** el recorrido muestra "avisó a {persona}
     (asignado)" y "recordatorio: vence mañana" como en W-09.
  3. **Dado** cada transición, **entonces** se registra dentro de la misma transacción que el cambio
     de estado (si el cambio falla, no queda la línea; ADR-010).
  4. **Dado** el registro, **entonces** es solo inserción: no hay forma de borrarlo ni editarlo.
  5. **Dado** una persona que después quedó inactiva, **entonces** sus líneas siguen con su nombre.
- **Reglas:** spec pedidos.md · ADR-010.
- **Permisos:** quien ve el pedido.
- **Dependencias:** HU-032…HU-036.
- **DoR:** ✅ listo.

### Épica J · Mis tareas e inicio

#### HU-038 · Mis tareas: la pantalla de inicio
- **F-04 · F-61 · W-01**
- **Como** cualquier persona **quiero** que al entrar me aparezcan todas mis tareas abiertas, de
  todas las obras, por fecha de entrega **para** no tener que entrar obra por obra.
- **Criterios de aceptación**
  1. **Dado** un usuario adentro, **cuando** toca Inicio o entra al sistema, **entonces** llega a
     **Mis tareas** (reemplaza lo que en la semana 1 abría Obras, HU-024 c1) con las pestañas Mis
     tareas · Pedidos que pedí · Tareas que sigo (y Tareas de mi gente si tiene gente a cargo,
     HU-040), cada una con su cantidad.
  2. **Dado** Mis tareas, **entonces** lista los pedidos **abiertos** (No iniciada, En curso,
     Bloqueada) de los que es propietario, **más** los que están en su sector sin persona (resuelta
     DU-16), de todas las obras y sin obra, ordenados por fecha de entrega (los vencidos quedan
     arriba).
  3. **Dado** cada fila, **entonces** muestra prioridad con su color, tarea (y el período si tiene),
     obra (o "Servicios generales"), solicitante, entrega ("mañana", o "vencido" en rojo) y estado,
     y arriba "N abiertas · M vencidas".
  4. **Dado** un pedido con entrega anterior a hoy y abierto, **entonces** figura **vencido**; el que
     vence hoy no (dice "hoy"); el día se corta en hora de Argentina (**DU-P14 ✅**).
  5. **Dado** los filtros Obra y Estado ("abiertas" por defecto) y "Buscar tarea…", **entonces**
     filtran sin salir de la pestaña.
  6. **Dado** un pedido que completa, reasigna o que le reasignan, **entonces** la lista se actualiza
     sola (inmediato si lo hizo él, ≤ 15 s si lo hizo otro, con la pestaña visible; ADR-005).
  7. **Dado** que nunca le asignaron nada, **entonces** ve "No tenés tareas abiertas." y el botón
     "+ Nuevo pedido".
  8. **Dado** la semana 2 encendida (HU-060), **entonces** la barra muestra Pedidos y Calendario y
     el celular la barra Tareas · Obras · Avisos · Más (B-03 se apaga, DU-A12).
- **Reglas:** spec pedidos.md (Mis tareas, vencidos).
- **Permisos:** cualquier usuario activo (no es fila de la matriz; ve solo lo suyo).
- **Dependencias:** HU-026, HU-060.
- **DoR:** ✅ listo (dudas resueltas el 19-nov-2026: va la propuesta).

#### HU-039 · Pedidos que pedí
- **F-05 · W-27**
- **Como** cualquier persona **quiero** ver cómo va cada pedido que cargué **para** no tener que
  preguntar.
- **Criterios de aceptación**
  1. **Dado** la pestaña Pedidos que pedí, **entonces** lista los pedidos donde es **solicitante**,
     de todas las obras, con tarea, obra, propietario (o "En el sector {sector}"), entrega, estado
     y **último movimiento** ("15-oct · Carla: 'Pedido hecho…'", "8-oct · creado; nadie lo tomó
     todavía").
  2. **Dado** "Abiertos" (por defecto) y "Todos", **entonces** con Todos aparecen también los
     completados, después de los abiertos.
  3. **Dado** una fila, **cuando** la toca, **entonces** abre el pedido (W-09).
  4. **Dado** un pedido que pidió otra persona, **entonces** no aparece aunque lo siga (eso es
     Tareas que sigo).
- **Reglas:** spec pedidos.md.
- **Permisos:** cualquier usuario activo.
- **Dependencias:** HU-026, HU-037.
- **DoR:** ✅ listo.

### Épica K · Jefaturas: mi gente y carga por persona

#### HU-040 · Tareas de mi gente
- **F-06 · F-61 (pantallas) · W-26**
- **Como** jefatura o gerencia **quiero** ver las tareas de las personas a mi cargo, agrupadas por
  persona **para** saber en qué está mi gente y qué se está atrasando.
- **Criterios de aceptación**
  1. **Dado** una persona con gente a cargo (al menos un usuario activo que depende de ella),
     **cuando** abre Inicio, **entonces** ve la pestaña **Tareas de mi gente**; sin gente a cargo,
     la pestaña no aparece y por API recibe 403 (RN-IDE-13).
  2. **Dado** una jerarquía de tres niveles (gerencia → jefatura → persona), **cuando** la gerencia
     abre la pestaña, **entonces** ve a **toda su área**: la jefatura y la gente de la jefatura
     (subordinados directos e indirectos); la jefatura ve solo a los suyos; nadie ve a gente de
     otra rama.
  3. **Dado** cada persona, **entonces** es un grupo "{persona} · {sector} · N abiertas · M
     vencidas" con sus tareas abiertas por entrega, vencidas arriba, y "ver las N restantes"; el
     propio jefe aparece como "(yo)", como en W-26.
  4. **Dado** un subordinado inactivo con pedidos abiertos, **entonces** se sigue **DU-P09 ✅**
     (propuesta: aparece como "{persona} (inactiva)" con sus abiertos); personas sin tareas abiertas
     y orden de los grupos: **DU-P14 ✅**.
  5. **Dado** Dirección, **entonces** ve a todas las personas que dependen de ella directa o
     indirectamente (F-61: "Dirección ve todo").
  6. **Dado** un cambio de "depende de" en W-39, **cuando** el jefe vuelve a abrir la pestaña,
     **entonces** ya refleja la jerarquía nueva.
- **Reglas:** identidad.md RN-IDE-13, RN-IDE-22/23 (`Jerarquia::subordinados`) · spec pedidos.md.
- **Permisos:** tener gente a cargo (jerarquía, no matriz).
- **Dependencias:** HU-038.
- **DoR:** ✅ listo (dudas resueltas el 19-nov-2026: va la propuesta).

#### HU-041 · Carga por persona
- **F-12 · W-04**
- **Como** gerencia **quiero** ver cuántos pedidos abiertos, vencidos y para esta semana tiene cada
  persona de mi gente **para** saber quién está tapado antes de pedirle algo más.
- **Criterios de aceptación**
  1. **Dado** una persona con gente a cargo, **cuando** abre Pedidos → Carga por persona,
     **entonces** ve una fila por persona de su gente (como HU-040 c2) con sector, **abiertas**,
     **vencidas**, **vencen esta semana** y una barra que compara la carga entre personas.
  2. **Dado** el título "Carga de mi gente · semana del {lunes} al {domingo}", **entonces** "esta
     semana" va de lunes a domingo, como W-04; qué cuenta como "vencen esta semana" y como
     "vencidas" sin contar dos veces el mismo pedido, si se incluye "yo" y el orden: **DU-P14 ✅**
     (propuesta: vencen esta semana = de hoy al domingo, sin las vencidas).
  3. **Dado** el filtro Sector, **entonces** filtra las filas.
  4. **Dado** una persona, **cuando** la toca, **entonces** ve sus tareas abiertas.
  5. **Dado** la barra, **entonces** es solo comparación (no hay puntaje ni orden de "mejor/peor").
  6. **Dado** alguien sin gente a cargo, **entonces** no ve el acceso y por API recibe 403.
  7. **Dado** una persona con 40 % de los pedidos (como compras hoy), **entonces** la pantalla carga
     sin una consulta por persona.
- **Reglas:** spec pedidos.md (conteos).
- **Permisos:** tener gente a cargo (RN-IDE-13).
- **Dependencias:** HU-040.
- **DoR:** ✅ listo (dudas resueltas el 19-nov-2026: va la propuesta).

### Épica L · Todos los pedidos y sin obra

#### HU-042 · Todos los pedidos (la minuta completa)
- **F-35 · W-24**
- **Como** cualquier persona **quiero** ver todos los pedidos de todas las obras en una tabla y
  filtrarla **para** usarla como hoy usan la planilla con filtros.
- **Criterios de aceptación**
  1. **Dado** `pedidos:ver` con alcance todos, **cuando** abre Pedidos, **entonces** ve la tabla con
     Obra · Tarea · Sector · Propietario · Solicitante · Pedido · Entrega · Prioridad · Estado,
     arranca en **abiertos** y ordenada por entrega, y arriba los accesos Todos los pedidos ·
     Servicios generales · Pedidos que pedí · Tareas de mi gente · Carga por persona · Equipos en
     reparación (los de mi gente solo a quien tiene gente a cargo).
  2. **Dado** los filtros por obra, sector, propietario, solicitante, estado, prioridad, "solo
     vencidos" y rango de fechas de entrega, **cuando** los combina, **entonces** ve solo lo que
     cumple todos, con "N de M pedidos con estos filtros".
  3. **Dado** un encabezado ordenable (Obra, Pedido, Entrega), **cuando** lo toca, **entonces** se
     ordena; tocando una fila se abre el pedido.
  4. **Dado** "Exportar a Excel" (W-24, aprobado), **cuando** lo toca, **entonces** baja un Excel
     con todo lo filtrado (sin pedidos que la persona no puede ver); columnas exactas: DU-P16
     (decisión técnica).
  5. **Dado** más de una página de pedidos, **entonces** se pagina sin perder los filtros (TL-03,
     HU-059).
  6. **Dado** un sector con Pedidos en **Sin acceso**, **cuando** pide la lista por API, **entonces**
     403 y la pantalla "No tenés acceso a esta sección".
  7. **Dado** un sector con Pedidos restringido a "propios" (los que pidió, tiene, sigue o de su
     gente; identidad.md §6.1), **entonces** la tabla, el Excel y los totales muestran solo esos.
- **Reglas:** spec pedidos.md (filtros componibles `PedidosQuery`).
- **Permisos:** `pedidos:ver` (inicial: todos, E·T; restringible en la tabla).
- **Dependencias:** HU-026, HU-059.
- **DoR:** ✅ listo.

#### HU-043 · Pedidos sin obra (Servicios generales)
- **F-36 · W-25**
- **Como** cualquier persona **quiero** cargar y ver en un lugar los pedidos que no son de ninguna
  obra (vehículos, pañol, oficina, cursos) **para** que no queden mezclados con los de las obras,
  como hoy en la solapa "Servicios generales".
- **Criterios de aceptación**
  1. **Dado** el alta (HU-026), **cuando** elige "Sin obra / Servicios generales", **entonces** el
     pedido se crea sin obra con los mismos datos que cualquier otro.
  2. **Dado** Pedidos → Servicios generales, **entonces** lista solo los pedidos sin obra, con
     prioridad, tarea, sector, propietario, solicitante, entrega y estado, y "+ Nuevo pedido sin
     obra" (abre W-08 con "Sin obra" ya elegido).
  3. **Dado** un pedido para varias obras de un mismo cliente, **cuando** se carga sin obra
     nombrando las obras en la tarea ("Curso de trabajo en altura · obras Piso nave 3 y Losa tanque
     B"), **entonces** aparece en Servicios generales (cap. 4.2: "van sin obra, nombrando las obras
     en la tarea"). Si además va en el bloque aparte "Pedidos que sirven a varias obras" que dibuja
     W-25, y cómo lo sabe el sistema: **DU-P13 ✅** (propuesta: una sola lista, sin bloque aparte).
  4. **Dado** un pedido sin obra, **entonces** no aparece en la pestaña Pedidos de ninguna obra ni
     cuenta en sus "pedidos abiertos"; cuenta en la fila Servicios generales de Obras (HU-055).
- **Reglas:** spec pedidos.md · obras.md RN-OBR-08.
- **Permisos:** `pedidos:ver` / `pedidos:editar`.
- **Dependencias:** HU-026, HU-042.
- **DoR:** ✅ listo (dudas resueltas el 19-nov-2026: va la propuesta).

### Épica M · Calendario

#### HU-044 · Calendario de tareas
- **F-11 · W-03**
- **Como** cualquier persona **quiero** ver en el mes qué vence cada día y qué está ocupado varios
  días seguidos (alojamientos, alquileres) **para** ver de un vistazo qué semana viene cargada.
- **Criterios de aceptación**
  1. **Dado** Calendario, **cuando** lo abre, **entonces** ve el mes actual (lunes a domingo) con
     cada pedido el día de su **fecha de entrega**, con el color de su prioridad; arranca en "Mis
     tareas".
  2. **Dado** un pedido con período inicio–fin, **entonces** se ve además como una **barra** de
     inicio a fin, con su tarea y obra.
  3. **Dado** un pedido Completada, **entonces** se ve tachado.
  4. **Dado** "Semana", **entonces** muestra la misma información de una semana; "‹ ›" cambia de mes
     o de semana.
  5. **Dado** el filtro Obra, **entonces** muestra solo los de esa obra; **dado** "Mi gente" (solo a
     quien tiene gente a cargo), **entonces** muestra los de su gente (HU-040 c2).
  6. **Dado** un día, **cuando** toca un pedido, **entonces** abre el pedido.
  7. **Dado** el alcance de Pedidos, **entonces** el calendario nunca muestra pedidos que la persona
     no puede ver.
- **Reglas:** spec pedidos.md.
- **Permisos:** `pedidos:ver` (el ítem Calendario aparece con `pedidos` ≥ Ver, identidad.md §6.4).
- **Dependencias:** HU-026, HU-040.
- **DoR:** ✅ listo.

### Épica N · Avisos y mail

#### HU-045 · Campana con contador y avisos en el momento
- **F-08 · W-02 · W-41**
- **Como** cualquier persona **quiero** enterarme en el momento de lo que pasa con mis pedidos, en
  una campana con contador **para** no tener que andar preguntando ni revisar el mail.
- **Criterios de aceptación**
  1. **Dado** un aviso nuevo para la persona, **cuando** tiene el sistema abierto, **entonces** el
     contador de la campana sube **sin recargar** en ≤ 15 s (pestaña visible; al volver a enfocarla,
     enseguida; ADR-005).
  2. **Dado** la campana, **cuando** la abre, **entonces** ve los últimos avisos con texto en sus
     palabras ("Diego te asignó Envío de cómputo…", "Carla tomó tu pedido… → pasó a En curso",
     "Vence mañana: …", "Carla completó tu pedido…: 'comentario'", "Diego comentó en tu pedido…:
     'texto'"), la obra, hace cuánto, y "Marcar todo como leído" y "Ver todos los avisos" (HU-049).
  3. **Dado** un aviso, **cuando** lo toca, **entonces** se marca leído, baja el contador y abre el
     pedido.
  4. **Dado** el contador, **entonces** cuenta solo los no leídos de la persona.
  5. **Dado** un aviso de otra persona, **cuando** lo pide o lo marca leído por id, **entonces** 404
     (IDOR).
  6. **Dado** el texto del aviso, **entonces** se arma con los datos guardados del aviso (no cambia
     si después se edita el pedido) y no expone datos de un pedido que la persona ya no puede ver.
- **Reglas:** spec avisos.md · ADR-005 · ADR-012.
- **Permisos:** cualquier usuario activo (sus propios avisos).
- **Dependencias:** HU-046.
- **DoR:** ✅ listo.

#### HU-046 · Quién recibe cada aviso
- **F-08 · F-07 · tabla "Avisos del pedido" del cap. 7 · W-02 · W-19**
- **Como** la empresa **queremos** que cada aviso le llegue a quien corresponde y a nadie más
  **para** que nadie pierda un pedido y que a nadie "le explote" el sistema con avisos de más.
- **Criterios de aceptación**
  1. **Asignado:** al crear un pedido, al **propietario** (en el sistema y por mail); si está en el
     sector sin persona, a **todas las personas activas del sector** (DU-16).
  2. **Reasignado:** al **nuevo propietario** (o a todo el sector), en el sistema y por mail.
  3. **Tomado:** al **solicitante** y a los **seguidores**.
  4. **Bloqueado:** al solicitante y a los seguidores, **con el motivo**.
  5. **Comentado:** a **la otra parte** (comenta el solicitante → al propietario; comenta el
     propietario → al solicitante) y a los seguidores (DU-22). Comenta un tercero → a los dos, y en
     un pedido en el sector "el propietario" es todo el sector: **DU-P23 ✅**.
  6. **Completado:** al solicitante (con el comentario), al **superior del solicitante** (su
     `jefe_id`; si no tiene, a nadie más) y a los seguidores (DU-13).
  7. **Reabierto:** al **propietario**.
  8. **Vence mañana:** al propietario (o a todo el sector), HU-048.
  9. **Nunca a uno mismo:** nadie recibe aviso de lo que hizo él (ADR-012): un jefe que completa el
     pedido de su subordinado no se avisa como superior; quien se pide algo a sí mismo no recibe el
     de asignación.
  10. **Uno por persona y evento:** si alguien es a la vez superior y seguidor (o solicitante y
      seguidor), recibe **un solo** aviso de ese evento.
  11. **Usuarios inactivos:** no reciben avisos ni mails.
  12. **Después de la transacción:** el aviso se crea solo si la acción se guardó (evento after
      commit); si la acción falla, no hay aviso.
  13. **Lo que no avisa:** retomar, seguir, sumar un seguidor y los equipos en reparación (no están
      en la tabla del cap. 7); editar el pedido tampoco (**DU-P24 ✅**).
- **Reglas:** spec avisos.md (destinatarios) · ADR-012.
- **Permisos:** — (lo decide el sistema).
- **Dependencias:** HU-026, HU-030 (seguidores), HU-005 (`jefe_id`).
- **DoR:** ✅ listo (dudas resueltas el 19-nov-2026: va la propuesta).

#### HU-047 · Mail de la asignación y "Mis avisos" en Mi perfil
- **F-09 · F-10 (parte semana 2) · W-19**
- **Como** cualquier persona **quiero** que la asignación me llegue también por mail, como hoy con
  Google, y elegir qué otros avisos me llegan por mail **para** enterarme aunque no tenga el sistema
  abierto sin que me explote el correo.
- **Criterios de aceptación**
  1. **Dado** una asignación o reasignación, **entonces** sale un mail al nuevo propietario (o a cada
     persona del sector) **por la cola `mail`**, desde noreply, con la tarea, la obra, la entrega,
     la prioridad, quién lo pidió y el link al pedido; nunca en el momento de guardar (si el mail
     falla, el pedido se crea igual).
  2. **Dado** los avisos de tomado, bloqueado, comentado, completado, reabierto y vence mañana,
     **entonces** **no** salen por mail salvo que la persona los haya activado.
  3. **Dado** Mi perfil, **cuando** abre "Mis avisos", **entonces** ve la tabla de W-19: cada aviso
     con "En el sistema: siempre" y la columna "Además por mail". Qué se puede tildar y qué no:
     **DU-P19 ✅** (propuesta: asignación y reasignación tildadas y fijas; se pueden activar tomado,
     completado, bloqueado, comentario nuevo y reabierto; "vence mañana" y el aviso al superior,
     nunca por mail).
  4. **Dado** que activa un aviso por mail y guarda, **cuando** ocurre ese aviso, **entonces** le
     llega también el mail; si lo desactiva, deja de llegar.
  5. **Dado** muchos mails juntos (por ejemplo, un pedido al sector de 5 personas), **entonces** la
     cola los manda de a **1 por segundo** y todos llegan (ADR-012).
  6. **Dado** el seguidor, **entonces** nunca recibe mail por ser seguidor (F-07: "dentro del
     sistema").
  7. **Dado** el mail, **entonces** no trae datos de otras obras ni de otros pedidos, y el link pide
     entrar si no hay sesión.
- **Reglas:** spec avisos.md · ADR-012 · identidad.md RN-IDE-18.
- **Permisos:** cualquier usuario activo (sus preferencias).
- **Dependencias:** HU-046, HU-003 (Mi perfil), HU-060 (worker de mail arriba).
- **DoR:** ✅ listo (dudas resueltas el 19-nov-2026: va la propuesta).

#### HU-048 · Recordatorio un día antes de la entrega
- **F-37 · W-02 · W-41**
- **Como** propietario **quiero** que el sistema me recuerde, un día antes de la fecha de entrega,
  los pedidos que sigo teniendo abiertos **para** que ninguno se me atrase sin enterarme.
- **Criterios de aceptación**
  1. **Dado** un pedido abierto (No iniciada, En curso o Bloqueada) con entrega **mañana**,
     **cuando** corre el recordatorio programado, **entonces** el propietario (o todo el sector, si
     está sin persona) recibe "Vence mañana: {tarea}" **dentro del sistema**.
  2. **Dado** un pedido Completada con entrega mañana, **entonces** no hay recordatorio.
  3. **Dado** que el recordatorio corre varias veces el mismo día (cada hora, o dos procesos a la
     vez), **entonces** el aviso sale **una sola vez** por pedido y fecha de entrega
     (`dedupe_key`, ADR-011/012).
  4. **Dado** que se cambia la fecha de entrega de un pedido que ya tuvo su recordatorio,
     **entonces** vuelve a tener recordatorio el día antes de la fecha nueva.
  5. **Dado** el recordatorio, **entonces** no sale por mail (F-37 "sin llenar el mail"; **DU-P19 ✅**).
  6. **Dado** la hora del aviso y una entrega del lunes, **entonces** se siguen **DU-P21 ✅** (propuesta:
     desde las 8:00 de Argentina, como W-02) y **DU-Q01 ✅** (propuesta: el domingo, días corridos
     como DU-04).
  7. **Dado** un pedido cargado con entrega hoy o ya vencida, **entonces** no hay recordatorio pero
     el aviso de asignación llega igual en el momento y el pedido figura vencido o "hoy" en Mis
     tareas.
  8. **Dado** el entorno, **entonces** el recordatorio está programado en el scheduler y corre sin
     que nadie lo dispare (HU-060).
- **Reglas:** spec avisos.md (recordatorio) · ADR-012.
- **Permisos:** — (sistema).
- **Dependencias:** HU-046, HU-060.
- **DoR:** ✅ listo (dudas resueltas el 19-nov-2026: va la propuesta).

#### HU-049 · Todos los avisos
- **F-64 · W-45**
- **Como** cualquier persona **quiero** repasar todos mis avisos, también los que ya leí, y filtrar
  los que me faltan **para** no perder nada que pasó mientras no miraba.
- **Criterios de aceptación**
  1. **Dado** "Ver todos los avisos", **entonces** ve la lista completa con Aviso · Obra · Cuándo, lo
     más nuevo primero, paginada, con los no leídos marcados.
  2. **Dado** los filtros Todos · No leídos (N) · Leídos y "Tipo", **entonces** filtran.
  3. **Dado** "Marcar todo como leído", **entonces** quedan todos leídos y el contador de la campana
     queda en 0.
  4. **Dado** un aviso, **cuando** lo toca, **entonces** se marca leído y abre el pedido.
  5. **Dado** los avisos de otra persona, **entonces** nunca aparecen ni se pueden marcar (404).
- **Reglas:** spec avisos.md.
- **Permisos:** cualquier usuario activo.
- **Dependencias:** HU-045.
- **DoR:** ✅ listo.

### Épica O · Equipos en reparación

#### HU-050 · Equipos en reparación
- **F-67 · ADR-014 (contextos `equipo_presupuesto`, `equipo_inventario`) · W-49**
- **Como** cualquier persona **quiero** seguir cada equipo que está en un taller: qué tiene, dónde
  está, si se aprobó el arreglo y cuándo vuelve **para** dejar de usar la solapa "Equipos en
  reparación" de la minuta.
- **Criterios de aceptación**
  1. **Dado** `equipos_reparacion:ver`, **cuando** abre Pedidos → Equipos en reparación, **entonces**
     ve la lista con Equipo · código, Falla o trabajo, Proveedor / taller, Estado, Aprobación,
     Fecha, Link al inventario y Notas.
  2. **Dado** `equipos_reparacion:editar`, **cuando** toca "+ Agregar equipo" y carga equipo, código,
     falla o trabajo, taller (texto, como hoy), estado, aprobación, fecha, notas, el presupuesto del
     taller y el inventario (cada uno **archivo o link**), **entonces** se guarda.
  3. **Dado** el estado de la reparación, **entonces** es uno de **No iniciada · Para retiro · En
     curso · Bloqueada · Completada**; la aprobación, uno de **Pendiente de aprobación · Aprobado ·
     Bloqueado**; otro valor da 422.
  4. **Dado** que cambia la aprobación, **entonces** **no** cambia el estado ni al revés: la
     aprobación la marca una persona y el sistema no decide nada.
  5. **Dado** una edición, **entonces** queda registrado quién y cuándo, con valor anterior.
  6. **Dado** un sector con Equipos en **Sin acceso**, **entonces** no ve el acceso y por API 403; un
     adjunto de un equipo pedido por alguien sin acceso, 404.
  7. **Dado** un sector con **solo** Equipos en reparación (sin Pedidos), **entonces** el ítem
     Pedidos lo lleva directo a Equipos en reparación (identidad.md §6.4).
  8. **Dado** qué es "Fecha" (**DU-Q03 ✅**: cuándo se mandó al taller) y qué es obligatorio, el orden
     de la lista y qué pasa con un equipo cargado por error (**DU-P18 ✅**: obligatorios equipo y
     falla o trabajo; no se borran; no completados primero).
  9. **Dado** un equipo, **entonces** no genera avisos (no están en la tabla de avisos del cap. 7).
- **Reglas:** spec pedidos.md (equipos) · ADR-014.
- **Permisos:** `equipos_reparacion:ver` / `:editar` (inicial: todos editan, como Pedidos; DU-01).
- **Dependencias:** HU-025, HU-060.
- **DoR:** ✅ listo (dudas resueltas el 19-nov-2026: va la propuesta).

### Épica P · Celular

#### HU-051 · Mis tareas y avisos en el celular
- **F-13 · F-04 · F-08 · W-41**
- **Como** cualquier persona en obra o de viaje **quiero** ver desde el celular lo que tengo que
  hacer y lo que pasó con mis pedidos **para** no depender de la computadora.
- **Criterios de aceptación**
  1. **Dado** un celular (390 px), **cuando** entra, **entonces** llega a **Mis tareas** en
     **tarjetas** (prioridad, tarea, obra, solicitante, entrega, estado), vencidos en rojo y
     arriba, "N vencidas · M abiertas" y "+ Nuevo pedido".
  2. **Dado** la barra de abajo **Tareas · Obras · Avisos · Más**, **entonces** Avisos muestra la
     lista de avisos con los no leídos marcados y el contador; "Más" lista Pedidos que pedí, Tareas
     que sigo, Tareas de mi gente (solo con gente a cargo), Calendario, lo que su sector puede ver,
     Mi perfil y Salir.
  3. **Dado** cualquier pantalla de esta historia, **entonces** no hay desplazamiento horizontal de
     la página y los botones entran en el ancho (como el arreglo de B-01).
  4. **Dado** el navegador del celular, **entonces** se usa sin instalar nada.
- **Reglas:** identidad.md RN-IDE-15 · spec pedidos.md.
- **Permisos:** los de Mis tareas y avisos.
- **Dependencias:** HU-038, HU-045, HU-060.
- **DoR:** ✅ listo.

#### HU-052 · Resolver y cargar un pedido desde el celular
- **F-13 · F-24 · F-30 · F-31 · W-42 · W-43**
- **Como** cualquier persona en obra **quiero** leer la ida y vuelta, comentar, completar con la foto
  del remito y cargar un pedido nuevo desde el celular, en menos de un minuto, **para** resolver
  sin esperar a volver a la oficina.
- **Criterios de aceptación**
  1. **Dado** un pedido en el celular (W-42), **entonces** se leen sus datos, links y comentarios
     de corrido, y abajo están "Bloquear" y "Completar" (si es el propietario) y "Escribir un
     comentario…".
  2. **Dado** "Completar", **cuando** escribe el comentario y toca "Subir archivo", **entonces** puede
     sacar la **foto del remito con la cámara** (HEIC del iPhone incluido) o pegar un link, y al
     confirmar el pedido queda Completada y avisa como en la PC (HU-034).
  3. **Dado** "+ Nuevo pedido" (W-43), **entonces** el formulario corto pide tarea, obra, sector,
     propietario, entrega, con quién y aclaraciones; la fecha de pedido la pone el sistema y la
     prioridad se ve calculada ("Prioritario (sola, 5 días)").
  4. **Dado** "Crear pedido", **entonces** ve "✓ Pedido creado" con "Se le avisó a {propietario}",
     y los botones "Agregar un link", "Ver el pedido" y "Cargar otro pedido" (vuelve al
     formulario vacío).
  5. **Dado** una persona con los datos del pedido en mano, **cuando** lo carga en el celular,
     **entonces** lo termina en **menos de un minuto** (criterio global n.º 4; se mide en E2E).
  6. **Dado** el celular, **entonces** las mismas reglas y permisos que en la PC (es el mismo
     backend) y sin desplazamiento horizontal.
- **Reglas:** spec pedidos.md.
- **Permisos:** los de HU-026, HU-029, HU-034.
- **Dependencias:** HU-026, HU-028, HU-029, HU-034, HU-051.
- **DoR:** ✅ listo.

#### HU-053 · Obras en el celular con sus pedidos
- **F-13 · F-15 · F-17 · W-47**
- **Como** cualquier persona en obra **quiero** entrar a una obra desde el celular y ver sus pedidos
  **para** saber qué falta en esa obra.
- **Criterios de aceptación**
  1. **Dado** Obras en el celular, **entonces** cada tarjeta muestra N.º, trabajo, cliente,
     responsable y **"N pedidos abiertos"** (el "—" de la semana 1 pasa a ser el número; "Gastado:
     —" sigue hasta la semana 3).
  2. **Dado** una obra, **cuando** la abre, **entonces** ve la pestaña **Pedidos (N)** con tarjetas
     (prioridad, tarea, propietario, entrega, estado) y "+ Nuevo pedido en esta obra" (W-43 con la
     obra ya elegida); "Rendición" sigue "Va a estar disponible en una próxima entrega." hasta la
     semana 3.
  3. **Dado** la ficha en el celular, **entonces** Documentos y contactos sigue como quedó con B-01.
- **Reglas:** obras.md RN-OBR-03 · spec pedidos.md.
- **Permisos:** `obras:ver` + `pedidos:ver`.
- **Dependencias:** HU-054, HU-055.
- **DoR:** ✅ listo.

### Épica Q · Integración con Obras

#### HU-054 · Pestaña Pedidos de la obra
- **F-17 (parte semana 2) · W-07**
- **Como** cualquier persona **quiero** entrar a la obra y ver sus pedidos en una pestaña, como hoy
  la solapa de la obra en la minuta, **para** tener adentro de la obra todo lo suyo.
- **Criterios de aceptación**
  1. **Dado** `obras:ver` y `pedidos:ver`, **cuando** abre la ficha, **entonces** la pestaña
     **Pedidos (N abiertos)** muestra Prioridad · Tarea · Sector · Propietario · Solicitante ·
     Entrega · Estado, con "Abiertos" (por defecto) y "Todos", filtro por sector y "+ Nuevo pedido
     en esta obra" (W-08 con la obra ya elegida).
  2. **Dado** una fila, **cuando** la toca, **entonces** abre el pedido.
  3. **Dado** un sector sin acceso a Pedidos, **entonces** la pestaña no aparece (y el backend no
     manda los pedidos).
  4. **Dado** una obra Terminada, **entonces** la pestaña muestra sus pedidos (abiertos y
     completados) y permite cargar uno nuevo con "Obra terminada" a la vista (DU-A15).
  5. **Dado** la semana 2 encendida, **entonces** desaparece "Disponible en la próxima entrega" de
     la pestaña Pedidos (HU-014).
- **Reglas:** obras.md §7 · spec pedidos.md.
- **Permisos:** `obras:ver` + `pedidos:ver` (con sus alcances).
- **Dependencias:** HU-026, HU-059 (puerto `PedidosDeObra`).
- **DoR:** ✅ listo.

#### HU-055 · Columna "pedidos abiertos" y fila Servicios generales en Obras
- **F-15 (parte semana 2) · W-05 · W-47**
- **Como** cualquier persona **quiero** ver en el listado cuántos pedidos abiertos tiene cada obra y
  una fila para los pedidos sin obra **para** ver de un vistazo cómo viene cada una.
- **Criterios de aceptación**
  1. **Dado** el listado de obras, **entonces** "Pedidos abiertos" muestra el número de pedidos No
     iniciada, En curso o Bloqueada de cada obra (0 si no tiene; ya no "—").
  2. **Dado** una página de 25 obras, **entonces** los números salen en **una sola consulta**
     (`contarAbiertosPorObra`), no una por obra.
  3. **Dado** `pedidos:ver`, **entonces** al final de **En ejecución** aparece la fila
     "Servicios generales · pedidos que no son de una obra" con la cantidad de pedidos sin obra
     abiertos y sin % gastado, que lleva a Servicios generales (W-25); sin acceso a Pedidos, o con
     texto en el buscador, no aparece (RN-OBR-08).
  4. **Dado** alcance "propios" en Pedidos, **entonces** los números cuentan según **DU-P17 ✅**
     (propuesta: todos los abiertos de la obra, porque es un conteo; la pestaña y las listas sí
     respetan el alcance).
- **Reglas:** obras.md RN-OBR-02, 03, 08.
- **Permisos:** `obras:ver`; la fila, además `pedidos:ver`.
- **Dependencias:** HU-013, HU-026, HU-043.
- **DoR:** ✅ listo (dudas resueltas el 19-nov-2026: va la propuesta).

#### HU-056 · Pedidos abiertos al pasar la obra a Terminada
- **F-21 (parte semana 2) · W-22**
- **Como** responsable de obra o Administración **quiero** ver los pedidos que siguen abiertos antes
  de pasar la obra a Terminada **para** completarlos o reasignarlos y que no queden escondidos como
  hoy en las solapas ocultas.
- **Criterios de aceptación**
  1. **Dado** una obra con pedidos abiertos, **cuando** elige "Terminada" en W-22, **entonces** ve
     "Esta obra tiene N pedidos abiertos" con Tarea · Propietario · Estado y "completar · reasignar"
     por pedido; sin abiertos, no ve la tabla.
  2. **Dado** que confirma "Pasar a Terminada" con pedidos abiertos, **entonces** la obra pasa igual
     (no frena; resuelta DU-12) y esos pedidos siguen en Mis tareas de su propietario y en la
     pestaña Pedidos de la obra.
  3. **Dado** que mientras tenía la ventana abierta alguien cargó otro pedido en la obra, **cuando**
     confirma, **entonces** 409 con la lista actualizada y la obra no cambia de estado
     (obras.md §6).
  4. **Dado** "completar · reasignar" para quien cierra la obra y no es propietario ni solicitante:
     **DU-P12 ✅** (propuesta: los accesos abren W-29 y W-31 con sus reglas de siempre y aparecen solo
     donde quien mira puede usarlos; si no, ve el pedido sin acceso).
- **Reglas:** obras.md RN-OBR-29, 30, §6 · spec pedidos.md.
- **Permisos:** los de cambiar el estado (HU-017); las acciones sobre cada pedido, las de HU-034 y
  HU-035.
- **Dependencias:** HU-017, HU-034, HU-035, HU-059.
- **DoR:** ✅ listo (dudas resueltas el 19-nov-2026: va la propuesta).

### Épica R · Buscador: pedidos por texto

#### HU-057 · Buscador general: pedidos por texto
- **F-03 (parte semana 2) · W-18**
- **Como** cualquier persona **quiero** encontrar un pedido escribiendo una parte de su texto en el
  buscador de arriba **para** no recorrer obras ni filtros.
- **Criterios de aceptación**
  1. **Dado** que escribe 2 o más caracteres, **entonces** aparece el grupo **Pedidos (N)** junto a
     Obras y Proveedores, cada uno con tarea · obra (o "Servicios generales") · propietario ·
     estado, que abre el pedido.
  2. **Dado** la búsqueda, **entonces** no distingue mayúsculas ni tildes y encuentra pedidos de
     cualquier estado (los completados también) y de obras terminadas.
  3. **Dado** qué texto del pedido se busca: **DU-P15 ✅** (propuesta: tarea, con quién / para quién y
     aclaraciones; no los comentarios; abiertos primero). Nota de QC para esa decisión: el ejemplo
     de W-18 ("tanque") muestra pedidos de la obra "losa tanque B" cuya tarea no dice "tanque"; con la
     propuesta, esos se encuentran desde el grupo Obras → pestaña Pedidos.
  4. **Dado** sin acceso a Pedidos, **entonces** el grupo no aparece ni viaja en la respuesta; con
     alcance "propios", solo trae los que puede ver.
  5. **Dado** muchos resultados, **entonces** muestra los primeros 10 y la cantidad total, como los
     otros grupos.
- **Reglas:** `App\Shared\Busqueda\FuenteBusqueda` (Pedidos registra su fuente) · spec pedidos.md.
- **Permisos:** `pedidos:ver` con su alcance.
- **Dependencias:** HU-022, HU-026.
- **DoR:** ✅ listo (dudas resueltas el 19-nov-2026: va la propuesta).

### Épica S · Arrastre de semana 1

Estado al cierre de la semana 1: los **bugs B-01…B-05 están arreglados** (cada uno con su test,
`qc/entrega-1.md`) y **no queda ninguno abierto**. Los hallazgos de `seguridad.md` cerrados quedan
cerrados; siguen en pausa ⏸ **TL-03, TL-07, TL-08, TL-13, FE-06 (`obras.css`) y FE-07**. De esos,
TL-03, TL-07 y TL-08 los toca Pedidos y se resuelven antes de construirlo (HU-059); TL-13 y FE-07
(nombres de tests por regla) y FE-06 se cumplen al tocar cada archivo (DoD). Sigue en pie la
condición de entorno de B-04 (subida ≥ 21 MB en el servidor del cliente) y los pendientes de
Juani (avisar al cliente ADR-014 y ADR-015; servidor de staging).

#### HU-058 · Personas y sectores con pedidos abiertos
- **F-58 · F-59 · W-39 · W-40** (completa HU-006 y HU-007, que dejaron este caso para la semana 2)
- **Como** gerencia **quiero** que al desactivar a una persona o sacar un sector el sistema me diga
  qué pedidos abiertos quedan **para** que ninguno quede sin nadie que lo reciba.
- **Criterios de aceptación**
  1. **Dado** un sector con pedidos abiertos **en el sector** (sin persona), **cuando** lo quiere
     sacar, **entonces** se sigue **DU-Q02 ✅** (propuesta: "Antes de sacar {sector}, reasigná sus
     N pedidos que están en el sector." y no se saca, 422).
  2. **Dado** un sector con pedidos (abiertos con persona o completados) y sin usuarios activos,
     **cuando** lo saca, **entonces** se **archiva** y los pedidos conservan su nombre (resuelta
     DU-A07).
  3. **Dado** una persona con pedidos abiertos como propietaria, **cuando** la desactivan,
     **entonces** se sigue **DU-P09 ✅** (propuesta: quedan como están, con "{persona} (inactiva)", y
     se ven en Tareas de mi gente de su jefe; no se reasigna nada solo, como DU-A08). Si era la
     última persona activa de un sector con pedidos en el sector: **DU-Q02 ✅** (aviso en W-39).
  4. **Dado** una persona inactiva, **entonces** no aparece en los desplegables de propietario,
     seguidor ni "una persona" de Reasignar.
- **Reglas:** identidad.md RN-IDE-25…33 · spec pedidos.md.
- **Permisos:** `usuarios_permisos:editar`.
- **Dependencias:** HU-006, HU-007, HU-035.
- **DoR:** ✅ listo (dudas resueltas el 19-nov-2026: va la propuesta).

#### HU-059 · Deuda técnica que toca Pedidos (TL-03, TL-07, TL-08) y contratos de Obras
- **— (transversal) · seguridad.md §4.4 · obras.md §10.4**
- **Como** el equipo **queremos** cerrar antes de construir Pedidos lo que quedó en pausa y que
  Pedidos va a usar desde el primer día **para** no copiar en un módulo nuevo lo que ya sabemos que
  hay que cambiar.
- **Criterios de aceptación**
  1. **TL-03:** **dado** un listado paginado con datos extra (`meta` de Obras hoy; Todos los pedidos,
     Mis tareas y avisos en la semana 2), **cuando** se arma la respuesta, **entonces** sale de
     `ApiResponse::paginated($paginator, $meta)` (o equivalente) sin desarmar el sobre en el
     controller; el JSON de Obras no cambia (los tests de `ListadoObrasTest` siguen en verde).
  2. **TL-07:** **dado** `AdjuntosService::asociar()`, **cuando** lo llama un caso de uso, **entonces**
     recibe el usuario (o su id) como parámetro y no lo lee del guard; los usos de Obras se
     actualizan y sus tests siguen en verde (lo necesitan la Importación de la semana 4 y cualquier
     job).
  3. **TL-08:** **dado** `app/Shared`, **entonces** queda decidido y escrito en ADR-003 si Shared
     tipa con `Authenticatable`/id o si la dependencia a `User` es la excepción admitida, y el test
     de arquitectura lo verifica.
  4. **Puerto `PedidosDeObra`:** **dado** el módulo Pedidos, **entonces** implementa
     `abiertosDe`, `contarAbiertosPorObra` y `contarAbiertosSinObra` y reemplaza a
     `PedidosDeObraNulo` (los tests de semana 1 que esperaban `[]`/`null` pasan a esperar datos).
  5. **Elegibilidad:** **dado** el contrato que Obras expone para "obra elegible para pedidos",
     **entonces** no mira el presupuesto por rubro (HU-019 c2) y admite obras Terminadas (DU-A15).
- **Reglas:** ADR-003 · ADR-014 · obras.md §10.4.
- **Permisos:** —
- **Dependencias:** —
- **DoR:** ✅ listo.

#### HU-060 · Encender la semana 2 en la barra y en el entorno de prueba
- **F-02 · F-13 · W-17 · W-41** (DU-A12: cada entrega enciende lo suyo)
- **Como** la empresa **queremos** que la entrega de la semana 2 aparezca completa y que los avisos
  funcionen solos en el entorno donde prueba el cliente **para** poder usarla en paralelo con la
  minuta desde el primer día.
- **Criterios de aceptación**
  1. **Dado** la configuración de módulos entregados con `pedidos` y `avisos`, **entonces** la barra
     muestra Inicio (Mis tareas) · Pedidos · Obras · Calendario · Proveedores · Usuarios según la
     tabla, la campana con contador en PC y celular, y abajo en el celular Tareas · Obras · Avisos
     · Más; Gastos y Rendiciones siguen ocultos hasta la semana 3.
  2. **Dado** el entorno de prueba del cliente, **entonces** el **worker de la cola `mail`** y el
     **scheduler** corren en Supervisord, y se verifica con un pedido de prueba: el mail de
     asignación llega y el "vence mañana" sale solo al día siguiente (con un pedido ficticio).
  3. **Dado** ese entorno, **entonces** se mantiene la condición de B-04 (subida ≥ 21 MB en PHP y
     nginx) porque la foto del remito sale del celular.
  4. **Dado** las notas de entrega de la semana 2 para el cliente, **entonces** dicen en sus
     palabras qué probar y cómo (cargar en los dos lados, qué avisos van a ver, que por mail solo
     llega la asignación).
- **Reglas:** identidad.md §6.4 · ADR-012 · seguridad.md §6.
- **Permisos:** —
- **Dependencias:** las historias "sí o sí" de §6.1.
- **DoR:** ✅ listo.

**Control de completitud de la semana 2:** las **27 F-XX que arrancan** (F-04…F-09, F-11, F-12,
F-13, F-24…F-38, F-64, F-65, F-67) tienen al menos una historia, y las **8 repartidas** que se
completan acá (F-03, F-10, F-14, F-15, F-17, F-21, F-61, F-68) también (ver §1). Además: F-02 y
F-13 (HU-060), F-58 y F-59 (HU-058). Pantallas de la semana: W-01, W-02, W-03, W-04, W-07 (pestaña
Pedidos), W-08, W-09, W-18, W-19 (Mis avisos), W-22, W-24…W-32, W-41, W-42, W-43, W-45, W-47,
W-49, W-50 (uso).

Dudas de esta semana: DU-04, DU-12, DU-13, DU-14, DU-15, DU-16, DU-22, DU-A13 y DU-A15,
**resueltas el 12-nov** (DU-13 con el texto firmado: el aviso de completado va al solicitante y al
superior del solicitante). **Nuevas (16-nov), abiertas, decide Juani:** DU-Q01…DU-Q03 (PO + QA, al bajar las historias) y
DU-P01…DU-P24 (Analista, al escribir la spec), cada una con su propuesta más cercana a la minuta.
Las que tocan historias "sí o sí" de la prueba del cliente (§6.1) y conviene decidir primero:
**DU-P01, DU-P02, DU-P05, DU-P08, DU-P19, DU-P21 y DU-Q01**.

---

## 7. Semana 3 · Gastos y rendición (detalle)

> Bajada a historias el 24-nov-2026, con la semana 2 aprobada por el cliente (prueba en paralelo con la minuta).
> Contrato: cap. 3, 4.3, 4.4, 8, 9, 10 (F-57) y 13 (criterios n.º 1 y 2) del documento firmado; maquetas W-05, W-07,
> W-10…W-14, W-18, W-33…W-38, W-44, W-46…W-48; ADR-005, ADR-006, **ADR-007** (los renglones son la única fuente del
> gastado), **ADR-008** (importes), ADR-010, ADR-011 y ADR-014 (contextos `comprobante_gasto` y `remito`). Las reglas
> finas están en [`especificacion/gastos.md`](especificacion/gastos.md) (RN-GAS-NN) y
> [`especificacion/rendicion.md`](especificacion/rendicion.md) (RN-REN-NN), que el 24-nov se alinearon entre sí
> (`gastos.md` §15, `rendicion.md` §17). Si una spec y un criterio no coinciden, se corrige el criterio antes de construir.

**27 historias** (HU-061…HU-087) en 7 épicas. Resumen de estado DoR: **27 ✅ listas, sin criterios ⛔**: las dudas de
la semana (DU-08, DU-17, DU-18, DU-19 del 12-nov; DU-G01…DU-G11 y DU-R01…DU-R09 del 24-nov) están **resueltas** (va la
propuesta; DU-G08 reemplazada por DU-R05 y DU-R03 por DU-G05).

| Épica | Historias | ✅ | Criterio que dependía de una duda (resuelta el 24-nov) |
|---|---|---|---|
| T · Cargar facturas | HU-061…HU-065 | 5 | HU-061 (G01), HU-062 (G04), HU-065 (G10) |
| U · Gastos sin factura y cargas desde la rendición | HU-066…HU-070 | 5 | HU-066 (G01, G02), HU-068 (G03, R05, R06), HU-069 (G05), HU-070 (G09, R04, R05) |
| V · Gastos cargados, corregir y anular | HU-071…HU-073 | 3 | HU-071 (G06, G11), HU-072 (G11), HU-073 (G11) |
| W · Rendición de la obra | HU-074…HU-079 | 6 | HU-074 (R07), HU-075 (R02), HU-076 (R01), HU-077 (R07), HU-078 (R07) |
| X · Todas las rendiciones y % gastado | HU-080…HU-081 | 2 | — |
| Y · Control de materiales por cantidad | HU-082…HU-083 | 2 | HU-083 (R06) |
| Z · Lo que se completa de semanas 1 y 2, contratos y encendido | HU-084…HU-087 | 4 | HU-084 (G06), HU-085 (G07), HU-086 (R08) |

> **Orden de construcción sugerido:** Z (HU-086 contratos y tablas) → T (HU-061, HU-062, HU-065) → W (HU-074) → U
> (HU-066, HU-070) → V → W (HU-075…HU-079) → X → U (HU-067, HU-068, HU-069) → Y → T (HU-063, HU-064) → Z (HU-084,
> HU-085, HU-087). Sin el contrato `GastadoDeObras` y la tabla `gasto_renglones` con su fecha no hay rendición que
> mirar, y sin factura con reparto por renglón (HU-061) no se puede probar el criterio de aceptación global n.º 1.

### 7.1 La prueba del cliente de la semana 3

**Qué prueban (roadmap firmado):** "Cargan las facturas de una obra en curso y la comparan con su rendición en Excel."
Eligen una obra En ejecución que ya cargaron en la semana 1 (con su presupuesto por rubro en W-13), cargan en el
sistema las facturas y gastos de esa obra que tienen en su libro "Gasto de obra" y comparan la rendición (W-11) contra
su hoja "General", rubro por rubro. La guía de comparación es `rendicion.md` §13 (qué número es cuál y dónde puede haber
diferencias que no son errores: IVA, redondeo por renglón, "% rent" que no es el % consumido).

**Tiene que estar sí o sí** (sin esto la comparación no se puede hacer o deja de ser justa):

| Lo que hacen en su Excel hoy | Historias que lo sostienen |
|---|---|
| Cargar cada factura en su libro, una fila por ítem, con obra y rubro | HU-061, HU-062 |
| Elegir (o dar de alta) el proveedor | HU-063 |
| Repartir una factura entre obras y dejar fuera lo de stock u oficina | HU-061 |
| Rendir gastos sin ticket, combustible y consumo de stock | HU-066 |
| Amortización, retiros de Tecnobahía y devoluciones | HU-067, HU-068, HU-069 |
| Horas liquidadas y fijos por quincena o mes | HU-070 |
| Corregir una fila mal cargada o sacar una repetida | HU-065, HU-072, HU-073 |
| La hoja "General": presupuestado, gastado y saldo por rubro, EPP dentro de INSUMOS | HU-074, HU-075 |
| "Cobrado · Ganancia · %" | HU-076 |
| Sus tablas dinámicas por rubro y por proveedor | HU-077, HU-078, HU-071 |
| Mirar todas las obras en curso juntas (hoy, una planilla por obra) | HU-080 |
| Que el entorno tenga la obra, sus usuarios y la guía de comparación | HU-086, HU-087 |

Se entregan en la misma semana pero **la prueba no depende de ellas** (si alguna se atrasa, se entrega en la semana 4
sin frenar la prueba; se le dice a Juani el miércoles): HU-064 (archivo de la factura), HU-079 (pestaña en el celular),
HU-081 (% del listado de obras), HU-082 y HU-083 (control por cantidad), HU-084 (gastado por
obra del proveedor), HU-085 (buscador).

**Criterio de aceptación global de la semana 3 — el caso de referencia** (`rendicion.md` §9, datos ficticios):
**Dado** la obra "Ampliación de nave de ensayo" con sus dos presupuestos (principal por rubro 11.150.000 y A1 solo total
1.000.000), su lista de 4 materiales y los **21 renglones** de `rendicion.md` §9.2 cargados **por la API de Gastos y de
Obras** (incluidos el renglón sin obra, el de la factura anulada y el de la "Obra de control"), **cuando** se piden W-11,
W-36, W-37, W-48, W-12 y W-05, **entonces** cada número da **exacto, al centavo**, lo de `rendicion.md` §9.3 y §9.4:
MATERIALES 2.701.827,63 (54 %), INSUMOS · incluye EPP 130.333,30, LOGISTICA, MANTENIMIENTO, FIJOS y HORAS LIQUIDADAS
pasados, total de la obra 12.150.000 · 6.727.161,68 · 5.422.838,32 · 55 %, monto 16.300.000 con ganancia prevista
4.150.000 (25 %) y al día 9.572.838,32 (59 %), W-37 con 6 grupos y 4 comprobantes, W-48 con total de la lista
2.751.080,13 y la línea "Renglones de MATERIALES sin material asociado: −49.252,50", W-12 con 53 %. Y al **anular** la
factura 0004-00000020, MATERIALES baja a 1.436.395,50, LOGISTICA deja de estar pasada y el total queda en 5.201.729,55
(43 %). Es el test `RendicionCasoDeReferenciaTest` (feature) y el E2E de la prueba
(`rendicion.spec.ts › el caso de referencia da cada rubro al centavo`). Si no está en verde el miércoles, la prueba peligra
(§8).

**Señales de que la prueba peligra** (se miran el miércoles de la semana 3): las de §8.

**Qué mira QC durante la prueba** (detalle en `qa/plan-de-pruebas.md`): diferencia por rubro entre W-11 y su General =
0, o explicada por una fila de la guía de `rendicion.md` §13 (IVA, redondeo, un renglón que en su Excel dice "INSUMO");
que ninguna factura del libro quede sin cargar o cargada dos veces (HU-065); que cada gerencia lea su rendición sola en
PC y celular (criterio global n.º 1).

### Épica T · Cargar facturas

#### HU-061 · Cargar una factura con sus renglones, cada uno a su obra y su rubro
- **F-39 · F-40 · W-10**
- **Como** administración o compras **quiero** cargar cada factura una sola vez, renglón por renglón como mi libro de
  gastos, mandando cada renglón a su obra y su rubro **para** no cargarla en facturas, en la rendición y en la obra.
- **Criterios de aceptación**
  1. **Dado** `gastos:editar`, **cuando** carga Tipo "Factura", Número, Fecha, Proveedor y los 5 renglones de W-10
     (Concepto · Cantidad · Precio unitario · Obra · Rubro) y toca "Guardar factura", **entonces** se guarda en una
     transacción con la actividad `creado`, ve "✓ Factura cargada. Ya se restó en la rendición de Piso industrial nave 3
     y Reparación de losa tanque B." y la rendición de cada obra ya lo resta (RN-GAS-03, RN-GAS-09, RN-GAS-16).
  2. **Dado** que falta número, fecha, proveedor, un renglón, o en un renglón el concepto, la cantidad, el precio o la
     obra (o "(sin obra)"), **cuando** guarda, **entonces** 422 con el error en su campo o renglón ("Renglón 3: elegí el
     rubro.") y no se guarda nada (RN-GAS-03, RN-GAS-09).
  3. **Dado** un renglón con obra, **entonces** el rubro es obligatorio y sale de los 11 que se imputan; FIJOS y HORAS
     LIQUIDADAS dan 422 "FIJOS y HORAS LIQUIDADAS se cargan como monto por período, desde la rendición."; sin obra, el
     rubro es opcional y el renglón no suma en ninguna rendición (RN-GAS-12, RN-GAS-13).
  4. **Dado** 10 bolsas que van 6 a una obra y 4 a otra, **entonces** se cargan dos renglones: no hay reparto por
     porcentaje (RN-GAS-13).
  5. **Dado** una obra Terminada o sin presupuesto cargado, **cuando** la elige, **entonces** se acepta, con "Obra
     terminada" al lado si lo está (RN-GAS-13, DU-A15).
  6. **Dado** la fecha, **entonces** por defecto es hoy en Argentina y no puede ser posterior ("La fecha no puede ser
     posterior a hoy."); cada renglón guarda la fecha de la factura como su fecha efectiva (RN-GAS-04, §4.2, **DU-G01 ✅**).
  7. **Dado** un renglón de una obra con materiales, **cuando** elige un material de esa obra o "No está en la lista"
     (nombre y unidad), **entonces** cuenta como "pedido" en W-48; un material de otra obra da 422 "El material Cemento x
     50 kg es de otra obra: quitá el material o elegí uno de esta obra." (RN-GAS-14).
  8. **Dado** un doble clic en "Guardar factura" con la misma `Idempotency-Key`, **entonces** queda un solo gasto
     (RN-GAS-50); `GastoImputado {gastoId, obraIds}` sale después del commit (RN-GAS-16).
  9. **Dado** un usuario sin `gastos:editar` (o con `gastos:editar (propias)` que imputa a una obra ajena o a "(sin
     obra)"), **cuando** llama al alta, **entonces** 403 (RN-GAS-49).
- **Reglas:** gastos.md RN-GAS-02…04, 09, 12…14, 16, 49, 50.
- **Permisos:** `gastos:editar` (inicial: Gerencia y Jefatura de Administración, Compras, E·T).
- **Dependencias:** HU-086 (tablas y contratos), HU-012 (obras), HU-020 (proveedores).
- **DoR:** ✅ listo.

#### HU-062 · Total calculado solo, sin IVA y con moneda convertida
- **F-41 · W-10**
- **Como** quien carga una factura **quiero** que el total de cada renglón y de la factura se calcule solo **para**
  compararlo con el papel antes de guardar y no tipear dos veces.
- **Criterios de aceptación**
  1. **Dado** cantidad y precio, **entonces** el total del renglón es `cantidad × precio` redondeado a 2 decimales mitad
     para arriba en valor absoluto (12,5 × 101.234,57 = 1.265.432,13); lo calcula el backend y un `total` enviado por el
     front se ignora (RN-GAS-10, §4.5).
  2. **Dado** los renglones, **entonces** el total de la factura es la suma sin volver a redondear y al lado se ve el
     reparto por obra en vivo ("905.600 · Nave 3: 549.000 · Losa tanque B: 311.600 · sin obra: 45.000") (RN-GAS-11).
  3. **Dado** un precio con más de 2 decimales o una cantidad con más de 3, **entonces** 422 (no se redondea en
     silencio); una cantidad negativa en una factura da 422 "La cantidad va en negativo solo en una devolución."
     (§4.5, RN-GAS-10).
  4. **Dado** una compra en dólares o con bonificación, **entonces** se carga convertida y con la bonificación en el
     precio, con la cotización en el detalle; adelanto y saldo van en dos renglones (RN-GAS-15).
  5. **Dado** el pie del formulario, **entonces** dice "Importes sin IVA (netos)": neto si el comprobante discrimina IVA,
     lo pagado si no (factura C, ticket) (§4.5, **DU-G04 ✅**, se confirma con María en la prueba).
  6. **Dado** más de 200 renglones, **entonces** 422 "Hasta 200 renglones por comprobante: dividilo en dos cargas."
- **Reglas:** gastos.md RN-GAS-10, 11, 15, §4.5 · ADR-008.
- **Permisos:** los de HU-061.
- **Dependencias:** HU-061.
- **DoR:** ✅ listo.

#### HU-063 · Elegir el proveedor de la base o darlo de alta sin salir de la factura
- **F-42 · W-10 · W-38**
- **Como** quien carga **quiero** buscar el proveedor por nombre o CUIT y, si no está, darlo de alta ahí mismo **para**
  no perder lo cargado.
- **Criterios de aceptación**
  1. **Dado** el buscador de proveedor, **cuando** elige uno, **entonces** se ven su CUIT y su rubro habitual, que se pone
     en los renglones sin rubro (RN-GAS-05).
  2. **Dado** `proveedores:editar`, **cuando** toca "+ nuevo proveedor", **entonces** abre W-38 con el aviso de parecidos
     y al guardar vuelve a la factura con el proveedor elegido y los renglones intactos (RN-GAS-05, RN-PRV-08…10).
  3. **Dado** el proveedor genérico, **cuando** se elige en una factura, **entonces** 422 "El proveedor genérico es solo
     para gastos sin documento." (RN-GAS-06).
- **Reglas:** gastos.md RN-GAS-05, 06 · proveedores.md RN-PRV-03, 08…10, 14.
- **Permisos:** `gastos:editar`; el alta rápida, `proveedores:editar`.
- **Dependencias:** HU-061, HU-021.
- **DoR:** ✅ listo.

#### HU-064 · La factura como archivo o link
- **F-43 · ADR-014 (contexto `comprobante_gasto`) · W-10 · W-34**
- **Como** administración **quiero** subir la factura escaneada o pegar su link **para** tenerla a mano; el papel queda
  en la oficina.
- **Criterios de aceptación**
  1. **Dado** el campo "Factura (opcional) · Subir archivo o pegar link", **cuando** sube uno o más (hasta 10) o no sube
     ninguno, **entonces** se guarda igual (§4.4, HU-025).
  2. **Dado** un adjunto ajeno, ya usado o de otro contexto, **entonces** 422 "Ese adjunto no se puede usar acá."
     (RN-IDE-46).
  3. **Dado** un gasto fuera del alcance de quien pide el archivo, **entonces** 404; un gasto anulado conserva sus
     adjuntos (§4.4, RN-GAS-48).
- **Reglas:** gastos.md §4.4 · identidad.md §6.6.
- **Permisos:** asociar `gastos:editar`; bajar `gastos:ver` (con alcance).
- **Dependencias:** HU-061, HU-025.
- **DoR:** ✅ listo.

#### HU-065 · Una factura no se carga dos veces
- **F-39 · criterio de aceptación global n.º 2 · W-10 · W-34**
- **Como** administración **quiero** que el sistema me frene si la factura ya está cargada **para** que la rendición no
  sume dos veces lo mismo.
- **Criterios de aceptación**
  1. **Dado** "3-12345", **entonces** se guarda normalizado "0003-00012345" (RN-GAS-07, **DU-G10 ✅**).
  2. **Dado** una factura no anulada del mismo proveedor con el mismo número normalizado, **cuando** sale del campo
     Número, **entonces** avisa "Esa factura ya está cargada: 0003-00012345 de Corralón Centro (Diego, 17-oct)." con
     link a W-34, y al guardar 422 con el mismo texto (RN-GAS-08).
  3. **Dado** el mismo número de otro proveedor, o una factura anulada, **entonces** se acepta (RN-GAS-08).
  4. **Dado** dos cargas a la vez de la misma factura, **entonces** el índice único deja una y la otra recibe el 422.
- **Reglas:** gastos.md RN-GAS-07, 08 · modelo-datos `numero_vigente`.
- **Permisos:** los de HU-061.
- **Dependencias:** HU-061.
- **DoR:** ✅ listo.

### Épica U · Gastos sin factura y cargas desde la rendición

#### HU-066 · Gastos sin comprobante, combustible y consumo de stock
- **F-44 · F-47 · W-35**
- **Como** administración **quiero** rendir los gastos de obra sin ticket, el combustible por carga y el consumo de
  stock propio, cada renglón con su fecha, obra y rubro **para** que sumen en la rendición igual que una factura.
- **Criterios de aceptación**
  1. **Dado** el modo "Sin documento", **entonces** se elige "Qué se carga: Gastos de obra rendidos · Combustible por
     carga · Consumo de stock propio" y cada renglón lleva **Fecha** (la del ticket, ≤ hoy, por defecto la de la
     rendición) (RN-GAS-17, RN-GAS-21, **DU-G01 ✅**, **DU-G02 ✅**).
  2. **Dado** "Gastos de obra rendidos", **entonces** "Rendido por" es obligatorio ("Elegí o escribí quién rindió."): una
     persona de la lista o un texto ("supervisor de obra Juan") (RN-GAS-18).
  3. **Dado** el proveedor, **entonces** por defecto es el genérico (se ve "—") y se puede elegir uno de la base, como la
     estación de servicio (RN-GAS-20).
  4. **Dado** "Consumo de stock propio" con un renglón "(sin obra)", **entonces** 422 "El consumo de stock va a una obra."
     (RN-GAS-19).
  5. **Dado** fotos de los tickets (también HEIC desde el celular), **entonces** se suben como archivo o link
     (`comprobante_gasto`, 0 a 10) (RN-GAS-17).
  6. **Dado** el caso de Laura (combustible 68.000, comida 42.000, peaje 3.500), **entonces** "Total rendido · 113.500" y
     cada renglón entra en la rendición con su fecha (gastos.md §10 caso 19).
- **Reglas:** gastos.md RN-GAS-17…21.
- **Permisos:** `gastos:editar`.
- **Dependencias:** HU-061.
- **DoR:** ✅ listo.

#### HU-067 · Amortización de herramientas, una por obra
- **F-47 · W-46 · W-11**
- **Como** administración **quiero** cargar la amortización de herramientas de la obra con su monto a mano **para** que
  sume en AMORTIZACION como hoy.
- **Criterios de aceptación**
  1. **Dado** W-46 abierta desde la rendición ("+ cargar renglón sin documento"), **cuando** carga Fecha · Concepto ·
     Importe, **entonces** queda un renglón de AMORTIZACION en esa obra (RN-GAS-22, RN-GAS-23).
  2. **Dado** que la obra ya tiene su amortización no anulada, **entonces** la sección la muestra con "Corregir" y otra
     carga da 422 "Esta obra ya tiene su amortización (310.000): corregila."; dos cargas a la vez dejan una (RN-GAS-23,
     RN-GAS-50).
  3. **Dado** alguien sin `rendicion:editar` en esa obra, **entonces** no ve el acceso y la API responde 403; corregirla
     en W-34 pide `gastos:editar` (gastos.md §8.1, RN-REN-58, **DU-R05 ✅**).
- **Reglas:** gastos.md RN-GAS-22, 23, 26 · rendicion.md RN-REN-58.
- **Permisos:** cargar `rendicion:editar`; corregir y anular `gastos:editar`.
- **Dependencias:** HU-061, HU-074.
- **DoR:** ✅ listo.

#### HU-068 · Retiros con remito: una sola carga para la rendición y el control por cantidad
- **F-47 · F-66 · ADR-014 (contexto `remito`) · W-46 · W-48**
- **Como** administración **quiero** cargar cada retiro de Tecnobahía una sola vez con su remito, eligiendo los
  materiales de la lista **para** que sume en la rendición y en "retirado" de W-48 con el mismo renglón.
- **Criterios de aceptación**
  1. **Dado** la sección "Retiros", **cuando** carga Fecha, N.º de remito, Proveedor de la base (sugerido el último
     usado), Remito (archivo, foto o link, opcional) y renglones Material · Cantidad · Precio · Total · Rubro, **entonces**
     queda un gasto `retiro_remito` que resta en su rubro (MATERIALES por defecto) y suma en W-48 (RN-GAS-24, resuelta
     DU-17, **DU-G03 ✅**).
  2. **Dado** un material de la lista, **entonces** se sugiere su precio presupuestado (editable) y la cantidad va en su
     unidad ("bolsas") (RN-GAS-24, **DU-R06 ✅**).
  3. **Dado** algo que no está en la lista, **cuando** elige "No está en la lista" y escribe material y unidad,
     **entonces** se crea como material fuera de presupuesto de la obra y aparece en "Fuera de presupuesto" de W-48; el
     próximo retiro ya lo ofrece; sin material ni "No está en la lista": 422 (RN-GAS-14, RN-GAS-24, RN-REN-49).
  4. **Dado** un N.º de remito del mismo proveedor ya cargado, **entonces** avisa "Ese remito ya está cargado (14-oct,
     Piso industrial nave 3). ¿Cargarlo igual?" y se guarda si confirma (RN-GAS-24).
  5. **Dado** `rendicion:editar` **o** `control_cantidad:editar` en esa obra, **entonces** puede cargar el retiro (desde
     W-11 o desde W-48 "+ cargar retiro con remito"); sin ninguna de las dos, 403 (RN-REN-58, **DU-R05 ✅**).
  6. **Dado** el remito, **entonces** lo ve quien tiene `gastos:ver` o `control_cantidad:ver` (con alcance) (identidad.md
     §6.6).
- **Reglas:** gastos.md RN-GAS-14, 24, 26 · rendicion.md RN-REN-48, 49, 53, 58.
- **Permisos:** cargar `rendicion:editar` o `control_cantidad:editar`; corregir y anular `gastos:editar`.
- **Dependencias:** HU-061, HU-082 (lista; sin ella todo entra como fuera de presupuesto, ver §8).
- **DoR:** ✅ listo.

#### HU-069 · Devoluciones de materiales que restan
- **F-47 · W-46**
- **Como** administración **quiero** cargar el material devuelto con cantidad negativa **para** que reste en la
  rendición y en lo retirado.
- **Criterios de aceptación**
  1. **Dado** la sección "Devolución", **cuando** carga Fecha · Material · Cantidad · Total · Rubro, **entonces** la
     cantidad va en negativo ("−4"), el total da negativo y resta en la rendición y, si tiene material, en lo retirado de
     W-48 (RN-GAS-25).
  2. **Dado** una cantidad positiva, **entonces** 422 "En una devolución la cantidad va en negativo." (RN-GAS-25).
  3. **Dado** una nota de crédito del proveedor, **entonces** no hay tipo "nota de crédito": por material devuelto se
     carga como devolución (con el proveedor de la base); si corrige el precio, se corrige la factura en W-34
     (RN-GAS-25, **DU-G05 ✅**, que reemplaza a DU-R03).
  4. **Dado** alguien sin `rendicion:editar` en esa obra, **entonces** 403 (RN-REN-58).
- **Reglas:** gastos.md RN-GAS-25, 26 · rendicion.md RN-REN-04.
- **Permisos:** cargar `rendicion:editar`; corregir y anular `gastos:editar`.
- **Dependencias:** HU-061.
- **DoR:** ✅ listo.

#### HU-070 · Horas liquidadas y fijos por período
- **F-54 · W-46 · W-11**
- **Como** administración **quiero** cargar las horas de la quincena y los fijos del mes como un monto total por obra
  **para** que resten en la rendición sin cargar el detalle por empleado.
- **Criterios de aceptación**
  1. **Dado** "+ cargar período" en la fila FIJOS u HORAS LIQUIDADAS de W-11, **entonces** abre la sección "Horas
     liquidadas y fijos por período" de W-46 con la obra y el rubro elegidos: Rubro · Desde · Hasta · Importe · Concepto
     (por defecto "Horas liquidadas 1 al 15-oct") (RN-GAS-27, RN-REN-18, **DU-R04 ✅**).
  2. **Dado** hasta anterior a desde, desde posterior a hoy o importe vacío, **entonces** 422 "El 'hasta' no puede ser
     anterior al 'desde'." · "El período no puede empezar después de hoy." · "Poné el importe del período." (RN-GAS-27).
  3. **Dado** un período cargado, **entonces** su renglón tiene fecha = el "hasta", también si todavía no llegó (fijos del
     1 al 30-nov cargados el 24-nov: 30-nov), y resta en la rendición desde ya (RN-GAS-27, RN-REN-16, RN-REN-17).
  4. **Dado** un período del mismo rubro y obra que se superpone, **entonces** avisa "Ya hay HORAS LIQUIDADAS de Piso
     industrial nave 3 del 1 al 15-oct (1.300.000). ¿Cargar igual?" y se guarda solo si confirma (RN-GAS-28,
     **DU-G09 ✅**).
  5. **Dado** W-36 de HORAS LIQUIDADAS, **entonces** cada renglón se ve como "Período 1 al 15-oct" (RN-REN-18, RN-REN-29).
  6. **Dado** alguien sin `rendicion:editar` en esa obra, **entonces** no ve el acceso y la API responde 403 (RN-REN-58).
- **Reglas:** gastos.md RN-GAS-27, 28 · rendicion.md RN-REN-16…18.
- **Permisos:** cargar `rendicion:editar`; corregir y anular `gastos:editar`.
- **Dependencias:** HU-061, HU-074.
- **DoR:** ✅ listo.

### Épica V · Gastos cargados, corregir y anular

#### HU-071 · Gastos cargados: filtros, total de lo filtrado y agrupar
- **F-45 · F-02 · W-33 · W-17**
- **Como** administración y gerencias **quiero** ver los gastos cargados con filtros y el total de lo filtrado **para**
  sacar lo que hoy saco con tablas dinámicas.
- **Criterios de aceptación**
  1. **Dado** `gastos:ver`, **entonces** el ítem "Gastos" de la barra abre W-33 con Fecha · Documento · Proveedor · Obras
     · Rubros · Total · Cargó, la más nueva primero, de a 50, del 1 del mes a hoy por defecto (RN-GAS-01, 29, 30, 32).
  2. **Dado** los filtros Obra (o "(sin obra)"), Proveedor, Rubro, Fecha desde–hasta y Tipo, **entonces** se combinan y
     el pie dice "Total de lo filtrado · 6 comprobantes · 5.447.900" sobre todo lo filtrado (RN-GAS-30, 32).
  3. **Dado** un filtro de obra o rubro, **entonces** la fila muestra lo de los renglones que cumplen ("549.000 · de
     905.600") y el total suma eso (RN-GAS-31, **DU-G06 ✅**).
  4. **Dado** "Agrupar por: proveedor · obra · rubro", **entonces** cada grupo tiene su subtotal y un comprobante
     repartido aparece en cada grupo con su parte; EPP es su propio grupo (RN-GAS-33).
  5. **Dado** un gasto anulado, **entonces** se ve tachado y no suma ("1 anulado (no suma)") (RN-GAS-34, **DU-G11 ✅**).
  6. **Dado** la etiqueta Documento, **entonces** es la misma que en la rendición ("Retiro · remito …", "Período 1 al
     15-oct", "Carga inicial") (RN-GAS-29).
  7. **Dado** "ver todos" desde W-11, **entonces** abre W-33 con esa obra y sin límite de fecha (RN-GAS-35).
- **Reglas:** gastos.md RN-GAS-01, 29…35, 48.
- **Permisos:** `gastos:ver` (Ingeniería: `gastos:ver (propias)`, solo sus renglones, RN-GAS-48).
- **Dependencias:** HU-061.
- **DoR:** ✅ listo.

#### HU-072 · Detalle de un gasto y corregir con registro
- **F-46 · F-14 · W-34**
- **Como** administración **quiero** ver un gasto con quién lo cargó y corregirlo dejando registro **para** arreglar un
  error sin perder el rastro.
- **Criterios de aceptación**
  1. **Dado** W-34, **entonces** muestra cabecera, renglones, reparto por obra, "Cargó: Diego · 17-oct 10:42", "Última
     edición" y el historial (RN-GAS-36, RN-GAS-42).
  2. **Dado** `gastos:editar`, **cuando** corrige cabecera o renglones (cambiar, agregar, quitar), **entonces** valen las
     validaciones de la carga (incluida la factura repetida sin contarse a sí misma) y el historial dice qué cambió, antes
     → después ("Diego cambió el rubro de 'Guantes de trabajo': MATERIALES → INSUMOS"); adjuntos por nombre, sin URL
     (RN-GAS-37, RN-GAS-42).
  3. **Dado** un renglón que cambia de obra, **entonces** `GastoCorregido` lleva las obras de antes y de después y las dos
     rendiciones se recalculan (RN-GAS-38).
  4. **Dado** que se intenta cambiar el tipo de documento o qué se cargó, **entonces** no se permite: se anula y se carga
     de nuevo (RN-GAS-37, **DU-G11 ✅**).
  5. **Dado** dos personas corrigiendo a la vez, **entonces** la segunda recibe 409 "Alguien cambió esto mientras lo
     tenías abierto. Actualizá y revisá." (RN-GAS-39).
  6. **Dado** guardar sin cambios, **entonces** no se registra nada (RN-GAS-37).
- **Reglas:** gastos.md RN-GAS-36…39, 42 · ADR-010.
- **Permisos:** ver `gastos:ver`; corregir `gastos:editar` (también lo cargado desde W-46).
- **Dependencias:** HU-061.
- **DoR:** ✅ listo.

#### HU-073 · Anular un gasto en lugar de borrarlo
- **F-46 · W-34**
- **Como** administración **quiero** anular un gasto mal cargado con su motivo **para** que deje de sumar sin borrar la
  historia.
- **Criterios de aceptación**
  1. **Dado** `gastos:editar`, **cuando** anula con motivo, **entonces** queda tachado con "Anulada por Diego el 18-oct
     9:30: 'Cargada dos veces'.", deja de sumar en la rendición, el % gastado, el control por cantidad y lo gastado por
     proveedor, y conserva sus adjuntos e historial (RN-GAS-40).
  2. **Dado** anular sin motivo, **entonces** 422 "Contá por qué se anula." (RN-GAS-40).
  3. **Dado** un gasto anulado, **cuando** se intenta corregir o volver a anular, **entonces** 409 "Está anulada: no se
     corrige. Si hace falta, cargala de nuevo."; no hay ruta para borrar (RN-GAS-41, **DU-G11 ✅**).
  4. **Dado** una factura anulada, **entonces** su número queda libre para cargarla bien (RN-GAS-08).
- **Reglas:** gastos.md RN-GAS-40, 41 · rendicion.md RN-REN-03.
- **Permisos:** `gastos:editar`.
- **Dependencias:** HU-072.
- **DoR:** ✅ listo.

### Épica W · Rendición de la obra

#### HU-074 · Rendición de la obra por rubro, en vivo
- **F-49 · F-48 (alimenta) · F-54 (EPP) · W-11**
- **Como** gerencia **quiero** abrir una obra y ver por rubro lo presupuestado, lo gastado, el saldo y el % **para**
  saber cuánto llevo sin esperar al final.
- **Criterios de aceptación**
  1. **Dado** una obra con presupuesto por rubro, **entonces** W-11 muestra los 12 rubros en el orden de su hoja General,
     con Presupuestado (suma de todos sus presupuestos por rubro) · Gastado · Saldo · % consumido, y la fila Total
     (RN-REN-09, RN-REN-19, RN-REN-20, RN-REN-27).
  2. **Dado** los renglones, **entonces** suman los de la obra de gastos no anulados, con su signo, sin filtrar por
     origen; los sin obra, de otra obra o anulados no (RN-REN-01…05).
  3. **Dado** EPP, **entonces** suma en la fila INSUMOS ("INSUMOS · incluye EPP, como en su hoja General") (RN-REN-14).
  4. **Dado** una obra solo total o mixta, **entonces** se ve la fila "Sin desglose por rubro" y, en solo total, se
     compara el total contra lo gastado (RN-REN-10, RN-REN-11, resuelta DU-08); sin presupuesto cargado, lo gastado igual
     y el aviso de RN-REN-07.
  5. **Dado** "Últimos gastos imputados a esta obra", **entonces** son los 10 renglones más recientes por fecha efectiva
     con Fecha · Documento · Proveedor · Concepto · Rubro · Importe, con las etiquetas de documento comunes (RN-REN-28,
     RN-REN-29, **DU-R07 ✅**).
  6. **Dado** que alguien carga, corrige o anula un gasto, **entonces** quien lo hizo ve el cambio al instante y los demás
     en ≤ 15 s ("Actualizada 10:42") (RN-REN-59, RN-REN-60).
  7. **Dado** la obra de referencia, **entonces** cada número da lo de `rendicion.md` §9.3 al centavo (criterio global de
     la semana, §7.1).
  8. **Dado** Ingeniería (`rendicion:ver (propias)`) y una obra de otro responsable, **entonces** 404 "No encontramos esta
     obra." (RN-REN-55).
- **Reglas:** rendicion.md RN-REN-01…14, 19, 20, 26…29, 55, 57, 59…61 · §4.3.
- **Permisos:** `rendicion:ver` (inicial: E·T administración y Compras; V·T Dirección, Gerencia de Operaciones,
  Jefatura de Proyectos; V·P Ingeniería).
- **Dependencias:** HU-086, HU-061, HU-018 (W-13).
- **DoR:** ✅ listo.

#### HU-075 · Rubros y obra pasados en rojo
- **F-50 · W-11 · W-36 · W-44 · W-12**
- **Como** gerencia **quiero** ver en rojo el rubro que gastó más de lo presupuestado **para** actuar a tiempo.
- **Criterios de aceptación**
  1. **Dado** un rubro con gastado mayor que lo presupuestado (estricto, al centavo), **entonces** la fila, el saldo
     ("−28.000"), el % y la barra se ven en rojo; 100 % justo no es pasado (RN-REN-21).
  2. **Dado** un rubro con gasto y sin presupuesto en una obra con presupuesto por rubro, **entonces** es pasado, con %
     "—" y "sin presupuesto" (RN-REN-21).
  3. **Dado** una obra mixta, **entonces** cada rubro se marca contra lo desglosado, con la leyenda "1.000.000 de esta obra
     no tienen desglose por rubro…"; en una obra solo total no se marca ningún rubro (RN-REN-11, **DU-R02 ✅**).
  4. **Dado** que el gastado de la obra supera su presupuestado total, **entonces** el total de W-11, la fila de W-12 y
     el % de W-05 se ven en rojo (RN-REN-22).
- **Reglas:** rendicion.md RN-REN-11, 21, 22 · N10…N12.
- **Permisos:** `rendicion:ver`.
- **Dependencias:** HU-074.
- **DoR:** ✅ listo.

#### HU-076 · Monto de la obra y ganancia
- **F-54 · W-11 · W-44**
- **Como** gerencia y administración **quiero** ver el monto de la obra, la ganancia prevista y la ganancia al día
  **para** compararlo con mi hoja General ("Cobrado · Ganancia · %").
- **Criterios de aceptación**
  1. **Dado** `monto_obra:ver` para esa obra, **entonces** debajo del total se ve "Monto de la obra: 16.300.000 · Costo
     presupuestado: 12.150.000 · Ganancia prevista: 4.150.000 (25 %)" y "Gastado hasta hoy: 6.727.161,68 · Ganancia al
     día: 9.572.838,32 (59 %)"; el % es sobre el monto (RN-REN-23, RN-REN-25, **DU-R01 ✅**).
  2. **Dado** alguien sin `monto_obra:ver` (Ingeniería), **entonces** el JSON **no tiene** la clave `monto` (RN-REN-23,
     RN-REN-56).
  3. **Dado** un presupuesto sin monto o ninguno, **entonces** se suma lo que hay y se avisa, o "Todavía no se cargó el
     monto de la obra."; el monto se carga en W-13, no acá (RN-REN-24).
  4. **Dado** una ganancia negativa, **entonces** se ve en rojo con signo (RN-REN-25).
- **Reglas:** rendicion.md RN-REN-23…25, 46, 56 · N13…N15.
- **Permisos:** `monto_obra:ver` (inicial: V·T Dirección y Gerencia de Operaciones; E·T Gerencia y Jefatura de
  Administración).
- **Dependencias:** HU-074, HU-018 (monto en W-13).
- **DoR:** ✅ listo.

#### HU-077 · Detalle de un rubro
- **F-51 · W-36**
- **Como** gerencia **quiero** tocar un rubro y ver todos los renglones que lo componen **para** entender en qué se fue.
- **Criterios de aceptación**
  1. **Dado** un rubro, **entonces** W-36 muestra "MATERIALES · Presupuestado 5.000.000 · Gastado 2.701.827,63 (54 %) ·
     Saldo 2.298.172,37" y todos sus renglones (sin páginas) con Fecha · Documento · Proveedor · Concepto · Cantidad ·
     Precio unitario · Total; el pie es igual al gastado de W-11 (RN-REN-30, RN-REN-31).
  2. **Dado** el orden, **entonces** se ordena por fecha (por defecto la más nueva primero), proveedor o total (RN-REN-31).
  3. **Dado** INSUMOS, **entonces** lista también los de EPP con la marca "EPP" y "de los cuales EPP: 87.000"; el detalle de
     EPP solo responde 404 (RN-REN-15, **DU-R07 ✅**).
  4. **Dado** `gastos:ver`, **entonces** cada renglón abre su gasto (W-34); sin ese permiso, la fila no es un vínculo
     (RN-REN-32, RN-REN-57).
- **Reglas:** rendicion.md RN-REN-15, 30…32.
- **Permisos:** `rendicion:ver`.
- **Dependencias:** HU-074.
- **DoR:** ✅ listo.

#### HU-078 · Gastos por proveedor de la obra
- **F-52 · W-37**
- **Como** gerencia **quiero** ver lo gastado en la obra por proveedor y por concepto **para** compararlo con mis tablas
  dinámicas.
- **Criterios de aceptación**
  1. **Dado** "Por proveedor", **entonces** cada proveedor con su total y la cantidad de facturas, y debajo sus conceptos
     con cantidad y total (RN-REN-33, RN-REN-34).
  2. **Dado** los renglones del proveedor genérico, **entonces** van en el grupo "Sin documento", siempre al final, con
     "—" en Facturas; un retiro con remito no cuenta como factura (RN-REN-34, RN-REN-35).
  3. **Dado** los filtros Rubro y Período (toda la obra · este mes · el mes anterior · desde–hasta, por fecha efectiva),
     **entonces** el pie suma todo lo filtrado ("6 grupos · 6.727.161,68 · 4 comprobantes") (RN-REN-36, RN-REN-37,
     **DU-R07 ✅**).
- **Reglas:** rendicion.md RN-REN-33…37.
- **Permisos:** `rendicion:ver`.
- **Dependencias:** HU-074.
- **DoR:** ✅ listo.

#### HU-079 · Pestaña Rendición de la ficha y rendición en el celular
- **F-17 (pestaña Rendición) · F-13 · W-07 · W-44 · W-47**
- **Como** gerencia **quiero** ver la rendición desde la ficha de la obra y desde el celular **para** tenerla a mano en
  la obra.
- **Criterios de aceptación**
  1. **Dado** `rendicion:ver` para esa obra, **entonces** la ficha muestra la pestaña **Rendición** (W-11) y la cabecera
     "Rendición: 55 % gastado"; desaparece "Disponible en la próxima entrega"; sin permiso, la pestaña no aparece
     (RN-REN-26, RN-REN-43).
  2. **Dado** el celular (390 px), **entonces** W-44 muestra el total y una tarjeta por rubro, los más consumidos arriba,
     con los mismos números que W-11 y "Se actualiza sola · solo lectura"; sin desplazamiento horizontal (RN-REN-44,
     RN-REN-45).
  3. **Dado** `monto_obra:ver`, **entonces** el bloque de monto y ganancia aparece al final en el celular (RN-REN-46).
- **Reglas:** rendicion.md RN-REN-26, 43…46.
- **Permisos:** `rendicion:ver`.
- **Dependencias:** HU-074, HU-014 (ficha), HU-053 (obras en el celular).
- **DoR:** ✅ listo.

### Épica X · Todas las rendiciones y % gastado

#### HU-080 · Todas las rendiciones en curso
- **F-53 · W-12**
- **Como** dirección y gerencias **quiero** ver todas las obras en ejecución con su % consumido **para** saber cuáles
  mirar primero.
- **Criterios de aceptación**
  1. **Dado** "Rendiciones" en la barra, **entonces** W-12 lista las obras En ejecución que la persona puede ver con Obra ·
     Cliente · Presupuestado · Gastado · % consumido · Rubros pasados, de mayor a menor %, las sin presupuesto al final
     (RN-REN-38, RN-REN-39).
  2. **Dado** la fila "Todas las obras en ejecución", **entonces** suma las filas que se ven (Σ presupuestado, Σ gastado,
     % = Σ gastado ÷ Σ presupuestado) (RN-REN-40).
  3. **Dado** Ingeniería con alcance propias, **entonces** ve solo sus obras, también en el total (RN-REN-55).
  4. **Dado** 15 obras, **entonces** se arma con una consulta por agregado, no una por obra (RN-REN-41).
- **Reglas:** rendicion.md RN-REN-38…41, 55.
- **Permisos:** `rendicion:ver`.
- **Dependencias:** HU-074.
- **DoR:** ✅ listo.

#### HU-081 · "% del presupuesto gastado" en el listado de obras
- **F-15 (% gastado) · W-05 · W-47**
- **Como** gerencia **quiero** ver en el listado de obras cuánto se gastó de cada una **para** detectar la que se pasó.
- **Criterios de aceptación**
  1. **Dado** Rendición implementando `AvanceGastoDeObra` (reemplaza a `AvanceGastoDeObraNulo`), **entonces** la columna
     "% del presupuesto gastado" muestra el % de la rendición, en rojo si se pasó, y "—" sin presupuesto (RN-REN-42).
  2. **Dado** alguien sin `rendicion:ver` para esa obra, **entonces** ve "—" (RN-OBR-04).
  3. **Dado** una página del listado, **entonces** una consulta por agregado para toda la página; si el puerto falla, el
     listado no se cae (RN-REN-42, rendicion.md §6).
- **Reglas:** rendicion.md RN-REN-42 · obras.md RN-OBR-04, 07.
- **Permisos:** `obras:ver` + `rendicion:ver` para ver el número.
- **Dependencias:** HU-074, HU-013.
- **DoR:** ✅ listo.

### Épica Y · Control de materiales por cantidad

#### HU-082 · Lista de materiales del presupuesto
- **F-66 · W-48**
- **Como** administración **quiero** cargar una vez la lista de materiales presupuestados de la obra **para** controlar
  por cantidad lo que se lleva.
- **Criterios de aceptación**
  1. **Dado** `control_cantidad:editar`, **cuando** agrega Material · Unidad · Cantidad presupuestada · Precio unitario
     (opcional) · Presupuesto (si hay más de uno), **entonces** queda en la lista (RN-REN-47).
  2. **Dado** un nombre que ya está en la obra (sin distinguir mayúsculas, tildes ni espacios dobles), **entonces** 422 "Ya
     está en la lista: Cemento x 50 kg." (§4.2).
  3. **Dado** una corrección, **entonces** queda quién, cuándo y antes → después en `actividades`; dos a la vez: 409
     (RN-REN-47, RN-REN-61).
  4. **Dado** quitar un material que algún renglón usa (aunque esté anulado), **entonces** 422 "Cemento x 50 kg ya tiene 2
     retiros o pedidos: no se puede quitar. Corregí la cantidad presupuestada." (RN-REN-53).
  5. **Dado** un fuera de presupuesto al que se le completa la cantidad presupuestada, **entonces** pasa a la lista
     (RN-REN-49).
- **Reglas:** rendicion.md RN-REN-47, 49, 53, 61 · §4.2.
- **Permisos:** `control_cantidad:editar` (inicial: E·T administración y Compras); ver `control_cantidad:ver`.
- **Dependencias:** HU-086.
- **DoR:** ✅ listo.

#### HU-083 · Retirado por fecha, resta llevar y fuera de presupuesto
- **F-66 · W-48**
- **Como** administración y operaciones **quiero** ver por material lo presupuestado, lo retirado por fecha y lo que
  resta llevar **para** no pedir de más ni quedarme corto.
- **Criterios de aceptación**
  1. **Dado** la lista, **entonces** las columnas son Material · Unidad · Presup. · las 6 fechas más recientes con
     movimientos (las anteriores juntas en "Hasta {fecha}") · Total retirado · Resta llevar · Precio unit. · Total
     (RN-REN-50, **DU-R06 ✅**).
  2. **Dado** los retiros, renglones de factura asociados y devoluciones de un material, **entonces** el retirado es la
     suma de sus cantidades (las devoluciones restan) y "Resta llevar" = presupuestado − retirado, en rojo si es negativo
     (RN-REN-50, N16…N18).
  3. **Dado** el total de la lista, **entonces** debajo "Gastado en MATERIALES en la rendición: X" y, si difiere,
     "Renglones de MATERIALES sin material asociado: Y" con acceso a W-36 (RN-REN-51).
  4. **Dado** "Fuera de presupuesto", **entonces** una fila por renglón con Material · Unidad · Fecha · Cantidad · Total ·
     Rubro (RN-REN-52).
  5. **Dado** el celular, **entonces** tarjetas por material ("Cemento x 50 kg · 96 de 150 bolsas · resta 54 · 945.648"),
     solo lectura (RN-REN-54).
  6. **Dado** la obra de referencia, **entonces** la tabla da lo de `rendicion.md` §9.4 (W-48).
- **Reglas:** rendicion.md RN-REN-50…54 · N16…N19.
- **Permisos:** `control_cantidad:ver` (V·P Ingeniería: sus obras).
- **Dependencias:** HU-082, HU-068.
- **DoR:** ✅ listo.

### Épica Z · Lo que se completa de semanas 1 y 2, contratos y encendido

#### HU-084 · Gastos del proveedor, por obra
- **F-57 · W-14 "Gastado por obra"** (completa lo que la semana 1 dejó con el puerto nulo)
- **Como** compras y dirección **quiero** ver en la ficha del proveedor cuánto se le imputó en cada obra **para** saber
  con quién gastamos qué, sin armar una tabla.
- **Criterios de aceptación**
  1. **Dado** Gastos implementando `GastadoPorProveedor` (reemplaza a `GastadoPorProveedorNulo`), **entonces** "Gastado por
     obra" muestra por obra la cantidad de renglones y el importe de gastos no anulados, con las devoluciones restando,
     "(sin obra)" al final y el total (RN-GAS-43).
  2. **Dado** alcance "solo sus obras", **entonces** solo sus obras y sin la fila "(sin obra)" (RN-GAS-43, RN-PRV-16,
     **DU-G06 ✅**).
  3. **Dado** una ficha con muchos gastos, **entonces** una consulta agrupada, no una por obra (RN-GAS-43).
- **Reglas:** gastos.md RN-GAS-43 · proveedores.md RN-PRV-15…17.
- **Permisos:** `proveedores:ver` y `gastos:ver`.
- **Dependencias:** HU-061, HU-020.
- **DoR:** ✅ listo.

#### HU-085 · Buscador general: gastos por número de factura
- **F-03 (gastos) · W-18**
- **Como** administración **quiero** encontrar una factura o un remito por su número desde el buscador **para** no
  recorrer listados.
- **Criterios de aceptación**
  1. **Dado** 3 o más dígitos, **entonces** el grupo **Gastos** encuentra facturas y remitos por número ignorando ceros a la
     izquierda, guiones y espacios ("12345" encuentra 0003-00012345), con "Factura 0003-00012345 · Corralón Centro ·
     17-oct · 905.600"; los anulados con "(anulada)" (RN-GAS-44, **DU-G07 ✅**).
  2. **Dado** el concepto de un renglón o el nombre de una obra, **entonces** el grupo Gastos no lo busca (lo de una obra
     se ve por Obras → Rendición) (RN-GAS-44).
  3. **Dado** alguien sin `gastos:ver`, **entonces** el grupo no viaja; con alcance propias, solo sus gastos y el importe
     de sus renglones (RN-GAS-44, RN-GAS-48).
- **Reglas:** gastos.md RN-GAS-44 · §12.3.
- **Permisos:** `gastos:ver`.
- **Dependencias:** HU-061, HU-022, HU-057.
- **DoR:** ✅ listo.

#### HU-086 · Tablas y contratos entre Gastos, Rendición y Obras
- **— (transversal) · ADR-003 · ADR-007 · gastos.md §12.4 y §12.5 · rendicion.md §12.4 y §16 · modelo-datos.md**
- **Como** el equipo **queremos** las tablas y los contratos de la semana antes de construir las pantallas **para** que
  Gastos y Rendición se hablen por un solo contrato, sin usar uno la `Infrastructure` del otro.
- **Criterios de aceptación**
  1. **Migraciones:** **dado** `modelo-datos.md` (ajustes del 24-nov), **entonces** existen `materiales` (con `nombre_clave`
     único por obra, CHECK de fuera de presupuesto, `created_by`/`updated_by`, timestamps), `gastos` (con `numero_vigente`
     único con el proveedor, `updated_by`, timestamps) y `gasto_renglones` (con `fecha`, índice `(obra_id, fecha)`),
     reversibles.
  2. **`Gastos\Contracts\GastadoDeObras`:** **dado** la firma de `rendicion.md` §12.4.1, **entonces** Gastos la implementa
     (`totalesPorRubro` en una consulta para varias obras, `renglonesDeObra` sin N+1, `contarRenglonesPorMaterial` con
     anulados) y cumple las garantías de `gastos.md` §12.5 (**DU-R08 ✅**).
  3. **Rendición expone** `MaterialesDeObra` (`opciones` con `precioPresupuestado`, `perteneceAObra`,
     `crearFueraDePresupuesto` dentro de la transacción de Gastos) y `CatalogoDeRubros` (con `cargaPorPeriodo`).
  4. **Obras amplía** `PresupuestadoDeObra` con los presupuestos y su `modoCarga`, y suma `presupuestadoDeObras(ids)`,
     `montoDeObra(id)`, `obrasEnEjecucion()` y el puerto `MaterialesDePresupuesto::tieneMateriales` (no se quita un
     adicional con materiales, RN-OBR-24).
  5. **Eventos** `GastoImputado`, `GastoCorregido` (antes ∪ después), `GastoAnulado` con `{gastoId, obraIds}` después del
     commit invalidan `rendicion:obra:{id}`; también `PresupuestoMontosCambiados` (RN-REN-60).
  6. **Test de arquitectura:** ningún módulo importa la `Infrastructure` de otro.
- **Reglas:** ADR-003 · ADR-007 · gastos.md §12.4, §12.5 · rendicion.md §12.4, §16.
- **Permisos:** —
- **Dependencias:** —
- **DoR:** ✅ listo.

#### HU-087 · Encender la semana 3 en la barra y preparar la prueba del cliente
- **F-02 · F-13 · W-17 · W-41** (DU-A12: cada entrega enciende lo suyo)
- **Como** la empresa **queremos** que Gastos y Rendiciones aparezcan en la barra y que el entorno de prueba tenga lo
  necesario **para** comparar con el Excel desde el primer día.
- **Criterios de aceptación**
  1. **Dado** la configuración de módulos entregados con `gastos` y `rendicion`, **entonces** la barra muestra "Gastos"
     (W-33) y "Rendiciones" (W-12) según la tabla, con "(ver)" si solo tiene Ver, y en el celular "Más → Gastos" y "Más →
     Rendiciones" (RN-GAS-01, rendicion.md §2).
  2. **Dado** el entorno de prueba, **entonces** corre el cache de la rendición (Redis) y el polling de 15 s; el remito
     sube desde el celular (condición de B-04).
  3. **Dado** las notas de entrega de la semana 3 para el cliente, **entonces** dicen en sus palabras qué probar y traen la
     guía de comparación con su Excel (`rendicion.md` §13), incluido que se carga el neto si el comprobante discrimina
     IVA (DU-G04) y que una nota de crédito se carga como devolución o corrigiendo la factura (DU-G05).
  4. **Dado** el caso de referencia, **entonces** `RendicionCasoDeReferenciaTest` y su E2E están en verde contra la base de
     tests antes de abrir el entorno al cliente (§7.1).
- **Reglas:** identidad.md §6.4 · ADR-005 · ADR-007.
- **Permisos:** —
- **Dependencias:** las historias "sí o sí" de §7.1.
- **DoR:** ✅ listo.

**Control de completitud de la semana 3:** las **17 F-XX que arrancan** (F-39…F-47, F-49…F-54, F-57, F-66) tienen al
menos una historia, y las **repartidas** que se completan acá también: F-03 (HU-085), F-14 en gastos (HU-072), F-15 %
gastado (HU-081), F-17 pestaña Rendición (HU-079), F-48 alimenta la rendición (HU-074) y F-57 (HU-084). Además: F-02 y
F-13 (HU-079, HU-087). Pantallas de la semana: W-05 (columna), W-07 (pestaña), W-10, W-11, W-12, W-13 (lectura), W-14
(sección), W-18 (grupo), W-33…W-38, W-44, W-46, W-47, W-48.

Dudas de esta semana: DU-08, DU-17, DU-18 y DU-19, **resueltas el 12-nov**; **DU-G01…DU-G11 y DU-R01…DU-R09, resueltas el
24-nov** (va la propuesta; DU-G08 reemplazada por DU-R05, DU-R03 por DU-G05; DU-R09 es de la semana 4 y no bloquea).

### Semana 4 · Puesta en marcha (detalle)

> Bajada a historias el 25-nov-2026, con la semana 3 en prueba del cliente. Contrato: cap. 12 (F-62 "NexoSmart con
> administración", F-63), cap. 9 (lista cerrada de rubros), cap. 13 (criterio de aceptación n.º 4) y cap. 14 (semana 4:
> "Arrancan a usarlo; la minuta y las rendiciones en Excel quedan de consulta"); sin maqueta propia (F-62 y F-63 tienen
> pantalla "—"); usa W-09, W-42, W-11, W-33, W-34, W-36, W-48 y W-20, que ya existen. ADR-003, ADR-007, ADR-008, ADR-010,
> ADR-011, ADR-014 y ADR-015. Las reglas finas están en [`especificacion/importacion.md`](especificacion/importacion.md)
> (RN-IMP-NN). Decisiones que van tal cual: DU-20, DU-21, DU-R09, DU-P10, DU-C01 ("tarea" en todo lo visible).
> **Método:** comandos que corre NexoSmart en el servidor, con plantillas que completa administración, **ensayo** que no
> guarda nada y deja un informe, y confirmación **solo** del archivo ensayado y aprobado (`importacion.md` §4).

**15 historias** (HU-088…HU-102) en 5 épicas, **todas ✅ en DoR**: las dudas **DU-I01…DU-I09** se resolvieron el
25-nov-2026 (va la propuesta).

| Épica | Historias | Dudas que aplican (resueltas) |
|---|---|---|
| AA · Contratos, plantillas, ensayo y confirmación | HU-088…HU-091 | — |
| AB · Equivalencias de rubros y personas | HU-092…HU-093 | — |
| AC · Obras en ejecución con su gastado | HU-094…HU-096 | I02, I06, I07, I09 |
| AD · Tareas abiertas y documentos de la minuta | HU-097…HU-099 | I03, I05, I08, I09 |
| AE · Arranque y acompañamiento | HU-100…HU-102 | I01, I04, I05 |

> **Orden de construcción sugerido:** AA (HU-088 contratos y tablas → HU-089 → HU-090 → HU-091) → AB → AC (HU-094 → HU-095
> → HU-096) → AD (HU-097 → HU-098 → HU-099) → AE. Sin los contratos de carga inicial de cada módulo (HU-088) Importacion
> tendría que escribir tablas ajenas (ADR-003), y sin ensayo (HU-090) administración no puede revisar nada antes de que se
> guarde (DU-21).

### La prueba de la semana 4: el arranque

**Qué prueban (roadmap firmado):** "Arrancan a usarlo; la minuta y las rendiciones en Excel quedan de consulta." No es una
prueba en paralelo: es el **arranque**. Antes de la apertura, administración y los sectores completan las plantillas con los
faltantes del informe; el día de la apertura cada persona entra, ve sus tareas abiertas en Mis tareas, y cada gerencia abre
una obra en ejecución y ve su rendición al día del corte.

**Tiene que estar sí o sí** (sin esto no se puede arrancar o se arranca con números que no son los suyos):

| Lo que tienen hoy | Historias que lo sostienen |
|---|---|
| Las tareas abiertas de la minuta, con quién las tiene y para cuándo | HU-093, HU-097 |
| Cada obra en ejecución con sus datos, sus presupuestos y su presupuesto por rubro | HU-094 |
| La hoja "General" de cada rendición al día: gastado por rubro | HU-092, HU-095 |
| Que nada se guarde sin que administración lo revise y lo apruebe | HU-090, HU-091, HU-092 |
| Usuarios por sector y la base de producción lista | HU-100 |
| Comparar lo cargado con su minuta y sus rendiciones | HU-101 |

Se entregan en la misma semana pero **el arranque no depende de ellas** (si alguna se atrasa, se termina en la primera
semana de acompañamiento; se le dice a Juani el miércoles): HU-096 (retirado en W-48: mientras tanto, "Resta llevar" se
mira en su planilla de retiro), HU-098 (documentos de la minuta: siguen en la minuta), HU-099 (la marca en pantalla; la
tarea entra igual con `carga_inicial`), HU-102 (guía por sector; la capacitación se da igual).

**Criterio de aceptación global de la semana 4:**
1. **El caso de referencia** (`importacion.md` §13, datos ficticios): **dado** el lote de obras (3 obras: una completa con
   "INSUMO" y "LOGÍSTICA" aprobados, una sin el mail del comprador y con "VARIOS" sin equivalencia, una Confirmada) y el de
   tareas (8 filas: 3 tareas y 1 documento que entran, 1 completada, 3 con faltantes), **cuando** se corre el ensayo,
   **entonces** la base no cambia y el informe dice exactamente lo de §13; **cuando** se confirma el mismo archivo dos veces,
   **entonces** no hay nada duplicado, no hay **ningún aviso ni mail**, y la rendición de 1-200-2026 da **al centavo**
   INSUMOS · incluye EPP 137.500,50 y total gastado 2.347.500,50, y W-48 "Hasta 30/11/2026 · Carga inicial" con Cemento 96
   (resta 54). Es `CargaInicialCasoDeReferenciaTest` (feature) y `carga-inicial.spec.ts` (E2E).
2. **El arranque real** (QC, `importacion.md` §12.3, conteos contra el origen): filas abiertas de la minuta = tareas
   importadas + filas con faltante con su motivo + filas que administración decidió dejar afuera, **sin diferencia sin
   explicar**; las obras en ejecución de su lista están todas o con su faltante; por obra y rubro, el gastado de W-11 = su
   hoja "General" al corte (**diferencia 0**); cada propietario entra y ve sus tareas en PC y en el celular.
3. **El cierre del acompañamiento** (criterio de aceptación n.º 4, `qa/plan-de-pruebas.md`): **dos semanas seguidas sin
   filas nuevas en la minuta**.

**Señales de que el arranque peligra** (se miran el miércoles de la semana 4): HU-088 no está en `main` el lunes; el
ensayo no corre con el caso de referencia el martes; el primer informe real a administración no salió el miércoles;
quedan dudas DU-I01 o DU-I02 sin decidir el miércoles (cambian dónde y con qué fecha se carga); faltan usuarios por sector
en producción el jueves.

### Épica AA · Contratos, plantillas, ensayo y confirmación

#### HU-088 · Contratos de carga inicial en cada módulo y registro de corridas
- **— (transversal) · F-62 · ADR-003 · importacion.md §11.3 y §11.4**
- **Como** el equipo **queremos** que cada módulo ofrezca su contrato de carga inicial y que Importacion registre sus
  corridas **para** importar por los casos de uso de cada módulo, con sus validaciones, sin escribir tablas ajenas.
- **Criterios de aceptación**
  1. **Dado** `importacion.md` §11.4, **entonces** existen `Obras\Contracts\CargaInicialDeObras`, `Pedidos\Contracts\CargaInicialDeTareas`,
     `Gastos\Contracts\CargaInicialDeGastos`, `MaterialesDeObra::agregarALaLista` (Rendicion) y la consulta de usuarios y
     sectores de Identidad, implementados en su módulo sobre sus casos de uso, recibiendo el actor por parámetro (RN-IMP-09, 10).
  2. **Dado** `CargaInicialDeTareas::crear`, **entonces** guarda `carga_inicial = true`, admite solicitante y fecha de pedido
     vacíos y **no** dispara `PedidoAsignado` (RN-IMP-34, 37).
  3. **Dado** `CargaInicialDeGastos::cargar`, **entonces** guarda origen `saldo_inicial`, proveedor genérico y fecha = corte,
     acepta renglones con precio 0 y material, y dispara `GastoImputado` después del commit (RN-IMP-23, 29).
  4. **Migraciones:** `importacion_corridas` e `importacion_filas` como §11.3, reversibles, **sin valores de celdas** (RN-IMP-13).
  5. **Test de arquitectura:** Importacion no importa la `Infrastructure` de otro módulo ni escribe sus tablas.
- **Reglas:** importacion.md RN-IMP-09, 10, 13 · §11.3, §11.4.
- **Permisos:** —
- **Dependencias:** HU-086 (contratos de la semana 3).
- **DoR:** ✅ listo.

#### HU-089 · Plantillas vacías y lectura tolerante
- **F-62 · — (comando `importacion:plantilla`)**
- **Como** administración **quiero** una plantilla con las columnas, las listas y la explicación de cada dato **para**
  completar la carga sin adivinar el formato.
- **Criterios de aceptación**
  1. **Dado** `importacion:plantilla obras` (o `tareas`), **entonces** deja en la carpeta de la importación el `.xlsx` con las
     hojas y columnas exactas de §5, listas desplegables (13 rubros, sectores activos, estados) y la hoja "Cómo completar"
     (importacion.md §5).
  2. **Dado** un archivo sin una columna obligatoria, **cuando** se corre, **entonces** no empieza: "Falta la columna 'Fecha de
     corte' en la hoja Obras." (RN-IMP-11).
  3. **Dado** fechas como fecha de Excel o "30/11/2026" e importes como número o "1.250.000,50", **entonces** se leen igual;
     "31/11/2026", un importe con 3 decimales o una cantidad con 4 son faltantes, no se redondean (RN-IMP-14).
  4. **Dado** una ruta fuera de `IMPORTACION_DIR`, **entonces** "El archivo tiene que estar en la carpeta de la importación."
     (RN-IMP-12).
- **Reglas:** importacion.md RN-IMP-11, 12, 14 · §5 · §11.2.
- **Permisos:** — (servidor).
- **Dependencias:** HU-088.
- **DoR:** ✅ listo.

#### HU-090 · Ensayo que no guarda nada e informe de faltantes
- **F-62 · — (informe `.xlsx`)**
- **Como** administración **quiero** ver antes de que se guarde qué entra, qué falta y de dónde sale cada número **para**
  completar y aprobar con la planilla en la mano (DU-21).
- **Criterios de aceptación**
  1. **Dado** `importacion:obras {archivo}` sin `--confirmar`, **entonces** valida todo por los mismos caminos que la carga
     real, **no guarda nada** (ni datos, ni actividades, ni eventos, ni avisos) y termina con "Ensayo: no se guardó nada. …
     Informe: {ruta}" (RN-IMP-03).
  2. **Dado** el informe, **entonces** tiene las hojas Resumen, Faltantes, Para completar por sector, Avisos, Rubros por revisar,
     Personas por revisar y Control por rubro, con las columnas de §5.8 y los textos de §8 (importacion.md §5.8, §8).
  3. **Dado** una obra con varios faltantes, **entonces** el informe los lista **todos juntos** (RN-IMP-19).
  4. **Dado** cualquier corrida, **entonces** la consola y los logs tienen solo conteos, huella corta y ruta; un error inesperado
     se registra con hoja y fila, sin el valor de ninguna celda (RN-IMP-13).
  5. **Dado** una corrida en curso, **cuando** se lanza otra, **entonces** "Hay otra carga en curso. Esperá a que termine."
     (RN-IMP-06).
- **Reglas:** importacion.md RN-IMP-03, 06, 13, 19 · §5.8 · §8.
- **Permisos:** — (servidor).
- **Dependencias:** HU-089.
- **DoR:** ✅ listo.

#### HU-091 · Confirmar solo un ensayo aprobado, por obra y sin duplicar
- **F-62 · — (comandos con `--confirmar --aprobo`)**
- **Como** administración **quiero** que se guarde exactamente lo que aprobé **para** que ningún número entre sin mi revisión.
- **Criterios de aceptación**
  1. **Dado** `--confirmar` sin un ensayo previo del mismo archivo (misma huella SHA-256), **entonces** "No se guardó nada:
     primero corré el ensayo de este archivo." (RN-IMP-04, 05).
  2. **Dado** `--aprobo` de alguien inactivo o sin `rendicion`, `obras` y `estado_obra` = editar (lote obras) o sin
     `pedidos = editar` (lote tareas), **entonces** "No se guardó nada: … no puede aprobar la carga de obras." (RN-IMP-04).
  3. **Dado** una confirmación, **entonces** todo lo de una obra se guarda en una transacción (cada tarea, en la suya); si una
     falla, no queda nada de ella, la corrida sigue y termina "con errores" (RN-IMP-07).
  4. **Dado** el mismo archivo confirmado dos veces, **entonces** la segunda guarda 0 y cuenta todo como "Ya importada"
     (RN-IMP-08).
  5. **Dado** lo guardado, **entonces** el "cargó"/"creó" es quien aprobó y la actividad lleva la marca de carga inicial
     (RN-IMP-10, 39).
- **Reglas:** importacion.md RN-IMP-04, 05, 07, 08, 10, 39.
- **Permisos:** quien aprueba, según RN-IMP-04 (inicial: Gerencia y Jefatura de Administración para obras).
- **Dependencias:** HU-090.
- **DoR:** ✅ listo.

### Épica AB · Equivalencias de rubros y personas

#### HU-092 · Equivalencias de rubros que revisa y aprueba administración
- **F-62 · cap. 9 · DU-21 · — (hoja Rubros)**
- **Como** administración **quiero** decidir yo a qué rubro va cada forma mal escrita de mis planillas **para** que "INSUMO"
  sume en INSUMOS porque lo aprobé, no porque el sistema lo adivinó.
- **Criterios de aceptación**
  1. **Dado** "INSUMOS" o "insumos " en la hoja Gastado, **entonces** va directo a INSUMOS; **dado** "INSUMO", "LOGÍSTICA" con
     tilde o "VARIOS" sin fila en la hoja Rubros con Aprobado = Sí, **entonces** la obra entera no entra y el faltante dice "El
     rubro 'INSUMO' no tiene equivalencia aprobada en la hoja Rubros." (RN-IMP-16, 19).
  2. **Dado** una equivalencia con Aprobado = No o un rubro del sistema que no es uno de los 13, **entonces** faltante (RN-IMP-16).
  3. **Dado** una forma sin equivalencia, **entonces** "Rubros por revisar" la trae con un rubro sugerido que **no se aplica**
     (RN-IMP-16).
  4. **Dado** una carga confirmada, **entonces** el historial del gasto dice "Equivalencias aprobadas por Carla: INSUMO →
     INSUMOS, LOGÍSTICA → LOGISTICA." (RN-IMP-17).
- **Reglas:** importacion.md RN-IMP-16, 17, 19.
- **Permisos:** aprueba administración (columna Aprobó).
- **Dependencias:** HU-090.
- **DoR:** ✅ listo.

#### HU-093 · Personas de la minuta a usuarios del sistema
- **F-62 · DU-20 · DU-P10 · — (hoja Personas)**
- **Como** cada sector **quiero** que las tareas de la minuta queden a nombre de quien las tiene **para** encontrarlas en Mis
  tareas el primer día.
- **Criterios de aceptación**
  1. **Dado** el mail exacto de un usuario en Propietario, **entonces** es esa persona; **dado** un nombre, se busca en la hoja
     Personas (RN-IMP-15).
  2. **Dado** un propietario sin fila en Personas, con un mail que no es de un usuario, inactivo o de un sector sin Tareas ≥ Ver,
     **entonces** faltante de esa tarea ("No encontramos a 'Cari' en la hoja Personas.", "Carla está inactiva: elegí a quién se
     la pasás.") (RN-IMP-15, RN-PED-03).
  3. **Dado** un solicitante que no se encuentra, **entonces** la tarea entra con "Solicitante: —" y el informe lo avisa
     (RN-IMP-15, DU-20).
  4. **Dado** "Personas por revisar", **entonces** cada nombre sin fila trae un usuario sugerido que no se aplica (§5.8).
- **Reglas:** importacion.md RN-IMP-15 · pedidos.md RN-PED-03, 61.
- **Permisos:** —
- **Dependencias:** HU-090, HU-100 (usuarios cargados en producción).
- **DoR:** ✅ listo.

### Épica AC · Obras en ejecución con su gastado

#### HU-094 · Obras en ejecución con sus datos, presupuestos y presupuesto por rubro
- **F-62 · F-16 · F-18 · F-48 · — (hojas Obras y Presupuestos)**
- **Como** administración **quiero** que las obras en ejecución entren completas, con sus presupuestos y su presupuesto por
  rubro **para** que la rendición tenga contra qué comparar desde el primer día.
- **Criterios de aceptación**
  1. **Dado** una obra con todos los obligatorios de `obras.md` §4.1 (oferta técnica como link), **cuando** se confirma,
     **entonces** nace Confirmada y pasa a En ejecución en la misma transacción, con su historial "(carga inicial)" y sin avisos
     (RN-IMP-18, 21, 39).
  2. **Dado** un faltante en la obra o en sus presupuestos, **entonces** no entra nada de esa obra (RN-IMP-19).
  3. **Dado** un presupuesto "Por rubro" con un Total, o "Solo total" con rubros, **entonces** faltante "Un presupuesto se carga
     por rubro o con el total, no las dos cosas." (RN-IMP-22, RN-OBR-31).
  4. **Dado** una obra sin presupuesto por rubro, **entonces** entra con el aviso "La obra no tiene presupuesto por rubro…"
     (RN-IMP-22, RN-OBR-34).
  5. **Dado** una obra cuyo N.º principal ya está en el sistema, **entonces** no se toca nada de ella y el informe lo avisa
     (RN-IMP-20).
  6. **Dado** Estado = Confirmada, **entonces** entra Confirmada (sin gastado obligatorio); **dado** cualquier otro estado,
     faltante (RN-IMP-18). ✅ resuelta  (obras Confirmadas y Terminadas).
- **Reglas:** importacion.md RN-IMP-18…22 · obras.md §4.1, RN-OBR-10…19, 29, 31…37.
- **Permisos:** quien aprueba (RN-IMP-04).
- **Dependencias:** HU-091.
- **DoR:** ✅ listo, salvo el criterio 6 (✅ resuelta : se construye con la propuesta).

#### HU-095 · Gastado acumulado por rubro como carga inicial en la rendición
- **F-62 · F-49 · W-11 · W-36 · W-33 · W-34**
- **Como** cualquier gerente **quiero** abrir una obra en ejecución y ver lo gastado hasta el corte, rubro por rubro, igual a
  mi hoja "General" **para** seguir la rendición en el sistema y dejar el Excel de consulta.
- **Criterios de aceptación**
  1. **Dado** la hoja Gastado de una obra, **cuando** se confirma, **entonces** queda **un** gasto "Carga inicial" con un renglón
     por rubro del sistema (las formas que van al mismo rubro, sumadas), cantidad 1, precio = importe, fecha = corte, proveedor
     genérico, concepto "Gastado hasta el {corte} según su rendición" (RN-IMP-23).
  2. **Dado** el caso de referencia, **entonces** W-11 de 1-200-2026 da INSUMOS · incluye EPP 137.500,50 y total
     2.347.500,50, al centavo (importacion.md §13, RN-REN-08).
  3. **Dado** "Total gastado según su planilla" distinto de la suma, **entonces** faltante con la diferencia (RN-IMP-24).
  4. **Dado** renglones no anulados de otro origen con fecha hasta el corte, **entonces** faltante por doble conteo; con fecha
     posterior, no (RN-IMP-25).
  5. **Dado** una fecha de corte por obra, posterior a hoy o vacía con gastado, **entonces** faltante (RN-IMP-23). ✅ resuelta 
     (una fecha por obra).
  6. **Dado** la carga inicial en W-34, **entonces** documento "Carga inicial", proveedor "—", **sin Editar** (`PATCH` 422 "La
     carga inicial no se corrige: anulala y volvé a importarla."); anulada, se puede volver a importar (RN-IMP-27, 41).
     ✅ resuelta .
- **Reglas:** importacion.md RN-IMP-23…27, 41, 42 · rendicion.md RN-REN-08, 29 · gastos.md RN-GAS-29, 40.
- **Permisos:** ver: la matriz (RN-IMP-43); anular: `gastos = editar`.
- **Dependencias:** HU-094, HU-092.
- **DoR:** ✅ listo, salvo los criterios 5 y 6 (✅ resuelta , DU-I09: se construyen con la propuesta).

#### HU-096 · Lista de materiales y retirado hasta el corte en el control por cantidad
- **F-62 · F-66 · W-48 · DU-R09**
- **Como** administración **quiero** que cada obra arranque con su lista de materiales y lo ya retirado **para** que "Resta
  llevar" dé bien desde el primer día sin contar la plata dos veces.
- **Criterios de aceptación**
  1. **Dado** la hoja Materiales, **entonces** la lista entra con las validaciones de W-48 (nombre no repetido, unidad,
     presupuesto si hay más de uno); un material que ya está no se duplica y se avisa si difiere (RN-IMP-28). ✅ resuelta 
     (la lista por la plantilla).
  2. **Dado** retirado > 0, **entonces** una carga inicial de retirado con un renglón por material: cantidad = retirado, precio
     0, total 0, rubro MATERIALES, el material, fecha = corte; **no suma** en la rendición (RN-IMP-29, DU-R09).
  3. **Dado** W-48, **entonces** primera columna "Hasta 30/11/2026 · Carga inicial", fuera de las 6 fechas recientes; Cemento
     96 de 150 → resta 54 (RN-IMP-30, importacion.md §13).
  4. **Dado** retirado mayor que lo presupuestado, **entonces** entra con aviso y "Resta llevar" en rojo (RN-IMP-31).
- **Reglas:** importacion.md RN-IMP-28…31 · rendicion.md RN-REN-47, 50, 53.
- **Permisos:** ver `control_cantidad:ver`.
- **Dependencias:** HU-094, HU-082, HU-083.
- **DoR:** ✅ listo, salvo el criterio 1 (✅ resuelta : se construye con la propuesta).

### Épica AD · Tareas abiertas y documentos de la minuta

#### HU-097 · Tareas abiertas de la minuta, marcadas "Carga inicial" y sin avisos
- **F-62 · F-04 · DU-20 · — (hoja Tareas)**
- **Como** cada persona **quiero** entrar el primer día y tener en Mis tareas lo que tenía abierto en la minuta **para** no
  volver a cargarlo ni ir a buscarlo al Excel.
- **Criterios de aceptación**
  1. **Dado** filas No iniciada, En curso o Bloqueada, **entonces** entran con `carga_inicial = true` y el estado de la minuta;
     las Completada no se importan y se cuentan en el Resumen; una sin estado es faltante (RN-IMP-32).
  2. **Dado** una fila sin fecha de entrega, sin propietario (ni "(en el sector)" con su sector), de una obra que no está en el
     sistema o está Terminada, o Bloqueada sin motivo, **entonces** no entra y queda en "Para completar por sector" (RN-IMP-33).
     ✅ resuelta  (lo que el sector no completa no entra).
  3. **Dado** una Bloqueada, **entonces** las Notas son su motivo; en las demás, las aclaraciones; "(en el sector)" + En curso
     es faltante (RN-IMP-34).
  4. **Dado** la prioridad de la minuta vacía o P3, **entonces** calculada si están las dos fechas, si no Normal; **dado** una
     distinta de la calculada, queda "(corregida a mano)" (RN-IMP-35).
  5. **Dado** la carga confirmada, **entonces** **0 avisos y 0 mails** de asignación; el recordatorio "vence mañana" sale por su
     fecha (RN-IMP-37). ✅ resuelta .
  6. **Dado** una tarea sin solicitante, **entonces** "Solicitante: —", la reabre cualquiera con Tareas = Editar y al completarla
     no hay aviso al solicitante ni a su superior (RN-PED-61, RN-AVI-15).
- **Reglas:** importacion.md RN-IMP-32…37 · pedidos.md §4.6, RN-PED-11…14, 61 · avisos.md RN-AVI-15, 16.
- **Permisos:** quien aprueba (RN-IMP-04); ver: la matriz.
- **Dependencias:** HU-093, HU-094.
- **DoR:** ✅ listo, salvo los criterios 2 y 5 (✅ resuelta , DU-I05: se construyen con la propuesta).

#### HU-098 · Documentos de la minuta a Documentos de la obra
- **F-62 · F-19 · W-20 · DU-20**
- **Como** administración **quiero** que los certificados, presupuestos y OC que hoy viven en la minuta queden en la obra
  **para** que la ficha arranque con su legajo de links y la lista de tareas sin ruido.
- **Criterios de aceptación**
  1. **Dado** una fila "Documento" de una obra en el sistema, con al menos un link, **entonces** entra a Documentos de la obra
     como Certificado (con su fecha) u Otro, con su nombre y sus links, sin importar el estado de la fila (RN-IMP-38). ✅ resuelta .
  2. **Dado** un documento sin link, sin fecha siendo Certificado, o "Sin obra", **entonces** faltante (RN-IMP-38).
  3. **Dado** la ficha (W-20), **entonces** el historial dice "Carla agregó Certificado N°1 (carga inicial)" (RN-IMP-39).
- **Reglas:** importacion.md RN-IMP-38, 39 · obras.md §4.5, RN-OBR-23.
- **Permisos:** quien aprueba; ver: `obras:ver`.
- **Dependencias:** HU-094.
- **DoR:** ✅ listo, salvo el criterio 1 (✅ resuelta : se construye con la propuesta).

#### HU-099 · La marca "Carga inicial" en la tarea, el recorrido y el gasto
- **F-62 · F-34 · W-09 · W-42 · W-34**
- **Como** cualquiera que abre una tarea o un gasto **quiero** saber que vino de la minuta o de la rendición en Excel **para**
  entender por qué no tiene solicitante, fecha de pedido o factura.
- **Criterios de aceptación**
  1. **Dado** una tarea con `carga_inicial`, **entonces** el detalle (W-09 y W-42) muestra "Carga inicial" al lado del estado,
     con la ayuda "Vino de la minuta al arrancar el sistema."; en los listados se ve como cualquier tarea (RN-IMP-40).
  2. **Dado** su recorrido, **entonces** la primera línea es "30/11/2026 18:10 · Carga inicial desde la minuta (aprobó Carla)"
     (RN-IMP-39).
  3. **Dado** una carga inicial de gasto en W-34, **entonces** título "Carga inicial · {obra} · gastado hasta el {corte}" (o
     "· retirado hasta el {corte}") y solo "Anular" (RN-IMP-41). ✅ resuelta .
- **Reglas:** importacion.md RN-IMP-39…41.
- **Permisos:** los de ver la tarea o el gasto.
- **Dependencias:** HU-097, HU-095.
- **DoR:** ✅ listo, salvo el criterio 3 (✅ resuelta : se construye con la propuesta).

### Épica AE · Arranque y acompañamiento

#### HU-100 · Producción lista: base limpia, usuarios por sector y respaldo
- **F-62 · F-58 · F-63 · ADR-015 · importacion.md §12**
- **Como** la empresa **queremos** que el día de la apertura cada persona tenga su usuario, su sector y su jefatura en
  producción **para** que la carga de tareas encuentre a sus propietarios y todos entren el primer día.
- **Criterios de aceptación**
  1. **Dado** producción con la versión de la semana 4, **entonces** migraciones al día, primer usuario creado con
     `identidad:crear-administrador`, las cuatro entregas encendidas en la barra y `/api/health` en 200 (RN-IMP-45).
     ✅ resuelta  (producción limpia).
  2. **Dado** la hoja Personas, **entonces** cada mail es de un usuario activo de producción con sector y jefatura, cargado por
     gerencia en W-39, y la tabla de permisos repite lo que hayan cambiado en las pruebas (RN-IMP-45).
  3. **Dado** cada `--confirmar`, **entonces** antes hay un respaldo de la base; hasta la apertura, una carga mal hecha se deshace
     restaurándolo (RN-IMP-44).
  4. **Dado** la carga, **entonces** no se importan proveedores; se dan de alta con su primera factura (RN-IMP-46). ✅ resuelta .
  5. **Dado** la carpeta de la importación, **entonces** es privada del servidor y tiene fecha de borrado a los 30 días de la
     apertura (RN-IMP-12).
- **Reglas:** importacion.md RN-IMP-12, 44…46 · §12.1, §12.2 · identidad.md RN-IDE-09.
- **Permisos:** W-39: `usuarios:editar` (gerencias y Dirección).
- **Dependencias:** las historias de AA a AD en `main`.
- **DoR:** ✅ listo, salvo los criterios 1 y 4 (✅ resuelta , DU-I04: se construyen con la propuesta).

#### HU-101 · Carga real, conteos contra el origen y apertura
- **F-62 · F-63 · cap. 13 criterio n.º 4 · importacion.md §12.2 y §12.3**
- **Como** la empresa **queremos** confirmar la carga, compararla con la minuta y las rendiciones y recién ahí abrir el
  sistema **para** arrancar con los números de verdad.
- **Criterios de aceptación**
  1. **Dado** el último informe de ensayo de cada lote aprobado por mail por administración (con su huella), **cuando**
     NexoSmart confirma obras y después tareas, **entonces** los informes finales dicen lo mismo que los ensayos aprobados
     (RN-IMP-04, 05).
  2. **Dado** los conteos de §12.3, **entonces** tareas abiertas = importadas + con faltante + dejadas afuera, sin diferencia sin
     explicar; obras en ejecución todas o con su faltante; gastado por obra y rubro = su "General" al corte (diferencia 0)
     (importacion.md §12.3).
  3. **Dado** la apertura, **entonces** cada propietario entra y ve sus tareas en PC y celular, y la campana no tiene avisos de
     asignación de la carga (§12.3).
  4. **Dado** la apertura, **entonces** las notas de entrega de la semana 4 le dicen al cliente que la minuta y las rendiciones
     en Excel quedan de consulta y que lo posterior al corte se carga en el sistema (cap. 14, RN-IMP-26). ✅ resuelta  (si se
     arranca en otra base, cambian las notas).
- **Reglas:** importacion.md RN-IMP-04, 05, 26 · §12.2, §12.3.
- **Permisos:** —
- **Dependencias:** HU-100.
- **DoR:** ✅ listo, salvo el criterio 4 (✅ resuelta ).

#### HU-102 · Guía por sector y cierre del acompañamiento
- **F-63 · cap. 13 criterio n.º 4 · importacion.md §12.4** (entregable de documentación, no pantalla)
- **Como** cada sector **quiero** una página que me diga qué hago el primer día **para** arrancar usándolo, no estudiándolo.
- **Criterios de aceptación**
  1. **Dado** `docs/entregas/semana-4/`, **entonces** hay una guía de una página por sector con usuario ("Tu primer día": qué ves
     al entrar, cargar una tarea, tomarla y completarla; administración y compras: cargar una factura y leer la rendición), con
     capturas de datos ficticios y "tarea" en todo lo visible (DU-C01).
  2. **Dado** el acompañamiento, **entonces** se registra por semana la cantidad de filas nuevas en la minuta contra tareas
     nuevas en el sistema, y se cierra con **dos semanas seguidas sin filas nuevas** (plan de pruebas, criterio n.º 4).
  3. **Dado** la guía, **entonces** dice que las tareas que vinieron de la minuta no avisaron al importarse y que se ven en Mis
     tareas (RN-IMP-37). ✅ resuelta .
- **Reglas:** importacion.md §12.4, RN-IMP-37.
- **Permisos:** —
- **Dependencias:** HU-101.
- **DoR:** ✅ listo, salvo el criterio 3 (✅ resuelta ).

**Control de completitud de la semana 4:** las **2 F-XX que arrancan** tienen historia: F-62 (HU-088…HU-101) y F-63 (HU-100,
HU-101, HU-102: lo que el sistema necesita y la guía; la capacitación es servicio). Pantallas que se tocan (ya existen): W-09
y W-42 (marca), W-34 (sin Editar en la carga inicial), W-48 (columna "Hasta {corte} · Carga inicial"), W-39 (usuarios en
producción); W-11, W-20, W-33, W-36 muestran lo importado sin cambios.

Dudas de esta semana: DU-20 y DU-21, **resueltas el 12-nov**; DU-R09, **resuelta el 24-nov** (va la propuesta). Nuevas de
la especificación: **DU-I01…DU-I09**, abiertas (decide Juani).

---

## 8. Riesgo: la semana 3 es la más cargada

La semana 3 arranca **17 funciones** y completa otras 5 que vienen de semanas anteriores. Es la que
tiene más reglas de dinero (renglones repartidos, negativos, EPP → INSUMOS, solo total, horas y
fijos por período, monto y ganancia con permiso propio) y la que el cliente prueba contra su Excel,
donde cualquier diferencia se ve.

**24-nov-2026:** con las 27 historias escritas (§7) el riesgo sigue igual. Lo nuevo que pesa: el contrato entre
Gastos y Rendición (HU-086) va primero y lo usan casi todas las historias; las cargas desde la rendición tienen su propio
permiso (`rendicion:editar`, DU-R05) y la Policy de Gastos tiene que probarlo celda por celda.

**Señales de que el plazo peligra** (se miran el miércoles de la semana 3):
- HU-086 (tablas, `GastadoDeObras`, `MaterialesDeObra`, `CatalogoDeRubros`) no está en `main` el lunes.
- La carga de factura (W-10) con reparto por renglón no está en verde de punta a punta (HU-061).
- La rendición (W-11) no da el mismo número que la rendición ficticia de referencia, rubro por rubro
  (`RendicionCasoDeReferenciaTest`, §7.1).
- La carga única de retiros (DU-17, resuelta: una sola carga suma en W-48 y en la rendición) no
  está en verde (HU-068).

**Recorte candidato para proponerle a Juani** (no está decidido; nada sale de la Etapa 1, solo se
mueve de semana):

| Se mueve | De → a | Por qué este y no otro |
|---|---|---|
| F-66 Control de materiales por cantidad (W-48: HU-082, HU-083) | Semana 3 → semana 4 | Es una vista aparte de la rendición; la rendición por rubro (criterio de aceptación global n.º 1) no depende de ella. Con DU-17 resuelta, la carga de retiros es una sola y se puede mover sin rehacerla. **Lo que no se mueve:** la tabla `materiales` y `MaterialesDeObra` (HU-086), porque el retiro (HU-068) los usa; mientras no haya lista, lo retirado entra como "fuera de presupuesto" y, al cargar la lista en la semana 4, completarle la cantidad lo pasa a la lista (RN-REN-49): no se pierde ni se carga dos veces. |
| F-57 Gastos del proveedor (en la ficha del proveedor, W-14: HU-084) | Semana 3 → semana 4 | Es una consulta sobre datos que ya se cargan; no frena la carga ni la rendición. La sección sigue mostrando lo de la semana 1 (puerto nulo, "Todavía no hay gastos cargados de este proveedor."). |

Lo que **no** se propone mover, porque sin eso el cliente no puede hacer su prueba de la semana 3
("cargan las facturas de una obra en curso y la comparan con su rendición en Excel"): F-39 a F-47,
F-49 a F-51, F-53 y F-54 (las historias "sí o sí" de §7.1), y HU-086.

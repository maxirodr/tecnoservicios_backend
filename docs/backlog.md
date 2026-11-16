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
> Analista; decide Juani). Las semanas 3 y 4 siguen como épicas (§7).
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
13-nov). **Semana 2: lo que entra ahora**, con la historia que lo construye.

| F-XX | Semana 1 (entregada) | Semana 2 (entra ahora · HU) | Semana 3 |
|---|---|---|---|
| F-03 Buscador general | Obras (N.º de presupuesto, cliente, planta, nombre del trabajo) y proveedores (nombre) | Pedidos por texto · HU-057 | Gastos por número de factura |
| F-10 Mi perfil | Mis datos, mi sector, de quién dependo (~~cambiar la contraseña~~: no hay contraseña, ADR-015) | "Mis avisos" (qué avisos llegan además por mail) · HU-047 | — |
| F-14 Registro de quién hizo qué | En obras (alta, edición, cambio de estado, presupuestos) | En pedidos (edición y recorrido) · HU-031, HU-037 | En gastos |
| F-15 Listado de obras | Pestañas por estado, N.º de presupuesto, trabajo, cliente y planta, responsable | Columna "pedidos abiertos" y fila de Servicios generales · HU-055 (celular: HU-053) | Columna "% del presupuesto gastado" (en rojo si se pasó) |
| F-17 Ficha de la obra | Datos de la obra y pestaña Documentos y contactos | Pestaña Pedidos · HU-054 (celular: HU-053) | Pestaña Rendición |
| F-21 Cambiar el estado de la obra | Confirmada → En ejecución → Terminada, por quien corresponde, con registro | Al pasar a Terminada se muestran los pedidos abiertos para completarlos o reasignarlos (no frena, DU-12) · HU-056 | — |
| F-61 Visibilidad por jerarquía | El dato "depende de" (jefatura) en cada usuario | Mis tareas en el inicio y Tareas de mi gente · HU-038, HU-040 | — |
| F-68 Plazos de la prioridad | Gerencia define y guarda los plazos (W-50) | La prioridad de cada pedido nuevo se calcula con esos plazos (F-26) · HU-027 | — |
| F-48 Presupuesto por rubro | Se carga por presupuesto (W-13) | — | Alimenta la columna "Presupuestado" de la rendición |
| F-57 Gastos del proveedor | — (la ficha del proveedor existe desde la semana 1) | — | Lo imputado al proveedor, por obra |

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

**35 historias** (HU-026…HU-060) en 12 épicas. Resumen de estado DoR: **35 ✅ listas**; **22 de
ellas con uno o más criterios puntuales ⛔** hasta que Juani resuelva las dudas nuevas: DU-Q01…DU-Q03
(PO + QA) y DU-P01…DU-P24 (Analista, abiertas en paralelo al escribir `especificacion/pedidos.md` y
`avisos.md`), todas al final de [`dudas.md`](dudas.md). Cada historia se construye con la propuesta
de su duda; si la decisión es otra, cambia **solo** el criterio marcado, no la historia.

| Épica | Historias | ✅ | Con criterio ⛔ (duda abierta) |
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
     (identidad.md RN-IDE-41). Tope de la fecha de pedido: **⛔ DU-P08**.
  4. **Dado** "Con quién / para quién", **cuando** escribe cualquier texto (un proveedor, el
     cliente, un área, la propia empresa), **entonces** se guarda tal cual: es texto libre, como
     la columna PROVEEDOR de la minuta, y **no** se elige de la base de proveedores.
  5. **Dado** un período inicio–fin (alojamiento, alquiler), **cuando** lo carga, **entonces** se
     guarda; es opcional; un fin anterior al inicio da error.
  6. **Dado** el propietario, **entonces** es **una sola persona** activa y el sector se completa
     con el suyo (se puede cambiar el sector antes de elegir a la persona: el desplegable de
     personas se filtra por sector). No se puede cargar un segundo propietario: otra persona va
     como seguidora (HU-030). Una persona de un sector con Pedidos en Sin acceso igual ve y
     resuelve lo suyo: **⛔ DU-P01**.
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
- **DoR:** ✅ listo, salvo los criterios 3 y 6 (⛔ DU-P08, DU-P01: se construyen con la propuesta).

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
     DU-A13; RN-IDE-43). Quién la corrige al editar y cómo vuelve a la calculada: **⛔ DU-P07**
     (propuesta: solo quien lo pidió, con "Usar la calculada").
  6. **Dado** el cálculo, **entonces** el backend lo hace con `PlazosPrioridad::calcular` (no confía
     en la prioridad calculada que mande el front) y guarda la prioridad en el pedido.
- **Reglas:** identidad.md RN-IDE-41…44 · spec pedidos.md.
- **Permisos:** los de crear y editar el pedido.
- **Dependencias:** HU-011 (W-50), HU-026.
- **DoR:** ✅ listo, salvo el criterio 5 en lo de DU-P07 (⛔).

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
  cualquiera que lo vea con `pedidos:editar` (spec pedidos.md). Lo propio con Sin acceso: **⛔ DU-P01**;
  comentario de un tercero o en un pedido en el sector: **⛔ DU-P23**.
- **Dependencias:** HU-026.
- **DoR:** ✅ listo, salvo lo de DU-P01 y DU-P23 (⛔).

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
     creado, cada uno se suma o se saca a sí mismo: **⛔ DU-P11**. Sumarse no avisa a nadie.
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
- **DoR:** ✅ listo, salvo el criterio 2 en lo de DU-P11 (⛔).

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
  3. **Dado** obra y sector, **entonces** se cambian según **⛔ DU-P05** (propuesta: la obra se
     puede corregir en Editar, con registro; el sector se ve sin desplegable y se cambia con
     Reasignar, HU-035). El propietario y el estado nunca se cambian desde Editar (tienen sus botones).
  4. **Dado** que guarda sin cambiar nada, **entonces** no se agrega ninguna línea.
  5. **Dado** que cambia la entrega, **entonces** la prioridad se recalcula según HU-027 c5 y, si el
     pedido sigue abierto, el recordatorio "vence mañana" se calcula con la fecha nueva (HU-048).
  6. **Dado** las validaciones del alta (HU-026, incluida **⛔ DU-P08**), **entonces** aplican igual.
  7. **Dado** `pedidos:ver` (o sin acceso), **cuando** edita por API, **entonces** 403.
  8. **Dado** un pedido **Completada**, **cuando** se edita, **entonces** se permite igual (corregir
     un link o una aclaración) y queda en el historial.
  9. **Dado** un pedido cargado por error (repetido, o a la persona equivocada), **entonces** se
     sigue **⛔ DU-P06** (propuesta: no se borra; se completa con el comentario "Cargado por error",
     como el criterio de no borrar de W-34 y W-39).
  10. **Dado** que se edita la entrega o la tarea, **entonces** no se avisa a nadie (la edición no
      está en la tabla de avisos del cap. 7; **⛔ DU-P24**).
- **Reglas:** spec pedidos.md (edición) · ADR-010.
- **Permisos:** `pedidos:editar` con su alcance (F-33 "según la tabla de permisos").
- **Dependencias:** HU-026, HU-027, HU-028.
- **DoR:** ✅ listo, salvo los criterios 3, 6, 9 y 10 (⛔ DU-P05, DU-P08, DU-P06, DU-P24).

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
  Pedidos en Sin acceso: **⛔ DU-P01**.
- **Dependencias:** HU-026, HU-046.
- **DoR:** ✅ listo, salvo lo de DU-P01 (⛔).

#### HU-033 · Bloquear con motivo y retomar
- **F-29 · W-30 · W-42**
- **Como** propietario **quiero** marcar un pedido como Bloqueada con el motivo, como hoy en la
  minuta, **para** que quien lo pidió sepa por qué está trabado y lo destrabe.
- **Criterios de aceptación**
  1. **Dado** un pedido En curso (o No iniciada: **⛔ DU-P03**) del que es propietario, **cuando** toca "Bloquear…" y
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
- **DoR:** ✅ listo, salvo bloquear desde No iniciada (⛔ DU-P03).

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
  6. **Dado** un pedido **en el sector** (sin persona), **entonces** se sigue **⛔ DU-P02** (propuesta:
     cualquier persona activa del sector lo puede completar y queda como propietaria; DU-16 resuelta
     solo dice "tomar").
  7. **Dado** un pedido ya Completada, **cuando** se vuelve a enviar "completar" (doble clic),
     **entonces** 409 con el estado actual y sin un segundo aviso (ADR-011).
  8. **Dado** alguien que no es el propietario, **entonces** 403.
- **Reglas:** spec pedidos.md · spec avisos.md · ADR-014.
- **Permisos:** propietario.
- **Dependencias:** HU-028, HU-032, HU-046.
- **DoR:** ✅ listo, salvo el criterio 6 (⛔ DU-P02).

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
  7. **Dado** el estado al reasignar, **entonces** sigue **⛔ DU-P04** (propuesta: el estado no cambia,
     como hoy que solo cambian el nombre). Dejarlo en un sector sin usuarios activos no se puede
     (propuesta DU-P02).
- **Reglas:** spec pedidos.md · spec avisos.md.
- **Permisos:** propietario o solicitante del pedido (F-32).
- **Dependencias:** HU-026, HU-046, HU-047.
- **DoR:** ✅ listo, salvo el criterio 7 (⛔ DU-P04, DU-P02).

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
  6. **Dado** un propietario que ya está inactivo, **entonces** se sigue **⛔ DU-P09**.
- **Reglas:** spec pedidos.md.
- **Permisos:** solicitante del pedido (F-65 "quien lo pidió").
- **Dependencias:** HU-034.
- **DoR:** ✅ listo, salvo el criterio 6 (⛔ DU-P09).

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
     vence hoy no (dice "hoy"); el día se corta en hora de Argentina (**⛔ DU-P14**).
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
- **DoR:** ✅ listo, salvo el criterio 4 (⛔ DU-P14).

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
  4. **Dado** un subordinado inactivo con pedidos abiertos, **entonces** se sigue **⛔ DU-P09**
     (propuesta: aparece como "{persona} (inactiva)" con sus abiertos); personas sin tareas abiertas
     y orden de los grupos: **⛔ DU-P14**.
  5. **Dado** Dirección, **entonces** ve a todas las personas que dependen de ella directa o
     indirectamente (F-61: "Dirección ve todo").
  6. **Dado** un cambio de "depende de" en W-39, **cuando** el jefe vuelve a abrir la pestaña,
     **entonces** ya refleja la jerarquía nueva.
- **Reglas:** identidad.md RN-IDE-13, RN-IDE-22/23 (`Jerarquia::subordinados`) · spec pedidos.md.
- **Permisos:** tener gente a cargo (jerarquía, no matriz).
- **Dependencias:** HU-038.
- **DoR:** ✅ listo, salvo el criterio 4 (⛔ DU-P09, DU-P14).

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
     "vencidas" sin contar dos veces el mismo pedido, si se incluye "yo" y el orden: **⛔ DU-P14**
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
- **DoR:** ✅ listo, salvo el criterio 2 (⛔ DU-P14).

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
     W-25, y cómo lo sabe el sistema: **⛔ DU-P13** (propuesta: una sola lista, sin bloque aparte).
  4. **Dado** un pedido sin obra, **entonces** no aparece en la pestaña Pedidos de ninguna obra ni
     cuenta en sus "pedidos abiertos"; cuenta en la fila Servicios generales de Obras (HU-055).
- **Reglas:** spec pedidos.md · obras.md RN-OBR-08.
- **Permisos:** `pedidos:ver` / `pedidos:editar`.
- **Dependencias:** HU-026, HU-042.
- **DoR:** ✅ listo, salvo el criterio 3 (⛔ DU-P13).

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
     un pedido en el sector "el propietario" es todo el sector: **⛔ DU-P23**.
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
      en la tabla del cap. 7); editar el pedido tampoco (**⛔ DU-P24**).
- **Reglas:** spec avisos.md (destinatarios) · ADR-012.
- **Permisos:** — (lo decide el sistema).
- **Dependencias:** HU-026, HU-030 (seguidores), HU-005 (`jefe_id`).
- **DoR:** ✅ listo, salvo los criterios 5 y 13 en lo de DU-P23 y DU-P24 (⛔).

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
     **⛔ DU-P19** (propuesta: asignación y reasignación tildadas y fijas; se pueden activar tomado,
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
- **DoR:** ✅ listo, salvo el criterio 3 (⛔ DU-P19).

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
  5. **Dado** el recordatorio, **entonces** no sale por mail (F-37 "sin llenar el mail"; **⛔ DU-P19**).
  6. **Dado** la hora del aviso y una entrega del lunes, **entonces** se siguen **⛔ DU-P21** (propuesta:
     desde las 8:00 de Argentina, como W-02) y **⛔ DU-Q01** (propuesta: el domingo, días corridos
     como DU-04).
  7. **Dado** un pedido cargado con entrega hoy o ya vencida, **entonces** no hay recordatorio pero
     el aviso de asignación llega igual en el momento y el pedido figura vencido o "hoy" en Mis
     tareas.
  8. **Dado** el entorno, **entonces** el recordatorio está programado en el scheduler y corre sin
     que nadie lo dispare (HU-060).
- **Reglas:** spec avisos.md (recordatorio) · ADR-012.
- **Permisos:** — (sistema).
- **Dependencias:** HU-046, HU-060.
- **DoR:** ✅ listo, salvo los criterios 5 y 6 (⛔ DU-P19, DU-P21, DU-Q01).

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
  8. **Dado** qué es "Fecha" (**⛔ DU-Q03**: cuándo se mandó al taller) y qué es obligatorio, el orden
     de la lista y qué pasa con un equipo cargado por error (**⛔ DU-P18**: obligatorios equipo y
     falla o trabajo; no se borran; no completados primero).
  9. **Dado** un equipo, **entonces** no genera avisos (no están en la tabla de avisos del cap. 7).
- **Reglas:** spec pedidos.md (equipos) · ADR-014.
- **Permisos:** `equipos_reparacion:ver` / `:editar` (inicial: todos editan, como Pedidos; DU-01).
- **Dependencias:** HU-025, HU-060.
- **DoR:** ✅ listo, salvo el criterio 8 (⛔ DU-Q03, DU-P18).

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
  4. **Dado** alcance "propios" en Pedidos, **entonces** los números cuentan según **⛔ DU-P17**
     (propuesta: todos los abiertos de la obra, porque es un conteo; la pestaña y las listas sí
     respetan el alcance).
- **Reglas:** obras.md RN-OBR-02, 03, 08.
- **Permisos:** `obras:ver`; la fila, además `pedidos:ver`.
- **Dependencias:** HU-013, HU-026, HU-043.
- **DoR:** ✅ listo, salvo el criterio 4 (⛔ DU-P17).

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
     **⛔ DU-P12** (propuesta: los accesos abren W-29 y W-31 con sus reglas de siempre y aparecen solo
     donde quien mira puede usarlos; si no, ve el pedido sin acceso).
- **Reglas:** obras.md RN-OBR-29, 30, §6 · spec pedidos.md.
- **Permisos:** los de cambiar el estado (HU-017); las acciones sobre cada pedido, las de HU-034 y
  HU-035.
- **Dependencias:** HU-017, HU-034, HU-035, HU-059.
- **DoR:** ✅ listo, salvo el criterio 4 (⛔ DU-P12).

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
  3. **Dado** qué texto del pedido se busca: **⛔ DU-P15** (propuesta: tarea, con quién / para quién y
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
- **DoR:** ✅ listo, salvo el criterio 3 (⛔ DU-P15).

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
     sacar, **entonces** se sigue **⛔ DU-Q02** (propuesta: "Antes de sacar {sector}, reasigná sus
     N pedidos que están en el sector." y no se saca, 422).
  2. **Dado** un sector con pedidos (abiertos con persona o completados) y sin usuarios activos,
     **cuando** lo saca, **entonces** se **archiva** y los pedidos conservan su nombre (resuelta
     DU-A07).
  3. **Dado** una persona con pedidos abiertos como propietaria, **cuando** la desactivan,
     **entonces** se sigue **⛔ DU-P09** (propuesta: quedan como están, con "{persona} (inactiva)", y
     se ven en Tareas de mi gente de su jefe; no se reasigna nada solo, como DU-A08). Si era la
     última persona activa de un sector con pedidos en el sector: **⛔ DU-Q02** (aviso en W-39).
  4. **Dado** una persona inactiva, **entonces** no aparece en los desplegables de propietario,
     seguidor ni "una persona" de Reasignar.
- **Reglas:** identidad.md RN-IDE-25…33 · spec pedidos.md.
- **Permisos:** `usuarios_permisos:editar`.
- **Dependencias:** HU-006, HU-007, HU-035.
- **DoR:** ✅ listo, salvo los criterios 1 y 3 (⛔ DU-Q02, DU-P09).

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

## 7. Semanas 3 y 4 (épicas, sin detalle fino)

Se detallan en historias al cierre de la semana anterior, con DoR.

### Semana 3 · Gastos y rendición

| Épica | F-XX | W-XX |
|---|---|---|
| Carga única de facturas y gastos (F-43: factura como archivo o link) | F-39, F-40, F-41, F-42, F-43 | W-10, W-38 (alta rápida) |
| Gastos sin factura (tickets y remito como archivo o link) | F-44, F-47 | W-35, W-46 |
| Gastos cargados y detalle | F-45, F-46 | W-33, W-34 |
| Rendición de la obra | F-49, F-50, F-51, F-52, F-54 | W-11, W-36, W-37, W-44 |
| Todas las rendiciones en curso | F-53 | W-12 |
| Control de materiales por cantidad (remito como archivo o link) | F-66 | W-48 |
| Gastos del proveedor | F-57 | W-14 |
| Lo que se completa de semanas 1 y 2 | F-03 (gastos), F-14 (gastos), F-15 (% gastado), F-17 (pestaña Rendición), F-48 (alimenta la rendición) | W-18, W-05, W-07 |

Dudas de esta semana: DU-08, DU-17, DU-18 y DU-19, **resueltas el 12-nov** (va la propuesta).

### Semana 4 · Puesta en marcha

| Épica | F-XX | W-XX |
|---|---|---|
| Carga inicial (pedidos abiertos de la minuta, obras en ejecución con su gastado acumulado por rubro) | F-62 | — |
| Capacitación por sector y acompañamiento del arranque | F-63 | — |

Dudas de esta semana: DU-20 y DU-21, **resueltas el 12-nov** (va la propuesta).

---

## 8. Riesgo: la semana 3 es la más cargada

La semana 3 arranca **17 funciones** y completa otras 5 que vienen de semanas anteriores. Es la que
tiene más reglas de dinero (renglones repartidos, negativos, EPP → INSUMOS, solo total, horas y
fijos por período, monto y ganancia con permiso propio) y la que el cliente prueba contra su Excel,
donde cualquier diferencia se ve.

**Señales de que el plazo peligra** (se miran el miércoles de la semana 3):
- La carga de factura (W-10) con reparto por renglón no está en verde de punta a punta.
- La rendición (W-11) no da el mismo número que la rendición ficticia de referencia, rubro por rubro.
- La carga única de retiros (DU-17, resuelta: una sola carga suma en W-48 y en la rendición) no
  está en verde.

**Recorte candidato para proponerle a Juani** (no está decidido; nada sale de la Etapa 1, solo se
mueve de semana):

| Se mueve | De → a | Por qué este y no otro |
|---|---|---|
| F-66 Control de materiales por cantidad (W-48) | Semana 3 → semana 4 | Es una vista aparte de la rendición; la rendición por rubro (criterio de aceptación global n.º 1) no depende de ella. Con DU-17 resuelta, la carga de retiros es una sola y se puede mover sin rehacerla. |
| F-57 Gastos del proveedor (en la ficha del proveedor, W-14) | Semana 3 → semana 4 | Es una consulta sobre datos que ya se cargan; no frena la carga ni la rendición. |

Lo que **no** se propone mover, porque sin eso el cliente no puede hacer su prueba de la semana 3
("cargan las facturas de una obra en curso y la comparan con su rendición en Excel"): F-39 a F-47,
F-49 a F-51, F-53 y F-54.

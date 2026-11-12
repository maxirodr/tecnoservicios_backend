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
matriz, para que nadie dé por terminada una función a medias.

| F-XX | Semana 1 | Se completa en |
|---|---|---|
| F-03 Buscador general | Obras (N.º de presupuesto, cliente, nombre del trabajo) y proveedores (nombre) | Semana 2: pedidos por texto · Semana 3: gastos por número de factura |
| F-10 Mi perfil | Mis datos, mi sector, de quién dependo, cambiar la contraseña | Semana 2: "Mis avisos" (qué avisos llegan además por mail) |
| F-14 Registro de quién hizo qué | En obras (alta, edición, cambio de estado, presupuestos) | Semana 2: en pedidos · Semana 3: en gastos |
| F-15 Listado de obras | Pestañas por estado, N.º de presupuesto, trabajo, cliente y planta, responsable | Semana 2: columna "pedidos abiertos" y fila de Servicios generales · Semana 3: columna "% del presupuesto gastado" (en rojo si se pasó) |
| F-17 Ficha de la obra | Datos de la obra y pestaña Documentos y contactos | Semana 2: pestaña Pedidos · Semana 3: pestaña Rendición |
| F-21 Cambiar el estado de la obra | Confirmada → En ejecución → Terminada, por quien corresponde, con registro | Semana 2: al pasar a Terminada se muestran los pedidos abiertos para completarlos o reasignarlos |
| F-61 Visibilidad por jerarquía | El dato "depende de" (jefatura) en cada usuario | Semana 2: Tareas de mi gente y lo que ve cada uno en su inicio |
| F-68 Plazos de la prioridad | Gerencia define y guarda los plazos (W-50) | Semana 2: la prioridad de cada pedido nuevo se calcula con esos plazos (F-26) |
| F-48 Presupuesto por rubro | Se carga por presupuesto (W-13) | Semana 3: alimenta la columna "Presupuestado" de la rendición |
| F-57 Gastos del proveedor | — (la ficha del proveedor existe desde la semana 1) | Semana 3: lo imputado al proveedor, por obra |

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
- **F-01 · W-16**
- **Como** cualquier persona de la empresa **quiero** entrar con mi mail y mi contraseña, desde
  la PC o el celular, **para** usar el sistema sin compartir usuario.
- **Criterios de aceptación**
  1. **Dado** un usuario activo, **cuando** ingresa su mail y su contraseña correctos, **entonces**
     entra y ve su pantalla de inicio.
  2. **Dado** un mail o una contraseña incorrectos, **cuando** intenta entrar, **entonces** ve un
     único mensaje "El mail o la contraseña no son correctos" (no dice cuál de los dos falló).
  3. **Dado** un usuario **inactivo** (W-39), **cuando** intenta entrar con su contraseña correcta,
     **entonces** no entra y ve el mismo mensaje genérico.
  4. **Dado** que marca "Recordarme en este equipo", **cuando** cierra el navegador y vuelve,
     **entonces** sigue adentro; si no lo marca, al cerrar el navegador tiene que volver a entrar.
  5. **Dado** que está adentro, **cuando** toca "Salir", **entonces** la sesión se cierra y
     cualquier pantalla interna lo devuelve al ingreso.
  6. **Dado** 5 intentos fallidos seguidos desde el mismo mail, **cuando** intenta otra vez,
     **entonces** el sistema le pide esperar un minuto (respuesta 429 con el sobre JSON).
  7. **Dado** un celular (ancho 390 px), **cuando** abre el ingreso, **entonces** la pantalla se
     usa sin zoom ni desplazamiento horizontal (W-16: "la misma pantalla sirve en la PC y en el
     celular").
- **Permisos:** ninguno (pantalla pública).
- **Dependencias:** HU-005 (existen usuarios).
- **DoR:** ✅ listo.

#### HU-002 · Recuperar la contraseña por mail
- **F-01 · W-16**
- **Como** cualquier persona **quiero** pedir un mail para elegir una contraseña nueva **para**
  no depender de nadie si me la olvido.
- **Criterios de aceptación**
  1. **Dado** un mail de un usuario activo, **cuando** toca "Olvidé mi contraseña" y lo ingresa,
     **entonces** recibe un mail (por cola, desde noreply) con un link para elegir una contraseña nueva.
  2. **Dado** un mail que no existe o de un usuario inactivo, **cuando** pide recuperar, **entonces**
     ve el mismo mensaje de confirmación que en el caso 1 y no se envía ningún mail.
  3. **Dado** el link del mail, **cuando** elige una contraseña nueva válida, **entonces** puede
     entrar con ella y la anterior deja de servir.
  4. **Dado** un link ya usado o vencido (60 minutos), **cuando** lo abre, **entonces** ve que el
     link no sirve más y puede pedir otro.
- **Permisos:** ninguno.
- **Dependencias:** HU-001; mail por cola configurado.
- **DoR:** ✅ listo.

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

#### HU-004 · Cambiar mi contraseña
- **F-10 · W-19**
- **Como** cualquier persona **quiero** cambiar mi contraseña desde mi perfil **para** mantener
  mi cuenta segura.
- **Criterios de aceptación**
  1. **Dado** su contraseña actual correcta y una nueva válida (mínimo 8 caracteres) repetida
     igual, **cuando** guarda, **entonces** la nueva queda vigente y ve la confirmación.
  2. **Dado** una contraseña actual incorrecta, **cuando** guarda, **entonces** ve el error en el
     campo y no cambia nada (422).
  3. **Dado** que cambió la contraseña, **cuando** otra sesión suya abierta en otro equipo hace
     su siguiente pedido al servidor, **entonces** esa sesión queda cerrada.
- **Permisos:** ninguno especial.
- **Dependencias:** HU-003.
- **DoR:** ✅ listo.

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
  4. **Dado** el usuario nuevo, **cuando** se guarda, **entonces** recibe un mail de bienvenida con
     un link para elegir su contraseña que dura 7 días (mismo circuito que "Olvidé mi contraseña").
  5. **Dado** un usuario activo que no usó el link a tiempo, **cuando** gerencia toca "Reenviar mail
     de bienvenida" en W-15, **entonces** le llega un link nuevo y el anterior deja de servir; si ya
     eligió su contraseña, el botón no aparece (por API, 422).
  6. **Dado** "depende de", **cuando** se elige, **entonces** solo se ofrecen usuarios activos y
     no se puede elegir a sí mismo.
  7. **Dado** un usuario sin `usuarios:editar`, **cuando** llama al alta por API, **entonces**
     recibe 403 y no se crea nada.
- **Permisos:** `usuarios:editar` (Dirección y las dos gerencias).
- **Dependencias:** HU-007 (existen sectores), HU-009.
- **DoR:** ✅ listo (resuelta DU-03: mail de bienvenida y reenvío).

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
  5. **Dado** un usuario inactivo, **cuando** se lo vuelve a Activo, **entonces** puede entrar con su
     contraseña anterior.
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

## 6. Semanas 2 a 4 (épicas, sin detalle fino)

Se detallan en historias al cierre de la semana anterior, con DoR.

### Semana 2 · Pedidos (la minuta)

| Épica | F-XX | W-XX |
|---|---|---|
| Crear y editar pedidos (F-27: varios archivos o links por pedido, HU-025) | F-24, F-25, F-26, F-27, F-33, F-36, F-38, F-68 (aplicar plazos) | W-08, W-25, W-32, W-50 |
| Ciclo del pedido (F-31: comprobante de cierre como archivo o link, p. ej. la foto del remito; aviso al solicitante y a **su** superior) | F-28, F-29, F-30, F-31, F-32, F-34, F-65 | W-09, W-29, W-30, W-31 |
| Mis tareas y vistas de inicio | F-04, F-05, F-06, F-07, F-61 (visibilidad) | W-01, W-26, W-27, W-28 |
| Todos los pedidos | F-35 | W-24 |
| Calendario y carga por persona | F-11, F-12 | W-03, W-04 |
| Avisos | F-08, F-09, F-10 (Mis avisos), F-37, F-64 | W-02, W-19, W-45 |
| Celular | F-13 | W-41, W-42, W-43, W-47 (W-44 en la semana 3) |
| Equipos en reparación (presupuesto e inventario: archivo o link) | F-67 | W-49 |
| Lo que se completa de semana 1 | F-03 (pedidos), F-14 (pedidos), F-15 (pedidos abiertos, fila Servicios generales), F-17 (pestaña Pedidos), F-21 (pedidos abiertos al terminar) | W-18, W-05, W-07, W-22 |

Dudas de esta semana: DU-04, DU-12, DU-13, DU-14, DU-15, DU-16 y DU-22, **resueltas el 12-nov**
(DU-13 con el texto firmado: el aviso de completado va al solicitante y al superior del solicitante).

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

## 7. Riesgo: la semana 3 es la más cargada

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

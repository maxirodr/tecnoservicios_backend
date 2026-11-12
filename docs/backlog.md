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

Es la base de los "permisos involucrados" de cada historia. Sectores de arranque (W-40):
Dirección · Operaciones · Proyectos · Ingeniería de proyectos · Seguridad e Higiene · Calidad ·
Administración · Compras · RRHH · Pañol y Logística (sin usuario por ahora).

| Módulo (fila de W-15) | Configuración inicial firmada |
|---|---|
| Pedidos | Todos editan: todos piden y le pueden pedir a cualquiera; todos ven todos los pedidos de todas las obras. |
| Gastos y facturas | Editan Administración y Compras. Ven Operaciones, Proyectos e Ingeniería de proyectos. |
| Presupuesto por rubro | Lo carga Administración (editan Administración y Compras según W-15). Ven Operaciones, Proyectos e Ingeniería. |
| Rendición | Editan Administración y Compras. Ven Operaciones y Proyectos; Ingeniería de proyectos ve **la de sus obras**. |
| Monto de la obra y ganancia | Lo ven gerencias y administración. |
| Usuarios, sectores y permisos (incluye Plazos de la prioridad, que vive en la misma sección) | Editan Dirección y las dos gerencias. |
| Obras · Proveedores · resto de las celdas | "A completar" por el cliente. Propuesta de arranque en **DU-02**. |

Notación usada abajo: `modulo:nivel` (nivel `ver` o `editar`), `(propias)` = alcance solo sobre
las obras donde la persona es responsable.

---

## 5. Semana 1 · Base (detalle)

**24 historias** en 6 épicas. Resumen de estado DoR: **18 ✅ listas · 6 ⛔ bloqueadas**.

| Épica | Historias | ✅ | ⛔ |
|---|---|---|---|
| A · Ingreso y perfil | HU-001…HU-004 | 4 | 0 |
| B · Usuarios, sectores y permisos | HU-005…HU-011 | 5 | 2 |
| C · Obras y presupuestos por rubro | HU-012…HU-019 | 5 | 3 |
| D · Proveedores | HU-020…HU-021 | 2 | 0 |
| E · Buscador parcial | HU-022 | 1 | 0 |
| F · Navegación | HU-023…HU-024 | 1 | 1 |

> Orden de construcción sugerido: B (HU-005, HU-009, HU-010) → A → F → C → D → E. Sin
> permisos aplicados no se puede probar ninguna otra historia con permitido/denegado.

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
  4. **Dado** el usuario nuevo, **cuando** se guarda, **entonces** recibe el mail para elegir su
     contraseña según se decida en **DU-03**.
  5. **Dado** "depende de", **cuando** se elige, **entonces** solo se ofrecen usuarios activos y
     no se puede elegir a sí mismo.
  6. **Dado** un usuario sin `usuarios:editar`, **cuando** llama al alta por API, **entonces**
     recibe 403 y no se crea nada.
- **Permisos:** `usuarios:editar` (Dirección y las dos gerencias).
- **Dependencias:** HU-007 (existen sectores), HU-009.
- **DoR:** ⛔ bloqueada por **DU-03** (cómo elige su primera contraseña el usuario nuevo).

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
  1. **Dado** la instalación, **cuando** se abre W-40, **entonces** están los 10 sectores de arranque
     (sección 4), con "Pañol y Logística" en 0 usuarios.
  2. **Dado** `usuarios:editar`, **cuando** agrega un sector con nombre nuevo, **entonces** aparece en
     la lista y como **una fila más en la tabla de permisos** (W-15), con todas sus celdas en
     "Sin acceso" salvo Pedidos en "Editar" (todos piden y le piden a cualquiera).
  3. **Dado** un nombre que ya existe (sin distinguir mayúsculas ni tildes), **cuando** agrega o
     renombra, **entonces** ve el error y no se guarda.
  4. **Dado** un sector renombrado, **cuando** se guarda, **entonces** sus usuarios y sus permisos
     siguen iguales y se ve el nombre nuevo en todas las pantallas.
  5. **Dado** un sector con usuarios (activos o inactivos), **cuando** intenta sacarlo, **entonces**
     ve "Este sector tiene N usuarios: pasalos a otro sector antes de sacarlo" y no se saca (todo
     usuario tiene sector).
  6. **Dado** un sector sin usuarios, **cuando** lo saca, **entonces** desaparece de la lista y de
     la tabla de permisos.
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
  3. **Dado** un cambio que dejaría **ningún sector** con Editar en "Usuarios", **cuando** guarda,
     **entonces** ve "Tiene que quedar al menos un sector que administre usuarios" y no se guarda.
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
     **entonces** solo la gerencia de Administración edita usuarios, según se decida en **DU-01**.
- **Permisos:** —
- **Dependencias:** HU-007, catálogo de módulos.
- **DoR:** ⛔ bloqueada por **DU-01** (filas que faltan en W-15 y "solo la gerencia") y por
  **DU-02** (valor de arranque de las celdas "a completar").

#### HU-011 · Plazos de la prioridad
- **F-68 (parte semana 1) · W-50**
- **Como** gerencia **quiero** definir con cuántos días de anticipación un pedido es urgente,
  prioritario o normal **para** que la prioridad automática refleje cómo trabajamos.
- **Criterios de aceptación**
  1. **Dado** la instalación, **cuando** se abre W-50, **entonces** muestra los plazos de arranque:
     urgente hasta 1 día, prioritario hasta 7 días, normal más de 7.
  2. **Dado** `usuarios:editar`, **cuando** cambia "urgente hasta" o "prioritario hasta" a números
     enteros donde urgente < prioritario, **entonces** se guardan y "normal" se muestra como "más de
     <prioritario> días".
  3. **Dado** urgente ≥ prioritario, un número negativo o vacío, **cuando** guarda, **entonces** ve el
     error y no se guarda (422).
  4. **Dado** un cambio de plazos, **cuando** se guarda, **entonces** queda registrado quién lo
     cambió, cuándo y los valores anteriores.
  5. **Dado** un usuario sin `usuarios:editar`, **cuando** intenta guardar, **entonces** 403.
- **Fuera de esta semana:** el cálculo de la prioridad de cada pedido (F-26) y la regla de que el
  cambio vale solo para los pedidos nuevos (semana 2). Si los días son corridos o hábiles: **DU-04**
  (no bloquea esta historia: acá solo se guardan los números).
- **Permisos:** `usuarios:editar` (W-50 vive en la sección Usuarios).
- **Dependencias:** HU-009.
- **DoR:** ✅ listo.

### Épica C · Obras y presupuestos por rubro

#### HU-012 · Dar de alta una obra
- **F-16 · F-23 · W-06**
- **Como** Operaciones (o Administración) **quiero** abrir el legajo de una obra ganada con lo
  mínimo **para** empezar a cargarle pedidos.
- **Criterios de aceptación**
  1. **Dado** `obras:editar`, **cuando** carga N.º de presupuesto y revisión, nombre del trabajo,
     link al presupuesto, cliente, responsable de obra y los dos contactos (solicitante en planta y
     comprador, cada uno con nombre, mail y teléfono) y guarda, **entonces** la obra queda creada en
     estado **Confirmada** con ese presupuesto como principal.
  2. **Dado** que no carga OC / OT / contrato / mail de aprobación (link ni número), **cuando**
     guarda, **entonces** la obra se crea igual (la OC no es obligatoria).
  3. **Dado** que falta cualquier dato obligatorio (N.º, revisión, nombre del trabajo, link al
     presupuesto, cliente, responsable, o nombre/mail/teléfono de cualquiera de los dos contactos),
     **cuando** guarda, **entonces** ve el error en cada campo y no se crea (422).
  4. **Dado** planta y fechas previstas de inicio y fin, **cuando** las carga, **entonces** se
     guardan; si no las carga, la obra se crea igual. Si el fin previsto es anterior al inicio
     previsto, ve el error.
  5. **Dado** que manda `estado` en el alta (por ejemplo "Terminada"), **cuando** guarda,
     **entonces** el backend lo ignora: siempre nace Confirmada.
  6. **Dado** un N.º de presupuesto que ya está en otra obra, **cuando** guarda, **entonces** ve el
     error según la regla que se decida en **DU-05**.
  7. **Dado** el cliente, **cuando** lo escribe, **entonces** se comporta según **DU-06** (elegir de
     los ya cargados o escribir uno nuevo).
  8. **Dado** el alta guardada, **entonces** queda registrado quién la creó y cuándo (F-14).
  9. **Dado** un mail de contacto mal escrito o links que no empiezan con `http(s)://`, **cuando**
     guarda, **entonces** ve el error.
- **Permisos:** `obras:editar` (Operaciones, Proyectos, Ingeniería; también Administración —
  arranque propuesto en DU-02).
- **Dependencias:** HU-005 (responsable = usuario activo), HU-009.
- **DoR:** ⛔ bloqueada por **DU-05** (formato y unicidad del N.º de presupuesto y la revisión) y
  **DU-06** (cliente: lista o texto libre).

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
     adicionales) con su revisión y su link; la OC / OT / contrato / mail de aprobación con número y
     link; los certificados como links; el link a la carpeta de Drive de la obra; y los contactos
     solicitante y comprador con mail y teléfono.
  3. **Dado** `obras:editar`, **cuando** agrega un certificado (nombre + link) o el link a la carpeta
     de Drive, **entonces** queda en la pestaña y en el registro de la obra.
  4. **Dado** cualquier link, **cuando** lo toca, **entonces** se abre en una pestaña nueva: el
     sistema guarda links, no copias de archivos.
  5. **Dado** el id de una obra que no existe, **cuando** abre la ficha, **entonces** ve "Obra no
     encontrada" (404 con el sobre JSON).
- **Fuera de esta semana:** el contenido de las pestañas Pedidos (semana 2) y Rendición (semana 3);
  hasta entonces muestran "Disponible en la próxima entrega".
- **Permisos:** `obras:ver`; agregar certificados y carpeta: `obras:editar`.
- **Dependencias:** HU-012.
- **DoR:** ✅ listo.

#### HU-015 · Agregar un presupuesto adicional a la obra
- **F-18 · W-21**
- **Como** Operaciones o Administración **quiero** sumar a la misma obra un presupuesto adicional
  (como el "A1") **para** no abrir otra obra y que su plata se sume a la de la obra.
- **Criterios de aceptación**
  1. **Dado** `obras:editar`, **cuando** carga N.º de presupuesto, revisión y link (y, si los tiene,
     N.º y link de la OC y una descripción) y guarda, **entonces** el adicional queda en la obra y
     pasa a cargar sus montos por rubro (HU-018).
  2. **Dado** una obra con 1 presupuesto, **cuando** se agregan 2 adicionales, **entonces** la obra
     tiene 3 y los muestra en orden de alta (como en sus rendiciones: 1 a 3 por obra).
  3. **Dado** que falta N.º, revisión o link, **cuando** guarda, **entonces** ve el error.
  4. **Dado** una obra **Terminada**, **cuando** agrega un adicional, **entonces** se permite (la
     obra se consulta igual) y queda registrado.
  5. **Dado** el alta del adicional, **entonces** queda en el registro de la obra (F-14).
- **Permisos:** `obras:editar`.
- **Dependencias:** HU-012.
- **DoR:** ⛔ bloqueada por **DU-05** (formato del N.º de un adicional: "N-NNN-AAAA A1" o número propio).

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
  2. **Dado** una persona de **Administración** (con permiso de editar obras), **cuando** cambia el
     estado de cualquier obra, **entonces** se permite.
  3. **Dado** una persona de Operaciones que **no** es responsable de esa obra, **cuando** intenta
     cambiar el estado, **entonces** recibe 403 (el estado lo cambian el responsable y Administración).
  4. **Dado** una obra En ejecución, **cuando** la pasan a Terminada, **entonces** sale de las activas
     y queda en Terminadas con todo lo suyo.
  5. **Dado** el cambio de estado, **entonces** solo se hace desde W-22 (la edición no lo cambia).
- **Fuera de esta semana:** la lista de pedidos abiertos al pasar a Terminada (semana 2, ver
  **DU-12**). Volver a un estado anterior o saltear uno: **DU-07** (los criterios de arriba no
  dependen de esa respuesta).
- **Permisos:** `obras:editar` con alcance (propias) para el responsable; alcance todas para
  Administración.
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
     rendiciones). Cómo se ve una obra que mezcla un presupuesto por rubro con otro solo total:
     **DU-08**.
  5. **Dado** una obra sin presupuesto por rubro cargado, **entonces** la obra existe igual y se
     puede trabajar (los gastos se cargan igual en la semana 3).
  6. **Dado** Operaciones o Ingeniería (`ver`), **cuando** abre W-13, **entonces** lo ve sin poder
     editar; por API, 403 al guardar.
  7. **Dado** un presupuesto ya cargado, **cuando** Administración corrige un importe, **entonces**
     se comporta según **DU-09** (corregible con registro del cambio).
- **Permisos:** `presupuesto_rubro:editar` (Administración y Compras); `presupuesto_rubro:ver`
  (Operaciones, Proyectos, Ingeniería).
- **Dependencias:** HU-012, HU-015.
- **DoR:** ⛔ bloqueada por **DU-08** (obra con presupuestos mixtos) y **DU-09** (corregir después).

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
- **Permisos:** `proveedores:ver` / `proveedores:editar` (Administración y Compras — arranque del
  resto en DU-02).
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
     guarda, **entonces** se comporta según **DU-10** (propuesta: no se guarda).
  5. **Dado** un proveedor, **cuando** lo edita, **entonces** se guardan los cambios con quién y cuándo.
  6. **Dado** la ficha, **entonces** no existe "borrar" (F-56 es alta y edición).
- **Permisos:** `proveedores:editar`.
- **Dependencias:** HU-020.
- **DoR:** ✅ listo (DU-10 es decisión técnica; el criterio 4 se escribe con la propuesta).

### Épica E · Buscador parcial

#### HU-022 · Buscador general: obras y proveedores
- **F-03 (parte semana 1) · W-18**
- **Como** cualquier persona **quiero** un solo buscador arriba donde escribo una parte del N.º de
  presupuesto, del cliente o del trabajo **para** encontrar la obra sin recorrer solapas, como hoy
  en la minuta.
- **Criterios de aceptación**
  1. **Dado** que escribe "087", **cuando** busca, **entonces** encuentra la obra 1-087-2026 (y sus
     adicionales) por N.º de presupuesto parcial.
  2. **Dado** que escribe parte del cliente o del nombre del trabajo ("tanque"), **entonces** ve las
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
  Si también busca por planta: **DU-11** (no bloquea: el resto de los criterios no cambia).
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
     Ver o Editar en su sector; "Sin acceso" no aparece.
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
- **DoR:** ⛔ bloqueada por **DU-01** (qué módulos tiene la tabla: define qué ítems de la barra
  dependen de qué fila).

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

---

## 6. Semanas 2 a 4 (épicas, sin detalle fino)

Se detallan en historias al cierre de la semana anterior, con DoR.

### Semana 2 · Pedidos (la minuta)

| Épica | F-XX | W-XX |
|---|---|---|
| Crear y editar pedidos | F-24, F-25, F-26, F-27, F-33, F-36, F-38, F-68 (aplicar plazos) | W-08, W-25, W-32, W-50 |
| Ciclo del pedido | F-28, F-29, F-30, F-31, F-32, F-34, F-65 | W-09, W-29, W-30, W-31 |
| Mis tareas y vistas de inicio | F-04, F-05, F-06, F-07, F-61 (visibilidad) | W-01, W-26, W-27, W-28 |
| Todos los pedidos | F-35 | W-24 |
| Calendario y carga por persona | F-11, F-12 | W-03, W-04 |
| Avisos | F-08, F-09, F-10 (Mis avisos), F-37, F-64 | W-02, W-19, W-45 |
| Celular | F-13 | W-41, W-42, W-43, W-47 (W-44 en la semana 3) |
| Equipos en reparación | F-67 | W-49 |
| Lo que se completa de semana 1 | F-03 (pedidos), F-14 (pedidos), F-15 (pedidos abiertos, fila Servicios generales), F-17 (pestaña Pedidos), F-21 (pedidos abiertos al terminar) | W-18, W-05, W-07, W-22 |

Dudas que conviene cerrar antes: DU-04, DU-12, DU-13, DU-14, DU-15, DU-16, DU-22.

### Semana 3 · Gastos y rendición

| Épica | F-XX | W-XX |
|---|---|---|
| Carga única de facturas y gastos | F-39, F-40, F-41, F-42, F-43 | W-10, W-38 (alta rápida) |
| Gastos sin factura | F-44, F-47 | W-35, W-46 |
| Gastos cargados y detalle | F-45, F-46 | W-33, W-34 |
| Rendición de la obra | F-49, F-50, F-51, F-52, F-54 | W-11, W-36, W-37, W-44 |
| Todas las rendiciones en curso | F-53 | W-12 |
| Control de materiales por cantidad | F-66 | W-48 |
| Gastos del proveedor | F-57 | W-14 |
| Lo que se completa de semanas 1 y 2 | F-03 (gastos), F-14 (gastos), F-15 (% gastado), F-17 (pestaña Rendición), F-48 (alimenta la rendición) | W-18, W-05, W-07 |

Dudas que conviene cerrar antes: DU-08, DU-17, DU-18, DU-19.

### Semana 4 · Puesta en marcha

| Épica | F-XX | W-XX |
|---|---|---|
| Carga inicial (pedidos abiertos de la minuta, obras en ejecución con su gastado acumulado por rubro) | F-62 | — |
| Capacitación por sector y acompañamiento del arranque | F-63 | — |

Dudas que conviene cerrar antes: DU-20, DU-21.

---

## 7. Riesgo: la semana 3 es la más cargada

La semana 3 arranca **17 funciones** y completa otras 5 que vienen de semanas anteriores. Es la que
tiene más reglas de dinero (renglones repartidos, negativos, EPP → INSUMOS, solo total, horas y
fijos por período, monto y ganancia con permiso propio) y la que el cliente prueba contra su Excel,
donde cualquier diferencia se ve.

**Señales de que el plazo peligra** (se miran el miércoles de la semana 3):
- La carga de factura (W-10) con reparto por renglón no está en verde de punta a punta.
- La rendición (W-11) no da el mismo número que la rendición ficticia de referencia, rubro por rubro.
- Quedan abiertas DU-17 o DU-18.

**Recorte candidato para proponerle a Juani** (no está decidido; nada sale de la Etapa 1, solo se
mueve de semana):

| Se mueve | De → a | Por qué este y no otro |
|---|---|---|
| F-66 Control de materiales por cantidad (W-48) | Semana 3 → semana 4 | Es una vista aparte de la rendición; la rendición por rubro (criterio de aceptación global n.º 1) no depende de ella. Además depende de DU-17. |
| F-57 Gastos del proveedor (en la ficha del proveedor, W-14) | Semana 3 → semana 4 | Es una consulta sobre datos que ya se cargan; no frena la carga ni la rendición. |

Lo que **no** se propone mover, porque sin eso el cliente no puede hacer su prueba de la semana 3
("cargan las facturas de una obra en curso y la comparan con su rendición en Excel"): F-39 a F-47,
F-49 a F-51, F-53 y F-54.

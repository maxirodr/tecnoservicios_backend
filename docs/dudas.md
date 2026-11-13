# Dudas · Etapa 1 · NS-2026-025

> **Estado al 12-nov-2026: todas resueltas.** Juani (dev a cargo, decide alcance) aprobó el
> 12-nov el paquete del Sprint 0:
>
> - **DU-01…DU-22 y DU-A01…DU-A21 (43 dudas): va la propuesta tal como está escrita**, con una
>   sola excepción: **DU-13** se resuelve con el texto firmado (el aviso de completado llega al
>   solicitante y **al superior del solicitante**, `jefe_id` de quien pidió).
> - **DU-23** (archivos además de links) entra ya resuelta → [ADR-014](adr/ADR-014-adjuntos-archivo-o-link.md).
> - Aceptados los **ADR-001…ADR-014** (incluidos el polling cada 15 s de ADR-005 y el alcance
>   "solo sus obras" de la matriz, ADR-006).
> - No queda ninguna historia ⛔ en el [backlog](backlog.md). Las especificaciones pasan a regla
>   definitiva lo que antes decía "propuesta" o "si se aprueba".
>
> Lo que el Documento del sistema firmado (v1.1, 4-nov-2026) no dice y hace falta para escribir
> un criterio de aceptación verificable. Regla de oro: no se agrega, quita ni cambia funcionalidad;
> cada propuesta es **la más cercana a cómo trabajan hoy**, con su fuente.
>
> - **"¿Cambia lo que ve el cliente? → decide Juani"**: la propuesta cambia una pantalla, un mensaje
>   o un comportamiento que el cliente nota. No se construye hasta que Juani decida.
> - **"Decisión técnica"**: la decide el equipo (Tech Lead) y se documenta en un ADR si hace falta.
> - Las historias que dependen de una duda "decide Juani" abierta quedan ⛔ en el [backlog](backlog.md)
>   (hoy ninguna: todas resueltas el 12-nov).
> - Una duda nunca se borra: al cerrarla se anota debajo **Decisión**, quién, fecha y el ADR o RN.

---

## Sprint 0 · alta de dudas al bajar el backlog a criterios de aceptación (12-nov-2026)

### Semana 1

#### DU-01 · Tabla de permisos: filas que faltan y "Editar, solo la gerencia"
- **Fuente:** W-15 (columnas: Obras · Pedidos · Gastos y facturas · Presupuesto por rubro · Rendición
  · Proveedores · Usuarios; celda Administración/Usuarios = "Editar, solo la gerencia") · W-40 (un
  solo sector "Administración") · cap. 3 ("El monto de la obra y la ganancia los ven gerencias y
  administración") · F-67 ("Según permisos") · F-68 ("Gerencia").
- **Por qué importa:** la tabla es por **sector**, pero (a) "solo la gerencia" pide distinguir a la
  gerente de Administración de la jefatura dentro del mismo sector, y (b) tres cosas firmadas tienen
  permiso propio y no tienen columna: Equipos en reparación, Monto de la obra y ganancia, y Plazos de
  la prioridad. Sin esto no se puede cargar la configuración inicial ni decidir la barra.
- **Propuesta:**
  1. Separar el sector **"Gerencia de Administración"** de **"Administración"** (jefatura), igual que
     W-15 ya separa "Operaciones" (gerencia) de "Proyectos" (jefatura). Así "solo la gerencia" se
     cumple con la tabla, sin reglas por persona. Fuente: organigrama (Gerencia de Administración y
     Finanzas → Jefatura de Administración).
  2. Agregar dos columnas: **"Equipos en reparación"** (arranque igual que Pedidos: todos editan, como
     hoy la solapa de la minuta) y **"Monto de la obra y ganancia"** (Ver: Dirección, Operaciones,
     Gerencia de Administración, Administración; Sin acceso el resto, cap. 3).
  3. **Plazos de la prioridad** sin columna propia: va con "Usuarios", porque W-50 vive en esa misma
     sección (pestañas Usuarios · Sectores · Tabla de permisos · Plazos de la prioridad).
- **¿Cambia lo que ve el cliente?** Sí (un sector más en W-40 y dos columnas en W-15) → **decide Juani**.
- **Resolución (Juani, 2026-11-12):** ✅ va la propuesta.

#### DU-02 · Valor de arranque de las celdas "a completar"
- **Fuente:** cap. 3 ("el resto de las celdas las completan ustedes al empezar") · W-15 (columnas Obras
  y Proveedores enteras "a completar") · F-15, F-17, F-19, F-22 ("Quién: Todos") · F-16 ("Operaciones ·
  también administración") · F-55, F-56 ("Administración y compras") · cap. 3 ("Dirección: ve todo").
- **Por qué importa:** el sistema no puede arrancar con celdas vacías: cada una tiene que valer Editar,
  Ver o Sin acceso el día 1, y la prueba de la semana 1 ("cargan dos o tres obras reales") necesita que
  Obras funcione.
- **Propuesta** (sale de la columna "Quién" de cada función; el cliente la ajusta en W-15 durante la
  semana 1):
  - **Obras:** Editar Operaciones, Proyectos, Ingeniería de proyectos, Gerencia de Administración y
    Administración (F-16); Ver todos los demás (F-15/F-17: "Todos").
  - **Proveedores:** Editar Administración, Gerencia de Administración y Compras (F-55/F-56); Ver
    Dirección, Operaciones, Proyectos e Ingeniería (que ya ven los gastos); Sin acceso el resto.
  - **Gastos, Presupuesto por rubro y Rendición** de Dirección: Ver ("ve todo"). De Seguridad e Higiene,
    Calidad, RRHH y Pañol: Sin acceso (el documento no les da nada en esos módulos).
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani** (idealmente lo valida con el cliente en la
  entrega de la semana 1).
- **Resolución (Juani, 2026-11-12):** ✅ va la propuesta.

#### DU-03 · Cómo elige su primera contraseña un usuario nuevo — reemplazada por ADR-015 (13-nov)
> Ya no hay contraseñas: el usuario nuevo entra directo con su mail pidiendo un código. Lo de abajo queda como historia.
- **Fuente:** W-39 (el alta tiene nombre, mail, sector, jefatura, estado y teléfono; **no** tiene
  contraseña) · W-16 ("Olvidé mi contraseña manda un mail para elegir una nueva") · F-01.
- **Por qué importa:** sin esto el usuario dado de alta no puede entrar y la historia de alta no se puede
  probar de punta a punta. Que gerencia invente y pase contraseñas a mano es inseguro.
- **Propuesta:** al guardar el alta, el usuario recibe un **mail de bienvenida con un link para elegir su
  contraseña** (el mismo circuito de "Olvidé mi contraseña", con un link que dura 7 días). Desde W-15 se
  puede reenviar si venció. Es lo más parecido a cómo ya funciona la recuperación firmada en W-16.
- **¿Cambia lo que ve el cliente?** Sí (un mail nuevo y un botón "reenviar") → **decide Juani**.
- **Resolución (Juani, 2026-11-12):** ✅ va la propuesta.

#### DU-04 · Los plazos de la prioridad, ¿en días corridos o hábiles?
- **Fuente:** F-26 / W-50 ("hasta 1 día · hasta 7 días · más de 7") · análisis de la minuta §7 (las
  medianas reales — urgente 0, prioritario 4, normal 8 días — están medidas en días corridos).
- **Por qué importa:** un pedido cargado el viernes para el lunes es "prioritario" (3 días corridos) o
  "urgente" (1 día hábil). Cambia la prioridad que ve todo el mundo y los tests de borde.
- **Propuesta:** **días corridos**, como se midió en su minuta, y la persona corrige a mano si hace falta
  (F-26 ya lo permite). Sin calendario de feriados.
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**. No bloquea la semana 1 (W-50 solo guarda números).
- **Resolución (Juani, 2026-11-12):** ✅ va la propuesta.

#### DU-05 · N.º de presupuesto: formato, revisión y unicidad
- **Fuente:** análisis de la minuta §2 (solapas `CLIENTE N-NNN-AAAA` + `REV01`/`rev02` cuando hay
  revisión; prefijos 1, 2 y 3; una solapa con dos números de presupuesto juntos en uno) · análisis de
  rendiciones (adicionales "con sufijo A1 o con número propio") · W-06 y W-21 (ejemplos "2-131-2026" /
  "REV01" y "2-131-2026 A1" / "REV01") · cap. 6 ("N.º de presupuesto y revisión: obligatorio").
- **Por qué importa:** es la clave con la que buscan las obras (F-03) y se valida en el alta. Si el
  formato es libre, vuelven los mismos números escritos de varias formas; si es muy estricto, no entran
  los adicionales.
- **Propuesta:**
  - N.º = `prefijo-número-año` (prefijo 1 dígito, número de 3 o más dígitos, año de 4) con sufijo
    opcional de adicional ` A1`, ` A2`… Un adicional con número propio usa el mismo formato sin sufijo.
  - **Revisión** obligatoria con formato `REVnn`, precargada en `REV01` como en las maquetas. Cuando el
    cliente emite una nueva revisión del **mismo** presupuesto se **edita** la revisión (queda en el
    historial, F-20), no se crea otro presupuesto.
  - **Único en todo el sistema** (sin contar la revisión): un mismo N.º no puede estar en dos obras.
    Un caso así (ej. ficticio "3-201203-2026") se carga como dos presupuestos de la misma obra.
- **¿Cambia lo que ve el cliente?** Sí (validaciones del alta) → **decide Juani**.
- **Resolución (Juani, 2026-11-12):** ✅ va la propuesta.

#### DU-06 · Cliente de la obra: ¿se elige de una lista o se escribe?
- **Fuente:** W-06 (el campo Cliente se dibuja como lista desplegable "Planta Ejemplo SA ▾"; Planta como
  texto) · W-05 ("un mismo cliente puede tener varias obras") · decisión D-026 ("el cliente es un dato
  de la obra", no hay módulo de clientes) · análisis de la minuta §4 (el mismo nombre escrito hasta de
  4 formas).
- **Por qué importa:** si es texto libre, "Planta Ejemplo SA" y "PLANTA EJEMPLO" son dos clientes y el
  buscador y el filtro los separan. No hay pantalla de alta de clientes en el documento, así que una
  lista cerrada no tendría de dónde cargarse.
- **Propuesta:** campo con **sugerencias de los clientes ya usados en otras obras** (se elige uno o se
  escribe uno nuevo, sin pantalla aparte de clientes), con aviso si lo escrito se parece a uno existente.
  Planta: texto libre opcional.
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**.
- **Resolución (Juani, 2026-11-12):** ✅ va la propuesta.

#### DU-07 · ¿Se puede volver una obra a un estado anterior?
- **Fuente:** F-21 ("Confirmada → En ejecución → Terminada") · W-22 (el selector muestra los tres estados)
  · hoy en Excel cambian la celda en cualquier sentido (rendiciones).
- **Por qué importa:** si alguien pasa una obra a Terminada por error, sin vuelta atrás la obra queda
  archivada y nadie la puede corregir; si se permite todo, se pierde el orden del ciclo.
- **Propuesta:** quien puede cambiar el estado (responsable y Administración) puede **volver un paso
  atrás** (Terminada → En ejecución, En ejecución → Confirmada), con registro de quién y cuándo. Avanzar
  sigue siendo de a un paso.
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**. No bloquea: la semana 1 construye el avance.
- **Resolución (Juani, 2026-11-12):** ✅ va la propuesta.

#### DU-08 · Obra que mezcla un presupuesto por rubro con otro "solo total"
- **Fuente:** cap. 4.4 / F-48 / W-13 ("si una obra solo tiene el total, se carga el total") · análisis de
  rendiciones (4 de 6 por rubro, 2 solo total; obras con 2 y 3 presupuestos).
- **Por qué importa:** el documento habla de "la obra" solo total, pero el total se carga **por
  presupuesto**. Si el principal está por rubro y el adicional "A1" llega solo con su total, la rendición
  no sabe dónde poner esa plata.
- **Propuesta:** "por rubro" o "solo total" se elige **por presupuesto**. En la rendición, los rubros
  muestran lo desglosado y una fila **"Sin desglose por rubro"** suma los presupuestos de solo total; el
  presupuestado total de la obra es la suma de todo. Si todos son solo total, se ve total contra gastado,
  como hoy.
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**.
- **Resolución (Juani, 2026-11-12):** ✅ va la propuesta.

#### DU-09 · ¿Se puede corregir el presupuesto por rubro después de cargado?
- **Fuente:** F-48 ("una vez, al darlo de alta") · cap. 4.1 ("lo carga el administrativo una sola vez;
  le lleva cinco minutos", Gerencia de Operaciones, 18-jun).
- **Por qué importa:** "una sola vez" describe el esfuerzo, pero un error de tipeo sin corrección deja la
  rendición mal para siempre. Hay que saber si se bloquea después de guardar.
- **Propuesta:** Administración **puede corregir** los importes, y cada corrección queda registrada con
  valor anterior, quién y cuándo (como la edición de la obra, F-20). Hoy corrigen la celda del Excel.
- **¿Cambia lo que ve el cliente?** Sí (historial en W-13) → **decide Juani**.
- **Resolución (Juani, 2026-11-12):** ✅ va la propuesta.

#### DU-10 · Proveedor repetido: ¿se bloquea o solo se avisa?
- **Fuente:** F-55 ("cada proveedor se carga una vez") · W-38 ("aviso de parecidos para no cargar el
  mismo proveedor dos veces") · análisis de la minuta §4 (el mismo proveedor escrito hasta de 4 formas).
- **Por qué importa:** "parecidos" avisa pero deja guardar; falta la regla para el duplicado exacto.
- **Propuesta:** se **rechaza** un nombre idéntico al normalizarlo (sin mayúsculas, tildes, puntos ni
  "SA / SRL") y un **CUIT** que ya tiene otro proveedor; los **parecidos** solo avisan y dejan guardar.
- **¿Cambia lo que ve el cliente?** Mínimamente (un mensaje de error que aplica lo firmado) → **decisión
  técnica**.
- **Resolución (Juani, 2026-11-12):** ✅ va la propuesta.

#### DU-11 · Buscador: ¿también por planta?
- **Fuente:** F-03 ("obras por N.º de presupuesto o por palabra clave (cliente, nombre del trabajo)") ·
  W-05 ("el cliente y la planta se ven juntos") · análisis de la minuta §2 (solapas nombradas
  contratista + planta en el mismo nombre).
- **Por qué importa:** hoy buscan la solapa por el nombre que incluye la planta; si el buscador no la
  mira, una búsqueda que hoy funciona en la minuta no encuentra la obra.
- **Propuesta:** incluir **planta** en la búsqueda de obras, junto con cliente y nombre del trabajo.
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**. No bloquea la semana 1.
- **Resolución (Juani, 2026-11-12):** ✅ va la propuesta.

### Semana 2

#### DU-12 · Pasar a Terminada con pedidos abiertos: ¿frena o solo muestra?
- **Fuente:** F-21 ("muestra los pedidos abiertos para completarlos o reasignarlos antes de cerrar") ·
  W-22 ("Antes de cerrar, cada pedido abierto se completa o se reasigna") · cap. 1 ("Ordena y muestra;
  no decide") · análisis de la minuta §7 (18 pedidos abiertos en solapas ocultas).
- **Por qué importa:** **reasignar** un pedido lo deja abierto y en la misma obra, así que "cada pedido
  se completa o se reasigna" no garantiza cero abiertos. Hay que saber si el botón "Pasar a Terminada"
  se habilita con pedidos abiertos.
- **Propuesta:** la ventana lista los abiertos con "completar" y "reasignar"; **"Pasar a Terminada" se
  puede confirmar igual** (el sistema muestra, no decide). Los pedidos que siguen abiertos quedan en Mis
  tareas de su propietario y en la obra terminada, así ninguno se esconde como hoy en las solapas
  ocultas.
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**.
- **Resolución (Juani, 2026-11-12):** ✅ va la propuesta.

#### DU-13 · Al completar un pedido, ¿a qué superior le llega el aviso?
- **Fuente:** cap. 4.2 y F-31 ("Le avisa al que lo pidió y, dentro del sistema, a su superior") · tabla
  de avisos del cap. 7 ("Al que lo pidió y a su superior") · definición C7 del 19-oct, cita de la Gerencia
  de Operaciones: "que a mi superior le aparezca que **ya terminé**".
- **Por qué importa:** la frase del documento se lee como el superior **del que pidió**; la cita de la
  reunión habla del superior **del que lo completó**. Son personas distintas y cambian los avisos y los
  tests.
- **Propuesta:** el superior (jefatura en "depende de") **del propietario que lo completó**, que es lo que
  dijo la gerencia en la reunión ("ya terminé"). Si la persona no tiene jefatura (Dirección), no hay
  aviso extra.
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**.
- **Resolución (Juani, 2026-11-12):** ✅ se sigue el texto firmado: aviso al solicitante y a su superior.
  "Su superior" = el `jefe_id` **de quien pidió**, no el de quien completó. Si el solicitante no tiene
  jefatura (Dirección), no hay aviso extra. La propuesta de arriba queda descartada.

#### DU-14 · ¿La fecha de entrega es obligatoria en un pedido nuevo?
- **Fuente:** F-24 (lista los datos del pedido sin marcar obligatorios) · F-26 (la prioridad se calcula
  con la fecha de entrega) · F-37 (el recordatorio sale de la fecha de entrega) · análisis de la minuta
  §2 (la fecha de entrega es "la fecha comprometida").
- **Por qué importa:** sin fecha de entrega no hay prioridad, ni recordatorio, ni vencido: el criterio
  global n.º 3 ("ningún pedido se atrasa sin que el propietario se entere antes") no se puede cumplir.
- **Propuesta:** **obligatoria** en todo pedido nuevo. Obligatorios: tarea, sector y propietario, fecha
  de entrega (obra puede ser "sin obra"). Los pedidos de la carga inicial sin fecha: DU-20.
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**.
- **Resolución (Juani, 2026-11-12):** ✅ va la propuesta.

#### DU-15 · ¿Se puede completar un pedido sin tomarlo antes?
- **Fuente:** estados del cap. 7 (No iniciada → En curso → Completada; "de Bloqueada se vuelve a En
  curso") · análisis de la minuta §1 (705 de 755 completados; hoy cambian el desplegable directo).
- **Por qué importa:** muchos pedidos chicos se resuelven en el momento; obligar a "tomar" y después
  "completar" agrega un paso (principio "Simple a propósito") y un aviso de "tomado" que nadie necesita.
- **Propuesta:** el propietario puede **completar desde No iniciada o En curso** (el recorrido registra
  que se completó sin tomarlo). Desde **Bloqueada**, primero "Retomar" (W-30).
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**.
- **Resolución (Juani, 2026-11-12):** ✅ va la propuesta.

#### DU-16 · Pedido "en el sector" (sin persona): ¿a quién le avisa y quién lo toma?
- **Fuente:** cap. 4.2 ("Si el propietario no está ese día, el pedido queda en su sector") · W-31
  ("asignar al sector para que lo tome el que pueda") · F-32.
- **Por qué importa:** sin persona no hay a quién mandarle el aviso de asignación ni el recordatorio de
  "falta 1 día" (criterio global n.º 3), y no está claro quién puede tomarlo.
- **Propuesta:** el pedido queda con **sector y sin persona**; el aviso de asignación (sistema y mail) y
  el recordatorio le llegan a **todas las personas activas del sector**; lo puede tomar cualquiera de
  ellas y, al tomarlo, pasa a ser su propietario. Aparece en Mis tareas de todo el sector hasta que alguien
  lo tome.
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**.
- **Resolución (Juani, 2026-11-12):** ✅ va la propuesta.

#### DU-22 · Comentario nuevo: ¿a quién le llega el aviso?
- **Fuente:** tabla de avisos del cap. 7 ("Bloquean o comentan un pedido mío → al que lo pidió") · W-41
  (el aviso de comentario nuevo aparece en el celular del propietario) · cap. 1 (hoy "hay que dejar un
  comentario y arrobar a la persona").
- **Por qué importa:** si solo le llega al solicitante, cuando el solicitante comenta "falta tal cosa" el
  propietario no se entera, que es justo el problema de hoy.
- **Propuesta:** el aviso le llega **a la otra parte** (comenta el solicitante → al propietario; comenta
  el propietario → al solicitante) y a los seguidores; **nunca al autor**. Solo dentro del sistema.
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**.
- **Resolución (Juani, 2026-11-12):** ✅ va la propuesta.

### Semana 3

#### DU-17 · Retiros y control por cantidad: una sola carga para las dos cosas
- **Fuente:** F-47 (retiros "por retiro, con el remito") · F-66 / W-48 ("lo carga administración con
  los remitos"; "lo retirado o pedido por fecha"; "fuera de presupuesto") · análisis de rendiciones
  (hoy la planilla de retiros por material entra al libro de gastos como **un solo renglón**) · principio
  de carga única (criterio global n.º 2).
- **Por qué importa:** si el retiro se carga en W-46 (importe para la rendición) **y** otra vez en W-48
  (cantidades por material), es doble carga. Además el documento no dice dónde se carga la lista de
  materiales presupuestados con sus cantidades.
- **Propuesta:** Administración carga **una vez** la lista de materiales del presupuesto (material,
  unidad, cantidad, precio) en W-48. Cada retiro se carga **una sola vez** con su remito, eligiendo
  materiales de esa lista y sus cantidades: suma en "retirado" de W-48 y resta en la rendición en su
  rubro. Lo que no está en la lista va a "fuera de presupuesto". Un renglón de factura de otro proveedor
  puede asociarse a un material de la lista para contar como "pedido".
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**.
- **Resolución (Juani, 2026-11-12):** ✅ va la propuesta.

#### DU-18 · HORAS LIQUIDADAS y FIJOS: ¿qué período?
- **Fuente:** F-54 ("un monto total por período") · análisis de rendiciones (hoja "Horas liquidadas"
  por quincena; hoja "Gastos fijos" aparte).
- **Por qué importa:** el formulario y los tests necesitan saber si el período es quincena, mes o libre.
- **Propuesta:** período **desde–hasta** (dos fechas), que sirve para quincenas (horas, como hoy) y
  meses (fijos), con un renglón por período y por obra en su rubro.
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**.
- **Resolución (Juani, 2026-11-12):** ✅ va la propuesta.

#### DU-19 · ¿Dónde se carga el "monto de la obra"?
- **Fuente:** F-54 / cap. 4.4 ("la rendición muestra también el monto de la obra, la ganancia y el %")
  · definición C19 ("monto asignado de cada presupuesto") · W-06, W-13 y W-21 no tienen ese campo.
- **Por qué importa:** sin el monto no hay ganancia ni %; y es un dato sensible (solo gerencias y
  administración) que no puede quedar en un formulario que ve operaciones.
- **Propuesta:** una fila **"Monto del presupuesto"** en W-13, por presupuesto, que solo ven y cargan
  quienes tienen "Monto de la obra y ganancia" (DU-01). Monto de la obra = suma de los montos.
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**.
- **Resolución (Juani, 2026-11-12):** ✅ va la propuesta.

### Semana 4

#### DU-20 · Carga inicial de la minuta: pedidos abiertos con datos faltantes
- **Fuente:** F-62 ("arranca con los pedidos abiertos de la minuta") · análisis de la minuta §1–§2
  (42 abiertos aprox.: No iniciada 17, En curso 16, Bloqueada 9; solicitante solo en el 22 %; fecha de
  pedido solo en 18 de 39 solapas; 101 sin prioridad; 8 sin estado; 110 sin propietario, casi todos
  documentos).
- **Por qué importa:** si se aplican las reglas de un pedido nuevo (DU-14), muchos abiertos reales no
  entran, y el arranque pierde justo lo que está en curso.
- **Propuesta:** los pedidos importados entran **marcados "carga inicial"** y con lo que tengan:
  solicitante vacío si no figura, fecha de pedido vacía si no figura, prioridad la de la minuta (o
  calculada si hay las dos fechas; si no, Normal). Los abiertos **sin fecha de entrega** o **sin
  propietario** se listan antes del arranque para que el sector los complete; los que son documentos
  (presupuesto, OC, certificado) van a Documentos de la obra y no a pedidos.
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**.
- **Resolución (Juani, 2026-11-12):** ✅ va la propuesta.

#### DU-21 · Carga inicial de obras en ejecución: obligatorios que faltan y rubros mal escritos
- **Fuente:** F-62 ("obras en ejecución con su gastado acumulado (un total por rubro)") · cap. 6 (contactos,
  link al presupuesto y responsable obligatorios) · cap. 9 (errores de tipeo como "INSUMO" en lugar de
  "INSUMOS").
- **Por qué importa:** las obras ya en marcha pueden no tener todos los obligatorios a mano, y el gastado
  acumulado sale de planillas con rubros mal escritos que hoy "no suman donde corresponde".
- **Propuesta:** las obras no se cargan incompletas: la semana 4 arranca con una planilla de faltantes
  por obra que completa administración. El gastado acumulado se mapea a la lista cerrada de rubros con
  una tabla de equivalencias ("INSUMO" → INSUMOS, EPP → EPP) que **revisa y aprueba administración**
  antes de importar; nada se mapea solo sin esa revisión.
- **¿Cambia lo que ve el cliente?** Sí (les pedimos completar y aprobar) → **decide Juani**.
- **Resolución (Juani, 2026-11-12):** ✅ va la propuesta.

---

## Analista funcional · especificaciones de la semana 1 (12-nov-2026)

> Dudas que aparecieron al escribir `especificacion/identidad.md`, `especificacion/obras.md` y
> `especificacion/proveedores.md`. Prefijo **DU-A** para no chocar con la numeración de QA. Donde QA
> ya abrió la duda, las especificaciones **siguen su propuesta** y acá solo se agrega lo que falta
> ("complementa DU-XX"). Ya cubiertas por QA y respetadas tal cual: DU-03, DU-04, DU-05 (salvo el
> tope de dígitos, DU-A16), DU-06, DU-07, DU-08, DU-09, DU-10, DU-11, DU-12, DU-19.

### Semana 1 · Identidad (usuarios, sectores y permisos)

#### DU-A01 · Nombres de los sectores y tres filas más en la tabla (complementa DU-01)
- **Fuente:** cap. 3 (sectores: "Gerencia de Operaciones · Jefatura de Proyectos · Ingeniería de
  proyectos", "Gerencia y Jefatura de Administración") · análisis del organigrama · W-40 (nombres
  cortos: "Operaciones", "Proyectos", "Administración") · F-21 (estado de la obra: "responsable de
  obra y administración") · F-66 (control por cantidad: "edita administración · ve operaciones") ·
  F-68 (plazos: "Gerencia") vs. F-60 (tabla: "Dirección y las dos gerencias").
- **Por qué importa:** DU-01 separa la Gerencia de Administración pero deja los nombres cortos de
  W-40. Además, tres cosas firmadas tienen un "quién" propio que no coincide con ninguna columna de
  W-15 ni de DU-01: cambiar el estado de la obra (administración, no todos los que editan la obra),
  el control por cantidad (no lo da a compras) y los plazos (gerencia, no Dirección). Si van dentro
  de otra columna, cambia quién puede hacerlas.
- **Propuesta:** **11 sectores** con los nombres del cap. 3 y del organigrama: Dirección · Calidad ·
  Gerencia de Operaciones · Jefatura de Proyectos · Ingeniería de proyectos · Seguridad e Higiene ·
  Gerencia de Administración · Jefatura de Administración · Compras · RRHH · Pañol y Logística
  (renombrables). **12 filas**: las 7 de W-15 + Equipos en reparación y Monto de obra y ganancia
  (DU-01) + **Estado de obra**, **Control por cantidad** y **Plazos de la prioridad**. Catálogo,
  qué habilita cada una y configuración inicial completa en `especificacion/identidad.md` §6.1–§6.3.
- **¿Cambia lo que ve el cliente?** Sí (nombres en W-40 y columnas en W-15) → **decide Juani**.
- **Resolución (Juani, 2026-11-12):** ✅ va la propuesta.

#### DU-A02 · Observaciones a DU-02 (arranque de las celdas "a completar")
- **Fuente:** W-17 ("[Ingeniería] No ve Proveedores ni Usuarios mientras la tabla de permisos no se
  los dé") · cap. 3 y F-61 ("Dirección ve todo").
- **Por qué importa:** DU-02 propone **Ver** Proveedores para Ingeniería de proyectos, lo contrario de
  lo que dibuja W-17 (firmado). Y las filas nuevas (DU-01, DU-A01) también necesitan valor el día 1.
- **Propuesta:** tomar DU-02 con dos ajustes: (1) Ingeniería de proyectos arranca **sin acceso** a
  Proveedores (W-17); Gerencia de Operaciones y Jefatura de Proyectos, **Ver** (como DU-02);
  (2) Dirección **Ver·todas** en todas las filas nuevas ("ve todo"). Lo que no tiene fuente arranca en
  **Sin acceso** y lo completa la empresa en W-15.
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**.
- **Resolución (Juani, 2026-11-12):** ✅ va la propuesta.

#### DU-A03 · RRHH: ¿carga gastos y lleva la rendición?
- **Fuente:** cap. 3, tabla de sectores ("Gerencia y Jefatura de Administración · Compras · **RRHH**:
  … Cargan gastos y facturas, y llevan la rendición") vs. cap. 3, configuración inicial ("Editan
  administración y compras") y W-15 (RRHH "a completar" en Gastos, Presupuesto y Rendición) · W-40
  (RRHH con 0 usuarios).
- **Por qué importa:** dos frases firmadas dicen cosas distintas y la migración necesita un valor.
- **Propuesta:** RRHH arranca **sin acceso** a Gastos, Presupuesto por rubro, Rendición y Proveedores
  (lo de la configuración inicial y W-15, que es lo específico). Hoy no tiene usuarios y la gerencia
  lo habilita desde W-15 cuando le den usuario.
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**.
- **Resolución (Juani, 2026-11-12):** ✅ va la propuesta.

#### DU-A04 · Ingeniería: "solo sus obras" también en Gastos, Presupuesto por rubro y Control por cantidad
- **Fuente:** cap. 3 ("las ingenieras ven la rendición **de sus obras**") · W-15 (Ingeniería: Gastos
  "Ver", Presupuesto por rubro "Ver", Rendición "Ver · sus obras") · F-53 ("cada ingeniera, las de sus
  obras") · ADR-006.
- **Por qué importa:** si la rendición se limita a sus obras pero Gastos cargados (W-33, filtrable por
  obra) y el presupuesto por rubro (W-13) muestran todas, la ingeniera ve por otro lado lo mismo que la
  rendición le oculta.
- **Propuesta:** Ingeniería de proyectos arranca en **Ver · solo sus obras** en las cuatro filas
  (Gastos y facturas, Presupuesto por rubro, Rendición, Control por cantidad). La gerencia la pasa a
  "todas" desde W-15 si quiere.
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**.
- **Resolución (Juani, 2026-11-12):** ✅ va la propuesta.

#### DU-A05 · Control por cantidad: ¿edita Compras?
- **Fuente:** F-66 ("Edita administración · ve operaciones") · cap. 3 ("Gastos, facturas y rendición:
  editan administración **y compras**") · W-48 se abre desde la rendición (W-11) · W-15 (Compras edita
  Gastos, Presupuesto y Rendición).
- **Por qué importa:** el control por cantidad es parte de la rendición, pero F-66 nombra solo a
  administración.
- **Propuesta:** Compras **edita** (igual que Gastos y Rendición; los retiros con remito son compras
  de material). Ver Gerencia de Operaciones y Jefatura de Proyectos; Ingeniería solo sus obras (DU-A04).
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**. No bloquea la semana 1 (es una celda).
- **Resolución (Juani, 2026-11-12):** ✅ va la propuesta.

#### DU-A06 · Con qué permisos nace un sector nuevo
- **Fuente:** W-40 ("Cada sector nuevo aparece como una fila más en la tabla de permisos") · cap. 3
  ("Todos pueden pedir y le pueden pedir a cualquiera") · cap. 6 (Obras, "Quién: Todos") ·
  `modelo-datos.md` (propone que nazca con todo en Sin acceso).
- **Por qué importa:** con todo en Sin acceso, la gente de un sector nuevo no puede ni cargar un pedido
  hasta que alguien toque la tabla, contra "todos pueden pedir".
- **Propuesta:** nace con **Pedidos Editar·todas**, **Equipos en reparación** igual que Pedidos (DU-01),
  **Obras Ver·todas** y el resto **Sin acceso**.
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani** (si se aprueba, ajustar `modelo-datos.md`).
- **Resolución (Juani, 2026-11-12):** ✅ va la propuesta.

#### DU-A07 · Sacar un sector que tiene gente o historia
- **Fuente:** F-59 / W-40 ("renombrar · sacar") · W-39 ("no se borra a nadie, para no perder el
  historial") · `modelo-datos.md` ("desactivar si tiene historia; borrar solo si nunca se usó").
- **Por qué importa:** el documento dice "sacar" pero no qué pasa con sus usuarios y con los pedidos
  que tienen ese sector.
- **Propuesta:** no se puede sacar un sector con **usuarios activos** ("Antes de sacar Pañol y
  Logística, pasá a sus 2 usuarios a otro sector."). Sin historia se borra; con historia (usuarios
  inactivos o pedidos) se **archiva**: sale de los desplegables y de la tabla, y lo histórico conserva
  su nombre.
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**.
- **Resolución (Juani, 2026-11-12):** ✅ va la propuesta.

#### DU-A08 · Desactivar a alguien que tiene gente a cargo u obras
- **Fuente:** W-39 ("Un usuario inactivo no entra, pero sus pedidos e historial quedan") · F-61 ·
  F-31 (el aviso de completado le llega al superior).
- **Por qué importa:** si se desactiva una jefatura, su gente queda sin superior (no reciben el aviso
  de completado) y sus obras con un responsable que no entra.
- **Propuesta:** se permite; antes de confirmar, la ventana avisa "Hernán tiene 2 personas a cargo y es
  responsable de 3 obras. Quedan como están hasta que las cambies." No se reasigna nada solo.
- **¿Cambia lo que ve el cliente?** Sí (un aviso en W-39) → **decide Juani**.
- **Resolución (Juani, 2026-11-12):** ✅ va la propuesta.

#### DU-A09 · Guardas para no quedarse sin administrador
- **Fuente:** F-58 / F-60 (usuarios y tabla los administran Dirección y las dos gerencias).
- **Por qué importa:** un cambio en la tabla, un cambio de sector o una baja pueden dejar el sistema
  sin nadie que pueda volver a darle permisos a alguien.
- **Propuesta:** nadie se desactiva a sí mismo, y se rechaza cualquier cambio que deje **0 usuarios
  activos** con Usuarios y permisos = Editar ("Tiene que quedar al menos una persona que pueda
  administrar usuarios y permisos.").
- **¿Cambia lo que ve el cliente?** No (solo un mensaje en un caso límite) → **decisión técnica**.
- **Resolución (Juani, 2026-11-12):** ✅ va la propuesta.

#### DU-A10 · Primer usuario en producción
- **Fuente:** roadmap (semana 1: "cargan … sus usuarios") · decisiones del Sprint 0 (catálogos por
  migración; ningún dato real en seeders ni en el repo).
- **Por qué importa:** con la base vacía nadie puede entrar a dar de alta a los demás.
- **Propuesta:** comando `php artisan identidad:crear-administrador {email} {nombre}` que crea un
  usuario en el sector Dirección y le manda el mail para elegir contraseña. Se corre una vez en el
  deploy con el mail que indique Juani; no queda escrito en el repo.
- **¿Cambia lo que ve el cliente?** No → **decisión técnica**.
- **Resolución (Juani, 2026-11-12):** ✅ va la propuesta.
  **13-nov (ADR-015):** el comando ya no manda mail; la persona entra pidiendo un código con su mail.

#### DU-A11 · ¿Se restringe el ingreso al dominio de mail de la empresa?
- **Fuente:** W-16 ("Se entra con el mail de la empresa").
- **Por qué importa:** si se valida el dominio, una persona con mail de otro dominio (un externo, un
  alias) no se puede dar de alta.
- **Propuesta:** no se valida el dominio: el alta la hace gerencia, que ya decide quién entra. Queda
  una variable opcional `MAIL_DOMINIOS_PERMITIDOS` por si se quiere después.
- **¿Cambia lo que ve el cliente?** No → **decisión técnica**.
- **Resolución (Juani, 2026-11-12):** ✅ va la propuesta.

#### DU-A12 · Semana 1 sin Pedidos, Gastos ni Rendición: qué se ve
- **Fuente:** cap. 14, roadmap (semana 1: ingreso, usuarios, obras, proveedores; "cargan dos o tres
  obras reales y sus usuarios") · F-04 (Inicio = Mis tareas, semana 2) · W-05 / W-07 (columnas
  "Pedidos abiertos" y "% gastado", fila "Servicios generales", pestañas Pedidos y Rendición).
- **Por qué importa:** si en la entrega de la semana 1 ven la barra completa, pantallas vacías y
  columnas en 0, pueden entender "no hay pedidos" o "no se gastó nada" en obras reales.
- **Propuesta:** la barra muestra solo lo entregado (Obras, Proveedores, Usuarios) aunque la tabla
  habilite el resto, con una lista de módulos habilitados por configuración (no por la tabla de
  permisos); **Inicio** abre Obras hasta la semana 2; en el listado "Pedidos abiertos" y "% gastado"
  muestran **"—"**; la fila "Servicios generales" no aparece; en la ficha no se muestran las pestañas
  Pedidos y Rendición. Cada entrega las enciende.
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**.
- **Resolución (Juani, 2026-11-12):** ✅ va la propuesta.

#### DU-A13 · Prioridad al cambiar la entrega de un pedido ya cargado (complementa DU-04)
- **Fuente:** W-32 ("Si cambia la entrega se recalcula la prioridad") · W-50 ("el cambio vale para los
  pedidos nuevos y no recalcula los ya cargados") · F-26 (quien carga la puede corregir a mano).
- **Por qué importa:** si gerencia cambió los plazos entre la carga y la edición, no está dicho con
  qué plazos se recalcula; y si la prioridad se había corregido a mano, si se pisa.
- **Propuesta:** al editar la entrega se recalcula con los plazos **vigentes en ese momento**; si la
  prioridad había sido **corregida a mano**, se mantiene la manual y el formulario muestra la
  calculada como sugerencia.
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**. No bloquea la semana 1.
- **Resolución (Juani, 2026-11-12):** ✅ va la propuesta.

### Semana 1 · Obras

#### DU-A14 · Obligatorios: tabla del cap. 6 vs. asteriscos de las maquetas (complementa DU-05)
- **Fuente:** cap. 6, "Datos de la obra" (link al presupuesto: Sí; contactos solicitante y comprador,
  nombre, mail y teléfono: Sí; presupuestos adicionales: No) · W-06 (sin asterisco en el link ni en
  los contactos) · W-21 (en el adicional solo el N.º lleva asterisco) · W-20 (adicional A1 con
  revisión "—").
- **Por qué importa:** el texto firmado y los asteriscos de las maquetas no coinciden; en un alta real
  puede faltar, por ejemplo, el teléfono del comprador, y el formulario no deja guardar.
- **Propuesta:** para el **principal** manda la tabla del cap. 6: link al presupuesto y los 6 datos de
  los dos contactos obligatorios (la revisión, obligatoria y precargada en REV01, según DU-05). En un
  **adicional**, solo el N.º es obligatorio (W-21); revisión, link, OC y descripción opcionales.
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**.
- **Resolución (Juani, 2026-11-12):** ✅ va la propuesta.

#### DU-A15 · Obra Terminada: ¿se sigue editando y admite pedidos y gastos nuevos? (complementa DU-12)
- **Fuente:** cap. 4.5 ("Sale del listado de activas pero no se pierde nada: se consulta igual") ·
  F-22 · F-65 (reabrir un pedido completado) · cap. 4.3 (las facturas llegan a administración ya
  aprobadas, a veces después del cierre) · análisis de la minuta ("certificado final").
- **Por qué importa:** después del cierre llegan facturas y el certificado final; si la obra Terminada
  queda de solo lectura, hay que volverla atrás para cargarlos (y DU-07 todavía no está decidida).
- **Propuesta:** una obra Terminada se puede **editar** y se le pueden cargar **pedidos y gastos**
  como a cualquier otra; Terminada solo la saca de las activas y de Rendiciones en curso. Las
  pantallas de carga muestran "Obra terminada" al lado del nombre para que se note.
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**.
- **Resolución (Juani, 2026-11-12):** ✅ va la propuesta.

#### DU-A16 · N.º de presupuesto: tope de dígitos y unicidad sin la revisión (complementa DU-05)
- **Fuente:** DU-05 ("número de 3 o más dígitos"; "3-115117-2026 se carga como dos presupuestos") ·
  `modelo-datos.md` (`presupuestos` único por `(numero, revision)`).
- **Por qué importa:** (a) con "3 o más dígitos" sin tope, `3-115117-2026` pasa como un número válido y
  no se cumple lo que la propia DU-05 propone; (b) único por número + revisión deja cargar
  2-131-2026 REV01 en una obra y 2-131-2026 REV02 en otra.
- **Propuesta:** el número del medio, de **1 a 4 dígitos**, se guarda con al menos 3 (`31` → `031`); la
  unicidad es por **número normalizado + sufijo**, sin la revisión (índice único sobre `numero`).
  Ajustar `modelo-datos.md`.
- **¿Cambia lo que ve el cliente?** No (afina la validación de DU-05) → **decisión técnica**.
- **Resolución (Juani, 2026-11-12):** ✅ va la propuesta.

#### DU-A17 · Borrar una obra, un presupuesto o un certificado cargado por error
- **Fuente:** F-16, F-18, F-19, F-20 (alta y edición; no hablan de borrar) · W-39 y W-34 (el criterio
  del documento es no borrar: usuario inactivo, gasto anulado).
- **Por qué importa:** en la semana 1 van a cargar obras reales de prueba y algo se va a cargar mal (un
  adicional en la obra equivocada, un certificado repetido).
- **Propuesta:** **obras y presupuestos principales no se borran** (se corrigen). Un **adicional** se
  puede quitar mientras no tenga montos por rubro cargados (ni materiales, desde la semana 3).
  **Certificados y links** se pueden quitar. Todo queda en el historial de la obra.
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**.
- **Resolución (Juani, 2026-11-12):** ✅ va la propuesta.

### Semana 1 · Proveedores

#### DU-A18 · Nombre del proveedor genérico
- **Fuente:** F-44 y cap. 4.3 ("como hoy, sin documento y con proveedor genérico") · W-11 (un gasto sin
  documento aparece con proveedor "—").
- **Por qué importa:** se crea por migración y es el nombre que van a ver en Gastos cargados, en las
  tablas por proveedor y en el filtro.
- **Propuesta:** **"Gastos sin comprobante"**, marcado "genérico" en la lista y no editable.
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**.
- **Resolución (Juani, 2026-11-12):** ✅ va la propuesta.

#### DU-A19 · Proveedores que ya están repetidos
- **Fuente:** cap. 10 ("Hoy el mismo proveedor aparece escrito de varias formas") · F-56 (alta y
  edición; no hay baja ni fusión) · F-62 (carga inicial) · DU-21 (tabla de equivalencias de rubros).
- **Por qué importa:** si la carga inicial trae el mismo proveedor escrito de dos formas, la base nace
  con duplicados y no hay cómo juntarlos.
- **Propuesta:** no hay fusión en la Etapa 1. La carga inicial (semana 4) entra **depurada** con una
  lista de equivalencias de proveedores que revisa y aprueba administración, igual que DU-21 para los
  rubros. Un duplicado que aparezca después y no tenga gastos se corrige renombrándolo.
- **¿Cambia lo que ve el cliente?** Sí (les pedimos aprobar la lista) → **decide Juani**.
- **Resolución (Juani, 2026-11-12):** ✅ va la propuesta.

### Semana 1 · Todos los listados

#### DU-A20 · Orden de los listados
- **Fuente:** W-05 (el ejemplo de "En ejecución" viene ordenado por % gastado: 104 %, 71 %, 55 %, 12 %)
  · W-12 ("ordenadas por % consumido") · W-14 y W-15 (listas en orden alfabético en el ejemplo).
- **Por qué importa:** el documento no lo dice y es lo primero que nota quien abre la pantalla.
- **Propuesta:** obras **En ejecución** por % gastado de mayor a menor (sin dato, al final por N.º);
  **Confirmadas** por fecha de alta, la más nueva primero; **Terminadas** por fecha en que se
  terminaron; **proveedores, usuarios y sectores** en orden alfabético.
- **¿Cambia lo que ve el cliente?** Sí (menor) → **decide Juani**.
- **Resolución (Juani, 2026-11-12):** ✅ va la propuesta.

#### DU-A21 · Ajustes al modelo de datos que piden estas especificaciones
- **Fuente:** `especificacion/identidad.md`, `obras.md` y `proveedores.md` vs. `modelo-datos.md`.
- **Por qué importa:** el DBA escribió el modelo en paralelo; hay diferencias que conviene cerrar antes
  de la primera migración.
- **Propuesta:** (1) `funciones`: las 12 filas del catálogo; `monto_obra` **admite editar** (lo carga
  administración, F-54; el modelo lo dejaba solo ver). (2) Certificados (`obra_documentos`) con
  **`fecha`** (W-20). (3) `presupuestos.created_by` (W-20 "Cargado: fecha · persona") y
  `modo_carga` (`sin_cargar`·`por_rubro`·`solo_total`). (4) `presupuestos` único por número
  normalizado, sin la revisión (DU-A16). (5) `proveedores.nombre_clave` (normalización suave, única,
  DU-10) además de `nombre_normalizado` (fuerte, para parecidos). (6) `obras.terminada_at` y
  `terminada_por`. (7) Sector nuevo con Pedidos y Obras habilitados (DU-A06).
- **¿Cambia lo que ve el cliente?** No → **decisión técnica** (DBA + Tech Lead).
- **Resolución (Juani, 2026-11-12):** ✅ va la propuesta.

---

## Juani · ampliación del 12-nov-2026

#### DU-23 · Archivos además de links
- **Fuente:** decisión D-022 del documento firmado ("los archivos siguen en Drive; el sistema guarda
  solo links") · F-16, F-18 (oferta técnica y OC/OT/contrato/mail) · F-19 (certificados y documentos de
  la obra) · F-27 (links del pedido) · F-31 (comprobante de cierre) · F-43, F-44 (factura y tickets) ·
  F-47, F-66 (remito de retiros) · F-67 (presupuesto e inventario de equipos) · pedido de Juani del 12-nov.
- **Por qué importa:** muchas veces el documento no está en el Drive: la foto del remito que se saca con
  el celular al completar un pedido, o el PDF de una factura que nadie escaneó. Con "solo links" hay que
  subirlo primero al Drive para poder cargarlo, y eso frena la carga.
- **Propuesta:** en cada uno de esos lugares se puede **subir el archivo o poner el link**, según
  convenga, con un solo mecanismo transversal (tabla `adjuntos`, disco privado, descarga autorizada por
  el recurso dueño, tipos y tamaño validados en el borde). La **carpeta de Drive de la obra** sigue
  siendo solo link. Detalle en [ADR-014](adr/ADR-014-adjuntos-archivo-o-link.md) y `modelo-datos.md`.
- **¿Cambia lo que ve el cliente?** Sí (amplía D-022 a su favor: en cada uno de esos campos aparecen
  "Subir archivo" y "Pegar link") → **decide Juani**.
- **Resolución (Juani, 2026-11-12):** ✅ va la propuesta (ADR-014 aceptada). Se lo comunica Juani al
  cliente como ampliación de D-022. Historia transversal: HU-025 en el backlog.

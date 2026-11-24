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
> **16-nov-2026: dudas nuevas abiertas de la semana 2 (Pedidos):** DU-Q01…DU-Q03 (PO + QA, al bajar
> las historias) y DU-P01…DU-P24 (Analista, al escribir `especificacion/pedidos.md` y `avisos.md`). Están al
> final, con su propuesta; decide Juani. Las que tocan la prueba del cliente de la semana 2: DU-P01, DU-P02,
> DU-P05, DU-P08, DU-P19, DU-P21 y DU-Q01.
>
> **19-nov-2026: las 27 resueltas.** Juani aprobó todas las propuestas tal como están escritas (DU-Q01…DU-Q03 y
> DU-P01…DU-P24, la parte (b) de DU-P10 incluida). Las técnicas (DU-P10 (a), DU-P16, DU-P17, DU-P20 y DU-P22) van
> con su propuesta por decisión del Tech Lead. No queda ningún criterio ⛔ en el [backlog](backlog.md).
>
> **24-nov-2026: dudas de la semana 3 (Gastos y rendición), las 20 resueltas.** Juani aprobó todas las propuestas
> (DU-G01…DU-G11 y DU-R01…DU-R09) con dos reemplazos del cruce entre las dos especificaciones: **DU-G08 queda
> reemplazada por DU-R05** (permisos de la tabla aprobada) y **DU-R03 queda reemplazada por DU-G05** (no hay nota de
> crédito en la Etapa 1). Períodos superpuestos (DU-G09, DU-R04): avisan y guardan si se confirma. Contrato único
> Gastos → Rendición: `Gastos\Contracts\GastadoDeObras`; Rendición ofrece `MaterialesDeObra` y `CatalogoDeRubros`.
> Detalle en "Cruce Gastos ⇄ Rendición", más abajo. No queda ningún criterio ⛔ en el [backlog](backlog.md).
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

---

## PO + QA · semana 2, al bajar Pedidos a historias (16-nov-2026)

Dudas que aparecieron al escribir los criterios de HU-026…HU-060 ([backlog](backlog.md) §6). No
re-preguntan nada resuelto (DU-04, DU-12…DU-16, DU-22, DU-A07, DU-A08, DU-A13, DU-A15 y DU-A17 se
aplican tal cual) **ni lo que el Analista ya abrió en paralelo** (DU-P01…DU-P24, más abajo): las
historias citan esas DU-P directamente. Quedan acá solo las **3 que no están en ninguna otra**.
Todas abiertas. Cada historia se construye con la propuesta y el criterio afectado queda marcado ⛔
en el backlog hasta la decisión.

Dudas que se encontraron al bajar las historias y **ya están abiertas por el Analista** (se responden
allá, una sola vez): sector y obra en Editar → DU-P05 · fecha de pedido futura → DU-P08 · qué avisos
van por mail y "vence mañana" → DU-P19 · hora del recordatorio → DU-P21 · Sin acceso y lo propio →
DU-P01 · pedido en el sector: quién completa → DU-P02 · bloquear sin tomar → DU-P03 · estado al
reasignar → DU-P04 · prioridad manual al editar → DU-P07 · accesos de W-22 → DU-P12 · vencido y "esta
semana" → DU-P14 · texto del buscador → DU-P15 · conteo con alcance propios → DU-P17 · equipos
(obligatorios, orden, borrado) → DU-P18 · propietario inactivo → DU-P09 · pedido cargado por error →
DU-P06 · bloque "varias obras" de W-25 → DU-P13 · seguidores después de crear → DU-P11 · comentario de
un tercero → DU-P23 · la edición no avisa → DU-P24.

#### DU-Q01 · Recordatorio de una entrega del lunes: ¿el domingo? — ✅ resuelta (19-nov-2026)
- **Decisión (Juani, 19-nov-2026):** va la propuesta tal como está escrita.
- **Fuente:** F-37 y cap. 4.2 ("un día antes de la fecha de entrega") · DU-04 (la prioridad va en días
  corridos, sin feriados) · DU-P21 (la hora: desde las 8:00) · criterio global n.º 3 ("ningún pedido
  se atrasa sin que el propietario se entere **antes**").
- **Por qué importa:** con días corridos, un pedido para el lunes se recuerda el **domingo**, que nadie
  mira: en la práctica el propietario se entera el lunes, el mismo día de la entrega, que es justo
  lo que el criterio n.º 3 quiere evitar. DU-P21 fija la hora pero no el día.
- **Propuesta:** **días corridos**, igual que la prioridad (DU-04) y sin feriados: un lunes se recuerda
  el domingo desde las 8:00, y el lunes el pedido igual aparece en Mis tareas como "hoy" (sin
  agregar avisos que el documento no tiene). Si prefieren "el día hábil anterior" (viernes para un
  lunes), es un cambio de regla que el documento no menciona y lo tendrían que pedir.
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**.
- **Afecta:** HU-048 criterio 6.

#### DU-Q02 · Sacar un sector, o desactivar a su última persona activa, con pedidos "en el sector" — ✅ resuelta (19-nov-2026)
- **Decisión (Juani, 19-nov-2026):** va la propuesta tal como está escrita.
- **Fuente:** DU-A07 (con usuarios activos no se saca; con historia se archiva) · DU-A08 (desactivar a
  quien tiene gente u obras: se avisa y no se reasigna nada solo) · DU-16 (un pedido en el sector sin
  persona les llega a las personas activas del sector) · DU-P02 (no se puede **dejar** un pedido en
  un sector sin usuarios activos) · HU-007 (este caso quedó para la semana 2).
- **Por qué importa:** DU-P02 evita dejar un pedido en un sector vacío, pero no cubre el camino
  inverso: un sector que **ya** tiene pedidos en el sector y se queda sin gente (se saca el sector, o
  se desactiva a su única persona activa). Esos pedidos no le avisan a nadie y no tienen
  recordatorio: quedan escondidos, como hoy en las solapas ocultas.
- **Propuesta:** (1) no se saca un sector con pedidos abiertos en el sector sin persona: "Antes de
  sacar {sector}, reasigná sus N pedidos que están en el sector." (422), igual que con los usuarios
  activos; (2) al desactivar a la **última** persona activa de un sector con pedidos en el sector, la
  ventana avisa "{sector} se queda sin nadie y tiene N pedidos en el sector. Reasignalos." y se
  permite igual (como DU-A08, no se reasigna nada solo).
- **¿Cambia lo que ve el cliente?** Sí (un mensaje en W-40 y otro en W-39) → **decide Juani** (menor).
- **Afecta:** HU-058 criterios 1 y 3.

#### DU-Q03 · Equipos en reparación: ¿qué fecha es la columna "Fecha"? — ✅ resuelta (19-nov-2026)
- **Decisión (Juani, 19-nov-2026):** va la propuesta tal como está escrita.
- **Fuente:** W-49 (columna "Fecha" sin aclarar; "para qué sirve: … y **cuándo vuelve**"; en el ejemplo
  las notas dicen "Lista para retirar desde el 18-oct") · su solapa de hoy ("fechas") ·
  `modelo-datos.md` (`equipos_reparacion.fecha`, una sola) · DU-P18 (obligatorios, orden y borrado;
  no define la fecha y ordena "por fecha más reciente").
- **Por qué importa:** si "Fecha" es cuándo fue al taller o cuándo vuelve, cambia lo que cargan y el
  orden de DU-P18 pone arriba cosas distintas.
- **Propuesta:** "Fecha" = **cuándo se mandó al taller** (opcional), como la usa el ejemplo de W-49;
  "cuándo vuelve" se anota en Notas, como en la maqueta ("Lista para retirar desde el 18-oct"). No se
  agrega una segunda fecha.
- **¿Cambia lo que ve el cliente?** Sí (el rótulo de una columna) → **decide Juani** (menor).
- **Afecta:** HU-050 criterio 8.

#### DU-Q04 · ¿La "ñ" cuenta como "n" para el proveedor repetido? — ✅ resuelta (Juani, 16-nov-2026)
- **Fuente:** PU-043 (prueba de usuario, semana 1) · RN-PRV-04 (cada proveedor se carga una vez) ·
  RN-PRV-08 (aviso de parecidos).
- **Por qué importa:** "Corralón Ñandú" y "Corralon Nandu" pueden ser dos proveedores distintos; con la
  ñ tomada como n el segundo no se podía cargar.
- **Resuelto:** para el **repetido** la ñ **no** es n (son dos proveedores distintos); el segundo aparece en
  "Parecidos ya cargados" (aviso que deja guardar). Para **buscar** la ñ sigue contando como n: "nandu"
  encuentra "Ñandú". La clave única (`nombre_clave`) conserva la ñ y la columna pasa a `utf8mb4_bin`
  (migración `2026_11_16_100000`).
- **Afecta:** RN-PRV-03, RN-PRV-04 (`especificacion/proveedores.md`).

#### DU-Q05 · Buscar proveedores por CUIT — ✅ resuelta (Juani, 16-nov-2026)
- **Fuente:** PU-044 (el buscador muestra el CUIT pero no encuentra por él) · RN-PRV-03 · F-03.
- **Resuelto:** el buscador de proveedores (listado W-14 y grupo Proveedores del buscador general) también
  encuentra por **CUIT**, con o sin guiones, espacios o puntos, cuando lo escrito tiene **al menos 4
  dígitos** y nada más. Por rubro **no** se busca.
- **Afecta:** RN-PRV-03 (`especificacion/proveedores.md`).

#### DU-Q06 · Ingreso bloqueado: ¿cómo se entera la persona y quién la destraba? — ✅ resuelta (Juani, 16-nov-2026)
- **Fuente:** PU-045 · ADR-015 (enmienda 2026-11-13, bloqueo progresivo) · RN-IDE-49.
- **Por qué importa:** la persona bloqueada espera un código que nunca llega y gerencia no tenía cómo
  ver ni quitar el bloqueo.
- **Resuelto:** (1) pedir código sigue **sin revelar** el bloqueo (ADR-015); la pantalla de ingreso suma la
  ayuda "¿No te llegó? Si probaste varias veces, esperá una hora o pedile a gerencia que lo revise." (front);
  (2) en Usuarios se ve si una persona está bloqueada y hasta cuándo (`bloqueo_ingreso`), y quien tiene
  `usuarios_permisos = editar` lo quita con `DELETE /api/usuarios/{id}/bloqueo-ingreso` ("Destrabamos el
  ingreso de {nombre}."), que queda en la actividad del usuario (`bloqueo_ingreso_quitado`).
- **Afecta:** RN-IDE-50 (`especificacion/identidad.md`), ADR-015 (enmienda 2026-11-16).

Nota para el Arquitecto (no es duda, es una inconsistencia de documento): `arquitectura.md` §4.2 dice
que completar valida `en_curso|bloqueada → completada`; lo resuelto es **desde No iniciada o En curso**
y desde Bloqueada primero "Retomar" (DU-15; spec `pedidos.md`). Hay que corregir ese párrafo.

---

## Analista funcional · especificaciones de la semana 2 (16-nov-2026)

> Dudas que aparecieron al escribir `especificacion/pedidos.md` y `especificacion/avisos.md`. Prefijo **DU-P**.
> No re-abren las resueltas (DU-12…DU-16, DU-20, DU-22, DU-A13, DU-A15): las **complementan** donde se dice.
> Las reglas que dependen de estas dudas dicen "propuesta DU-PXX" en la especificación. **Todas abiertas.**

### Semana 2 · Pedidos

#### DU-P01 · Pedidos con la tabla restringida: qué alcanza con Ver, a quién se le puede asignar y qué pasa con lo propio — ✅ resuelta (19-nov-2026)
- **Decisión (Juani, 19-nov-2026):** va la propuesta tal como está escrita.
- **Fuente:** `identidad.md` §6.1 ("No son filas: Inicio / Mis tareas / Pedidos que pedí / Tareas que sigo") y
  RN-IDE-39 (el propietario toma, bloquea y completa; quien lo pidió reabre: reglas fijas) vs. RN-IDE-35 ("Sin
  acceso oculta la función del menú y responde 403") · cap. 3 ("le pueden pedir a cualquiera").
- **Por qué importa:** si un sector queda en Sin acceso pero alguien le asigna un pedido a una persona de ese
  sector, con 403 esa persona no puede ni abrir su tarea: el pedido se pierde (criterio de aceptación n.º 3).
- **Propuesta:** (1) con **Ver** se ve, **se comenta y se sigue** (F-30 "quien lo ve", F-07 "cualquiera"; ajusta
  `identidad.md` §6.1, que ponía comentar y seguir en Editar); crear, editar, reasignar y reabrir piden **Editar**;
  tomar, bloquear, retomar y completar son del **propietario**, con Ver o Editar. (2) El desplegable de propietario
  (al crear y al reasignar) solo ofrece personas de sectores con Pedidos ≥ Ver. (3) Si la tabla le saca el acceso a
  un sector **después** de asignarle pedidos, **lo propio se sigue viendo y resolviendo** (Mis tareas, Pedidos que
  pedí, Tareas que sigo y sus detalles, que no son filas de la matriz). (4) Con alcance "propios", la columna
  "pedidos abiertos" y la fila Servicios generales del listado de obras cuentan solo lo que esa persona puede ver
  (cómo se implementa: DU-P17). Esta propuesta reúne lo que PO + QA había escrito en paralelo para HU-026, HU-029,
  HU-030, HU-032 y HU-055.
- **¿Cambia lo que ve el cliente?** Sí (solo si alguna vez ponen un sector en Sin acceso) → **decide Juani**.

#### DU-P02 · Pedido en el sector: quién bloquea, completa, reasigna y comenta (complementa DU-16) — ✅ resuelta (19-nov-2026)
- **Decisión (Juani, 19-nov-2026):** va la propuesta tal como está escrita.
- **Fuente:** DU-16 ("lo puede tomar cualquiera de ellas y, al tomarlo, pasa a ser su propietario") · W-31 ("lo
  toma el primero que pueda") · F-29, F-31, F-32 (acciones del propietario) · DU-22 (comentario "a la otra parte").
- **Por qué importa:** DU-16 solo dice "tomar". Sin propietario no está claro quién puede completar directo (DU-15
  lo permite al propietario), bloquear o pasarlo a otra persona, ni a quién le llega el comentario del solicitante.
  Además, dejarlo en un sector **sin usuarios activos** (hoy Pañol y Logística) lo deja sin nadie que se entere.
- **Propuesta:** mientras está en el sector, **cualquier persona activa del sector puede hacer lo que haría el
  propietario** (tomar, bloquear, completar, reasignar) y la primera que lo hace **queda como propietaria**; el
  comentario de quien lo pidió avisa a todas las personas activas del sector. No se puede dejar un pedido en un
  sector sin usuarios activos ("Pañol y Logística no tiene usuarios activos: elegí una persona.").
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**.

#### DU-P03 · ¿Se puede bloquear un pedido que todavía no se tomó? — ✅ resuelta (19-nov-2026)
- **Decisión (Juani, 19-nov-2026):** va la propuesta tal como está escrita.
- **Fuente:** cap. 4.2 ("Lo toma: pasa a En curso… Si algo lo traba, se marca Bloqueada") · cap. 7 estados ·
  análisis de la minuta §7 (hoy cambian el desplegable directo; "esperando presupuesto") · DU-15 (completar desde
  No iniciada) · W-30 ("Retomar → vuelve a En curso").
- **Por qué importa:** muchas veces lo que traba el pedido aparece antes de empezar (falta un dato); obligar a
  "tomar" y después "bloquear" agrega un paso y un aviso de "tomado" que no corresponde.
- **Propuesta:** el propietario puede **bloquear desde No iniciada o En curso**, siempre con motivo; al **Retomar**
  pasa a En curso (W-30), sin importar desde dónde se bloqueó. Igual criterio que DU-15.
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**.

#### DU-P04 · Al reasignar, ¿cambia el estado? — ✅ resuelta (19-nov-2026)
- **Decisión (Juani, 19-nov-2026):** va la propuesta tal como está escrita.
- **Fuente:** W-31 ("Hoy cambian el propietario a mano en la minuta"; "La ida y vuelta anterior no se pierde") ·
  F-32 · cap. 7 estados.
- **Por qué importa:** un pedido En curso o Bloqueado que pasa a otra persona puede quedar "En curso" sin que el
  nuevo propietario lo haya tomado, o volver a No iniciada y obligarlo a tomarlo.
- **Propuesta:** a **una persona**, el estado **se mantiene** (como hoy, que solo cambian el nombre; un Bloqueado sigue
  Bloqueado con su motivo y el nuevo propietario lo retoma); **al sector**, vuelve a **No iniciada** para que lo
  "tome" quien pueda (DU-16), y el estado anterior queda en el recorrido. El comentario opcional de W-31 queda en el
  hilo de comentarios y viaja en el aviso.
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**.

#### DU-P05 · Editar (W-32): el sector y la obra — ✅ resuelta (19-nov-2026)
- **Decisión (Juani, 19-nov-2026):** va la propuesta tal como está escrita.
- **Fuente:** W-32 (muestra "Sector Compras ▾" editable y no muestra la obra; "Se pueden cambiar fechas, texto,
  links y aclaraciones") · W-31 (reasignar a persona o sector, con aviso) · F-38 (persona y su sector).
- **Por qué importa:** (a) cambiar el sector desde Editar dejaría al propietario en un sector que no es el suyo y
  sin aviso, o haría lo mismo que Reasignar por otro camino; (b) un pedido cargado en la obra equivocada (error
  común) no tiene cómo corregirse si la obra no se edita.
- **Propuesta:** (a) en W-32 el **sector se ve sin desplegable**; para cambiar sector o persona está **Reasignar**
  (W-31), que es el que avisa. (b) La **obra se puede corregir en W-32** (también a "Sin obra"), con registro en el
  historial ("Obra: Piso industrial nave 3 → Reparación de losa tanque B").
- **¿Cambia lo que ve el cliente?** Sí (dos detalles de W-32) → **decide Juani**.

#### DU-P06 · Pedido cargado por error — ✅ resuelta (19-nov-2026)
- **Decisión (Juani, 19-nov-2026):** va la propuesta tal como está escrita.
- **Fuente:** F-24…F-38 (no hablan de borrar) · criterio del documento de no borrar (W-39 usuario inactivo, W-34
  gasto anulado) · DU-A17 (obras y principales no se borran) · D-030 ("todo el recorrido queda registrado").
- **Por qué importa:** en la semana de uso en paralelo con la minuta se van a cargar pedidos repetidos o de prueba.
- **Propuesta:** **no se borran pedidos**: uno cargado por error se **completa** con el comentario "Cargado por
  error" (queda en el recorrido y fuera de Mis tareas), o se **reasigna** si era para otra persona. Sin estado nuevo
  ni botón de borrar.
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**.

#### DU-P07 · Prioridad corregida a mano: quién la cambia al editar y cómo vuelve a la calculada (complementa DU-A13) — ✅ resuelta (19-nov-2026)
- **Decisión (Juani, 19-nov-2026):** va la propuesta tal como está escrita.
- **Fuente:** F-26 ("Quien carga el pedido la puede corregir a mano") · W-32 ("Prioridad (sola) Normal 11 días";
  "Si cambia la entrega se recalcula") · DU-A13 (la manual se mantiene y se muestra la calculada como sugerencia).
- **Por qué importa:** cualquiera con Pedidos = Editar puede editar (F-33); si cualquiera puede pisar la prioridad
  manual, deja de ser la de quien cargó. Y DU-A13 no dice cómo volver a la automática. Además la prioridad depende
  de dos fechas y W-32 permite cambiar también la de pedido.
- **Propuesta:** la corrige **solo quien lo pidió**, al crear o al editar; si elige la misma que la calculada no
  queda manual; con una manual, W-32 muestra "Calculada: Normal · 11 días" y el botón **"Usar la calculada"**. Se
  recalcula si cambia la entrega **o la fecha de pedido**.
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**.

#### DU-P08 · Fechas del pedido: ¿qué combinaciones se aceptan? — ✅ resuelta (19-nov-2026)
- **Decisión (Juani, 19-nov-2026):** va la propuesta tal como está escrita.
- **Fuente:** F-25 ("se completa sola con el día de carga y se puede corregir (para pedidos que llegaron antes por
  mail)").
- **Por qué importa:** una fecha de pedido futura (error de tipeo) achica la anticipación y da una prioridad
  equivocada; F-25 solo prevé corregir hacia atrás.
- **Propuesta:** (1) la fecha de pedido **no puede ser posterior a hoy** ("La fecha de pedido no puede ser posterior a
  hoy."); (2) la **entrega** puede ser hoy o ya pasada (un pedido que se carga tarde) y queda vencido; (3) una entrega
  **anterior a la fecha de pedido** se acepta y queda **Urgente**, como ya dice RN-IDE-41 (aprobada): se descarta el
  test de la matriz de semana 1 que la rechazaba (`test_entrega_anterior_a_fecha_de_pedido_es_rechazada`); (4) el fin
  del período no puede ser anterior al inicio.
- **¿Cambia lo que ve el cliente?** Sí (mensajes y qué se puede guardar) → **decide Juani**.

#### DU-P09 · Propietario inactivo con pedidos abiertos — ✅ resuelta (19-nov-2026)
- **Decisión (Juani, 19-nov-2026):** va la propuesta tal como está escrita.
- **Fuente:** W-39 ("Un usuario inactivo no entra, pero sus pedidos e historial quedan") · RN-IDE-24 / DU-A08
  (obras y gente a cargo "quedan como están") · F-65 (reabrir avisa al propietario) · F-37 · criterio n.º 3.
- **Por qué importa:** los pedidos de alguien que se fue no le avisan a nadie, y si se reabre uno que completó,
  vuelve a una persona que no entra.
- **Propuesta:** como DU-A08: al desactivar, la ventana de W-39 avisa "Carla tiene 4 pedidos abiertos. Quedan como
  están hasta que los reasignes." y no se reasigna nada solo; esos pedidos se ven con "Carla (inactiva)" en Todos los
  pedidos y en Tareas de mi gente de su jefatura. **Reabrir** un pedido cuyo propietario está inactivo **pide elegir
  a quién pasárselo en el mismo paso** (persona o sector) y el aviso va al nuevo propietario.
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**.

#### DU-P10 · Carga inicial: pedidos sin solicitante y ajustes al modelo (complementa DU-20) — ✅ resuelta (19-nov-2026)
- **Decisión (19-nov-2026):** (a) técnica, Tech Lead: va la propuesta; (b) Juani: va la propuesta.
- **Fuente:** DU-20 ("solicitante vacío si no figura, fecha de pedido vacía si no figura", "marcados carga
  inicial") · análisis de la minuta §1 (solicitante en el 22 %) · F-65 (reabre "quien lo pidió") ·
  `modelo-datos.md` (`solicitante_id` y `fecha_pedido` obligatorios, sin columna de carga inicial).
- **Por qué importa:** (a) el modelo planificado no admite lo que DU-20 aprobó; (b) un pedido sin solicitante no
  tiene quién lo reabra ni a quién avisar.
- **Propuesta:** (a) **técnico**: `solicitante_id` y `fecha_pedido` nullable (solo con `carga_inicial = true`),
  columna `carga_inicial`, `completado_por` FK a users. (b) Un pedido sin solicitante lo **reabre cualquiera que lo
  vea con Pedidos = Editar** (como hoy en la minuta, donde cualquiera cambia el desplegable) y no genera avisos al
  solicitante ni al superior.
- **¿Cambia lo que ve el cliente?** (a) No → **decisión técnica** (DBA + Tech Lead). (b) Sí → **decide Juani**.

#### DU-P11 · Seguidores después de crear el pedido — ✅ resuelta (19-nov-2026)
- **Decisión (Juani, 19-nov-2026):** va la propuesta tal como está escrita.
- **Fuente:** F-38 ("si hace falta otra persona, se la suma como seguidora") · W-08 ("Seguidores (opcional) +
  Agregar seguidor" al crear) · W-28 ("Botón 'Seguir' en cualquier pedido y 'dejar de seguir' desde acá") · W-09 y
  W-32 (no tienen "agregar seguidor") · tabla de avisos del cap. 7 (no hay aviso de "te sumaron").
- **Por qué importa:** no está dicho si quien pidió (o el propietario) puede sumar a otra persona después, ni si a
  la persona sumada le llega algo.
- **Propuesta:** a otra persona se la suma **solo al crear**; después, cada uno se suma o se saca **a sí mismo** con
  "Seguir" / "dejar de seguir". Al sumarse no se avisa (el pedido aparece en su "Tareas que sigo").
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**.

#### DU-P12 · Pasar la obra a Terminada (W-22): ¿quien cierra completa o reasigna pedidos ajenos? (complementa DU-12) — ✅ resuelta (19-nov-2026)
- **Decisión (Juani, 19-nov-2026):** va la propuesta tal como está escrita.
- **Fuente:** W-22 ("completar · reasignar" en cada pedido abierto; "Cambian el estado el responsable de obra y
  administración") · F-31 (completa el propietario) · F-32 (reasignan el propietario y quien lo pidió) · DU-12
  (no frena).
- **Por qué importa:** quien cambia el estado (responsable o administración) casi nunca es el propietario de todos
  los pedidos abiertos; si los accesos le permiten completarlos, se saltean F-31/F-32.
- **Propuesta:** los accesos **abren W-29 y W-31 con sus reglas de siempre**: "completar" solo si quien mira es el
  propietario, "reasignar" si es el propietario o quien lo pidió; a los demás, "abrir el pedido". Como DU-12 no frena, la obra se pasa a
  Terminada igual.
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**.

#### DU-P13 · W-25: "Pedidos que sirven a varias obras" como sección aparte — ✅ resuelta (19-nov-2026)
- **Decisión (Juani, 19-nov-2026):** va la propuesta tal como está escrita.
- **Fuente:** F-36 ("Los de varias obras de un mismo cliente van acá, nombrando las obras en la tarea") · W-25 (dos
  tablas: pedidos sin obra y "Pedidos que sirven a varias obras").
- **Por qué importa:** para separarlos en otra tabla el sistema necesitaría una marca nueva ("sirve a varias
  obras") que F-36 no pide: el documento dice que se reconocen porque nombran las obras en la tarea.
- **Propuesta:** **una sola lista** de pedidos sin obra (la segunda tabla de W-25 queda como explicación); el
  buscador los encuentra por el nombre de la obra escrito en la tarea.
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**.

#### DU-P14 · Vencido, "esta semana" y orden de las vistas de jefatura — ✅ resuelta (19-nov-2026)
- **Decisión (Juani, 19-nov-2026):** va la propuesta tal como está escrita.
- **Fuente:** F-04 / W-01 ("vencidos marcados"; ejemplo con hoy 19-oct: "16-oct · vencido", "20-oct · mañana") ·
  F-12 / W-04 ("vencidas y las que vencen esta semana"; "semana del 19 al 25-oct") · W-26 ("con abiertas y vencidas
  arriba"; incluye "Hernán (yo)") · W-03 (semana de lunes a domingo).
- **Por qué importa:** son números que la gerencia va a mirar; sin definición, "vencidas" y "esta semana" se
  pueden contar dos veces o distinto en cada pantalla.
- **Propuesta:** **vencido** = abierto con entrega **antes de hoy** (uno que vence hoy no está vencido); **semana**
  = lunes a domingo; **vencen esta semana** = abiertas con entrega de **hoy al domingo** (sin las vencidas).
  W-04: personas de mi gente **y yo**, ordenadas por "vencen esta semana" y después vencidas; la barra = "vencen
  esta semana" en proporción a la persona con más. W-26: incluye **"(yo)"**, grupos por vencidas y después
  abiertas, y también personas sin tareas abiertas ("sin tareas abiertas"). Calendario "Mi gente": sin incluirme.
- **¿Cambia lo que ve el cliente?** Sí (menor) → **decide Juani**.

#### DU-P15 · Buscador: ¿en qué texto del pedido busca? — ✅ resuelta (19-nov-2026)
- **Decisión (Juani, 19-nov-2026):** va la propuesta tal como está escrita.
- **Fuente:** F-03 ("pedidos por texto") · W-18 ("texto del pedido") · análisis de la minuta §4 (el proveedor se
  escribe en la columna "PROVEEDOR", que acá es "con quién").
- **Por qué importa:** si solo busca en la tarea, "Corralón Centro" no encuentra el pedido que lo tiene en "con
  quién"; si busca en comentarios, trae ruido.
- **Propuesta:** en **tarea, con quién / para quién y aclaraciones**; no en los comentarios. Abiertos primero. Los
  pedidos de una obra encontrada por su nombre **no** se suman al grupo Pedidos (se llega desde la obra → pestaña
  Pedidos); así se lee el ejemplo de W-18, donde "tanque" trae pedidos de "Reparación de losa tanque B".
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**.

#### DU-P16 · Exportar a Excel (W-24): qué columnas y cuánto — ✅ resuelta (19-nov-2026)
- **Decisión (técnica, Tech Lead, 19-nov-2026):** va la propuesta tal como está escrita.
- **Fuente:** W-24 ("Exportar lo filtrado a Excel") · F-35.
- **Por qué importa:** el documento no dice columnas, formato ni si baja la página o todo.
- **Propuesta:** `.xlsx` con **todo lo filtrado** (sin paginar), en el orden de la tabla y respetando el alcance;
  columnas de la tabla + N.º de presupuesto de la obra, Con quién / para quién, Período, Aclaraciones y fecha de
  Completado; nombre `pedidos-AAAA-MM-DD.xlsx`. La librería la elige el Tech Lead.
- **¿Cambia lo que ve el cliente?** No (detalle de un archivo aprobado) → **decisión técnica**.

#### DU-P17 · "Pedidos abiertos" del listado de obras con alcance "propias" — ✅ resuelta (19-nov-2026)
- **Decisión (técnica, Tech Lead, 19-nov-2026):** va la propuesta tal como está escrita.
- **Fuente:** RN-OBR-03, RN-OBR-08 · contrato `Obras\Application\Ports\PedidosDeObra::contarAbiertosPorObra(ids)`
  (no recibe al usuario) · ADR-006.
- **Por qué importa:** si un sector tiene Pedidos con alcance "propias", el número de la columna podría contar
  pedidos que esa persona no puede abrir.
- **Propuesta:** el número cuenta solo lo que la persona puede ver (DU-P01 punto 4) y se implementa **sin cambiar la
  firma del puerto** que ya está en `main`: `PedidosDeObraEloquent` aplica el alcance del usuario autenticado adentro
  (con alcance "todas" no cambia nada).
- **¿Cambia lo que ve el cliente?** No → **decisión técnica**.

#### DU-P18 · Equipos en reparación: obligatorios, cambios, borrado y orden — ✅ resuelta (19-nov-2026)
- **Decisión (Juani, 19-nov-2026):** va la propuesta tal como está escrita.
- **Fuente:** F-67 · W-49 (columnas; "+ Agregar equipo"; "la marca una persona, no el sistema") · análisis de la
  minuta §9 (solapa de 26 filas con presupuesto en link) · ADR-014 (`equipo_presupuesto`, `equipo_inventario`).
- **Por qué importa:** el documento no dice qué es obligatorio, si estado y aprobación siguen un orden, si se borra
  un equipo ni cómo se ordena la lista.
- **Propuesta:** obligatorios **equipo y falla o trabajo**; estado y aprobación **se cambian libremente** (como la
  planilla; la aprobación no frena nada); uno **cargado por error se puede quitar mientras siga No iniciada**
  (queda en actividades); después se corrige y lo resuelto queda Completada; orden: los que no están Completada
  primero, por fecha (el más viejo arriba: la "Fecha" es cuándo se mandó al taller, DU-Q03), los Completada al final; el **presupuesto del taller** se carga
  como archivo o link en el formulario (W-49 no tiene columna: se ve en el detalle de la fila).
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**.

#### DU-P20 · Selector de obras al cargar un pedido — ✅ resuelta (19-nov-2026)
- **Decisión (técnica, Tech Lead, 19-nov-2026):** va la propuesta tal como está escrita.
- **Fuente:** W-08 ("Obra * ▾") · `GET /api/obras` exige Obras ≥ Ver (obras.md §7) · `ObrasConsulta` (sin
  búsqueda).
- **Por qué importa:** quien puede pedir (Pedidos = Editar) tiene que poder elegir cualquier obra aunque su sector
  no vea el módulo Obras, y con alcance "solo sus obras" en Obras el listado no trae las demás.
- **Propuesta:** `GET /api/pedidos/obras-opciones?buscar=` (Pedidos = Editar) que devuelve `{id, numero,
  nombre_trabajo, cliente, estado}` de **todas** las obras (en ejecución, confirmadas y terminadas marcadas), con un
  método nuevo `ObrasConsulta::opciones(texto, limite)` del contrato de Obras. Y `GET /api/usuarios/opciones` suma
  el filtro `sector_id` (propietario por sector).
- **¿Cambia lo que ve el cliente?** No → **decisión técnica**.

### Semana 2 · Avisos

#### DU-P19 · ¿Qué avisos se pueden pedir por mail? (W-19 vs. F-37 y F-31) — ✅ resuelta (19-nov-2026)
- **Decisión (Juani, 19-nov-2026):** va la propuesta tal como está escrita.
- **Fuente:** F-09 ("la asignación… también por mail… cada persona puede activar más avisos por mail") · W-19
  (casillas: asignado ✓, tomado, completado, **vence mañana**; la tabla no tiene "reabren un pedido que completé")
  · F-37 ("Dentro del sistema, sin llenar el mail") · F-31 ("y, **dentro del sistema**, a su superior") · cap. 7
  ("reabren un pedido que completé → al propietario") · ADR-012.
- **Por qué importa:** W-19 ofrece por mail el recordatorio, que F-37 dice que no va por mail; no dice si la
  asignación se puede apagar; y el aviso de reapertura no tiene fila para elegirlo.
- **Propuesta:** **asignación y reasignación siempre por mail** (casillas tildadas y fijas: es lo acordado "como hoy
  con Google"); se pueden activar por mail **tomado, completado, bloqueado, comentario nuevo y reabierto** (se suma
  la fila "Reabren un pedido que completé" a W-19); **nunca por mail** "vence mañana" (F-37; se saca esa casilla) ni
  el aviso al superior (F-31).
- **¿Cambia lo que ve el cliente?** Sí (W-19) → **decide Juani**.

#### DU-P21 · ¿A qué hora sale el recordatorio "vence mañana"? — ✅ resuelta (19-nov-2026)
- **Decisión (Juani, 19-nov-2026):** va la propuesta tal como está escrita.
- **Fuente:** F-37 · W-02 y W-45 ("hoy 8:00 · recordatorio a 1 día de la entrega") · ADR-012 (comando cada hora).
- **Por qué importa:** si corre a medianoche, el aviso aparece "a las 0:00"; si corre una sola vez, un pedido
  cargado más tarde con entrega mañana se queda sin recordatorio.
- **Propuesta:** a partir de las **8:00** (hora de Argentina) y en cada corrida horaria siguiente, sin repetir; un
  pedido cargado después de las 8 con entrega mañana recibe el recordatorio en la corrida siguiente. Qué día (un lunes
  se recuerda el domingo, días corridos): DU-Q01.
- **¿Cambia lo que ve el cliente?** Sí (menor) → **decide Juani**.

#### DU-P22 · Avisos viejos y avisos de pedidos que ya no se pueden ver — ✅ resuelta (19-nov-2026)
- **Decisión (técnica, Tech Lead, 19-nov-2026):** va la propuesta tal como está escrita.
- **Fuente:** F-64 ("la lista completa, con los leídos y los no leídos") · ADR-006 (la tabla de permisos cambia
  cuando quieren).
- **Por qué importa:** no está dicho si los avisos se purgan, ni qué pasa al abrir el aviso de un pedido al que la
  persona ya no tiene acceso.
- **Propuesta:** los avisos **no se borran** en la Etapa 1 (≈13 usuarios: volumen chico); el aviso de un pedido que
  ya no se puede ver sigue en la lista y al abrirlo se ve "No tenés acceso a este pedido." (404 del pedido).
- **¿Cambia lo que ve el cliente?** No → **decisión técnica**.

#### DU-P23 · Comentario de un tercero y comentario en un pedido en el sector (complementa DU-22) — ✅ resuelta (19-nov-2026)
- **Decisión (Juani, 19-nov-2026):** va la propuesta tal como está escrita.
- **Fuente:** DU-22 ("comenta el solicitante → al propietario; comenta el propietario → al solicitante; y a los
  seguidores") · F-30 ("Quien pidió, propietario **y quien lo ve**") · DU-16.
- **Por qué importa:** DU-22 no cubre a quien comenta sin ser ninguna de las dos partes (un seguidor, una
  jefatura), ni un pedido sin propietario.
- **Propuesta:** si comenta un tercero, el aviso va a **quien lo pidió y al propietario** (y a los seguidores, menos
  el autor); si el pedido está en el sector, "el propietario" son **las personas activas del sector**.
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**.

#### DU-P24 · Cambian la fecha de entrega (o la tarea) de mi pedido: ¿me avisa? — ✅ resuelta (19-nov-2026)
- **Decisión (Juani, 19-nov-2026):** va la propuesta tal como está escrita.
- **Fuente:** tabla de avisos del cap. 7 (no incluye la edición) · W-32 (historial con el valor anterior) · F-37 ·
  criterio de aceptación n.º 3.
- **Por qué importa:** si quien pidió adelanta la entrega, el propietario puede no enterarse hasta el recordatorio.
- **Propuesta:** **no se avisa** por la edición (el cap. 7 tiene la lista cerrada de avisos y la edición no está);
  el cambio queda en el historial del pedido y el recordatorio usa la fecha nueva. Si quieren que el propietario se
  entere al momento, quien edita deja un comentario (que sí avisa).
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**.

## Cruce Gastos ⇄ Rendición (24-nov-2026, desarrollo)

Las dos especificaciones se escribieron en paralelo. Donde se pisan:

- **Permiso de las cargas desde la rendición (DU-G08 vs DU-R05):** va **DU-R05**, porque es la tabla aprobada (`identidad.md` §6.4: `rendicion` editar = W-46, horas y fijos; `control_cantidad` editar = retiros con remito en W-48). DU-G08 queda reemplazada.
- **Nota de crédito (DU-G05 vs DU-R03):** se contradecían. **Decisión (Juani, 24-nov-2026): va DU-G05** (no hay tipo "nota de crédito" en la Etapa 1: material devuelto = devolución en negativo, precio mal = se corrige la factura). Es lo que hacen hoy, su libro no tiene notas de crédito y el contrato solo prevé negativos en devoluciones. DU-R03 queda reemplazada.
- **Períodos superpuestos de horas y fijos (DU-G09 y DU-R04):** misma regla en los dos: avisa con los períodos que se pisan y guarda si se confirma. La fecha del renglón es `periodo_hasta` (DU-R04), también en los fijos del mes en curso. **Resuelta (Juani, 24-nov-2026).**
- **Fecha de cada renglón (DU-G01):** es la "fecha efectiva" que usa Rendición. Se suma `gasto_renglones.fecha` al modelo (`modelo-datos.md`). **Resuelta (Juani, 24-nov-2026).**
- **Contrato Gastos → Rendición:** un solo nombre, `Gastos\Contracts\GastadoDeObras` (DU-R08), con lo que pide `rendicion.md` §12.4.1. Rendición ofrece `MaterialesDeObra` y `CatalogoDeRubros`, que es lo que Gastos necesita. **Resuelta (Juani, 24-nov-2026).**
- **Lo que no está en la lista de materiales (DU-G03 vs RN-REN-49):** va el contrato `MaterialesDeObra::crearFueraDePresupuesto` con la unidad, porque W-48 firmada muestra "Unidad" en "Fuera de presupuesto". Se carga en el mismo retiro (una sola carga, como pedía DU-G03).

**Cómo quedó cada una (24-nov-2026):** todas las DU-G y DU-R **resueltas, va la propuesta**, salvo DU-G08 (reemplazada por DU-R05) y DU-R03 (reemplazada por DU-G05). `gastos.md` y `rendicion.md` dicen "resuelta DU-XNN".

## Analista funcional · especificación de la semana 3 · Gastos (24-nov-2026)

Dudas que aparecieron al escribir `especificacion/gastos.md`. No re-preguntan nada firmado ni resuelto (DU-08,
DU-17, DU-18, DU-19, DU-A04, DU-A15, DU-A18 y DU-23 / ADR-014 se aplican tal cual). **24-nov-2026: todas
resueltas** (va la propuesta; DU-G08 reemplazada por DU-R05): las reglas citan "resuelta DU-GNN".

### Semana 3 · Gastos

#### DU-G01 · Fecha de cada renglón (tickets de días distintos en una misma rendición) — ✅ resuelta (24-nov-2026)
- **Decisión (Juani, 24-nov-2026):** va la propuesta tal como está escrita.
- **Fuente:** W-35 (cabecera "Fecha de la rendición: 18-10-2026" y **columna "Fecha" por renglón**: 15-oct, 16-oct,
  16-oct) · su libro de gastos (la fecha va en cada fila) · `modelo-datos.md` (`gastos.fecha` en la cabecera;
  `gasto_renglones` **sin** fecha) · W-37 "Período: toda la obra ▾" y W-48 "retiros por fecha".
- **Por qué importa:** con la fecha solo en la cabecera, una rendición de tickets de toda una semana entra entera con
  la fecha del día en que se rindió, y la rendición por período (W-37) y el control por fecha pierden el día real.
- **Opciones:** (a) fecha por renglón, obligatoria, por defecto la de la cabecera; en facturas siempre igual a la de
  la factura · (b) sin fecha por renglón: una rendición por día (más cargas, se pierde la columna de W-35).
- **Propuesta:** (a). Se agrega `gasto_renglones.fecha` (date, no nula); la rendición usa **la fecha del renglón**;
  Gastos cargados (W-33) filtra y ordena por la fecha del comprobante (lista comprobantes).
- **Reglas que dependen:** RN-GAS-21, §4.2, §4.6 punto 1, §12.5 (`RenglonImputado.fecha`).
- **¿Cambia lo que ve el cliente?** No respecto de W-35 (ya muestra la columna); es un ajuste al modelo → **decisión
  técnica** (DBA + Tech Lead).

#### DU-G02 · W-35: qué se carga, proveedor y "rendido por" — ✅ resuelta (24-nov-2026)
- **Decisión (Juani, 24-nov-2026):** va la propuesta tal como está escrita.
- **Fuente:** F-44 ("sin documento, con proveedor genérico. Se suma el dato 'rendido por'") · F-47 (combustible por
  carga; consumo de stock como renglón sin documento) · W-10 ("Sin documento para tickets, combustible por carga y
  consumo de stock propio") · W-33 ("LOGISTICA · combustible") · `modelo-datos.md` (`origen`: `sin_comprobante`,
  `combustible`, `consumo_stock`) · su libro (combustible con la estación de servicio como proveedor y el chofer en el
  concepto; stock como "ARCHIVO").
- **Por qué importa:** W-35 no tiene cómo decir si es combustible, consumo de stock o gastos de obra rendidos (y el
  modelo y W-33 lo necesitan), ni un campo de proveedor: con el genérico siempre, el combustible deja de verse por
  estación de servicio en "Gastado por obra" (hoy lo ven en su tabla dinámica). Tampoco dice si "rendido por" es
  obligatorio.
- **Opciones:** (a) arriba "Qué se carga: Gastos de obra rendidos · Combustible por carga · Consumo de stock propio";
  proveedor **opcional** (el genérico si no se elige); "rendido por" obligatorio solo en gastos de obra rendidos ·
  (b) sin selector (todo `sin_comprobante`), siempre el genérico, "rendido por" opcional.
- **Propuesta:** (a). Es lo más cercano a su libro (proveedor real cuando lo hay) y a C12 (el dato "rendido por" es
  para poder filtrar a quién se le rindió, que hoy va en el concepto). El consumo de stock exige obra.
- **Reglas que dependen:** RN-GAS-17, RN-GAS-18, RN-GAS-20, §4.3, caso borde 20.
- **¿Cambia lo que ve el cliente?** Sí (un selector y un campo que W-35 no tiene) → **decide Juani**.

#### DU-G03 · Retiros y devoluciones en W-46: remito, proveedor, material y "fuera de presupuesto" — ✅ resuelta (24-nov-2026)
- **Decisión (Juani, 24-nov-2026):** va la propuesta (una sola carga, proveedor y N.º de remito obligatorios). Al alinearla con el contrato `Rendicion\Contracts\MaterialesDeObra` (cruce) y con W-48 firmada, que muestra la columna **Unidad** en "Fuera de presupuesto": lo que no está en la lista se escribe **con su unidad** en la misma carga y queda como material fuera de presupuesto de la obra (`crearFueraDePresupuesto`); ya no hay "—" en la unidad.
- **Fuente:** F-47 ("retiros de Tecnobahía por retiro, con el remito") · C14 · resuelta DU-17 ("cada retiro se carga
  una sola vez con su remito, eligiendo materiales de esa lista… Lo que no está en la lista va a 'fuera de
  presupuesto'") · W-46 (Fecha · Material · Cantidad · Precio · Total · Rubro; sin columna de remito ni de proveedor)
  · W-48 ("Fuera de presupuesto: Material · Unidad · Fecha · Cantidad · Total · Rubro") · su planilla de retiros
  (valuado al precio del presupuesto) · `modelo-datos.md` (los renglones no tienen unidad).
- **Por qué importa:** W-46 no dice dónde va el N.º y el archivo del remito, ni de qué proveedor es el retiro (sin
  eso, los retiros no aparecen bajo su proveedor en W-37 ni en "Gastado por obra"), ni qué precio se usa, ni cómo se
  carga lo que no está en la lista.
- **Opciones:** (a) un retiro = un remito: fecha, **N.º de remito obligatorio**, **proveedor de la base obligatorio**
  (se sugiere el último usado), remito como archivo o link **opcional**, y renglones con material de la lista (precio
  presupuestado sugerido, editable) o, si no está, el concepto escrito → "fuera de presupuesto" sin unidad ("—" en
  W-48); devolución con material de la lista opcional y proveedor opcional · (b) lo de "fuera de presupuesto" se da de
  alta primero en W-48 como material (con unidad) y el retiro siempre elige de la lista (dos pasos, y lo carga quien
  tenga Control por cantidad).
- **Propuesta:** (a): una sola carga, como hoy, y sin pedir permisos de otra fila. Si se quiere la unidad en "fuera de
  presupuesto", se agrega después sin rehacer nada.
- **Reglas que dependen:** RN-GAS-24, RN-GAS-25, §4.3, §4.6 punto 5, §12.5 (`retirosFueraDePresupuesto`);
  `rendicion.md` (W-48).
- **¿Cambia lo que ve el cliente?** Sí (campos que W-46 no muestra; la unidad "—") → **decide Juani**.

#### DU-G04 · "Sin IVA" cuando el comprobante no discrimina el IVA — ✅ resuelta (24-nov-2026)
- **Decisión (Juani, 24-nov-2026):** va la propuesta tal como está escrita.
- **Fuente:** F-41 y C9 ("Importes sin IVA (netos)", Propuesto, aprobado) · auditoría de gaps §5.4 ("ninguna fuente
  lo menciona") · W-35 (tickets de combustible, comidas, peajes).
- **Por qué importa:** una factura A trae el neto; una factura C (monotributista) o un ticket no discriminan IVA: no
  hay neto para cargar. Si no se dice, una persona carga el total con IVA y otra intenta "sacarle" el 21 %, y la
  rendición de una misma obra mezcla criterios. Lo mismo con percepciones e impuestos internos.
- **Opciones:** (a) en un comprobante que discrimina IVA se carga el **neto** (sin IVA ni percepciones); en uno que no
  lo discrimina (factura C, ticket, rendición sin comprobante) se carga **lo pagado**, porque no hay IVA que recuperar ·
  (b) siempre lo pagado · (c) siempre se le descuenta el 21 %.
- **Propuesta:** (a), confirmándolo con María (administración) en la prueba de la semana 3, porque define cómo se
  compara contra su rendición en Excel. El sistema no calcula IVA en ningún caso.
- **Reglas que dependen:** §4.5, RN-GAS-11 (texto "Importes sin IVA (netos)"), manual de uso.
- **¿Cambia lo que ve el cliente?** Sí (qué número cargan) → **decide Juani** (con María).

#### DU-G05 · Notas de crédito del proveedor — ✅ resuelta (24-nov-2026)
- **Decisión (Juani, 24-nov-2026):** va la propuesta tal como está escrita.
- **Fuente:** F-39 (tipo de documento: factura o sin documento) · F-47 y C15 (devoluciones restan) · auditoría de gaps
  ("notas de crédito / devoluciones: no aparecen en el libro de gastos") · ADR-008 (negativas solo en devoluciones).
- **Por qué importa:** el documento solo admite negativos en devoluciones. Si llega una nota de crédito y no hay cómo
  cargarla, la rendición queda con más gasto que el real, o alguien la carga como factura con precios negativos.
- **Opciones:** (a) no hay tipo "nota de crédito": si es por **material devuelto**, se carga como **devolución** en la
  obra (W-46); si **corrige el precio** de una factura ya cargada, se **corrige la factura** (W-34) y queda en su
  historial; las demás quedan en Tango · (b) agregar el tipo "nota de crédito" con renglones negativos.
- **Propuesta:** (a): es lo que hacen hoy (su libro no tiene notas de crédito) y no agrega un tipo de documento que el
  documento firmado no tiene.
- **Reglas que dependen:** RN-GAS-10, RN-GAS-25, RN-GAS-37, manual de uso.
- **¿Cambia lo que ve el cliente?** Sí (cómo resuelven un caso) → **decide Juani**.

#### DU-G06 · Factura repartida: total con filtro de obra y alcance "solo sus obras" — ✅ resuelta (24-nov-2026)
- **Decisión (Juani, 24-nov-2026):** va la propuesta tal como está escrita.
- **Fuente:** F-45 ("con el total de lo filtrado") · W-33 ("como hoy lo sacan con tablas dinámicas", columna "Total"
  por comprobante) · DU-A04 (Ingeniería ve solo sus obras para no ver "por otro lado" lo que la rendición le oculta) ·
  `identidad.md` §6.1 (`gastos`: "solo sus obras" = renglones de sus obras) · RN-IDE-47.
- **Por qué importa:** una factura de 905.600 con 549.000 en Nave 3: filtrando por Nave 3, ¿la fila suma 905.600 o
  549.000? Y una ingeniera de Nave 3, ¿ve los renglones de las otras obras y el total completo?
- **Opciones:** (a) con filtro de obra o rubro, la fila muestra lo de los renglones que cumplen ("549.000 · de
  905.600") y el total de lo filtrado suma eso; con "solo sus obras", solo sus renglones y "Esta factura tiene además N
  renglones de otras obras o sin obra, que no ves", y el archivo de la factura sí se baja · (b) siempre el total del
  comprobante; con "solo sus obras", la factura entera.
- **Propuesta:** (a): da lo mismo que su tabla dinámica filtrada y cumple DU-A04. El archivo se baja porque es el
  comprobante en papel escaneado (no la rendición de otras obras); si prefieren ocultarlo, se cambia sin tocar nada
  más.
- **Reglas que dependen:** RN-GAS-31, RN-GAS-33, RN-GAS-48, RN-GAS-43, §12.2 (`total_filtrado`, `renglones_ocultos`).
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**.

#### DU-G07 · Buscador: ¿qué encuentra en gastos? (complementa DU-P15) — ✅ resuelta (24-nov-2026)
- **Decisión (Juani, 24-nov-2026):** va la propuesta tal como está escrita.
- **Fuente:** F-03 ("gastos por número de factura") · W-18 (con "tanque" muestra "Factura 0003-00012345 · Corralón
  Centro · renglón 'Disco diamantado 350 mm' imputado a losa tanque B", que coincide por el **nombre de la obra**) ·
  resuelta DU-P15 (pedidos: solo texto propio; lo de una obra se ve desde la obra).
- **Por qué importa:** el texto firmado dice "por número de factura"; el ejemplo de W-18 encuentra un gasto por la obra.
  Buscando por obra o por concepto, el grupo Gastos se llena de renglones con cualquier palabra común ("cemento",
  "flete").
- **Opciones:** (a) número de factura **y de remito**, tolerante a ceros, guiones y espacios ("12345" encuentra
  0003-00012345), con 3 o más dígitos · (b) además el concepto de los renglones · (c) además el nombre de la obra
  (como el ejemplo de W-18).
- **Propuesta:** (a), igual criterio que DU-P15: lo de una obra se encuentra por el grupo Obras → Rendición.
- **Reglas que dependen:** RN-GAS-44, §12.3.
- **¿Cambia lo que ve el cliente?** Sí (el ejemplo de W-18 no se da así) → **decide Juani**.

#### DU-G08 · Permiso de las cargas que se hacen desde la rendición (W-46, horas y fijos) — ✅ resuelta (24-nov-2026) · reemplazada por DU-R05
- **Decisión (Juani, 24-nov-2026):** **reemplazada por DU-R05**. Va la tabla aprobada (`identidad.md` §6.1 filas 8 y 9, §6.6): las cargas desde la rendición (W-46: amortización, devoluciones, horas y fijos) piden `rendicion` = editar; los retiros con remito, `rendicion` = editar **o** `control_cantidad` = editar; corregir y anular en W-34 sigue con `gastos` = editar (fila 7, F-46). `gastos.md` §8 lo dice igual que `rendicion.md` RN-REN-58.
- **Fuente:** `identidad.md` §6.1 (`rendicion` Editar: "cargas que se hacen desde la rendición (W-46, horas y fijos
  por período)") · `identidad.md` §6.6 (`remito`: `gastos` (W-46)) · F-46 (corregir y anular gastos: "Administración
  y compras") · ADR-007 (todo es un renglón de gasto).
- **Por qué importa:** amortización, retiros, devoluciones, horas y fijos son gastos: se ven en Gastos cargados y se
  corrigen o anulan en W-34 con `gastos = editar`. Si cargarlos pide `rendicion = editar`, alguien con una celda y no
  la otra podría cargar algo que después no puede corregir (o al revés).
- **Opciones:** (a) cargar, corregir y anular piden `gastos = editar` en todos los casos; el acceso desde la rendición
  aparece a quien lo tenga · (b) las cargas desde la rendición piden `rendicion = editar` y su corrección también · (c)
  las dos celdas.
- **Propuesta:** (a). Con la configuración inicial las dos celdas las tienen las mismas personas (Gerencia y Jefatura
  de Administración, Compras), así que nadie nota la diferencia. Ajusta la línea de `identidad.md` §6.1.
- **Reglas que dependen:** §8.1, §8.2, RN-GAS-22.
- **¿Cambia lo que ve el cliente?** No con la configuración inicial → **decisión técnica** (Tech Lead), avisando a
  Juani porque toca una celda de la tabla de permisos.

#### DU-G09 · Montos por período que se superponen (complementa DU-18) — ✅ resuelta (24-nov-2026)
- **Decisión (Juani, 24-nov-2026):** va la propuesta (avisa y guarda si se confirma). La fecha del renglón es la de DU-R04 (`periodo_hasta`, también para los fijos del mes en curso), que es la fecha efectiva de la rendición: una sola regla en `gastos.md` RN-GAS-27 y `rendicion.md` RN-REN-17.
- **Fuente:** resuelta DU-18 ("período desde–hasta… un renglón por período y por obra en su rubro") · F-54 · su hoja
  "Horas liquidadas" (por quincena).
- **Por qué importa:** DU-18 no dice qué pasa si se cargan dos veces las horas de la misma quincena (error típico al
  cargar a mano), ni qué fecha toma un monto de fijos del mes en curso.
- **Opciones:** (a) si para la misma obra y rubro ya hay un monto cuyo período se superpone, **avisa** ("Ya hay HORAS
  LIQUIDADAS de Piso industrial nave 3 del 01/10 al 15/10 (1.300.000). ¿Cargar igual?") y se guarda si se confirma; la
  fecha del gasto es el "hasta", o hoy si el "hasta" todavía no llegó · (b) se rechaza · (c) no se controla.
- **Propuesta:** (a): evita la doble carga sin trabar un caso real (un ajuste de la misma quincena). El sistema muestra,
  la persona decide (D-006).
- **Reglas que dependen:** RN-GAS-27, RN-GAS-28.
- **¿Cambia lo que ve el cliente?** Sí (un aviso) → **decide Juani**.

#### DU-G10 · Número de factura: forma y "la misma factura" — ✅ resuelta (24-nov-2026)
- **Decisión (Juani, 24-nov-2026):** va la propuesta tal como está escrita.
- **Fuente:** criterio de aceptación n.º 2 ("una factura se carga una sola vez") · `modelo-datos.md` (único
  `(proveedor_id, tipo_documento, numero)` de facturas no anuladas) · W-10 ("0003-00012345") · F-39.
- **Por qué importa:** "3-12345" y "0003-00012345" son la misma factura; si se guardan como se escribieron, el
  anti-duplicado no las ve y la factura se carga dos veces. Además hay que decidir si la letra (A, B, C) cuenta.
- **Opciones:** (a) si el número tiene la forma punto de venta–número, se guarda normalizado (punto de venta con al
  menos 4 dígitos, número con 8: `0003-00012345`); la misma factura = mismo proveedor + mismo número normalizado, no
  anulada; la letra no se pide; un remito repetido del mismo proveedor avisa sin frenar · (b) se guarda tal cual y solo
  frena si es idéntico.
- **Propuesta:** (a). Un mismo proveedor no repite número de punto de venta y número entre letras distintas en la
  práctica; si apareciera el caso, se corrige el número de la segunda.
- **Reglas que dependen:** RN-GAS-07, RN-GAS-08, RN-GAS-24, RN-GAS-44.
- **¿Cambia lo que ve el cliente?** Poco (el número se ve con ceros) → **decide Juani**.

#### DU-G11 · Corregir y anular: qué se puede y qué no — ✅ resuelta (24-nov-2026)
- **Decisión (Juani, 24-nov-2026):** va la propuesta tal como está escrita.
- **Fuente:** F-46 ("ver, corregir o anular un gasto") · W-34 ("Anular la deja tachada y deja de sumar en la
  rendición"; botones Editar y Anular) · `modelo-datos.md` (`origen` "no se edita"; `motivo_anulacion`) · criterio
  del documento: no se borra (W-34, W-39).
- **Por qué importa:** el documento no dice si un anulado se puede reactivar, si la anulación necesita un motivo, si se
  puede cambiar el tipo de documento al corregir ni cómo se ve un anulado en Gastos cargados.
- **Opciones:** (a) corregir todo menos el tipo de documento y qué se cargó (para eso, anular y cargar de nuevo);
  anular con **motivo obligatorio** y **sin vuelta atrás**; en W-33 los anulados se ven tachados y no suman ("1
  anulado (no suma)") · (b) permitir "desanular" y cambiar el tipo; los anulados no se listan.
- **Propuesta:** (a): una sola forma de deshacer (cargar de nuevo) deja el historial claro, y ver el anulado tachado es
  lo que dice W-34.
- **Reglas que dependen:** RN-GAS-34, RN-GAS-37, RN-GAS-40, RN-GAS-41, §6.
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**.

## Analista funcional · especificaciones de la semana 3 · Rendición (24-nov-2026)

> Dudas que aparecieron al escribir `especificacion/rendicion.md`. Prefijo **DU-R**. No re-abren las resueltas (DU-08, DU-09,
> DU-17, DU-18, DU-19, DU-21, DU-A03…A05): las **complementan** donde se dice. Las reglas que dependen de estas dudas dicen
> "resuelta DU-RXX" en la especificación. **24-nov-2026: todas resueltas** (va la propuesta; DU-R03 reemplazada por DU-G05).
> Las que tocaban la prueba del cliente de la semana 3 ("cargan las facturas de una obra en curso y la comparan con su rendición
> en Excel"): DU-R01, DU-R02, DU-R03 y DU-R04.

### Semana 3 · Rendición

#### DU-R01 · Ganancia: ¿solo la "prevista" de W-11 o también la "al día" de su hoja General? — ✅ resuelta (24-nov-2026)
- **Decisión (Juani, 24-nov-2026):** va la propuesta tal como está escrita.
- **Fuente:** F-54 / cap. 4.4 ("la rendición muestra también el monto de la obra, la ganancia y el %, **como su hoja General**") ·
  W-11 ("Monto de la obra (OC): 36.500.000 · Costo presupuestado: 28.900.000 · Ganancia prevista: 7.600.000 (21 %)") · auditoría
  de gaps 19-oct §4.4 (en las 6 rendiciones: `Ganancia = Cobrado − Total gastado`, `% = Ganancia ÷ Cobrado`, con Cobrado = suma de
  los montos asignados).
- **Por qué importa:** la maqueta calcula la ganancia contra lo **presupuestado** (monto − costo presupuestado); su planilla, contra
  lo **gastado** (monto − gastado). En la prueba de la semana 3 van a comparar contra su General y el número no va a coincidir.
- **Opciones:** (a) solo la prevista (W-11 literal); (b) solo la al día (su planilla); (c) las dos.
- **Propuesta:** (c) las dos, en dos líneas del mismo bloque y con el mismo permiso (`monto_obra` ≥ ver): "Monto de la obra · Costo
  presupuestado · Ganancia prevista (%)" y "Gastado hasta hoy · Ganancia al día (%)". El % siempre sobre el monto, como su planilla.
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**.
- **Reglas que dependen:** RN-REN-23, RN-REN-25, N15 (§4.3), caso de referencia §9.3, §13.

#### DU-R02 · Obra mixta (por rubro + solo total): ¿se marcan en rojo los rubros contra lo desglosado? (complementa DU-08) — ✅ resuelta (24-nov-2026)
- **Decisión (Juani, 24-nov-2026):** va la propuesta tal como está escrita.
- **Fuente:** DU-08 resuelta ("los rubros muestran lo desglosado y una fila 'Sin desglose por rubro'") · F-50 ("el rubro que gastó más
  de lo presupuestado se ve en rojo") · F-53 ("rubros pasados").
- **Por qué importa:** en una obra con el principal por rubro y un adicional solo total, todo gasto tiene rubro pero parte de la plata
  de ese rubro está en "sin desglose". Un rubro puede verse "pasado" aunque la obra tenga plata para ese rubro dentro del total del
  adicional. Lo mismo pasa con un rubro vacío en W-13 que tiene gasto.
- **Opciones:** (a) marcar contra lo desglosado, con una leyenda que lo explica; (b) no marcar ningún rubro en obras mixtas (solo el
  total); (c) repartir el "sin desglose" entre los rubros en proporción (inventa un número que nadie cargó).
- **Propuesta:** (a). Se marca contra lo desglosado (es lo que muestran los números de la fila) y arriba de la tabla: "1.000.000 de
  esta obra no tienen desglose por rubro (presupuesto 1-200-2026 A1): cada rubro se compara solo contra lo desglosado." Un rubro
  con gasto y sin presupuesto también se marca, con "sin presupuesto". "Rubros pasados" de W-12 cuenta igual. En obras **solo total**
  no se marca ningún rubro (solo el total).
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**.
- **Reglas que dependen:** RN-REN-11, RN-REN-21, N10 y N12 (§4.3), RN-REN-39.

#### DU-R03 · Nota de crédito del proveedor: ¿cómo resta? — ✅ resuelta (24-nov-2026) · reemplazada por DU-G05
- **Decisión (Juani, 24-nov-2026):** **reemplazada por DU-G05**. No hay tipo "nota de crédito" en la Etapa 1: material devuelto = devolución en negativo (W-46); precio mal = se corrige la factura (W-34, queda en su historial). El renglón 8 del caso de referencia de `rendicion.md` §9 pasa a ser una devolución al proveedor por el mismo importe.
- **Fuente:** F-39 ("tipo de documento (factura o sin documento)") · C15 (solo las **devoluciones** restan, renglón negativo) ·
  `modelo-datos.md` (`cantidad` negativa **solo** si `origen = devolucion`) · auditoría de gaps §3.5 ("notas de crédito: no aparecen
  en el libro de gastos").
- **Por qué importa:** cuando un proveedor bonifica o corrige una factura con una nota de crédito, hoy la restan a mano en la planilla
  (o corrigen el precio). Si el sistema no la admite, para que la rendición dé igual que su Excel hay que editar la factura original
  (y se pierde el rastro) o cargar una "devolución" que no lo es. La rendición, por su lado, resta cualquier renglón negativo.
- **Opciones:** (a) la nota de crédito se carga como documento propio ("Nota de crédito", número y proveedor, renglones en negativo
  en la obra y el rubro de la factura); (b) se corrige la factura original (W-34, queda en el historial); (c) se carga como
  devolución.
- **Propuesta:** (a), con la forma exacta que defina `gastos.md` (tipo de documento nuevo o marca en la factura): la rendición la
  resta en su obra y rubro, la muestra como "Nota de crédito 0001-00000009" y la cuenta como comprobante del proveedor en W-37.
  Amplía C15 (que solo nombraba devoluciones) y el dato "tipo de documento" de F-39.
- **¿Cambia lo que ve el cliente?** Sí (un tipo de comprobante más) → **decide Juani** (lo especifica Gastos).
- **Reglas que dependen:** RN-REN-04, RN-REN-29, RN-REN-34, caso de referencia §9.2 renglón 8, §16.

#### DU-R04 · Horas liquidadas y fijos: dónde se cargan, qué fecha usan y qué pasa si se superponen (complementa DU-18) — ✅ resuelta (24-nov-2026)
- **Decisión (Juani, 24-nov-2026):** va la propuesta tal como está escrita (sección de W-46, fecha = `periodo_hasta`, superposición avisa y guarda si se confirma; misma regla que DU-G09).
- **Fuente:** F-54 ("administración los carga como un monto total por período") · DU-18 resuelta (desde–hasta, un renglón por período
  y por obra) · `identidad.md` §6.1 fila 8 ("cargas que se hacen desde la rendición (W-46, horas y fijos por período)") · **ninguna
  maqueta tiene la carga** (W-11 solo muestra "monto por período").
- **Por qué importa:** sin pantalla no se puede construir ni probar; y la rendición necesita una fecha para ordenar el detalle (W-36) y
  filtrar por período (W-37). Además, cargar dos veces la misma quincena duplica las horas y hoy en su planilla eso no pasa (es una
  hoja por quincena).
- **Opciones de pantalla:** (a) una sección "Horas liquidadas y fijos por período" dentro de W-46 (renglones sin documento de la
  obra), con acceso "+ cargar período" desde las filas FIJOS y HORAS LIQUIDADAS de W-11; (b) una pantalla nueva en Gastos con todas
  las obras de un período. **Fecha:** (a) el último día del período; (b) el primero; (c) la de carga. **Superposición:** (a) avisar y
  dejar cargar; (b) frenar; (c) nada.
- **Propuesta:** pantalla (a), con Rubro (FIJOS / HORAS LIQUIDADAS) · Desde · Hasta · Importe · Concepto (por defecto "Horas
  liquidadas 1 al 15-oct", editable); fecha (a) el **último día del período** (`periodo_hasta`): una quincena cae en la quincena; y
  superposición (a): "Ya hay HORAS LIQUIDADAS de esta obra del 1 al 15-oct (950.000). ¿Cargar igual?", sin frenar (el sistema muestra,
  no decide, D-006). La carga la construye Gastos (una sola fuente, origen `monto_periodo`).
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**.
- **Reglas que dependen:** RN-REN-17, RN-REN-18, fecha efectiva (§4.3), §12.4.1 (`RenglonImputado::fecha`), caso de referencia
  renglones 18…21.

#### DU-R05 · ¿Qué permiso habilita las cargas "desde la rendición" (W-46, horas y fijos, retiros)? — ✅ resuelta (24-nov-2026)
- **Decisión (Juani, 24-nov-2026):** va la propuesta tal como está escrita; **reemplaza a DU-G08**. Corregir y anular en W-34 sigue con `gastos` = editar (fila 7); los adjuntos `remito`, según `identidad.md` §6.6.
- **Fuente:** `identidad.md` §6.1 (aprobado): fila 7 `gastos` Editar "cargar, corregir, anular (F-39…F-47)"; fila 8 `rendicion` Editar
  "cargas que se hacen desde la rendición (W-46, horas y fijos por período)"; fila 9 `control_cantidad` Editar "cargar materiales
  presupuestados y retiros con remito"; §6.6 contexto `remito`: "`gastos` (W-46) / `control_cantidad` (W-48)" · F-47 está en Gastos.
- **Por qué importa:** la misma carga (un retiro, una amortización) tiene tres celdas candidatas. En la configuración inicial coinciden
  (administración y compras editan las tres), pero si la gerencia cambia una celda en W-15 el resultado depende de cuál se mire, y
  las dos especificaciones (Gastos y Rendición) tienen que decir lo mismo.
- **Opciones:** (a) lo que dice `identidad.md`: W-46 y horas/fijos con `rendicion` = editar; los retiros, además, con
  `control_cantidad` = editar; (b) todo con `gastos` = editar (es un gasto) y `rendicion` = editar no habilita nada; (c) pedir dos
  celdas a la vez.
- **Propuesta:** (a), porque es la tabla aprobada y la que Juani ya vio en W-15. Las facturas y los gastos sin comprobante (W-10,
  W-35) siguen con `gastos` = editar. El endpoint es de Gastos y su Policy aplica la regla. Si se elige (b), hay que corregir
  `identidad.md` §6.1 fila 8.
- **¿Cambia lo que ve el cliente?** Solo si cambian la tabla de permisos → **decide Juani** (junto con el analista de Gastos).
- **Reglas que dependen:** RN-REN-58, §7.1, §12.4.1 invariante 7.

#### DU-R06 · Control por cantidad (W-48): precio, total, columnas de fecha, cierre con MATERIALES y unidad (complementa DU-17) — ✅ resuelta (24-nov-2026)
- **Decisión (Juani, 24-nov-2026):** va la propuesta tal como está escrita.
- **Fuente:** W-48 ("Precio unit. · Total", "Hasta 25-sep · 30-sep · 9-oct · 17-oct", "Total MATERIALES (igual al gastado de la
  rendición, W-11 y W-36)") · su planilla de retiros (hasta 13 columnas de fecha; TOTAL = Σ retiros × precio de la planilla) · "Real
  vs Presup" (precio presupuestado contra pedido) · DU-17 resuelta (lista con material, unidad, cantidad y precio; facturas de otro
  proveedor asociables).
- **Por qué importa:** (1) si "Precio unit." es el presupuestado y "Total" se calcula con él, el total de W-48 deja de ser lo gastado
  y no cierra con la rendición; (2) la maqueta muestra 4 columnas de fecha y una "Hasta", sin decir cuántas; (3) el total de W-48 solo
  es igual al de MATERIALES si **todos** los renglones de MATERIALES están asociados a un material, cosa que en la práctica no va a
  pasar (y un retiro puede ir a INSUMOS); (4) una factura en "kg" asociada a un material en "bolsas" rompe la cuenta.
- **Propuesta:** (1) "Precio unit." = el **presupuestado** de la lista y "Total" = lo **gastado de verdad** en ese material (la suma de
  sus renglones, el mismo número de la rendición); (2) las **6 fechas más recientes** y las anteriores juntas en "Hasta {fecha}";
  (3) debajo del total: "Gastado en MATERIALES en la rendición: X" y, si difiere, "Renglones de MATERIALES sin material asociado: Y",
  con acceso al detalle; (4) la cantidad va siempre **en la unidad del material** (el formulario la muestra), sin conversión.
- **¿Cambia lo que ve el cliente?** Sí → **decide Juani**.
- **Reglas que dependen:** RN-REN-50, RN-REN-51, RN-REN-53, N16…N19 (§4.3), caso de referencia W-48 (§9.4).

#### DU-R07 · Detalle por rubro, por proveedor y últimos gastos: lo que W-11, W-36 y W-37 no dicen — ✅ resuelta (24-nov-2026)
- **Decisión (Juani, 24-nov-2026):** va la propuesta tal como está escrita.
- **Fuente:** W-11 ("Últimos gastos imputados… · ver todos"), W-36 (columna Documento, "Orden por fecha, proveedor o total"),
  W-37 ("Sin documento · — ", "Mostrando 4 de 9 proveedores", "Período: toda la obra ▾") · sus tablas dinámicas (EPP figura separado
  aunque en el General sume en INSUMOS).
- **Por qué importa:** son detalles de pantalla, pero el cliente los va a usar para cuadrar contra su Excel (por proveedor y por rubro)
  y hay que fijarlos para los tests.
- **Propuesta:** (1) "Últimos gastos": los **10** más recientes por fecha; (2) columna **Documento** con estas etiquetas: "Factura N",
  "Nota de crédito N" (si DU-R03), "Sin documento", "Consumo de stock", "Amortización", "Retiro · remito N", "Devolución", "Período 1
  al 15-oct", "Carga inicial"; proveedor genérico o vacío = "—"; (3) en W-37, los renglones del proveedor genérico o sin proveedor
  van a un grupo "**Sin documento**" siempre al final; "Facturas" cuenta los comprobantes con número (facturas y notas de crédito; un
  remito no); se muestran 10 grupos y "ver los N restantes"; el pie suma todo lo filtrado; (4) **Período**: toda la obra · este mes ·
  el mes anterior · desde–hasta, por fecha efectiva; (5) en el detalle de INSUMOS, los renglones de EPP con la marca "EPP" y el
  subtotal "de los cuales EPP: N" (para cuadrar con su tabla dinámica).
- **¿Cambia lo que ve el cliente?** Sí (detalle de pantalla) → **decide Juani**.
- **Reglas que dependen:** RN-REN-15, RN-REN-28, RN-REN-29, RN-REN-35…37.

#### DU-R08 · Contratos nuevos entre módulos para la rendición (técnica) — ✅ resuelta (24-nov-2026)
- **Decisión (Juani, 24-nov-2026):** va la propuesta tal como está escrita.
- **Fuente:** ADR-003 (los módulos se hablan por `Contracts/` y puertos) · ADR-007 · `obras.md` §10.4 (hoy `PresupuestadoDeObra
  {porRubro, sinDesglose, total}` por una sola obra; `AvanceGastoDeObra` por lista) · RN-OBR-24 (no quitar un adicional con materiales)
  · `modelo-datos.md` §3 (W-12 con una consulta por agregado para todas las obras).
- **Por qué importa:** sin estos contratos la rendición no puede saber qué presupuestos están sin cargar o solo total, ni leer el
  monto, ni armar W-12 y el % del listado sin una consulta por obra; y Gastos no tiene de dónde leer ni validar los materiales.
- **Propuesta:** (1) **Gastos** implementa `Gastos\Contracts\GastadoDeObras` (`totalesPorRubro`, `renglonesDeObra`,
  `contarRenglonesPorMaterial`) con `RenglonImputado` y los eventos `GastoImputado/GastoCorregido/GastoAnulado {gastoId, obraIds}`
  (todas las obras afectadas), según `rendicion.md` §12.4.1; (2) **Obras** amplía `PresupuestadoDeObra` con la lista de presupuestos
  y su `modoCarga`, y suma `presupuestadoDeObras(ids)`, `montoDeObra(id)`, `obrasEnEjecucion()` y el puerto
  `MaterialesDePresupuesto::tieneMateriales`; (3) **Rendicion** expone `MaterialesDeObra` (`opciones`, `perteneceAObra`,
  `crearFueraDePresupuesto`) y `CatalogoDeRubros`; (4) `materiales` suma `nombre_clave` único por obra, `created_by`, `updated_by`,
  timestamps y el CHECK de fuera de presupuesto.
- **¿Cambia lo que ve el cliente?** No → **decisión técnica** (Tech Lead + DBA, con el analista de Gastos).
- **Reglas que dependen:** §12.4, §16, RN-REN-41, RN-REN-42, RN-REN-49, RN-REN-53, RN-REN-60.

### Semana 4 (no bloquea la semana 3)

#### DU-R09 · Control por cantidad de una obra que arranca con carga inicial: ¿qué pasa con lo ya retirado? (complementa DU-21) — ✅ resuelta (24-nov-2026)
- **Decisión (Juani, 24-nov-2026):** va la propuesta tal como está escrita.
- **Fuente:** F-62 ("las obras en ejecución con su gastado acumulado (un total por rubro)") · DU-21 resuelta · F-66 / W-48 (resta
  llevar) · su planilla de retiros (las 6 rendiciones la tienen, con los retiros hechos hasta hoy).
- **Por qué importa:** la carga inicial trae plata por rubro, no cantidades. Una obra en ejecución que arranca en el sistema con su
  lista de materiales va a mostrar "Total retirado 0" y "Resta llevar" igual a todo lo presupuestado, aunque ya se llevó la mitad; si
  se carga el retirado como retiros con importe, se cuenta dos veces contra el gastado acumulado.
- **Opciones:** (a) el control por cantidad arranca de cero en esas obras (se usa la planilla de hoy para lo previo); (b) la carga
  inicial trae, por material de la lista, la cantidad ya retirada como un renglón de carga inicial **con importe 0** asociado al
  material (el importe ya está en el gastado acumulado por rubro); (c) se carga el retirado previo con importe y se descuenta del
  gastado acumulado.
- **Propuesta:** (b): no cuenta dos veces (importe 0), "Resta llevar" da bien desde el primer día y en W-48 se ve en la columna "Hasta
  {fecha de arranque}" con la etiqueta "Carga inicial". Lo revisa y aprueba administración, como las equivalencias de DU-21.
- **¿Cambia lo que ve el cliente?** Sí (les pedimos el retirado por material) → **decide Juani**.
- **Reglas que dependen:** RN-REN-08, RN-REN-50 (columna "Hasta"); importación de la semana 4.

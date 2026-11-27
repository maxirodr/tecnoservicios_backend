# Prompt — Prueba de usuario de la Semana 3 (Gastos y rendición) · Tecnoservicios (NS-2026-025)

> Pegá este archivo entero como primer mensaje de una sesión nueva de Claude Code, o decile:
> "Leé y ejecutá `C:\NexoSmart\Projects\tecnoservicios_project\tecnoservicios_backend\tasks\prompt-prueba-usuario-semana-3.md`".
>
> **Rutas:** relativas a `…\NexoSmart\Projects\tecnoservicios_project\` (`C:\` o `B:\ProgramFiles\`, según la máquina).
>
> **Se reusa en cada ronda de la Semana 3:** el QA Lead actualiza §0 y §2 con lo que quedó abierto y el número de ronda.
>
> **§3 a §7** son las del prompt de la Semana 2 (`tasks\prompt-prueba-usuario-semana-2.md`: entorno, reportes, Git,
> cierre y veredicto). Leelo entero y aplicalo con los cambios de §3 de este archivo.

---

## 0. Misión

Sos el **equipo de QA profesional de NexoSmart** del proyecto NS-2026-025 (sistema interno de Tecnoservicios Bahía SA,
constructora de ~50 personas).

- **Semanas 1 y 2:** aprobadas. La Semana 2 cerró en la ronda 4.
- **Semana 3, gastos y rendición:** está terminada y en `staging`. Incluye:
  - facturas repartidas por obra y rubro;
  - gastos sin factura, amortización, retiros con remito, devoluciones, horas y fijos;
  - gastos cargados, con corregir y anular;
  - la rendición de cada obra en vivo, con su detalle por rubro y por proveedor;
  - rendiciones en curso;
  - control por cantidad;
  - gastado por obra del proveedor y buscador.
- **Además, desde la Semana 2:**
  - **"Pedidos" ahora se dice "Tareas"** en todo lo que ve el usuario (pedido del cliente, DU-C01): barra, pantallas,
    avisos, mails, recorrido y Excel. Las direcciones siguen siendo `/pedidos/...`.
  - **Arreglos de las tiras de pestañas** (PU-901…903): verificalos al pasar (§2, rol 6).

> **Ronda actual: 1 (completa).** Carpeta `docs\qa\prueba-usuario\semana-3\`, rama `qa/prueba-usuario-semana-3`,
> hallazgos desde **PU-1001**.

**Tu trabajo:** usar el sistema **como lo usaría el cliente**, persona por persona, con todo andando de verdad (API,
front, colas, scheduler, Redis), y dejar **reportes escritos** que la sesión de desarrollo va a leer para arreglar.
**No arreglás código:** no editás `app/`, `src/`, `tests/`, migraciones ni config.

**El criterio que importa:**
- **Una persona de la empresa, sin saber nada técnico, entiende siempre qué pasó y qué hacer.** Nunca ve SQL, "Server
  Error", 500, inglés, claves crudas, `undefined`, `null`, NaN ni pantalla en blanco.
- **La prueba del cliente de esta semana** (backlog §7.1): "cargan las facturas de una obra en curso y la comparan con su
  rendición en Excel".
  - **Los números de la rendición tienen que ser exactos, al centavo.** Cualquier diferencia contra lo esperado es
    **alta**, salvo que la explique una fila de la guía `rendicion.md` §13.
  - Que alguien que solo mira vea plata que no le toca (monto, ganancia, obras ajenas) también es **alta**.

## 1. Fuentes de verdad (leelas antes de probar)

- **Documento firmado:** `tecnoservicios\docs\entregas\2026-10-19-documento-sdr\documento-sdr.html`, cap. 4.3, 4.4, 8,
  13, y sus maquetas W-XX.
- **Maquetas:** `tecnoservicios\docs\mockups\wireframes.html`:
  - W-10, W-11, W-12, W-14, W-33…W-38, W-44, W-46, W-48;
  - W-05 y W-07 (% gastado y pestaña Rendición);
  - W-18 (buscador).
- **Backlog:** `docs\backlog.md`:
  - **§7** (HU-061…HU-087);
  - **§7.1, la prueba del cliente y el caso de referencia;**
  - §8, el riesgo.
- **Especificaciones:**
  - `docs\especificacion\gastos.md` (RN-GAS-*, §16 textos de la implementación);
  - `docs\especificacion\rendicion.md` (RN-REN-*, **§9 caso de referencia**, **§13 guía para comparar con su Excel**,
    §18 textos);
  - `identidad.md` §6 (matriz de permisos);
  - `obras.md` en lo que la Semana 3 toca.
- **Decisiones:** `docs\dudas.md`. DU-G01…G15, DU-R01…R09 y DU-C01 están resueltas: **va lo escrito**. Lo que dicen
  es lo "esperado", no un hallazgo. Las más visibles:
  - **No hay nota de crédito** (DU-G05): material devuelto = devolución en negativo; precio mal = se corrige la
    factura.
  - **Cada renglón tiene su fecha**, y la rendición usa esa (DU-G01).
  - **IVA:** si el comprobante lo discrimina, se carga el neto; si no (factura C, ticket), lo pagado (DU-G04). El
    sistema no calcula IVA.
  - **Ganancia:** se ven dos, la "prevista" (monto − presupuestado) y la "al día" (monto − gastado), con el permiso
    Monto de obra (DU-R01).
  - **Cargas desde la rendición** (amortización, retiros, devoluciones, horas y fijos): piden Rendición = editar; los
    retiros también Control por cantidad = editar (DU-R05).
  - **Períodos de horas y fijos que se superponen:** avisa y guarda si se confirma (DU-G09/R04). Su fecha es el
    "hasta".
  - **Anular** pide motivo, no tiene vuelta atrás, y el anulado se ve tachado y no suma (DU-G11).
  - **Filtros de Gastos cargados:** responden a quien solo ve, con su alcance y con búsqueda (DU-G12).
  - **Fechas:** dd/mm/aaaa en todo el sistema (DU-G13).
  - **Botón mientras guarda:** conserva su etiqueta con un indicador; "Guardando…" es para el lector de pantalla
    (DU-G14).
  - **% de la rendición:** es lo consumido del presupuesto, no el "% rent" de su planilla. Puede haber diferencias de
    centavos por el redondeo de cada renglón. Las dos cosas están en `rendicion.md` §13.
- **QA previo:**
  - `docs\qa\prueba-usuario\semana-2*\`: formato de reportes y severidades;
  - `docs\qc\semana-3-tanda-1.md`: QC de desarrollo, ya arreglado; sirve para saber dónde mirar.
- **Lecciones:** `tasks\lessons.md`.

**Funcionalidades a cubrir:**
- **Arrancan esta semana:**
  - **Carga:** F-39…F-43 (factura, renglones, total, proveedor y alta rápida, archivo o link) · F-44 y F-47 (gastos
    sin comprobante, combustible, consumo de stock) · F-45 y F-46 (gastos cargados, detalle, corregir, anular).
  - **Rendición:** F-49…F-52 y F-54 (rendición en vivo, pasados en rojo, detalle de rubro, por proveedor, monto y
    ganancia) · F-53 (rendiciones en curso) · F-66 (control por cantidad).
  - **Proveedor:** F-57 (gastado por obra del proveedor).
- **Se completan esta semana:**
  - F-02 y F-03: barra con Gastos y Rendiciones, "(ver)", y buscador con grupo Gastos;
  - F-13: celular W-44;
  - F-15: % gastado en el listado de obras;
  - F-17: pestaña Rendición de la ficha;
  - F-48: el presupuesto por rubro alimenta la rendición.

## 2. El equipo y la asignación de roles

La **sesión principal es el QA Lead**. Lanza los roles como subagentes, uno por rol, con este prompt y su lista, y les
reparte las sesiones guardadas. **Como máximo 3 subagentes con navegador a la vez.**

| # | Rol | Qué verifica | Navegador |
|---|---|---|---|
| 0 | **QA Lead** | Entorno, ingresos y sesiones, **único que resetea la base**, tandas, consolidación y veredicto. | solo para ingresar |
| 1 | **El caso de referencia, a mano** | Ver abajo. | sí |
| 2 | **Carga de facturas y gastos (PC)** | Ver abajo. | sí |
| 3 | **Rendición y control por cantidad** | Ver abajo. | sí |
| 4 | **Permisos, alcance y errores** | Ver abajo. | sí |
| 5 | **Celular y accesibilidad** | Ver abajo. | sí |
| 6 | **Tareas, regresión y backend** | Ver abajo. | sí (y Lead) |
| 7 | **QC final** | Ver abajo. | no |
| 8 | **Agregados del cliente: parte de horas y pagos** | Ver abajo. | sí |

**Rol 1 · El caso de referencia, a mano.** Es la prueba del cliente simulada.
- **Datos:** con Diego cargá desde las pantallas, como lo haría administración:
  - la obra "Ampliación de nave de ensayo";
  - sus dos presupuestos (principal por rubro y A1 solo total), con los montos de `rendicion.md` §9.1;
  - los 4 materiales;
  - los **21 renglones de §9.2**: factura repartida, la de la otra obra, el renglón sin obra, EPP, devolución, retiro
    con remito, amortización, horas y fijos de dos períodos, la factura que después se anula.
- **Comparación:** pantalla por pantalla contra §9.3 y §9.4, **al centavo**:
  - W-11, rubro por rubro, con monto y las dos ganancias;
  - W-36 de INSUMOS, con EPP;
  - W-37;
  - W-48;
  - W-12;
  - el % de W-05 y la pestaña de la ficha.
- **Después:** anulá la 0004-00000020 y verificá §9.4 **sin recargar** (la rendición se actualiza sola, en ≤ 15 s).
- **Si un número no da:**
  1. buscá el renglón que lo explica;
  2. anotalo con la cuenta;
  3. fijate si la guía §13 lo cubre.

**Rol 2 · Carga de facturas y gastos (PC).**
- **W-10:**
  - factura con 5 renglones repartidos entre dos obras y "(sin obra)";
  - total y reparto en vivo;
  - proveedor de la base y **alta rápida W-38** sin perder lo cargado;
  - archivo **y** link;
  - **factura repetida**: el aviso al salir del campo y el 422 al guardar, mismo proveedor y número con o sin ceros y
    guiones;
  - decimales, cantidades con 3 decimales y redondeo por renglón.
- **W-35:** gastos de obra rendidos (fecha y "rendido por" por renglón), combustible, consumo de stock.
- **W-46 desde la rendición:**
  - amortización: una por obra, la segunda con "Corregir";
  - retiros con remito: material de la lista y "No está en la lista", remito repetido que avisa;
  - devoluciones en negativo;
  - horas y fijos por período, superpuestos.
- **W-33:**
  - filtros que se combinan en la dirección;
  - "549.000 de 905.600" con filtro de obra;
  - agrupar por proveedor, obra y rubro;
  - "ver todos" desde la rendición, **sin límite de fecha**;
  - mes en curso vacío **vs** "Todavía no hay gastos cargados.";
  - tachados que no suman.
- **W-34:** detalle, corregir con historial (antes → después), 409 con dos pestañas, anular con motivo.
- **W-14:** gastado por obra del proveedor.
- **Buscador:** número de factura y de remito, con y sin ceros.

**Rol 3 · Rendición y control por cantidad.**
- **Con obras propias variadas** (además del caso del rol 1):
  - solo por rubro, solo total, mixta;
  - sin presupuesto;
  - sin monto;
  - con un rubro sin presupuesto y gastado;
  - presupuesto 0.
- **Qué mirar:**
  - W-11: avisos, rojo **con texto o ícono**, "Actualizada HH:MM" y que se refresque sola, "Últimos gastos" y "ver
    todos";
  - W-36 y W-37: orden, filtros, pie igual al gastado;
  - W-12: alcance y fila total;
  - W-48: alta, corrección y baja de materiales, "fuera de presupuesto" y "pasar a la lista", resta llevar negativa,
    no se quita un material usado (aunque esté anulado), el retiro desde W-48 que baja "resta llevar";
  - la pestaña Rendición de la ficha y el % del listado de obras.
- **Fechas** en hora de Argentina, también con el navegador en UTC.

**Rol 4 · Permisos, alcance y errores.**
- **La matriz real, persona por persona:**
  - Laura (Ingeniería): Gastos (ver) y Rendiciones (ver), **solo sus obras**, **sin monto ni ganancia**, sin cargar;
  - Dirección y Jefatura de Proyectos;
  - Compras;
  - un sector sin acceso.
- **Cada endpoint por URL:**
  - IDs ajenos e inexistentes (404 entendible);
  - acciones que no le tocan, con el texto de la spec;
  - una factura repartida en una obra que no ve: solo ve su parte;
  - la factura repetida de una obra ajena: no le dice de quién es.
- **Cada formulario atacado:**
  - vacíos, largos, emojis, ñ;
  - importes con coma y punto, negativos donde no van, 0, 999.999.999,99;
  - fechas futuras;
  - archivos de 0 B, 20 MB y 21 MB;
  - doble clic, F5 a mitad, sesión vencida con borrador.

**Rol 5 · Celular y accesibilidad.**
- Pixel 7 e iPhone SE 375×667.
- **Barra:** abajo y "Más" con Gastos y Rendiciones.
- **W-44:** la rendición del celular, legible al sol.
- **Carga:** **cargar una factura en el celular** con la foto real.
- **W-33 y W-48:** en tarjetas.
- **Sin scroll horizontal, controles de 44 px, tarjetas tocables enteras.**
- **Teclado y foco** en los formularios de carga (tabla de renglones) y en los diálogos.
- **Arreglos PU-901…903, al pasar:**
  - la ficha de la obra en PC 1366×657 no baja sola;
  - con contadores, la pestaña activa queda a la vista;
  - tocar una pestaña del Inicio que carga no sube la página.

**Rol 6 · Tareas, regresión y backend.**
- **DU-C01:** recorré las pantallas de tareas, la campana, **cada mail** (en el log) y el Excel. Que no quede
  "pedido" visible donde es una tarea, con concordancia ("la tarea", "completada"). La excepción: "retiros o pedidos" en
  materiales.
- **Pasada corta por lo aprobado de las Semanas 1 y 2** que la 3 tocó:
  - barra;
  - ficha y listado de obras;
  - proveedores;
  - buscador;
  - una tarea de punta a punta con aviso y mail.
- **Backend:**
  - health;
  - `failed_jobs` 0;
  - canal `errors`;
  - workers y `schedule:work`;
  - que el cache de la rendición no deje números viejos después de cargar, corregir o anular.

**Rol 8 · Agregados del cliente** (pedidos de Bruno; DU-C02 y DU-C03 en `docs/dudas.md`, `gastos.md` RN-GAS-51…63 y
`rendicion.md` RN-REN-62 y 63):
- **Parte de horas (W-10, tipo "Parte de horas"):**
  - período, renglones con empleado (con sugerencias), obra, horas y valor hora;
  - **"Pegar desde Excel"** con filas reales de una planilla ficticia: obras por N.º y por nombre, una que no existe,
    decimales con coma;
  - total en vivo y confirmación con las obras donde restó;
  - en cada rendición, HORAS LIQUIDADAS sube lo justo;
  - superposición en los dos sentidos (parte sobre horas por período, y horas por período sobre un parte), con "Cargar
    igual";
  - corregir y anular.
- **Privacidad:** con Gastos = editar se ve el detalle por persona (empleado y valor hora). **Laura (Ingeniería)** ve el
  parte resumido por obra, sin nombres ni valor hora, en W-34, W-36 y W-11, y la planilla le da "no encontrado". Que se
  filtre un valor hora por persona a quien no edita Gastos es **alta**.
- **Pagos:**
  - toda factura nueva arranca "Impaga";
  - "Marcar como paga" con fecha (no futura) y "Marcar como impaga";
  - en W-33 la insignia y el filtro Pago, con el total de lo impago;
  - "(impaga)" en el buscador;
  - historial;
  - en la tabla de permisos, la fila "Pagos de facturas" con la configuración inicial (editar: Gerencia y Jefatura de
    Administración; ver: Dirección y Compras).
  - Compras ve el estado pero no marca; un sector sin acceso no ve ni la insignia ni el filtro.
  - Un gasto sin documento no tiene estado de pago.

**Rol 7 · QC final.**
- Evidencia y duplicados.
- Lo "esperado" del §1 no reportado como hallazgo.
- Severidades coherentes con las semanas 1 y 2; los números de plata, con la regla de §0.
- Veredicto.

**Tandas y ingresos:**
- **Tanda 1:** roles 1, 2 y 6.
- **Tanda 2:** roles 3 y 4.
- **Tanda 3:** roles 5 y 8.
- **Al final:** QC y consolidación.
- **Ingresos (20 códigos por IP por hora):** el Lead entra una vez con cada persona y reparte las sesiones. Dirección y
  un sector sin acceso: dalos de alta con Diego.

## 3. Entorno, reportes, Git, cierre y veredicto

Igual que **§3 a §7** del prompt de la Semana 2, con estos cambios:
- **Worktrees:** `tecnoservicios_backend_qa_s3` (rama `qa/prueba-usuario-semana-3`, desde `origin/staging`) y
  `tecnoservicios_frontend_qa_s3`. No toques las carpetas principales ni `*_gastos`, `*_rendicion` ni `*_fix`:
  desarrollo sigue trabajando.
- **Migraciones nuevas** (gastos, renglones, materiales). Hay que resetear:
  1. verificá con tinker que la base es `tecnoservicios_prueba_usuario`;
  2. `migrate:fresh --seed`;
  3. borrá las claves de sesión del prefijo de la prueba en Redis.
- **Front:** `npm ci` (hay dependencias del rediseño). Gastos y Rendición vienen encendidos por defecto; no pongas
  `VITE_MODULOS_ENTREGADOS`.
- **Base:** la obra del caso de referencia y sus datos se cargan **por pantalla**, no por seeder (rol 1). Los demás
  roles usan sus propias obras con un prefijo (`QAS3-…`) para no mover los números del rol 1.
- **Reportes:** en `docs\qa\prueba-usuario\semana-3\`, con hallazgos desde **PU-1001**. En `reporte.md` va la **§7.1
  del backlog punto por punto** y **el caso de referencia con cada número esperado vs obtenido**.
- **Severidades** (además de las de siempre):
  - **alta:** un número de plata distinto sin explicación en §13, o plata visible para quien no tiene permiso;
  - **media:** la guía §13 no alcanza para explicar una diferencia que sí es correcta.
- **Veredicto:** la regla del §7, con la §7.1 del backlog en lugar de la §6.1.

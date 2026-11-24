# Brief común — Semana 3 · Tanda 1 (NS-2026-025 Tecnoservicios)

Sos parte del equipo de desarrollo. Rutas relativas a `C:\NexoSmart\Projects\tecnoservicios_project\`.

## Leé antes de codear (completos)
- `CLAUDE.md` y `CONTRIBUTING.md` del repo que te toca (backend o frontend).
- **Backend, la documentación de base:**
  - `docs/arquitectura.md`, `docs/modelo-datos.md` (secciones Gastos y Rendición, ajustadas el 24-nov) y `docs/adr/`
    (todos aceptados, en especial ADR-006 matriz y alcance, ADR-007 renglones = única fuente del gastado, ADR-008
    importes como string decimal, ADR-011 idempotencia, ADR-014 archivo o link).
  - `docs/backlog.md` §2 (DoR), §3 (DoD), **§7 y §7.1 (semana 3 y prueba del cliente)** y §8 (riesgo).
- **Tu contrato:**
  - `docs/especificacion/gastos.md` y `docs/especificacion/rendicion.md`;
  - `docs/dudas.md`: todas resueltas, **va la propuesta**, DU-G01…G11 y DU-R01…R09. DU-G08 se reemplazó por DU-R05 y
    DU-R03 por DU-G05: no hay nota de crédito.
- **Las dos specs dicen lo mismo en todo lo que comparten.** Si encontrás una contradicción, gana la spec que es dueña
  del dato:
  - Gastos, en la carga y los renglones;
  - Rendición, en los números de la rendición y los materiales.

  Seguí con eso y reportala.
- **Maquetas:** `tecnoservicios\docs\mockups\wireframes.html`, en la sección `id="W-XX"` de cada pantalla.
- **El patrón a seguir:** Obras y Pedidos (backend `app/Modules/Obras` y `app/Modules/Pedidos`, front `src/features/obras`
  y `src/features/pedidos`):
  - casos de uso, resources, permisos por matriz con alcance;
  - idempotencia, versión `updated_at` con 409;
  - adjuntos (`App\Shared\Adjuntos`, contextos registrados en el provider);
  - actividad / historial;
  - `useCambiosSinGuardar`, `AvisoEnVista`;
  - fechas con `src/shared/dominio/fechas.ts`, textos en `mensajes.ts`.
- **Front:** el sistema de diseño "Obrador" ya está en `main`:
  - `docs/ui/tokens.md`, `docs/ui/componentes.md` y `docs/ui/direccion-visual.md`;
  - componentes de `src/shared/ui`;
  - íconos de `lucide-react`;
  - **solo variables de `tokens.css`**, sin colores ni tamaños sueltos.

  Las pantallas nuevas tienen que verse como las de Pedidos y Obras.

## Reglas de oro
- **No cambiar la funcionalidad** respecto de la spec y el backlog: no agregar, no quitar, no modificar. Si falta algo,
  elegí lo más cercano a cómo trabaja hoy el cliente (su rendición en Excel y su libro de facturas), seguí y reportalo.
- **Backend:**
  - **Forma del código:**
    - `declare(strict_types=1)`;
    - un caso de uso por clase, controllers delgados, FormRequest en el borde y API Resources;
    - envelope `{data,message,errors}` (`app/Shared/Http/`);
    - archivos de unas 200 líneas, sin números mágicos ni `catch` genérico;
    - nombres según ADR-004.
  - **Permisos:** siempre por la matriz (ADR-006), con alcance "solo sus obras" donde corresponda, nunca por roles.
  - **Tests:** cada endpoint lleva test permitido, denegado e IDOR.
  - **Catálogos y configuración:** por migración idempotente.
  - **Textos:** los de la spec, al pie de la letra.
- **Dinero:**
  - importes como string decimal, nunca float, también en JSON y en el front;
  - `total = round(cantidad × precio, 2)`, mitad para arriba en valor absoluto, calculado en el backend;
  - negativos solo en devoluciones.

  El front muestra con `src/shared/dominio` (formateo es-AR) y **nunca recalcula** un total que manda el back: si
  necesita uno antes de guardar (el total de la factura mientras se carga), usa la misma regla y el back manda.
- **Fechas:**
  - la empresa trabaja en hora de Argentina;
  - las fechas "del día" se cortan en `config('app.zona_empresa')`;
  - la fecha efectiva de un renglón es `gasto_renglones.fecha` (resuelta DU-G01).
- **Front:**
  - TS strict sin `any`;
  - mensajes del back tal cual;
  - mobile-first: Pixel 7 e iPhone SE 375×667, controles de al menos 44 px;
  - teclado y foco visibles;
  - tarjetas tocables enteras como en Pedidos.
- **Módulos encendidos:** `gastos` y `rendicion` **no** se encienden por defecto en `VITE_MODULOS_ENTREGADOS`. Eso es
  HU-087, en la tanda 2. Tus tests y e2e los encienden.
- **Datos ficticios siempre:** los de las maquetas o los del caso de referencia de `rendicion.md` §9. Nada de datos
  reales, secretos ni tokens en código, logs o commits. Los `.xlsx` del cliente **no se abren ni se commitean**.
- **Tests:**
  - **Nombres:** el nombre del test dice la regla.
  - **Antes de correr:** una suite con 0 assertions o con "connection refused" no cuenta. Verificá MySQL
    (`C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe -h 127.0.0.1 -u root -e "select 1"`) y Redis antes.
  - **Base de tests: SOLO la tuya.** Es la de abajo, ya está en tu `phpunit.xml` y está marcada skip-worktree: no la
    commitees.
  - **Bases prohibidas:**
    - nunca `tecnoservicios`, `tecnoservicios_testing`, `tecnoservicios_prueba_usuario`, `tecnoservicios_integracion`
      ni la de otro agente;
    - nunca `--env=testing`, porque apunta a la base de desarrollo;
    - nunca dropear bases.
- **En paralelo corre la ronda 3 de QA:**
  - carpetas `tecnoservicios_backend_qa_s2r3` y `tecnoservicios_frontend_qa_s2r3`, puertos 8340 y 5316, base
    `tecnoservicios_prueba_usuario`;
  - **no los toques** y no mates procesos ajenos (también hay otros proyectos en 8000 y 5173);
  - usá solo tus puertos y matá solo tus procesos.
- **Memoria de la máquina:** no dejes servidores ni navegadores colgados. Cerralos al terminar cada prueba.
- **Git:**
  - trabajás **solo en tu worktree y tu rama**, con commits chicos y conventional commits en inglés terminados en
    `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`;
  - **no pushees, no mergees, no toques otras ramas.**
  - Si el backend no encuentra una clase nueva, corré `composer dump-autoload`.
- **Al terminar:**
  - en el backend, actualizá `docs/qa/matriz-trazabilidad.md` en tu worktree, solo las filas de tus F-XX;
  - el **reporte final**, sin adornos:
    - historia → criterios cumplidos, y los que no, por qué;
    - salida de tests (conteo y assertions);
    - pint y phpstan, o typecheck, lint, format y build;
    - curls ejecutados;
    - desvíos y decisiones propias;
    - pendientes.

## Contratos compartidos (ya en `main`, commit `b72e919`)
Respetalos exactamente. Si tenés que cambiarlos, reportalo.

- **`Gastos\Contracts\GastadoDeObras`:**
  - **Métodos:**
    - `totalesPorRubro(obraIds)`;
    - `renglonesDeObra(obraId, FiltroRenglones)`, que devuelve `list<RenglonImputado>`;
    - `contarRenglonesPorMaterial(materialIds)`.
  - **Quién lo usa:** Gastos lo implementa (hoy `GastadoDeObrasNulo`) y Rendición lo usa.
  - **Qué garantiza:** las invariantes de `rendicion.md` §12.4.1 (Gastos las testea).
- **Eventos `Gastos\Domain\Events\{GastoImputado, GastoCorregido, GastoAnulado}`:**
  - `extends EventoDeGasto` (`gastoId`, `obraIds`: todas las obras afectadas, en la corrección las de antes ∪ las de
    después);
  - `ShouldDispatchAfterCommit`;
  - Gastos los dispara y Rendición los escucha para invalidar su cache.
- **`Rendicion\Contracts\MaterialesDeObra`:**
  - **Métodos:**
    - `opciones(obraId, texto)`, que devuelve `list<MaterialOpcion>`;
    - `perteneceAObra`;
    - `crearFueraDePresupuesto`, que corre dentro de la transacción de Gastos.
  - **Quién lo usa:** Rendición lo implementa (hoy `MaterialesDeObraNulo`) y Gastos lo usa.
- **`Rendicion\Contracts\CatalogoDeRubros`:** `imputables()`, `presupuestables()` y `resumeEn(clave)`. Ya tiene la
  implementación real (`CatalogoDeRubrosEloquent`).
- **Tabla `materiales` y modelo `Rendicion\Domain\Models\Material`:** ya están, en la migración `2026_11_24_100001`.
  Los renglones de Gastos la referencian por `material_id`.
- **Reemplazar un nulo:** el dueño lo reemplaza con `bind`, no con `bindIf`, en su provider, igual que Pedidos.
- **En tus tests:** del otro lado de un contrato, bindeá un fake.
- **API:** la de `gastos.md` §12 y la de `rendicion.md` §12.
  - El front programa contra eso con la API mockeada.
  - Si el back necesita desviarse, lo reporta, y el front sigue la spec.

## Asignación de la tanda 1

| Agente | Worktree · rama | Base de tests · puertos | Historias |
|---|---|---|---|
| **Back Gastos** | `tecnoservicios_backend_gastos` · `feat/w3-gastos-back` | `tecnoservicios_testing_gastos` · serve 8352 | Ver abajo. |
| **Back Rendición** | `tecnoservicios_backend_rendicion` · `feat/w3-rendicion-back` | `tecnoservicios_testing_rendicion` · serve 8353 | Ver abajo. |
| **Front Gastos** | `tecnoservicios_frontend_gastos` · `feat/w3-gastos-front` | E2E_PORT=5325 · dev 5326 | Ver abajo. |
| **Front Rendición** | `tecnoservicios_frontend_rendicion` · `feat/w3-rendicion-front` | E2E_PORT=5327 · dev 5328 | Ver abajo. |

**Back Gastos:**
- **Historias:**
  - HU-061…HU-073: factura con renglones, total, proveedor con alta rápida, archivo o link, factura repetida, sin
    comprobante, amortización, retiros, devoluciones, horas y fijos, listado y filtros, detalle, corregir y anular;
  - HU-084, la parte back: implementa el puerto `Proveedores\Application\Ports\GastadoPorProveedor`;
  - HU-085, la parte back: grupo gastos del buscador;
  - HU-086, la parte de Gastos.
- **Tablas:** las migraciones de `gastos` y `gasto_renglones`, después de `materiales`.
- **Contratos:**
  - implementa `GastadoDeObras` y dispara los 3 eventos;
  - con `MaterialesDeObra`, en los tests bindea un fake.
- **Adjuntos:** registra los contextos `comprobante_gasto` y `remito` según `identidad.md` §6.6.
- **Permisos:** de las cargas desde la rendición, según DU-R05.

**Back Rendición:**
- **Historias:**
  - HU-074…HU-083: rendición por rubro, pasados en rojo, monto y ganancia (DU-R01), detalle de rubro, por proveedor,
    pestaña y celular (la API), rendiciones en curso, % gastado, lista de materiales, retirado y resta llevar;
  - HU-086, la parte de Rendición y Obras.
- **Contratos que implementa:**
  - `MaterialesDeObra`, el real;
  - el puerto `Obras\Application\Ports\AvanceGastoDeObra`, el real;
  - el puerto nuevo `MaterialesDePresupuesto`.
- **Contratos que amplía:** `ObrasConsulta` y `PresupuestadoDeObra`, como dice `rendicion.md` §12.4.2. Esos son
  archivos de `app/Modules/Obras`: tocá solo esos y sus tests.
- **Cache:** escucha los eventos de Gastos y `PresupuestoMontosCambiados` para invalidarla.
- **El caso de referencia de `rendicion.md` §9 es un test de aceptación obligatorio:** con un fake de `GastadoDeObras`
  que devuelve esos renglones, la rendición tiene que dar exacto, rubro por rubro. En la tanda 2 se corre contra los
  gastos reales.

**Front Gastos:**
- **Pantallas de HU-061…HU-073:**
  - W-10, la carga de factura;
  - W-38, el alta rápida de proveedor;
  - W-35, los gastos sin comprobante;
  - W-46, las cargas desde la rendición: amortización, retiros, devoluciones, horas y fijos. Es un formulario que se
    abre desde la rendición: exportá la ruta y el link para que Front Rendición lo enlace;
  - W-33, gastos cargados;
  - W-34, el detalle, con corregir y anular.
- **Además:**
  - el bloque "Gastado por obra" de la ficha del proveedor (W-14, HU-084);
  - el grupo Gastos del buscador (W-18, HU-085);
  - el ítem Gastos de la barra, con "(ver)" (F-03, RN-IDE-11 y RN-IDE-12);
  - el celular de todo eso.

**Front Rendición:**
- **Pantallas de HU-074…HU-083:**
  - W-11, la rendición de la obra;
  - W-36, el detalle de rubro;
  - W-37, por proveedor;
  - W-44, el celular;
  - W-12, rendiciones en curso;
  - W-48, el control por cantidad, con la lista de materiales y su edición;
  - la pestaña Rendición de la ficha (W-07, F-17);
  - el % gastado en el listado de obras (W-05, W-47, F-15);
  - el ítem Rendiciones de la barra.
- **Links a las cargas:** van a la ruta de W-46 que arma Front Gastos. Hasta que esté, usá la ruta de la spec.

**Tanda 2 (después):**
- HU-087, encender la semana 3;
- la integración real front ↔ back con el caso de referencia cargado por la API;
- la prueba del cliente de la semana 3;
- QC.

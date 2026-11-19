# Brief común — Semana 2 · Tanda 1 (NS-2026-025 Tecnoservicios)

Sos parte del equipo de desarrollo. Rutas relativas a `C:\NexoSmart\Projects\tecnoservicios_project\`.

## Leé antes de codear (completos)
- `CLAUDE.md` y `CONTRIBUTING.md` del repo que te toca (backend o frontend).
- Backend: `docs/arquitectura.md`, `docs/modelo-datos.md`, `docs/adr/` (todos aceptados), `docs/backlog.md` §2 (DoR), §3
  (DoD) y **§6 (semana 2)**, `docs/especificacion/pedidos.md` y `docs/especificacion/avisos.md` (**son tu contrato**),
  `docs/dudas.md` (todas resueltas: **va la propuesta**, DU-Q01…Q03 y DU-P01…P24 incluidas).
- Maquetas: `tecnoservicios\docs\mockups\wireframes.html` (sección `id="W-XX"`) y notas en
  `C:\Users\GREEN\AppData\Local\Temp\claude\4fe530b1c11d8e8d\7321fd61-f89b-4a2a-ab84-df45cc04969d\scratchpad\maquetas-notas.txt`.
- Mirá cómo está hecho Obras (backend `app/Modules/Obras`, front `src/features/obras`): es el patrón a seguir
  (casos de uso, resources, permisos por matriz, idempotencia, versión `updated_at` con 409, borradores,
  `useCambiosSinGuardar`, `AvisoEnVista`, fechas con `src/shared/dominio/fechas.ts`, textos en `mensajes.ts`).

## Reglas de oro
- No agregar, quitar ni cambiar funcionalidad respecto de spec y backlog. Si falta algo, elegí lo más cercano a cómo
  trabaja hoy el cliente (la minuta), seguí y reportalo.
- Backend: `declare(strict_types=1)`, un caso de uso por clase, controllers delgados, FormRequest en el borde, API
  Resources, envelope `{data,message,errors}` (`app/Shared/Http/`), archivos de ~200 líneas, sin números mágicos, sin
  `catch` genérico, nombres según ADR-004. Permisos **siempre por la matriz** (ADR-006), nunca roles. Cada endpoint:
  test permitido, denegado e IDOR. Catálogos/config por **migración** idempotente. Textos al usuario en español
  rioplatense y sin jerga, con los de la spec al pie de la letra.
- **Fechas:** la empresa trabaja en hora de Argentina. Las fechas "del día" se cortan en `config('app.zona_empresa')`
  (ver `PresupuestoResource`), nunca en UTC; los instantes viajan ISO-8601 con huso. "Vencido", "hoy", "mañana" y el
  recordatorio se calculan en esa zona.
- Front: TS strict sin `any`, componentes de `src/shared/ui`, mensajes del back tal cual, mobile-first (Pixel 7 y
  iPhone SE 375×667, controles ≥ 44 px), teclado y foco visibles.
- Datos ficticios siempre (los de las maquetas). Nada de datos reales, secretos ni tokens en código, logs o commits.
- **Tests:** nombre que dice la regla. Suite con 0 assertions o "connection refused" no cuenta: verificá MySQL
  (`C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe -h 127.0.0.1 -u root -e "select 1"`) y Redis antes.
  **Base de tests: SOLO la tuya** (abajo; ya está en tu `phpunit.xml`, marcado skip-worktree: no lo commitees). Nunca
  `tecnoservicios`, `tecnoservicios_testing`, `tecnoservicios_prueba_usuario` ni `tecnoservicios_integracion`. Nunca
  dropear bases.
- **En paralelo corre la ronda 5 de QA** (carpeta `tecnoservicios_backend_qa5`, puertos 8340 y 5316, base
  `tecnoservicios_prueba_usuario`, y la carpeta `tecnoservicios_frontend` en `staging`). **No los toques**, no mates
  procesos ajenos (también hay otros proyectos en 8000 y 5173). Usá solo tus puertos y matá solo tus procesos.
- Memoria de la máquina: no dejes servidores ni navegadores colgados; cerralos al terminar cada prueba.
- Git: trabajás **solo en tu worktree y tu rama**, commits chicos con conventional commits en inglés terminados en
  `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`. **No pushees, no mergees, no toques otras ramas.**
- Al terminar: actualizá `docs/qa/matriz-trazabilidad.md` (solo las filas de tus F-XX) en tu worktree (backend).
- **Reporte final:** HU → criterios cumplidos (y los que no, por qué), salida de tests (conteo y assertions),
  pint/phpstan o typecheck/lint/format/build, curls ejecutados, desvíos y decisiones propias, pendientes. Sin adornos.

## Contratos compartidos (ya en `main`; respetalos exactamente, no los cambies sin reportarlo)
- **Eventos de dominio** `app/Modules/Pedidos/Domain/Events/`: `PedidoAsignado`, `PedidoReasignado`, `PedidoTomado`,
  `PedidoBloqueado`, `PedidoComentado`, `PedidoCompletado`, `PedidoReabierto`, todos `extends EventoDePedido`
  (`pedidoId`, `actorId`, `actividadId`, `Participantes`, `FotoDelPedido`), `ShouldDispatchAfterCommit`.
  Pedidos los **dispara**; Avisos los **escucha** (un listener por evento, cola `default`).
- `Pedidos\Contracts\PedidosParaRecordar::conEntregaEl(fecha): list<PedidoParaRecordar>` — Pedidos implementa (hoy
  `PedidosParaRecordarNulo`), Avisos usa.
- `Avisos\Contracts\AvisosDelPedido::enviadosDe(pedidoId): list<AvisoEnviado>` y
  `Avisos\Contracts\DestinatariosDeAviso::deAsignacion(Participantes, actorId): list<User>` — Avisos implementa (hoy
  nulos), Pedidos usa.
- Para reemplazar un nulo, el dueño cambia el `bindIf` de su `ServiceProvider` por la implementación real.
- Del otro lado de un contrato, en tus tests bindeá un fake.
- **API:** la del §11 de `pedidos.md` y el §9 de `avisos.md`. El front programa contra eso con la API mockeada. Si el
  back necesita desviarse, lo reporta, y el front sigue la spec.

## Asignación de la tanda 1

| Agente | Worktree · rama | Base de tests · puertos | Historias |
|---|---|---|---|
| **Back Pedidos** | `tecnoservicios_backend_pedidos` · `feat/w2-pedidos` | `tecnoservicios_testing_pedidos` · serve 8350 | HU-059 · HU-026…HU-039 (crear, prioridad, detalle, comentarios, seguir, editar, estados, reasignar, reabrir, recorrido, Mis tareas e Inicio). Dispara los eventos, implementa `PedidosParaRecordar`, el puerto `PedidosDeObra` (HU-059 c4) y los contextos de adjuntos de pedido. |
| **Back Avisos** | `tecnoservicios_backend_avisos` · `feat/w2-avisos` | `tecnoservicios_testing_avisos` · serve 8351 | HU-045…HU-049 (campana, destinatarios por evento, mail, "vence mañana", lista de avisos, Mis avisos en Mi perfil) y la parte backend de HU-060 (cola `mail`, scheduler en `config/supervisor.php`, recordatorio horario). Implementa `AvisosDelPedido` y `DestinatariosDeAviso`. |
| **Front Pedidos** | `tecnoservicios_frontend_pedidos` · `feat/w2-pedidos` | E2E_PORT=5320 · dev 5322 | UI de HU-026…HU-039: W-08 (crear, también W-43 en el celular), W-09 detalle con comentarios y recorrido, W-29…W-32 (completar, bloquear/retomar, reasignar, editar), reabrir, seguir, W-01 Inicio (Mis tareas, Pedidos que pedí, Tareas que sigo), W-41/W-42 celular. |
| **Front Avisos** | `tecnoservicios_frontend_avisos` · `feat/w2-avisos` | E2E_PORT=5321 · dev 5323 | UI de HU-045…HU-049: campana con contador y polling (ADR-005), W-02/W-45 lista de avisos, W-19 "Mis avisos" en Mi perfil, y HU-060 c1 (barra con Pedidos, Calendario, campana; abajo en el celular Tareas · Obras · Avisos · Más). Los links "Ver el pedido" van a `/pedidos/{id}` (lo arma Front Pedidos). |

Tanda 2 (después): HU-040…HU-044 (mi gente, carga por persona, todos los pedidos, sin obra, calendario), HU-050
equipos, HU-051…HU-053 celular, HU-054…HU-058 (integración con Obras, buscador, sectores con pedidos), integración
real front ↔ back y QC.

---

## Tanda 2 (2026-11-19) — la tanda 1 ya está en `main`

Tanda 1 en `main` (back `60c3723`, front `36bd778`): Pedidos HU-026…HU-039, Avisos HU-045…HU-049 y HU-060 c1, con
pruebas de punta a punta de Pedidos + Avisos en el backend (`tests/Feature/Pedidos/AvisosDePuntaAPuntaTest.php`).
Mismas reglas de arriba. Las carpetas se reusan (ya tienen `vendor`/`node_modules`; si el backend no encuentra una
clase nueva, `composer dump-autoload`).

| Agente | Worktree · rama | Base · puertos | Historias |
|---|---|---|---|
| **Back Pedidos 2** | `tecnoservicios_backend_pedidos` · `feat/w2-pedidos-2` | `tecnoservicios_testing_pedidos` · serve 8350 | HU-040 (mi gente), HU-041 (carga por persona), HU-042 (todos los pedidos + exportar a Excel, DU-P16), HU-043 (sin obra, DU-P13), HU-044 (calendario), HU-050 (equipos en reparación, DU-Q03/P18, contextos `equipo_presupuesto` y `equipo_inventario`), HU-054…HU-056 (lo que falte de la integración con Obras: columna, fila Servicios generales, W-22 con DU-P12), HU-057 (buscador: grupo pedidos, DU-P15), HU-058 (sectores y personas con pedidos abiertos, DU-Q02/P09). |
| **Front Pedidos 2** | `tecnoservicios_frontend_pedidos` · `feat/w2-pedidos-2` | E2E_PORT=5320 · dev 5322 | UI de esas mismas historias: W-26 Tareas de mi gente (pestaña de Inicio), W-04, W-24 + exportar, W-25, W-03 calendario, W-49, pestaña Pedidos de la ficha (W-07) y columna/fila del listado (W-05, W-47), W-22 con los pedidos abiertos, grupo Pedidos del buscador (W-18), avisos de W-39/W-40 (HU-058), y HU-051…HU-053 (celular: lo que falte de W-41…W-43 y W-47). |
| **Integración real** | back `tecnoservicios_backend_avisos` y front `tecnoservicios_frontend_avisos` · `feat/w2-integracion` en los dos | `tecnoservicios_integracion` · 8320 / 5312 (las de `playwright.integracion.config.ts`), con `BACKEND_DIR` apuntando al worktree del back | `npm run e2e:integracion` al día con la semana 2 (la barra cambió: Inicio = Mis tareas) y specs nuevos de punta a punta contra el backend real: crear un pedido → aviso en la campana del propietario **y** mail en el log; tomar, comentar, completar con comprobante (también @celular); reasignar al sector; el recordatorio "vence mañana" con `avisos:recordatorios`; Mis avisos por mail. Es la "señal de que la prueba peligra" del backlog §6.1. Cada diferencia de contrato front ↔ back que encuentres: arreglala con el diff mínimo (schemas/api del front o resource del back), con test, y listala en el reporte. **No** toques pantallas que esté haciendo Front Pedidos 2 (las de HU-040…HU-058): si el problema es ahí, reportalo. |

# Tareas

Plan de trabajo con ítems chequeables. Cada tarea termina con una sección de revisión.

## Sprint 0 — cerrado (2026-11-12)
- [x] Base del backend (Laravel 13, módulos, envelope, /health, /errors, CI)
- [x] Base del frontend (React 19, barra por permisos, cliente API, CI)
- [x] Arquitectura, modelo de datos, ADR-001…014 aprobados por Juani
- [x] Backlog semana 1 (25 historias, todas listas), matriz F-01…F-68, plan de pruebas
- [x] Especificación de Identidad, Obras y Proveedores; 43 dudas resueltas
- [x] ADR-014: archivo o link en todos los documentos (amplía D-022)

## Semana 1 — a retomar el 2026-11-13
Plan (brief de agentes: rehacerlo con las mismas reglas; está en `tasks/brief-agentes-semana1.md`):
- [x] Tanda 1 en paralelo (2026-11-13, sin push ni merge; esperando OK de Juani):
  - Backend Identidad (HU-001…011): rama `feat/w1-identidad`, base `tecnoservicios_testing`
  - Backend transversal (HU-025 adjuntos, RegistroActividad, middleware idempotent): worktree propio + base `tecnoservicios_testing_shared` (ya creada). Copiar `vendor/` y correr `composer dump-autoload` antes de lanzar el agente (tarda varios minutos en esta máquina)
  - Frontend identidad + barra + AdjuntosField (HU-001…011, 023, 024, 025 UI): rama `feat/w1-identidad`
- [x] Ingreso con código por mail (ADR-015) + pendientes del merge, back y front — en main
- [ ] Integración front ↔ backend real (hasta ahora todo el front está probado con API mockeada; confirmar D-UI-03/04/05)
- [ ] `modelo-datos.md`: reflejar desvíos de Obras (`presupuesto_montos` en Obras, `modo_carga` columna) y Proveedores (`nombre_busqueda`, CUIT char(11))
- [x] Tanda 2 (2026-11-13, en main): backend Obras (HU-012…019, base `tecnoservicios_testing_obras`) y Proveedores + buscador (HU-020…022, base `tecnoservicios_testing_proveedores`); front de Obras y Proveedores
- [ ] Revisión del Tech Lead, `docs/seguridad.md`, QC `docs/qc/entrega-1.md`, notas de entrega, merge a main
- [x] Contratos compartidos: interfaces `Permisos` y `Jerarquia` en `app/Modules/Identidad/Contracts/`; 12 claves de función de `especificacion/identidad.md`; front en puerto 5310 (agregarlo a SANCTUM_STATEFUL_DOMAINS)

## Pendientes de Juani
- Servidor de staging y producción (para `docs/deploy.md` y la URL de `/bug-report`)
- Avisarle al cliente que ADR-014 amplía lo firmado (archivos además de links)
- Avisarle al cliente que ADR-015 cambia F-01 (ingreso con código por mail, sin contraseña)

### Revisión Tanda 1 (2026-11-13)
- Identidad (`feat/w1-identidad`): 149 tests / 753 assertions verde, pint ok, larastan 0. HU-001…011.
- Transversal (`feat/w1-transversal`, worktree `../tecnoservicios_backend_shared`): 118 / 521 verde (base `_shared`), pint ok, larastan 0.
- Front (`feat/w1-identidad`): vitest 112 ok, e2e 26 ok (API mockeada), typecheck/lint/format ok.
- `git merge-tree` identidad × transversal: sin conflictos de texto.
- Al mergear: bindear RegistroActividad real (6 tests con espía → aserción en `actividades`), `idempotent` en POST usuarios/sectores, `AdjuntoResource` lee `nombre`, actualizar `modelo-datos.md` (plazos en una fila, `users.nombre`).
- Entorno de esta máquina: MariaDB 10.4 (XAMPP), Redis portable en scratchpad; PHP `upload_max_filesize` 8M (prod necesita ≥21M).

### Revisión Tanda 2 (2026-11-13)
- Backend en main: 433 tests / 2292 assertions, pint ok, larastan 0 (Identidad + Shared + Proveedores + Obras).
- Front en main: vitest 168 ok, e2e 44 ok (API mockeada), typecheck/lint/format ok.
- Rubros: una sola tabla (`2026_11_13_300001`, modelo en Rendicion); Obras la reusa. Buscador general en Shared (`/api/buscar`), grupos obras y proveedores.

### Integración y revisión (2026-11-13, en main)
- [x] Integración real front ↔ backend: `npm run e2e:integracion` 42 ok (backend 8320 + front 5312, base `tecnoservicios_integracion`). Bug: 422 fuera de formulario mostraba mensaje genérico (arreglado).
- [x] Revisión Tech Lead + seguridad (`docs/seguridad.md`), hallazgos arreglados; SEG-01 bloqueo progresivo y TL-02 (enmienda ADR-003) decididos por Juani.
- [x] `modelo-datos.md` alineado con las migraciones.
- Suites en main: backend 483 / 2633; front unit 215, e2e mock 44, e2e real 42.
- [ ] Mensajes de validación del backend en español (hoy las reglas sin mensaje propio responden en inglés)
- [ ] QC `docs/qc/entrega-1.md` + notas de entrega para el cliente
- [ ] Diseño pendiente: TL-03 (meta paginado), TL-07 (asociar con usuario), TL-08 (Shared → User), renombrar tests viejos a la regla

### Cierre del día (2026-11-13)
- [x] Mensajes del backend en español y sin jerga (lang/es a mano, 27 FormRequest cubiertos)
- [x] QC `docs/qc/entrega-1.md`: apta (18 ✅ · 4 ⚠️ · 0 ❌); bugs B-01…B-05 arreglados
- [x] Notas `docs/entregas/semana-1.md` (BORRADOR) + `CHANGELOG.md`
- Suites en main: backend 518 / 2859; front unit 225, e2e mock 46, e2e real 42.
- Semana 1 ≈ 98 % de lo nuestro. Para cerrar (Juani): staging/prod (límites de subida ≥ 21 MB), avisar ADR-014/015 al cliente, revisar y mandar notas.

## Próxima sesión
1. Levantar MariaDB (XAMPP) y Redis portable; verificar `select 1` y `PING`.
2. Matriz de trazabilidad: sumar tests de los fixes de QC (B-01…B-05).
3. Semana 2 (Pedidos / minuta): Sprint-0 de la semana — especificación `docs/especificacion/pedidos.md`, backlog HU, dudas; después tandas con agentes como en semana 1.

## Prueba de usuario semana 1 (2026-11-16)
Reporte: `docs/qa/prueba-usuario/semana-1/` (45 hallazgos PU-001…045: 2 bloq · 3 altas · 18 medias · 22 bajas).
Juani aprobó PU-043 (ñ ≠ n en duplicados de proveedor), PU-044 (buscar proveedor por CUIT) y PU-045 (pista en el ingreso + gerencia destraba el bloqueo desde Usuarios).
Arreglos en ramas, **sin mergear y sin verificar** (los agentes se cortaron a mitad):
- Backend `fix/w1-prueba-usuario-back`: 11 commits (401 JSON, 4xx fuera de `errors`, búsqueda con signos, links con ñ, minutos de espera, health sin Redis + cola de mail, revisión, fechas del historial, doble punto, 409 por edición vieja con `updated_at`, ñ + CUIT, quitar bloqueo de ingreso). Falta: correr pint/phpstan/suite completa; DU-Q04…Q06 en dudas.md ya escritos, revisar bitácora/ADR-015/especificaciones.
- Front `fix/w1-pu-formularios` (carpeta principal): último commit `wip` con todo lo hecho; faltan tests de PegarLink y verificar todo.
- Front `fix/w1-pu-listas` (worktree `../tecnoservicios_frontend_listas`): último commit `wip`; verificar.
### Próxima sesión
1. Revisar cada rama contra su lista de PU (prompts de los agentes en esta sesión), completar lo que falte y dejar suites en verde.
2. Front: UI de PU-045 (bloqueo en Usuarios + pista "¿No te llegó?…" en el ingreso) y fechas del historial si quedan en el front.
3. Mergear las 3 ramas, `npm run e2e` y `npm run e2e:integracion`, re-probar los PU graves; borrar ramas y el worktree.
4. Actualizar el prompt de la prueba: worker `--queue=mail,default`, iPhone SE 375×667.
5. Semana 2: Juani confirma las 27 dudas (DU-P01…P24, DU-Q01…Q03) → tandas de Pedidos y Avisos.

### Revisión arreglos de la prueba de usuario (2026-11-17)
- [x] Backend `fix/w1-prueba-usuario-back` en main: 557 tests / 3091, pint y larastan 0. PU-043/044/045 hechos y documentados (DU-Q04…Q06, ADR-015 enmienda).
- [x] Front `fix/w1-pu-formularios` + `fix/w1-pu-listas` en main (conflictos resueltos en EditarObra y Proveedores) + UI de PU-045: vitest 310 ok, typecheck/lint/format ok, e2e mock 46/46.
- [x] `npm run e2e:integracion` 42/42 (2026-11-17). Encontró un bug del guard de cambios sin guardar (el link al proveedor/obra existente quedaba frenado): arreglado. Base `tecnoservicios_integracion` creada en esta máquina (el health da 503 sin base).
- [x] Ramas mergeadas y worktree borrados.
- Flaky: `EditarObraPantalla.test.tsx` "guarda los datos de la obra y, aparte, solo lo que cambió del principal" falló 1 vez bajo carga; solo pasa.
- [ ] Re-probar en el navegador los PU graves (001–005).

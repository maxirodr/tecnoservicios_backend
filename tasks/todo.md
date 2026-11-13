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
- [ ] Tanda 2: backend Obras (HU-012…019, base `tecnoservicios_testing_obras`) y Proveedores + buscador (HU-020…022, base `tecnoservicios_testing_proveedores`); front de Obras y Proveedores
- [ ] Revisión del Tech Lead, `docs/seguridad.md`, QC `docs/qc/entrega-1.md`, notas de entrega, merge a main
- [x] Contratos compartidos: interfaces `Permisos` y `Jerarquia` en `app/Modules/Identidad/Contracts/`; 12 claves de función de `especificacion/identidad.md`; front en puerto 5310 (agregarlo a SANCTUM_STATEFUL_DOMAINS)

## Pendientes de Juani
- Servidor de staging y producción (para `docs/deploy.md` y la URL de `/bug-report`)
- Avisarle al cliente que ADR-014 amplía lo firmado (archivos además de links)

### Revisión Tanda 1 (2026-11-13)
- Identidad (`feat/w1-identidad`): 149 tests / 753 assertions verde, pint ok, larastan 0. HU-001…011.
- Transversal (`feat/w1-transversal`, worktree `../tecnoservicios_backend_shared`): 118 / 521 verde (base `_shared`), pint ok, larastan 0.
- Front (`feat/w1-identidad`): vitest 112 ok, e2e 26 ok (API mockeada), typecheck/lint/format ok.
- `git merge-tree` identidad × transversal: sin conflictos de texto.
- Al mergear: bindear RegistroActividad real (6 tests con espía → aserción en `actividades`), `idempotent` en POST usuarios/sectores, `AdjuntoResource` lee `nombre`, actualizar `modelo-datos.md` (plazos en una fila, `users.nombre`).
- Entorno de esta máquina: MariaDB 10.4 (XAMPP), Redis portable en scratchpad; PHP `upload_max_filesize` 8M (prod necesita ≥21M).

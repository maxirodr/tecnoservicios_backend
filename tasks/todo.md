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
- [ ] Tanda 1 en paralelo:
  - Backend Identidad (HU-001…011): rama `feat/w1-identidad`, base `tecnoservicios_testing`
  - Backend transversal (HU-025 adjuntos, RegistroActividad, middleware idempotent): worktree propio + base `tecnoservicios_testing_shared` (ya creada). Copiar `vendor/` y correr `composer dump-autoload` antes de lanzar el agente (tarda varios minutos en esta máquina)
  - Frontend identidad + barra + AdjuntosField (HU-001…011, 023, 024, 025 UI): rama `feat/w1-identidad`
- [ ] Tanda 2: backend Obras (HU-012…019, base `tecnoservicios_testing_obras`) y Proveedores + buscador (HU-020…022, base `tecnoservicios_testing_proveedores`); front de Obras y Proveedores
- [ ] Revisión del Tech Lead, `docs/seguridad.md`, QC `docs/qc/entrega-1.md`, notas de entrega, merge a main
- [ ] Contratos compartidos: interfaces `Permisos` y `Jerarquia` en `app/Modules/Identidad/Contracts/`; 12 claves de función de `especificacion/identidad.md`; front en puerto 5310 (agregarlo a SANCTUM_STATEFUL_DOMAINS)

## Pendientes de Juani
- Servidor de staging y producción (para `docs/deploy.md` y la URL de `/bug-report`)
- Avisarle al cliente que ADR-014 amplía lo firmado (archivos además de links)

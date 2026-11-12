# ADR-013 · Tests y CI

- **Estado:** Aceptada (Juani, 2026-11-12) · **Fecha:** 2026-11-12 · **Rol:** QA + DevOps

## Decisión
- Backend: PHPUnit contra **MySQL real** `tecnoservicios_testing` (nunca la base de dev, nunca
  sqlite, nunca dropear bases). `RefreshDatabase`. Unit para reglas de dominio; feature por
  endpoint con permitido, denegado e IDOR. `--parallel` cuando la suite lo soporte.
- Front: Vitest + Testing Library; Playwright con viewport de PC y de celular; al menos un test
  funcional y un E2E de camino feliz por feature.
- Un solo agente o proceso corre la suite de backend a la vez: comparten la base de tests.
- Una suite con 0 assertions, o con "connection refused", es infraestructura caída: no cuenta.
- CI (GitHub Actions) en cada PR: lint, tipos y tests. El merge a `main` exige CI verde y
  revisión del Tech Lead.

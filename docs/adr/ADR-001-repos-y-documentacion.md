# ADR-001 · Dos repos; la documentación de producto vive en el backend

- **Estado:** Aceptada (Juani, 2026-11-12) · **Fecha:** 2026-11-12 · **Rol:** CTO

## Contexto
El trabajo se reparte en `tecnoservicios_backend` y `tecnoservicios_frontend`. Backlog, specs,
QA, QC, seguridad y deploy son del producto, no de un repo. Si se duplican, divergen.

## Decisión
- `tecnoservicios_backend/docs/` es la casa de la documentación de producto: arquitectura,
  modelo de datos, ADR, backlog, especificación por módulo, QA, QC, seguridad, deploy, notas de
  entrega, manual por sector, bitácora de firmas y dudas.
- `tecnoservicios_frontend/docs/` guarda solo lo de UI (tokens, bitácora de UI, dudas de UI) y
  enlaza al backend para todo lo demás.
- El repo `tecnoservicios` (relevamiento) es de solo lectura para el equipo de desarrollo: ahí
  vive el documento firmado y sus fuentes.

## Consecuencias
- Un agente nuevo empieza por `tecnoservicios_backend/CLAUDE.md` y `docs/`.
- Una historia que solo toca el front igual actualiza la matriz de trazabilidad en el backend.

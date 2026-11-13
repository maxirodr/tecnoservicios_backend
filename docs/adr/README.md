# ADR — Registro de decisiones técnicas

Una decisión por archivo `ADR-NNN-titulo.md`. Estados: **Propuesta** (espera a Juani) ·
**Aceptada** · **Reemplazada por ADR-XXX**. Nunca se borra un ADR: se reemplaza con otro.
Cualquier desvío del stack estándar de NexoSmart necesita un ADR aceptado por Juani.

| ADR | Título | Estado |
|---|---|---|
| [001](ADR-001-repos-y-documentacion.md) | Dos repos; la documentación de producto vive en el backend | Aceptada |
| [002](ADR-002-stack.md) | Stack: Laravel API + React SPA, MySQL, Redis, Resend, Supervisord | Aceptada |
| [003](ADR-003-monolito-modular.md) | Monolito modular con capas limpias | Aceptada |
| [004](ADR-004-idioma-del-codigo.md) | Idioma del código: lenguaje del cliente para el dominio | Aceptada |
| [005](ADR-005-tiempo-real-polling.md) | Tiempo real con polling corto, sin websockets en la Etapa 1 | Aceptada |
| [006](ADR-006-permisos-matriz.md) | Permisos: matriz sector × función con nivel y alcance | Aceptada |
| [007](ADR-007-rendicion-fuente-unica.md) | Rendición calculada desde una única fuente: los renglones | Aceptada |
| [008](ADR-008-importes-y-cantidades.md) | Importes y cantidades: decimales exactos y value objects | Aceptada |
| [009](ADR-009-autenticacion.md) | Autenticación: Sanctum SPA con cookie, mail + contraseña | Reemplazada en parte por ADR-015 |
| [010](ADR-010-auditoria.md) | Registro de quién hizo qué: tabla de actividades | Aceptada |
| [011](ADR-011-idempotencia.md) | Idempotencia en el backend con Idempotency-Key | Aceptada |
| [012](ADR-012-avisos.md) | Avisos: sistema + mail en cola y recordatorio programado | Aceptada |
| [013](ADR-013-tests-y-ci.md) | Tests contra MySQL real separado y CI en cada PR | Aceptada |
| [014](ADR-014-adjuntos-archivo-o-link.md) | Adjuntos: archivo subido o link | Aceptada |
| [015](ADR-015-ingreso-con-codigo-por-mail.md) | Ingreso con código por mail, sin contraseña | Aceptada |

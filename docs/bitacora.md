# Bitácora de firmas

> Cada rol firma su paso antes de que avance el siguiente. Una línea por firma: fecha · rol ·
> qué se aprobó · evidencia. Solo se agrega; nunca se edita una línea ya escrita.

| Fecha | Rol | Qué se aprueba | Evidencia |
|---|---|---|---|
| 2026-11-12 | CTO / Arquitecto | Arquitectura y ADR-001…013 propuestos (pendientes de OK de Juani) | `docs/arquitectura.md`, `docs/adr/` |
| 2026-11-12 | DBA | Modelo de datos propuesto (pendiente de OK de Juani) | `docs/modelo-datos.md` |
| 2026-11-12 | Juani (dev a cargo) | Aprueba arquitectura, modelo de datos, ADR-001…013, backlog de semana 1, matriz y todas las propuestas de `dudas.md` (DU-13: aviso al superior del solicitante, como dice el texto firmado) | Respuesta en sesión: "perfecto" |
| 2026-11-12 | Juani (dev a cargo) | **Amplía D-022:** donde hay un documento se puede subir el archivo o poner el link, según aplique | ADR-014 · `docs/modelo-datos.md` (tabla `adjuntos`) |
| 2026-11-12 | CTO / DBA | Arquitectura y modelo ajustados a ADR-014 | `docs/arquitectura.md`, `docs/modelo-datos.md` |
| 2026-11-13 | Juani (dev a cargo) | **Cambia F-01:** se entra con el mail y un código por mail, sin contraseña; sesión de 90 días desde el último uso. Hay que avisarle al cliente | ADR-015 · `docs/especificacion/identidad.md` (RN-IDE-01…10) |
| 2026-11-13 | Tech Lead / Seguridad | Revisión de cierre de semana 1 (backend y front en `main`): checklist de PR, límites de módulo, autorización de las 53 rutas (49 de `/api`) con sus tests (permitido / denegado / IDOR), ingreso por código, adjuntos, cabeceras, CORS/Sanctum, logs, secretos y dependencias. Sin bloqueantes; 6 importantes en backend (SEG-01…04, TL-01, TL-02) y 1 en front (FE-01), abiertos para la semana 2. Modelo de datos alineado a las migraciones | `docs/seguridad.md` (hallazgos y checklist de producción), `docs/modelo-datos.md`, `route:list --json`, `composer audit` (0), `npm audit --omit=dev` (0) |

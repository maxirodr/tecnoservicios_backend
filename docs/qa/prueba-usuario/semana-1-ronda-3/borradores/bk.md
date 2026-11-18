# Borrador BK · QA backend, cola y health · Ronda 3 · parte 1 (sin cortes)

- **Fecha:** 2026-11-18 (desde 10:38 hora local; logs del back en UTC, +3 h)
- **Personas / sesiones:** Diego (`sesiones\diego.json`, solo GET en la prueba en seco de `cortes-app.mjs`). Sin navegador manual.
- **Ingresos reales gastados:** 0
- **Datos creados:** ninguno (prefijo no usado)
- **Scripts:** `C:\Users\juanc\AppData\Local\Temp\claude\C--Users-juanc\3fd8de0f-88e6-40e4-a658-bb42248ba145\scratchpad\qa3\BK\` → `linea-base.ps1` (+ `linea-base.log`), `sin-sesion.txt`, `scheduler.txt`, `medir-health.ps1`, `cortes-app.mjs`, `plan-cortes.md`
- **Entorno:** API `c27e382`+ (main con `5c556c3`, `290e9d0`), front con `d468ada`. Base `tecnoservicios_prueba_usuario` (verificado con tinker), Redis prefijo `tecnoservicios_prueba_`, `MAIL_MAILER=log`, cola `redis`.

## Tabla de verificación

| PU | Título corto | Estado | Pantallas revisadas | Evidencia | Nota |
|---|---|---|---|---|---|
| PU-020 | Redis caído | pendiente (parte 2) | — | `BK\plan-cortes.md` §2 | Requiere corte; lo coordina el Lead. Línea de base con Redis arriba: health 200 en 0,86 s. |
| PU-138 | Redis/MySQL caído tapa la app y no se recupera sola | pendiente (parte 2) | código `RequireSesion.tsx`, `sesion.ts`, `queryClient.ts`, `ServicioCaido.php` | `BK\plan-cortes.md` §0, §2, §3 | Hipótesis H1 (del código, sin reproducir): el reintento automático cada 10 s es solo de `/api/me`; las queries de pantalla no tienen `refetchInterval` en error → la pantalla que falló durante el corte podría quedar con "Reintentar" hasta navegar o enfocar. Se mide a +8/+35/+70 s en la parte 2. |
| PU-139 | Health tarda 9 s con MySQL caído | pendiente (parte 2) | código `SondaDeBase.php`, `HealthController.php` | `BK\plan-cortes.md` §3, §4 | Requiere corte de MySQL (pedir permiso al usuario). |
| PU-005 (control) | API sin sesión da 500 | ✅ | `GET /api/obras/1`, `/api/adjuntos/1`, `/api/proveedores/1`, `/api/adjuntos/999999`, `/api/obras`, `/api/me` (Accept JSON y HTML) | `BK\sin-sesion.txt` | Las 12: 401 `application/json` "Tenés que ingresar para seguir." en 0,2–0,8 s (la primera 2,47 s, arranque en frío). `errors-2026-11-18.log` no se creó (sin 4xx en el canal `errors`). |

## Línea de base (cada ~10 min, solo lectura)

Script `BK\linea-base.ps1` → `BK\linea-base.log`. Mira: `/api/health` (status y tiempo), `failed_jobs` y `jobs`, `errors-2026-11-18.log`, niveles de `laravel-2026-11-18.log`, mails duplicados (mismo `To:` en el mismo segundo), procesos de `proc\pids.txt` vivos y salidas `proc\*.out|*.err`.

| Hora | Health | failed_jobs | errors log | laravel log | Mails / duplicados | Procesos | Salidas proc |
|---|---|---|---|---|---|---|---|
| 10:41 | 200 · 0,86 s · todo `true` | 0 (jobs db 0) | no existe | 7 DEBUG (mails), sin ERROR | 7 / ninguno | 5 vivos | sin FAIL/Exception; `.err` vacíos |
| 10:55 | 200 · 1,05 s · todo `true` | 0 (jobs db 0) | **6 ERROR** 10:54:06–10:55:00 (local) `unserialize(): Extra data…` en `GET /api/me` → **BK-01** | 11 DEBUG + 1 WARNING (`errors_log.unauthorized`, mi único intento a `/api/errors`) | 11 / ninguno | 5 vivos | sin FAIL/Exception; `.err` vacíos |
| 11:05 | 200 · 0,70 s · todo `true` | 0 (jobs db 0) | sin entradas nuevas (siguen las 6 de BK-01) | 14 DEBUG + 1 WARNING | 14 / ninguno | 5 vivos | `sched.out`: a las 11:00:01 (local) corrieron `adjuntos:purgar-huerfanos` e `idempotencia:purgar` (830 ms), DONE; sin FAIL |

## Scheduler

| Comando | Resultado | Evidencia |
|---|---|---|
| `adjuntos:purgar-huerfanos` | exit 0 en 0,79 s · "Adjuntos huérfanos purgados: 0" (1 temporal, de menos de 24 h: no correspondía) | `BK\scheduler.txt` |
| `idempotencia:purgar` | exit 0 en 0,66 s · "Idempotency-Key purgadas: 0" (ninguna de más de 24 h) | `BK\scheduler.txt` |
| `schedule:list` | ambos `0 * * * *`, próximos a las 11:00 | `BK\scheduler.txt` |
| `schedule:work` (11:00) | los dos corrieron solos a las 11:00:01, DONE, `sched.err` vacío | `proc\sched.out` |

Ejecutados con `qa-env.ps1` dot-sourceado; antes, tinker: `{"db":"tecnoservicios_prueba_usuario","queue":"redis","redis_prefix":"tecnoservicios_prueba_","mail":"log"}`.

## Hallazgos nuevos

### BK-01 · Una sesión dañada en Redis hace que `GET /api/me` dé 500 en vez de pedir ingresar
- **Severidad:** baja
- **Tipo:** error-servidor
- **F-XX / pantalla:** transversal · `GET /api/me` (cualquier pantalla con sesión)
- **Persona:** sesión de `qa-lead-b*` (uid 8/9) del verificador B, en Chromium headless
- **Pasos:** 1. Una sesión válida en Redis queda con 2 bytes de más al final del valor (en esta ronda: el script de B la borró para simular vencimiento y la restauró con el salto de línea (CR LF) que agrega `redis-cli GET` en Windows; `B\lib.mjs` `restaurar()`, corregido por B con `fix.cjs` a las 10:56). 2. El navegador con esa cookie pide `GET /api/me`.
- **Esperado:** la sesión ilegible se descarta y la API responde 401 "Tenés que ingresar para seguir." (como una sesión vencida), sin entrada en el canal `errors`.
- **Obtenido:** `GET /api/me` → 500; en `errors-2026-11-18.log` 6 entradas `local.ERROR: unserialize(): Extra data starting at offset 233 of 235 bytes` (`ErrorException`, `RedisStore.php:541` ← `RedisStore->unserialize`), 13:54:06 a 13:55:00 UTC, una cada ~10 s (el reintento automático de `/api/me` de `d468ada`: la persona se queda viendo el error en vez de ir a Ingresar).
- **Evidencia:** `logs/BK-01.log` · claves de sesión en Redis db0 de 233 bytes (la dañada tenía 235) · `qa3\Bix.cjs`, `qa3\B\limpiar.mjs`
- **Causa probable (opcional):** el driver de sesión `redis` lee con `Cache\RedisStore::unserialize()` (`vendor/laravel/framework/src/Illuminate/Cache/RedisStore.php:541`); el `E_WARNING` de `unserialize()` se convierte en `ErrorException` y no lo atrapa nadie (ni `ServicioCaido` ni el renderer: no es de conexión) → 500.
- **Nota para el Lead:** **no reproducido por BK** (requiere escribir en Redis, fuera de mi rol); las 6 ocurrencias son de la misma sesión. Origen = artefacto de la herramienta de prueba de B; en producción solo pasaría con el store dañado o manipulado. Si se descarta como artefacto, pasa a observación.

## Observaciones

- `/api/errors` no se puede usar en este entorno: `ERRORS_TOKEN` no está en el `.env` ni en `qa-env.ps1`, así que siempre da 401 `{"message":"Unauthorized","error":"Unauthorized"}` (en inglés, pero es la respuesta del contrato de `/bug-report`, no la ve una persona). Ojo: cada intento sin token suma al bloqueo por IP (10 → 403 "Banned" por un día); se hizo **un** intento. El canal `errors` se revisó por archivo.
- En `proc\pids.txt` el proceso `api=29796` es `artisan serve`; el que atiende es su hijo `php -S` (29884).
- Corre un `vendor/bin/phpunit` (PID 19544) en el backend: usa la base `tecnoservicios_testing`, `CACHE_STORE=array`, `QUEUE_CONNECTION=sync`, `SESSION_DRIVER=array` (forzado en `phpunit.xml`): no toca la base de la prueba.

## Lo que no pude probar y por qué

- PU-020, PU-138, PU-139 y el control de PU-021: necesitan cortar Redis / MySQL / workers, prohibido en la parte 1. Procedimiento exacto en `BK\plan-cortes.md`.
- Purga con datos de más de 24 h: no hay temporales ni Idempotency-Key tan viejos en la base; solo se probó que los comandos corren sin error.

---
BK · parte 1: línea de base de health/cola/logs/procesos, API sin sesión (12 requests), scheduler (2 comandos a mano) y plan de cortes para la parte 2.

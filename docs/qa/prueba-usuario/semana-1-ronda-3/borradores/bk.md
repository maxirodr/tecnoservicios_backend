# Borrador BK · QA backend, cola y health · Ronda 3 · parte 1 (sin cortes) + parte 2 (cortes)

- **Fecha:** 2026-11-18 · parte 1 10:38–11:36 · parte 2 11:56–12:21 (hora local; logs del back y de `cortes-app` en UTC, +3 h)
- **Personas / sesiones:** Diego (`sesiones\diego.json`: `cortes-app.mjs`, 2 pestañas headless, y curl con su cookie en el corte 2 de MySQL). Sin navegador manual.
- **Ingresos reales gastados:** 1 código pedido para `qa-lead-b1@…` (control PU-021, 12:04:39; no se usó para ingresar). Un `pedir-codigo` anterior con Redis caído dio 503 y no generó código.
- **Datos creados:** ninguno (prefijo no usado)
- **Scripts:** `C:\Users\juanc\AppData\Local\Temp\claude\C--Users-juanc\3fd8de0f-88e6-40e4-a658-bb42248ba145\scratchpad\qa3\BK\` → `linea-base.ps1` (+ `linea-base.log`), `sin-sesion.txt`, `scheduler.txt`, `medir-health.ps1`, `cortes-app.mjs`, `plan-cortes.md`
- **Entorno:** API `c27e382`+ (main con `5c556c3`, `290e9d0`), front con `d468ada`. Base `tecnoservicios_prueba_usuario` (verificado con tinker), Redis prefijo `tecnoservicios_prueba_`, `MAIL_MAILER=log`, cola `redis`.

## Tabla de verificación

| PU | Título corto | Estado | Pantallas revisadas | Evidencia | Nota |
|---|---|---|---|---|---|
| PU-020 | Redis caído | ⚠️ | `GET /api/health` (2 cortes, 25 mediciones), `/obras` → `/proveedores` (pestaña A) y `/proveedores` en pestaña nueva (B), `/api/obras/1` y `pedir-codigo` sin sesión | `PU-020-r3-1.png`, `PU-020-r3-2.png` · `logs/PU-020-r3.log` · `PU-138-r3-1…5.png` | Health ✅: 503 con `"cache": false, "redis": false, "cola_mail": false`, `"database": true`, en 1,15–2,30 s (un solo medidor); vuelve a 200 en la primera medición tras levantar. La primera medición después del corte llegó a 3,15 s y 3,71 s cuando corrían dos medidores y dos pestañas a la vez contra `php artisan serve` (un solo proceso). `/api/me` y pantallas → 503 "El sistema no está disponible en este momento. Probá de nuevo en unos minutos." (nunca 500). Sesiones sobrevivieron (`shutdown save`). Sin sesión: 401 en 0,14 s. `pedir-codigo` → 503 en 1,2 s. Sigue ⚠️ porque la app no se recupera sola en la pantalla que ya estaba abierta (**BK-02**). Los dos workers murieron con cada corte (en producción los relevanta Supervisord); relanzados. |
| PU-138 | Redis/MySQL caído tapa la app y no se recupera sola | ⚠️ | Redis ×2 y MySQL ×2: pestaña A (sesión cargada, Obras → Proveedores), pestaña B (nueva, `/proveedores`), curl con la sesión de Diego | `PU-138-r3-1…5.png`, `BK-02-1.png`, `BK-02-2.png`, `BK-03-1.png` · `logs/BK-02.log`, `logs/BK-03.log` | Con Redis: ✅ A conserva la barra y el error queda solo en el contenido; ✅ B muestra "No pudimos cargar esta información · El sistema no está disponible en este momento. Probá de nuevo en unos minutos. Apenas vuelva, esta pantalla se recupera sola. · Reintentar" y vuelve **sola** antes de los 8 s. ❌ A sigue con "No pudimos cargar esta información · El sistema no está disponible en este momento. Probá de nuevo en unos minutos. · Reintentar" a los +8, +35 y +70 s, aunque su `/api/me` ya dio 200: no vuelve a pedir `/api/proveedores` (**BK-02**, 2 veces). Con MySQL: ❌ `/api/me` y `/api/proveedores` con sesión → **500** "El sistema tuvo un problema. Volvé a intentar en un momento." a los **38,6 s** (no 503) y no queda nada en el canal `errors`; la pestaña nueva se queda en "Cargando el sistema…" (**BK-03**, 2 veces). Al volver MySQL, A y B se recuperaron solas. |
| PU-139 | Health tarda 9 s con MySQL caído | ✅ | `GET /api/health` con MySQL parado y Redis arriba, sin navegador (4 mediciones seguidas) | `logs/PU-139-r3.log` | 503 `{"database": false, "cache": true, "redis": true, "cola_mail": true}` en 1,23 / 1,25 / 1,25 / 1,23 s (ronda 2: 9,2–9,4 s); vuelve a 200 en 0,31 s. Con dos pestañas abiertas pidiendo `/api/me` durante el corte, el health se encola detrás de esos requests de 38 s (`php -S` atiende de a uno): 17,8 s y timeouts de 30 s. Eso es efecto de BK-03 y del servidor de un solo proceso, no del health. |
| PU-021 (control) | Worker de mail caído | ✅ | `GET /api/health`, `pedir-codigo` de `qa-lead-b1` | `logs/PU-021-r3.log` | Worker de mail parado + pedido a las 12:04:39 → `"cola_mail": false` (503, 0,2 s) a las 12:06:42 (~2 min); al relanzar, `EnviarCodigoJob` y `CodigoIngresoMail` DONE y **un solo** mail a las 15:07:03 UTC; health 200. |
| PU-005 (control) | API sin sesión da 500 | ✅ | `GET /api/obras/1`, `/api/adjuntos/1`, `/api/proveedores/1`, `/api/adjuntos/999999`, `/api/obras`, `/api/me` (Accept JSON y HTML) | `BK\sin-sesion.txt` | Las 12: 401 `application/json` "Tenés que ingresar para seguir." en 0,2–0,8 s (la primera 2,47 s, arranque en frío). `errors-2026-11-18.log` no se creó (sin 4xx en el canal `errors`). |

## Línea de base (cada ~10 min, solo lectura)

Script `BK\linea-base.ps1` → `BK\linea-base.log`. Mira: `/api/health` (status y tiempo), `failed_jobs` y `jobs`, `errors-2026-11-18.log`, niveles de `laravel-2026-11-18.log`, mails duplicados (mismo `To:` en el mismo segundo), procesos de `proc\pids.txt` vivos y salidas `proc\*.out|*.err`.

| Hora | Health | failed_jobs | errors log | laravel log | Mails / duplicados | Procesos | Salidas proc |
|---|---|---|---|---|---|---|---|
| 10:41 | 200 · 0,86 s · todo `true` | 0 (jobs db 0) | no existe | 7 DEBUG (mails), sin ERROR | 7 / ninguno | 5 vivos | sin FAIL/Exception; `.err` vacíos |
| 10:55 | 200 · 1,05 s · todo `true` | 0 (jobs db 0) | **6 ERROR** 10:54:06–10:55:00 (local) `unserialize(): Extra data…` en `GET /api/me` → **BK-01** | 11 DEBUG + 1 WARNING (`errors_log.unauthorized`, mi único intento a `/api/errors`) | 11 / ninguno | 5 vivos | sin FAIL/Exception; `.err` vacíos |
| 11:05 | 200 · 0,70 s · todo `true` | 0 (jobs db 0) | sin entradas nuevas (siguen las 6 de BK-01) | 14 DEBUG + 1 WARNING | 14 / ninguno | 5 vivos | `sched.out`: a las 11:00:01 (local) corrieron `adjuntos:purgar-huerfanos` e `idempotencia:purgar` (830 ms), DONE; sin FAIL |
| 11:15 | 200 · 0,29 s · todo `true` | 0 (jobs db 0) | sin entradas nuevas | 14 DEBUG + 1 WARNING (sin cambios) | 14 / ninguno | 5 vivos | sin FAIL/Exception; `.err` vacíos |
| 11:25 | 200 · 0,72 s · todo `true` | 0 (jobs db 0) | sin entradas nuevas | sin cambios | 14 / ninguno | 5 vivos | sin FAIL/Exception; `.err` vacíos |
| 11:35 | 200 · 1,80 s · todo `true` | 0 (jobs db 0) | sin entradas nuevas | sin cambios | 14 / ninguno | 5 vivos | sin FAIL/Exception; `.err` vacíos |

Resumen 10:41–11:35 (6 corridas): health siempre 200 (0,29–1,80 s), 0 jobs fallidos, 0 mails duplicados, workers y scheduler vivos todo el tiempo; el canal `errors` solo tiene las 6 entradas de BK-01 y ningún 4xx.

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
- **Evidencia:** `logs/BK-01.log` · claves de sesión en Redis db0 de 233 bytes (la dañada tenía 235) · `qa3\B\fix.cjs`, `qa3\B\limpiar.mjs`
- **Causa probable (opcional):** el driver de sesión `redis` lee con `Cache\RedisStore::unserialize()` (`vendor/laravel/framework/src/Illuminate/Cache/RedisStore.php:541`); el `E_WARNING` de `unserialize()` se convierte en `ErrorException` y no lo atrapa nadie (ni `ServicioCaido` ni el renderer: no es de conexión) → 500.
- **Nota para el Lead:** **no reproducido por BK** (requiere escribir en Redis, fuera de mi rol); las 6 ocurrencias son de la misma sesión. Origen = artefacto de la herramienta de prueba de B; en producción solo pasaría con el store dañado o manipulado. Si se descarta como artefacto, pasa a observación.

### BK-02 · Tras un corte de Redis, la pantalla que ya estaba abierta no se recupera sola: queda "No pudimos cargar esta información … Reintentar"
- **Severidad:** media
- **Tipo:** loader
- **F-XX / pantalla:** transversal · probado en F-55 Proveedores · `/proveedores` (pasa con cualquier listado o ficha que falle durante el corte)
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Con la app abierta en `/obras` (sesión ya cargada), parar Redis (`redis-cli shutdown save`). 2. Ir a "Proveedores" desde la barra. 3. Volver a levantar Redis y esperar a que `/api/health` dé 200. 4. Esperar sin tocar 8 s, 35 s y 70 s.
- **Esperado:** (prompt ronda 3, PU-138) la pantalla se recupera sola al volver el servicio, sin "Reintentar", igual que una pestaña abierta durante el corte.
- **Obtenido:** a los +8, +35 y +70 s sigue "No pudimos cargar esta información · El sistema no está disponible en este momento. Probá de nuevo en unos minutos. · Reintentar", aunque el reintento automático de la sesión ya dio `GET /api/me → 200` a los ~5 s: no se vuelve a pedir `GET /api/proveedores`. El mensaje dice "no está disponible" cuando ya lo está. La barra sí queda (✅). Solo se recupera al tocar otra sección ("Obras" → listado OK) o "Reintentar". En la misma corrida, la pestaña abierta durante el corte (sesión nunca cargada) sí volvió sola antes de los 8 s. Reproducido 2 veces (11:57 y 12:02).
- **Evidencia:** `capturas/BK-02-1.png` (corrida 1, +70 s) · `capturas/BK-02-2.png` (corrida 2, +70 s) · `capturas/PU-138-r3-1.png` (durante el corte, con barra) · `capturas/PU-138-r3-3.png` (pestaña B recuperada) · `logs/BK-02.log` · script `BK\cortes-app.mjs redis` / `redis3`
- **Causa probable (opcional):** el reintento cada 10 s (`SESION_REINTENTO_MS`, `src/shared/sesion/sesion.ts:43-48`, front `d468ada`) es solo de la query `['sesion']`. Las queries de pantalla usan los defaults de `src/app/queryClient.ts:40-45` (`retry: debeReintentar`, 2 reintentos, y `refetchOnWindowFocus`): sin `refetchInterval` en error ni invalidación cuando la sesión vuelve a dar 200, quedan en error hasta que haya foco, navegación o "Reintentar".
- **Regresión de:** no; arreglo incompleto de PU-138 (front `d468ada`)

### BK-03 · Con MySQL caído, cualquier pedido con sesión tarda 38 s y responde 500 "El sistema tuvo un problema." en vez de 503, y no queda registrado
- **Severidad:** alta
- **Tipo:** error-servidor
- **F-XX / pantalla:** transversal · `GET /api/me`, `GET /api/proveedores` (cualquier request con sesión) · pestaña nueva en `/proveedores`
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Con Redis arriba, parar MySQL (`mysqladmin --user=root --host=127.0.0.1 shutdown`). 2. Con la sesión de Diego, `GET /api/me` y `GET /api/proveedores?page=1` (curl, `Accept: application/json`, de a uno, sin navegador). 3. Abrir `/proveedores` en una pestaña nueva.
- **Esperado:** (PU-138, `5c556c3`) 503 "El sistema no está disponible en este momento. Probá de nuevo en unos minutos." en pocos segundos (el health detecta la base caída en 1,2 s), con la falla registrada en el canal `errors`; la pestaña nueva muestra "… Apenas vuelva, esta pantalla se recupera sola."
- **Obtenido:** `GET /api/me → 500` `{"message":"El sistema tuvo un problema. Volvé a intentar en un momento."}` en **38,62 s**, y `GET /api/proveedores → 500`, mismo texto, en **38,62 s**. En `errors-2026-11-18.log` **no hay ninguna entrada** de esos dos 500. En la salida de `artisan serve` aparece `PHP Fatal error: Maximum execution time of 30 seconds exceeded … Connector.php on line 67`. La pestaña nueva queda en "Cargando el sistema…" sin mensaje ni "Reintentar" durante todo el corte, y como `php artisan serve` atiende de a un pedido, el health y los demás requests se encolan detrás (17,8 s y timeouts de 30 s). Reproducido 2 veces: corte 1 (12:07–12:16, con navegador; una sola entrada `Maximum execution time of 30 seconds exceeded`, `FatalError`, a las 15:16:50 UTC) y corte 2 (12:18–12:20, curl, 2 requests).
- **Evidencia:** `capturas/BK-03-1.png` ("Cargando el sistema…") · `logs/BK-03.log` (curl, health 1,23 s en el mismo corte, traza de `api.out`) · `BK\corte-mysql2-operador.log`
- **Causa probable (opcional):** la traza de `api.out` muestra que el `PDOException [2002]` original se reporta y el reporte vuelve a ir a la base: `bootstrap/app.php:51` → `App\Http\Controllers\Api\Support\ErrorLogContext::fromRequest()` (`ErrorLogContext.php:34`) → `$request->user()` → `SessionGuard::user()` → `EloquentUserProvider::retrieveById()` → nueva conexión a MySQL caída (con el reintento de Laravel) dentro del reporte. Las esperas de conexión se suman hasta superar `max_execution_time` (30 s), el `FatalError` no pasa por `ServicioCaido` (→ 500 genérico) y la entrada del canal `errors` nunca se escribe. El health no lo sufre porque usa `SondaDeBase` con su propio timeout de 1 s.
- **Regresión de:** no; arreglo incompleto de PU-138 (back `5c556c3`; su test `ServicioCaidoTest` no pasa por el reporte con usuario autenticado)

## Observaciones

- `/api/errors` no se puede usar en este entorno: `ERRORS_TOKEN` no está en el `.env` ni en `qa-env.ps1`, así que siempre da 401 `{"message":"Unauthorized","error":"Unauthorized"}` (en inglés, pero es la respuesta del contrato de `/bug-report`, no la ve una persona). Ojo: cada intento sin token suma al bloqueo por IP (10 → 403 "Banned" por un día); se hizo **un** intento. El canal `errors` se revisó por archivo.
- En `proc\pids.txt` el proceso `api=29796` es `artisan serve`; el que atiende es su hijo `php -S` (29884).
- Corre un `vendor/bin/phpunit` (PID 19544) en el backend: usa la base `tecnoservicios_testing`, `CACHE_STORE=array`, `QUEUE_CONNECTION=sync`, `SESSION_DRIVER=array` (forzado en `phpunit.xml`): no toca la base de la prueba.

### Parte 2 · canal `errors` y procesos durante los cortes
- **Canal `errors` (solo 5xx):** ✅ no registró ningún 4xx. Durante los cortes de Redis: 38 entradas (31 `StreamInitException`, 7 `ConnectionException`; 10 de ellas de los workers/scheduler de consola) de Predis, en `/api/me`, `/api/proveedores`, `/api/usuarios/opciones` y `pedir-codigo`, todas respondidas con 503. Con MySQL caído no registró los 500 (BK-03), salvo una `FatalError` "Maximum execution time of 30 seconds exceeded" a las 15:16:50 UTC.
- **`failed_jobs`:** 0 al final. Sin mails duplicados (18 mails en el log, ninguno repetido en el mismo segundo).
- **Workers:** los dos `queue:work` murieron en cada corte de Redis (`stopWorkerIfLostConnection`; en producción los relevanta Supervisord) y se relanzaron con `qa-env.ps1`. Con MySQL caído sobrevivieron. `schedule:work` (9704) sobrevivió a todo; en `sched.out` quedaron trazas `StreamInitException` del minuto en que Redis estaba caído, sin cortar el proceso.
- **Incidentes de operación (míos, no del sistema):**
  - (a) 12:03:47: un `Stop-Process` al PID de un worker ya muerto mató al `redis-server` nuevo, que había reusado ese PID. Redis se relanzó a las 12:04:10 desde `dump.rdb` (12:01:07) y las 7 sesiones siguieron válidas.
  - (b) 12:10: `mysqld` lanzado con `Invoke-CimMethod Win32_Process Create` (sin consola) aceptaba TCP pero nunca contestaba el saludo (1 núcleo al 100 %) durante 6 min. Lo paré y lo relancé con `Start-Process … -WindowStyle Hidden` (el comando del plan) y respondió `select 1` en 2 s. Por eso el corte 1 de MySQL duró 9 min.
  - (c) Una tercera corrida de Redis ("redis2") se descartó: cortó y levantó antes de que arrancara el navegador. Sus capturas se borraron; su log quedó como `cortes-app-redis2-INVALIDA.log`.
- **Estado final (12:21):** health 200 con todo en `true` (0,26 s). Procesos vivos: `wmail=26644`, `wdef=21144`, `sched=9704`, `api=29796`, `front=20224` (`proc\pids.txt` actualizado; respaldo del original en `pids.txt.bak-bk`). `redis-server` 12336, `mysqld` 25360. `node qa3\chequear-sesiones.mjs` → las 7 sesiones dan 200.

## Lo que no pude probar y por qué

- Corte simultáneo de Redis + MySQL (§4 del plan): no lo hice para no sumar otro corte de MySQL. BK-03 ya muestra el comportamiento con MySQL caído.
- Purga con datos de más de 24 h: no hay temporales ni Idempotency-Key tan viejos en la base; solo se probó que los comandos corren sin error, a mano y desde el scheduler.

---
BK · parte 1: línea de base (6 corridas), API sin sesión (12 requests), scheduler, plan de cortes · parte 2: Redis ×2 (+1 descartada), worker de mail ×1, MySQL ×2, con health, app (2 pestañas), canal `errors`, workers y sesiones.

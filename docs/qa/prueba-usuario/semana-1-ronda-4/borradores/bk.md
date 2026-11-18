# Borrador BK · QA backend, cola y health · Ronda 4 · Parte 1 (sin cortes)

- **Fecha:** 2026-11-18, 13:59–14:5x (hora local; los logs del back están en UTC = local + 3 h).
- **Commits probados:** back `74beea5` (rama `qa/prueba-usuario-semana-1-ronda-4`, desde `staging`) · front `15b544f` (`staging`).
- **Personas / sesiones:** Diego (`sesiones\diego.json`), solo `GET` con fetch para la medición en seco de PU-202 (sin navegador). Sin sesión para los 401.
- **Ingresos reales gastados:** 0. Códigos pedidos: 0.
- **Datos creados:** ninguno en la base de la prueba. Base propia `tecnoservicios_prueba_r4_mig` (creada y borrada por BK), con 3 links y 3 actividades ficticias "QA4-BK …".
- **Scripts:** `C:\Users\juanc\AppData\Local\Temp\claude\C--Users-juanc\3fd8de0f-88e6-40e4-a658-bb42248ba145\scratchpad\qa4\BK\` (`linea-base.ps1`, `migraciones.ps1`, `migraciones2.ps1`, `api-con-sesion.mjs`, `cortes-app.mjs`, `medir-health.ps1`, `relanzar-workers.ps1`, `plan-cortes.md`; salidas `linea-base.log`, `migraciones.log`, `sin-sesion.txt`).
- **Servicios no tocados:** Redis, MySQL, API, workers y scheduler no se pararon ni reiniciaron.

## Tabla de verificación

| PU | título corto | estado | pantallas revisadas | evidencia | nota |
|---|---|---|---|---|---|
| PU-202 | MySQL caído → 503 en ~1–3 s y entrada en `errors` | pendiente (parte 2) | — | `BK\plan-cortes.md` §4 | Requiere MySQL parado. En seco, con todo arriba, `api-con-sesion.mjs` con la sesión de Diego: 4 × 200 en 0,21–0,37 s. Código leído: `8b8166a` + `47f4883`. |
| PU-203 | Pantalla abierta se recupera sola tras un corte | pendiente (parte 2) | — | `BK\plan-cortes.md` §3–4 | Requiere cortes. Código leído: `aa04a4d` (reintento cada 10 s solo con 503 o red). |
| PU-020 | Redis caído | pendiente (parte 2) | — | `BK\plan-cortes.md` §3 | Health en línea de base: 200 en todas las corridas. |
| PU-138 | Redis/MySQL caído no tapa la app y se recupera sola | pendiente (parte 2) | — | `BK\plan-cortes.md` §3–4 | — |

## Parte 1 · Resultados

### Sin sesión (PU-206 desde el back)
`BK\sin-sesion.txt`, 2 vueltas, curl directo y con `Origin`/`Referer` de la SPA:
- `GET /api/obras/1`, `/api/adjuntos/1`, `/api/proveedores/1` y `/api/me` → **401** `"Tu sesión venció. Volvé a ingresar."` en 0,17–0,98 s. ✅
- Canal `errors`: **ninguna** entrada nueva por esos 401 (archivo y `GET /api/errors`). ✅

### Migraciones `2026_11_18_*` (idempotencia)
Base propia `tecnoservicios_prueba_r4_mig` (`CREATE DATABASE IF NOT EXISTS`), variables de `qa4\qa-env.ps1` con `DB_DATABASE` cambiada; antes de cada tanda, `tinker` → `tecnoservicios_prueba_r4_mig`. `BK\migraciones.log`:
1. `migrate` sobre base vacía: 25 migraciones, rc=0, las dos nuevas `DONE` (1,8 ms y 1,3 ms).
2. Se cargaron datos "viejos" a mano en esa base: 2 links con nombre = dominio (`drive.example`, uno con host `www.`), 1 con nombre propio, 2 actividades "agregado (drive.example)" y 1 "quitado" con comillas.
3. `migrate:rollback --step=2` rc=0 → `migrate` rc=0 (links → "Plano nave año.pdf" y "oferta-ñandú"; el de nombre propio intacto; historial con esos nombres; "quitado" sin comillas) → `migrate` rc=0 "Nothing to migrate".
4. Segundo ciclo (`migraciones2.ps1`): `rollback --step=2` → `migrate` rc=0, y además se borró el registro de las dos en `migrations` para correr `up()` **otra vez sobre datos ya migrados**: rc=0 y los datos quedan idénticos.
5. `DROP DATABASE tecnoservicios_prueba_r4_mig` (solo esa). ✅ sin error y dos veces seguidas.
- Observación (no hallazgo): el `down()` de la migración de links devuelve "www.drive.example" a un link que antes de migrar se llamaba "drive.example" (usa `NombreDeLink::dominio`). Solo afecta un rollback y no cambia lo que ve la persona.

### Scheduler
`proc4\sched.out`: `2026-11-18 17:00:07 Running ["artisan" adjuntos:purgar-huerfanos] .. 872.13ms DONE` y `17:00:08 Running ["artisan" idempotencia:purgar] .. 631.92ms DONE` (14:00 local; `hourly()`), sin errores en `sched.err`. ✅

### Línea de base (cada ~10 min, `BK\linea-base.log`)

LINEA_BASE_TABLA

- `GET /api/errors`: con `Authorization: Bearer` (también acepta `?token=`; se usó el header para que el token no quede en la salida de `artisan serve`). El token no se escribió en ningún archivo de la ronda.
- Las 54 entradas del canal `errors` del día son **anteriores a la ronda 4** (cortes de la ronda 3 y pruebas de desarrollo a las 13:02–13:15 local contra el puerto 3399); ninguna de tipo 4xx.

## Hallazgos nuevos

HALLAZGOS

## Decisiones aprobadas
No le tocan a BK en la parte 1 (401 "Tu sesión venció…" verificado arriba desde el back).

## Lo que no pude probar y por qué
- PU-202, PU-203, PU-020, PU-138 y el corte del worker de mail: requieren parar servicios; quedan para la parte 2 con `BK\plan-cortes.md`.
- No abrí navegador (consigna de la parte 1): el `cortes-app.mjs` adaptado no se probó en seco; es el primer paso de la parte 2.
- Memoria libre al empezar: 3,1 GB (menos de los 6 GB del §3). Los tiempos de la línea de base no se vieron afectados, pero conviene revisarla antes de los cortes.

## Firma
BK · parte 1: línea de base, 401 sin sesión, migraciones (idempotencia en base propia), scheduler y plan de cortes de la parte 2. Sin cortes, sin ingresos.

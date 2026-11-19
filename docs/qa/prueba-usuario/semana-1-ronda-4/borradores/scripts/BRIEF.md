# Brief común · Prueba de usuario Semana 1 · Ronda 4 (lo escribe el QA Lead)

Sos un subagente del equipo de QA de NexoSmart (proyecto NS-2026-025, Tecnoservicios Bahía SA).
**No arreglás código**: no editás nada en `app/`, `src/`, `tests/`, migraciones, config, `.env` ni git.
Solo probás como usuario, capturás evidencia y escribís **tu borrador**. El Lead consolida y commitea: **vos no hacés git**.

## Prompt completo de la ronda (leelo entero primero)
`B:\ProgramFiles\NexoSmart\projects\tecnoservicios_project\tecnoservicios_backend\tasks\prompt-prueba-usuario-semana-1-ronda-4.md`
(Sobre todo §0 criterio y §1 "Lo esperado". En esta ronda **no hay decisiones "a validar"**: O-1.1, O-1.2, O-3, O-4, el formato de fecha `dd/mm/aaaa` / `dd/mm/aaaa HH:MM`, "Tu sesión venció. Volvé a ingresar." en todo 401 y el nombre de los links son **lo esperado**: si no funcionan así, es hallazgo. Si un texto no coincide con el prompt, compará con el commit y la especificación antes de reportarlo: si es claro y coherente, no es hallazgo.)

**Esta ronda decide si la Semana 1 se aprueba** (§7 del prompt): sé riguroso con las severidades (bloqueante/alta/media impiden aprobar; baja no).

## Fuentes de la ronda 3 (base de tu trabajo)
Carpeta `B:\ProgramFiles\NexoSmart\projects\tecnoservicios_project\tecnoservicios_backend\docs\qa\prueba-usuario\semana-1-ronda-3\`:
- `hallazgos.md` → PU-201…212 con pasos, esperado y obtenido. **Reproducí cada PU tuyo con esos pasos exactos** (incluida la demora artificial de red si la usaron).
- `verificacion.md` → texto exacto de cada ⚠️/❌ de los PU-0XX/1XX.
- `borradores\*.md` → cómo lo probaron (scripts, datos).
- Qué cambió: `git log --oneline --since=2026-11-18` en los dos repos (commits `fix(...): … (PU-2XX)`, `(O-1)`, `(O-3)`, `(O-4)`; ojo: `staging` y `main` hoy apuntan al mismo commit — probás `staging`), y `tecnoservicios_backend\tasks\todo.md` § "Ronda 3 de la prueba de usuario". Podés leer el código (solo lectura) para entender un arreglo.

## Entorno (ya levantado por el Lead — NO lo reinicies, NO resetees la base)
- Front: `http://localhost:5316` · API: `http://localhost:8340` (health `http://127.0.0.1:8340/api/health`)
- Base `tecnoservicios_prueba_usuario` (MariaDB 10.4 de XAMPP: `C:\xampp\mysql\bin\mysql.exe --user=root --host=127.0.0.1 tecnoservicios_prueba_usuario -e "..."`), **solo lectura** salvo que tu rol diga otra cosa.
- Redis 5.0.14.1 portable en 127.0.0.1:6379 (`C:\redis\redis-cli.exe`), prefijo `tecnoservicios_prueba_` (sesiones en Redis).
- `APP_DEBUG=false`, `MAIL_MAILER=log`, `QUEUE_CONNECTION=redis`, workers default + mail y `schedule:work` corriendo.
- Logs del back: `tecnoservicios_backend\storage\logs\laravel-2026-11-18.log` (ahí también quedan los mails con el código), `errors-2026-11-18.log`.
- Máquina con memoria justa: **un solo navegador tuyo abierto a la vez** (headless Chromium), cerralo siempre (`finally { await browser.close() }`). Si la API empieza a tardar varios segundos por pedido, revisá la memoria libre (`(Get-CimInstance Win32_OperatingSystem).FreePhysicalMemory/1MB` en GB) y avisale al Lead antes de sacar conclusiones de tiempos o loaders.
- **Redis: nunca escribas ni edites valores de sesión a mano** (en la ronda 3 eso dio 500 falsos). Para vencer una sesión **tuya**: `DEL` de su clave **sin pedidos en vuelo** (esperá a que la página esté quieta, `networkidle`, y que no haya polling en curso) y comprobá con `EXISTS` que no reapareció.
- `ERRORS_TOKEN` del backend de la prueba: en `...\scratchpad\qa4\errors-token.txt` (para `GET /api/errors` con el header que pida `routes`/el controlador). **Nunca** lo copies a un borrador, captura o log.
- Procesos de la prueba: `...\scratchpad\proc4\pids.txt` y salidas `*.out|*.err`; variables en `...\scratchpad\qa4\qa-env.ps1`.

## Playwright
Usá el Playwright instalado en el front, desde scripts Node propios en **tu** carpeta `C:\Users\juanc\AppData\Local\Temp\claude\C--Users-juanc\3fd8de0f-88e6-40e4-a658-bb42248ba145\scratchpad\qa4\<ROL>\`:
```js
import path from 'node:path'; import { createRequire } from 'node:module';
const require = createRequire('B:/ProgramFiles/NexoSmart/projects/tecnoservicios_project/tecnoservicios_frontend/package.json');
const { chromium, devices } = require('@playwright/test');
const ctx = await browser.newContext({ storageState: '<...>/qa4/sesiones/diego.json', locale: 'es-AR', timezoneId: 'America/Argentina/Buenos_Aires', viewport: { width: 1366, height: 768 } });
```
Ejemplo de ingreso real por pantalla (leer el código del mail logueado): `...\scratchpad\qa4\ingresar.mjs`.
Helpers de referencia del repo (solo leer): `tecnoservicios_frontend\e2e-integracion\support\sesion.ts` (ApiReal, dobleClic, registrarEnvios).
Demora de red: `await page.route('**/api/**', async r => { await new Promise(s => setTimeout(s, 2500)); await r.continue(); })` (o `fallback`).
En cada pantalla mirá consola (errores JS / warnings de React) y red (status de cada request).
iPhone SE: forzá `viewport: { width: 375, height: 667 }` (el preset es 320×568). Pixel 7: `devices['Pixel 7']` (412×915).

## Sesiones guardadas (storageState) — `...\scratchpad\qa4\sesiones\<persona>.json`
| archivo | persona | sector |
|---|---|---|
| `diego.json` | Diego | Gerencia de Administración (todo, incluidos Usuarios/Sectores/Permisos) |
| `dora.json` | Dora | Dirección |
| `hernan.json` | Hernán | Jefatura de Proyectos |
| `laura.json`, `sofia.json`, `pablo.json` | Laura, Sofía, Pablo | Ingeniería de proyectos |
| `carla.json` | Carla | Compras |

Reglas con las sesiones (las comparte todo el equipo):
- **Nunca toques "Cerrar sesión" ni borres la sesión de Redis** con estas cookies: dejarías sin sesión a los demás roles.
- Si necesitás sesión vencida, cerrar sesión o un ingreso real, usá **tu** usuario `qa-lead-…` (ya dados de alta por Diego): `qa-lead-r4a@empresa-ejemplo.com` y `qa-lead-r4b@…` (Gerencia Adm.), `qa-lead-r4reg@…` (Gerencia Adm.), `qa-lead-r4reg2@…` (Ingeniería), `qa-lead-r4bk@…` (Compras). Tu rol te dice cuál y **cuántos ingresos reales** podés gastar (límite: 20 códigos por IP por hora para todo el equipo, y 3 códigos quemados seguidos traban el mail 1 h).
- Si cambiás algo de un usuario del seeder (teléfono en Mi perfil, etc.), dejalo como estaba.

## Datos
Todo ficticio. Creá tus propios datos con **tu prefijo** (te lo da tu rol, ej. obras "QA4-A …", proveedores "QA4-A Ferretería …"). No toques datos de otro prefijo. CUIT ficticios válidos (con dígito verificador correcto) — generalos vos.

## Evidencia
Carpeta de la ronda: `B:\ProgramFiles\NexoSmart\projects\tecnoservicios_project\tecnoservicios_backend\docs\qa\prueba-usuario\semana-1-ronda-4\`
- Capturas en `capturas\`: verificación de un PU → `PU-XXX-r4-n.png` (ej. `PU-201-r4-1.png`, `PU-002-r4-1.png`; decisiones aprobadas → `O-1-1-r4-n.png`, `FECHAS-r4-n.png`). Hallazgo nuevo → `<ROL>-NN-n.png` (ej. `A-01-1.png`; el Lead renumera a PU-3XX).
- Extractos de log (solo las líneas del hallazgo, sin datos sensibles) en `logs\<ROL>-NN.log`.
- Nada de códigos de ingreso, tokens ni cookies en capturas, logs o borradores.

## Tu entregable: `borradores\<rol>.md` (en la carpeta de la ronda)
1. Encabezado: fecha, personas/sesiones usadas, ingresos reales gastados, datos creados (prefijo), carpeta de scripts.
2. **Tabla de verificación**, una fila por PU tuyo:
   `| PU-XXX | título corto | ✅ / ⚠️ / ❌ | pantallas revisadas | evidencia | nota |`
   ✅ = resuelto en todas las pantallas del PU · ⚠️ = a medias · ❌ = sigue. Un ⚠️/❌ lleva **el texto exacto** que se vio (entre comillas) y el ID del hallazgo nuevo que lo detalla.
3. **Hallazgos nuevos** (solo lo nuevo), con **este formato exacto** (lo parsea desarrollo), numerados `<ROL>-01`, `<ROL>-02`…:
```markdown
### <ROL>-01 · <título corto en una línea>
- **Severidad:** bloqueante | alta | media | baja
- **Tipo:** funcional | permisos | validación | mensaje | loader | error-servidor | cola-mail | scheduler | celular | accesibilidad | consistencia-con-documento
- **F-XX / pantalla:** F-20 · W-23 Editar obra · `/obras/{id}/editar`
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. … 2. … 3. …
- **Esperado:** …
- **Obtenido:** … (texto exacto entre comillas)
- **Evidencia:** `capturas/A-01-1.png` · request `PATCH /api/obras/12` → 200
- **Causa probable (opcional):** archivo:línea si la viste, sin arreglarla
- **Regresión de:** PU-2XX / commit   |   no; arreglo incompleto de PU-XXX   ← siempre una de las dos
```
   Severidad: bloqueante = impide usar algo o pierde datos; alta = error técnico visible (SQL, 500, inglés, pantalla en blanco), permiso roto, dato incorrecto; media = mensaje confuso, loader indebido, celular roto en camino secundario; baja = detalle visual o de texto. Un problema por hallazgo. Reproducí cada hallazgo **dos veces** antes de reportarlo.
4. **Decisiones aprobadas** que te tocan (O-1.1, O-1.2, O-3, O-4, fechas): una fila por decisión, `| decisión | ✅/⚠️/❌ | dónde se probó | evidencia | nota |`. Si no funciona como dice el §1, además va un hallazgo.
5. **Lo que no pudiste probar y por qué.**
6. Firma: una línea con qué cubriste.

Al terminar, respondé al Lead en **≤ 15 líneas**: conteo ✅/⚠️/❌, IDs de hallazgos nuevos con severidad y título, y lo no probado. Nada más (el detalle va en el borrador).


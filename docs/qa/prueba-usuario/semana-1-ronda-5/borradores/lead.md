# Borrador · QA Lead · ronda 5 (2026-11-19, máquina con Laragon `C:\`)

## Entorno
- **Código probado:** `staging` en los dos repos — backend `6516ba7` (arreglo de PU-302 `a8deacf`) y, desde las 11:52 local, el worktree en `f36c23a` (= `6516ba7` + docs: el prompt actualizado y la regla nueva del §7, sin cambios de código); front `d2a4806`. `config:clear` después del pull (`app.zona_empresa` = `America/Argentina/Buenos_Aires`, verificado con tinker).
- **Base:** `tecnoservicios_prueba_usuario`, `migrate` ("Nothing to migrate") y `migrate:fresh --seed` (tinker confirmó la base antes). 25 migraciones, ninguna pendiente.
- **Procesos:** API :8340, workers `default` y `mail` iguales a `config/supervisor.php`, `schedule:work`, front :5316. Variables: `APP_DEBUG=false`, `MAIL_MAILER=log`, `QUEUE_CONNECTION=redis`, prefijos propios de Redis, `DB_CONNECT_TIMEOUT=1`, `ERRORS_TOKEN` nuevo (fuera del repo), `IDENTIDAD_CODIGO_EN_PANTALLA=false`.
- **Ingresos reales:** 7 del Lead (las 7 personas; Diego dio de alta a Dora y a `qa-lead-r5a/b/reg/reg2/bk`) + 6 del Verificador A (PU-305) + 4 del Cazador = **17**, repartidos en ~2 h. Ningún 429 ni código quemado.

### Incidente de entorno · otra sesión movió los repos principales a `main` (Semana 2)
- A las 11:48 local, una sesión de desarrollo en el mismo repo del backend pasó de la rama de QA a `staging`, commiteó `f36c23a` (que incluyó la actualización de §0 y §2 del prompt hecha por el Lead, todavía sin commitear, más la regla nueva del §7) y dejó el repo en `main`, que después avanzó con código de la Semana 2 (`dc57bae`).
- La API que el Lead había levantado desde el repo principal quedó sirviendo ese código entre ~11:48 y 12:02. **Nadie probó en ese lapso** (los roles se lanzaron a las 12:10) y no se corrió ninguna migración (25 migraciones, 0 pendientes).
- Desde 12:02 la prueba corre en **worktrees fijos en `staging`**: `tecnoservicios_backend_qa5` (rama `qa/prueba-usuario-semana-1-ronda-5`) y `tecnoservicios_frontend_qa5` (`d2a4806`, `npm ci`). Las sesiones siguieron válidas (misma `.env`).

## QA backend, cola y health (rol 4, sin cortes)
| Qué | Resultado |
|---|---|
| `/api/health` | 200 con los 5 servicios en `true` (0,1–1,1 s) durante toda la ronda. |
| `failed_jobs` | 0. |
| Canal `errors` | Ningún archivo `errors-2026-11-19.log` en el worktree durante toda la ronda; `GET /api/errors` (con token) → "No logs for date 2026-11-19". **Ni 4xx ni 5xx.** |
| Workers | Sin errores; 17 mails de código enviados, uno por pedido. |
| Scheduler | 16:00 UTC `adjuntos:purgar-huerfanos` DONE (5 s) e `idempotencia:purgar` DONE (1 s). |
| Cortes | No se repitieron: PU-020, 202, 203 y 138 quedaron ✅ en la ronda 4 y el backend solo cambió `PresupuestoResource` y `app.zona_empresa`. |

- Nota del armado: la primera consulta a `/api/errors` dio 401 porque el `ERRORS_TOKEN` que el Lead le pasó a la API tenía un `\r` final (generado con `openssl` en Windows). Se corrigió y se relanzó solo la API al final, sin agentes probando. No es hallazgo.

## Observaciones (no son hallazgos)
- **Sesiones viejas en Redis tras el reseteo (REG):** las sesiones de la ronda 4 de los uid 10 y 11 siguen vivas en Redis y, después de `migrate:fresh --seed`, apuntarían a los usuarios nuevos con ese id (`qa-lead-r5reg/reg2`). Es un efecto del reseteo de la base de la prueba, no de la app; en producción no se resetea. Para próximas rondas: borrar las claves de sesión del prefijo de la prueba al resetear.
- **"Ya está cargado: …" una vez (REG):** en un alta de proveedor salió una vez en vez de "Dimos de alta a …"; en 4 intentos más no se repitió. Sin evidencia suficiente para un hallazgo; queda anotado para mirar.

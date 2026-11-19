# Borrador · QA Lead · ronda 4 retomada (2026-11-19, máquina con Laragon `C:\`)

- **Código:** `staging` en los dos repos — backend `56e5d8f` (rama de QA `qa/prueba-usuario-semana-1-ronda-4` con `origin/staging` mergeado: mismo código + docs), front **`380ddaf`**. `main` = `staging` en los dos repos hoy.
- **Entorno:** MySQL 8.4.3 y Redis 5.0.14.1 de Laragon; PHP 8.3.30; base `tecnoservicios_prueba_usuario` con `migrate` (las dos `2026_11_18_*` corrieron: 127 ms y 1,3 ms) y `migrate:fresh --seed` (tinker confirmó la base antes). `DB_CONNECT_TIMEOUT=1`, `ERRORS_TOKEN` nuevo (solo en el entorno de los procesos de la prueba, no en el repo), `IDENTIDAD_CODIGO_EN_PANTALLA=false`, `APP_DEBUG=false`, `MAIL_MAILER=log`, cola Redis con workers `default` y `mail` iguales a `config/supervisor.php` y `schedule:work`.
- **Ingresos:** las 7 personas por pantalla (Diego dio de alta a Dora y a `qa-lead-r4a/b/reg/reg2/bk`): 7 códigos.

## Incidente de entorno · MySQL dejó de responder y se cayó (no fue un corte de la prueba)
- **12:45–13:06 UTC (09:45–10:06 local):** MySQL dejó de responder (`SQLSTATE[HY000] [2002] A connection attempt failed because the connected party did not properly respond…`, 68 entradas en el canal `errors`, 63 de `GET /api/me`) y alrededor de las 12:58 el proceso `mysqld` desapareció sin apagado en el log. Memoria libre en ese momento: ~3,6 GB (14 GB totales, 3 navegadores de la prueba + otras apps). Juani lo relanzó desde Laragon; escuchó otra vez a las 13:06:33 UTC. Datos intactos (12 usuarios, `failed_jobs` 0); API, workers y scheduler siguieron vivos sin relanzarlos.
- Durante el incidente la API respondió **503 en ~1,2 s** con `"database": false` (health) — de paso, es el comportamiento que pide PU-202, sin 500.
- A las 13:00 UTC las dos tareas del scheduler (`adjuntos:purgar-huerfanos`, `idempotencia:purgar`) fallaron por la base caída (2 entradas `Scheduled command … failed` en `errors`): esperado con la base caída.
- Todo lo que los roles midieron en esa franja se descartó y se repitió.

## Verificación propia del Lead
### LEAD-01 · "Cargado" del presupuesto muestra el día siguiente si se cargó entre las 21:00 y las 24:00
- **Severidad:** alta
- **Tipo:** funcional
- **F-XX / pantalla:** F-14 Ficha de obra · W-07 · `/obras/{id}` → tabla de presupuestos, columna "Cargado"
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Cargar un presupuesto a las 22:30 de Argentina (19/11/2026). 2. Abrir la ficha de la obra y mirar "Cargado". (Para no esperar a la noche, se puso `created_at` del presupuesto 2 en `2026-11-20 01:30:00` UTC = 19/11 22:30 en Argentina, y en `2026-11-19 02:59:00` UTC = 18/11 23:59; después se restauró el valor original `2026-11-19 12:29:45`.)
- **Esperado:** "19/11/2026 · Diego" (y "18/11/2026" en el segundo caso): la fecha de Argentina, como se arregló el historial en B-01.
- **Obtenido:** "20/11/2026 · Diego" y "19/11/2026 · Diego": un día después. La API manda `"created_at":"2026-11-20"` para el presupuesto (fecha de calendario ya cortada en UTC), así que el front no lo puede corregir. Control con la hora original (12:29 UTC): "19/11/2026", bien. Reproducido 2 veces (dos horarios) por API y por pantalla.
- **Evidencia:** `capturas/LEAD-CARGADO-1.png` (20/11/2026), `capturas/LEAD-CARGADO-2.png`, `capturas/LEAD-CARGADO-control.png` · `GET /api/obras/2` → 200
- **Causa probable (opcional):** `app/Modules/Obras/Http/Resources/PresupuestoResource.php:49` `'created_at' => $presupuesto->created_at->toDateString()` con `config/app.php:70` `'timezone' => 'UTC'`. Ya estaba anotado en la causa de B-01 ("no reproducido: por la hora de la prueba").
- **Regresión de:** no; arreglo incompleto de B-01 (el front `380ddaf` pasa a hora de Argentina los instantes con hora, pero esta fecha ya llega cortada en UTC desde el backend)

## QA backend · parte 2 (cortes) — la hizo el Lead, con nadie más probando (13:37–14:05 UTC)
Scripts de `borradores/scripts/BK/` adaptados a esta máquina (`cortes-app.mjs` con Chromium `--lang=es-AR`, `api-con-sesion.mjs`, medidor de health cada ~3 s). Navegador con la sesión de Diego: A = pestaña ya cargada en Obras que durante el corte va a Proveedores; B = pestaña nueva abierta durante el corte; después de que vuelve el servicio **no se toca nada** y se mira a +8/+15/+35/+70 s.

| PU | Estado | Evidencia | Nota |
|---|---|---|---|
| PU-020 · Redis caído | ✅ | `logs/PU-020-r4-redis.log`, `capturas/BK-redis-*.png` | `redis-cli SHUTDOWN SAVE` 13:37:21 UTC. Health **503** con `"redis": false, "cache": false, "cola_mail": false, "database": true` en 1,09–3,54 s (9 mediciones; 8 ≤ 2,4 s, una de 3,54 s con el navegador reintentando al mismo tiempo contra `artisan serve`, que atiende de a uno). 200 en la primera medición después de levantar (13:38:12). Sesiones sobrevivieron (99 claves, Diego 4 × 200 después). |
| PU-202 · MySQL parado de verdad | ✅ | `logs/PU-202-r4-mysql.log`, `logs/errores-canal-2026-11-19.log` | `mysqladmin shutdown` 13:40:42 UTC (proceso y puerto 3306 ausentes a las 13:40:46). Con sesión, 2 vueltas × 4 rutas: **503** "El sistema no está disponible en este momento. Probá de nuevo en unos minutos." con `Retry-After=30` en 1,11–3,37 s; nunca 500 ni sin respuesta. Sin sesión: 401 "Tu sesión venció. Volvé a ingresar." en 0,8 s. Health 503 `"database": false` en 1,08–4,48 s (30 mediciones, 24 ≤ 3,1 s; las más lentas coinciden con los reintentos del navegador sobre `artisan serve`). Canal `errors`: 37 entradas `SQLSTATE[HY000] [2002]` con `user_id` 1 (Diego) entre 13:40 y 13:42; **ningún 4xx** en todo el día. |
| PU-203 · la pantalla abierta se recupera sola | ✅ | `capturas/BK-redis-*.png`, `capturas/BK-mysql-*.png` | Redis: B mostró "No pudimos cargar esta información · El sistema no está disponible en este momento. Probá de nuevo en unos minutos. Apenas vuelva, esta pantalla se recupera sola." con "Reintentar"; A conservó la barra con "Cargando proveedores…" solo en la lista. A y B con datos a los **+8 s** sin tocar. MySQL (+ Redis, ver nota): B con el mismo aviso; A con datos a +8 s y B a +15 s, sin tocar. `visibilidad=visible` en todas las mediciones. |
| PU-138 · Redis/MySQL caído no tapa la app | ✅ | ídem | A (sesión ya cargada) conserva la barra en los dos cortes; la pestaña nueva B muestra el aviso entendible con "se recupera sola"; nunca 500. |
| PU-021 (control) · worker de mail parado | ✅ | `logs/PU-021-r4-wmail.log` | Código pedido por pantalla (qa-lead-r4bk) 14:02:40 UTC con el worker de mail parado: "Te mandamos un código a … · Si el mail está registrado, te llegó un código.", `LLEN queues:mail = 1`. Health **503 `"cola_mail": false`** a las 14:04:41 (121 s). Worker relanzado 14:04:50: `EnviarCodigoJob` y `CodigoIngresoMail` DONE, **un solo** mail a ese usuario, health 200 a las 14:04:55, `failed_jobs` 0. |

**Notas de entorno de los cortes:**
- Los `queue:work` murieron en cada caída de Redis (`Error while writing bytes to the server`), como en las rondas anteriores (en producción los reinicia Supervisord). El Lead los relanzó con la misma línea de `config/supervisor.php`, verificando antes la base con tinker. `schedule:work` sobrevivió a todos los cortes.
- Al levantar MySQL desde Laragon (13:43:27), **Laragon bajó también Redis** (ya pasó en la ronda 2): Redis quedó caído hasta las 14:00:08, cuando el Lead lo relanzó desde `C:\Users\GREEN` (su carpeta de trabajo, con el `dump.rdb` del `SHUTDOWN SAVE`: 99 claves, sesiones intactas). Por eso la recuperación de PU-203 "con MySQL" es, en rigor, tras MySQL **y** Redis.
- `POST /api/auth/pedir-codigo` por curl sin cookie CSRF → 419 "Tu sesión venció. Volvé a ingresar." Solo pasa fuera de la app (la pantalla pide la cookie antes): no es hallazgo.
- Ingresos/códigos del día: 7 (Lead) + 5 (REG) + 1 (PU-021) = 13 de 20 por hora.

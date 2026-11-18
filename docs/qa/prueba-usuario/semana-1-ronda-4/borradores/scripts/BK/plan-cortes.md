# BK · Ronda 4 · Parte 2 · Procedimiento de cortes (PU-202, PU-203, PU-020, PU-138 + worker de mail)

Lo coordina el Lead **con todos los testers en pausa** (cada corte deja sin sistema a todos). Duración estimada: ~30 min (Redis 6–8 min, MySQL 8–10 min, worker de mail 5 min, controles).
Carpeta de scripts: `C:\Users\juanc\AppData\Local\Temp\claude\C--Users-juanc\3fd8de0f-88e6-40e4-a658-bb42248ba145\scratchpad\qa4\BK\` (abajo `BK\`).

## 0. Qué cambió y qué se valida

| PU | Arreglo | Qué hace el código (leído, solo lectura) |
|---|---|---|
| PU-202 (back) | `8b8166a` + `47f4883` | `ConectorMySqlSinReintentos` (bind de `db.connector.mysql`): **un solo intento de conexión por pedido o job**; si falla por conexión (`ServicioCaido::es`), lo recuerda en `BasesCaidas` (`scoped`) y el resto del pedido falla al instante. `DB_CONNECT_TIMEOUT` = 1 (el backend de la prueba lo tiene en `qa-env.ps1`). `ErrorLogContext::userId()` saca el id del guard en uso o de la sesión `web` **sin ir a la base** → la entrada del canal `errors` se escribe. Resultado esperado: 503 `"El sistema no está disponible en este momento. Probá de nuevo en unos minutos."` + `Retry-After: 30` en ~1 s. |
| PU-206 (back) | `9454a20` | Todo 401 → `"Tu sesión venció. Volvé a ingresar."` (ya verificado sin sesión en la parte 1). |
| PU-203 (front) | `aa04a4d` | `queryClient.ts`: `refetchInterval: reintentoSiServicioCaido` → una query en error **503 o sin respuesta** se vuelve a pedir sola cada 10 s (`SESION_REINTENTO_MS`); apenas carga, se apaga. `ConsultaError` agrega `"Apenas vuelva, esta pantalla se recupera sola."` solo en ese caso. `refetchIntervalInBackground: false` (no reintenta con la pestaña oculta). |

**Riesgos a mirar (del código, no reproducidos):**
- **R1:** el reintento automático solo corre con **503 o red**. Si con MySQL caído algún pedido sigue dando **500**, la pantalla **no** se recupera sola y no dice "se recupera sola" (PU-202 arrastra a PU-203). Mirar el status de cada request en `cortes-app-mysql.log`.
- **R2:** `refetchIntervalInBackground: false`: si la pestaña figura oculta no reintenta. El script registra `visibilidad=` en cada estado: tiene que decir `visible`; si no, la corrida no vale para PU-203.
- **R3:** `artisan serve` atiende de a un pedido: con el navegador reintentando cada 10 s y el medidor del health cada 3 s se encolan. Por eso la medición de PU-202 con curl/fetch va **antes** de abrir el navegador en el mismo corte (o con el navegador quieto) y el tiempo se lee del log del script.
- **R4:** el canal `errors` no debe tener 4xx (el 401 sin sesión, ni con base caída). Con MySQL caído, cada 503 tiene que dejar su entrada (`PDOException`/`QueryException` con `[2002]`, `user_id` de Diego).
- **R5:** los `queue:work` mueren con el corte de Redis (`stopWorkerIfLostConnection`) y quizás con el de MySQL (un job que toque la base): relanzarlos con `relanzar-workers.ps1` (no a mano).

## 1. Lecciones de la ronda 3 (reglas del procedimiento)

1. **Nunca `Stop-Process` por un PID guardado sin comprobar antes su línea de comando.** En la ronda 3, `Stop-Process` al PID de un worker ya muerto mató a `redis-server`, que había reusado ese PID. Usar siempre:
   ```powershell
   function Parar-Si([int]$Id, [string]$Firma) {
     $c = (Get-CimInstance Win32_Process -Filter "ProcessId=$Id" -ErrorAction SilentlyContinue).CommandLine
     if (-not $c) { "pid $Id ya no existe: nada que parar"; return }
     if ($c -notlike "*$Firma*") { "pid $Id es OTRO programa ($c): NO lo paro"; return }
     Stop-Process -Id $Id; "parado $Id ($c)"
   }
   ```
   Redis y MySQL **no** se paran con `Stop-Process`: Redis con `redis-cli shutdown save`, MySQL con `mysqladmin shutdown` (MariaDB 10.4 con Aria puede quedar corrupta si se mata).
2. **MySQL se relanza igual que lo levantó el Lead.** Comprobado en la parte 1 (14:00): el `mysqld` que corre es el pid **25360**, iniciado 12:20:13, línea `"C:\xampp\mysql\bin\mysqld.exe" --defaults-file=mysql\bin\my.ini --standalone`, lanzado con `Start-Process … -WorkingDirectory C:\xampp -WindowStyle Hidden` (ver `semana-1-ronda-3\borradores\bk.md`, incidente b). **Ojo, contradicción a confirmar con el Lead:** según el borrador de la ronda 3, lo que se colgó 6 min fue el `mysqld` lanzado con `Invoke-CimMethod Win32_Process Create` (sin consola: aceptaba TCP sin saludar, 1 núcleo al 100 %); `Start-Process -WindowStyle Hidden` **sí** crea consola (oculta) y respondió `select 1` en 2 s. Regla: **nunca CIM**. Usar `Start-Process` con consola; si el Lead prefiere verla, la variante visible es la misma línea sin `-WindowStyle Hidden`. Si a los 60 s no responde `select 1`, mirar `C:\xampp\mysql\data\*.err` antes de tocar nada.
3. **Los workers mueren en cada corte de Redis:** relanzar con `BK\relanzar-workers.ps1` (dot-sourcea `qa4\qa-env.ps1`, comprueba con tinker que apunta a `tecnoservicios_prueba_usuario`, no mata nada, no duplica, respalda y actualiza `proc4\pids.txt`).
4. No escribir ni editar sesiones en Redis. No tocar "Cerrar sesión". El navegador del script usa `sesiones\diego.json` solo para navegar.
5. Arrancar `cortes-app.mjs` **antes** del corte y esperar la línea `[A antes] … alerta=no` (en la ronda 3 una corrida quedó inválida por cortar antes de que arrancara el navegador).

## 2. Antes de empezar

```powershell
$BK = 'C:\Users\juanc\AppData\Local\Temp\claude\C--Users-juanc\3fd8de0f-88e6-40e4-a658-bb42248ba145\scratchpad\qa4\BK'
C:\redis\redis-cli.exe PING                                                   # PONG
C:\xampp\mysql\bin\mysql.exe --user=root --host=127.0.0.1 -e "select 1"      # 1
"{0:N1} GB libres" -f ((Get-CimInstance Win32_OperatingSystem).FreePhysicalMemory/1MB)   # en la parte 1 había 3,1 GB (< 6 GB del §3): avisar
powershell -NoProfile -ExecutionPolicy Bypass -File "$BK\linea-base.ps1"      # health 200, failed_jobs 0, procesos vivos con su línea de comando
node "$BK\api-con-sesion.mjs" antes 1                                          # 4 × 200 con la sesión de Diego
node "$BK\cortes-app.mjs" seco --seco                                          # prueba de selectores con todo arriba (1 Chromium, ~30 s)
New-Item -ItemType Directory -Force "$BK\senal" | Out-Null; Remove-Item "$BK\senal\caido","$BK\senal\arriba" -ErrorAction SilentlyContinue
$B = 'B:\ProgramFiles\NexoSmart\projects\tecnoservicios_project\tecnoservicios_backend\storage\logs'
(Get-Item "$B\errors-2026-11-18.log").Length; (Get-Item "$B\laravel-2026-11-18.log").Length   # anotar: se extraen solo las líneas nuevas
```
Los logs del back están en **UTC** (local + 3 h).

Cada corte usa **3 consolas**:
1. **Health:** `powershell -File "$BK\medir-health.ps1" -Etiqueta <redis|mysql|wmail> -Segundos 400` (cada 3 s → `BK\health-<etiqueta>.log`).
2. **App:** `node "$BK\cortes-app.mjs" <redis|mysql>` (Diego; un Chromium headless; capturas `BK-<etiqueta>-*.png` en `semana-1-ronda-4\capturas\`; log `BK\cortes-app-<etiqueta>.log`). Mira A (sesión cargada en Obras → durante el corte va a Proveedores) y B (pestaña nueva abierta durante el corte), y a +8/+15/+35/+70 s **sin tocar**.
3. **Operador:** corta, crea las señales, mide y levanta.

## 3. Corte A · Redis (PU-020, PU-203, PU-138)

```powershell
# (consola App ya mostró "[A antes] … alerta=no"; consola Health corriendo)
C:\redis\redis-cli.exe shutdown save                       # conserva las sesiones en dump.rdb
New-Item -ItemType File "$BK\senal\caido" | Out-Null
# sin sesión (503 o 401 JSON en español, nunca 500, < 3 s):
curl.exe -s -w " %{http_code} %{time_total}s`n" -H "Accept: application/json" http://127.0.0.1:8340/api/obras/1
# con sesión (503, nunca 500):
node "$BK\api-con-sesion.mjs" redis 1
# esperar ~25 s más (la App toma "B caído"), después levantar con consola oculta, como estaba:
Start-Process C:\redis\redis-server.exe -ArgumentList 'redis.windows.conf' -WorkingDirectory C:\redis -WindowStyle Hidden
C:\redis\redis-cli.exe PING                                 # PONG
# cuando medir-health muestre 200:
New-Item -ItemType File "$BK\senal\arriba" | Out-Null
node "$BK\api-con-sesion.mjs" redis-despues 1               # 200: la sesión de Diego sobrevivió
powershell -NoProfile -ExecutionPolicy Bypass -File "$BK\relanzar-workers.ps1"   # relanza wdef/wmail (y sched si murió); actualiza pids.txt
```
**Esperado:**
- Health caído: **503 en < 3 s** con `"redis": false`, `"cache": false`, `"cola_mail": false`, `"database": true`; **200** en la primera medición después de levantar.
- App, durante el corte: `/api/me` y `/api/proveedores` → **503**. A conserva la barra; el contenido muestra "No pudimos cargar esta información · El sistema no está disponible en este momento. Probá de nuevo en unos minutos. **Apenas vuelva, esta pantalla se recupera sola.**" (`se-recupera-sola=1`). B (pestaña nueva) muestra lo mismo.
- **PU-203:** después de `arriba`, sin tocar, a +8/+15/+35/+70 s **A y B** sin alerta y con datos (`alerta=no`, `links-a-fichas>0`); en la red aparece un `GET /api/proveedores → 200` nuevo **sin clic** (≤ ~13 s: intervalo de 10 s + 2 reintentos). `visibilidad=visible`.
- Diego sigue logueado (no aparece "Ingresar").
- `errors`: entradas Predis (`StreamInitException`/`ConnectionException`) de los 503; **ningún 4xx**. `failed_jobs` sin filas nuevas.

## 4. Corte B · MySQL parado de verdad (PU-202, PU-203, PU-138)

```powershell
Remove-Item "$BK\senal\caido","$BK\senal\arriba" -ErrorAction SilentlyContinue
# 1) PRIMERO curl/fetch sin navegador (R3). Health corriendo.
C:\xampp\mysql\bin\mysqladmin.exe --user=root --host=127.0.0.1 shutdown
Wait-Process -Id 25360 -Timeout 60 -ErrorAction SilentlyContinue   # (25360 = mysqld actual; comprobar con Get-Process mysqld antes)
Get-Process mysqld -ErrorAction SilentlyContinue                    # vacío = parado de verdad
Get-NetTCPConnection -LocalPort 3306 -State Listen -ErrorAction SilentlyContinue   # vacío
node "$BK\api-con-sesion.mjs" mysql 2                               # reproducción 1 y 2: /api/me, /api/proveedores, /api/obras, /api/obras/1
curl.exe -s -w " %{http_code} %{time_total}s`n" -H "Accept: application/json" http://127.0.0.1:8340/api/obras/1   # sin sesión: 401 rápido
# 2) Con MySQL todavía caído, levantar la App: (otra consola) node "$BK\cortes-app.mjs" mysql
#    ⚠ el script necesita la app ARRIBA para "[A antes]": por eso para PU-203 con MySQL hay que
#    volver a levantar, arrancar cortes-app, y hacer un segundo corte (paso 3).
# levantar (igual que el Lead; NUNCA Invoke-CimMethod):
Start-Process C:\xampp\mysql\bin\mysqld.exe -ArgumentList '--defaults-file=mysql\bin\my.ini','--standalone' -WorkingDirectory C:\xampp -WindowStyle Hidden
1..30 | % { $o = C:\xampp\mysql\bin\mysql.exe --user=root --host=127.0.0.1 -N -e "select 1" 2>$null; if ($o -eq 1) { "select 1 OK a los $_ x2 s"; break }; Start-Sleep 2 }
# 3) Corte con la App: arrancar  node "$BK\cortes-app.mjs" mysql  y esperar "[A antes] … alerta=no"
C:\xampp\mysql\bin\mysqladmin.exe --user=root --host=127.0.0.1 shutdown
New-Item -ItemType File "$BK\senal\caido" | Out-Null
# esperar ~30 s (la App toma A y B caídos), levantar como arriba y, con select 1 OK y health 200:
New-Item -ItemType File "$BK\senal\arriba" | Out-Null
powershell -NoProfile -ExecutionPolicy Bypass -File "$BK\relanzar-workers.ps1"   # por si algún worker murió
```
Anotar el pid del `mysqld` nuevo y su línea de comando (`Get-CimInstance Win32_Process -Filter "Name='mysqld.exe'"`).

**Esperado:**
- **PU-202:** cada pedido con sesión → **503** `"El sistema no está disponible en este momento. Probá de nuevo en unos minutos."` con `Retry-After=30` en **~1–3 s**; nunca 500, nunca 30 s ni `SIN-RESPUESTA`. Las dos vueltas del script iguales (= reproducido 2 veces).
- Health: **503 en < 3 s** (dev midió 1,05 s) con `"database": false`, `"redis": true`, `"cache": true`, `"cola_mail": true`.
- **Canal `errors`:** una entrada por cada 503 (`[2002]`, `PDOException`/`QueryException`), con `user_id` de Diego; en `GET /api/errors` aparecen; **ninguna** entrada del 401 sin sesión. En `api.out` no aparece `Maximum execution time`.
- **PU-203 con MySQL:** igual que en Redis: A con barra y "Apenas vuelva, esta pantalla se recupera sola."; B igual; las dos vuelven solas sin tocar (≤ ~13 s después de que la base responda).
- Al volver, health 200 en la primera medición.

## 5. Corte C · Worker de mail (control PU-021)

```powershell
$p = Get-Content "$env:QA_LOGS\pids.txt"  # o C:\...\scratchpad\proc4\pids.txt → wmail=<id>
Parar-Si -Id <wmail> -Firma 'queue:work redis --queue=mail'          # SOLO el worker de mail, comprobando la línea de comando
powershell -File "$BK\medir-health.ps1" -Etiqueta wmail -Segundos 200
# 1 código con el usuario de BK (gasta 1 de los 20/h del equipo; NO ingresar con él):
curl.exe -s -w " %{http_code} %{time_total}s`n" -H "Accept: application/json" -H "Content-Type: application/json" -H "Origin: http://localhost:5316" -d "{\"email\":\"qa-lead-r4bk@empresa-ejemplo.com\"}" http://localhost:8340/api/auth/pedir-codigo
C:\redis\redis-cli.exe LLEN tecnoservicios_prueba_queues:mail       # 1 pendiente (si la clave lleva otro prefijo, KEYS '*queues*')
# a los ~120 s (HEALTH_QUEUE_MAX_AGE) → 503 con "cola_mail": false. Entonces:
powershell -NoProfile -ExecutionPolicy Bypass -File "$BK\relanzar-workers.ps1" -Solo wmail
```
**Esperado:** health 503 `"cola_mail": false` a los ~120–125 s; al relanzar, el mail sale **una sola vez** (un `To: qa-lead-r4bk@…` en `laravel-2026-11-18.log`; `linea-base.ps1` marca duplicados) y health vuelve a 200. `failed_jobs` 0.

## 6. Después de cada corte

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File "$BK\linea-base.ps1"      # health, failed_jobs, errors (archivo + GET /api/errors), laravel.log, mails, procesos con su línea de comando
```
- Extraer **solo** las líneas del corte a `semana-1-ronda-4\logs\` (`PU-202-r4.log`, `PU-203-r4.log`, `PU-020-r4.log`, `BK-NN.log`), sin cookies, tokens ni códigos.
- Avisar a los testers que recarguen y sigan; confirmar con `node "$BK\api-con-sesion.mjs" despues 1` que Diego sigue con sesión (las demás personas comparten el mismo Redis).
- Al final, `pids.txt` tiene que listar procesos vivos con su línea de comando correcta (lo marca `linea-base.ps1`).

## 7. Criterio de cierre

| PU | ✅ si… |
|---|---|
| PU-202 | MySQL parado: todo pedido con sesión → 503 "El sistema no está disponible…" en ~1–3 s (2 vueltas), nunca 500 ni 30 s; entrada en el canal `errors` por cada uno. |
| PU-203 | Tras el corte de Redis **y** el de MySQL, A (abierta) y B (nueva) vuelven solas sin tocar, y durante el corte dicen "Apenas vuelva, esta pantalla se recupera sola.". |
| PU-020 | Redis caído: health 503 < 3 s con `"redis": false`; 200 al volver; sesiones sobreviven; pantalla recuperada sola. |
| PU-138 | Redis y MySQL: 503 (nunca 500); A conserva la barra; B "se recupera sola"; ambas vuelven solas. |
| PU-021 (control) | `"cola_mail": false` a los ~2 min; al relanzar, 1 mail y health 200. |

# BK ronda 4 · Línea de base (solo lectura). Uso: powershell -NoProfile -File linea-base.ps1  → agrega una corrida a linea-base.log
# Los logs del back están en UTC: 14:00 local = 17:00 UTC. El token de /api/errors se lee del archivo y solo va en el header (nunca se escribe).
$ErrorActionPreference = 'Continue'
$BACK  = 'B:\ProgramFiles\NexoSmart\projects\tecnoservicios_project\tecnoservicios_backend'
$SCR   = 'C:\Users\juanc\AppData\Local\Temp\claude\C--Users-juanc\3fd8de0f-88e6-40e4-a658-bb42248ba145\scratchpad'
$PROC  = "$SCR\proc4"
$OUT   = Join-Path $PSScriptRoot 'linea-base.log'
$MYSQL = 'C:\xampp\mysql\bin\mysql.exe'
$fecha = '2026-11-18'
$desdeUtc = '2026-11-18 17:00:00'   # 14:00 local
$r = New-Object System.Collections.Generic.List[string]
$r.Add("===== corrida $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss') (local) · libre $([math]::Round((Get-CimInstance Win32_OperatingSystem).FreePhysicalMemory/1MB,1)) GB =====")

function Pedir($url, $headers = @{}) {
  $sw = [Diagnostics.Stopwatch]::StartNew()
  try { $h = Invoke-WebRequest -UseBasicParsing -Uri $url -Headers $headers -TimeoutSec 40; $st = $h.StatusCode; $b = $h.Content }
  catch { $resp = $_.Exception.Response; if ($resp) { $st = [int]$resp.StatusCode; $b = (New-Object IO.StreamReader($resp.GetResponseStream())).ReadToEnd() } else { $st = 'SIN-RESPUESTA'; $b = $_.Exception.Message } }
  $sw.Stop(); [pscustomobject]@{ st = $st; s = [math]::Round($sw.Elapsed.TotalSeconds, 2); b = $b }
}

# 1. Health
$h = Pedir 'http://127.0.0.1:8340/api/health' @{ Accept = 'application/json' }
$r.Add("health: $($h.st) en $($h.s) s · $($h.b)")

# 2. failed_jobs (+ jobs en la base, por si alguno no fue a Redis) y cola en Redis
$fj = & $MYSQL --user=root --host=127.0.0.1 -N tecnoservicios_prueba_usuario -e "select concat('failed_jobs=',count(*)) from failed_jobs; select concat('jobs(db)=',count(*)) from jobs; select concat(id,' ',queue,' ',failed_at,' ',left(replace(exception,'\n',' '),200)) from failed_jobs order by id desc limit 5;" 2>&1
$r.Add('base: ' + ($fj -join ' | '))
$q = foreach ($c in 'default','mail') { "$c=" + (& C:\redis\redis-cli.exe LLEN "tecnoservicios_prueba_queues:$c") + "/" + (& C:\redis\redis-cli.exe ZCARD "tecnoservicios_prueba_queues:$c:delayed") + "/" + (& C:\redis\redis-cli.exe ZCARD "tecnoservicios_prueba_queues:$c:reserved") }
$r.Add('cola redis (pendientes/demorados/reservados): ' + ($q -join ' '))

# 3. Canal errors: archivo
$ef = Join-Path $BACK "storage\logs\errors-$fecha.log"
if (Test-Path $ef) {
  $ent = Get-Content $ef -Encoding UTF8 | Select-String '^\[(\d{4}-\d\d-\d\d \d\d:\d\d:\d\d)\]'
  $nuevas = $ent | Where-Object { $_.Matches[0].Groups[1].Value -ge $desdeUtc }
  $r.Add("errors-$fecha.log: $($ent.Count) entradas; desde 17:00 UTC (14:00 local): $($nuevas.Count)")
  $nuevas | Select-Object -Last 10 | ForEach-Object { $r.Add('  ' + $_.Line.Substring(0, [Math]::Min(260, $_.Line.Length))) }
} else { $r.Add("errors-$fecha.log: no existe") }

# 3b. GET /api/errors (Bearer; el token no se imprime)
$tok = (Get-Content "$SCR\qa4\errors-token.txt" -Raw).Trim()
$e = Pedir "http://127.0.0.1:8340/api/errors?date=$fecha" @{ Accept = 'application/json'; Authorization = "Bearer $tok" }
Remove-Variable tok
if ($e.st -eq 200) {
  $j = $e.b | ConvertFrom-Json
  $err = @($j.errors)
  $nuev = @($err | Where-Object { "$($_.timestamp)" -ge $desdeUtc })
  $clases = $err | Group-Object exception_class | ForEach-Object { "$($_.Name)=$($_.Count)" }
  $r.Add("GET /api/errors: 200 en $($e.s) s · total=$($j.total) · desde 14:00 local=$($nuev.Count) · clases: " + ($clases -join ', '))
  # ¿algún 4xx en el canal? (excepciones HTTP < 500, auth, validación, 404 de modelo)
  $cuatro = @($err | Where-Object { $_.exception_class -match 'Authentication|Authorization|Validation|NotFound|ModelNotFound|ThrottleRequests|MethodNotAllowed|AccessDenied|TokenMismatch|Unauthorized|Conflict' })
  $r.Add("  entradas tipo 4xx en el canal: $($cuatro.Count)")
  $nuev | Select-Object -Last 10 | ForEach-Object { $r.Add("  $($_.timestamp) $($_.exception_class) $($_.method) $($_.url) :: $("$($_.message)".Substring(0,[Math]::Min(150,"$($_.message)".Length)))") }
} else { $r.Add("GET /api/errors: $($e.st) en $($e.s) s · $($e.b.Substring(0,[Math]::Min(200,$e.b.Length)))") }

# 4. laravel log: niveles != DEBUG y excepciones desde 17:00 UTC; mails duplicados
$lf = Join-Path $BACK "storage\logs\laravel-$fecha.log"
if (Test-Path $lf) {
  $ll = Get-Content $lf -Encoding UTF8
  $cab = $ll | Select-String '^\[(\d{4}-\d\d-\d\d \d\d:\d\d:\d\d)\] \w+\.(\w+):'
  $niv = $cab | ForEach-Object { $_.Matches[0].Groups[2].Value } | Group-Object | ForEach-Object { "$($_.Name)=$($_.Count)" }
  $nd = @($cab | Where-Object { $_.Matches[0].Groups[1].Value -ge $desdeUtc -and $_.Matches[0].Groups[2].Value -ne 'DEBUG' })
  $r.Add("laravel-$fecha.log: $($ll.Count) líneas; niveles: $($niv -join ', ') · no-DEBUG desde 14:00 local: $($nd.Count)")
  $nd | Select-Object -Last 10 | ForEach-Object { $r.Add('  ' + $_.Line.Substring(0, [Math]::Min(260, $_.Line.Length))) }
  $exc = @($ll | Select-String 'Exception|Stack trace|#0 ' )
  $r.Add("  líneas con Exception/traza en todo el archivo: $($exc.Count)")
  $ts = $null; $mails = @()
  foreach ($l in $ll) {
    if ($l -match '^\[([^\]]+)\] \w+\.DEBUG: From:') { $ts = $Matches[1] }
    elseif ($ts -and $l -match '^To: (.+)$') { $mails += [pscustomobject]@{ t = $ts; to = $Matches[1] }; $ts = $null }
  }
  $dups = $mails | Group-Object { "$($_.t)|$($_.to)" } | Where-Object Count -gt 1
  # mismo destinatario dentro de 5 s (doble envío del mismo pedido)
  $cerca = @(); $porTo = $mails | Group-Object to
  foreach ($g in $porTo) { $ord = $g.Group | Sort-Object t; for ($i = 1; $i -lt $ord.Count; $i++) { if (([datetime]$ord[$i].t - [datetime]$ord[$i-1].t).TotalSeconds -le 5) { $cerca += "$($ord[$i].t) $($g.Name)" } } }
  $r.Add("mails: $($mails.Count) (desde 14:00 local: $(@($mails | Where-Object { $_.t -ge $desdeUtc }).Count)) · duplicados mismo seg: $(if ($dups) { ($dups | % Name) -join '; ' } else { 'ninguno' }) · mismo To en <=5 s: $(if ($cerca) { $cerca -join '; ' } else { 'ninguno' })")
} else { $r.Add("laravel-$fecha.log: no existe") }

# 5. Procesos: vivos + que el PID siga siendo el mismo programa (PID reusado = lección ronda 3)
$pids = Get-Content (Join-Path $PROC 'pids.txt') | Where-Object { $_ -match '=' } | ForEach-Object { $k, $v = $_ -split '='; [pscustomobject]@{ n = $k; p = [int]$v } }
$esperado = @{ api = 'artisan serve'; wdef = 'queue:work redis --queue=default'; wmail = 'queue:work redis --queue=mail'; sched = 'schedule:work'; front = 'npm' }
$r.Add('procesos: ' + (($pids | ForEach-Object {
  $c = (Get-CimInstance Win32_Process -Filter "ProcessId=$($_.p)" -ErrorAction SilentlyContinue).CommandLine
  $ok = if (-not $c) { 'MUERTO' } elseif ($esperado[$_.n] -and $c -notlike "*$($esperado[$_.n])*") { "OTRO-PROGRAMA($c)" } else { 'vivo' }
  "$($_.n)=$($_.p):$ok" }) -join ' '))
$r.Add('servicios: mysqld=' + ((Get-Process mysqld -ErrorAction SilentlyContinue | % Id) -join ',') + ' redis-server=' + ((Get-Process redis-server -ErrorAction SilentlyContinue | % Id) -join ','))
foreach ($f in Get-ChildItem $PROC -File | Where-Object { $_.Extension -in '.out', '.err' -and $_.Name -notlike 'front*' }) {
  $oc = @(Get-Content $f.FullName -ErrorAction SilentlyContinue)
  $fail = @($oc | Select-String 'FAIL|Exception|ERROR|Fatal|Warning' | Where-Object { $_.Line -notmatch '/api/errors' })
  $r.Add("$($f.Name): $($oc.Count) líneas · FAIL/Exception/ERROR/Fatal: $($fail.Count)")
  $fail | Select-Object -Last 5 | ForEach-Object { $r.Add('  ' + $_.Line.Substring(0, [Math]::Min(220, $_.Line.Length))) }
}
# 5xx en la salida de artisan serve (api.err lleva una línea por pedido con el tiempo; no lleva status)
$r.Add('')
$r | Out-File -Append -Encoding utf8 $OUT
$r -join "`n"

# BK ronda 4 · parte 2: relanza SOLO los procesos de cola/scheduler que estén muertos, con las variables de qa4\qa-env.ps1,
# y actualiza proc4\pids.txt. Nunca mata nada: un PID "vivo" solo cuenta si su línea de comando es la esperada
# (lección ronda 3: un PID reusado era Redis). Uso: powershell -NoProfile -File relanzar-workers.ps1 [-Solo wmail]
param([string]$Solo = '')
$ErrorActionPreference = 'Stop'
. 'C:\Users\juanc\AppData\Local\Temp\claude\C--Users-juanc\3fd8de0f-88e6-40e4-a658-bb42248ba145\scratchpad\qa4\qa-env.ps1'
Set-Location $QA_BACK
$pidsTxt = Join-Path $QA_LOGS 'pids.txt'
$def = [ordered]@{
  wdef  = @{ firma = 'queue:work redis --queue=default'; args = @('artisan','queue:work','redis','--queue=default','--sleep=3','--tries=3','--backoff=10') }
  wmail = @{ firma = 'queue:work redis --queue=mail';    args = @('artisan','queue:work','redis','--queue=mail','--sleep=1','--rest=1','--tries=5','--backoff=30') }
  sched = @{ firma = 'schedule:work';                     args = @('artisan','schedule:work') }
}
# 1. La base a la que van a apuntar (tiene que ser la de la prueba)
$base = (& C:\Php\php.exe artisan tinker --execute="echo DB::connection()->getDatabaseName();").Trim()
if ($base -ne 'tecnoservicios_prueba_usuario') { throw "tinker apunta a '$base': NO relanzo" }
$actual = [ordered]@{}
Get-Content $pidsTxt | Where-Object { $_ -match '=' } | ForEach-Object { $k, $v = $_ -split '='; $actual[$k] = [int]$v }
Copy-Item $pidsTxt "$pidsTxt.bak-$(Get-Date -Format HHmmss)"
$sufijo = Get-Date -Format HHmmss
foreach ($n in $def.Keys) {
  if ($Solo -and $n -ne $Solo) { continue }
  $id = $actual[$n]
  $cl = if ($id) { (Get-CimInstance Win32_Process -Filter "ProcessId=$id" -ErrorAction SilentlyContinue).CommandLine } else { $null }
  if ($cl -and $cl -like "*$($def[$n].firma)*") { "$n=$id sigue vivo ($cl): no se toca"; continue }
  if ($cl) { "$n=$id ahora es OTRO programa ($cl): NO se toca, se relanza el worker igual" }
  # ¿Hay otro proceso con la misma firma vivo (relanzado a mano)? Entonces no duplicar.
  $otro = Get-CimInstance Win32_Process -Filter "Name='php.exe'" | Where-Object { $_.CommandLine -like "*$($def[$n].firma)*" } | Select-Object -First 1
  if ($otro) { "$n ya corre como $($otro.ProcessId): actualizo pids.txt"; $actual[$n] = $otro.ProcessId; continue }
  $p = Start-Process C:\Php\php.exe -ArgumentList $def[$n].args -WorkingDirectory $QA_BACK -WindowStyle Hidden -PassThru `
        -RedirectStandardOutput (Join-Path $QA_LOGS "$n-$sufijo.out") -RedirectStandardError (Join-Path $QA_LOGS "$n-$sufijo.err")
  "$n relanzado: pid $($p.Id) (salida $n-$sufijo.out)"
  $actual[$n] = $p.Id
}
($actual.Keys | ForEach-Object { "$_=$($actual[$_])" }) | Set-Content -Encoding ascii $pidsTxt
Get-Content $pidsTxt

# BK ronda 4 · idempotencia de las migraciones 2026_11_18_* sobre la base PROPIA tecnoservicios_prueba_r4_mig (nunca la de la prueba).
# Uso: powershell -NoProfile -File migraciones.ps1   (agrega a migraciones.log)
$ErrorActionPreference = 'Continue'
. 'C:\Users\juanc\AppData\Local\Temp\claude\C--Users-juanc\3fd8de0f-88e6-40e4-a658-bb42248ba145\scratchpad\qa4\qa-env.ps1'
$env:DB_DATABASE = 'tecnoservicios_prueba_r4_mig'
Set-Location $QA_BACK
$log = Join-Path $PSScriptRoot 'migraciones.log'
$MYSQL = 'C:\xampp\mysql\bin\mysql.exe'
function Estado($t) {
  "--- estado $t"
  & $MYSQL --user=root --host=127.0.0.1 --default-character-set=utf8mb4 -N tecnoservicios_prueba_r4_mig -e "select concat('adj ',id,' = ',nombre) from adjuntos; select concat('act ',id,' ',accion,' ',cambios) from actividades; select concat('migrations=',count(*),' max_batch=',max(batch)) from migrations;"
}
$chk = & C:\Php\php.exe artisan tinker --execute="echo DB::connection()->getDatabaseName();"
if ("$chk".Trim() -ne 'tecnoservicios_prueba_r4_mig') { "ABORTO: tinker apunta a '$chk'" | Tee-Object -Append -FilePath $log; exit 1 }
$all = @("=== $(Get-Date -Format HH:mm:ss) tinker -> $chk") + (Estado 'antes')
$pasos = @(
  @('migrate:rollback', '--step=2'),
  @('migrate'),
  @('migrate')
)
foreach ($p in $pasos) {
  $argv = @('artisan') + $p + @('--force', '--no-interaction')
  $o = & C:\Php\php.exe $argv 2>&1; $rc = $LASTEXITCODE
  $all += "=== $(Get-Date -Format HH:mm:ss) php artisan $($p -join ' ') --force -> rc=$rc"
  $all += $o
  $all += (Estado "después de $($p -join ' ')")
}
$all | Out-File -Append -Encoding utf8 $log
$all

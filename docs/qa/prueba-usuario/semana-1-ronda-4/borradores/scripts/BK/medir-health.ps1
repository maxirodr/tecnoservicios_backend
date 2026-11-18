# BK · ronda 4 · parte 2: mide /api/health cada 3 s (límite del health: 60/min) y escribe status, segundos y servicios.
# Uso: powershell -File medir-health.ps1 -Etiqueta redis -Segundos 300
param([string]$Etiqueta = 'corte', [int]$Segundos = 300)
$OUT = Join-Path $PSScriptRoot "health-$Etiqueta.log"
$fin = (Get-Date).AddSeconds($Segundos)
while ((Get-Date) -lt $fin) {
  $sw = [Diagnostics.Stopwatch]::StartNew()
  try { $h = Invoke-WebRequest -UseBasicParsing 'http://127.0.0.1:8340/api/health' -TimeoutSec 30; $st = $h.StatusCode; $b = $h.Content }
  catch { $resp = $_.Exception.Response; if ($resp) { $st = [int]$resp.StatusCode; $b = (New-Object IO.StreamReader($resp.GetResponseStream())).ReadToEnd() } else { $st = 'SIN-RESPUESTA'; $b = $_.Exception.Message } }
  $sw.Stop()
  $srv = try { (($b | ConvertFrom-Json).data.services | ConvertTo-Json -Compress) } catch { $b }
  $l = "$(Get-Date -Format HH:mm:ss) $st $([math]::Round($sw.Elapsed.TotalSeconds,2))s $srv"
  $l | Out-File -Append -Encoding utf8 $OUT; $l
  Start-Sleep -Seconds 3
}

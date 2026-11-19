<?php

declare(strict_types=1);

// Tareas programadas (Schedule::command(...)). Corren con `schedule:work` bajo Supervisord, nunca crontab.

use Illuminate\Support\Facades\Schedule;

// Adjuntos temporales no asociados: fila y archivo a las 24 h (ADR-014, RN-IDE-45).
Schedule::command('adjuntos:purgar-huerfanos')->hourly()->withoutOverlapping();

// Idempotency-Key vencidas (24 h, ADR-011).
Schedule::command('idempotencia:purgar')->hourly()->withoutOverlapping();

// Recordatorio "vence mañana" (F-37, ADR-012): cada hora; sale desde las 8:00 de Argentina (DU-P21) y no se
// repite por el dedupe_key, aunque corra dos veces.
Schedule::command('avisos:recordatorios')->hourly()->withoutOverlapping();

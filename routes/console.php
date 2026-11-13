<?php

declare(strict_types=1);

// Tareas programadas (Schedule::command(...)). Corren con `schedule:work` bajo Supervisord, nunca crontab.

use Illuminate\Support\Facades\Schedule;

// Idempotency-Key vencidas (24 h, ADR-011).
Schedule::command('idempotencia:purgar')->hourly()->withoutOverlapping();

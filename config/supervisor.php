<?php

declare(strict_types=1);

/*
 * Procesos en segundo plano. Todo worker y el scheduler corren bajo Supervisord (nunca crontab).
 * `php artisan supervisor:sync` genera un .conf por programa en `directory` y recarga supervisor.
 */

return [
    'directory' => env('SUPERVISOR_CONF_DIR') ?: storage_path('supervisor'),

    // auto: recarga si supervisorctl está disponible y no es Windows · always · never
    'reload' => env('SUPERVISOR_RELOAD', 'auto'),

    'program_prefix' => env('SUPERVISOR_PROGRAM_PREFIX', 'tecnoservicios'),

    'php_binary' => env('SUPERVISOR_PHP_BINARY', 'php'),

    'user' => env('SUPERVISOR_USER', 'www-data'),

    'log_directory' => env('SUPERVISOR_LOG_DIR') ?: storage_path('logs'),

    'programs' => [
        'worker-default' => [
            'command' => 'queue:work redis --queue=default --sleep=3 --tries=3 --backoff=10 --max-time=3600',
            'numprocs' => 1,
            'stopwaitsecs' => 3600,
        ],
        // Mail: un solo proceso que descansa 1 s después de cada job → como máximo 1 mail por segundo.
        'worker-mail' => [
            'command' => 'queue:work redis --queue=mail --sleep=1 --rest=1 --tries=5 --backoff=30 --max-time=3600',
            'numprocs' => 1,
            'stopwaitsecs' => 120,
        ],
        'scheduler' => [
            'command' => 'schedule:work',
            'numprocs' => 1,
            'stopwaitsecs' => 60,
        ],
    ],
];

<?php

declare(strict_types=1);

/*
 * GET /api/health (bug-report NexoSmart). PU-020 / PU-021.
 */
return [

    // Un job pendiente más viejo que esto (segundos) en alguna de estas colas = el worker no está levantando.
    'cola_max_segundos' => (int) env('HEALTH_QUEUE_MAX_AGE', 120),

    'colas' => ['mail', 'default'],

    // Límite propio por IP, fuera de Redis: el health tiene que responder aunque Redis esté caído.
    'pedidos_por_minuto' => (int) env('HEALTH_REQUESTS_PER_MINUTE', 60),

    'limite_cache_store' => env('HEALTH_LIMIT_STORE', 'file'),

];

<?php

declare(strict_types=1);

/*
 * CORS para la SPA (Sanctum stateful con cookie): orígenes explícitos y credenciales habilitadas.
 * Nunca '*' con supports_credentials.
 */

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout', 'forgot-password', 'reset-password'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173')),
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];

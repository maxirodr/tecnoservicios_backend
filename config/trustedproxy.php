<?php

declare(strict_types=1);

/*
 * Proxies de confianza (SEG-04): solo de estas IPs (o CIDR) se aceptan X-Forwarded-For/-Proto/-Host.
 * TRUSTED_PROXIES separado por comas; vacío = ninguno (la IP es la de la conexión). Producción: la
 * IP del nginx que está delante (p. ej. 127.0.0.1). Nunca `*`: permitiría falsificar la IP y
 * esquivar los límites por IP del ingreso y el ban de /api/errors.
 */
return [
    'proxies' => array_values(array_filter(
        array_map(trim(...), explode(',', (string) env('TRUSTED_PROXIES', ''))),
        static fn (string $proxy): bool => $proxy !== '',
    )),
];

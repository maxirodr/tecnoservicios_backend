<?php

declare(strict_types=1);

/*
 * Adjuntos: archivo subido o link (ADR-014). El disco es privado y configurable para poder pasar
 * a un almacenamiento de objetos sin tocar el código. PHP (upload_max_filesize, post_max_size) y
 * nginx (client_max_body_size) tienen que aceptar al menos `max_mb`.
 */

return [
    'disk' => env('ADJUNTOS_DISK', 'adjuntos'),

    'max_mb' => (int) env('ADJUNTOS_MAX_MB', 20),

    'subidas_por_minuto' => (int) env('ADJUNTOS_SUBIDAS_POR_MINUTO', 30),

    // Un adjunto temporal (no asociado a ningún recurso) se purga pasado este plazo.
    'horas_huerfano' => 24,
];

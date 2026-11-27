<?php

declare(strict_types=1);

/*
 * Carga inicial (importacion.md RN-IMP-12): los .xlsx de la minuta y las rendiciones y los informes viven en una
 * carpeta privada del servidor, fuera del repositorio y de `public`. Los comandos solo leen archivos de ahí.
 */

return [
    'dir' => env('IMPORTACION_DIR') ?: storage_path('app/private/importacion'),

    // RN-IMP-06: una corrida a la vez; el bloqueo se libera solo si el proceso muere.
    'bloqueo_segundos' => 3600,
];

<?php

declare(strict_types=1);

return [
    // URL del front (SPA): los links de los mails de cuenta apuntan a su pantalla "elegir contraseña".
    'frontend_url' => rtrim((string) env('FRONTEND_URL', 'http://localhost:5310'), '/'),

    // RN-IDE-26: dominios de mail permitidos para dar de alta usuarios, separados por coma. Vacío = sin restricción.
    'dominios_permitidos' => array_values(array_filter(array_map(
        static fn (string $dominio): string => strtolower(trim($dominio)),
        explode(',', (string) env('MAIL_DOMINIOS_PERMITIDOS', '')),
    ))),
];

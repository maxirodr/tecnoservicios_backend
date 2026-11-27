<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Domain\Informe;

/** Una fila de la hoja "Avisos" (§5.8): entra igual, pero conviene mirarlo. No es un aviso de la campana. */
final readonly class Aviso
{
    public function __construct(
        public string $hoja,
        public ?int $fila,
        public string $sujeto,
        public string $que,
    ) {}
}

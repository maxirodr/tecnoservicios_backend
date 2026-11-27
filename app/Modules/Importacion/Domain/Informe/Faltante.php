<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Domain\Informe;

/**
 * Una fila de la hoja "Faltantes" (§5.8): lo que hay que completar para que la obra o la tarea entre. Bloquea
 * siempre. `fila` null = no es de una fila sola (el control contra su planilla, la carga vigente).
 */
final readonly class Faltante
{
    public function __construct(
        public string $hoja,
        public ?int $fila,
        public string $sujeto,
        public string $columna,
        public string $que,
        public string $loCompleta,
    ) {}
}

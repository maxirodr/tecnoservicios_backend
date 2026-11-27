<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Application\Corridas;

use App\Modules\Importacion\Domain\Resultado;

/** Lo que queda en `importacion_filas` (§11.3): la clave y lo creado, sin valores de celdas (RN-IMP-13). */
final readonly class FilaDeCorrida
{
    public function __construct(
        public string $hoja,
        public string $clave,
        public Resultado $resultado,
        public ?string $sujetoType = null,
        public ?int $sujetoId = null,
    ) {}
}

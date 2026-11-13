<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\Obras;

/** ObraFicha (obras.md §10.1): la fila + los permisos para mostrar u ocultar botones. */
final readonly class FichaObra
{
    /**
     * @param  array<string, bool>  $puede
     */
    public function __construct(
        public FilaObra $fila,
        public array $puede,
    ) {}
}

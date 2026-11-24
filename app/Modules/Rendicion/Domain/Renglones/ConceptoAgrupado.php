<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Domain\Renglones;

final readonly class ConceptoAgrupado
{
    /**
     * @param  numeric-string  $cantidad  escala 3
     * @param  numeric-string  $total  escala 2
     */
    public function __construct(
        public string $concepto,
        public string $cantidad,
        public string $total,
    ) {}
}

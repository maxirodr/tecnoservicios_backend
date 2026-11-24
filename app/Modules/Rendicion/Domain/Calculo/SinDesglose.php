<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Domain\Calculo;

/** La fila "Sin desglose por rubro" (N2, RN-REN-10, RN-REN-11): lo de los presupuestos cargados solo total. */
final readonly class SinDesglose
{
    /**
     * @param  numeric-string  $presupuestado
     * @param  list<string>  $presupuestos  números de esos presupuestos ("1-200-2026 A1")
     */
    public function __construct(
        public string $presupuestado,
        public array $presupuestos,
    ) {}
}

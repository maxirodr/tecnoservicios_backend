<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Domain\Calculo;

use App\Modules\Obras\Contracts\PresupuestoDeObra;

/** Lo que muestra la tabla de W-11 (y W-44, W-12, W-05), ya calculado (rendicion.md §4.3). */
final readonly class ResumenDeRendicion
{
    /**
     * @param  list<PresupuestoDeObra>  $presupuestos
     * @param  list<string>  $sinCargar  números de los presupuestos sin montos (RN-REN-12)
     * @param  list<FilaDeRubro>  $rubros  los 12 del resumen, en el orden de su hoja General
     */
    public function __construct(
        public Situacion $situacion,
        public array $presupuestos,
        public array $sinCargar,
        public array $rubros,
        public ?SinDesglose $sinDesglose,
        public TotalDeObra $total,
    ) {}

    public function rubro(string $clave): ?FilaDeRubro
    {
        foreach ($this->rubros as $fila) {
            if ($fila->clave === $clave) {
                return $fila;
            }
        }

        return null;
    }
}

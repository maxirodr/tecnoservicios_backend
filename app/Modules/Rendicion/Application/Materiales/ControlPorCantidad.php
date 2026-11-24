<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Application\Materiales;

use App\Modules\Gastos\Contracts\RenglonImputado;
use App\Modules\Rendicion\Domain\Materiales\ColumnasDeFechas;
use App\Modules\Rendicion\Domain\Models\Material;

/** W-48 (rendicion.md §12.2 `ControlPorCantidad`). */
final readonly class ControlPorCantidad
{
    /**
     * @param  list<FilaDeMaterial>  $materiales
     * @param  numeric-string  $totalLista
     * @param  numeric-string  $gastadoEnMateriales
     * @param  numeric-string|null  $sinMaterial  la diferencia con el gastado de MATERIALES; null si coinciden
     * @param  list<array{material: Material, renglon: RenglonImputado, rubroNombre: string}>  $fueraDePresupuesto
     * @param  array<string, bool>  $puede
     */
    public function __construct(
        public ColumnasDeFechas $columnas,
        public array $materiales,
        public string $totalLista,
        public string $gastadoEnMateriales,
        public ?string $sinMaterial,
        public array $fueraDePresupuesto,
        public array $puede,
    ) {}
}

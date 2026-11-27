<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Application\Obras;

use App\Modules\Obras\Contracts\CargaInicialDeObras;
use App\Shared\CargaInicial\CargaRechazada;

/**
 * Las obras de la hoja Obras por su N.º principal normalizado (RN-OBR-11): las otras hojas nombran la obra como la
 * escriben ("1-200-2026", "1 200 2026") y se encuentra igual.
 */
final readonly class LoteDeObras
{
    /**
     * @param  array<string, ObraDelLote>  $obras
     */
    public function __construct(
        public array $obras,
        private CargaInicialDeObras $contrato,
    ) {}

    public function buscar(?string $escrito): ?ObraDelLote
    {
        if ($escrito === null || $escrito === '') {
            return null;
        }

        try {
            return $this->obras[$this->contrato->numero($escrito)] ?? null;
        } catch (CargaRechazada) {
            return null;
        }
    }
}

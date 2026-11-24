<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Infrastructure;

use App\Modules\Obras\Application\Ports\AvanceGastoDeObra;
use App\Modules\Rendicion\Application\Rendicion\RendicionesDeVariasObras;
use App\Modules\Rendicion\Domain\Calculo\ResumenDeRendicion;
use Illuminate\Database\QueryException;

/**
 * Rendicion implementa el "% del presupuesto gastado" de Obras (RN-REN-42, reemplaza a `AvanceGastoDeObraNulo`):
 * N9 por obra (null sin presupuesto cargado o con presupuestado 0) y N11, con una consulta por agregado para
 * toda la página. A quién se le muestra lo decide Obras (RN-OBR-04). Si la lectura falla, el listado no se
 * cae: se registra el error y se ve "—" (rendicion.md §6).
 */
final readonly class AvanceGastoDeObraEloquent implements AvanceGastoDeObra
{
    public function __construct(private RendicionesDeVariasObras $rendiciones) {}

    public function porcentajesGastados(array $obraIds): array
    {
        return array_map(static fn (?ResumenDeRendicion $r): ?string => $r?->total->porcentaje, $this->resumenes($obraIds));
    }

    public function pasadas(array $obraIds): array
    {
        return array_map(static fn (?ResumenDeRendicion $r): bool => $r !== null && $r->total->pasado, $this->resumenes($obraIds));
    }

    /**
     * @param  list<int>  $obraIds
     * @return array<int, ResumenDeRendicion|null>
     */
    private function resumenes(array $obraIds): array
    {
        try {
            $resumenes = $this->rendiciones->de($obraIds);
        } catch (QueryException $error) {
            report($error);
            $resumenes = [];
        }

        $porObra = [];
        foreach ($obraIds as $id) {
            $porObra[$id] = $resumenes[$id] ?? null;
        }

        return $porObra;
    }
}

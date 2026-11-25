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
final class AvanceGastoDeObraEloquent implements AvanceGastoDeObra
{
    /** El listado pide % y pasadas de las mismas obras: se calcula una vez (RN-REN-42). @var array<string, array<int, ResumenDeRendicion|null>> */
    private array $calculadas = [];

    public function __construct(private readonly RendicionesDeVariasObras $rendiciones) {}

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
        $clave = implode(',', $obraIds);

        return $this->calculadas[$clave] ??= $this->calcular($obraIds);
    }

    /**
     * @param  list<int>  $obraIds
     * @return array<int, ResumenDeRendicion|null>
     */
    private function calcular(array $obraIds): array
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

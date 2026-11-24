<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Application\Rendicion;

use App\Modules\Gastos\Contracts\FiltroRenglones;
use App\Modules\Gastos\Contracts\GastadoDeObras;
use App\Modules\Gastos\Contracts\RenglonImputado;
use App\Modules\Obras\Contracts\ObraResumen;
use App\Modules\Rendicion\Domain\Calculo\Decimal;
use App\Modules\Rendicion\Domain\Calculo\FilaDeRubro;
use App\Modules\Rendicion\Domain\Renglones\OrdenDeRenglones;
use App\Shared\Http\ApiResponse;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * F-51 (W-36): todos los renglones de un rubro del resumen, sin páginas (RN-REN-31). INSUMOS trae también los
 * de EPP, con su subtotal aparte (RN-REN-15, resuelta DU-R07); EPP solo, o un rubro que no existe, da 404.
 */
final readonly class DetalleDeRubroQuery
{
    public const SIN_DETALLE = 'Ese rubro no tiene detalle propio.';

    public function __construct(
        private RendicionDeObraQuery $rendicion,
        private GastadoDeObras $gastado,
    ) {}

    /**
     * @param  'fecha'|'proveedor'|'total'  $orden
     * @param  'asc'|'desc'|null  $dir
     * @return array{fila: FilaDeRubro, subtotales: array<string, numeric-string>, renglones: list<RenglonImputado>}
     *
     * @throws HttpResponseException 404 si el rubro no tiene fila propia en el resumen
     */
    public function handle(ObraResumen $obra, string $clave, string $orden, ?string $dir): array
    {
        $fila = $this->rendicion->resumen($obra->id)->rubro($clave);

        if ($fila === null) {
            throw new HttpResponseException(ApiResponse::error(self::SIN_DETALLE, 404));
        }

        $renglones = $this->gastado->renglonesDeObra($obra->id, new FiltroRenglones(rubroClaves: [$fila->clave, ...$fila->incluye], orden: $orden, dir: $dir ?? OrdenDeRenglones::direccionPorDefecto($orden)));
        $subtotales = [];

        foreach ($fila->incluye as $incluida) {
            $subtotales[$incluida] = Decimal::suma(array_map(
                static fn (RenglonImputado $r): string => $r->total,
                array_filter($renglones, static fn (RenglonImputado $r): bool => $r->rubroClave === $incluida),
            ));
        }

        return [
            'fila' => $fila,
            'subtotales' => $subtotales,
            'renglones' => OrdenDeRenglones::ordenar($renglones, $orden, $dir ?? OrdenDeRenglones::direccionPorDefecto($orden)),
        ];
    }
}

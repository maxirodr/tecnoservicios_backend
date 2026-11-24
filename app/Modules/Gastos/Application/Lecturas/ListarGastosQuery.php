<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Lecturas;

use App\Modules\Gastos\Application\Acceso\AccesoGasto;
use App\Modules\Gastos\Application\Rubros;
use App\Modules\Gastos\Domain\Models\Gasto;
use App\Modules\Gastos\Infrastructure\Consultas\CriterioDeRenglones;
use App\Modules\Gastos\Infrastructure\Consultas\FechaEntre;
use App\Modules\Gastos\Infrastructure\Consultas\FiltroDeRenglones;
use App\Modules\Gastos\Infrastructure\Consultas\FiltroGastos;
use App\Modules\Gastos\Infrastructure\Consultas\GastosQuery;
use App\Modules\Gastos\Infrastructure\Consultas\PorObra;
use App\Modules\Gastos\Infrastructure\Consultas\PorProveedor;
use App\Modules\Gastos\Infrastructure\Consultas\PorRubro;
use App\Modules\Gastos\Infrastructure\Consultas\PorTipoDocumento;
use App\Modules\Gastos\Infrastructure\Consultas\SinObra;
use App\Modules\Gastos\Infrastructure\Consultas\VisiblesPara;
use App\Modules\Identidad\Domain\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * F-45 / HU-071 · W-33: gastos cargados con filtros componibles, el alcance siempre antes (RN-GAS-48), de a
 * 50, el total de lo filtrado sobre todo (RN-GAS-32), los anulados contados aparte (RN-GAS-34) y, si se
 * pide, agrupado como sus tablas dinámicas (RN-GAS-33).
 */
final readonly class ListarGastosQuery
{
    public function __construct(
        private AccesoGasto $acceso,
        private Rubros $rubros,
        private LecturaDeGasto $lectura,
        private AgruparGastos $agrupar,
    ) {}

    /**
     * @return array{pagina: LengthAwarePaginator<int, Gasto>, filas: list<GastoLeido>, meta: array<string, mixed>}
     */
    public function handle(User $user, FiltrosDeGastos $filtros): array
    {
        $cabecera = $this->cabecera($filtros);
        $criterio = $this->criterio($user, $filtros);

        $pagina = GastosQuery::ordenados(GastosQuery::filtrados($cabecera, $criterio))
            ->with(LecturaDeGasto::FILA)
            ->paginate(FiltrosDeGastos::POR_PAGINA);

        $filas = array_map(fn (Gasto $g): GastoLeido => $this->lectura->de($user, $g, $criterio), array_values($pagina->items()));
        $totales = GastosQuery::totales($cabecera, $criterio);

        $meta = [
            'total_filtrado' => $totales['total'],
            'comprobantes' => $totales['comprobantes'],
            'anulados' => $totales['anulados'],
            'desde' => $filtros->desde,
            'hasta' => $filtros->hasta,
        ];

        if ($filtros->agrupar !== null) {
            $todos = GastosQuery::ordenados(GastosQuery::filtrados($cabecera, $criterio))->with(LecturaDeGasto::FILA)->get();
            $meta['grupos'] = $this->agrupar->handle(
                $filtros->agrupar,
                array_values($todos->map(fn (Gasto $g): GastoLeido => $this->lectura->de($user, $g, $criterio))->all()),
                $criterio,
            );
        }

        return ['pagina' => $pagina, 'filas' => $filas, 'meta' => $meta];
    }

    /**
     * @return list<FiltroGastos>
     */
    private function cabecera(FiltrosDeGastos $filtros): array
    {
        $cabecera = [new FechaEntre($filtros->desde, $filtros->hasta)];

        if ($filtros->proveedorId !== null) {
            $cabecera[] = new PorProveedor($filtros->proveedorId);
        }

        if ($filtros->tipo !== null) {
            $cabecera[] = new PorTipoDocumento($filtros->tipo);
        }

        return $cabecera;
    }

    private function criterio(User $user, FiltrosDeGastos $filtros): CriterioDeRenglones
    {
        /** @var list<FiltroDeRenglones> $renglones */
        $renglones = [];
        $visibles = $this->acceso->obrasVisibles($user);

        if ($visibles !== null) {
            $renglones[] = new VisiblesPara($visibles);
        }

        if ($filtros->sinObra) {
            $renglones[] = new SinObra;
        } elseif ($filtros->obraId !== null) {
            $renglones[] = new PorObra($filtros->obraId);
        }

        $rubroId = $filtros->rubro === null ? null : $this->rubros->idDe($filtros->rubro);

        if ($rubroId !== null) {
            $renglones[] = new PorRubro($rubroId);
        }

        return new CriterioDeRenglones($renglones);
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Application\Rendicion;

use App\Modules\Gastos\Contracts\FiltroRenglones;
use App\Modules\Gastos\Contracts\GastadoDeObras;
use App\Modules\Identidad\Domain\Enums\ClaveFuncion;
use App\Modules\Identidad\Domain\Enums\Nivel;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Contracts\ObraResumen;
use App\Modules\Obras\Contracts\ObrasConsulta;
use App\Modules\Rendicion\Application\Acceso\AccesoRendicion;
use App\Modules\Rendicion\Domain\Calculo\CalculoDeRendicion;
use App\Modules\Rendicion\Domain\Calculo\MontoYGanancia;
use App\Modules\Rendicion\Domain\Calculo\ResumenDeRendicion;
use App\Modules\Rendicion\Domain\Renglones\OrdenDeRenglones;
use Carbon\CarbonImmutable;

/**
 * F-49 · F-54 (W-11, W-44, pestaña Rendición de W-07): la rendición de la obra, calculada en el momento con lo
 * gastado (cacheado por obra) y lo presupuestado; el monto y los permisos se leen en cada pedido (RN-REN-60).
 */
final readonly class RendicionDeObraQuery
{
    public const ULTIMOS_GASTOS = 10;

    public function __construct(
        private ObrasConsulta $obras,
        private GastadoEnCache $gastado,
        private GastadoDeObras $renglones,
        private CalculoDeRendicion $calculo,
        private AccesoRendicion $acceso,
    ) {}

    public function resumen(int $obraId): ResumenDeRendicion
    {
        return $this->calculo->calcular($this->obras->presupuestadoPorRubro($obraId), $this->gastado->porRubro($obraId));
    }

    public function handle(User $user, ObraResumen $obra): RendicionDeObra
    {
        $resumen = $this->resumen($obra->id);
        $verMonto = $this->acceso->puede($user, ClaveFuncion::MontoObra, Nivel::Ver, $obra);
        $monto = $verMonto ? $this->obras->montoDeObra($obra->id) : null;
        $ultimos = $this->renglones->renglonesDeObra($obra->id, new FiltroRenglones(limite: self::ULTIMOS_GASTOS));

        return new RendicionDeObra(
            $obra,
            $resumen,
            $verMonto,
            $monto === null ? null : MontoYGanancia::de($monto, $resumen->total),
            array_slice(OrdenDeRenglones::ordenar($ultimos, FiltroRenglones::ORDEN_FECHA, 'desc'), 0, self::ULTIMOS_GASTOS),
            $this->puede($user, $obra),
            CarbonImmutable::now(config()->string('app.zona_empresa')),
        );
    }

    /**
     * Accesos de la pantalla (rendicion.md §7.2); la API de cada uno vuelve a verificar (RN-IDE-14).
     *
     * @return array<string, bool>
     */
    private function puede(User $user, ObraResumen $obra): array
    {
        $cargarDesdeRendicion = $this->acceso->puede($user, ClaveFuncion::Rendicion, Nivel::Editar, $obra);
        $verGastos = $this->acceso->puede($user, ClaveFuncion::Gastos, Nivel::Ver, $obra);

        return [
            'ver_monto_obra' => $this->acceso->puede($user, ClaveFuncion::MontoObra, Nivel::Ver, $obra),
            'abrir_gastos' => $verGastos,
            'ver_todos_los_gastos' => $verGastos,
            'cargar_sin_documento' => $cargarDesdeRendicion || $this->acceso->puede($user, ClaveFuncion::ControlCantidad, Nivel::Editar, $obra),
            'cargar_periodo' => $cargarDesdeRendicion,
            'ver_control_cantidad' => $this->acceso->puede($user, ClaveFuncion::ControlCantidad, Nivel::Ver, $obra),
            'ver_presupuesto_rubro' => $this->acceso->puede($user, ClaveFuncion::PresupuestoRubro, Nivel::Ver, $obra),
        ];
    }
}

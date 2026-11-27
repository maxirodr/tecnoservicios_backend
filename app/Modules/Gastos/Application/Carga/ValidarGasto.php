<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Carga;

use App\Modules\Gastos\Domain\Enums\Origen;
use App\Modules\Gastos\Domain\Models\Gasto;
use App\Shared\Adjuntos\AdjuntosService;
use Illuminate\Validation\ValidationException;

/**
 * Las reglas de negocio de la carga (gastos.md §4.3 y §5), las mismas al cargar y al corregir
 * (RN-GAS-37). Devuelve todos los errores juntos, cada uno en su campo o renglón; si no hay, el gasto listo
 * para guardar. Lo que necesita la base con el gasto bloqueado (repetidos) lo mira `CargaRepetida`.
 */
final readonly class ValidarGasto
{
    /** El remito de un retiro: 0 a 1 (gastos.md §4.4). */
    public const MAX_REMITOS = 1;

    public function __construct(
        private ReglasDeCabecera $cabecera,
        private ReglasDeRenglones $renglones,
    ) {}

    /**
     * @throws ValidationException
     */
    public function handle(DatosGasto $datos): GastoAGuardar
    {
        $errores = new Errores;
        $cabecera = $this->cabecera->aplicar($datos, $errores);
        $renglones = $this->renglones->aplicar($datos, $cabecera['fecha'], $errores);
        $this->adjuntos($datos, $errores);

        $errores->lanzarSiHay();

        return new GastoAGuardar(
            $datos->origen,
            $cabecera['numero'],
            (string) $cabecera['fecha'],
            (int) $cabecera['proveedorId'],
            $cabecera['rendidoPor'],
            $cabecera['desde'],
            $cabecera['hasta'],
            $renglones,
        );
    }

    private function adjuntos(DatosGasto $datos, Errores $errores): void
    {
        $contexto = Gasto::contextoDe($datos->origen);

        if ($datos->adjuntos !== [] && $contexto === null) {
            $errores->campo(AdjuntosService::CAMPO_ERROR, AdjuntosService::MENSAJE_NO_UTILIZABLE);
        }

        // El remito de un retiro y la planilla del parte de horas: 0 a 1 (RN-GAS-52).
        if (($contexto === Gasto::CONTEXTO_REMITO || $datos->origen === Origen::ParteHoras) && count($datos->adjuntos) > self::MAX_REMITOS) {
            $errores->campo(AdjuntosService::CAMPO_ERROR, 'Son demasiados (máximo '.self::MAX_REMITOS.').');
        }
    }
}

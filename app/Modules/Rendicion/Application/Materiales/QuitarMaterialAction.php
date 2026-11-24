<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Application\Materiales;

use App\Modules\Gastos\Contracts\GastadoDeObras;
use App\Modules\Rendicion\Domain\Events\MaterialCambiado;
use App\Modules\Rendicion\Domain\Models\Material;
use App\Shared\Actividad\RegistroActividad;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Connection;
use Illuminate\Validation\ValidationException;

/**
 * RN-REN-53 (HU-082 c4): un material se quita solo si **ningún** renglón lo usa, tampoco uno anulado; si no, se
 * corrige su cantidad presupuestada. Lo quitado queda en `actividades`.
 */
final readonly class QuitarMaterialAction
{
    public const ACCION = 'material_quitado';

    public function __construct(
        private Connection $db,
        private GastadoDeObras $gastado,
        private RegistroActividad $actividad,
        private Dispatcher $eventos,
    ) {}

    /**
     * @throws ValidationException si algún renglón usa el material
     */
    public function handle(Material $material): void
    {
        $usos = $this->gastado->contarRenglonesPorMaterial([$material->id])[$material->id] ?? 0;

        if ($usos > 0) {
            $cuantos = $usos === 1 ? '1 retiro o pedido' : "{$usos} retiros o pedidos";

            throw ValidationException::withMessages(['material' => ["{$material->nombre} ya tiene {$cuantos}: no se puede quitar. Corregí la cantidad presupuestada."]]);
        }

        $this->db->transaction(function () use ($material): void {
            $this->actividad->registrar($material, self::ACCION, HistorialDeMaterial::baja($material));
            $material->delete();
            $this->eventos->dispatch(new MaterialCambiado($material->id, $material->obra_id, MaterialCambiado::QUITADO));
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Correccion;

use App\Modules\Gastos\Application\Acceso\BuscarGasto;
use App\Modules\Gastos\Application\Acceso\GastoAdjuntos;
use App\Modules\Gastos\Domain\Models\Gasto;
use App\Modules\Identidad\Domain\Models\User;
use App\Shared\Concurrencia\VersionDeFila;
use Carbon\CarbonInterface;
use Illuminate\Database\Connection;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;

/**
 * gastos.md §4.4, fila `remito` (QC S3 M-3): sin `gastos = editar`, quien tiene `control_cantidad = editar`
 * (W-48) asocia o quita el remito de un retiro de sus obras con el mismo `PATCH /api/gastos/{id}`, mandando
 * solo `adjuntos` (+ `updated_at`). Cualquier otro cambio sigue pidiendo `gastos = editar` (RN-GAS-37). No
 * cambia importes: no dispara `GastoCorregido`.
 */
final readonly class CambiarRemitoAction
{
    public function __construct(
        private Connection $db,
        private GastoAdjuntos $permisos,
        private AdjuntosDelGasto $adjuntos,
    ) {}

    /**
     * @throws ValidationException|HttpResponseException 403 · 404 · 409 · 422
     */
    public function handle(User $actor, int $id, CambiosGasto $cambios, ?CarbonInterface $version = null): Gasto
    {
        return $this->db->transaction(function () use ($actor, $id, $cambios, $version): Gasto {
            $gasto = Gasto::query()->with('renglones')->lockForUpdate()->find($id);

            if (! $gasto instanceof Gasto || ! $this->permisos->puedeVer($actor, $gasto)) {
                throw BuscarGasto::noEncontrado();
            }

            BuscarGasto::exigir($cambios->soloAdjuntos() && $this->permisos->puedeEditar($actor, $gasto));
            VersionDeFila::bloquearYVerificar($gasto, $version);

            if ($this->adjuntos->reemplazar($actor->id, $gasto, $cambios->adjuntos ?? [])) {
                $gasto->updated_by = $actor->id;
                $gasto->touch();
            }

            return $gasto;
        });
    }
}
